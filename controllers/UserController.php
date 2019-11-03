<?php


namespace app\controllers;


use app\models\AdministratorActions;
use app\models\User;
use app\models\UserActions;
use Throwable;
use Yii;
use yii\db\StaleObjectException;
use yii\filters\AccessControl;
use yii\web\Controller;

class UserController extends Controller
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
                        'actions' => ['delete-execution'],
                        'roles' => ['reader'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionDeleteExecution(){
        if(Yii::$app->request->isPost){
            // если удаление выполняется от лица администратора- удалю запись и перенаправлю на страницу администрирования
            if(Yii::$app->user->can('manage')){
                $referer = explode('/', $_SERVER['HTTP_REFERER']);
                $executionNumber = $referer[array_key_last($referer)];
                UserActions::deleteUser($executionNumber);
                return $this->redirect('/site/administrate', 301);
            }
            if(Yii::$app->user->can('read')){
                // разлогиню пользователя и удалю учётную запись
                $id = Yii::$app->user->identity->username;
                AdministratorActions::simpleDeleteItem($id);
            }
        }
    }
}