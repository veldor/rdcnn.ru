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

    public static function sendAllPatientsNotification(string $text): void
    {
        $server_key = 'Info::FIREBASE_SERVER_KEY';
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $message->setPriority('high');
        $message->addRecipient(new Device('dw_5XCRrScyZYWULpuVLWW:APA91bF_ibV2MhtOKscHKD2JqZbWrPYfFzDrii0P0gcIYNraZv7Zu4FtYJYTc0OzoFJqi_VC5Cj9WF41uGzuCE-74Qwo6aI7apJAIIu0-oABhvuxyrmx1sN7Bj0TM6vTW798uzgWglPw'));
        $message
            ->setNotification(new Notification('some title', 'some body'))
            ->setData(['key' => 'value'])
        ;

        $response = $client->send($message);
        var_dump($response->getStatusCode());
        var_dump($response->getBody()->getContents());
        die;
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