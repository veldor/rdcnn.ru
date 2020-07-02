<?php


namespace app\models\utils;


use app\models\FileUtils;
use Exception;
use Yii;
use yii\base\Model;

class Management extends Model
{
    public static function handleChanges()
    {
        // если обновление не в ходу и с последнего обновления прошло больше 10 минут- запущу его
        if(!FileUtils::isUpdateInProgress() && FileUtils::getLastUpdateTime() < (time() - 60)){
            $file = Yii::$app->basePath . '\\yii.bat';
            if(is_file($file)){
                $command = "$file console";
                $outFilePath =  Yii::$app->basePath . '/logs/content_change.log';
                $outErrPath = Yii::$app->basePath . '/logs/content_change_err.log';
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
        else{
                // запишу в файл отчётов, что ещё не пришло время для проверки
                return 'timeout ' . (300 - (time() - FileUtils::getLastUpdateTime()));
        }
        return false;
    }
}