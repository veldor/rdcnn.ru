<?php


namespace app\models;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $type [enum('download_conclusion', 'download_execution', 'print_conclusion')]
 * @property string $user_id [varchar(255)]
 * @property int $timestamp [int(10) unsigned]
 */
class Table_statistics extends ActiveRecord
{
    public static function tableName():string
    {
        return 'statistics';
    }

    public static function plusConclusionDownload($userId): void
    {
        $newCounter = new self(['user_id' => $userId, 'type' => 'download_conclusion', 'timestamp' => time()]);
        $newCounter->save();
    }

    public static function plusExecutionDownload($userId): void
    {
        $newCounter = new self(['user_id' => $userId, 'type' => 'download_execution', 'timestamp' => time()]);
        $newCounter->save();
    }

    public static function plusConclusionPrint($userId): void
    {
        $newCounter = new self(['user_id' => $userId, 'type' => 'print_conclusion', 'timestamp' => time()]);
        $newCounter->save();
    }
}