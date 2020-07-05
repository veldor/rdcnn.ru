<?php


namespace app\controllers;


use app\models\FileUtils;
use app\models\utils\MailSettings;
use app\models\utils\Management;
use app\models\utils\TimeHandler;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ManagementController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8', 301);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'check-update',
                            'check-changes',
                            'update-dependencies',
                            'reset-change-check-counter',
                            'check-changes-sync',
                            'check-java',
                            'restart-server',
                            'send-mail',
                            'add-backgrounds'
                        ],
                        'roles' => [
                            'manager'
                        ],
                        //'ips' => Info::ACCEPTED_IPS,
                    ],
                ],
            ],
        ];
    }

    /**
     * принудительная загрузка обновления с гитхаба
     */
    public function actionCheckUpdate(): void
    {
        // отмечу время проверки обновления
        FileUtils::setLastCheckUpdateTime();
        $file = Yii::$app->basePath . '\\updateFromGithub.bat';
        if (is_file($file)) {
            $command = $file . ' ' . Yii::$app->basePath;
            $outFilePath = Yii::$app->basePath . '\\logs\\update_file.log';
            $outErrPath = Yii::$app->basePath . '\\logs\\update_err.log';
            $command .= ' > ' . $outFilePath . ' 2>' . $outErrPath . ' &"';
            echo $command;
            try {
                // попробую вызвать процесс асинхронно
                $handle = new \COM('WScript.Shell');
                $handle->Run($command, 0, false);
            } catch (Exception $e) {
                exec($command);
            }
        }
    }

    /**
     * принудительная проверка содержимого папок
     */
    public function actionCheckChanges()
    {
        FileUtils::writeUpdateLog('try to start : ' . TimeHandler::timestampToDate(time()));
        FileUtils::writeUpdateLog('result is  : ' . Management::handleChanges());
    }

    public function actionUpdateDependencies()
    {

        $file = Yii::$app->basePath . '\\composerUpdate.bat';
        if (is_file($file)) {
            $command = $file . ' ' . Yii::$app->basePath;
            $outFilePath = Yii::$app->basePath . '\\logs\\update_file.log';
            $outErrPath = Yii::$app->basePath . '\\logs\\update_err.log';
            $command .= ' > ' . $outFilePath . ' 2>' . $outErrPath . ' &"';
            echo $command;
            try {
                // попробую вызвать процесс асинхронно
                $handle = new \COM('WScript.Shell');
                $handle->Run($command, 0, false);
            } catch (Exception $e) {
                exec($command);
            }
        }
    }

    public function actionResetChangeCheckCounter()
    {
        FileUtils::setUpdateFinished();
    }

    public function actionCheckChangesSync()
    {
        $file = Yii::$app->basePath . '\\yii.bat';
        $command = "$file console";
        exec($command, $output);
        var_dump($output);
    }

    public function actionRestartServer()
    {
        $file = Yii::$app->basePath . '\\restartServer.bat';
        // попробую вызвать процесс асинхронно
        $handle = new \COM('WScript.Shell');
        $handle->Run($file, 0, false);
    }

    public function actionCheckJava()
    {
        $file = Yii::$app->basePath . '\\checkJava.bat';
        if (is_file($file)) {
            $command = $file . ' ' . Yii::$app->basePath;
            $outFilePath = Yii::$app->basePath . '\\logs\\java_info.log';
            $outErrPath = Yii::$app->basePath . '\\logs\\java_info_error.log';
            $command .= ' > ' . $outFilePath . ' 2>' . $outErrPath . ' &"';
            echo $command;
            try {
                // попробую вызвать процесс асинхронно
                $handle = new \COM('WScript.Shell');
                $handle->Run($command, 0, false);
            } catch (Exception $e) {
                exec($command);
            }
        }
    }

    public function actionSendMail()
    {
        $settingsFile = Yii::$app->basePath . '\\priv\\mail_settings.conf';
        // получу данные
        $content = file_get_contents($settingsFile);
        $settingsArray = mb_split("\n", $content);
        if (count($settingsArray) === 3) {
            // получу текст письма
            $text = 'test';
            // отправлю письмо
            $mail = Yii::$app->mailer->compose()
                ->setFrom([MailSettings::getInstance()->address => 'Ошибки сервера РДЦ'])
                ->setSubject('Найдены новые ошибки')
                ->setHtmlBody($text)
                ->setTo(['eldorianwin@gmail.com' => 'eldorianwin@gmail.com']);
            // попробую отправить письмо, в случае ошибки- вызову исключение
            echo $mail->send();
        }
        else{
            echo 'no mail settings';
        }
    }
}