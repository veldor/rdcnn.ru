<?php


namespace app\controllers;


use app\models\Viber;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

class ViberController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action):bool
    {
        if ($action->id === 'connect') {
            // отключу csrf для возможности запроса
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionConnect(): void
    {
        Viber::handleRequest();
    }

    /**
     * настройка хука, выполняется только один раз при регистрации бота
     */
    public function actionSetup(): void
    {
        Viber::setup();
    }
}