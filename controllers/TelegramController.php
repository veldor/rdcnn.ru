<?php


namespace app\controllers;


use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

class TelegramController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if ($action->id === 'connect') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }
    public function actionConnect(): void
    {
        $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/telebot_msg_' . time() . '.log';
        $report = 'test';
        file_put_contents($file, $report);
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

            $bot->on(function ($Update) use ($bot) {
                $message = $Update->getMessage();
                $msg_text = $message->getText();

                $bot->sendMessage($message->getChat()->getId(), "You text: " . $msg_text);
            }, function () { return true; });

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