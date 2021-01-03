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
 * @property int $id [int(10) unsigned]
 * @property int $initiator [int(10) unsigned]
 * @property int $target [int(10) unsigned]
 * @property int $executor [int(10) unsigned]
 * @property int $task_creation_time [int(10) unsigned]
 * @property int $task_accept_time [int(10) unsigned]
 * @property int $task_planned_finish_time [int(10) unsigned]
 * @property int $task_finish_time [int(10) unsigned]
 * @property string $task_header [varchar(255)]
 * @property string $task_body [varchar(255)]
 * @property string $task_status [varchar(255)]
 * @property string $executor_comment [varchar(255)]
 */
class PersonalTask extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'personal_tasks';
    }
}