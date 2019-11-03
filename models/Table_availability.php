<?php


namespace app\models;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $userId [varchar(255)]
 * @property int $startTime [int(10) unsigned]
 */

class Table_availability extends ActiveRecord
{
    public static function tableName()
    {
        return 'dataavailability';
    }
}