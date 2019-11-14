<?php

use app\assets\AdminAsset;
use app\models\ExecutionHandler;
use app\models\User;
use app\models\Utils;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\web\View;

AdminAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Администрирование';

/* @var $this View */
/* @var $executions User[] */

$centers = ['all' => '', 'nv' => '', 'aurora' => ''];

$days = ['all' => '', 'today' => '', 'yesterday' => ''];

if (Utils::isCenterFiltered()) {
    $center = Yii::$app->session['center'];
    foreach ($centers as $key => $value) {
        if ($key === $center) {
            $centers[$key] = 'selected';
        } else {
            $centers[$key] = '';
        }
    }
}
if (Utils::isTimeFiltered()) {
    $time = Yii::$app->session['timeInterval'];
    foreach ($days as $key => $value) {
        if ($key === $time) {
            $days[$key] = 'selected';
        } else {
            $days[$key] = '';
        }
    }
}

// добавлю кнопку для создания нового обследования
echo "<div class='col-sm-12 text-center'>";

echo Html::button('Добавить обследование', ['class' => 'btn btn-lg btn-info margin', 'id' => 'addExecution']);

echo "</div><div class='col-sm-12'>";

echo Html::beginForm(['/administrate'], 'post');

echo "<div class='col-sm-6'><label class='control-label' for='#centerSelect'>Центр</label><select id='centerSelect' name='center' onchange='this.form.submit();' class='form-control'><option value='all'>Все</option><option value='nv' {$centers['nv']}>Нижневолжская набережная</option><option value='aurora' {$centers['aurora']}>Аврора</option></select></div>";

echo "<div class='col-sm-6'><label class='control-label' for='#centerSelect'>Время</label><select name='timeInterval' onchange='this.form.submit();' class='form-control'><option value='all'>Всё время</option><option value='today' {$days['today']}>Сегодня</option><option value='yesterday' {$days['yesterday']}>Вчера</option></select></div>";

echo Html::endForm();

echo "</div>";

$executionsCounter = 0;

if (!empty($executions)) {
    echo "<table class='table-hover table table-striped'><thead><tr><th>Номер обследования</th><th>Действия</th><th>Загружено заключение</th><th>Загружены файлы</th></tr></thead><tbody>";
    foreach ($executions as $execution) {

        // проверю, если включена фильтрация по центру- выведу только те обследования, которые проведены в этом центре
        if(Utils::isCenterFiltered()){
            if(Utils::isFiltered($execution)){
                continue;
            }
        }
            ++ $executionsCounter;
        ?>
        <tr>
            <td>
                <a class='btn-link' href='/person/<?= $execution->username ?>'><?= $execution->username ?></a>
            </td>
            <td>

                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="<?= $execution->username ?>"></label><label class='btn btn-default activator' data-toggle='tooltip' data-placement='auto' title='Добавить заключение'><span class='text-info glyphicon glyphicon-file'></span><input id="addConclusion" data-id='<?= $execution->username ?>' class='hidden loader' type='file' accept='application/pdf' name='AdministratorActions[conclusion]'></label></form>
                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="<?= $execution->username ?>"></label><label class='btn btn-default activator' data-toggle='tooltip' data-placement='auto' title='Добавить обследование'><span class='text-info glyphicon glyphicon-folder-close'></span><input id="addExecution" data-id='<?= $execution->username ?>' class='hidden loader' type='file' accept='application/zip' name='AdministratorActions[execution]'></label></form>
                <a class='btn btn-default activator' data-action='check-data'
                   data-id='<?= $execution->username ?>' data-toggle='tooltip' data-placement='auto'
                   title='Подтвердить загруженные данные'><span class='glyphicon glyphicon-refresh'></span></a>
            </td>
            <td data-conclusion="<?= $execution->username ?>"><?= ExecutionHandler::isConclusion($execution->username) ? "<span class='glyphicon glyphicon-ok text-success'></span>" : "<span class='glyphicon glyphicon-remove text-danger'></span>"?></td>
            <td data-execution="<?= $execution->username ?>"><?= ExecutionHandler::isExecution($execution->username) ? "<span class='glyphicon glyphicon-ok text-success'></span>" : "<span class='glyphicon glyphicon-remove text-danger'></span>"?></td>
            <td>
                <a class='btn btn-default activator' data-action='change-password'
                   data-id='<?= $execution->username ?>' data-toggle='tooltip' data-placement='auto'
                   title='Сменить пароль'><span class='text-info glyphicon glyphicon-retweet'></span></a>
                <a class='btn btn-default activator' data-action='delete' data-id='<?= $execution->username ?>'
                   data-toggle='tooltip' data-placement='auto' title='Удалить запись'><span
                            class='text-danger glyphicon glyphicon-trash'></span></a>
            </td>
        </tr>
        <?php
    }
    echo "</tbody></table>";
}

if($executionsCounter > 0){
    echo "<div class='col-sm-12'><h2 class='text-center'>Всего обследований: $executionsCounter</h2></div>";
}
else{
    echo "<div class='col-sm-12'><h2 class='text-center'>Обследований не зарегистрировано</div>";
}

echo "<div class='col-sm-12 text-center'>";

echo Html::beginForm(['/site/logout'], 'post')
    . Html::submitButton(
        'Выйти из учётной записи',
        ['class' => 'btn btn-default logout']
    )
    . Html::endForm();

echo "</div>";
?>

<label><textarea class="hidden" id="forPasswordCopy"></textarea></label>

