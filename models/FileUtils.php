<?php


namespace app\models;


use Yii;

class FileUtils
{
    public const FOLDER_WAITING_TIME = 300;

    /**
     * Метод проверяет нераспознанные директории файлов и возвращает список нераспознанных
     * На случай ошибок администраторов в обзывании папок
     * @return array
     */
    public static function checkUnhandledFolders(): array
    {
        // это список нераспознанных папок
        $unhandledFoldersList = [];
        // паттерн валидных папок
        $pattern = '/^[aа]?\d+$/ui';
        // получу список папок с заключениями
        $dirs = array_slice(scandir(Yii::getAlias('@executionsDirectory')), 2);
        foreach ($dirs as $dir) {
            $path = Yii::getAlias('@executionsDirectory') . '/' . $dir;
            if (is_dir($path)) {
                // если папка не соответствует принятому названию- внесу её в список нераспознанных
                // отфильтрую свежесозданные папки: они могут быть ещё в обработке
                $stat = stat($path);
                $changeTime = $stat['mtime'];
                $difference = time() - $changeTime;
                if($difference > self::FOLDER_WAITING_TIME && !preg_match($pattern, $dir)){
                    $unhandledFoldersList[] = $dir;
                }
            }
        }
        return $unhandledFoldersList;
    }

    public static function deleteUnhandledFolder(): void
    {
        // получу имя папки
        $folderName = Yii::$app->request->post('folderName');
        if(!empty($folderName)){
            $path = Yii::getAlias('@executionsDirectory') . '/' . $folderName;
            if(is_dir($path)){
                self::removeDir($path);
            }
        }
    }

    public static function removeDir($path)
    {
        if (is_file($path)) {
            return unlink($path);
        }
        if (is_dir($path)) {
            foreach (scandir($path, SCANDIR_SORT_NONE) as $p) {
                if (($p !== '.') && ($p !== '..')) {
                    self::removeDir($path . DIRECTORY_SEPARATOR . $p);
                }
            }
            return rmdir($path);
        }
        return false;
    }

    public static function renameUnhandledFolder(): void
    {
        $oldFolderName = Yii::$app->request->post('oldName');
        $newFolderName = Yii::$app->request->post('newName');
        if(!empty($oldFolderName)){
            $path = Yii::getAlias('@executionsDirectory') . '/' . $oldFolderName;
            if(is_dir($path)){
                rename($path, Yii::getAlias('@executionsDirectory') . '\\' . $newFolderName);
            }
        }

    }

    /**
     * Получение списка папок, ожидающих обработки
     * @return array <p>Возвращает список имён папок</p>
     */
    public static function checkWaitingFolders(): array
    {
        // это список ожидающих папок
        $waitingFoldersList = [];
        // паттерн валидных папок
        $pattern = '/^[aа]?\d+$/ui';
        // получу список папок с заключениями
        $dirs = array_slice(scandir(Yii::getAlias('@executionsDirectory')), 2);
        foreach ($dirs as $dir) {
            $path = Yii::getAlias('@executionsDirectory') . '/' . $dir;
            // если папка не соответствует принятому названию- внесу её в список нераспознанных
            // отфильтрую свежесозданные папки: они могут быть ещё в обработке
            if (is_dir($path) && preg_match($pattern, $dir)) {
                $waitingFoldersList[] = $dir;
            }
        }
        return $waitingFoldersList;
    }

    /**
     * @return string
     */
    public static function getUpdateInfo():string
    {
        $file = Yii::$app->basePath . '\\logs\\update.log';
        if(is_file($file)){
            return file_get_contents($file);
        }
        return 'file is empty';
    }
    /**
     * @return string
     */
    public static function getOutputInfo():string
    {
        $file = Yii::$app->basePath . '\\logs\\file.log';
        if(is_file($file)){
            return file_get_contents($file);
        }
        return 'file is empty';
    }
    /**
     * @return string
     */
    public static function getErrorInfo():string
    {
        $file = Yii::$app->basePath . '\\logs\\err.log';
        if(is_file($file)){
            return file_get_contents($file);
        }
        return 'file is empty';
    }

    public static function setUpdateInProgress():void
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        file_put_contents($file, "1");
    }
    public static function setUpdateFinished():void
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        file_put_contents($file, '0');
    }

    public static function isUpdateInProgress():bool
    {
        $file = Yii::$app->basePath . '\\priv\\update_progress.conf';
        if($file){
            $content = file_get_contents($file);
            return (bool) $content;
        }
        return false;
    }

    public static function setLastUpdateTime():void
    {
        $file = Yii::$app->basePath . '\\priv\\last_update_time.conf';
        file_put_contents($file, time());
    }
    public static function getLastUpdateTime():int
    {
        $file = Yii::$app->basePath . '\\priv\\last_update_time.conf';
        if(is_file($file)){
            return file_get_contents($file);
        }
        return 0;
    }
}