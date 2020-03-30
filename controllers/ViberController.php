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
            ->setText("Hi, you can see some demo: send 'k1' or 'k2' etc.");
    })
    // when user subscribe to PA
    ->onSubscribe(function ($event) use ($bot, $botSender) {
        $this->getClient()->sendMessage(
            (new Text())
                ->setSender($botSender)
                ->setText('Добрый день. Я бот РДЦ. Выберите, что вы хотите сделать')
                ->setKeyboard(
                    (new Keyboard())
                        ->setButtons([
                            (new Button())
                                ->setBgColor('#2fa4e7')
                                ->setTextHAlign('center')
                                ->setActionType('reply')
                                ->setActionBody('btn-click')
                                ->setText('Получить заключение'),
                            (new Button())
                                ->setBgColor('#2fa4e7')
                                ->setTextHAlign('center')
                                ->setActionType('reply')
                                ->setActionBody('btn-click')
                                ->setText('Что-то ещё'),

                        ])
                )
        );
    })
    ->onText('|btn-click|s', function ($event) use ($bot, $botSender) {
        $receiverId = $event->getSender()->getId();
        $bot->getClient()->sendMessage(
            (new Text())
                ->setSender($botSender)
                ->setReceiver($receiverId)
                ->setText('you press the button')
        );
    })
    ->onText('|k\d+|is', function ($event) use ($bot, $botSender) {
        $caseNumber = (int)preg_replace('|[^0-9]|s', '', $event->getMessage()->getText());
        $client = $bot->getClient();
        $receiverId = $event->getSender()->getId();
        switch ($caseNumber) {
            case 0:
                $client->sendMessage(
                    (new Text())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setText('Basic keyboard layout')
                        ->setKeyboard(
                            (new Keyboard())
                                ->setButtons([
                                    (new Button())
                                        ->setActionType('reply')
                                        ->setActionBody('btn-click')
                                        ->setText('Tap this button')
                                ])
                        )
                );
                break;
            //
            case 1:
                $client->sendMessage(
                    (new Text())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setText('More buttons and styles')
                        ->setKeyboard(
                            (new Keyboard())
                                ->setButtons([
                                    (new Button())
                                        ->setBgColor('#8074d6')
                                        ->setTextSize('small')
                                        ->setTextHAlign('right')
                                        ->setActionType('reply')
                                        ->setActionBody('btn-click')
                                        ->setText('Button 1'),

                                    (new Button())
                                        ->setBgColor('#2fa4e7')
                                        ->setTextHAlign('center')
                                        ->setActionType('reply')
                                        ->setActionBody('btn-click')
                                        ->setText('Button 2'),

                                    (new Button())
                                        ->setBgColor('#555555')
                                        ->setTextSize('large')
                                        ->setTextHAlign('left')
                                        ->setActionType('reply')
                                        ->setActionBody('btn-click')
                                        ->setText('Button 3'),
                                ])
                        )
                );
                break;
            //
            case 2:
                $client->sendMessage(
                    (new \Viber\Api\Message\Contact())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setName('Novikov Bogdan')
                        ->setPhoneNumber('+380000000000')
                );
                break;
            //
            case 3:
                $client->sendMessage(
                    (new \Viber\Api\Message\Location())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setLat(48.486504)
                        ->setLng(35.038910)
                );
                break;
            //
            case 4:
                $client->sendMessage(
                    (new \Viber\Api\Message\Sticker())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setStickerId(114408)
                );
                break;
            //
            case 5:
                $client->sendMessage(
                    (new \Viber\Api\Message\Url())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setMedia('https://hcbogdan.com')
                );
                break;
            //
            case 6:
                $client->sendMessage(
                    (new \Viber\Api\Message\Picture())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setText('some media data')
                        ->setMedia('https://developers.viber.com/img/devlogo.png')
                );
                break;
            //
            case 7:
                $client->sendMessage(
                    (new \Viber\Api\Message\Video())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setSize(2 * 1024 * 1024)
                        ->setMedia('http://techslides.com/demos/sample-videos/small.mp4')
                );
                break;
            //
            case 8:
                $client->sendMessage(
                    (new \Viber\Api\Message\CarouselContent())
                        ->setSender($botSender)
                        ->setReceiver($receiverId)
                        ->setButtonsGroupColumns(6)
                        ->setButtonsGroupRows(6)
                        ->setBgColor('#FFFFFF')
                        ->setButtons([
                            (new Button())
                                ->setColumns(6)
                                ->setRows(3)
                                ->setActionType('open-url')
                                ->setActionBody('https://www.google.com')
                                ->setImage('https://i.vimeocdn.com/portrait/58832_300x300'),

                            (new Button())
                                ->setColumns(6)
                                ->setRows(3)
                                ->setActionType('reply')
                                ->setActionBody('https://www.google.com')
                                ->setText('<span style="color: #ffffff; ">Buy</span>')
                                ->setTextSize("large")
                                ->setTextVAlign("middle")
                                ->setTextHAlign("middle")
                                ->setImage('https://s14.postimg.org/4mmt4rw1t/Button.png')
                        ])
                );
                break;
        }
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