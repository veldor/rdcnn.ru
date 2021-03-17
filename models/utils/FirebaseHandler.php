<?php


namespace app\models\utils;


use app\models\database\FirebaseToken;
use app\models\database\PersonalItems;
use app\models\database\PersonalTask;
use app\priv\Info;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
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

    public static function sendTaskCreated(PersonalTask $task): void
    {
        $list = [];
        // отправлю сообщение всем контактам, которые зарегистрированы
        $executors = PersonalItems::find()->where(['role' => $task->target])->all();
        if(!empty($executors)){
            /** @var PersonalItems $executor */
            foreach ($executors as $executor) {
                $contacts = FirebaseToken::find()->where(['user' => $executor->id])->all();
                if(!empty($contacts)){
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $list = array_merge($list, $contacts);
                }
            }
        }

        $message = new Message();
        $message->setPriority('high');
        $message
            ->setData([
                'action' => 'task_created',
                'task_id' => $task->id
            ]);
        self::sendMultipleMessage($list, $message);
    }

    /**
     * @param array $contacts
     * @param Message $message
     */
    private static function sendMultipleMessage(array $contacts, Message $message): void
    {
        if(!empty($contacts) && count($contacts) > 0){
            $server_key = Info::FIREBASE_SERVER_KEY;
            $client = new Client();
            $client->setApiKey($server_key);
            $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
            foreach ($contacts as $contact) {
                $message->addRecipient(new Device($contact->token));
            }
            $client->send($message);
        }
    }
}