<?php

use app\assets\PersonalAsset;
use app\models\ExecutionHandler;
use app\models\User;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;


PersonalAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $execution User */

$this->title = "РДЦ, обследование " . $execution->username;

?>

<div id="ourLogo"></div>

<h1 class="text-center">Обследование № <?= $execution->username ?></h1>

<div class="col-sm-12">

    <?php
        echo "<div id='availabilityTimeContainer' class='alert alert-info text-center " . (ExecutionHandler::isConclusion($execution->username) ? '' : 'hidden') . "'><span class='glyphicon glyphicon-info-sign'></span> Данные исследования будут доступны в течение <span id='availabilityTime'></span></div>";
    ?>
</div>


<div class="col-sm-12 col-md-6 col-md-offset-3">
    <?php
    echo "<a id='downloadConclusionBtn' class='btn btn-primary btn-lg btn-block margin " . (ExecutionHandler::isConclusion($execution->username) ? '' : 'hidden') . "' href='" . Url::toRoute('download/conclusion') . "' role='button'><span class='glyphicon glyphicon-cloud-download'></span> Загрузить заключение врача</a>";

    echo "<a id='printConclusionBtn' class='btn btn-primary  btn-lg btn-block margin " . (ExecutionHandler::isConclusion($execution->username) ? '' : 'hidden') . "' target='_blank' href='" . Url::toRoute('download/print-conclusion') . "' role='button'><span class='glyphicon glyphicon-print'></span> Распечатать заключение врача</a>";

    echo "<a id='conclusionNotReadyBtn' class='btn btn-primary  btn-lg btn-block margin disabled " . (ExecutionHandler::isConclusion($execution->username) ? 'hidden' : '') . "' role='button'>Заключение врача ещё не готово</a>";

    echo "<a id='downloadExecutionBtn' class='btn btn-primary  btn-lg btn-block margin " . (ExecutionHandler::isExecution($execution->username) ? '' : 'hidden') . "' href='" . Url::toRoute('download/execution') . "' role='button'><span class='glyphicon glyphicon-cloud-download'></span> Загрузить данные сканирования</a>";

    echo "<a id='executionNotReadyBtn' class='btn btn-primary btn-lg btn-block margin disabled " . (ExecutionHandler::isExecution($execution->username) ? 'hidden' : '') . "' role='button'>Данные сканирования пока недоступны</a>";

    echo "<a id='clearDataBtn' class='btn btn-danger btn-lg btn-block margin' role='button'><span class='glyphicon glyphicon-trash'></span> Удалить данные</a>";
    ?>
    <?php
    echo Html::beginForm(['/site/logout'], 'post')
        . Html::submitButton(
            '<span class="glyphicon glyphicon-log-out"></span> Выйти из учётной записи',
            ['class' => 'btn btn-primary btn-lg btn-block margin logout']
        )
        . Html::endForm();
    ?>
</div>

<div class="col-sm-12 text-center">
    <div class="alert alert-success"><span class='glyphicon glyphicon-info-sign'></span> Если Вам необходима печать на заключение, обратитесь в центр, где Вы проходили
        исследование
    </div>
    <?php
        echo "<div id='removeReasonContainer' class='alert alert-info " . (ExecutionHandler::isConclusion($execution->username) ? '' : 'hidden') . "'><span class='glyphicon glyphicon-info-sign'></span> Ограничение доступа к данным исследования по времени необходимо в целях обеспечения безопасности Ваших персональных данных</div>";
    ?>

    <a href="tel:+78312020200" class="btn btn-default"><span class="glyphicon glyphicon-earphone text-success"></span><span class="text-success"> +7(831)20-20-200</span></a>
</div>


