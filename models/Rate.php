<?php


namespace app\models;


use Yii;

class Rate
{

    public static function handleReview($id): void
    {
        $review =  Yii::$app->request->post('reviewArea');
        Telegram::sendDebug("Пользователь {$id} оставил отзыв: " . $review);
    }

    public static function handleRate(string $id, string $rate): void
    {
        Telegram::sendDebug("Пользователь {$id} оценил нашу работу на {$rate}");
    }
}