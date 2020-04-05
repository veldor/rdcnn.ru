<?php


namespace app\models;


use app\models\database\TempDownloadLinks;
use app\models\database\ViberMessaging;
use app\models\database\ViberPersonalList;
use app\models\database\ViberSubscriptions;
use app\priv\Info;
use Exception;
use Viber\Api\Event;
use Viber\Api\Keyboard;
use Viber\Api\Keyboard\Button;
use Viber\Api\Message\File;
use Viber\Api\Message\Text;
use Viber\Api\Sender;
use Viber\Bot;
use Viber\Client;
use Yii;
use yii\base\Model;
use yii\helpers\Url;

class Viber extends Model
{

    public const CONCLUSIONS = 'заключения';
    public const EXECUTIONS = 'файлы';

    public static function notifyExecutionLoaded()
    {

    }

    public static function notifyConclusionLoaded()
    {

    }

    /**
     * регистрация хука
     */
    public static function setup(): void
    {

        $apiKey = Info::VIBER_API_KEY;
        $webHookUrl = 'https://rdcnn.ru/viber/connect';
        try {
            $client = new Client(['token' => $apiKey]);
            $result = $client->setWebhook($webHookUrl);
            var_dump($result);
            echo "Success!\n";
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public static function handleRequest(): void
    {
        $apiKey = Info::VIBER_API_KEY;

// так будет выглядеть наш бот (имя и аватар - можно менять)
        $botSender = new Sender([
            'name' => 'Бот РДЦ',
            'avatar' => 'https://developers.viber.com/img/favicon.ico',
        ]);

        try {
            $bot = new Bot(['token' => $apiKey]);
            $bot
                // При подключении бота
                ->onConversation(static function () use ($bot, $botSender) {
                    return (new Text())
                        ->setSender($botSender)
                        ->setText('Добрый день. Я бот РДЦ. Выберите, что вы хотите сделать')
                        ->setKeyboard(
                            (new Keyboard())
                                ->setButtons([
                                    (new Button())
                                        ->setBgColor('#2fa4e7')
                                        ->setTextHAlign('center')
                                        ->setActionType('reply')
                                        ->setActionBody('get-data')
                                        ->setText('Получить заключение'),
                                    (new Button())
                                        ->setBgColor('#2fa4e7')
                                        ->setTextHAlign('center')
                                        ->setActionType('reply')
                                        ->setActionBody('more-actions')
                                        ->setText('Что-то ещё'),

                                ])
                        );
                })
                // действие при подписке
                ->onSubscribe(static function () use ($bot, $botSender) {
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setText('Сейчас продолжим')
                    );
                })
                ->onText(/**
                 * @param $event Event
                 */ '|get-data|s', static function ($event) use ($bot, $botSender) {
                    $receiverId = $event->getSender()->getId();
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            ->setText('Введите через пробел номер обследования и пароль, например: 1 432')
                    );
                })
                ->onText('|more-actions|s', static function ($event) use ($bot, $botSender) {
                    $receiverId = $event->getSender()->getId();
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            ->setText('Напишите, что бы вы хотели сделать')
                    );
                })
//                ->onText(/**
//                 * @param $event Event
//                 */ '|^[aа]?\d+ \d{4}$|isu', static function ($event) use ($bot, $botSender) {
//                    $receiverId = $event->getSender()->getId();
//                    // попробую найти обследование по переданным данным
//                    $text = $event->getMessage()->getText();
//                    self::logMessaging($receiverId, 'ищу обследование' . $text);
//                    [$id, $password] = explode(' ', $text);
//                    self::logMessaging($receiverId, "id is $id pass is $password");
//
//                    if(!empty($id)){
//                        $user = User::findByUsername($id);
//                        $bot->getClient()->sendMessage(
//                            (new Text())
//                                ->setSender($botSender)
//                                ->setReceiver($receiverId)
//                                ->setText('hehe')
//                        );
//                        if($id === null){
//                            $bot->getClient()->sendMessage(
//                                (new Text())
//                                    ->setSender($botSender)
//                                    ->setReceiver($receiverId)
//                                    ->setText('Вы ввели неверный номер обследования или неправильный пароль. Можете попробовать ещё раз или обратиться к нам за помощью по номеру 2020200')
//                            );
//                        }
//                        else{
//
//                            $bot->getClient()->sendMessage(
//                                (new Text())
//                                    ->setSender($botSender)
//                                    ->setReceiver($receiverId)
//                                    ->setText('Кажется, я что-то нашёл...')
//                            );
//                            if ($user->failed_try > 20) {
//                                $bot->getClient()->sendMessage(
//                                    (new Text())
//                                        ->setSender($botSender)
//                                        ->setReceiver($receiverId)
//                                        ->setText('Было выполнено слишком много неверных попыток ввода пароля. В целях безопасности данные были удалены. Вы можете обратиться к нам для восстановления доступа')
//                                );
//                            }
//                            // проверю совпадение пароля, если не совпадает- зарегистрирую ошибку
//                            if(!$user->validatePassword($password)){
//                                $user->last_login_try = time();
//                                $user->failed_try = ++$user->failed_try;
//                                $user->save();
//                                $bot->getClient()->sendMessage(
//                                    (new Text())
//                                        ->setSender($botSender)
//                                        ->setReceiver($receiverId)
//                                        ->setText('Вы ввели неверный номер обследования или неправильный пароль. Можете попробовать ещё раз или обратиться к нам за помощью по номеру 2020200')
//                                );
//                            }
//                            else{
//                                // подпишу пользователя на обновление информации
//                                $bot->getClient()->sendMessage(
//                                    (new Text())
//                                        ->setSender($botSender)
//                                        ->setReceiver($receiverId)
//                                        ->setText('Вы ввели правильные данные, спасибо! Мы будем посылать вам информацию по мере поступления!')
//                                );
//                                self::subscribe($receiverId, $user->username);
//                                // проверю наличие заключения и файлов
//                                $isFiles = ExecutionHandler::isExecution($user->username);
//                                $isConclusion = ExecutionHandler::isConclusion($user->username);
//                                if(!$isFiles && !$isConclusion){
//                                    $bot->getClient()->sendMessage(
//                                        (new Text())
//                                            ->setSender($botSender)
//                                            ->setReceiver($receiverId)
//                                            ->setText('Данные по вашему обследованию пока не получены. Мы напишем вам сразу же, как они будут получены')
//                                    );
//                                }
//                                else{
//                                    // отправлю ссылки на скачивание файлов
//                                    if($isConclusion){
//                                        Table_viber_download_links::getConclusionLinks($user->username);
//                                    }
//                                }
//                            }
//                        }
//                    }
//                    else{
//                        $bot->getClient()->sendMessage(
//                            (new Text())
//                                ->setSender($botSender)
//                                ->setReceiver($receiverId)
//                                ->setText('Не распознал номер обследования, напишите ещё раз...')
//                        );
//                    }
//                })
                ->onText('|.+|s', static function ($event) use ($bot, $botSender) {
                    $receiverId = $event->getSender()->getId();
                    $text = $event->getMessage()->getText();
                    self::handleTextRequest($receiverId, $text, $bot, $botSender);
                })
                ->run();
        } catch (Exception $e) {
            $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/viber_err_' . time() . '.log';
            $report = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            file_put_contents($file, $report);
        }
    }

    public static function subscribe($receiverId, $patientId): void
    {
        // проверю, не подписан ли уже пользователь
        $existentSubscribe = ViberSubscriptions::findOne(['viber_id' => $receiverId]);
        if ($existentSubscribe !== null) {
            $existentSubscribe->patient_id = $patientId;
            $existentSubscribe->save();
        } else {
            (new ViberSubscriptions(['patient_id' => $patientId, 'viber_id' => $receiverId]))->save();
            self::logAction('подписка оформлена');
        }
    }


    /**
     * @param $receiverId
     * @param $text
     * @param $bot
     * @param $botSender
     * @throws \yii\base\Exception
     */
    public static function handleTextRequest($receiverId, $text, $bot, $botSender): void
    {
        $lowerText = mb_strtolower($text);
        $workHere = ViberPersonalList::iWorkHere($receiverId);
        self::logMessaging($receiverId, $text);
        $executionPattern = '/^([aа]?\d+) (\d{4})$/ui';
        if (preg_match($executionPattern, $text, $matches)) {
            $executionNumber = $matches[1];
            self::sendMessage($bot, $botSender, $receiverId, 'Ищу информацию об обследовании № ' . $executionNumber);
            $execution = User::findByUsername($executionNumber);
            if ($execution === null) {
                self::sendMessage($bot, $botSender, $receiverId, "Обследование №$executionNumber не найдено! Попробуйте ввести данные ещё раз.");
            } else {
                // проверю, подходит ли пароль
                if ($execution->failed_try > 20) {
                    self::sendMessage($bot, $botSender, $receiverId, 'Было выполнено слишком много неверных попыток ввода пароля. В целях безопасности данные были удалены. Вы можете обратиться к нам для восстановления доступа');
                    return;
                }

                $password = $matches[2];
// проверю совпадение пароля, если не совпадает- зарегистрирую ошибку
                if (!$execution->validatePassword($password)) {
                    $execution->last_login_try = time();
                    $execution->failed_try = ++$execution->failed_try;
                    $execution->save();
                    self::sendMessage($bot, $botSender, $receiverId, 'Вы ввели неверный номер обследования или неправильный пароль. Можете попробовать ещё раз или обратиться к нам за помощью по номеру 2020200');
                } else {
                    self::sendMessage($bot, $botSender, $receiverId, 'Вы ввели верные данные, спасибо. Вы получите результаты как только они будут готовы!');
                    self::subscribe($receiverId, $execution->id);
                    ExecutionHandler::checkAvailabilityForBots($execution->id, true, $receiverId);
                }
            }
        } elseif ($lowerText === 'я работаю в рдц') {
            // запрос доступа к приватным данным
            self::sendMessage($bot, $botSender, $receiverId, 'Докажите');
        } elseif ($text === Info::VIBER_SECRET) {
            // регистрирую пользователя как нашего сотрудника
            ViberPersonalList::register($receiverId);
            self::sendMessage($bot, $botSender, $receiverId, 'Ок, вы работаете у нас. Теперь у вас есть доступ к закрытым функциям. Чтобы увидеть список команд, введите "команды"');
        } elseif ($lowerText === 'команды') {
            if ($workHere) {
                // отправлю список команд, которые может выполнять сотрудник
                self::sendMessage(
                    $bot,
                    $botSender,
                    $receiverId,
                    "'" . self::CONCLUSIONS . "' : выводит список пациентов без заключений\n'файлы' : выводит список пациентов без загруженных файлов обследования\n'статистика загрузок' : выводит статистику загрузок заключений и файлов\nсписок будет дополняться по мере развития"
                );
            } else {
                self::sendMessage($bot, $botSender, $receiverId, 'Не понимаю, что вы имеете в виду');
            }
        } elseif ($lowerText === self::CONCLUSIONS && $workHere) {
                // получу список обследований без заключений
                $withoutConclusions = Table_availability::getWithoutConclusions();
                if($withoutConclusions !== null){
                    $list = "Не загружены заключения:\n";
                    foreach ($withoutConclusions as $withoutConclusion) {
                        $user = User::findByUsername($withoutConclusion->userId);
                        if($user !== null){
                            $list .= "{$user->username}\n";
                        }
                    }
                    self::sendMessage($bot, $botSender, $receiverId, $list);
                }
                else{
                    self::sendMessage($bot, $botSender, $receiverId, 'Вау, все заключения загружены!');
                }
        }
        elseif($lowerText === self::EXECUTIONS && $workHere){
            $withoutExecutions = Table_availability::getWithoutExecutions();
            if($withoutExecutions !== null){
                $list = "Не загружены файлы:\n";
                foreach ($withoutExecutions as $withoutExecution) {
                    $user = User::findByUsername($withoutExecution->userId);
                    if($user !== null){
                        $list .= "{$user->username}\n";
                    }
                }
                self::sendMessage($bot, $botSender, $receiverId, $list);
            }
            else{
                self::sendMessage($bot, $botSender, $receiverId, 'Вау, все файлы загружены!');
            }
        }
        else {
            self::sendMessage($bot, $botSender, $receiverId, 'Делаю вид, что работаю');
        }
    }

    public static function logMessaging($receiverId, $text): void
    {
        (new ViberMessaging(['timestamp' => time(), 'text' => $text, 'receiver_id' => $receiverId]))->save();
    }

    private static function logAction($text): void
    {
        $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/viber_log_' . time() . '.log';
        file_put_contents($file, $text);
    }

    /**
     * Отправляю ссобщение пользователю
     * @param $bot Bot
     * @param $botSender
     * @param $receiverId
     * @param $text
     */
    private static function sendMessage($bot, $botSender, $receiverId, $text): void
    {
        $bot->getClient()->sendMessage(
            (new Text())
                ->setSender($botSender)
                ->setReceiver($receiverId)
                ->setText($text)
        );
    }

    /**
     * @param string $subscriberId
     * @param string $link
     */
    public static function sendTempLink(string $subscriberId, string $link): void
    {
        $bot = new Bot(['token' => Info::VIBER_API_KEY]);
        $botSender = new Sender([
            'name' => 'Бот РДЦ',
            'avatar' => 'https://developers.viber.com/img/favicon.ico',
        ]);
        $linkInfo = TempDownloadLinks::findOne(['link' => $link]);
        if ($linkInfo !== null) {
            if ($linkInfo->file_type === 'conclusion') {
                self::sendMessage($bot, $botSender, $subscriberId, 'Заключение врача готово!');
            } else {
                self::sendMessage($bot, $botSender, $subscriberId, 'Файлы сканирования загружены!');
            }
            self::sendFile($subscriberId, $link);
        }
    }

    /**
     * @param string $subscriberId
     * @param string $link
     */
    private static function sendFile(string $subscriberId, string $link): void
    {
        $linkInfo = TempDownloadLinks::findOne(['link' => $link]);
        if ($linkInfo !== null) {
            if ($linkInfo->file_type === 'execution') {
                $file = Yii::getAlias('@executionsDirectory') . '\\' . $linkInfo->file_name;
            } else {
                $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $linkInfo->file_name;
            }
            if (is_file($file)) {
                $bot = new Bot(['token' => Info::VIBER_API_KEY]);
                $botSender = new Sender([
                    'name' => 'Бот РДЦ',
                    'avatar' => 'https://developers.viber.com/img/favicon.ico',
                ]);
                $bot->getClient()->sendMessage(
                    (new File())
                        ->setSender($botSender)
                        ->setReceiver($subscriberId)
                        ->setSize(222)
                        ->setFileName($linkInfo->file_name)
                        ->setMedia(Url::toRoute(['download/download-temp', 'link' => $link], 'https'))
                );
            }
        }
    }
}