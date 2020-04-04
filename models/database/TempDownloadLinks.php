<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]
 * @property int $execution_id [int(11)]
 * @property string $link [varchar(255)]
 * @property string $file_type [enum('execution', 'conclusion')]
 * @property string $file_name [varchar(255)]
 */

class TempDownloadLinks extends ActiveRecord
{
    public static function tableName():string
    {
        return 'temp_download_links';
    }
}