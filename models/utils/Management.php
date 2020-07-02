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
        if(!FileUtils::isUpdateInProgress() && FileUtils::getLastUpdateTime() < (time() - 300)){
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
            try{
                // запишу в файл отчётов, что ещё не пришло время для проверки
                $outFilePath =  Yii::$app->basePath . '/logs/content_change.log';
                file_put_contents($outFilePath, 'Проверка недавно проведена, нужно подождать');
                $outErrPath = Yii::$app->basePath . '/logs/content_change_err.log';
                file_put_contents($outErrPath, '');
            }
            catch (Exception $e){}
        }
        return false;
    }
}