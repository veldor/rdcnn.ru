<?php


namespace app\models\utils;


use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\Table_availability;
use app\models\User;
use app\models\Viber;
use app\priv\Info;
use http\Exception\RuntimeException;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\web\UploadedFile;

class FilesHandler extends Model
{

    public static function handleDroppedFile(UploadedFile $file): void
    {
        echo $file->extension;
        // для начала - проверю тип файла
        switch ($file->extension) {
            case 'pdf':
            case 'doc':
            case 'docx':
                // сохраню файл во временную папку
                $savedFile = self::saveTempFile($file);
                try {
                    FileUtils::handleFileUpload($savedFile);
                } catch (Exception $e) {
                }
                break;
            case 'zip':
                self::saveZip($file);
        }
    }

    private static function saveTempFile(UploadedFile $file): string
    {
        $root = Yii::$app->basePath;
        // создам временную папку, если её ещё не существует
        if (!is_dir($root . '/temp') && !mkdir($concurrentDirectory = $root . '/temp') && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $fileName = $root . "/temp/" . Yii::$app->security->generateRandomString() . '.' . $file->extension;
        $file->saveAs($fileName);
        return $fileName;
    }

    private static function saveZip(UploadedFile $file): void
    {
        $fileName = GrammarHandler::toLatin($file->name);
        $user = User::findByUsername(GrammarHandler::getBaseFileName($fileName));
        $path = Info::EXEC_FOLDER . '\\' . $fileName;
        $file->saveAs($path);
        // зарегистрирую файл
        $md5 = md5_file($path);
        $stat = stat($path);
        $changeTime = $stat['mtime'];
        if($user === null){
            // создам пользователя
            ExecutionHandler::createUser(GrammarHandler::toLatin(GrammarHandler::getBaseFileName($fileName)));
            $user = User::findByUsername(GrammarHandler::getBaseFileName($fileName));
        }
        (new Table_availability(['file_name' => $fileName, 'is_execution' => true, 'md5' => $md5, 'file_create_time' => $changeTime, 'userId' => $user->username]))->save();
        // оповещу мессенджеры о наличии файла
        Viber::notifyExecutionLoaded($user->username);
    }
}