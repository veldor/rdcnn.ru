<?php


namespace app\models;


use app\models\database\PersonalItems;
use JsonException;
use RuntimeException;
use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;

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
        if(!empty($data['cmd'])){
            switch ($data['cmd']){
                case 'login':
                    self::login();
            }
        }
    }

    private static function login()
    {
        $login = self::$data['login'];
        $password = self::$data['pass'];
        $user = PersonalItems::findOne(['login' => $login]);
        if($user !== null){
            if(Yii::$app->security->validatePassword($password, $user->pass_hash)){
                // всё верно, верну токен доступа
                Telegram::sendDebug("Успешный вход: " . $user->name);
            }
            else{
                Telegram::sendDebug("Неудачная попытка входа " . $user->name . ", логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
            }
        }
        else{
            Telegram::sendDebug("Неудачная попытка входа, логин:" . self::$data['login'] . " ,пароль: " . self::$data['pass']);
        }
    }
}