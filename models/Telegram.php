<?php


namespace app\models;


use app\models\database\TempDownloadLinks;
use app\models\database\ViberPersonalList;
use app\models\utils\ComHandler;
use app\models\utils\FilesHandler;
use app\models\utils\FirebaseHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\MailHandler;
use app\models\utils\MailSettings;
use app\models\utils\Management;
use app\priv\Info;
use CURLFile;
use Exception;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use Yii;

class Telegram
{
    public static function handleRequest(): void
    {

        try {
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
                try {
                    /** @var Message $message */
                    // проверю, зарегистрирован ли пользователь как работающий у нас
                    if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                        $answer = 'Команды:
/help - вывод справки
/cbl - очистить чёрный список IP
/fb - отправить тестовые сообщения Firebase
/upd - обновить ПО сервера
/v - текущая версия ПО
/avr_today - не загружено сегодня в Авроре
/nv_today - не загружено сегодня на НВ
/conc - список незагруженных заключений
/exec - список незагруженных обследований';
                    } else {
                        $answer = 'Команды:
/help - вывод справки';
                    }
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(), $answer);
                } catch (Exception $e) {
                    $bot->sendMessage($message->getChat()->getId(), $e->getMessage());
                }
            });
// команда для очистки чёрного списка
            $bot->command('cbl', static function ($message) use ($bot) {
                /** @var Message $message */
                // проверю, зарегистрирован ли пользователь как работающий у нас
                if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                    Table_blacklist::clear();
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(), 'Чёрный список вычищен');
                }
            });
// команда получения списка незагруженных материалов для конкретного центра за конкретный день
            $bot->command('avr_today', static function ($message) use ($bot) {
                /** @var Message $message */
                // проверю, зарегистрирован ли пользователь как работающий у нас
                if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                    $startOfInterval = Utils::getStartInterval(true);
                    $endOfInterval = Utils::getEndInterval(true);
                    // получу зарегистрированных сегодня в авроре
                    $registeredToday = User::findRegistered('a', $startOfInterval, $endOfInterval);
                    $list = '';
                    if (!empty($registeredToday)) {
                        /** @var User $item */
                        foreach ($registeredToday as $item) {
                            if (!Table_availability::isConclusion($item)) {
                                $list .= "{$item->username}: нет заключения\n";
                            }
                            if (!Table_availability::isExecution($item)) {
                                $list .= "{$item->username}: нет снимков\n";
                            }

                        }
                    }
                    if (empty($list)) {
                        $bot->sendMessage($message->getChat()->getId(), 'Всё загружено');
                    }
                }
            });
// команда получения списка незагруженных материалов для конкретного центра за конкретный день
            $bot->command('nv_today', static function ($message) use ($bot) {
                /** @var Message $message */
                // проверю, зарегистрирован ли пользователь как работающий у нас
                if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                    $startOfInterval = Utils::getStartInterval(true);
                    $endOfInterval = Utils::getEndInterval(true);
                    // получу зарегистрированных сегодня в авроре
                    $registeredToday = User::findRegistered('n', $startOfInterval, $endOfInterval);
                    $list = '';
                    if (!empty($registeredToday)) {
                        /** @var User $item */
                        foreach ($registeredToday as $item) {
                            if (!Table_availability::isConclusion($item)) {
                                $list .= "{$item->username}: нет заключения\n";
                            }
                            if (!Table_availability::isExecution($item)) {
                                $list .= "{$item->username}: нет снимков\n";
                            }

                        }
                    }
                    if (empty($list)) {
                        $bot->sendMessage($message->getChat()->getId(), 'Всё загружено');
                    }
                }
            });
// команда для отображения версии сервера
            $bot->command('v', static function ($message) use ($bot) {
                self::saveLastHandledMessage(time() . " проверка версии ПО");
                /** @var Message $message */
                /** @var Message $message */
                $versionFile = Yii::$app->basePath . '\\version.info';
                if (is_file($versionFile)) {
                    try {
                        $result = $bot->sendMessage($message->getChat()->getId(), 'Текущая версия: ' . file_get_contents($versionFile));
                        if ($result !== null) {
                            FileUtils::setTelegramLog(time() . 'сообщение отправлено');
                        } else {
                            FileUtils::setTelegramLog(time() . 'сообщение не отправлено');
                        }
                    } catch (Exception $e) {
                        FileUtils::setTelegramLog('Ошибка отправки : ' . $e->getMessage());
                    }
                } else {
                    $bot->sendMessage($message->getChat()->getId(), 'Файл с версией сервера не обнаружен');
                }
            });
// команда для отправки тестовых сообщений
            $bot->command('fb', static function ($message) use ($bot) {
                /** @var Message $message */
                $result = FirebaseHandler::sendTest();
                $bot->sendMessage($message->getChat()->getId(), 'Сообщения отправлены');
            });
// команда для обновления ПО сервера
            $bot->command('upd', static function ($message) use ($bot) {
                /** @var Message $message */
                // проверю, зарегистрирован ли пользователь как работающий у нас
                if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                    Management::updateSoft();
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(), 'Обновляю ПО через телеграм-запрос');
                }
            });
// команда для вывода незагруженных заключений
            $bot->command('conc', static function ($message) use ($bot) {
                /** @var Message $message */
                // проверю, зарегистрирован ли пользователь как работающий у нас
                if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                    $withoutConclusions = Table_availability::getWithoutConclusions();
                    if (!empty($withoutConclusions)) {
                        $answer = "Не загружены заключения:\n " . $withoutConclusions;
                    } else {
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
                if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                    $withoutExecutions = Table_availability::getWithoutExecutions();
                    if (!empty($withoutExecutions)) {
                        $answer = "Не загружены файлы:\n " . $withoutExecutions;
                    } else {
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
                try {
                    $message = $Update->getMessage();
                    $document = $message->getDocument();
                    if (ViberPersonalList::iWorkHere($message->getChat()->getId())) {
                        if ($document !== null) {
                            $mime = $document->getMimeType();
                            $bot->sendMessage($message->getChat()->getId(), 'Mime is ' . $mime);
                            if ($mime === 'application/pdf') {
                                $bot->sendMessage($message->getChat()->getId(), 'обрабатываю PDF');
                                $file = $bot->getFile($document->getFileId());
                                // в строке- содержимое файла
                                $downloadedFile = $bot->downloadFile($file->getFileId());
                                if (!empty($downloadedFile) && $downloadedFile !== '') {
                                    // файл получен
                                    // файл получен
                                    // сохраню полученный файл во временную папку
                                    $path = FileUtils::saveTempFile($downloadedFile, '.pdf');
                                    if (is_file($path)) {
                                        $answer = FileUtils::handleFileUpload($path);
                                        // отправлю сообщение с данными о фале
                                        $fileName = GrammarHandler::getFileName($answer);
                                        $availItem = Table_availability::findOne(['file_name' => $fileName]);
                                        if ($availItem !== null) {
                                            $bot->sendMessage($message->getChat()->getId(), "Обработано заключение\nИмя пациента: {$availItem->patient_name}\nОбласть обследования:{$availItem->execution_area}\nНомер обследования:{$availItem->userId}");
                                        }
                                        $file = new CURLFile($answer, 'application/pdf', $fileName);
                                        if (is_file($answer)) {
                                            $bot->sendDocument(
                                                $message->getChat()->getId(),
                                                $file
                                            );
                                        } else {
                                            $bot->sendMessage($message->getChat()->getId(), $answer);
                                        }
                                    }
                                }
                            } else if ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                                $bot->sendMessage($message->getChat()->getId(), 'обрабатываю DOCX');
                                $file = $bot->getFile($document->getFileId());
                                // в строке- содержимое файла
                                $downloadedFile = $bot->downloadFile($file->getFileId());
                                if (!empty($downloadedFile) && $downloadedFile !== '') {
                                    // файл получен
                                    // сохраню полученный файл во временную папку
                                    $path = FileUtils::saveTempFile($downloadedFile, '.docx');
                                    self::sendFileBack($path, $bot, $message);
                                }
                            } else if ($mime === 'application/msword') {
                                $bot->sendMessage($message->getChat()->getId(), 'обрабатываю DOC');
                                $file = $bot->getFile($document->getFileId());
                                // в строке- содержимое файла
                                $downloadedFile = $bot->downloadFile($file->getFileId());
                                if (!empty($downloadedFile) && $downloadedFile !== '') {
                                    // файл получен
                                    // сохраню полученный файл во временную папку
                                    $path = FileUtils::saveTempFile($downloadedFile, '.doc');
                                    self::sendFileBack($path, $bot, $message);
                                }
                            } else if ($mime === 'application/zip') {
                                $bot->sendMessage($message->getChat()->getId(), 'Разбираю архив');
                                $dlFile = $bot->getFile($document->getFileId());
                                // скачаю файл в фоновом режиме
                                $file = Yii::$app->basePath . '\\yii.bat';
                                if (is_file($file)) {
                                    $command = "$file console/handle-zip " . $dlFile->getFileId() . ' ' . $message->getChat()->getId();
                                    ComHandler::runCommand($command);
                                }
                            } else {
                                $bot->sendMessage($message->getChat()->getId(), 'Я понимаю только файлы в формате PDF и DOCX (и ZIP)');
                            }
                        } else {
                            // зарегистрируюсь для получения ошибок обработки
                            $msg_text = $message->getText();
                            if ($msg_text === 'register for errors') {
                                ViberPersonalList::subscribeGetErrors($message->getChat()->getId());
                                $bot->sendMessage($message->getChat()->getId(), 'Вы подписаны на получение ошибок');
                                return;
                            }
                            if(GrammarHandler::startsWith($msg_text, "/dl_")){
                                // find all files and create a temp links for it
                                $executionId = substr($msg_text, 4);
                                // find files
                                $user = User::findByUsername($executionId);
                                if($user !== null){
                                    $existentFiles = Table_availability::getFilesInfo($user);
                                    if(!empty($existentFiles)){
                                        $answer = '';
                                        foreach ($existentFiles as $file) {
                                            $answer .= $file['name'] . "\n";
                                            $link = TempDownloadLinks::createLink($user, $file['type'], $file['fileName']);
                                            $answer .= "https://rdcnn.ru/dl/$link->link\n";
                                            //$answer .= 'Ссылка действительна только для одной загрузки!';
                                        }
                                        $bot->sendMessage($message->getChat()->getId(), $answer);

                                    }
                                    else{
                                        $bot->sendMessage($message->getChat()->getId(), 'Файлов по данному обследованию не найдено');
                                    }
                                }
                                else{
                                    $bot->sendMessage($message->getChat()->getId(), 'Файлов по данному обследованию не найдено');
                                }
                                return $executionId;
                            }
                            $bot->sendMessage($message->getChat()->getId(), $msg_text);
                        }
                    } else {
                        $msg_text = $message->getText();
                        // получен простой текст, обработаю его в зависимости от содержимого
                        $answer = self::handleSimpleText($msg_text, $message);
                        $bot->sendMessage($message->getChat()->getId(), $answer);
                    }
                } catch (Exception $e) {
                    $bot->sendMessage($message->getChat()->getId(), $e->getMessage());
                }
            }, static function () {
                return true;
            });

            try {
                $bot->run();
            } catch (InvalidJsonException $e) {
                // что-то сделаю потом
            }
        } catch (Exception $e) {
            // запишу ошибку в лог
            $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/telebot_err_' . time() . '.log';
            $report = $e->getMessage();
            file_put_contents($file, $report);
        }
    }

    private static function handleSimpleText(string $msg_text, Message $message): string
    {
        switch ($msg_text) {
            // если введён токен доступа- уведомлю пользователя об успешном входе в систему
            case Info::VIBER_SECRET:
                // регистрирую получателя
                ViberPersonalList::register($message->getChat()->getId());
                return 'Ага, вы работаете на нас :) /help для списка команд';
        }
        return 'Не понимаю, о чём вы :( (вы написали ' . $msg_text . ')';
    }

    /**
     * @param string $path
     * @param $bot
     * @param Message $message
     * @throws \TelegramBot\Api\Exception
     * @throws InvalidArgumentException
     * @throws \yii\base\Exception
     */
    private static function sendFileBack(string $path, $bot, Message $message): void
    {
        /** @var BotApi|Client $bot */
        if (is_file($path)) {
            $answer = FileUtils::handleFileUpload($path);
            $file = new CURLFile($answer, 'application/pdf', GrammarHandler::getFileName($answer));
            if (is_file($answer)) {
                $bot->sendDocument(
                    $message->getChat()->getId(),
                    $file
                );
            } else {
                $bot->sendMessage($message->getChat()->getId(), $answer);
            }
            unlink($path);
        }
    }

    /**
     * @param string $errorInfo
     */
    public static function sendDebug(string $errorInfo): void
    {
        try {
            // проверю, есть ли учётные записи для отправки данных
            $subscribers = ViberPersonalList::findAll(['get_errors' => 1]);
            if (!empty($subscribers)) {
                $token = Info::TG_BOT_TOKEN;
                /** @var BotApi|Client $bot */
                $bot = new Client($token);
                foreach ($subscribers as $subscriber) {
                    $bot->sendMessage($subscriber->viber_id, $errorInfo);
                }
            }
        } catch (Exception $e) {
            try {
                // отправлю письмо с ошибкой на почту
                $mail = Yii::$app->mailer->compose()
                    ->setFrom([MailSettings::getInstance()->address => 'РДЦ'])
                    ->setSubject('Не получилось отправить сообщение боту')
                    ->setHtmlBody($errorInfo . "<br/>" . $e->getMessage())
                    ->setTo(['eldorianwin@gmail.com' => 'eldorianwin@gmail.com']);
                // попробую отправить письмо, в случае ошибки- вызову исключение
                $mail->send();
            } catch (Exception $e) {
                // ну тут уж ничего не сделать...
            }
        }
    }

    public static function downloadZip(string $fileId, $clientId): void
    {
        try {
            $token = Info::TG_BOT_TOKEN;
            /** @var BotApi|Client $bot */
            $bot = new Client($token);
            $file = $bot->getFile($fileId);
            $downloadedFile = $bot->downloadFile($file->getFileId());
            $bot->sendMessage($clientId, 'Архив скачан');
            $path = FileUtils::saveTempFile($downloadedFile, '.zip');
            if (is_file($path)) {
                // сохраню файл
                $num = FilesHandler::unzip($path);
                if ($num !== null) {
                    $bot->sendMessage($clientId, 'Добавлены файлы сканирования обследования ' . $num);
                } else {
                    $bot->sendMessage($clientId, 'Не смог обработать архив');
                }
            }
        } catch (Exception $e) {
            self::sendDebug("Ошибка при обработке команды: " . $e->getMessage());
        }
    }

    /**
     * @param string $message
     */
    private static function saveLastHandledMessage(string $message): void
    {
        $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/last_tg_message.log';
        file_put_contents($file, $message);
    }
}