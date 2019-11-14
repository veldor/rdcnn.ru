<?php


namespace app\controllers;


use app\models\Table_statistics;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class DownloadController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/error', 404);
                },
                'rules' => [

                    [
                        'allow' => true,
                        'actions' => ['execution', 'conclusion', 'print-conclusion'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionExecution(){
        // если это запись администратора- загружу запись. Для этого узнаю, с какой страницы был переход
        if(Yii::$app->user->can('manage')){
            $referer = explode('/', $_SERVER['HTTP_REFERER']);
            $executionNumber = $referer[array_key_last($referer)];
            if(!empty($executionNumber)){
                // получу данные о пользователе
                $execution = User::findByUsername($executionNumber);
                if(!empty($execution)){
                    $file = Yii::getAlias('@executionsDirectory') . '\\' . $execution->username . '.zip';
                    // проверю, если есть файл результатов сканирования- выдам его на загрузку
                    if(is_file($file)){
                        Yii::$app->response->sendFile($file, 'Файлы обследования МРТ №' . $execution->username . '.zip');
                    }
                }
            }
        }
        else{
            if(Yii::$app->user->can('read')){
                $executionNumber = Yii::$app->user->identity->username;
                if(!empty($executionNumber)){
                    // получу данные о пользователе
                    $execution = User::findByUsername($executionNumber);
                    if(!empty($execution)){
                        $file = Yii::getAlias('@executionsDirectory') . '\\' . $execution->username . '.zip';
                        // проверю, если есть файл результатов сканирования- выдам его на загрузку
                        if(is_file($file)){
                            // запишу данные о скачивании
                            Table_statistics::plusExecutionDownload($executionNumber);
                            Yii::$app->response->sendFile($file, 'Файлы обследования МРТ №' . $execution->username . '.zip');
                        }
                    }
                }
            }
        }
    }
    public function actionConclusion(){

        // если это запись администратора- загружу запись. Для этого узнаю, с какой страницы был переход
        if(Yii::$app->user->can('manage')){
            $referer = explode('/', $_SERVER['HTTP_REFERER']);
            $executionNumber = $referer[array_key_last($referer)];
            if(!empty($executionNumber)){
                // получу данные о пользователе
                $execution = User::findByUsername($executionNumber);
                if(!empty($execution)){
                    $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $execution->username . '.pdf';
                    // проверю, если есть файл результатов сканирования- выдам его на загрузку
                    if(is_file($file)){
                        Yii::$app->response->sendFile($file, 'Заключение врача по обследованию №' . $execution->username. '.pdf');
                    }
                }
            }
        }
        else{
            if(Yii::$app->user->can('read')){
                $executionNumber = Yii::$app->user->identity->username;
                if(!empty($executionNumber)){
                    // получу данные о пользователе
                    $execution = User::findByUsername($executionNumber);
                    if(!empty($execution)){
                        $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $execution->username . '.pdf';
                        // проверю, если есть файл результатов сканирования- выдам его на загрузку
                        if(is_file($file)){
                            Table_statistics::plusConclusionDownload($executionNumber);
                            Yii::$app->response->sendFile($file, 'Заключение врача по обследованию №' . $execution->username. '.pdf');
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function actionPrintConclusion(){
        // если это запись администратора- загружу запись. Для этого узнаю, с какой страницы был переход
        if(Yii::$app->user->can('manage')){
            $referer = explode('/', $_SERVER['HTTP_REFERER']);
            $executionNumber = $referer[array_key_last($referer)];
            if(!empty($executionNumber)){
                // получу данные о пользователе
                $execution = User::findByUsername($executionNumber);
                if(!empty($execution)){
                    $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $execution->username . '.pdf';
                    // проверю, если есть файл результатов сканирования- выдам его на загрузку
                    if(is_file($file)){
                        Yii::$app->response->sendFile($file, 'Заключение врача по обследованию ' . $execution->username, ['inline'=>true]);
                    }
                }
            }
        }
        else{
            if(Yii::$app->user->can('read')){
                $executionNumber = Yii::$app->user->identity->username;
                if(!empty($executionNumber)){
                    // получу данные о пользователе
                    $execution = User::findByUsername($executionNumber);
                    if(!empty($execution)){
                        $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $execution->username . '.pdf';
                        // проверю, если есть файл результатов сканирования- выдам его на загрузку
                        if(is_file($file)){
                            Table_statistics::plusConclusionPrint($executionNumber);
                            Yii::$app->response->sendFile($file, 'Заключение врача по обследованию ' . $execution->username, ['inline'=>true]);
                        }
                    }
                }
            }
        }
    }
}