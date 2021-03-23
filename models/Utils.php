<?php


namespace app\models;


use app\models\database\PatientInfo;
use app\models\utils\MailHandler;
use DateTime;
use Exception;
use Yii;
use yii\base\Model;
use yii\db\StaleObjectException;

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
     * @param bool $forToday
     * @return int
     * @throws Exception
     */
    public static function getStartInterval($forToday = false): int
    {
        if($forToday){
            $dtNow = new DateTime();
        }
        else{
            $dtNow = self::setupDay();
        }
        $dtNow->modify('today');
        return $dtNow->getTimestamp();
    }

    /**
     * Получение временной метки завершения суток
     * @param bool $forToday
     * @return int
     * @throws Exception
     */
    public static function getEndInterval($forToday = false): int
    {
        if($forToday){
            $dtNow = new DateTime();
        }
        else{
            $dtNow = self::setupDay();
        }
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
                    return $execution1->username < $execution2->username ? 1 : 0;
                case 'byExecutions':
                    return ExecutionHandler::isExecution($execution1->username) > ExecutionHandler::isExecution($execution2->username)? 1 : 0;
                case 'byConclusion':
                    return ExecutionHandler::isConclusion($execution1->username) > ExecutionHandler::isConclusion($execution2->username)? 1 : 0;
                case 'byTime':
                default:
                    return $execution1->created_at < $execution2->created_at ? 1 : 0;
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

    public static function sendTest()
    {
        $username = 'Ольга Викторовна';
        $text = "<br/><br/>Добрый день, $username<br/> 
В Региональном диагностическом центре открылось отделение <b>компьютерной томографии</b> по адресу:<br/> <b>г. Нижний Новгород, ул. Советская, д.12 (пл. Ленина). </b><br/>
Записаться на исследования вы можете по тел. <a href='tel:88312020200'>+7(831)20-20-200</a>. <br/>
Подробная информация на нашем сайте <a href='http://www.мрт-кт.рф'>www.мрт-кт.рф</a><br/><br/><br/>
<a href='http://xn----ttbeqkc.xn--p1ai/nn/kt'><img class='advice' src='https://rdcnn.ru/images/ct_advice.jpg' alt='ct_advice'></a><br/><br/><br/><br/>
<a href='https://rdcnn.ru/unsubscribe/#'><b>Если не хотите получать от нас письма- нажмите сюда</b></a>
";
        MailHandler::sendMessage('Тест рассылки',
        $text,
        'om@rdcnn.ru',
        'Ольга Царапкина',
        null);
    }

    public static function handlePatientsTable()
    {
        // получить одновременно десять покупателей и перебрать их одного за другим
        /** @var PatientInfo $patient */
        foreach (PatientInfo::find()->each(10) as $patient) {
            $dublicates = PatientInfo::find()->where(['email' => $patient->email])->count();
            if($dublicates > 1){
                $values = PatientInfo::findAll(['email' => $patient->email]);
                if(!empty($values)){
                    $counter = 0;
                    foreach ($values as $value) {
                        if($counter === 0){
                            continue;
                        }
                        try {
                            $value->delete();
                        } catch (StaleObjectException | \Throwable $e) {}
                    }
                }
            }
//            if($patient->unsubscribe_token === null){
//                $patient->unsubscribe_token = Yii::$app->security->generateRandomString(255);
//                $patient->save();
//            }
        }
    }

}