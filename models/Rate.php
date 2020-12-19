<?php


namespace app\models;


use app\models\utils\MailHandler;
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

    private static function sendRate($text){
        // получу настройки почты
        $settingsFile = Yii::$app->basePath . '\\priv\\mail_settings.conf';
        if (is_file($settingsFile)) {
            // получу данные
            $content = file_get_contents($settingsFile);
            $settingsArray = mb_split("\n", $content);
            if (count($settingsArray) === 3) {
                    // отправлю письмо
                    $mail = Yii::$app->mailer->compose()
                        ->setFrom([$settingsArray[0] => 'РДЦ Отзывы'])
                        ->setSubject('Получен новый отзыв')
                        ->setHtmlBody($text)
                        ->setTo(['eldorianwin@gmail.com' => 'Мне']);
                    // попробую отправить письмо, в случае ошибки- вызову исключение
                    $mail->send();
            }
        }
    }
}