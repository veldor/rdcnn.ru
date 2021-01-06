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
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        foreach ($contacts as $contact) {
            $message->addRecipient(new Device($contact->token));
        }
        $client->send($message);
    }

    /**
     * @param $task PersonalTask
     */
    public static function sendTaskAccepted(PersonalTask $task): void
    {
        // отправлю сообщение всем контактам, которые зарегистрированы
        $initiator = PersonalItems::findOne($task->initiator);
        if($initiator !== null){
            $contacts = FirebaseToken::find()->where(['user' => $initiator->id])->all();
            if(!empty($contacts)){
                $message = new Message();
                $message->setPriority('high');
                $message
                    ->setData([
                        'action' => 'task_accepted',
                        'task_id' => $task->id
                    ]);
                self::sendMultipleMessage($contacts, $message);
            }
        }
    }

    public static function sendTaskCancelled(PersonalTask $item): void
    {
        // если задаче назначен исполнитель- отправлю ему сообщение о отмене действия
        if(!empty($item->executor)){
            $executor = PersonalItems::findOne($item->executor);
            if($executor !== null){
                $contacts = FirebaseToken::find()->where(['user' => $executor->id])->all();
                if(!empty($contacts)){
                    $message = new Message();
                    $message->setPriority('high');
                    $message
                        ->setData([
                            'action' => 'task_cancelled',
                            'task_id' => $item->id
                        ]);
                    self::sendMultipleMessage($contacts, $message);
                }
            }
        }
    }

    public static function sendTaskFinished(PersonalTask $item): void
    {
        // отправлю сообщение всем контактам, которые зарегистрированы
        $initiator = PersonalItems::findOne($item->initiator);
        if($initiator !== null){
            $contacts = FirebaseToken::find()->where(['user' => $initiator->id])->all();
            if(!empty($contacts)){
                $message = new Message();
                $message->setPriority('high');
                $message
                    ->setData([
                        'action' => 'task_finished',
                        'task_id' => $item->id
                    ]);
                self::sendMultipleMessage($contacts, $message);
            }
        }
    }
}