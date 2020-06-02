<?php


namespace app\controllers;


use Exception;
use yii\filters\AccessControl;
use yii\web\Controller;

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
        $file = dirname(__DIR__) . '\\updateFromGithub.bat';
        if(is_file($file)){
            $command = "$file";
            $outFilePath =  dirname(__DIR__) . '/logs/file.log';
            $outErrPath =  dirname(__DIR__) . '/logs/err.log';
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
    public function actionCheckChanges(): void
    {
        $file = dirname(__DIR__) . '\\yii.bat';
        if(is_file($file)){
            //system("cmd /c \"$file console\" ", $retval);
            $command = "$file console";
            $outFilePath =  dirname(__DIR__) . '/logs/file.log';
            $outErrPath =  dirname(__DIR__) . '/logs/err.log';
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
}