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
            $referer = $_SERVER['HTTP_REFERER'];
            $id = explode("/", $referer)[4];
        } else
            $id = Yii::$app->user->identity->username;
        $isExecution = !!ExecutionHandler::isExecution($id);
        $isConclusion = !!ExecutionHandler::isConclusion($id);
        $timeLeft = 0;
        // посмотрю, сколько времении ещё будет доступно обследование
        $startTime = User::findByUsername($id)->created_at;
        if (!empty($startTime)) {
            // найдено время старта
            $now = time();
            $lifetime = $startTime + Info::DATA_SAVING_TIME;
            if ($now < $lifetime) {
                $timeLeft = Utils::secondsToTime($lifetime - $now);
            } else {
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
        if (is_dir($executionDir)) {
            self::PackFiles($executionNumber, $executionDir);
            return ['status' => 1, 'header' => '<h2 class="text-center text-success">Успех</h2>', 'message' => '<p class="text-success text-center">Папка найдена и успешно обработана</p>'];
        }
        return ['status' => 1, 'header' => '<h2 class="text-center text-danger">Неудача</h2>', 'message' => '<p class="text-center text-danger">Папка не найдена</p>'];

    }

    public static function rmRec($path)
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
        foreach ($existentFiles as $existentFile) {
            if (preg_match($searchPattern, $existentFile)) {
                $addsQuantity++;
            }
        }
        return $addsQuantity;
    }

    public static function deleteAddConcs($id)
    {
        if (self::isAdditionalConclusions($id)) {
            $searchPattern = '/' . $id . '-[0-9]+\.pdf/';
            $existentFiles = scandir(Info::CONC_FOLDER);
            foreach ($existentFiles as $existentFile) {
                if (preg_match($searchPattern, $existentFile)) {
                    if (is_file($existentFile)) {
                        unlink($existentFile);
                    }
                }
            }
        }
    }

    /**
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function check()
    {
        // автоматическая обработка папок
        $dirs = array_slice(scandir( Yii::getAlias('@executionsDirectory')), 2);
        $pattern =  '/^[aа]?[0-9]+$/ui';
        // проверю папки
        if(!empty($dirs)){
            foreach ($dirs as $dir) {
                $path = Yii::getAlias('@executionsDirectory') . '/' . $dir;
                if(is_dir($path)){
                    // для начала проверю папку, если она изменена менее 10 минут назад- пропускаю её
                    $stat = stat($path);
                    $changeTime = $stat['mtime'];
                    $difference = time() - $changeTime;
                    if($difference > 600){
                        // проверю, соответствует ли название папки шаблону
                        if (preg_match($pattern, $dir)) {
                            $dirLatin = self::toLatin(mb_strtoupper($dir));
                            // вероятно, папка содержит файлы обследования
                            // проверю, что папка не пуста
                            if(count(scandir($path)) > 2){
                                // папка не пуста
                                // проверю, зарегистрирован ли пользователь с данным именем. Если нет- зарегистрирую
                                $user = User::findByUsername($dirLatin);
                                if(empty($user)){
                                    $transaction = new DbTransaction();
                                    self::createUser($dirLatin);
                                    self::startTimer($dirLatin);
                                    $transaction->commitTransaction();
                                }
                                // сохраню содержимое папки в архив
                                self::PackFiles($dirLatin, $path);
                            }
                            else{
                                // удалю папку
                                self::rmRec($path);
                            }

                        }
                        else{
                            // удалю папку
                            self::rmRec($path);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $name
     * @return string
     * @throws Exception
     * @throws \Exception
     */
    public static function createUser($name): string
    {
        $new = new User();
        $password = User::generateNumericPassword();
        $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
        $auth_key = Yii::$app->getSecurity()->generateRandomString(32);
        $new->username = $name;
        $new->auth_key = $auth_key;
        $new->password_hash = $hash;
        $new->status = 1;
        $new->created_at = time();
        $new->save();
        // выдам пользователю права на чтение
        $auth = Yii::$app->authManager;
        $readerRole = $auth->getRole('reader');
        $auth->assign($readerRole, $new->getId());
        return $password;
    }

    public static function toLatin($executionNumber)
    {
        $input = ["А"];
        $replace = ["A"];
        return str_replace($input, $replace, mb_strtoupper($executionNumber));
    }

    /**
     * @param $executionNumber
     * @param string $executionDir
     */
    public static function PackFiles($executionNumber, string $executionDir): void
    {
// скопирую в папку содержимое dicom-просмотровщика
        $viewer_dir = Yii::getAlias('@dicomViewerDirectory');
        self::recurse_copy($viewer_dir, $executionDir);
        $fileWay = Yii::getAlias('@executionsDirectory') . '\\' . $executionNumber . '_tmp.zip';
        $trueFileWay = Yii::getAlias('@executionsDirectory') . '\\' . self::toLatin(mb_strtoupper($executionNumber)) . '.zip';
        // создам архив и удалю исходное
        shell_exec('cd /d ' . $executionDir . ' && "' . Info::WINRAR_FOLDER . '"  a -afzip -r -df  ' . $fileWay . ' .');
        // удалю пустую директорию
        // переименую файл
        rename($fileWay, $trueFileWay);
        rmdir($executionDir);
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
            $password = self::createUser($this->executionNumber);
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

    public static function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}