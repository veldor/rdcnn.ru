<?php


namespace app\controllers;


use app\priv\Info;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use yii\web\Controller;

class TelegramController extends Controller
{
    /**
     * @throws InvalidJsonException
     */
    public function actionConnect(): void
    {
        $token = Info::TG_BOT_TOKEN;
        $bot = new Client($token);
// команда для start
        $bot->command('start', static function ($message) use ($bot) {
            $answer = 'Добро пожаловать!';
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

// команда для помощи
        $bot->command('help', function ($message) use ($bot) {
            $answer = 'Команды:
/help - вывод справки';
            $bot->sendMessage($message->getChat()->getId(), $answer);
        });

        $bot->run();
    }
}