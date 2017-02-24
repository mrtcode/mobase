<?php
/* @var $this yii\web\View */
$this->title = 'Dealers';

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Modal;
?>

<ul class="nav nav-tabs">
   <li role="presentation"><a href="<?= Url::toRoute('site/sims') ?>">Sims</a></li>
   <li role="presentation"><a href="<?= Url::toRoute('site/vouchers') ?>">Vouchers</a></li>
   <li role="presentation" class="active"><a href="<?= Url::toRoute('site/dealers') ?>">Dealers</a></li>
</ul>

<?php
$gridColumns = [
    [
        'attribute' => 'nr',
        'content' => function($model) {
           $url = Url::toRoute('site/editDealer', array('id' => $model->id));
           return "<a id=\"{$model->id}\" onclick=\"event.preventDefault();editDealer(this);return false;\" href=\"$url\">$model->nr</a>";
        }
            ],
            'name',
            /* [
              'attribute' => 'name',
              'content' => function($model) {
              $url = Url::toRoute('site/editDealer', array('id' => $model->id));
              return "<a id=\"{$model->id}\" onclick=\"event.preventDefault();editDealer(this);return false;\" href=\"$url\">$model->name</a>";
              }
              ] */
            'company_name',
            'address',
            'phone',
            'area',
            [
                'attribute' => 'comment',
                'content' => function($model) {

                   if (strlen($model->comment) > 45)
                      $comment = substr($model->comment, 0, 45) . '..';
                   else
                      $comment = $model->comment;

                   return $comment;
                }
            ]
        ];
        ?>


        <br>

        <div class="container-fluid">
           <div class="row">

              <div class="col-sm-1">

                 <button id="create-dealer" type="button" class="btn btn-primary">Create Dealer</button>
              </div>
           </div>

           <br>

           <div class="xcc dealers">
              <?php
              echo GridView::widget([
                  'dataProvider' => $dataProvider,
                  'filterModel' => $searchModel,
                  'columns' => $gridColumns,
                  'export' => false,
                  'pjax' => true,
                  'pjaxSettings' => [
                      'neverTimeout' => true
                  ],
                  'id' => 'grid1',
              ]);
              ?>
           </div>

           <button id="excel-btn" type="button" class="btn btn-default">Export to EXCEL</button>

           <?php
           Modal::begin([
               'id' => 'modal'
           ]);
           echo "<div id='modalContent'></div>";
           Modal::end();


           $this->registerJs('$(document).ready(ready)');
           ?>



           <script>



              function ready() {

                $('#create-dealer').click(function () {
                  $('#modal').modal('show')
                          .find('#modalContent')
                          .load('<?= Url::to(['site/dealercreate']); ?>');
                });


                $('#excel-btn').on('click', function (e) {
                  $('<form action="<?= Url::to(['site/getxlsx']); ?>" method="post" target="_blank"><input type="hidden" name="magic" value="1"><input type="hidden" name="field" value="1"><input type="hidden" name="table" value="dealer"></form>').appendTo('body').submit();
                });
              }


              function editDealer(d) {

                $('#modal').modal('show')
                        .find('#modalContent')
                        .load('<?= Url::to(['site/dealeredit']); ?>' + '?id=' + d.id);

      }
      function submitForm($form) {
        $.post(
                $form.attr("action"), // serialize Yii2 form
                $form.serialize()
                )
                .done(function (result) {
                  $form.parent().html(result.message);
                  $('#modal').modal('hide');
                  $.pjax.reload({container: '#grid1'});
                })
                .fail(function () {
                  console.log("server error");
                  $form.replaceWith('<button class="newType">Fail</button>').fadeOut()
                });
        return false;
      }



      function mod($form) {

        $('#modal').modal('hide');
        $.pjax.reload({container: '#grid1-pjax', timeout: 500000});

        return false;
      }

   </script>