<?php

use app\assets\AdminAsset;
use app\models\ExecutionHandler;
use app\models\User;
use app\models\Utils;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

AdminAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Администрирование';

/* @var $this View */
/* @var $executions User[] */

$centers = ['all' => '', 'nv' => '', 'aurora' => ''];

$days = ['all' => '', 'today' => '', 'yesterday' => ''];

$sort = ['byTime' => '', 'byNumber' => '', 'byExecutions' => '', 'byConclusion' => ''];

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
$sortBy = Yii::$app->session['sortBy'];
    foreach ($sort as $key => $value) {
        if ($key === $sortBy) {
            $sort[$key] = 'selected';
        } else {
            $sort[$key] = '';
        }
    }
echo '<div class="col-xs-12 text-center margin">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="darkSwitch">
            <label class="custom-control-label" for="darkSwitch">Ночной режим</label>
          </div>
          <div class="pull-right"><a href="' . Url::toRoute('site/management') . '"><span class="glyphicon glyphicon-cog"></span></a></div>
        </div>';

// добавлю кнопку для создания нового обследования
echo "<div class='col-sm-12 text-center'>";

$form = ActiveForm::begin(['id' => 'addPatientForm', 'options' => ['class' => 'form-horizontal bg-default', 'enctype' => 'multipart/form-data'], 'enableAjaxValidation' => false, 'validateOnSubmit'  => false, 'action' => ['/execution/add']]);

try {
    echo $form->field($model, 'executionNumber', ['template' =>
        '<div class="col-sm-offset-4 col-sm-4"><div class="input-group">{input}<span class="input-group-btn"><button type="submit" class="btn btn-success">Добавить пациента</button></span></div>{error}{hint}</div>','inputOptions' =>
        ['class' => 'form-control', 'tabindex' => '1']])
        ->textInput(['autocomplete' => 'off', 'focus' => true])
        ->hint('Номер обследования пациента')
        ->label('Номер обследования');
} catch (Exception $e) {
}

ActiveForm::end();

echo "</div><div class='col-sm-12'>";

echo Html::beginForm(['/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8'], 'post');

echo "<div class='col-sm-4'><label class='control-label' for='#centerSelect'>Центр</label><select id='centerSelect' name='center' onchange='this.form.submit();' class='form-control'><option value='all'>Все</option><option value='nv' {$centers['nv']}>Нижневолжская набережная</option><option value='aurora' {$centers['aurora']}>Аврора</option></select></div>";

echo "<div class='col-sm-4'><label class='control-label' for='#centerSelect'>Время</label><select name='timeInterval' onchange='this.form.submit();' class='form-control'><option value='all'>Всё время</option><option value='today' {$days['today']}>Сегодня</option><option value='yesterday' {$days['yesterday']}>Вчера</option></select></div>";

echo "<div class='col-sm-4'><label class='control-label' for='#sortBy'>Сортировать по</label><select name='sortBy' onchange='this.form.submit();' class='form-control'><option value='byTime' {$sort['byTime']}>Времени добавления</option><option value='byNumber' {$sort['byNumber']}>Номеру обследования</option><option value='byConclusion' {$sort['byConclusion']}>Наличию заключения</option><option value='byExecutions' {$sort['byExecutions']}>Наличию файлов</option></select></div>";

echo Html::endForm();

echo '</div>';

// тут список нераспознанных папок

echo "
    <div id='unhandledFoldersContainer' class='col-sm-12 hidden margin'>
        <h2 class='text-center text-danger'><span class='glyphicon glyphicon-warning-sign'></span> Найдены неопознанные папки <span class='glyphicon glyphicon-warning-sign'></span></h2>
        <h3 class='text-danger text-center'>Удалите или назовите папки правильно!</h3>
        <table class='table-hover table'><thead><tr><th>Имя папки</th><th>Действия</th></tr></thead><tbody id='unhandledFoldersList'></tbody></table>
    </div>
";

// тут список папок, ожидающих обработки

echo "
    <div id='waitingFoldersContainer' class='col-sm-12 hidden margin'>
        <h2 class='text-center text-info'><span class='glyphicon glyphicon-info-sign'></span> Файлы данных обследований ожидают обработки <span class='glyphicon glyphicon-info-sign'></span></h2>
        <h3 class='text-info text-center'>Они появятся в результатах обследования через некоторое время</h3>
        <ul id='waitingFoldersList'></ul>
    </div>
";

echo "
    <div class='col-sm-12 margin'><div class='col-sm-4 text-center'>Всего обследований: <b class='text-info'><span id='patientsCount'>0</span></b></div><div class='col-sm-4 text-center'>Без заключений: <b class='text-danger'><span id='withoutConclusions'>0</span></b><br/><a target='_blank' href='" . Url::toRoute('administrator/print-missed-conclusions-list') . "' class='btn btn-default'><span class='text-info'>Распечатать список</span></a></div><div class='col-sm-4 text-danger text-center'>Без файлов: <b class='text-danger'><span id='withoutExecutions'>0</span></b></div></div>
";

$executionsCounter = 0;

if (!empty($executions)) {
    echo "<table class='table-hover table'><thead><tr><th>Номер обследования</th><th>Действия</th><th>Загружено заключение</th><th>Загружены файлы</th></tr></thead><tbody id='executionsBody'>";
    foreach ($executions as $execution) {
        // проверю, если включена фильтрация по центру- выведу только те обследования, которые проведены в этом центре
        if(Utils::isCenterFiltered() && Utils::isFiltered($execution)) {
            continue;
        }
            ++ $executionsCounter;
        ?>
        <tr class="patient" data-id="<?= $execution->username?>">
            <td>
                <a class='btn-link execution-id' href='/person/<?= $execution->username ?>'><?= $execution->username ?></a>
                <span class="pull-right"><?=Utils::showDate($execution->created_at)?></span>
            </td>
            <td>

                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="<?= $execution->username ?>"></label><label class='btn btn-default activator' data-toggle='tooltip' data-placement='auto' title='Добавить заключение'><span class='text-info glyphicon glyphicon-file'></span><input data-id='<?= $execution->username ?>' multiple="multiple" class='hidden loader addConclusion' type='file' accept='application/pdf' name='AdministratorActions[conclusion][]'></label></form>
                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="<?= $execution->username ?>"></label><label class='btn btn-default activator' data-toggle='tooltip' data-placement='auto' title='Добавить обследование'><span class='text-info glyphicon glyphicon-folder-close'></span><input data-id='<?= $execution->username ?>' class='hidden loader addExecution' type='file' accept='application/zip' name='AdministratorActions[execution]'></label></form>
            </td>
            <?= ExecutionHandler::isConclusion($execution->username) ? "<td data-conclusion='$execution->username' class='field-success'><span class='glyphicon glyphicon-ok text-success status-icon'></span></td>" : "<td data-conclusion='$execution->username' class='field-danger'><span class='glyphicon glyphicon-remove text-danger status-icon'></span></td>"?>
            <?= ExecutionHandler::isExecution($execution->username) ?  "<td data-execution='$execution->username' class='field-success'><span class='glyphicon glyphicon-ok text-success status-icon'></span></td>" : "<td data-execution='$execution->username' class='field-danger'><span class='glyphicon glyphicon-remove text-danger status-icon'></span></td>"?>
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
    echo '</tbody></table>';
}
if($executionsCounter === 0){
    echo "<div class='col-sm-12'><h2 class='text-center'>Обследований не зарегистрировано</div>";
}

echo "<div class='col-sm-12 text-center'>";

echo '</div>';
?>

<label><textarea class="hidden" id="forPasswordCopy"></textarea></label>

