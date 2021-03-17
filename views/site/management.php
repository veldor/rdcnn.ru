<?php

use app\assets\ManagementAsset;
use app\models\database\ViberPersonalList;
use app\models\FileUtils;
use app\models\utils\GrammarHandler;
use app\priv\Info;
use app\models\Table_blacklist;
use app\models\utils\TimeHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

ManagementAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Всякие разные настройки';

/* @var $this View */
/* @var $updateInfo string */
/* @var $outputInfo string */
/* @var $errorsInfo string */
/* @var $errors string */
/* @var $updateOutputInfo string */
/* @var $updateErrorsInfo string */
/* @var $telegramInfo array */

\app\models\utils\FirebaseHandler::sendConclusionLoaded(
        '9203',
        'a1000.pdf',
    false
);
?>

<div class="text-center">
    <div class="float-left"><a href="/"><span class="glyphicon glyphicon-chevron-left"></span></a></div>
    <span>Весия ПО: <b class="text-success"><?= FileUtils::getSoftwareVersion() ?></b></span>
</div>

<ul class="nav nav-tabs">
    <li id="bank_set_li" class="active"><a href="#global_actions" data-toggle="tab" class="active">Обшие действия</a>
    </li>
    <li><a href="#reports" data-toggle="tab">Отчёты</a></li>
    <li><a href="#blacklist_actions" data-toggle="tab">Чёрный список</a></li>
    <li><a href="#telegram_actions" data-toggle="tab">Телеграм</a></li>
    <li><a href="#existent_conclusions" data-toggle="tab">Файлы заключений</a></li>
    <li><a href="#existent_executions" data-toggle="tab">Обследования</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="global_actions">
        <div class="row">
            <div class="col-sm-12">Статус проверки новых
                данных: <?= FileUtils::isUpdateInProgress() ? '<b class="text-danger">Проверяются</b>' : '<b class="text-success">Ожидание</b>' ?>
                <br/>Последняя проверка: <?= TimeHandler::timestampToDate(FileUtils::getLastUpdateTime()) ?><br>Последняя
                проверка обновлений: <?= TimeHandler::timestampToDate(FileUtils::getLastCheckUpdateTime()) ?></div>
            <div class="col-sm-12">
                <div class="btn-group-vertical">
                    <button class="btn btn-default activator" data-action="/management/check-update">
                        <span>Check update</span></button>
                    <button class="btn btn-default activator" data-action="/management/check-changes"><span>Check changes</span>
                    </button>
                    <button class="btn btn-default activator" data-action="/management/update-dependencies"><span>Updating dependencies</span>
                    </button>
                    <button class="btn btn-default activator" data-action="/management/restart-server"><span>Restart server</span>
                    </button>
                    <button class="btn btn-default activator" data-action="/management/send-mail">
                        <span>Send test mail</span></button>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane margened" id="reports">

        <div class="col-sm-12">
            <div class="col-sm-4">
                <h3 class="text-center">Last TG message</h3>
                <?= FileUtils::getLastTgMessage() ?>
            </div>
            <div class="col-sm-4">
                <h3 class="text-center">Last TG state</h3>
                <?= FileUtils::getLastTelegramLog() ?>
            </div>
            <div class="col-sm-4">
                <h3 class="text-center">Action log</h3>
                <?= str_replace("\n", '<br/>', $outputInfo) ?>
            </div>
            <div class="col-sm-4">
                <h3 class="text-center">Errors log</h3>
                <?= str_replace("\n", '<br/>', GrammarHandler::convertToUTF($errorsInfo)) ?>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="col-sm-4">
                <h3 class="text-center">Update process log</h3>
                <?= str_replace("\n", '<br/>', $updateOutputInfo) ?>
            </div>
            <div class="col-sm-4">
                <h3 class="text-center">Update errors log</h3>
                <?= str_replace("\n", '<br/>', $updateErrorsInfo) ?>
            </div>
            <div class="col-sm-4">
                <h3 class="text-center">Java info</h3>
                <?= str_replace("\n", '<br/>', FileUtils::getJavaInfo()) ?>
            </div>
        </div>
        <div class="col-sm-12 text-center">
            <h3>Service errors</h3>
            <?= str_replace("\n", '<br/>', $errors) ?>
        </div>
    </div>
    <div class="tab-pane margened" id="blacklist_actions">
        <?php
        // получу список ip из чёрного списка
        $blacklistData = Table_blacklist::find()->all();
        if ($blacklistData !== null && count($blacklistData) > 0) {
            echo "<table class='table table-striped table-hover'>";
            /** @var Table_blacklist $item */
            foreach ($blacklistData as $item) {
                echo "<tr><td>{$item->ip}</td><td>" . TimeHandler::timestampToDate($item->last_try) . "</td></tr>";
            }
            echo "</table>";
            echo '<div class="text-center"><button class="btn btn-default activator" data-action="/management/clear-blacklist-table"><span>Clear blacklist table</span></button></div>';
        } else {
            echo "<h2 class='text-center'>Empty</h2>";
        }
        ?>
    </div>
    <div class="tab-pane margened" id="telegram_actions">
        <?php
        // получу список ip из чёрного списка
        $data = ViberPersonalList::find()->all();
        if ($data !== null && count($data) > 0) {
            echo "<table class='table table-striped table-hover'>";
            /** @var ViberPersonalList $item */
            foreach ($data as $item) {
                echo "<tr><td>{$item->id}</td><td>{$item->get_errors}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<h2 class='text-center'>Empty</h2>";
        }
        ?>
    </div>
    <div class="tab-pane margened" id="existent_conclusions">
        <?php
        // получу список ip из чёрного списка
        $files = array_slice(scandir(Info::CONC_FOLDER), 2);
        if ($files !== null && count($files) > 0) {
            echo "<table class='table table-striped table-hover'>";
            foreach ($files as $item) {
                $stat = stat(Info::CONC_FOLDER . DIRECTORY_SEPARATOR . $item);
                $changeTime = $stat['mtime'];
                echo "<tr><td>$item</td><td>" . TimeHandler::timestampToDate($changeTime) . "</td><td><a href='#' class='activator' data-action='/delete/conc/$item'><span class='glyphicon glyphicon-trash text-danger'></span></a></td></tr>";
            }
            echo "</table>";
        } else {
            echo "<h2 class='text-center'>Empty</h2>";
        }
        ?>
    </div>
    <div class="tab-pane margened" id="existent_executions">
        <?php
        $files = array_slice(scandir(Info::EXEC_FOLDER), 2);
        if ($files !== null && count($files) > 0) {
            echo "<table class='table table-striped table-hover'>";
            foreach ($files as $item) {
                $path = Info::EXEC_FOLDER . DIRECTORY_SEPARATOR . $item;
                $stat = stat($path);
                $changeTime = $stat['mtime'];
                $type = is_dir($path) ? "<span class='glyphicon glyphicon-folder-close text-info'></span>" : "<span class='glyphicon glyphicon-file text-success'></span>";
                echo "<tr><td>$item</td><td>{$type}</td><td>" . TimeHandler::timestampToDate($changeTime) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<h2 class='text-center'>Empty</h2>";
        }
        ?>
    </div>
</div>

