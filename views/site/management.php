<?php

use app\assets\BaseAsset;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

BaseAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $updateInfo string */
/* @var $outputInfo string */
/* @var $errorsInfo string */
?>

<div class="row">
    <div class="col-sm-12">
        <div class="btn-group-vertical">
            <button class="btn btn-default activator" data-action="/management/check-update"><span>Check update</span></button>
            <button class="btn btn-default activator" data-action="/management/check-changes"><span>Check changes</span></button>
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
</div>
