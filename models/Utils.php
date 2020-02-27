<?php


namespace app\models;


use app\priv\Info;
use DateTime;
use Yii;
use yii\base\Model;

class Utils extends Model
{
    public static function secondsToTime($seconds)
    {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a дней, %h часов, %i минут %s секунд');
    }

    public static function isCenterFiltered()
    {
        if (!empty(Yii::$app->session['center']) && Yii::$app->session['center'] != "all") {
            return true;
        }
        return false;
    }

    public static function isFiltered(User $execution)
    {
        // если фильтр по авроре- номер должен начинаться с буквы A, если НВ- с цифры
        $firstSymbol = substr($execution->username, 0, 1);
        if ((Yii::$app->session['center'] == 'aurora' && $firstSymbol == 'A') || (Yii::$app->session['center'] == 'nv' && !empty((int)$firstSymbol))) {
            return false;
        }
        return true;
    }

    public static function getSort()
    {
        if(!empty(Yii::$app->session['sortBy'])){
            return Yii::$app->session['sortBy'];
        }
        return "byTime";
    }

    public static function isTimeFiltered()
    {
        if (!empty(Yii::$app->session['timeInterval']) && Yii::$app->session['timeInterval'] != "all") {
            return true;
        }
        return false;
    }

    public static function getStartInterval()
    {
        if (Yii::$app->session['timeInterval'] == 'today') {
            $time = time();
        }
        if (Yii::$app->session['timeInterval'] == 'yesterday') {
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
        if (Yii::$app->session['timeInterval'] == 'today') {
            $time = time();
        }
        if (Yii::$app->session['timeInterval'] == 'yesterday') {
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

    public static function clearGarbage()
    {
        // найду в папке с файлами обследований все папки, и почищу их
        $files = scandir(Info::EXEC_FOLDER);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $path = Info::EXEC_FOLDER . "/$file";
                if (is_dir($path)) {
                    ExecutionHandler::rmRec($path);
                }
            }
        }
    }

    public static function sortExecutions(array $executionsList)
    {
        usort(/**
         * @param $execution1 User
         * @param $executon2 User
         * @return mixed
         */ $executionsList, function ($execution1, $execution2){
            switch (self::getSort()){
                case "byTime":
                    return $execution1->created_at < $execution2->created_at;
                case "byNumber":
                    return $execution1->username < $execution2->username;
                case "byExecutions":
                    return ExecutionHandler::isExecution($execution1->username) < ExecutionHandler::isExecution($execution2->username);
                case "byConclusion":
                    return ExecutionHandler::isConclusion($execution1->username) < ExecutionHandler::isConclusion($execution2->username);
            }
        });
        return $executionsList;
    }

}