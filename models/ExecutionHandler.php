<?php


namespace app\models;


use app\priv\Info;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\web\UploadedFile;
use ZipArchive;

class ExecutionHandler extends Model
{
    const SCENARIO_ADD = 'add';

    public static function checkAvailability()
    {
        // получу информацию о пациенте
        if (Yii::$app->user->can('manage')) {
            $referer =  $_SERVER['HTTP_REFERER'];
            $id = explode("/", $referer)[4];
        }
        else
            $id = Yii::$app->user->identity->username;
        $isExecution = !!ExecutionHandler::isExecution($id);
        $isConclusion = !!ExecutionHandler::isConclusion($id);
        $timeLeft = 0;
        // посмотрю, сколько времении ещё будет доступно обследование
        $startTime = User::findByUsername($id)->created_at;
        if(!empty($startTime)){
            // найдено время старта
            $now = time();
            $lifetime = $startTime + Info::DATA_SAVING_TIME;
            if($now < $lifetime){
                $timeLeft = Utils::secondsToTime($lifetime - $now);
            }
            else{
                AdministratorActions::simpleDeleteItem($id);
                return ['status' => 2];
            }
        }

        $addConc = ExecutionHandler::isAdditionalConclusions($id);

        return ['status' => 1, 'execution' => $isExecution, 'conclusion' => $isConclusion, 'timeLeft' => $timeLeft, 'addConc' => $addConc];
    }

    public static function checkFiles($executionNumber)
    {
        $executionDir = Yii::getAlias('@executionsDirectory') . '\\' . $executionNumber;
        if(is_dir($executionDir)){
            $fileWay = Yii::getAlias('@executionsDirectory') . '\\' . $executionNumber . '.zip';
            // Initialize archive object
            $zip = new ZipArchive();
            $zip->open($fileWay, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            // Create recursive directory iterator
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($executionDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file)
            {
                // Skip directories (they would be added automatically)
                if (!$file->isDir())
                {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($executionDir) + 1);
                    // Add current file to archive
                    $zip->addFile($filePath, $relativePath);
                }
            }
            // Zip archive will be created only after closing object
            $zip->close();
            ExecutionHandler::rmRec($executionDir);
            return ['status' => 1, 'header' => '<h2 class="text-center text-success">Успех</h2>', 'view' =>  '<p class="text-success text-center">Папка найдена и успешно обработана</p>'];
        }
        return ['status' => 1, 'header' => '<h2 class="text-center text-danger">Неудача</h2>', 'view' =>  '<p class="text-center text-danger">Папка не найдена</p>'];

    }

    private static function rmRec($path)
    {
        if (is_file($path)) {
            return unlink($path);
        }
        if (is_dir($path)) {
            foreach (scandir($path, SCANDIR_SORT_NONE) as $p) {
                if (($p !== '.') && ($p !== '..')) {
                    ExecutionHandler::rmRec($path . DIRECTORY_SEPARATOR . $p);
                }
            }
            return rmdir($path);
        }
        return false;
    }

    public static function isAdditionalConclusions(string $username)
    {
        $searchPattern = '/' . $username . '-[0-9]+\.pdf/';
        $existentFiles = scandir(Info::CONC_FOLDER);
        $addsQuantity = 0;
        foreach ($existentFiles as $existentFile){
            if(preg_match($searchPattern, $existentFile)){
                $addsQuantity++;
            }
        }
        return $addsQuantity;
    }

    public static function deleteAddConcs($id)
    {
        if(self::isAdditionalConclusions($id)){
            $searchPattern = '/' . $id . '-[0-9]+\.pdf/';
            $existentFiles = scandir(Info::CONC_FOLDER);
            foreach ($existentFiles as $existentFile){
                if(preg_match($searchPattern, $existentFile)){
                    if(is_file($existentFile)){
                        unlink($existentFile);
                    }
                }
            }
        }
    }

    public static function toLatin($executionNumber)
    {
        $input = ["А"];
        $replace = ["A"];
        return str_replace($input, $replace, mb_strtoupper($executionNumber));
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_ADD => ['executionNumber', 'executionData', 'executionResponse'],
        ];
    }

    public $executionNumber;
    /**
     * @var UploadedFile
     */
    public $executionData;
    /**
     * @var UploadedFile
     */
    public $executionResponse;

    public function attributeLabels(): array
    {
        return [
            'executionNumber' => 'Номер обследования',
            'executionData' => 'Данные обследования',
            'executionResponse' => 'Заключение',
        ];
    }

    public function rules(): array
    {
        return [
            [['executionData'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip', 'maxSize' => 1048576000],
            [['executionResponse'], 'file', 'skipOnEmpty' => true, 'extensions' => 'pdf', 'maxSize' => 104857600],
            [['executionNumber'], 'required', 'on' => self::SCENARIO_ADD],
            ['executionNumber', 'string', 'length' => [1, 255]],
            ['executionNumber', 'match', 'pattern' => '/^[а-яa-z0-9]+$/iu']
        ];
    }

    /**
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function register()
    {
        if ($this->validate()) {
            $transaction = new DbTransaction();
            if (empty($this->executionNumber)) {
                return ['status' => 2, 'message' => 'Не указан номер обследования'];
            }
            // todo Сделать замену букв в номере на латиницу
            $this->executionNumber = self::toLatin($this->executionNumber);
            // проверю, не зарегистрировано ли уже обследование
            if (!empty(User::findByUsername($this->executionNumber))) {
                return ['status' => 4, 'message' => 'Это обследование уже зарегистрировано, вы можете изменить информацию о нём в списке'];
            }
            if (!empty($this->executionData)) {
                // сохраняю данные в папку с обследованиями
                $filename = Yii::getAlias('@executionsDirectory') . '\\' . $this->executionNumber . '.zip';
                $this->executionData->saveAs($filename);
                $this->startTimer($this->executionNumber);
            }
            if (!empty($this->executionResponse)) {
                // сохраняю данные в папку с обследованиями
                $filename = Yii::getAlias('@conclusionsDirectory') . '\\' . $this->executionNumber . '.pdf';
                $this->executionResponse->saveAs($filename);

                $this->startTimer($this->executionNumber);
            }
            $new = new User();
            $password = User::generateNumericPassword();
            $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $auth_key = Yii::$app->getSecurity()->generateRandomString(32);
            $new->username = $this->executionNumber;
            $new->auth_key = $auth_key;
            $new->password_hash = $hash;
            $new->status = 1;
            $new->created_at = time();
            $new->save();
            // выдам пользователю права на чтение
            $auth = Yii::$app->authManager;
            $readerRole = $auth->getRole('reader');
            $auth->assign($readerRole, $new->getId());
            $transaction->commitTransaction();
            return ['status' => 1, 'message' => ' <h2 class="text-center">Обследование №' . $this->executionNumber . '  зарегистрировано.</h2> Пароль для пациента: <b class="text-success">' . $password . '</b> <button class="btn btn-default" id="copyPassBtn" data-password="' . $password . '"><span class="text-success">Копировать пароль</span></button>'];
        }
        die('error');
    }

    /**
     * Проверю наличие файлов
     * @param $name
     * @return bool
     */
    public static function isExecution($name)
    {
        $filename = Yii::getAlias('@executionsDirectory') . '\\' . $name . '.zip';
        if (!empty($name) && is_file($filename)) {
            return true;
        }
        return false;
    }

    /**
     * Проверю наличие заключения
     * @param $name
     * @return bool
     */
    public static function isConclusion($name)
    {
        $filename = Yii::getAlias('@conclusionsDirectory') . '\\' . $name . '.pdf';
        if (!empty($name) && is_file($filename)) {
            return true;
        }
        return false;
    }

    public static function startTimer($id)
    {
        // проверю, нет ли ещё в базе данного пациента
        $contains = Table_availability::findOne(['userId' => $id]);
        if (empty($contains)) {
            $timer = new Table_availability();
            $timer->userId = $id;
            $timer->startTime = time();
            $timer->save();
        }
    }
}