<?php


namespace app\models;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $userId [varchar(255)]
 * @property bool $is_conclusion [tinyint(1)]
 * @property bool $is_execution [tinyint(1)]
 * @property string $file_name [varchar(255)]
 * @property int $file_create_time [int(10) unsigned]
 * @property string $md5 [char(32)]
 */
class Table_availability extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'dataavailability';
    }

    /**
     * @return Table_availability[]
     */
    public static function getWithoutConclusions(): array
    {
        return self::findAll(['is_conclusion' => 0]);
    }

    /**
     * @return Table_availability[]
     */
    public static function getWithoutExecutions(): array
    {
        return self::findAll(['is_execution' => 0]);
    }
}