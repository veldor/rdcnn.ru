<?php

use app\assets\BaseAsset;
use app\models\FileUtils;
use app\models\utils\TimeHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

BaseAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $updateInfo string */
/* @var $outputInfo string */
/* @var $errorsInfo string */
/* @var $errors string */
/* @var $updateOutputInfo string */
/* @var $updateErrorsInfo string */
/* @var $telegramInfo array */
?>

<div class="row">
    <div class="col-sm-12">Статус проверки новых данных: <?= FileUtils::isUpdateInProgress() ? '<b class="text-danger">Проверяются</b>' : '<b class="text-success">Ожидание</b>'?><br/>Последняя проверка: <?= TimeHandler::timestampToDate(FileUtils::getLastUpdateTime())?><br>Последняя проверка обновлений: <?= TimeHandler::timestampToDate(FileUtils::getLastCheckUpdateTime())?></div>
    <div class="col-sm-12">
        <div class="btn-group-vertical">
            <button class="btn btn-default activator" data-action="/management/check-update"><span>Check update</span></button>
            <button class="btn btn-default activator" data-action="/management/check-changes"><span>Check changes</span></button>
            <button class="btn btn-default activator" data-action="/management/update-dependencies"><span>Updating dependencies</span></button>
            <button class="btn btn-default activator" data-action="/management/add-backgrounds"><span>Add backgrounds</span></button>
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
        <div class="col-sm-6">
            <h3 class="text-center">Update process log</h3>
            <?=str_replace("\n", '<br/>', $updateOutputInfo)?>
        </div>
        <div class="col-sm-6">
            <h3 class="text-center">Update errors log</h3>
            <?=str_replace("\n", '<br/>', $updateErrorsInfo)?>
        </div>
    </div>
    <div class="col-sm-12 text-center">
        <h3>Service errors</h3>
        <?=str_replace("\n", '<br/>', $errors)?>
    </div>
</div>