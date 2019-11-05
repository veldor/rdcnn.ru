<?php


namespace app\controllers;


use app\models\AdministratorActions;
use app\models\ExecutionHandler;
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
                        'actions' => ['add-execution', 'change-password', 'delete-item', 'add-conclusion', 'add-execution-data', 'patients-check'],
                        'roles' => ['manager'],
                        'ips' => Info::ACCEPTED_IPS,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionAddExecution(){
        if(Yii::$app->request->isAjax && Yii::$app->request->isGet){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            return ['status' => 1, 'header' => 'Добавление обследования',  'view' => $this->renderAjax('add-execution-form', ['model' => $model])];
        }
        elseif(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            $model->load(Yii::$app->request->post());
            $model->executionData = UploadedFile::getInstance($model, 'executionData');
            $model->executionResponse = UploadedFile::getInstance($model, 'executionResponse');
            return $model->register();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionChangePassword(){
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
    public function actionDeleteItem(){
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
    public function actionAddConclusion(){
        if(Yii::$app->request->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_ADD_CONCLUSION]);
            $model->load(Yii::$app->request->post());
            $model->conclusion = UploadedFile::getInstance($model, 'conclusion');
            return $model->addConclusion();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionAddExecutionData(){
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
}