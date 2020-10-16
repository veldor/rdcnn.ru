<?php


namespace app\models\database;


use app\models\Table_availability;
use app\models\User;
use app\models\utils\MailSettings;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * @property int $id [int(10) unsigned]
 * @property string $address [varchar(255)]
 * @property string $patient_id [int(11)]
 * @property bool $mailed_yet [tinyint(1)]
 */
class Emails extends ActiveRecord
{
    public static function checkExistent(string $patient_id)
    {
        return self::find()->where(['patient_id' => $patient_id])->count();
    }

    /**
     * @param Table_availability $item
     * @throws Exception
     */
    public static function sendEmail(Table_availability $item): void
    {
        // получу адрес
        $user = User::findByUsername($item->userId);
        if($user !== null){
            $mail = self::findOne(['patient_id' => $user->id]);
            if($mail !== null){
                // проверю наличие ссылки на скачивание элемента
                $existentLink = TempDownloadLinks::findOne(['file_name' => $item->file_name]);
                if($existentLink === null){
                    // пока временно буду каждый раз создавать новую ссылку
                    $existentLink = TempDownloadLinks::createLink(
                        $user,
                        ($item->is_conclusion ? 'conclusion' : 'execution'),
                        $item->file_name
                    );
                }
                if($existentLink !== null){
                    // отправлю письмо со ссылкой
                    $text = "Ссылка на скачивание контента: <a href='" . Url::base('https') . "/download/temp/{$existentLink->link}'>Скачать</a>";
                    // отправлю письмо
                    $mail = Yii::$app->mailer->compose()
                        ->setFrom([MailSettings::getInstance()->address => 'Тест'])
                        ->setSubject('Тест')
                        ->setHtmlBody($text)
                        ->setTo(['eldorianwin@gmail.com' => 'eldorianwin@gmail.com']);
                    // попробую отправить письмо, в случае ошибки- вызову исключение
                    $mail->send();
                }
            }
        }
    }


    public function rules():array
    {
        return [
            [['address', 'patient_id'], 'required'],
            ['address' , 'email']
        ];
    }

    public static function tableName(): string
    {
        return 'mailing';
    }
}