<?php

require_once dirname(__DIR__) . '/priv/Info.php';

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=rdcnn',
    'username' => \app\priv\Info::DB_LOGIN,
    'password' => \app\priv\Info::DB_PASSWORD,
    'charset' => 'utf8',
];