<?php


namespace app\controllers;


use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
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
        echo 'here';
    }
    public function actionSetup(){
        $apiKey = Info::VIBER_API_KEY; // <- PLACE-YOU-API-KEY-HERE
        $webhookUrl = 'https://rdcnn.ru/viber/connect'; // <- PLACE-YOU-HTTPS-URL
        try {
            $client = new \Viber\Client([ 'token' => $apiKey ]);
            $result = $client->setWebhook($webhookUrl);
            var_dump($result);
            echo "Success!\n";
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() ."\n";
        }
    }
}