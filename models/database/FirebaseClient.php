<?php


namespace app\models\database;


use app\models\Telegram;
use app\models\User;
use Throwable;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

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
            // при наличии- удалю записи, дублирующие токен
            $existent = self::findAll(['token' => $firebaseToken]);
            if(!empty($existent)){
                foreach ($existent as $item) {
                    try {
                        $item->delete();
                    } catch (StaleObjectException | Throwable $e) {
                        Telegram::sendDebug("Не удалось удалить запись {$item->id}");
                    }
                }
            }
            (new self(['token' => $firebaseToken, 'patient_id' => $user->id]))->save();
        }
    }
}