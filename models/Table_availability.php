<?php


namespace app\models;


use yii\base\Exception;
use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $userId [varchar(255)]
 * @property int $startTime [int(10) unsigned]
 * @property bool $is_conclusion [tinyint(1)]
 * @property bool $is_execution [tinyint(1)]
 */
class Table_availability extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'dataavailability';
    }

    /**
     * регистрация загруженных данных обследования
     * @param $username
     * @throws Exception
     */
    public static function setDataLoaded($username): void
    {
        // получу данные о пользователе
        $user = User::findByUsername($username);
        if ($user !== null) {
            // поверю, если данные ещё не заносились- добавлю и уведомлю о загруженном заключении
            $existentData = self::findOne(['userId' => $username]);
            if ($existentData === null) {
                // добавлю новую запись
                $newData = new self(['userId' => $username, 'is_execution' => true, 'startTime' => $user->created_at]);
                $newData->save();
                // оповещу пользователя через вайбер, если он есть
                Viber::notifyExecutionLoaded($username);
            } else if (!$existentData->is_execution) {
                $existentData->is_execution = true;
                $existentData->save();
                Viber::notifyExecutionLoaded($username);
            }
        }
    }

    /**
     * регистрация загруженного заключения
     * @param string $username
     */
    public static function setConclusionLoaded(string $username): void
    {
        // получу данные о пользователе
        $user = User::findByUsername($username);
        if ($user !== null) {
            // поверю, если данные ещё не заносились- добавлю и уведомлю о загруженном заключении
            $existentData = self::findOne(['userId' => $username]);
            if ($existentData === null) {
                // добавлю новую запись
                $newData = new self(['userId' => $username, 'is_conclusion' => true, 'startTime' => $user->created_at]);
                $newData->save();
                // оповещу пользователя через вайбер, если он есть
                Viber::notifyConclusionLoaded();
            } else if (!$existentData->is_conclusion) {
                $existentData->is_conclusion = true;
                $existentData->save();
                Viber::notifyConclusionLoaded();
            }
        }
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