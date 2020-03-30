<?php


namespace app\controllers;


use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
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
                ->onConversation(function ($event) use ($bot, $botSender) {
                    // это событие будет вызвано, как только пользователь перейдет в чат
                    // вы можете отправить "привествие", но не можете посылать более сообщений
                    return (new Text())
                        ->setSender($botSender)
                        ->setText('Чё как, сучара?');
                })
                ->onText('|whois .*|si', function ($event) use ($bot, $botSender) {
                    // это событие будет вызвано если пользователь пошлет сообщение
                    // которое совпадет с регулярным выражением
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setReceiver($event->getSender()->getId())
                            ->setText('Понятия не имею )')
                    );
                })

                ->onText('*', function ($event) use ($bot, $botSender) {
                    // это событие будет вызвано если пользователь пошлет сообщение
                    // которое совпадет с регулярным выражением
                    $bot->getClient()->sendMessage(
                        (new Text())
                            ->setSender($botSender)
                            ->setReceiver($event->getSender()->getId())
                            ->setText('Понятия не имею )')
                    );
                })
                ->run();
        } catch (Exception $e) {
            // todo - log exceptions
            $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/viber_err_' . time() . '.log';
            $report = $e->getMessage();
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