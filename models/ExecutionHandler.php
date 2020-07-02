<?php /** @noinspection PhpUndefinedClassInspection */


namespace app\models;


use app\models\database\AuthAssignment;
use app\models\database\TempDownloadLinks;
use app\models\database\ViberSubscriptions;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use app\priv\Info;
use RuntimeException;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\Model;

class ExecutionHandler extends Model
{
    public const SCENARIO_ADD = 'add';

    /**
     * @return array
     * @throws Throwable
     */
    public static function checkAvailability(): array
    {
        // получу информацию о пациенте
        if (Yii::$app->user->can('manage')) {
            $referer = $_SERVER['HTTP_REFERER'];
            $id = explode('/', $referer)[4];
        } else {
            $id = Yii::$app->user->identity->username;
        }
        $user = User::findByUsername($id);
        if ($user !== null) {
            $isExecution = self::isExecution($id);
            $conclusions = Table_availability::getConclusions($id);
            $timeLeft = 0;
            // посмотрю, сколько времении ещё будет доступно обследование
            $startTime = $user->created_at;
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
            return ['status' => 1, 'execution' => $isExecution, 'conclusions' => $conclusions, 'timeLeft' => $timeLeft];
        }
        return [];
    }

    public static function checkFiles($executionNumber): array
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
                    self::rmRec($path . DIRECTORY_SEPARATOR . $p);
                }
            }
            return rmdir($path);
        }
        return false;
    }

    public static function isAdditionalConclusions(string $username): int
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

    public static function deleteAddConcs($id): void
    {
        if (self::isAdditionalConclusions($id)) {
            $searchPattern = '/' . $id . '-[0-9]+\.pdf/';
            $existentFiles = scandir(Info::CONC_FOLDER);
            foreach ($existentFiles as $existentFile) {
                if (preg_match($searchPattern, $existentFile) && is_file($existentFile)) {
                    unlink($existentFile);
                }
            }
        }
    }

    /**
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function check(): void
    {
        FileUtils::writeUpdateLog('error delete dir ');
        echo TimeHandler::timestampToDate(time()) . "start report \n";
        // проверю устаревшие данные
        // получу всех пользователей
        $users = User::findAllRegistered();
        if (!empty($users)) {
            foreach ($users as $user) {
                // ищу данные по доступности обследований.
                if (($user->created_at + Info::DATA_SAVING_TIME) < time()) {
                    AdministratorActions::simpleDeleteItem($user->username);
                    echo TimeHandler::timestampToDate(time()) . "user {$user->username} expired and deleted\n";
                }
            }
        }
        // автоматическая обработка папок
        $entities = array_slice(scandir(Info::EXEC_FOLDER), 2);
        $pattern = '/^[aа]?[0-9]+$/ui';
        // проверю папки
        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $path = Info::EXEC_FOLDER . '/' . $entity;
                if (is_dir($path)) {
                    // для начала проверю папку, если она изменена менее 5 минут назад- пропускаю её
                    $stat = stat($path);
                    $changeTime = $stat['mtime'];
                    $difference = time() - $changeTime;
                    if ($difference > 60) {
                        // проверю, соответствует ли название папки шаблону
                        if (preg_match($pattern, $entity)) {
                            $dirLatin = GrammarHandler::toLatin($entity);
                            // вероятно, папка содержит файлы обследования
                            // проверю, что папка не пуста
                            if (count(scandir($path)) > 2) {
                                // папка не пуста
                                self::checkUser($dirLatin);
                                // сохраню содержимое папки в архив
                                self::PackFiles($dirLatin, $path);
                                echo TimeHandler::timestampToDate(time()) . "dir $entity handled and load to $path \n";
                            } else {
                                // удалю папку
                                try{
                                    self::rmRec($path);
                                    echo TimeHandler::timestampToDate(time()) . "dir $entity is empty and deleted \n";
                                }
                                catch (\Exception $e){
                                    FileUtils::writeUpdateLog('error delete dir ' . $path);
                                }
                            }
                        } else {
                            echo TimeHandler::timestampToDate(time()) . "dir $entity not handled \n";
                        }
                    } else {
                        echo TimeHandler::timestampToDate(time()) . "dir $entity waiting for timeout \n";
                    }
                }
            }
            // теперь перепроверю данные для получения актуальной информации о имеющихся файлах
            $entities = array_slice(scandir(Info::EXEC_FOLDER), 2);
            $pattern = '/^A?[0-9]+.zip$/';
            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    echo TimeHandler::timestampToDate(time()) . " check zip $entity\n";
                    $path = Info::EXEC_FOLDER . '/' . $entity;
                    if (is_file($path) && preg_match($pattern, $entity)) {
                        // найден файл, обработаю информацию о нём
                        $existentFile = Table_availability::findOne(['is_execution' => true, 'file_name' => $entity]);
                        $user = User::findByUsername(GrammarHandler::getBaseFileName($entity));
                        if ($user !== null) {
                            if ($existentFile !== null) {
                                // проверю дату изменения и md5 файлов. Если они совпадают- ничего не делаю, если не совпадают- отправлю в вайбер уведомление об обновлении файла
                                //$md5 = md5_file($path);
                                $stat = stat($path);
                                $changeTime = $stat['mtime'];
                                if ($changeTime !== $existentFile->file_create_time) {
                                //if ($changeTime !== $existentFile->file_create_time && $md5 !== $existentFile->md5) {
                                    // отправлю новую версию файла пользователю
                                    $md5 = md5_file($path);
                                    $existentFile->md5 = $md5;
                                    $existentFile->file_create_time = $changeTime;
                                    $existentFile->save();
                                    Viber::notifyExecutionLoaded($user->username);
                                }
                            } else {
                                // внесу информацию о файле в базу
                                $md5 = md5_file($path);
                                $stat = stat($path);
                                $changeTime = $stat['mtime'];
                                (new Table_availability(['file_name' => $entity, 'is_execution' => true, 'md5' => $md5, 'file_create_time' => $changeTime, 'userId' => $user->username]))->save();
                                // оповещу мессенджеры о наличии файла
                                Viber::notifyExecutionLoaded($user->username);
                            }
                        }
                    }
                }
            }
        }
        $entity = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs';
        if (!is_dir($entity) && !is_dir($entity) && !mkdir($entity) && !is_dir($entity)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $entity));
        }
/*        // теперь обработаю заключения
        $pattern = '/^[aа]?\W?\d+-?\.?\d*\.pdf$/ui';
        $dotPattern = '/^([aа]?\W?\d+)\.(\d+\.pdf)$/ui';
        // проверю папку с заключениями
        $conclusionsDir = Info::CONC_FOLDER;
        if (!empty($conclusionsDir) && is_dir($conclusionsDir)) {
            $files = array_slice(scandir($conclusionsDir), 2);
            foreach ($files as $file) {
                try {
                    $path = Info::CONC_FOLDER . '\\' . $file;
                    if (is_file($path)) {
                        // проверю, подходит ли файл под регулярку
                        if (preg_match($pattern, $file)) {
                            // получу данные о файле
                            $stat = stat($path);
                            $changeTime = $stat['mtime'];
                            $difference = time() - $changeTime;
                            if ($difference > 30) {
                                // переименую файл в нормальный вид
                                $fileLatin = GrammarHandler::toLatin($file);
                                // уберу пробелы
                                $filePureName = preg_replace('/\s/', '', $fileLatin);
                                // заменю разделитель-точку на тире
                                if (preg_match($dotPattern, $file, $arr)) {
                                    // переименую файл
                                    $filePureName = $arr[1] . '-' . $arr[2];
                                    rename($path, Info::CONC_FOLDER . '\\' . $filePureName);
                                    echo TimeHandler::timestampToDate(time()) . "file $file renamed from dot to $filePureName \n";
                                }
                                // проверю наличие учётной записи
                                // если это не дублирующее заключение
                                if (empty(strpos($filePureName, '-'))) {
                                    echo TimeHandler::timestampToDate(time()) . "check user $filePureName\n";
                                    try {
                                        self::checkUser(GrammarHandler::getBaseFileName($filePureName));
                                    } catch (\Exception $e) {
                                        echo 'ERROR WHEN CHECK USER ' . $e->getMessage();
                                    }
                                }
                                // если файл не соответствует строгому шаблону
                                if ($file !== $filePureName) {
                                    try {
                                        rename($path, Info::CONC_FOLDER . '\\' . $filePureName);
                                        echo TimeHandler::timestampToDate(time()) . "file $file renamed to $filePureName \n";
                                    } catch (\Exception $e) {
                                        echo "skipped file $file no renamed to $filePureName with error {$e->getMessage()}\n";
                                    }
                                }
                            } else {
                                echo TimeHandler::timestampToDate(time()) . "file $file in conclusions waiting for timeout \n";
                            }
                        } else {
                            echo TimeHandler::timestampToDate(time()) . "file $file not handled \n";
                        }
                    }
                }
                catch (\Exception $e){
                    echo 'ERROR CHECKING FILE' . $e->getMessage();
                }
            }
        }*/

        echo 'check conclusions actuality';

        // теперь проверю актуальность данных по доступности заключений
        $conclusionsDir = Info::CONC_FOLDER;
        if (!empty($conclusionsDir) && is_dir($conclusionsDir)) {
            $files = array_slice(scandir($conclusionsDir), 2);
            $strictPattern = '/^A?\d+-?\d*\.pdf$/ui';
            foreach ($files as $file) {
                $path = Info::CONC_FOLDER . '/' . $file;
                if (is_file($path) && preg_match($strictPattern, $file)) {
                    $existentFile = Table_availability::findOne(['is_conclusion' => true, 'file_name' => $file]);
                    if ($existentFile !== null) {
// проверю дату изменения и md5 файлов. Если они совпадают- ничего не делаю, если не совпадают- отправлю в вайбер уведомление об обновлении файла
                        $md5 = md5_file($path);
                        $stat = stat($path);
                        $changeTime = $stat['mtime'];
                        if ($changeTime !== $existentFile->file_create_time && $md5 !== $existentFile->md5) {
                            FileUtils::addBackgroundToPDF($conclusionsDir . DIRECTORY_SEPARATOR . $file);
                            $md5 = md5_file($path);
                            // отправлю новую версию файла пользователю
                            $existentFile->md5 = $md5;
                            $existentFile->file_create_time = $changeTime;
                            $existentFile->save();
                            echo TimeHandler::timestampToDate(time()) . "add background to existent {$file}\n";
                            Viber::notifyConclusionLoaded($file);
                        }
                    } else {
                        $name = GrammarHandler::getBaseFileName($file);
                        echo TimeHandler::timestampToDate(time()) . "check file owner {$file} by name " . $name . "\n";
                        // найду пользователя
                        $user = User::findByUsername($name);
                        if ($user !== null) {
                            // внесу информацию о файле в базу
                            FileUtils::addBackgroundToPDF($conclusionsDir . DIRECTORY_SEPARATOR . $file);
                            $md5 = md5_file($path);
                            $stat = stat($path);
                            $changeTime = $stat['mtime'];
                            (new Table_availability(['file_name' => $file, 'is_conclusion' => true, 'md5' => $md5, 'file_create_time' => $changeTime, 'userId' => $user->username]))->save();
                            echo TimeHandler::timestampToDate(time()) . "add background to new {$file}\n";
                            // оповещу мессенджеры о наличии файла
                            Viber::notifyConclusionLoaded($file);
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
        if ($auth !== null) {
            $readerRole = $auth->getRole('reader');
            $auth->assign($readerRole, $new->getId());
            return $password;
        }
// Добавлю вручную
        (new AuthAssignment(['user_id' => $new->id, 'item_name' => 'reader', 'created_at' => time()]))->save();
        return $password;
    }

    /**
     * @param $executionNumber
     * @param string $executionDir
     */
    public static function PackFiles($executionNumber, string $executionDir): void
    {
// скопирую в папку содержимое dicom-просмотровщика
        $viewer_dir = Info::DICOM_VIEWER_FOLDER;
        self::recurse_copy($viewer_dir, $executionDir);
        $fileWay = Info::EXEC_FOLDER . '\\' . $executionNumber . '_tmp.zip';
        $trueFileWay = Info::EXEC_FOLDER . '\\' . GrammarHandler::toLatin($executionNumber) . '.zip';
        // создам архив и удалю исходное
        shell_exec('cd /d ' . $executionDir . ' && "' . Info::WINRAR_FOLDER . '"  a -afzip -r -df  ' . $fileWay . ' .');
        // удалю пустую директорию
        // переименую файл
        rename($fileWay, $trueFileWay);
        rmdir($executionDir);
    }

    /**
     * @param $name
     * @throws Exception
     * @throws \yii\db\Exception
     */
    private static function checkUser($name): void
    {
// проверю, зарегистрирован ли пользователь с данным именем. Если нет- зарегистрирую
        $user = User::findByUsername($name);
        if ($user === null) {
            $transaction = new DbTransaction();
            echo "create user {$name}\n";
            self::createUser($name);
            $transaction->commitTransaction();
        }
    }

    /**
     * @param int $id
     * @param bool $resend
     * @param string|null $subscriberId
     * @throws Exception
     */
    public static function checkAvailabilityForBots(int $id, string $subscriberId = null): void
    {
        // получу обследование
        $execution = User::findIdentity($id);
        if ($execution !== null) {
            // сначала получу аккаунты, которые подписаны на это обследование
            $subscribers = ViberSubscriptions::findAll(['patient_id' => $id]);
            if (!empty($subscribers)) {
                // проверю наличие заключений и файлов обследования
                $existentFile = Table_availability::findOne(['is_execution' => true, 'userId' => $execution->username]);
                if ($existentFile !== null) {
                    $link = TempDownloadLinks::createLink(
                        $execution,
                        'execution',
                        $existentFile->file_name
                    );
                    if ($link !== null) {
                        Viber::sendTempLink($subscriberId, $link->link);
                    }
                }
                // получу все доступные заключения
                $existentConclusions = Table_availability::findAll(['is_conclusion' => 1, 'userId' => $execution->username]);
                if ($existentConclusions !== null) {
                    foreach ($existentConclusions as $existentConclusion) {
                        $link = TempDownloadLinks::createLink(
                            $execution,
                            'conclusion',
                            $existentConclusion->file_name
                        );
                        if ($link !== null) {
                            Viber::sendTempLink($subscriberId, $link->link);
                        }
                    }
                }
            }
        }
    }

    /**
     * Посчитаю количество загруженных заключений
     * @param string $username <p>Номер обследования</p>
     * @return int
     */
    public static function countConclusions(string $username): int
    {
        // посчитаю заключения по конкретному обследованию
        $entities = array_slice(scandir(Info::CONC_FOLDER), 2);
        $conclusionsCount = 0;
        $pattern = '/^' . $username . '-\d.+\pdf$/';
        foreach ($entities as $entity) {
            if ($entity === $username . '.pdf' || preg_match($pattern, $entity)) {
                $conclusionsCount++;
            }
        }
        return $conclusionsCount;
    }

    public function scenarios(): array
    {
        return [
            self::SCENARIO_ADD => ['executionNumber'],
        ];
    }

    public $executionNumber;

    public function attributeLabels(): array
    {
        return [
            'executionNumber' => 'Номер обследования',
        ];
    }

    public function rules(): array
    {
        return [
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
    public function register(): ?array
    {
        if ($this->validate()) {
            $transaction = new DbTransaction();
            if (empty($this->executionNumber)) {
                return ['status' => 2, 'message' => 'Не указан номер обследования'];
            }
            $this->executionNumber = GrammarHandler::toLatin($this->executionNumber);
            // проверю, не зарегистрировано ли уже обследование
            if (User::findByUsername($this->executionNumber) !== null) {
                return ['status' => 4, 'message' => 'Это обследование уже зарегистрировано, вы можете изменить информацию о нём в списке'];
            }
            $password = self::createUser($this->executionNumber);
            // отмечу, что добавлены файлы обследования
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
    public static function isExecution($name): bool
    {
        $filename = Yii::getAlias('@executionsDirectory') . '\\' . $name . '.zip';
        if (is_file($filename)) {
            return true;
        }
        return false;
    }

    /**
     * Проверю наличие заключения
     * @param $name
     * @return bool
     */
    public static function isConclusion($name): bool
    {
        $filename = Info::CONC_FOLDER . '\\' . $name . '.pdf';
        if (is_file($filename)) {
            return true;
        }
        return false;
    }

    public static function recurse_copy($src, $dst): void
    {
        $dir = opendir($src);
        if (!is_dir($dst) && !mkdir($dst) && !is_dir($dst)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dst));
        }
        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
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