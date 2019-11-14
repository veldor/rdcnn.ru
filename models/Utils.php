<?php


namespace app\models;


use DateTime;
use Yii;
use yii\base\Model;

class Utils extends Model
{
    public static function secondsToTime($seconds) {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a дней, %h часов, %i минут %s секунд');
    }

    public static function isCenterFiltered(){
        if(!empty(Yii::$app->session['center']) && Yii::$app->session['center'] != "all"){
            return true;
        }
        return false;
    }

    public static function isFiltered(User $execution)
    {
        // если фильтр по авроре- номер должен начинаться с буквы A, если НВ- с цифры
        $firstSymbol = substr($execution->username, 0, 1);
        if((Yii::$app->session['center'] == 'aurora' && $firstSymbol == 'A') || (Yii::$app->session['center'] == 'nv' && !empty((int) $firstSymbol))){
            return false;
        }
        return true;
    }

    public static function isTimeFiltered()
    {
        if(!empty(Yii::$app->session['timeInterval']) && Yii::$app->session['timeInterval'] != "all"){
            return true;
        }
        return false;
    }

    public static function getStartInterval()
    {
        if(Yii::$app->session['timeInterval'] == 'today'){
            $time = time();
        }
        if(Yii::$app->session['timeInterval'] == 'yesterday'){
            $time = time() - 86400;
        }
        $dtNow = new DateTime();
// Set a non-default timezone if needed
        $dtNow->setTimestamp($time);
        $dtNow->modify('today');
        return $dtNow->getTimestamp();
    }

    public static function getEndInterval()
    {
        if(Yii::$app->session['timeInterval'] == 'today'){
            $time = time();
        }
        if(Yii::$app->session['timeInterval'] == 'yesterday'){
            $time = time() - 86400;
        }
        $dtNow = new DateTime();
// Set a non-default timezone if needed
        $dtNow->setTimestamp($time);
        $dtNow->modify('today');
        $endOfDay = clone $dtNow;
        $endOfDay->modify('tomorrow');
        $endOfDateTimestamp = $endOfDay->getTimestamp();
        $endOfDay->setTimestamp($endOfDateTimestamp - 1);
        return $endOfDay->getTimestamp();
    }

}