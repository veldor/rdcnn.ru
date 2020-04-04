<?php


namespace app\models;


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
use yii\base\Model;

class Viber extends Model
{

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
                ->onText(/**
                 * @param $event Event
                 */ '|^[aа]?\d+ \d{4}$|isu', static function ($event) use ($bot, $botSender) {
                    $receiverId = $event->getSender()->getId();
                    // попробую найти обследование по переданным данным
                    $text = $event->getMessage()->getText();
                    [$id, $password] = explode(' ', $text);
                    $user = User::findByUsername($id);
                    if(empty($id)){
                        $bot->getClient()->sendMessage(
                            (new Text())
                                ->setSender($botSender)
                                ->setReceiver($receiverId)
                                ->setText('Вы ввели неверный номер обследования или неправильный пароль. Можете попробовать ещё раз или обратиться к нам за помощью по номеру 2020200')
                        );
                    }
                    else{
                        if ($user->failed_try > 20) {
                            $bot->getClient()->sendMessage(
                                (new Text())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setText('Было выполнено слишком много неверных попыток ввода пароля. В целях безопасности данные были удалены. Вы можете обратиться к нам для восстановления доступа')
                            );
                        }
                        // проверю совпадение пароля, если не совпадает- зарегистрирую ошибку
                        if(!$user->validatePassword($password)){
                            $user->last_login_try = time();
                            $user->failed_try = ++$user->failed_try;
                            $user->save();
                        }
                        else{
                            // подпишу пользователя на обновление информации
                            $bot->getClient()->sendMessage(
                                (new Text())
                                    ->setSender($botSender)
                                    ->setReceiver($receiverId)
                                    ->setText('Вы ввели правильные данные, спасибо! Мы будем посылать вам информацию по мере поступления!')
                            );
                            self::subscribe($receiverId, $user->username);
                            // проверю наличие заключения и файлов
                            $isFiles = ExecutionHandler::isExecution($user->username);
                            $isConclusion = ExecutionHandler::isConclusion($user->username);
                            if(!$isFiles && !$isConclusion){
                                $bot->getClient()->sendMessage(
                                    (new Text())
                                        ->setSender($botSender)
                                        ->setReceiver($receiverId)
                                        ->setText('Данные по вашему обследованию пока не получены. Мы напишем вам сразу же, как они будут получены')
                                );
                            }
                            else{
                                // отправлю ссылки на скачивание файлов
                                if($isConclusion){
                                    Table_viber_download_links::getConclusionLinks($user->username);
                                }
                            }
                        }
                    }
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
        if($existentSubscribe !== null){
            $existentSubscribe->patient_id = $patientId;
            $existentSubscribe->save();
        }
        else{
            (new ViberSubscriptions(['patient_id' => $patientId, 'viber_id' => $receiverId]))->save();
        }
    }
}