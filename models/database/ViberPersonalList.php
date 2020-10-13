<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]
 * @property string $viber_id [varchar(255)]
 * @property bool $get_errors [tinyint(1)]
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

    public static function subscribeGetErrors(int $getId): void
    {
        $data = self::findOne(['viber_id' => $getId]);
        if($data !== null && $data->get_errors === 0){
            $data->get_errors = 1;
            $data->save();
        }
    }
}