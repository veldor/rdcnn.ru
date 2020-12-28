<?php


namespace app\controllers;


use app\models\AdministratorActions;
use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\User;
use app\models\utils\MailHandler;
use app\models\utils\Management;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdministratorController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    /** @noinspection SpellCheckingInspection */
                    return $this->redirect('/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'add-execution',
                            'change-password',
                            'delete-item',
                            'add-conclusion',
                            'add-execution-data',
                            'patients-check',
                            'files-check',
                            'delete-unhandled-folder',
                            'rename-unhandled-folder',
                            'print-missed-conclusions-list',
                            'register-next-patient',
                            'send-info-mail',
                            'auto-print',
                            'show-notifications',
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

    /**
     * Регистрация пациента
     * @return array
     * @throws Exception
     */
    public function actionAddExecution(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            return ['status' => 1, 'header' => 'Добавление обследования', 'view' => $this->renderAjax('add-execution-form', ['model' => $model])];
        }

        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            $model->load(Yii::$app->request->post());
            return $model->register();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionChangePassword(): array
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_CHANGE_PASSWORD]);
            $model->load(Yii::$app->request->post());
            return $model->changePassword();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws Exception
     * @throws NotFoundHttpException|Throwable
     */
    public function actionDeleteItem(): array
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_DELETE_ITEM]);
            $model->load(Yii::$app->request->post());
            return $model->deleteItem();
        }
        throw new NotFoundHttpException();
    }


    /**
     * @return array
     * @throws Exception
     */
    public function actionPatientsCheck(): array
    {
        try {
            $isCheckStarted = Management::handleChanges();
        } catch (\Exception $e) {
            $isCheckStarted = $e->getMessage();
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return AdministratorActions::checkPatients($isCheckStarted);
    }

    public function actionFilesCheck($executionNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::checkFiles($executionNumber);
    }

    public function actionDeleteUnhandledFolder(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        FileUtils::deleteUnhandledFolder();
        return ['status' => 1];
    }

    public function actionRenameUnhandledFolder(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        FileUtils::renameUnhandledFolder();
        return ['status' => 1];
    }

    public function actionPrintMissedConclusionsList(): string
    {
        return $this->render('missed-conclusions-list');
    }

    /**
     */
    public function actionTest()
    {
        User::findIdentityByAccessToken("test");
    }

    public function actionSendInfoMail($id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        // отправлю письмо с информацией, если есть адрес
        return MailHandler::sendInfoMail($id);
    }

    /**
     * @param $center
     * @return array
     * @throws Exception
     */
    public function actionRegisterNextPatient($center): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::registerNext($center);
    }

    public function actionAutoPrint($fileName): void
    {
        $file = Yii::getAlias('@conclusionsDirectory') . '\\' . 'nb_' . $fileName;
        if (is_file($file)) {
            Yii::$app->response->sendFile($file, 'заключение', ['inline' => true]);
        } else {
            $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $fileName;
            Yii::$app->response->sendFile($file, 'заключение', ['inline' => true]);
        }
    }

    public function actionShowNotifications()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $state = Yii::$app->request->post('state');
        $cookies = Yii::$app->response->cookies;
        if ($state === 'true') {

// добавление новой куки в HTTP-ответ
            $cookies->add(new Cookie([
                'name' => 'show_notifications',
                'value' => $state,
                'httpOnly' => false,
            ]));
        }
        else{
            $cookies->remove('show_notifications');
        }
        return ['status' => 'success'];
    }
}