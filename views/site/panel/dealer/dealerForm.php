<?php

/* @var $this yii\web\View */
$this->title = 'Sims';

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Button;

$form = ActiveForm::begin(['layout' => 'horizontal', 'action' => $scenario == 'edit' ? Url::to(['site/dealeredit']) : Url::to(['site/dealercreate']), 'enableAjaxValidation' => true, 'id' => 'dealer-form']);


$js = <<<JS
 
// get the form id and set the event
$('#dealer-form').on('beforeSubmit', function(e) {
   var form = $(this);
e.preventDefault(); 
mod(form);
return false;
   // do whatever here, see the parameter \$form? is a jQuery Element to your form
}).on('submit', function(e){
        var form = $(this);
         e.preventDefault(); 
//
        
  
        return false;
});
JS;
$this->registerJs($js);

if ($scenario == 'edit') {
   echo $form->field($model, 'id')->hiddenInput()->label('ID: ' . $model->id);
}

echo $form->field($model, 'nr');
echo $form->field($model, 'name');
echo $form->field($model, 'company_name');
echo $form->field($model, 'address');
echo $form->field($model, 'phone');
echo $form->field($model, 'area');
echo $form->field($model, 'comment')->textArea(['rows' => '4']);

echo Button::widget([
    'label' => $scenario == 'create' ? 'Create' : 'Save',
    'options' => ['class' => 'btn-lg', 'type' => 'submit'],
]);

ActiveForm::end();
?>
