<?php

namespace app\models\utils;

use DateTime;

class TimeHandler
{
    public static $months = ['Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря',];

    /**
     * Получу метку времени начала сегдняшнего дня
     *
     */
    public static function getTodayStart(): int
    {
        $dtNow = new DateTime();
        $dtNow->modify('today');
        return $dtNow->getTimestamp();
    }

    public static function timestampToDate(int $timestamp)
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $answer = '';
        $day = $date->format('d');
        $answer .= $day;
        $month = mb_strtolower(self::$months[$date->format('m') - 1]);
        $answer .= ' ' . $month . ' ';
        $answer .= $date->format('Y') . ' года.';
        $answer .= $date->format(' H:i:s');
        return $answer;
    }
}