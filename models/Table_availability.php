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
     * @return string
     */
    public static function getWithoutConclusions(): string
    {
        $answer = '';
        $persons = User::findAllRegistered();
        if(!empty($persons)){
            foreach ($persons as $person) {
                // если не найдено заключений по данному пациенту- верну его
                if(!self::find()->where(['userId' => $person->username, 'is_conclusion' => 1])->count()){
                    $answer .= "{$person->username}\n";
                }
            }
        }
        return $answer;
    }

    /**
     * @return string
     */
    public static function getWithoutExecutions(): string
    {
        $answer = '';
        $persons = User::findAllRegistered();
        if(!empty($persons)){
            foreach ($persons as $person) {
                // если не найдено заключений по данному пациенту- верну его
                if(!self::find()->where(['userId' => $person->username, 'is_execution' => 1])->count()){
                    $answer .= "{$person->username}\n";
                }
            }
        }
        return $answer;
    }

    /**
     * @param $id
     */
    public static function getConclusions($id)
    {
        $answer = [];
        $existentConclusions = self::findAll(['userId' => $id, 'is_conclusion' => true]);
        if(!empty($existentConclusions)){
            foreach ($existentConclusions as $existentConclusion) {
                $answer[] = $existentConclusion->file_name;
            }
        }
        return $answer;
    }
}