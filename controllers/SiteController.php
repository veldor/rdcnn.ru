<?php /** @noinspection PhpUndefinedClassInspection */

namespace app\controllers;

use app\models\AdministratorActions;
use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\LoginForm;
use app\models\Telegram;
use app\models\User;
use app\models\Utils;
use app\models\utils\DicomHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\Management;
use app\priv\Info;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
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
                        'actions' => [
                            'index',
                            'error',
                            'dicom-viewer'
                        ],
                        'roles' => ['?', '@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['check'],
                        'roles' => ['?', '@'],
                        'ips' => Info::ACCEPTED_IPS,
                    ],
                    [
                        'allow' => true,
                        'actions' => ['iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8'],
                        'roles' => ['?', '@'],
                        //'ips' => Info::ACCEPTED_IPS,
                    ],

                    [
                        'allow' => true,
                        'actions' => [
                            'logout',
                            'availability-check'
                        ],
                        'roles' => ['@'],
                    ],

                    [
                        'allow' => true,
                        'actions' => [
                            'management',
                            'test'
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

    public function actions()
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }


    /**
     * Displays homepage.
     *
     * @param null $executionNumber
     * @return string|Response
     * @throws Exception
     */
    public function actionIndex($executionNumber = null)
    {
        Management::handleChanges();
        // если пользователь не залогинен- показываю ему страницу с предложением ввести номер обследования и пароль
        if (Yii::$app->user->isGuest) {
            if (Yii::$app->request->isGet) {
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_USER_LOGIN]);
                if ($executionNumber !== null) {
                    $model->username = GrammarHandler::toLatin($executionNumber);
                }
                return $this->render('login', ['model' => $model]);
            }
            if (Yii::$app->request->isPost) {
                // попробую залогинить
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_USER_LOGIN]);
                $model->load(Yii::$app->request->post());
                if ($model->loginUser()) {
                    Telegram::sendDebug("Залогинился пользователь " . $model->username);
                    // загружаю личный кабинет пользователя
                    return $this->redirect('/person/' . Yii::$app->user->identity->username, 301);
                }
                return $this->render('login', ['model' => $model]);
            }
        }
        // если пользователь залогинен как администратор- показываю ему страницу для скачивания
        if (Yii::$app->user->can('manage')) {
            if ($executionNumber !== null) {
                // получу информацию о обследовании
                $execution = User::findByUsername($executionNumber);
                if ($execution !== null) {
                    return $this->render('personal', ['execution' => $execution]);
                }
            }

// страница не найдена, перенаправлю на страницу менеджмента
            return $this->redirect(Url::toRoute('site/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8'));
        }

        if (Yii::$app->user->can('read')) {
            $execution = User::findByUsername(Yii::$app->user->identity->username);
            if ($execution !== null) {
                return $this->render('personal', ['execution' => $execution]);
            }

            return $this->render('error', ['message' => 'Страница не найдена']);
        }
        return $this->render('error', ['message' => 'Страница не найдена']);
    }

    /**
     * @return string|Response
     * @noinspection SpellCheckingInspection
     * @throws \Exception
     */
    public function actionIolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8()
    {
        Management::handleChanges();
        // если пользователь не залогинен- показываю ему страницу с предложением ввести номер обследования и пароль
        if (Yii::$app->user->isGuest) {
            if (Yii::$app->request->isGet) {
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_ADMIN_LOGIN]);
                return $this->render('administrationLogin', ['model' => $model]);
            }
            if (Yii::$app->request->isPost) {
                // попробую залогинить
                $model = new LoginForm(['scenario' => LoginForm::SCENARIO_ADMIN_LOGIN]);
                $model->load(Yii::$app->request->post());
                if ($model->loginAdmin()) {
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
            //AdministratorActions::clearGarbage();
            $this->layout = 'administrate';
            if (Yii::$app->request->isPost) {
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
        }

// редирект на главную
        return $this->redirect('site/index', 301);
    }

    public function actionTest(): void
    {
        // попробую получить информацию о DICOM
        DicomHandler::readInfoFromDicomdir();
    }


    public function actionError(): string
    {
        return $this->render('wooops');
    }

    public function actionLogout(): Response
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->user->logout();
            return $this->redirect('/', 301);
        }
        return $this->redirect('/', 301);
    }

    /**
     * @return array
     * @throws Throwable
     */
    public function actionAvailabilityCheck(): array
    {
        try {
            Management::handleChanges();
        } catch (\Exception $e) {

        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::checkAvailability();
    }

    /**
     */
    public function actionCheck(): void
    {
        try {
            Management::handleChanges();
            ExecutionHandler::check();
        } catch (\Exception $e) {
        }
    }

    public function actionManagement(): string
    {
        $updateInfo = FileUtils::getUpdateInfo();
        $outputInfo = FileUtils::getOutputInfo();
        $errorsInfo = FileUtils::getErrorInfo();
        $updateOutputInfo = FileUtils::getUpdateOutputInfo();
        $updateErrorsInfo = FileUtils::getUpdateErrorInfo();
        $errors = FileUtils::getServiceErrorsInfo();
        return $this->render('management', ['updateInfo' => $updateInfo, 'outputInfo' => $outputInfo, 'errorsInfo' => $errorsInfo, 'errors' => $errors, 'updateOutputInfo' => $updateOutputInfo, 'updateErrorsInfo' => $updateErrorsInfo]);
    }

    public function actionDicomViewer(): string
    {
        $this->layout = 'empty';
        return $this->render('dicom-viewer');
    }
}
