<?php


namespace app\models\utils;


use app\models\database\Emails;
use app\models\database\TempDownloadLinks;
use app\models\Table_availability;
use app\models\User;
use app\priv\Info;
use Yii;
use yii\base\Model;
use yii\helpers\Url;

class MailHandler extends Model
{
    public static function getMailText($text):string
    {
        return Yii::$app->controller->renderPartial('/site/mail-template', ['text' => $text]);
    }

    public static function sendInfoMail($id): array
    {
        $user = User::findIdentity($id);
        if($user !== null){
            // первым делом, получу данные о почте
            $mail = Emails::findOne(['patient_id' => $id]);
            if($mail !== null){
                // отправлю сообщение о доступных данных
                $text = '
                <h1>Вы прошли обследование в региональном диагностическом центре</h1>
                <p>
                Результаты обследования будут доступны в личном кабинете по <a href="https://rdcnn.ru/person/' . $id . '">ссылке</a>
                </p
            ';
                // проверю наличие снимков
                $existentExecution = Table_availability::findOne(['is_execution' => 1, 'userId' => $user->username]);
                if($existentExecution !== null){
                    // проверю существование файла
                    $path = Info::EXEC_FOLDER . DIRECTORY_SEPARATOR . $existentExecution->file_name;
                    if(is_file($path)){
                        // проверю, существует ли уже ссылка на файл
                        $existentLink = TempDownloadLinks::findOne(['execution_id' => $id, 'file_type' => 'execution', 'file_name' => $existentExecution->file_name]);
                        if($existentLink === null){
                            // создам ссылку
                            $existentLink = TempDownloadLinks::createLink($user, 'execution', $existentExecution->file_name);
                        }
                        $text .= "<p>
                                        Доступен для скачивания архив обследования- <a href='" . Url::toRoute(['download/download-temp', 'link' => $existentLink->link], 'https') . "'>Скачать</a>
                                  </p>";
                    }
                }
                // проверю наличие заключения
                $existentConclusions = Table_availability::findAll(['is_conclusion' => 1, 'userId' => $user->username]);
                if($existentConclusions !== null){
                    foreach ($existentConclusions as $existentConclusion) {
                        // проверю существование файла
                        $path = Info::CONC_FOLDER . DIRECTORY_SEPARATOR . $existentConclusion->file_name;
                        if(is_file($path)){
                            // проверю, существует ли уже ссылка на файл
                            $existentLink = TempDownloadLinks::findOne(['execution_id' => $id, 'file_type' => 'conclusion', 'file_name' => $existentConclusion->file_name]);
                            if($existentLink === null){
                                // создам ссылку
                                $existentLink = TempDownloadLinks::createLink($user, 'conclusion', $existentConclusion->file_name);
                            }
                            $text .= "<p>
                                        Доступно заключение врача- <a href='" . Url::toRoute(['download/download-temp', 'link' => $existentLink->link], 'https') . "'>Скачать</a>
                                  </p>";
                        }
                    }
                }
                if(self::sendMessage('Заголовок', $text, $mail->address)){

                    return ['status' => 1, 'message' => 'Сообщение отправлено'];
                }
            }
        }

        return ['status' => 1, 'message' => 'Не найден адрес почты'];
    }

    private static function sendMessage($title, $text, $address): bool
    {
        $settingsFile = Yii::$app->basePath . '\\priv\\mail_settings.conf';
        if(is_file($settingsFile)){
            // получу данные
            $content = file_get_contents($settingsFile);
            $settingsArray = mb_split("\n", $content);
            if(count($settingsArray) === 3){
                    $text = self::getMailText($text);
                    // отправлю письмо
                    $mail = Yii::$app->mailer->compose()
                        ->setFrom([$settingsArray[0] => 'Ошибки сервера РДЦ'])
                        ->setSubject($title)
                        ->setHtmlBody($text)
                        ->setTo([$address]);
                    // попробую отправить письмо, в случае ошибки- вызову исключение
                    $mail->send();
                    return true;
            }
        }
        return false;
    }
}