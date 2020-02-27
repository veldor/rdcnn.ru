<?php


namespace app\models;


use app\priv\Info;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\db\StaleObjectException;
use yii\web\UploadedFile;

class AdministratorActions extends Model
{
    const SCENARIO_CHANGE_PASSWORD = 'change_password';
    const SCENARIO_DELETE_ITEM = 'delete_item';
    const SCENARIO_ADD_EXECUTION = 'add_execution';
    const SCENARIO_ADD_CONCLUSION = 'add_conclusion';

    public $executionId;
    /**
     * @var UploadedFile
     */
    public $execution;
    /**
     * @var UploadedFile
     */
    public $conclusion;

    public static function checkPatients()
    {
        $response = [];
        // верну список пациентов со статусами данных
        $patientsList = User::find()->where(['<>', 'username', User::ADMIN_NAME])->all();
        if(!empty($patientsList)){
            foreach ($patientsList as $item) {
                // проверю, загружены ли данные по пациенту
                $patientInfo = [];
                $patientInfo['id'] = $item->username;
                $patientInfo['execution'] = ExecutionHandler::isExecution($item->username);
                $patientInfo['conclusion'] = ExecutionHandler::isConclusion($item->username);
                $response[] = $patientInfo;
            }
        }
        return $response;
    }

    public static function selectCenter()
    {
        $center =  Yii::$app->request->post('center');
        $session = Yii::$app->session;
        $session['center'] = $center;
    }

    public static function selectTime()
    {
        $time =  Yii::$app->request->post('timeInterval');
        $session = Yii::$app->session;
        $session['timeInterval'] = $time;
    }


    public static function selectSort()
    {
        $sortBy =  Yii::$app->request->post('sortBy');
        $session = Yii::$app->session;
        $session['sortBy'] = $sortBy;
    }

    public static function clearGarbage()
    {
        // получу список всех пациентов
        $patients = User::findAllRegistered();
        // определю время жизни учётной записи
        $lifetime = time() - Info::DATA_SAVING_TIME;
        if(!empty($patients)){
            foreach ($patients as $patient) {
                if($patient->created_at < $lifetime){
                    self::simpleDeleteItem($patient->username);
                }
            }
        }
    }

    /**
     * @param $id
     * @throws Throwable
     * @throws StaleObjectException
     */
    public static function simpleDeleteItem($id)
    {
        $execution = User::findByUsername($id);
        if(!empty($execution)){
            $conclusionFile = Yii::getAlias('@conclusionsDirectory') . '\\' . $id . '.pdf';
            if(is_file($conclusionFile)){
                unlink($conclusionFile);
                ExecutionHandler::deleteAddConcs($execution->username);
            }
            $executionFile = Yii::getAlias('@executionsDirectory') . '\\' . $id . '.zip';
            if(is_file($executionFile)){
                unlink($executionFile);
            }
            // удалю запись в таблице выдачи разрешений
            Table_auth_assigment::findOne(["user_id" => $execution->id])->delete();
            $execution->delete();
            // если пользователь залогинен и это не админ- выхожу из учётной записи
            if(!Yii::$app->user->can('manage')){
                Yii::$app->user->logout(true);
            }
        }
    }

    public function scenarios(){
        return [
            self::SCENARIO_CHANGE_PASSWORD => ['executionId'],
            self::SCENARIO_DELETE_ITEM => ['executionId'],
            self::SCENARIO_ADD_EXECUTION => ['executionId', 'execution'],
            self::SCENARIO_ADD_CONCLUSION => ['executionId', 'conclusion'],
        ];
    }

    public function rules(): array
    {
        return [
            ['executionId', 'string', 'length' => [1, 255]],
            [['executionNumber'], 'required', 'on' => [self::SCENARIO_CHANGE_PASSWORD, self::SCENARIO_DELETE_ITEM, self::SCENARIO_ADD_EXECUTION, self::SCENARIO_ADD_CONCLUSION]],
            ['executionId', 'match', 'pattern' => '/^[a-z0-9]+$/iu'],
            [['execution'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip', 'maxSize' => 1048576000],
            [['conclusion'], 'file', 'skipOnEmpty' => true, 'extensions' => 'pdf', 'maxSize' => 104857600],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function changePassword()
    {
        if($this->validate()){
            $execution = User::findByUsername($this->executionId);
            if(empty($execution)){
                return ['status' => 3, 'message' => 'Обследование не найдено'];
            }
            $password = User::generateNumericPassword();
            $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $execution->password_hash = $hash;
            $execution->save();
            return ['status' => 1, 'message' => 'Пароль пользователя успешно изменён на <b class="text-success">' . $password . '</b> <button class="btn btn-default" id="copyPassBtn" data-password="' . $password . '"><span class="text-success">Копировать пароль</span></button> '];
        }
        return ['status' => 2, 'message' => $this->errors];
    }

    /**
     * @return array
     * @throws Exception
     * @throws Throwable
     */
    public function deleteItem()
    {
        if($this->validate()){
            $execution = User::findByUsername($this->executionId);
            if(empty($execution)){
                return ['status' => 3, 'message' => 'Обследование не найдено'];
            }
            AdministratorActions::simpleDeleteItem($execution->username);
            return ['status' => 1, 'message' => 'Обследование удалено'];
        }
        return ['status' => 2, 'message' => $this->errors];
    }

    public function addConclusion()
    {
        if($this->validate()){
            $execution = User::findByUsername($this->executionId);
            if(empty($execution)){
                return ['status' => 3, 'message' => 'Обследование не найдено'];
            }
            if(empty($this->conclusion)){
                return ['status' => 4, 'message' => 'Файл не загрузился, попробуйте ещё раз'];
            }
            $filename = Yii::getAlias('@conclusionsDirectory') . '\\' . $execution->username . '.pdf';
            $this->conclusion->saveAs($filename);
            ExecutionHandler::startTimer($this->executionId);
            return ['status' => 1, 'message' => 'Заключение добавлено'];
        }
        return ['status' => 2, 'message' => $this->errors];
    }
    public function addExecution()
    {
        if($this->validate()){
            $execution = User::findByUsername($this->executionId);
            if(empty($execution)){
                return ['status' => 3, 'message' => 'Обследование не найдено'];
            }
            if(empty($this->execution)){
                return ['status' => 4, 'message' => 'Файл не загрузился, попробуйте ещё раз'];
            }
            $filename = Yii::getAlias('@executionsDirectory') . '\\' . $execution->username . '.zip';
            $this->execution->saveAs($filename);
            ExecutionHandler::startTimer($this->executionId);
            return ['status' => 1, 'message' => 'Файлы обследования добавлены'];
        }
        return ['status' => 2, 'message' => $this->errors];
    }
}