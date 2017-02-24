<?php
/* @var $this yii\web\View */
$this->title = 'Vouchers';

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Modal;
use yii\base\View;
use kartik\widgets\Alert;
?>



<?php
$gridColumns = [

    /* [
      'content' => function($model) {
      if ($model->dealer_id != 0)
      return '<i class="glyphicon glyphicon-ban-circle red">';
      else
      return '<i class="glyphicon glyphicon-plus-sign green">';
      }
      ], */
    [
        'attribute' => 'serial',
        'content' => function($model) {
           $url = Url::toRoute('site/editvoucher', array('id' => $model->id));
           return "<a id=\"{$model->id}\" onclick=\"event.preventDefault();voucherEdit(this);return false;\" href=\"$url\">$model->serial</a>";
        }
            ],
            'preload_value',
            [
                'attribute' => 'dealer_id',
                'content' => function($model) {
                   if ($model->dealer_id) {
                      $name = $model->dealer->nr . '. ' . $model->dealer->name . ' (' . $model->dealer->company_name . ')';

                      return $name;
                   } else {
                      return '';
                   }
                }
            ],
            [
                'attribute' => 'assign_date',
                'content' => function($model) {
                   if ($model->assign_date[0] == 0)
                      return '';
                   else
                      return $model->assign_date;
                }
            ],
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
              <div class="col-sm-12">
                 <ul class="nav nav-tabs">
                    <li role="presentation"><a href="<?= Url::toRoute('site/sims') ?>">Sims</a></li>
                    <li role="presentation" class="active"><a href="<?= Url::toRoute('site/vouchers') ?>">Vouchers</a></li>
                    <li role="presentation"><a href="<?= Url::toRoute('site/dealers') ?>">Dealers</a></li>
                 </ul>
              </div>
           </div>

           <br>

           <div class="row">
              <div class="col-sm-7">
                 <div class="input-group">
                    <textarea id="magic-numbers" class="form-control" rows="3" placeholder="Please choose vouchers and click 'Select' OR CTRL+ENTER. Examples: '12000001-12004000,1200*000, 12000001, 12000002, 12000001' OR just paste a column from EXCEL"></textarea>           
                    <span class="input-group-addon" id="basic-addon1"><button id="magic-btn" type="button" class="btn btn-primary">Select</button></span>
                 </div>
                 <div id="magic-search-fields">
                    <label class="radio-inline">
                       <input type="radio" name="magic-field" id="inlineRadio1" value="serial" checked="checked"> by Serial
                    </label>
                    <label class="radio-inline">
                       <input type="radio" name="magic-field" id="inlineRadio2" value="dealer_id"> by Dealer nr.
                    </label>
                 </div>

              </div>

              <div class="col-sm-5">
                 <div class="input-group">
                    <span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
                    <input onClick="this.setSelectionRange(0, this.value.length)" value="" autocomplete="off" id="fast-dealer" type="text" class="form-control typeahead" placeholder="Just type dealer id or name" aria-describedby="basic-addon1">
                 </div>

                 <br>
                 <button id="assign-btn" type="button" class="btn btn-primary btn-success">Assign selected</button>
                 <button id="unassign-btn" type="button" class="btn btn-primary btn-danger">Unassign selected</button>
                 <br>
                 <a id="comment-menu-show" href="#">Comment menu [<span class="sym">+</span>]</a>
                 <br>
                 <div id="comment-menu" class="input-group hidden">
                    <textarea id="comment-text" class="form-control" rows="2" placeholder="Enter a comment"></textarea>           
                    <span class="input-group-addon" id="basic-addon2"><button id="comment-btn" type="button" class="btn btn-primary">Comment selected</button></span>
                 </div>
              </div>
           </div>

           <br>
           <div id="alerts"></div>
           <div class="xcc vouchers">
              <?php
              $gridOptions = [
                  'dataProvider' => $dataProvider,
                  'filterModel' => $searchModel,
                  'columns' => $gridColumns,
                  'export' => false,
                  'pjax' => true,
                  'pjaxSettings' => [
                      'neverTimeout' => true,
                      'afterGrid' => $message,
                  ],
                  'id' => 'grid1',
                  'striped' => false,
              ];

              $gridOptions['rowOptions'] = function ($model, $key, $index, $grid) {
                 $res = [];
                 if ($model->dealer_id != 0) {
                    $res['class'] = 'danger';
                 }
                 return $res;
              };

              echo GridView::widget($gridOptions);
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

              function voucherEdit(d) {

                $('#modal').modal('show')
                        .find('#modalContent')
                        .load('<?= Url::to(['site/voucheredit']); ?>' + '?id=' + d.id);

              }
              function voucherSubmit($form) {
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













              var dealer_id = 0;
              var dealer_input_selected = '';

              function ready() {

                $('#comment-menu-show').on('click', function () {

                  if ($('#comment-menu').hasClass('hidden')) {
                    $('#comment-menu').removeClass('hidden');
                    $('#comment-menu-show > .sym').html('-');
                  } else {
                    $('#comment-menu').addClass('hidden');
                    $('#comment-menu-show > .sym').html('+');
                  }
                });

                var dealers = new Bloodhound({
                  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                  queryTokenizer: Bloodhound.tokenizers.whitespace,
                  remote: {url: '<?= Url::toRoute('site/fastdealer') ?>?key=%QUERY', cache: false},
                });

                dealers.initialize();

                var myTypeahead = $('#fast-dealer').typeahead({
                  hint: true,
                  highlight: true,
                  minLength: 1
                }, {
                  displayKey: 'value',
                  source: dealers.ttAdapter()
                });

                myTypeahead.on('typeahead:selected', function (evt, data) {
                  dealer_id = data.id;
                  dealer_input_selected = data.value;
                });

                myTypeahead.on('typeahead:autocompleted', function (evt, data) {
                  dealer_id = data.id;
                  dealer_input_selected = data.value;
                });

                magicData = magicData = {
                  magic: $("#magic-numbers").val(),
                  field: $('input[name=magic-field]:checked', '#magic-search-fields').val()
                };

                function tableReload(old) {

                  if (!old) {
                    magicData = {
                      magic: $("#magic-numbers").val(),
                      field: $('input[name=magic-field]:checked', '#magic-search-fields').val()
                    };
                  }

                  $.pjax.reload({
                    container: '#grid1-pjax',
                    type: 'POST',
                    data: magicData,
                    //dataType: 'application/json',
                    timeout: 500000,
                  });

                }

                $('#magic-btn').on('click', function () {
                  tableReload();
                });

                $(document).on("keydown", "#magic-numbers", function (e)
                {
                  if ((e.keyCode == 10 || e.keyCode == 13) && e.ctrlKey)
                  {
                    tableReload();
                  }
                });

                $('#assign-btn').on('click', function (e) {
                  var data = magicData;
                  var dealer_input = $('#fast-dealer').val();

                  if (dealer_input != dealer_input_selected) {
                    dealer_id = 0;
                    $('#fast-dealer').val('');
                  }
                  data['dealer_id'] = dealer_id;
                  $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: '<?= Url::to(['site/voucherassign']); ?>',
                    data: magicData,
                    success: function (data) {
                      tableReload(true);
                      if (data.message) {
                        handleMessage(data.message)
                      }
                    }
                  });
                });

                $('#unassign-btn').on('click', function (e) {

                  var r = confirm("Are you absolutely sure that you want to UN-ASSIGN?");
                  if (r !== true)
                    return false;

                  var data = magicData;
                  data['dealer_id'] = dealer_id;
                  $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: '<?= Url::to(['site/voucherunassign']); ?>',
                    data: magicData,
                    success: function (data) {
                      tableReload(true);
                      if (data.message) {
                        handleMessage(data.message)
                      }
                    }
                  });
                });

                $('#comment-btn').on('click', function (e) {
                  var data = magicData;
                  var comment = $('#comment-text').val();

                  /*if (dealer_input != dealer_input_selected) {
                   dealer_id = 0;
                   $('#fast-dealer').val('');
                   }*/

                  data['comment'] = comment;
                  $.ajax({
                    type: "POST",
                    dataType: "json",
                    url: '<?= Url::to(['site/vouchercomment']); ?>',
                    data: magicData,
                    success: function (data) {
                      tableReload(true);
                      if (data.message) {
                        handleMessage(data.message)
                      }
                    }
                  });
                });

                $('#excel-btn').on('click', function (e) {
                  $('<form action="<?= Url::to(['site/getxlsx']); ?>" method="post" target="_blank"><input type="hidden" name="magic" value="' + magicData.magic + '"><input type="hidden" name="field" value="' + magicData.field + '"><input type="hidden" name="table" value="voucher"></form>').appendTo('body').submit();
        });

        $(document).on('pjax:beforeSend', function (event, xhr, settings) {
          /*console.log(xhr); // Do something with the data before its sent
           settings.type = "POST";
           settings.dataType = "json";
           settings.data = $.param({asdf: 'asdf'});
           settings.timeout = 500000;
           console.log(settings.data);*/
        });

        $(document).on('pjax:click', function (event, settings) {
          console.log(settings); // Do something with the data before its sent
          settings.type = "POST";
          settings.data = magicData;
          settings.timeout = 500000;
        });

        $(document).on('pjax:end', function (event, xhr, settings) {
          $("#magic-numbers").focus();
          //alert(settings.url); // Do something with the data before its sent
        });


        $("#alerts").bsAlerts({fade: 6000});
        $("#magic-numbers").focus();
      }

      function handleMessage(message) {
        $(document).trigger("add-alerts", {
          message: message.text,
          priority: message.type,
        });
      }


      function mod($form) {

        $('#modal').modal('hide');
        $.pjax.reload({container: '#grid1-pjax', timeout: 500000, type: 'POST', data: magicData});

        return false;
      }

   </script>