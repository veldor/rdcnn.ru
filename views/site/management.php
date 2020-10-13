<?php

use app\assets\ManagementAsset;
use app\models\database\ViberPersonalList;
use app\models\FileUtils;
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
?>

<ul class="nav nav-tabs">
    <li id="bank_set_li" class="active"><a href="#global_actions" data-toggle="tab" class="active">Обшие действия</a></li>
    <li><a href="#blacklist_actions" data-toggle="tab">Чёрный список</a></li>
    <li><a href="#telegram_actions" data-toggle="tab">Телеграм</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="global_actions">
        <div class="row">
            <div class="col-sm-12">Статус проверки новых данных: <?= FileUtils::isUpdateInProgress() ? '<b class="text-danger">Проверяются</b>' : '<b class="text-success">Ожидание</b>'?><br/>Последняя проверка: <?= TimeHandler::timestampToDate(FileUtils::getLastUpdateTime())?><br>Последняя проверка обновлений: <?= TimeHandler::timestampToDate(FileUtils::getLastCheckUpdateTime())?></div>
            <div class="col-sm-12">
                <div class="btn-group-vertical">
                    <button class="btn btn-default activator" data-action="/management/check-update"><span>Check update</span></button>
                    <button class="btn btn-default activator" data-action="/management/check-changes"><span>Check changes</span></button>
                    <button class="btn btn-default activator" data-action="/management/update-dependencies"><span>Updating dependencies</span></button>
                    <!--            <button class="btn btn-default activator" data-action="/management/add-backgrounds"><span>Add backgrounds</span></button>-->
                    <!--            <button class="btn btn-default activator" data-action="/management/reset-change-check-counter"><span>Reset check counter</span></button>-->
                    <!--            <button class="btn btn-default activator" data-action="/management/check-changes-sync"><span>Sync check changes</span></button>-->
                    <button class="btn btn-default activator" data-action="/management/restart-server"><span>Restart server</span></button>
                    <!--            <button class="btn btn-default activator" data-action="/management/check-java"><span>Check Java</span></button>-->
                    <button class="btn btn-default activator" data-action="/management/send-mail"><span>Send test mail</span></button>
                    <button class="btn btn-default activator" data-action="/management/clear-blacklist-table"><span>Clear blacklist table</span></button>
                    <button class="btn btn-default activator" data-action="/management/change-tg-table"><span>Change tg table</span></button>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="col-sm-4">
                    <h3 class="text-center">Update log</h3>
                    <?=str_replace("\n", '<br/>', $updateInfo)?>
                </div>
                <div class="col-sm-4">
                    <h3 class="text-center">Action log</h3>
                    <?=str_replace("\n", '<br/>', $outputInfo)?>
                </div>
                <div class="col-sm-4">
                    <h3 class="text-center">Errors log</h3>
                    <?=str_replace("\n", '<br/>', $errorsInfo)?>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="col-sm-4">
                    <h3 class="text-center">Update process log</h3>
                    <?=str_replace("\n", '<br/>', $updateOutputInfo)?>
                </div>
                <div class="col-sm-4">
                    <h3 class="text-center">Update errors log</h3>
                    <?=str_replace("\n", '<br/>', $updateErrorsInfo)?>
                </div>
                <div class="col-sm-4">
                    <h3 class="text-center">Java info</h3>
                    <?=str_replace("\n", '<br/>', FileUtils::getJavaInfo())?>
                </div>
            </div>
            <div class="col-sm-12 text-center">
                <h3>Service errors</h3>
                <?=str_replace("\n", '<br/>', $errors)?>
            </div>

        </div>
    </div>
    <div class="tab-pane margened" id="blacklist_actions">
        <?php
        // получу список ip из чёрного списка
        $blacklistData = Table_blacklist::find()->all();
        if($blacklistData !== null && count($blacklistData) > 0){
            echo "<table class='table table-striped table-hover'>";
            /** @var Table_blacklist $item */
            foreach ($blacklistData as $item) {
                echo "<tr><td>{$item->ip}</td><td>" . TimeHandler::timestampToDate($item->last_try) . "</td></tr>";
            }
            echo "</table>";
        }
        else{
            echo "<h2 class='text-center'>Empty</h2>";
        }
        ?>
    </div>
    <div class="tab-pane margened" id="telegram_actions">
        <?php
        // получу список ip из чёрного списка
        $data = ViberPersonalList::find()->all();
        if($data !== null && count($data) > 0){
            echo "<table class='table table-striped table-hover'>";
            /** @var ViberPersonalList $item */
            foreach ($data as $item) {
                echo "<tr><td>{$item->id}</td><td>{$item->get_errors}</td></tr>";
            }
            echo "</table>";
        }
        else{
            echo "<h2 class='text-center'>Empty</h2>";
        }
        ?>
    </div>
</div>

