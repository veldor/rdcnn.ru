<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\ExecutionHandler;
use app\models\utils\Gdrive;
use DateTime;
use Google_Exception;
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

        $date = new DateTime();
        $date = $date->format('Y-m-d H:i:s');
        $logPath = dirname(__DIR__) . '\\logs\\update.log';
        file_put_contents($logPath, 'start: ' . $date . "\n", FILE_APPEND);

        echo "Checking changes\n";

        // подключаю Gdrive, проверю заключения, загруженные из папок

        try {
            Gdrive::check();
        } catch (Google_Exception $e) {
        } catch (Exception $e) {
            echo "error work with Gdrive: {$e->getMessage()}";
        }
        catch (Google_Exception $ge){
            echo "error work with google : {$ge->getMessage()}";
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
        $date = new DateTime();
        $date = $date->format('Y-m-d H:i:s');
        $logPath = dirname(__DIR__) . '\\logs\\update.log';
        file_put_contents($logPath, $date . "\n", FILE_APPEND);
        return ExitCode::OK;
    }

}
