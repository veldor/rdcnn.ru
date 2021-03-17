<?php


namespace app\models\database;


use app\models\Table_availability;
use app\models\User;
use app\models\utils\GrammarHandler;
use app\models\utils\MailSettings;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * @property int $patient_id [int(11)]
 * @property string $token [varchar(255)]
 * @property int $id [int(11) unsigned]
 */
class FirebaseClient extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'person_firebase_tokens';
    }

    public static function register(User $user, $firebaseToken): void
    {
        if(!self::find()->where(['token' => $firebaseToken, 'patient_id' => $user->id])->count()){
            (new self(['token' => $firebaseToken, 'patient_id' => $user->id]))->save();
        }
    }
}