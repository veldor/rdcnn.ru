<?php


namespace app\models;


use yii\base\Model;

class Utils extends Model
{
    public static function secondsToTime($seconds) {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a дней, %h часов, %i минут %s секунд');
    }
}