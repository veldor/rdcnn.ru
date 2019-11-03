<?php


namespace app\models;


use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $type [enum('download_conclusion', 'download_execution', 'print_conclusion')]
 * @property string $user_id [varchar(255)]
 * @property int $count [int(11)]
 */

class Table_statistics extends ActiveRecord
{
    public static function tableName()
    {
        return 'statistics';
    }

    public static function plusConclusionDownload($userId)
    {
        // проверю, не скачивал ли уже пациент заключение
        $previousCounter = Table_statistics::findOne(['user_id' => $userId, 'type' => 'download_conclusion']);
        if($previousCounter){
            ++ $previousCounter->count;
            $previousCounter->save();
        }
        else{
            $newCounter = new Table_statistics();
            $newCounter->user_id = $userId;
            $newCounter->type = 'download_conclusion';
            $newCounter->count = 1;
            $newCounter->save();
        }
    }

    public static function plusExecutionDownload($userId)
    {
        // проверю, не скачивал ли уже пациент заключение
        $previousCounter = Table_statistics::findOne(['user_id' => $userId, 'type' => 'download_execution']);
        if($previousCounter){
            ++ $previousCounter->count;
            $previousCounter->save();
        }
        else{
            $newCounter = new Table_statistics();
            $newCounter->user_id = $userId;
            $newCounter->type = 'download_execution';
            $newCounter->count = 1;
            $newCounter->save();
        }
    }
    public static function plusConclusionPrint($userId)
    {
        // проверю, не скачивал ли уже пациент заключение
        $previousCounter = Table_statistics::findOne(['user_id' => $userId, 'type' => 'print_conclusion']);
        if($previousCounter){
            ++ $previousCounter->count;
            $previousCounter->save();
        }
        else{
            $newCounter = new Table_statistics();
            $newCounter->user_id = $userId;
            $newCounter->type = 'print_conclusion';
            $newCounter->count = 1;
            $newCounter->save();
        }
    }
}