<?php


namespace app\models;


use app\models\utils\MailSettings;
use Yii;

class Rate
{

    public static function handleReview($id): void
    {
        $review =  Yii::$app->request->post('reviewArea');
        $text = "Пользователь {$id} оставил отзыв: " . $review;
        Telegram::sendDebug($text);
        self::sendRate($text);
    }

    public static function handleRate(string $id, string $rate): void
    {
        $text = "Пользователь {$id} оценил нашу работу на {$rate}";
        Telegram::sendDebug($text);
        self::sendRate($text);

    }

    private static function sendRate($text): void
    {
        // получу настройки почты
        $settingsFile = Yii::$app->basePath . '\\priv\\mail_settings.conf';
        $content = file_get_contents($settingsFile);
        $settingsArray = mb_split("\n", $content);
        if (count($settingsArray) === 3) {
            // отправлю письмо
            $mail = Yii::$app->mailer->compose()
                ->setFrom([MailSettings::getInstance()->address => 'Наши оценки'])
                ->setSubject('Получен новый отзыв')
                ->setHtmlBody($text)
                ->setTo(['eldorianwin@gmail.com' => 'eldorianwin@gmail.com']);
            // попробую отправить письмо, в случае ошибки- вызову исключение
            $mail->send();
        }
    }
}