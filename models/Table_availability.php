<?php


namespace app\models;

use Exception;
use yii\db\ActiveRecord;

/**
 * @property int $id [bigint(20) unsigned]  Глобальный идентификатор
 * @property string $userId [varchar(255)]
 * @property bool $is_conclusion [tinyint(1)]
 * @property bool $is_execution [tinyint(1)]
 * @property string $file_name [varchar(255)]
 * @property int $file_create_time [int(10) unsigned]
 * @property string $md5 [char(32)]
 * @property string $patient_name [varchar(255)]
 * @property string $execution_area [varchar(255)]
 * @property bool $is_notification_sent [tinyint(1)]  Отправлялось ли уведомление
 * @property int $execution_date [int(10) unsigned]  Время проведения обследования
 * @property string $execution_type [varchar(255)]  Тип обследования
 */
class Table_availability extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'dataavailability';
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getWithoutConclusions(): string
    {
        $answerArray = [];
        $answer = '';
        $persons = User::findAllRegistered();
        if (!empty($persons)) {
            foreach ($persons as $person) {
                // если не найдено заключений по данному пациенту- верну его
                if (!self::find()->where(['userId' => $person->username, 'is_conclusion' => 1])->count()) {
                    $answerArray[] = $person->username;
                }
            }
        }
        if (!empty($answerArray)) {
            sort($answerArray, SORT_NATURAL);
            foreach ($answerArray as $item) {
                $answer .= $item . "\n";
            }
        }
        return $answer;
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getWithoutExecutions(): string
    {
        $answerArray = [];
        $answer = '';
        $persons = User::findAllRegistered();
        if (!empty($persons)) {
            foreach ($persons as $person) {
                // если не найдено заключений по данному пациенту- верну его
                if (!self::find()->where(['userId' => $person->username, 'is_execution' => 1])->count()) {
                    $answerArray[] = $person->username;
                }
            }
        }
        if (!empty($answerArray)) {
            sort($answerArray, SORT_NATURAL);
            foreach ($answerArray as $item) {
                $answer .= $item . "\n";
            }
        }
        return $answer;
    }

    /**
     * @param $id
     * @return Table_availability[]
     */
    public static function getConclusions($id): array
    {
        return self::findAll(['userId' => $id, 'is_conclusion' => true]);
    }

    public static function getPatientName(string $username): ?string
    {
        $existentItem = self::findOne(['userId' => $username, 'is_conclusion' => 1]);
        if ($existentItem !== null) {
            return $existentItem->patient_name;
        }
        return null;
    }

    public static function getConclusionAreas(string $username): string
    {
        $answer = '';
        $items = self::findAll(['userId' => $username, 'is_conclusion' => 1]);
        if ($items !== null) {
            foreach ($items as $item) {
                $answer .= "$item->execution_area \n";
            }
        }
        return $answer;
    }

    public static function isRegistered($conclusionFile): bool
    {
        return (bool)self::find()->where(['file_name' => $conclusionFile])->count();
    }

    public static function getRegistered(): array
    {
        /** @var Table_availability[] $data */
        $data = self::find()->all();
        if (!empty($data)) {
            $answer = [];
            foreach ($data as $item) {
                $answer[$item->file_name] = $item;
            }
            return $answer;
        }
        return [];
    }

    public static function isConclusion(User $item)
    {
        return self::find()->where(['userId' => $item->username, 'is_conclusion' => 1])->count();
    }

    public static function isExecution(User $item)
    {
        return self::find()->where(['userId' => $item->username, 'is_execution' => 1])->count();
    }

    public static function isNewFile(string $md5, string $entity): bool
    {
        $existentEntity = self::findOne(['file_name' => $entity]);
        return !(($existentEntity !== null) && $existentEntity->md5 === $md5);
    }

    public static function getFilesInfo(User $user): array
    {
        $name = self::getPatientName($user->username) ?? '';
        $answer = [];
        $existentFiles = self::findAll(['userId' => $user->username]);
        if (!empty($existentFiles)) {
            foreach ($existentFiles as $existentFile) {
                if($existentFile->is_execution){
                    $type = 'execution';
                    $fileName = "$name\n Архив снимков по обследованию {$user->username}.zip";
                }
                else{
                    $type = 'conclusion';
                    $fileName = $existentFile->execution_area ? "$name\n заключение {$existentFile->execution_area}.pdf" : "{$name}\n заключение {$existentFile->file_name}";
                }
                $answer[] = ['name' => $fileName, 'type' => $type, 'fileName' => $existentFile->file_name];
            }
        }
        usort($answer, array(self::class,'sortFiles'));
        return $answer;
    }

    private static function sortFiles($a, $b): int
    {
        $firstFileEnding = mb_substr($a['fileName'], -3);
        $secondFileEnding = mb_substr($b['fileName'], -3);
        if($firstFileEnding === $secondFileEnding){
            return 0;
        }
        if($firstFileEnding === 'zip'){
            return -1;
        }
        return 1;
    }
}