<?php
/* @var $this yii\web\View */
$this->title = 'Sims';

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Button;
use kartik\widgets\DatePicker;

$form = ActiveForm::begin(['layout' => 'horizontal', 'action' => $scenario == 'edit' ? Url::to(['site/simedit']) : Url::to(['site/']), 'enableAjaxValidation' => true, 'id' => 'table-form']);


$js = <<<JS
 
// get the form id and set the event
$('#table-form').on('beforeSubmit', function(e) {
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
        
          $(function () {
            $('#sim-assign_date').datetimepicker({
                format: 'YYYY-MM-DD HH:mm'
            });
        });
        
JS;
$this->registerJs($js);


//echo Html::label($model->iccid);
?>
<div class="form-group">
   <label style="padding-top: 0px;" class="control-label col-sm-3">ICCID</label>
   <div class="col-sm-6"><?php echo $model->iccid ?></div>
</div>

<div class="form-group">
   <label style="padding-top: 0px;" class="control-label col-sm-3">MSISDN</label>
   <div class="col-sm-6"><?php echo $model->msisdn ?></div>
</div>

<div class="form-group">
   <label style="padding-top: 0px;" class="control-label col-sm-3">Value</label>
   <div class="col-sm-6"><?php echo $model->preload_value ?></div>
</div>

<div class="form-group">
   <label style="padding-top: 0px;" class="control-label col-sm-3">Dealer</label>
   <div class="col-sm-6"><?php if ($model->dealer) echo $model->dealer->nr . '. ' . $model->dealer->name . ' (' . $model->dealer->company_name . ')'; ?></div>
</div>

<?php
echo $form->field($model, 'id')->hiddenInput();

if ($model->assign_date[0] != '0') {
   echo $form->field($model, 'assign_date');
} else {
   ?>
   <div class="form-group">
      <label style="padding-top: 0px;" class="control-label col-sm-3">Assign Date</label>
      <div class="col-sm-6"></div>
   </div>
   <?php
}



echo $form->field($model, 'comment')->textArea(['rows' => '4']);


echo Button::widget([
    'label' => $scenario == 'create' ? 'Create' : 'Save',
    'options' => ['class' => 'btn-lg', 'type' => 'submit'],
]);

ActiveForm::end();
?>
