<?php


namespace app\models;


use app\models\database\ViberPersonalList;
use app\priv\Info;
use Exception;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;

class Telegram
{
    public static function handleRequest(): void
    {

        try{
            $token = Info::TG_BOT_TOKEN;
            /** @var BotApi|Client $bot */
            $bot = new Client($token);
// команда для start
            $bot->command(/**
             * @param $message Message
             */ 'start', static function ($message) use ($bot) {
                $answer = 'Добро пожаловать!';
                /** @var Message $message */
                $bot->sendMessage($message->getChat()->getId(), $answer);
            });

// команда для помощи
            $bot->command('help', static function ($message) use ($bot) {
                $answer = 'Команды:
/help - вывод справки';
                /** @var Message $message */
                $bot->sendMessage($message->getChat()->getId(), $answer);
            });

            $bot->on(/**
             * @param $Update Update
             */ static function ($Update) use ($bot) {
                /** @var Update $Update */
                /** @var Message $message */
                $message = $Update->getMessage();
                $msg_text = $message->getText();
                // получен простой текст, обработаю его в зависимости от содержимого
                $answer = self::handleSimpleText($msg_text, $message);
                $bot->sendMessage($message->getChat()->getId(), 'You text: ' . $answer);
            }, static function () { return true; });

            try {
                $bot->run();
            } catch (InvalidJsonException $e) {
                // что-то сделаю потом
            }
        }
        catch (Exception $e){
            // запишу ошибку в лог
            $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/telebot_err_' . time() . '.log';
            $report = $e->getMessage();
            file_put_contents($file, $report);
        }
    }

    private static function handleSimpleText(string $msg_text, \TelegramBot\Api\Types\Message $message):string
    {
        switch ($msg_text){
            // если введён токен доступа- уведомлю пользователя об успешном входе в систему
            case Info::VIBER_SECRET:
                // регистрирую получателя
                //ViberPersonalList::register($message->getChat()->getId());
                return 'Ага, вы работаете на нас :)';
        }
        return 'Не понимаю, о чём вы :(';
    }
}