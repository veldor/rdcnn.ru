<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\Telegram;
use app\models\utils\Gdrive;
use app\models\utils\MyErrorHandler;
use app\models\utils\TimeHandler;
use yii\console\Controller;
use yii\console\Exception;
use yii\console\ExitCode;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ConsoleController extends Controller
{
    public function init():void
    {
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_ENV') or define('YII_ENV', 'dev');
    }

    /**
     * This command load data from Gdrive and handle changes
     * @return int Exit code
     * @throws \Exception
     */
    public function actionIndex(): int
    {
        FileUtils::writeUpdateLog('start update check : ' . TimeHandler::timestampToDate(time()));
        // проверю, не запущено ли уже обновление, если запущено- ничего не делаю
        if (FileUtils::isUpdateInProgress()) {
            return ExitCode::OK;
        }
        try {
            FileUtils::setUpdateInProgress();
            FileUtils::writeUpdateLog('start : ' . TimeHandler::timestampToDate(time()));
            echo TimeHandler::timestampToDate(time()) . "Checking changes\n";

            // подключаю Gdrive, проверю заключения, загруженные из папок

            try {
                Gdrive::check();
            } catch (Exception $e) {
                echo "error work with Gdrive: {$e->getMessage()}";
            }
            // теперь обработаю изменения
            try {
                ExecutionHandler::check();
            } catch (\Exception $e) {
                echo "error handling changes with message {$e->getMessage()}";
                echo $e->getTraceAsString();
            }
            //
            echo TimeHandler::timestampToDate(time()) . "Finish changes handle\n";
            FileUtils::writeUpdateLog('finish : ' . TimeHandler::timestampToDate(time()));
            FileUtils::setLastUpdateTime();
        }
        catch (\Exception $e){
            FileUtils::writeUpdateLog('error when handle changes : ' . $e->getMessage());
        }
        finally {
            FileUtils::setUpdateFinished();
        }
        return ExitCode::OK;
    }

    public function actionSendErrors(): void
    {
        MyErrorHandler::sendErrors();
    }

    /**
     * @throws Exception
     * @throws \Google_Exception
     * @throws \JsonException
     */
    public function actionCheckGdrive(): void
    {
        Gdrive::requireToken();
    }

    public function actionHandlePdf($fileDestination): int
    {
        FileUtils::addBackgroundToPDF($fileDestination);
        return ExitCode::OK;
    }
    public function actionHandleZip($fileId, $clientId): int
    {
        Telegram::downloadZip($fileId, $clientId);
        return ExitCode::OK;
    }
}
