<?php

namespace app\models\utils;

use DateTime;

class TimeHandler
{
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
}