<?php

use app\assets\AdminAsset;
use app\models\ExecutionHandler;
use app\models\User;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\web\View;

AdminAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Администрирование';

/* @var $this View */
/* @var $executions User[] */

// добавлю кнопку для создания нового обследования

echo "Your IP is " . $_SERVER['REMOTE_ADDR'] . "<br/>";

echo Html::button('Добавить обследование', ['class' => 'btn btn-info margin', 'id' => 'addExecution']);

if (!empty($executions)) {
    echo "<table class='table-hover table table-striped'><thead><tr><th>Номер обследования</th><th>Действия</th><th>Загружено заключение</th><th>Загружены файлы</th></tr></thead><tbody>";
    foreach ($executions as $execution) {
        ?>
        <tr>
            <td>
                <a class='btn-link' href='/person/<?= $execution->username ?>'><?= $execution->username ?></a>
            </td>
            <td>

                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="<?= $execution->username ?>"></label><label class='btn btn-default activator' data-toggle='tooltip' data-placement='auto' title='Добавить заключение'><span class='text-info glyphicon glyphicon-file'></span><input id="addConclusion" data-id='<?= $execution->username ?>' class='hidden loader' type='file' accept='application/pdf' name='AdministratorActions[conclusion]'></label></form>
                    <form class="inline"><label><input class="hidden" name="AdministratorActions[executionId]" value="<?= $execution->username ?>"></label><label class='btn btn-default activator' data-toggle='tooltip' data-placement='auto' title='Добавить обследование'><span class='text-info glyphicon glyphicon-folder-close'></span><input id="addExecution" data-id='<?= $execution->username ?>' class='hidden loader' type='file' accept='application/zip' name='AdministratorActions[execution]'></label></form>
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

echo Html::beginForm(['/site/logout'], 'post')
    . Html::submitButton(
        'Выйти из учётной записи',
        ['class' => 'btn btn-default logout']
    )
    . Html::endForm();

?>

<label><textarea class="hidden" id="forPasswordCopy"></textarea></label>

