<?php


namespace app\models;


use JsonException;
use RuntimeException;
use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;

class PersonalApi
{

    /**
     * Обработка запроса
     * @return array|string[]
     */
    public static function handleRequest(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        Telegram::sendDebug("Доступ к API: " . $data);
        return $data;
    }
}