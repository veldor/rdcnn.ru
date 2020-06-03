<?php

use app\assets\BaseAsset;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;

BaseAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
?>

<div class="row">
    <div class="col-sm-12">
        <div class="btn-group-vertical">
            <button class="btn btn-default activator" data-action="/management/check-update"><span>Check update</span></button>
            <button class="btn btn-default activator" data-action="/management/check-changes"><span>Check changes</span></button>
        </div>
    </div>
</div>
