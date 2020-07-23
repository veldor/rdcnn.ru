<?php


namespace app\models;


use app\models\database\ViberPersonalList;
use app\models\utils\GrammarHandler;
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
                $answer = 'Добро пожаловать! /help для вывода команд';
                /** @var Message $message */
                $bot->sendMessage($message->getChat()->getId(), $answer);
            });

// команда для помощи
            $bot->command('help', static function ($message) use ($bot) {
                try{
                    /** @var Message $message */
                    // проверю, зарегистрирован ли пользователь как работающий у нас
                    if(ViberPersonalList::iWorkHere($message->getChat()->getId())){
                        $answer = 'Команды:
/help - вывод справки
/conc - список незагруженных заключений
/exec - список незагруженных обследований';
                    }
                    else{
                        $answer = 'Команды:
/help - вывод справки';
                    }
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(), $answer);
                }
                catch (Exception $e){
                    $bot->sendMessage($message->getChat()->getId(), $e->getMessage());
                }
            });
// команда для вывода незагруженных заключений
            $bot->command('conc', static function ($message) use ($bot) {
                /** @var Message $message */
                // проверю, зарегистрирован ли пользователь как работающий у нас
                if(ViberPersonalList::iWorkHere($message->getChat()->getId())){
                    $withoutConclusions = Table_availability::getWithoutConclusions();
                    if (!empty($withoutConclusions)) {
                        $answer = "Не загружены заключения:\n " . $withoutConclusions;
                    }
                    else{
                        $answer = 'Вау, все заключения загружены!';
                    }
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(), $answer);
                }
            });
// команда для вывода незагруженных обследований
            $bot->command('exec', static function ($message) use ($bot) {
                /** @var Message $message */
                // проверю, зарегистрирован ли пользователь как работающий у нас
                if(ViberPersonalList::iWorkHere($message->getChat()->getId())){
                    $withoutExecutions = Table_availability::getWithoutExecutions();
                    if (!empty($withoutExecutions)) {
                        $answer = "Не загружены файлы:\n " . $withoutExecutions;
                    }
                    else{
                        $answer = 'Вау, все файлы загружены!';
                    }
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(), $answer);
                }
            });

            $bot->on(/**
             * @param $Update Update
             */ static function ($Update) use ($bot) {
                /** @var Update $Update */
                /** @var Message $message */
                try{
                    $message = $Update->getMessage();
                    $document = $message->getDocument();
                    if($document !== null && ViberPersonalList::iWorkHere($message->getChat()->getId())){
                        $mime = $document->getMimeType();
                        if($mime === 'application/pdf'){
                            $bot->sendMessage($message->getChat()->getId(), 'обрабатываю PDF');
                            $file = $bot->getFile($document->getFileId());
                            // в строке- содержимое файла
                            $downloadedFile = $bot->downloadFile($file->getFileId());
                            if(!empty($downloadedFile) && $downloadedFile !== ''){
                                // файл получен
                                // файл получен
                                // сохраню полученный файл во временную папку
                                $path = FileUtils::saveTempFile($downloadedFile, '.pdf');
                                if(is_file($path)){
                                    $answer = FileUtils::handleFileUpload($path);
                                    $file = new \CURLFile($answer, 'application/pdf', GrammarHandler::getFileName($answer));
                                    if(is_file($answer)){
                                        $bot->sendDocument(
                                            $message->getChat()->getId(),
                                            $file,
                                            GrammarHandler::getFileName($answer)
                                        );
                                    }
                                    else{
                                        $bot->sendMessage($message->getChat()->getId(), $answer);
                                    }
                                }
                            }
                        }
                        else if($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'){
                            $bot->sendMessage($message->getChat()->getId(), 'обрабатываю DOCX');
                            $file = $bot->getFile($document->getFileId());
                            // в строке- содержимое файла
                            $downloadedFile = $bot->downloadFile($file->getFileId());
                            if(!empty($downloadedFile) && $downloadedFile !== ''){
                                // файл получен
                                // сохраню полученный файл во временную папку
                                $path = FileUtils::saveTempFile($downloadedFile, '.docx');
                                if(is_file($path)){
                                    $answer = FileUtils::handleFileUpload($path);
                                    $file = new \CURLFile($answer, 'application/pdf', GrammarHandler::getFileName($answer));
                                    if(is_file($answer)){
                                        $bot->sendDocument(
                                            $message->getChat()->getId(),
                                            $file,
                                            GrammarHandler::getFileName($answer)
                                        );
                                    }
                                    else{
                                        $bot->sendMessage($message->getChat()->getId(), $answer);
                                    }
                                    unlink($path);
                                }
                            }
                        }
                        else{
                            $bot->sendMessage($message->getChat()->getId(), 'Я понимаю только файлы в формате PDF и DOCX');
                        }
                    }
                    else{
                        $msg_text = $message->getText();
                        // получен простой текст, обработаю его в зависимости от содержимого
                        $answer = self::handleSimpleText($msg_text, $message);
                        $bot->sendMessage($message->getChat()->getId(), $answer);
                    }
                }
                catch (Exception $e){
                    $bot->sendMessage($message->getChat()->getId(), $e->getMessage());
                }
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

    private static function handleSimpleText(string $msg_text, Message $message):string
    {
        switch ($msg_text){
            // если введён токен доступа- уведомлю пользователя об успешном входе в систему
            case Info::VIBER_SECRET:
                // регистрирую получателя
                ViberPersonalList::register($message->getChat()->getId());
                return 'Ага, вы работаете на нас :) /help для списка команд';
        }
        return 'Не понимаю, о чём вы :(';
    }
}