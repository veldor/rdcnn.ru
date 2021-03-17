<?php

// comment out the following two lines when deployed to production
use app\models\utils\MyErrorHandler;

$host = $_SERVER['REMOTE_ADDR'];
if($host === '127.0.0.1'){
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'dev');
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

try {
    (new yii\web\Application($config))->run();
} catch (Exception $e) {
    // отправлю отчёт об ошибке
    MyErrorHandler::sendError($e);
    throw $e;
}
