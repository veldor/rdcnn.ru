<?php


namespace app\models\utils;


use app\models\FileUtils;
use Exception;
use Yii;
use yii\base\Model;

class Management extends Model
{
    public static function handleChanges():bool
    {
        // если обновление не в ходу и с последнего обновления прошло больше 10 минут- запущу его
        if(!FileUtils::isUpdateInProgress() && FileUtils::getLastUpdateTime() < (time() - 300)){
            $file = Yii::$app->basePath . '\\yii.bat';
            if(is_file($file)){
                $command = "$file console";
                $outFilePath =  Yii::$app->basePath . '/logs/file.log';
                $outErrPath = Yii::$app->basePath . '/logs/err.log';
                $command .= ' > ' . $outFilePath . ' 2>' . $outErrPath . ' &"';
                try{
                    // попробую вызвать процесс асинхронно
                    $handle = new \COM('WScript.Shell');
                    $handle->Run($command, 0, false);
                    return true;
                }
                catch (Exception $e){
                    exec($command);
                    return true;
                }
            }
        }
        return false;
    }
}