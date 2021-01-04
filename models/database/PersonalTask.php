<?php


namespace app\models\database;


use app\models\selections\Task;
use yii\db\ActiveRecord;

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

    public static function getTaskList(int $id): array
    {
        $existent = self::find()->where(['initiator' => $id])->all();
        $result = [];
        if(!empty($existent)){
            /** @var PersonalTask $item */
            foreach ($existent as $item) {
                $task = new Task();
                $task->id = $item->id;
                $initiator = PersonalItems::findOne(['id' => $item->initiator]);
                if($initiator !== null){
                    $task->initiator = $initiator->name;
                }
                if(!empty($item->executor)){
                    $executor = PersonalItems::findOne(['id' => $item->executor]);
                    if($executor !== null){
                        $task->executor = $executor->name;
                    }
                }
                else{
                    $task->executor = '';
                }
                /** @var PersonalRoles $target */
                $target = PersonalRoles::findOne($item->target);
                if($target !== null){
                    $task->target = $target->role;
                }
                $task->task_creation_time = $item->task_creation_time;
                $task->task_accept_time = $item->task_accept_time ?: 0;
                $task->task_planned_finish_time = $item->task_planned_finish_time ?:0;
                $task->task_finish_time = $item->task_finish_time ?:0;
                $task->task_header = $item->task_header ?:'';
                $task->task_body = $item->task_body;
                $task->task_status = $item->task_status;
                $task->executor_comment = $item->executor_comment ?:'';
                $result[] = $task;
            }
        }
        return $result;
    }
}