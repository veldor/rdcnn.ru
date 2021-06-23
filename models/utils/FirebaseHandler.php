<?php


namespace app\models\utils;


use app\models\database\FirebaseClient;
use app\models\Table_availability;
use app\models\Telegram;
use app\models\User;
use app\priv\Info;
use Exception;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Topic;
use Throwable;

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
            Telegram::sendDebug("conclusion info $userId sent for persons: " . count($clients));
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
            $result = $client->send($message);
            $json = $result->getBody()->getContents();
            if (!empty($json)) {
                try {
                    $encoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $results = $encoded['results'];
                    foreach ($results as $key => $resultItem) {
                        if (!empty($resultItem['error']) && $resultItem['error'] === 'NotRegistered') {
                            $target = $clients[$key];
                            if ($target !== null) {
                                $target->delete();
                            }
                        }
                    }
                } catch (Exception $e) {
                    Telegram::sendDebug("exception when parse message send: " . $e->getMessage());
                } catch (Throwable $e) {
                    Telegram::sendDebug("exception when delete firebase contact: " . $e->getMessage());
                }
            }
        }
    }

    public static function sendExecutionLoaded(string $userId, string $fileName, bool $double): void
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());

        $message = new Message();
        $message->setPriority('high');
        $clients = FirebaseClient::findAll(['patient_id' => $userId]);
        if (!empty($clients)) {
            Telegram::sendDebug("execution info $userId sent for persons: " . count($clients));
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
            $result = $client->send($message);
            $json = $result->getBody()->getContents();
            if (!empty($json)) {
                try {
                    $encoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $results = $encoded['results'];
                    foreach ($results as $key => $resultItem) {
                        if (!empty($resultItem['error']) && $resultItem['error'] === 'NotRegistered') {
                            $target = $clients[$key];
                            if ($target !== null) {
                                $target->delete();
                            }
                        }
                    }
                } catch (Exception $e) {
                    Telegram::sendDebug("exception when parse message send: " . $e->getMessage());
                } catch (Throwable $e) {
                    Telegram::sendDebug("exception when delete firebase contact: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Тестовая рассылка нотификаций, всем без разбора по всем файлам
     * @return array
     */
    public static function sendTest(): array
    {
        // get all available files
        $files = Table_availability::find()->all();
        if (!empty($files)) {
            foreach ($files as $file) {
                $user = User::findByUsername($file->userId);
                if ($user !== null) {
                    if ($file->is_conclusion) {
                        self::sendConclusionLoaded($user->getId(), $file->file_name, "");
                    } else {
                        self::sendExecutionLoaded($user->getId(), $file->file_name, "");
                    }
                }
            }
        }
        return ['status' => 'success'];
    }

    public static function sendTopicTest()
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        $message = new Message();
        $message->setPriority('high');
        $message->addRecipient(new Topic('news'));$message
        ->setNotification(new Notification('Тест рассылки', 'Рассылка для подписанных на топик!!'));
        $result = $client->send($message);
        var_dump($result);
        return ['status' => 'success'];
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
            $result = $client->send($message);
            $json = $result->getBody()->getContents();
            if (!empty($json)) {
                try {
                    $encoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $results = $encoded['results'];
                    foreach ($results as $key => $resultItem) {
                        if (!empty($resultItem['error']) && $resultItem['error'] === 'NotRegistered') {
                            $target = $contacts[$key];
                            if ($target !== null) {
                                $target->delete();
                            }
                        }
                    }
                } catch (Exception $e) {
                    Telegram::sendDebug("exception when parse message send: " . $e->getMessage());
                } catch (Throwable $e) {
                    Telegram::sendDebug("exception when delete firebase contact: " . $e->getMessage());
                }
            }
        }
    }
}