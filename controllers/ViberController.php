<?php


namespace app\controllers;


use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use Viber\Api\Event;
use Viber\Api\Keyboard;
use Viber\Api\Keyboard\Button;
use Viber\Api\Message\Text;
use Viber\Api\Sender;
use Viber\Bot;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

class ViberController extends Controller
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
        $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/viber_request_' . time() . '.log';
        $report = serialize($_POST);
        file_put_contents($file, $report);
        $apiKey = Info::VIBER_API_KEY;

// так будет выглядеть наш бот (имя и аватар - можно менять)
        $botSender = new Sender([
            'name' => 'Бот РДЦ',
            'avatar' => 'https://developers.viber.com/img/favicon.ico',
        ]);

        try {
            $bot = new Bot(['token' => $apiKey]);
            $bot
                // first interaction with bot - return "welcome message"
                ->onConversation(function ($event) use ($bot, $botSender) {
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
                                        ->setActionBody('get-conclusion')
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
                // when user subscribe to PA
                ->onSubscribe(function ($event) use ($bot, $botSender) {
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setText('Сейчас продолжим')
                    );
                })
                ->onText('|get-conclusion|s', function ($event) use ($bot, $botSender) {
                    $receiverId = $event->getSender()->getId();
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            ->setText('Введите номер обследования')
                    );
                })
                ->onText('|more-actions|s', function ($event) use ($bot, $botSender) {
                    $receiverId = $event->getSender()->getId();
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setReceiver($receiverId)
                            ->setText('Напишите, что бы вы хотели сделать')
                    );
                })
                ->run();
        } catch (Exception $e) {
            // todo - log exceptions
            $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/viber_err_' . time() . '.log';
            $report = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine();
            file_put_contents($file, $report);
        }
    }

    public function actionSetup()
    {
        $apiKey = Info::VIBER_API_KEY; // <- PLACE-YOU-API-KEY-HERE
        $webhookUrl = 'https://rdcnn.ru/viber/connect'; // <- PLACE-YOU-HTTPS-URL
        try {
            $client = new \Viber\Client(['token' => $apiKey]);
            $result = $client->setWebhook($webhookUrl);
            var_dump($result);
            echo "Success!\n";
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }
    }
}