<?php


namespace app\models\database;


use app\models\Table_availability;
use app\models\User;
use app\models\utils\MailSettings;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * @property string $execution_id [varchar(255)]
 * @property bool $is_conc [tinyint(1)]
 * @property bool $is_exec [tinyint(1)]
 * @property bool $sent [tinyint(1)]
 * @property int $person_id [int(11)]
 * @property string $address [varchar(255)]
 */
class NotificationSendingInfo extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'notification_sending_info';
    }
}