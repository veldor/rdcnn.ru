<?php


namespace app\models\utils;


use app\models\database\FirebaseClient;
use app\priv\Info;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Recipient;

class FirebaseHandler
{
//    public static function sendMessage($token, $message): void
//    {
//        $server_key = Info::FIREBASE_SERVER_KEY;
//        $client = new Client();
//        $client->setApiKey($server_key);
//        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
//        $response = $client->send($message);
//    }

    public static function sendAllPatientsNotification(string $message): void
    {
        $list = [];
        // отправлю сообщение всем контактам, которые зарегистрированы
        $executors = FirebaseClient::find()->all();
        if (!empty($executors)) {
            foreach ($executors as $executor) {
                $list[] = $executor->token;
            }
        }
        $notification = new Notification("Тест", $message);
        $firebaseMessage = new Message();
        $firebaseMessage->setPriority('high');
        $firebaseMessage->setNotification($notification);
        self::sendMultipleMessage($list, $firebaseMessage);
    }

    /**
     * @param array $contacts
     * @param Message $message
     */
    private static function sendMultipleMessage(array $contacts, Message $message): void
    {
        if (!empty($contacts) && count($contacts) > 0) {
            $server_key = Info::FIREBASE_SERVER_KEY;
            $client = new Client();
            $client->setApiKey($server_key);
            $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
            foreach ($contacts as $contact) {
                $message->addRecipient(new Device($contact));
            }
            $client->send($message);
        }
    }
}