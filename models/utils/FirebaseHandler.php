<?php


namespace app\models\utils;


use app\models\database\FirebaseClient;
use app\models\Telegram;
use app\priv\Info;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;

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

    public static function sendConclusionLoaded(string $userId, string $fileName, string $double): void
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $message->setPriority('high');
        $clients = FirebaseClient::findAll(['patient_id' => $userId]);
        if (!empty($clients)) {
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            $message
                ->setNotification(new Notification('Добавлено заключение врача', 'Просмотреть заключение вы можете в приложении'))
                ->setData([
                    'type' => 'conclusion',
                    'fileName' => $fileName,
                    'double' => $double
                    ]);
            $response = $client->send($message);
        }
        else{

            Telegram::sendDebug("not found clients for {$userId}");
        }
    }
    public static function sendExecutionLoaded(string $userId, string $fileName, bool $double): void
    {
        Telegram::sendDebug("send execution to {$userId}");
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $message->setPriority('high');
        $clients = FirebaseClient::findAll(['patient_id' => $userId]);
        if (!empty($clients)) {
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            $message
                ->setNotification(new Notification('Добавлен архив со снимками обследования', 'Архив будет загружен и отображён в приложении'))
                ->setData([
                    'type' => 'execution',
                    'fileName' => $fileName,
                    'double' => $double
                    ]);
            $response = $client->send($message);
        }
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