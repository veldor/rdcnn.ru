<?php


namespace app\controllers;


use app\models\FileUtils;
use app\models\utils\Management;
use app\models\utils\TimeHandler;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ManagementController extends Controller
{
    public function behaviors():array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'check-update',
                            'check-changes',
                            'update-dependencies',
                            'reset-change-check-counter',
                            'add-backgrounds'
                        ],
                        'roles' => [
                            'manager'
                        ],
                        //'ips' => Info::ACCEPTED_IPS,
                    ],
                ],
            ],
        ];
    }

    /**
     * принудительная загрузка обновления с гитхаба
     */
    public function actionCheckUpdate(): void
    {
        // отмечу время проверки обновления
        FileUtils::setLastCheckUpdateTime();
        $file = Yii::$app->basePath . '\\updateFromGithub.bat';
        if(is_file($file)){
            $command = $file . ' ' . Yii::$app->basePath;
            $outFilePath =  Yii::$app->basePath . '\\logs\\update_file.log';
            $outErrPath =  Yii::$app->basePath . '\\logs\\update_err.log';
            $command .= ' > ' . $outFilePath . ' 2>' . $outErrPath . ' &"';
            echo $command;
            try{
                // попробую вызвать процесс асинхронно
                $handle = new \COM('WScript.Shell');
                $handle->Run($command, 0, false);
            }
            catch (Exception $e){
                exec($command);
            }
        }
    }

    /**
     * принудительная проверка содержимого папок
     */
    public function actionCheckChanges()
    {
        FileUtils::writeUpdateLog('try to start : ' . TimeHandler::timestampToDate(time()));
       return Management::handleChanges();
    }

    public function actionUpdateDependencies(){

        $file = Yii::$app->basePath . '\\composerUpdate.bat';
        if(is_file($file)){
            $command = $file . ' ' . Yii::$app->basePath;
            $outFilePath =  Yii::$app->basePath . '\\logs\\update_file.log';
            $outErrPath =  Yii::$app->basePath . '\\logs\\update_err.log';
            $command .= ' > ' . $outFilePath . ' 2>' . $outErrPath . ' &"';
            echo $command;
            try{
                // попробую вызвать процесс асинхронно
                $handle = new \COM('WScript.Shell');
                $handle->Run($command, 0, false);
            }
            catch (Exception $e){
                exec($command);
            }
        }
    }

    public function actionResetChangeCheckCounter(){
        FileUtils::setUpdateFinished();
    }
}