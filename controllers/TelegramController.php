<?php


namespace app\controllers;


use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use yii\web\Controller;

class TelegramController extends Controller
{
    public function actionConnect(): void
    {
        try{
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

            try {
                $bot->run();
            } catch (InvalidJsonException $e) {
                // запишу ошибку в лог
                $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/telebot_' . time() . '.log';
                $report = $e->getMessage();
                file_put_contents($file, $report);
            }
        }
        catch (Exception $e){
            // запишу ошибку в лог
            $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/telebot_err_' . time() . '.log';
            $report = $e->getMessage();
            file_put_contents($file, $report);
        }
    }
}