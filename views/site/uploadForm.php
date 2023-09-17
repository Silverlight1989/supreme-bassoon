<?php
use yii\widgets\ActiveForm;
$form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);
echo $form->field($model, 'file')->fileInput() ?>

<button>Отправить</button>

<?php ActiveForm::end() ?>