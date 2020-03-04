<?php

namespace app\controllers;

use app\models\AdministratorActions;
use app\models\ExecutionHandler;
use app\models\LoginForm;
use app\models\Test;
use app\models\User;
use app\models\Utils;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('error', 404);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'error', 'check'],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8'],
                        'roles' => ['?', '@'],
                        //'ips' => Info::ACCEPTED_IPS,
                    ],

                    [
                        'allow' => true,
                        'actions' => ['logout', 'availability-check'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }


    /**
     * Displays homepage.
     *
     * @param null $executionNumber
     * @return string
     * @throws Exception
     */
    public function actionIndex($executionNumber = null)
    {
        // если пользователь не залогинен- показываю ему страницу с предложением ввести номер обследования и пароль
        if (Yii::$app->user->isGuest) {
            if (Yii::$app->request->isGet) {
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_USER_LOGIN]);
                if ($executionNumber != null) {
                    $model->username = ExecutionHandler::toLatin($executionNumber);
                }
                return $this->render('login', ['model' => $model]);
            }
            if (Yii::$app->request->isPost) {
                // попробую залогинить
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_USER_LOGIN]);
                $model->load(Yii::$app->request->post());
                if ($model->loginUser()) {
                    // загружаю личный кабинет пользователя
                    return $this->redirect('/person/' . Yii::$app->user->identity->username, 301);
                }
                return $this->render('login', ['model' => $model]);
            }
        }
        // если пользователь залогинен как администратор- показываю ему страницу для скачивания
        if (Yii::$app->user->can('manage')) {
            // получу информацию о обследовании
            $execution = User::findByUsername($executionNumber);
            if (!empty($execution)) {
                return $this->render('personal', ['execution' => $execution]);
            } else {
                // страница не найдена, перенаправлю на страницу менеджмента
                return $this->redirect(Url::toRoute('site/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8'));
            }
        } elseif (Yii::$app->user->can('read')) {
            $execution = User::findByUsername(Yii::$app->user->identity->username);
            if (!empty($execution)) {
                return $this->render('personal', ['execution' => $execution]);
            } else {
                return $this->render('error', ['message' => 'Страница не найдена']);
            }
        }
        return $this->render('error', ['message' => 'Страница не найдена']);
    }

    /**
     * @return string|Response
     */
    public function actionIolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8()
    {
        // если пользователь не залогинен- показываю ему страницу с предложением ввести номер обследования и пароль
        if (Yii::$app->user->isGuest) {
            if(Yii::$app->request->isGet){
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_ADMIN_LOGIN]);
                return $this->render('administrationLogin', ['model' => $model]);
            }
            if(Yii::$app->request->isPost){
                // попробую залогинить
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_ADMIN_LOGIN]);
                $model->load(Yii::$app->request->post());
                if($model->loginAdmin()){
                    // загружаю страницу управления
                    return $this->redirect('iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8', 301);
                }
                return $this->render('administrationLogin', ['model' => $model]);
            }
            // зарегистрирую пользователя как администратора
            //LoginForm::autoLoginAdmin();
        }
        // если пользователь админ
        if (Yii::$app->user->can('manage')) {

            // очищу неиспользуемые данные

            AdministratorActions::clearGarbage();

            $this->layout = 'administrate';
            if(Yii::$app->request->isPost){
                // выбор центра, обследования которого нужно отображать
                AdministratorActions::selectCenter();
                AdministratorActions::selectTime();
                AdministratorActions::selectSort();
                return $this->redirect('site/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8', 301);
            }
            // получу все зарегистрированные обследования
            $executionsList = User::findAllRegistered();
            // отсортирую список
            $executionsList = Utils::sortExecutions($executionsList);
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            return $this->render('administration', ['executions' => $executionsList, 'model' => $model]);
        } else {
            // редирект на главную
            return $this->redirect('site/index', 301);
        }
    }


    public function actionError()
    {
        return $this->render('wooops');
    }

    /**
     * @throws Exception
     */
    public function actionTest()
    {
        Test::test();
    }

    public function actionLogout()
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->user->logout();
            return $this->redirect('/', 301);
        }
        return $this->redirect('/', 301);
    }

    public function actionAvailabilityCheck(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::checkAvailability();
    }

    public function actionCheck(){
        ExecutionHandler::check();
    }
}
