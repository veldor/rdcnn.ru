<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]
 * @property string $viber_id [varchar(255)]
 */

class ViberPersonalList extends ActiveRecord
{
    public static function tableName():string
    {
        return 'viber_personal_list';
    }

    /**
     * @param $receiverId
     */
    public static function register($receiverId): void
    {
        if(null === self::findOne(['viber_id' => $receiverId])){
            (new self(['viber_id' => $receiverId]))->save();
        }
    }

    /**
     * Проверю, работает ли у нас собеседник
     * @param $receiverId
     * @return bool
     */
    public static function iWorkHere($receiverId): bool
    {
        return (bool)self::find()->where(['viber_id' => $receiverId])->count();
    }
}