<?php


namespace app\controllers;


use app\models\AdministratorActions;
use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\Utils;
use app\priv\Info;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class AdministratorController extends Controller
{
    public function behaviors():array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/error', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['add-execution', 'change-password', 'delete-item', 'add-conclusion', 'add-execution-data', 'patients-check', 'files-check', 'clear-garbage', 'delete-unhandled-folder', 'rename-unhandled-folder', 'print-missed-conclusions-list'],
                        'roles' => ['manager'],
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
            return ['status' => 1, 'header' => 'Добавление обследования',  'view' => $this->renderAjax('add-execution-form', ['model' => $model])];
        }

        if(Yii::$app->request->isPost) {
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
        if(Yii::$app->request->isPost){
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
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionDeleteItem(): array
    {
        if(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_DELETE_ITEM]);
            $model->load(Yii::$app->request->post());
            return $model->deleteItem();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAddConclusion(): array
    {
        if(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_ADD_CONCLUSION]);
            $model->load(Yii::$app->request->post());
            $model->conclusion = UploadedFile::getInstances($model, 'conclusion');
            return $model->addConclusion();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAddExecutionData(): array
    {
        if(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_ADD_CONCLUSION]);
            $model->load(Yii::$app->request->post());
            $model->execution = UploadedFile::getInstance($model, 'execution');
            return $model->addExecution();
        }
        throw new NotFoundHttpException();
    }

    public function actionPatientsCheck(){
        Yii::$app->response->format = Response::FORMAT_JSON;
        return AdministratorActions::checkPatients();
    }

    public function actionFilesCheck($executionNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::checkFiles($executionNumber);
    }

    public function actionClearGarbage(): array
    {
        Utils::clearGarbage();
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['status' => 1, 'message' => 'Весь мусор удалён.'];
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
}