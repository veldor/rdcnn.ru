<?php


namespace app\models\database;


use app\models\User;
use Yii;
use yii\base\Exception;
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

    /**
     * @param User $execution
     * @param string $type
     * @param string|null $filename
     * @return TempDownloadLinks|null
     * @throws Exception
     */
    public static function createLink(User $execution, string $type, string $filename = null): ?TempDownloadLinks
    {
        $link = Yii::$app->security->generateRandomString(255);
        if($type === 'execution'){
            $link = new self(['file_name' => $execution->username . '.zip', 'file_type' => 'execution', 'link' => $link, 'execution_id' => $execution->id]);
            $link->save();
            return $link;
        }
        if($type === 'conclusion'){
            $link = new self(['file_name' => $filename, 'file_type' => 'conclusion', 'link' => $link, 'execution_id' => $execution->id]);
            $link->save();
            return $link;
        }
        return null;
    }

    public static function executionLinkExists(string $username)
    {
        return self::find()->where(['file_type' => 'execution', 'execution_id' => $username])->count();
    }

}