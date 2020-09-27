<?php

use yii\db\Connection;

require_once dirname(__DIR__) . '/priv/Info.php';

return [
    'class' => Connection::class,
    'dsn' => 'mysql:host=localhost;dbname=rdcnn',
    'username' => \app\priv\Info::DB_LOGIN,
    'password' => \app\priv\Info::DB_PASSWORD,
    'charset' => 'utf8',
];