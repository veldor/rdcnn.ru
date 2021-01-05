<?php


namespace app\models\utils;


use app\models\database\FirebaseToken;
use app\models\database\PersonalTask;
use app\priv\Info;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;

class FirebaseHandler
{
    public static function sendMessage($token, $message): void
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        $response = $client->send($message);
    }

    public static function sendTaskCreated(PersonalTask $task)
    {
        // отправлю сообщение всем контактам, которые зарегистрированы
        // todo организовать отправку только выбранным контактам
        $contacts = FirebaseToken::find()->all();

        $message = new Message();
        $message->setPriority('high');
        $message
            ->setData(['key' => 'value']);
        self::sendMultipleMessage($contacts, $message);
    }

    private static function sendMultipleMessage(array $contacts, Message $message)
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        foreach ($contacts as $contact) {
            $message->addRecipient(new Device($contact->token));
        }
        $response = $client->send($message);
    }

    public static function sendTestMessage()
    {
        $contacts = FirebaseToken::find()->all();
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $message = new Message();
        $message->setPriority('high');
        $message
            ->setData(['key' => 'value']);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        foreach ($contacts as $contact) {
            $message->addRecipient(new Device($contact->token));
        }
        $response = $client->send($message);
    }
}