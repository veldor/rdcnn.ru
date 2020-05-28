<?php


namespace app\models;


use DateTime;
use Exception;
use Yii;
use yii\base\Model;

class Utils extends Model
{
    /**
     * Перевод секунд таймера в дату завершения таймера
     * @param $seconds
     * @return string
     * @throws Exception
     */
    public static function secondsToTime($seconds): string
    {
        $dtF = new DateTime('@0');
        $dtT = new DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a дней, %h часов, %i минут %s секунд');
    }

    /**
     * @return bool
     */
    public static function isCenterFiltered(): bool
    {
        return !empty(Yii::$app->session['center']) && Yii::$app->session['center'] !== 'all';
    }

    /**
     * @param User $execution
     * @return bool
     */
    public static function isFiltered(User $execution): bool
    {
        // если фильтр по авроре- номер должен начинаться с буквы A, если НВ- с цифры
        $firstSymbol = $execution->username[0];
        return !((Yii::$app->session['center'] === 'aurora' && $firstSymbol === 'A') || (Yii::$app->session['center'] === 'nv' && !empty((int)$firstSymbol)));
    }

    public static function getSort()
    {
        if (!empty(Yii::$app->session['sortBy'])) {
            return Yii::$app->session['sortBy'];
        }
        return 'byTime';
    }

    /**
     * Проверка наличия фильтра по дате прохождения обследования
     * @return bool
     */
    public static function isTimeFiltered(): bool
    {
        return !empty(Yii::$app->session['timeInterval']) && Yii::$app->session['timeInterval'] !== 'all';
    }

    /**
     * Получение временной метки начала суток
     * @return int
     * @throws Exception
     */
    public static function getStartInterval(): int
    {
        $dtNow = self::setupDay();
        $dtNow->modify('today');
        return $dtNow->getTimestamp();
    }

    /**
     * Получение временной метки завершения суток
     * @return int
     * @throws Exception
     */
    public static function getEndInterval(): int
    {
        $dtNow = self::setupDay();
        $dtNow->modify('today');
        $endOfDay = clone $dtNow;
        $endOfDay->modify('tomorrow');
        $endOfDateTimestamp = $endOfDay->getTimestamp();
        $endOfDay->setTimestamp($endOfDateTimestamp - 1);
        return $endOfDay->getTimestamp();
    }

    /**
     * Сортировка заключений по выбранным параметрам
     * @param array $executionsList
     * @return array
     */
    public static function sortExecutions(array $executionsList): array
    {
        usort(
            /**
         * @param $execution1 User
         * @param $executon2 User
         * @return mixed
         */ $executionsList, static function ($execution1, $execution2) {
            switch (self::getSort()) {
                case 'byNumber':
                    return $execution1->username < $execution2->username;
                case 'byExecutions':
                    return ExecutionHandler::isExecution($execution1->username) > ExecutionHandler::isExecution($execution2->username);
                case 'byConclusion':
                    return ExecutionHandler::isConclusion($execution1->username) > ExecutionHandler::isConclusion($execution2->username);
                case 'byTime':
                default:
                    return $execution1->created_at < $execution2->created_at;
            }
        });
        return $executionsList;
    }

    public static function showDate(int $timestamp)
    {
        setlocale(LC_ALL, 'ru_RU.utf8');
        return strftime('%d %h %H:%M', $timestamp);
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    public static function setupDay(): DateTime
    {
        if (Yii::$app->session['timeInterval'] === 'today') {
            $time = time();
        }
        if (Yii::$app->session['timeInterval'] === 'yesterday') {
            $time = time() - 86400;
        }
        $dtNow = new DateTime();
        if (!empty($time)) {
            $dtNow->setTimestamp($time);
        }
        return $dtNow;
    }

}