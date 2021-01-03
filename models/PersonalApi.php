<?php


namespace app\models;


use app\models\database\PersonalItems;
use app\models\database\PersonalTask;
use JsonException;
use Yii;

class PersonalApi
{
    private static array $data;

    /**
     * Обработка запроса
     * @return array|string[]
     * @throws JsonException
     */
    public static function handleRequest(): array
    {
        self::$data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        if (!empty(self::$data['cmd'])) {
            switch (self::$data['cmd']) {
                case 'login':
                    return self::login();
                case 'getTaskList':
                    return self::getTaskList();
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function login(): array
    {
        $login = self::$data['login'];
        $password = self::$data['pass'];
        $user = PersonalItems::findOne(['login' => $login]);
        if ($user !== null) {
            if (Yii::$app->security->validatePassword($password, $user->pass_hash)) {
                // всё верно, верну токен доступа
                Telegram::sendDebug("Успешный вход: " . $user->name);
                return ['status' => 'success', 'token' => $user->access_token];
            }
            Telegram::sendDebug("Неудачная попытка входа " . $user->name . ", логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
            return ['status' => 'failed', 'message' => 'invalid data'];
        }
        Telegram::sendDebug("Неудачная попытка входа, логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
        return ['status' => 'failed', 'message' => 'invalid data'];
    }

    private static function getTaskList()
    {
        // получу учётную запись по токену
        $token = self::$data['token'];
        if(!empty($token)){
            $user = PersonalItems::findOne(['access_token' => $token]);
            if($user !== null){
                return ['status' => 'success', 'list' => PersonalTask::find()->where(['initiator' => $user->id])->all()];
            }
        }
        return ['status' => 'failed', 'message' => 'invalid data'];
    }
}