<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

use app\assets\LoginAsset;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

LoginAsset::register($this);

$this->title = "РДЦ, личный кабинет";

?>
<div class="site-login text-center">

    <div id="ourLogo"></div>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'layout' => 'horizontal',
        'fieldConfig' => [
            'labelOptions' => ['class' => 'control-label'],
        ],
    ]); ?>

        <?= $form->field($model, 'username', ['template' => "<div class='col-sm-12 col-lg-offset-4 col-lg-4'>{label}</div><div class='col-sm-11 col-lg-offset-4 col-lg-4'>{input} </div><div class='col-lg-1'><button tabindex='-1' type='button' class='btn btn-info' id='loginHint'><span class='glyphicon glyphicon-question-sign'></span></button></div><div class='col-sm-12'>{error}</div>",])->textInput(['autofocus' => true,]) ?>

        <?= $form->field($model, 'password', ['template' => "<div class='col-sm-12 col-lg-offset-4 col-lg-4'>{label}</div><div class='col-sm-11 col-lg-offset-4 col-lg-4'>{input} </div><div class='col-lg-1'><button tabindex='-1' type='button' class='btn btn-info'><span class='glyphicon glyphicon-question-sign'></span></button></div><div class='col-sm-12'>{error}</div>",])->passwordInput() ?>

        <div class="form-group">
            <div class="col-sm-12 text-center">
                <?= Html::submitButton('Вход', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            </div>
        </div>
        <div><a href="tel:+78312020200" class="btn btn-default"><span class="glyphicon glyphicon-earphone text-success"></span><span class="text-success"> +7(831)20-20-200</span></a></div>
    <?php ActiveForm::end(); ?>

</div>
