<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\ExecutionHandler;
use app\models\FileUtils;
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

    /**
     * This command load data from Gdrive and handle changes
     * @return int Exit code
     * @throws \Exception
     */
    public function actionIndex(): int
    {
        // проверю, не запущено ли уже обновление, если запущено- ничего не делаю
        if (FileUtils::isUpdateInProgress()) {
            return ExitCode::OK;
        }
        try {
            FileUtils::setUpdateInProgress();
            FileUtils::writeUpdateLog('start : ' . TimeHandler::timestampToDate(time()));
            echo "Checking changes\n";

            // подключаю Gdrive, проверю заключения, загруженные из папок

            try {
                Gdrive::check();
            }catch (Exception $e) {
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
            echo "Finish changes handle\n";
            FileUtils::writeUpdateLog('finish : ' . TimeHandler::timestampToDate(time()));
            FileUtils::setLastUpdateTime();
        } finally {
            FileUtils::setUpdateFinished();
        }
        return ExitCode::OK;
    }

    public function actionSendErrors(): void
    {
        MyErrorHandler::sendErrors();
    }
}
