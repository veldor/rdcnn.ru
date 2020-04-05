<?php


namespace app\controllers;


use app\models\AdministratorActions;
use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\Table_availability;
use app\models\User;
use app\models\Utils;
use app\models\utils\GrammarHandler;
use app\models\utils\TimeHandler;
use app\models\Viber;
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
                        'actions' => ['add-execution', 'change-password', 'delete-item', 'add-conclusion', 'add-execution-data', 'patients-check', 'files-check', 'clear-garbage', 'delete-unhandled-folder', 'rename-unhandled-folder', 'print-missed-conclusions-list', 'test'],
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

    /**
     * @return array
     */
    public function actionPatientsCheck(): array
    {
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

    public function actionTest(): void
    {
        $fileValue = file_get_contents('Z:\sites\rdcnn.ru\logs\viber_log_1586077378.log');
        $json = json_decode($fileValue, true, 512, JSON_THROW_ON_ERROR);
        if(!empty($json) && !empty($json['message']) && !empty($json['message']['type'])){
            if($json['message']['type'] === 'file'){
                // проверю, что заявленный файл является PDF
                if(GrammarHandler::isPdf($json['message']['file_name'])){
                    // получу базовое название файла
                    $basename = GrammarHandler::getBaseFileName($json['message']['file_name']);
                    if(!empty($basename)){
                        $name = GrammarHandler::toLatin($basename);
                        // проверю, зарегистрировано ли уже обследование с данным номером,
                        // так как будем подгружать заключения только к зарегистрированным
                        $execution = User::findByUsername($name);
                        if($execution !== null){
                            $realName =  GrammarHandler::toLatin($json['message']['file_name']);
                            // ещё раз удостоверюсь, что файл подходит для загрузки
                            $strictPattern = '/^A?\d+-?\d*\.pdf$/';
                            if(preg_match($strictPattern, $realName)){
                                // загружу файл
                                $file = file_get_contents($json['message']['media']);
                                if(!empty($file)){
                                    file_put_contents('Z:\sites\rdcnn.ru\logs\\' . $realName, $file);
                                }
                                else{
                                    // не удалось загрузить файл, сообщу об ошибке
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}