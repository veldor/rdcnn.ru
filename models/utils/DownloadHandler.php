<?php


namespace app\models\utils;


use app\models\Table_availability;
use app\models\Table_statistics;
use app\models\Telegram;
use app\models\User;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use Yii;
use yii\web\NotFoundHttpException;

class DownloadHandler
{

    /**
     * Обработаю выдачу заключения
     * @param string $href <p>Имя файла</p>
     * @param bool $print <p>Флаг распечатки, если стоит- pdf отдаётся как страница</p>
     * @throws NotFoundHttpException
     */
    public static function handleConclusion(string $href, $print = false): void
    {
        // если это запись администратора- загружу запись. Для этого узнаю, с какой страницы был переход
        if (Yii::$app->user->can('manage')) {
            if(empty($_SERVER['HTTP_REFERER'])){
                // левая ссылка, считаю, что ничего не найдено
                throw new NotFoundHttpException('Файл не найден');
            }
            $referer = explode('/', $_SERVER['HTTP_REFERER']);
            $executionNumber = $referer[array_key_last($referer)];
            self::uploadConclusion($href, $executionNumber, $print);
        } else if (Yii::$app->user->can('read')) {
            $executionNumber = Yii::$app->user->identity->username;
            self::uploadConclusion($href, $executionNumber, $print);
        }
        else{
            throw new NotFoundHttpException('Файл не найден');
        }
    }

    /**
     * @param string $href
     * @param $executionNumber
     * @param $print
     * @throws NotFoundHttpException
     */
    public static function uploadConclusion(string $href, $executionNumber, $print): void
    {
        if (!empty($executionNumber)) {
            // получу данные о пользователе
            $execution = User::findByUsername($executionNumber);
            if ($execution !== null) {
                // проверю, что заключение принадлежит именно этой учётной записи
                $base = GrammarHandler::getBaseFileName($href);
                if ($base === $execution->username) {
                    $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $href;
                    // проверю, если файл результатов сканирования присутствует- выдам его на загрузку
                    if (is_file($file)) {
                        // получу данные о пациенте
                        $avail = Table_availability::findOne(['file_name' => $href]);
                        if($avail !== null){
                            if($print){
                                Yii::$app->response->sendFile($file, 'МРТ ' . "{$avail->execution_area}.pdf", ['inline' => true]);
                                if (!Yii::$app->user->can('manage')) {
                                    // если обследование скачал пациент а не администратор- посчитаю скачивание
                                    Table_statistics::plusConclusionPrint($executionNumber);
                                    try {
                                        Telegram::sendError("Кто-то распечатал заключение. За всё время заключений распечатано: " . Table_statistics::getTotalConclusionsPrintCount());
                                    } catch (InvalidArgumentException $e) {
                                    } catch (Exception $e) {
                                    }
                                }
                            }
                            else{
                                Yii::$app->response->sendFile($file, 'МРТ ' . "{$avail->execution_area}.pdf");
                                if (!Yii::$app->user->can('manage')) {
                                    // если обследование скачал пациент а не администратор- посчитаю скачивание
                                    Table_statistics::plusConclusionDownload($executionNumber);
                                    try {
                                        Telegram::sendError("Кто-то скачал заключение. За всё время заключений скачано: " . Table_statistics::getTotalConclusionsCount());
                                    } catch (InvalidArgumentException $e) {
                                    } catch (Exception $e) {
                                    }
                                }
                            }
                        }
                        return;
                    }
                }
            }
        }
        throw new NotFoundHttpException('Файл не найден');
    }

    /**
     * Возвращает данные обследования при их наличии
     * @throws NotFoundHttpException
     */
    public static function handleExecution(): void
    {
        // если это запись администратора- загружу запись. Для этого узнаю, с какой страницы был переход
        if (Yii::$app->user->can('manage')) {
            if(empty($_SERVER['HTTP_REFERER'])){
                // левая ссылка, считаю, что ничего не найдено
                throw new NotFoundHttpException('Файл не найден');
            }
            $referer = explode('/', $_SERVER['HTTP_REFERER']);
            $executionNumber = $referer[array_key_last($referer)];
        } else if (Yii::$app->user->can('read')) {
            $executionNumber = Yii::$app->user->identity->username;
        }
        if (!empty($executionNumber)) {
            // получу данные о пользователе
            $execution = User::findByUsername($executionNumber);
            if ($execution !== null) {
                $file = Yii::getAlias('@executionsDirectory') . '\\' . $execution->username . '.zip';
                // проверю, если есть файл результатов сканирования- выдам его на загрузку
                if (is_file($file)) {
                    if (!Yii::$app->user->can('manage')){
                        // запишу данные о скачивании
                        Table_statistics::plusExecutionDownload($executionNumber);
                        try {
                            Telegram::sendError("Кто-то скачал файл обследования. За всё время обследований скачано: " . Table_statistics::getTotalExecutionsCount());
                        } catch (InvalidArgumentException $e) {
                        } catch (Exception $e) {
                        }
                    }
                    Yii::$app->response->sendFile($file, 'MRI_files_' . $execution->username . '.zip');
                }
            }
        }
    }
}