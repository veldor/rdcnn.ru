<?php

use app\assets\PersonalAsset;
use app\models\ExecutionHandler;
use app\models\Table_availability;
use app\models\User;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;


PersonalAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $execution User */

$this->title = 'РДЦ, обследование ' . $execution->username;

?>

<div id="ourLogo" class="visible-sm visible-md visible-lg "></div>
<div id="ourSmallLogo" class="visible-xs"></div>

<h1 class="text-center">Обследование № <?= $execution->username ?></h1>

<?php
$name = Table_availability::getPatientName($execution->username);
if($name !== null){
    echo "<h2 class='text-center'>{$name}</h2>";
}
?>

<div class="col-sm-12 col-md-6 col-md-offset-3">

    <?php
    echo "<div id='availabilityTimeContainer' class='alert alert-info text-center " . (ExecutionHandler::isConclusion($execution->username) ? '' : 'hidden') . "'><span class='glyphicon glyphicon-info-sign'></span> Данные обследования будут доступны в течение<br/> <span id='availabilityTime'></span></div>";
    ?>
</div>

<div class="col-sm-12 col-md-6 col-md-offset-3">
    <div id="conclusionsContainer">
        <?php
        // получу список заключений
        $conclusions = Table_availability::getConclusions($execution->username);
        if (empty($conclusions)) {
            echo "<a id='conclusionNotReadyBtn' class='btn btn-primary btn-block margin with-wrap disabled' role='button'>Заключение врача в работе</a>";
        } else {
            foreach ($conclusions as $conclusion) {
                echo "
                <a href='" . Url::toRoute(['/download/conclusion', 'href' => $conclusion->file_name]) . "' class='btn btn-primary btn-block margin with-wrap conclusion hinted' data-href='$conclusion->file_name'>Загрузить заключение врача<br/>{$conclusion->execution_area}</a>
                <a target='_blank' href='" . Url::toRoute(['/download/print-conclusion', 'href' => $conclusion->file_name]) . "' class='btn btn-info btn-block margin with-wrap print-conclusion hinted' data-href='$conclusion->file_name'>Распечатать заключение врача<br/>{$conclusion->execution_area}</a>
";
            }
        }
        ?>
    </div>
    <div id="executionContainer">
        <?php
        // если доступно заключение- дам ссылку на него
        if (ExecutionHandler::isExecution($execution->username)) {
            echo "<a id='executionReadyBtn' href='" . Url::toRoute('/download/execution') . "' class='btn btn-primary  btn btn-block margin with-wrap hinted' data-href='/download/execution'>Загрузить архив обследования</a><br><a  href='" . Url::toRoute('/dicom-viewer') . "' class='btn btn-primary  btn btn-block margin with-wrap hinted'>Просмотр загруженных изображений</a>";
        } else {
            echo "<a id='executionNotReadyBtn' class='btn btn-primary btn-block margin with-wrap disabled' role='button'>Архив обследования подготавливается</a>";
        }
        ?>
    </div>
    <?php
    echo "<a id='clearDataBtn' class='btn btn-danger btn-block margin with-wrap' role='button'><span class='glyphicon glyphicon-trash'></span> Удалить данные</a>";
    echo Html::beginForm(['/site/logout'], 'post')
        . Html::submitButton(
            '<span class="glyphicon glyphicon-log-out"></span> Выйти из учётной записи',
            ['class' => 'btn btn-primary btn btn-block margin with-wrap logout']
        )
        . Html::endForm();
    ?>
</div>

<div class="col-sm-12 col-md-6 col-md-offset-3 text-center">
    <div class="alert alert-success"><span class='glyphicon glyphicon-info-sign'></span> Если Вам необходима печать на
        заключение, обратитесь в центр, где Вы проходили
        исследование
    </div>
    <?php
    echo "<div id='removeReasonContainer' class='alert alert-info " . (ExecutionHandler::isConclusion($execution->username) ? '' : 'hidden') . "'><span class='glyphicon glyphicon-info-sign'></span> Ограничение доступа к данным исследования по времени необходимо в целях обеспечения безопасности Ваших персональных данных</div>";
    ?>

    <a href="tel:+78312020200" class="btn btn-default"><span
                class="glyphicon glyphicon-earphone text-success"></span><span
                class="text-success"> +7(831)20-20-200</span></a>
</div>


