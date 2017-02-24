<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\data\ActiveDataProvider;
use app\models\Sim;
use app\models\Voucher;
use app\models\Dealer;
use app\models\Selected;
use yii\helpers\Json;
use yii\web\Response;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use app\components\ExportData;
use app\components\XLSXWriter;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

class SiteController extends Controller {

   public function behaviors() {
      return [
          'access' => [
              'class' => AccessControl::className(),
              'rules' => [
                  [
                      'allow' => true,
                      'roles' => ['@'],
                  ],
              ],
          ],
          'verbs' => [
              'class' => VerbFilter::className(),
              'actions' => [
                  'logout' => ['post'],
              ],
          ],
      ];
   }

   public function actions() {
      return [
          'error' => [
              'class' => 'yii\web\ErrorAction',
          ],
          'captcha' => [
              'class' => 'yii\captcha\CaptchaAction',
              'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
          ],
      ];
   }

   public function beforeAction($action) {
      $this->enableCsrfValidation = ($action->id !== "getxlsx"); // <-- here
      return parent::beforeAction($action);
   }

   public function actionIndex() {
      return $this->actionSims();
   }

   public function actionGetxlsx() {
      ini_set('max_execution_time', 600);
      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');
      $table = Yii::$app->getRequest()->post('table');
      if (($magic && $field)) {
         $sql_range = Yii::$app->tools->rangeString($magic, $field);
      } else
         $sql_range = '';


      $writer = WriterFactory::create(Type::XLSX);
      $writer->openToBrowser($table . '-' . date('Y-m-d_H.i.s') . ".xlsx"); // stream data directly to the browser


      switch ($table) {
         case 'sim': $reader = Sim::exportArrayNumeric($sql_range);
            $writer->addRow(['ICCID', 'MSISDN', 'Value', 'Dealer Nr.', 'Dealer Name', 'Dealer Company', 'Assign Date', 'Comment']);
            break;
         case 'voucher': $reader = Voucher::exportArrayNumeric($sql_range);
            $writer->addRow(['Serial', 'Value', 'Dealer Nr.', 'Dealer Name', 'Dealer Company', 'Assign Date', 'Comment']);

            break;
         case 'dealer': $reader = Dealer::exportArrayNumeric($sql_range);
            $writer->addRow(['Dealer Nr', 'Name', 'Company', 'Address', 'Phone', 'Area', 'Comment']);

            break;
      }



      while ($row = $reader->read()) {
         switch ($table) {
            case 'sim':
               if ($row[6][0] == '0')
                  $row[6] = '';
               break;
            case 'voucher':
               if ($row[5][0] == '0')
                  $row[5] = '';
               break;
            case 'dealer':
               break;
         }
         $writer->addRow($row);
      }

      //$writer->addRows($multipleRows); // add multiple rows at a time


      $writer->close();




      /* Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        $headers = Yii::$app->response->headers;

        $filename = $table . '-' . date('Y-m-d') . '-' . time() . ".xlsx";
        header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        ini_set('max_execution_time', 300);
        $writer = new XLSXWriter();

        $writer->setAuthor('My Company');

        $writer->writeSheetRow('sim', ['1', '2', '3', '4', '5']);

        while ($row = $reader->read()) {
        $writer->writeSheetRow('sim', $row);
        }

        //$writer->finalizeSheet('sim');
        //$writer->sheet['sim']->
        // $fh = fopen('php://output', 'w');
        // fwrite($fh, 'asdfasdfasdf');

        $file=$filename = tempnam(sys_get_temp_dir(), "xxxx");
        $writer->writeToFile($file);

        $fp = fopen($file, 'rb');
        fpassthru($fp);

        //
        //return $writer->writeToString(); */
      return '';
   }

   public function actionStats() {
      Yii::$app->response->format = 'json';

      $simCount = Sim::find()->count();
      $simUsedCount = Sim::find()->where('dealer_id>0')->count();
      $selectedCount = Sim::find()->where(['selected_user_id' => 1])->count();

      return [
          'sim_count' => $simCount,
          'sim_used_count' => $simUsedCount,
          'selected_count' => $selectedCount,
      ];
   }

   public function actionSims() {

      $message = false;

      $searchModel = new Sim;

      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');
      if ($magic && $field) {
         $sql_range = Yii::$app->tools->rangeString($magic, $field);
         if (is_string($sql_range))
            $searchModel->range = $sql_range;
      }

      $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

      if ($message)
         $message = '<script>handleMessage({type:"' . $message['type'] . '", text:"' . htmlentities($message['text']) . '"})</script>';


      return $this->render('panel/sim/sims', [
                  'dataProvider' => $dataProvider,
                  'searchModel' => $searchModel,
                  'message' => $message
      ]);
   }

   public function actionSimassign() {

      $message = [];
      $n = 0;
      Yii::$app->response->format = 'json';

      $dealerId = Yii::$app->getRequest()->post('dealer_id');
      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');

      if ($dealerId && $magic && $field) {
         $sqlRange = Yii::$app->tools->rangeString($magic, $field);

         if (($n = Sim::assignCheck($dealerId, $sqlRange)) > 0) {
            $message = [
                'type' => 'error',
                'text' => "There are already assigned items ($n). Please unassign them or select a different interval."
            ];
         } else {

            $n = Sim::assign($dealerId, $sqlRange);
            if ($n > 0) {
               $message = [
                   'type' => 'info',
                   'text' => "Items assigned: $n"
               ];
            } else {
               $message = [
                   'type' => 'warning',
                   'text' => "No items assigned."
               ];
            }
         }
      } else {
         $message = [
             'type' => 'warning',
             'text' => "Please select items and dealer"
         ];
      }

      return [
          'message' => $message
      ];
   }

   public function actionSimunassign() {

      $message = [];
      $n = 0;
      Yii::$app->response->format = 'json';

      $dealerId = Yii::$app->getRequest()->post('dealer_id');
      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');

      if ($dealerId && $magic && $field) {
         $sqlRange = Yii::$app->tools->rangeString($magic, $field);
         $n = Sim::unassign($dealerId, $sqlRange);
         if ($n > 0) {
            $message = [
                'type' => 'info',
                'text' => "Items unassigned: $n"
            ];
         } else {
            $message = [
                'type' => 'warning',
                'text' => "No items unassigned."
            ];
         }
      } else {
         $message = [
             'type' => 'warning',
             'text' => "Please select items and dealer"
         ];
      }

      return [
          'message' => $message
      ];
   }

   public function actionSimcomment() {

      $message = [];
      $n = 0;
      Yii::$app->response->format = 'json';


      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');
      $comment = Yii::$app->getRequest()->post('comment');

      if ( $magic && $field) {
         $sqlRange = Yii::$app->tools->rangeString($magic, $field);


         $n = Sim::comment($comment, $sqlRange);
         if ($n > 0) {
            $message = [
                'type' => 'info',
                'text' => "Comments set: $n"
            ];
         } else {
            $message = [
                'type' => 'warning',
                'text' => "No comments set."
            ];
         }
      } else {
         $message = [
             'type' => 'warning',
             'text' => "Please select items"
         ];
      }

      return [
          'message' => $message
      ];
   }

   public function actionSimedit() {

      /* $model = new Dealer;
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($model->validate()) {
        if ($model->update()) {

        }
        }

        return ActiveForm::validate($model);
        } */

      if (isset($_POST['Sim'])) {
         $model = new Sim;
         if ($model = Sim::findOne($_POST['Sim']['id'])) {
            unset($_POST['Sim']['id']);
            $model->attributes = $_POST['Sim'];
            $success = false;
            if ($model->validate()) {
               if ($model->update())
                  $success = true;
            }
         }

         Yii::$app->response->format = Response::FORMAT_JSON;

         return ActiveForm::validate($model);
      } else {
         $id = Yii::$app->getRequest()->getQueryParam('id');
         $model = Sim::findOne($id);
      }

      //$model->scenario = 'edit';
      // either the page is initially displayed or there is some validation error
      //return $this->render('panel/dealer/dealerForm', ['model' => $model]);
      return $this->renderAjax('panel/sim/simForm', [
                  'model' => $model,
                  'scenario' => 'edit',
      ]);
   }

   public function actionVouchers() {

      $message = false;

      $searchModel = new Voucher;

      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');
      if ($magic && $field) {
         $sql_range = Yii::$app->tools->rangeString($magic, $field);
         $searchModel->range = $sql_range;
      }

      $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

      if ($message)
         $message = '<script>handleMessage({type:"' . $message['type'] . '", text:"' . htmlentities($message['text']) . '"})</script>';


      return $this->render('panel/voucher/vouchers', [
                  'dataProvider' => $dataProvider,
                  'searchModel' => $searchModel,
                  'message' => $message
      ]);
   }

   public function actionVoucherassign() {

      $message = [];
      $n = 0;
      Yii::$app->response->format = 'json';

      $dealerId = Yii::$app->getRequest()->post('dealer_id');
      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');

      if ($dealerId && $magic && $field) {
         $sqlRange = Yii::$app->tools->rangeString($magic, $field);

         if (($n = Voucher::assignCheck($dealerId, $sqlRange)) > 0) {
            $message = [
                'type' => 'error',
                'text' => "There are already assigned items ($n). Please unassign them or select a different interval."
            ];
         } else {

            $n = Voucher::assign($dealerId, $sqlRange);
            if ($n > 0) {
               $message = [
                   'type' => 'info',
                   'text' => "Items assigned: $n"
               ];
            } else {
               $message = [
                   'type' => 'warning',
                   'text' => "No items assigned."
               ];
            }
         }
      } else {
         $message = [
             'type' => 'warning',
             'text' => "Please select items and dealer"
         ];
      }

      return [
          'message' => $message
      ];
   }

   public function actionVoucherunassign() {

      $message = [];
      $n = 0;
      Yii::$app->response->format = 'json';

      $dealerId = Yii::$app->getRequest()->post('dealer_id');
      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');

      if ($dealerId && $magic && $field) {
         $sqlRange = Yii::$app->tools->rangeString($magic, $field);
         $n = Voucher::unassign($dealerId, $sqlRange);
         if ($n > 0) {
            $message = [
                'type' => 'info',
                'text' => "Items unassigned: $n"
            ];
         } else {
            $message = [
                'type' => 'warning',
                'text' => "No items unassigned."
            ];
         }
      } else {
         $message = [
             'type' => 'warning',
             'text' => "Please select items and dealer"
         ];
      }

      return [
          'message' => $message
      ];
   }
   
   public function actionVouchercomment() {

      $message = [];
      $n = 0;
      Yii::$app->response->format = 'json';


      $magic = Yii::$app->getRequest()->post('magic');
      $field = Yii::$app->getRequest()->post('field');
      $comment = Yii::$app->getRequest()->post('comment');

      if ($magic && $field) {
         $sqlRange = Yii::$app->tools->rangeString($magic, $field);


         $n = Voucher::comment($comment, $sqlRange);
         if ($n > 0) {
            $message = [
                'type' => 'info',
                'text' => "Comments set: $n"
            ];
         } else {
            $message = [
                'type' => 'warning',
                'text' => "No comments set."
            ];
         }
      } else {
         $message = [
             'type' => 'warning',
             'text' => "Please select items"
         ];
      }

      return [
          'message' => $message
      ];
   }

   public function actionVoucheredit() {

      /* $model = new Dealer;
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($model->validate()) {
        if ($model->update()) {

        }
        }

        return ActiveForm::validate($model);
        } */

      if (isset($_POST['Voucher'])) {

         $model = new Voucher;
         if ($model = Voucher::findOne($_POST['Voucher']['id'])) {
            unset($_POST['Voucher']['id']);
            $model->attributes = $_POST['Voucher'];
            $success = false;
            if ($model->validate()) {
               if ($model->update())
                  $success = true;
            }
         }
         return 0;
         Yii::$app->response->format = Response::FORMAT_JSON;

         return ActiveForm::validate($model);
      } else {
         $id = Yii::$app->getRequest()->getQueryParam('id');
         $model = Voucher::findOne($id);
      }

      //$model->scenario = 'edit';
      // either the page is initially displayed or there is some validation error
      //return $this->render('panel/dealer/dealerForm', ['model' => $model]);
      return $this->renderAjax('panel/voucher/voucherForm', [
                  'model' => $model,
                  'scenario' => 'edit',
      ]);
   }

   public function actionDealers() {
      // your default model and dataProvider generated by gii
      $searchModel = new Dealer;
      $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());


      // non-ajax - render the grid by default
      return $this->render('panel/dealer/dealers', [
                  'dataProvider' => $dataProvider,
                  'searchModel' => $searchModel
      ]);
   }

   public function actionDealercreate() {
      $model = new Dealer;
      //$model->scenario = 'create';
      /* $error = false;
        if ($model->load(Yii::$app->request->post())) {
        if ($model->validate()) {
        if ($model->save())
        $error = true;
        }
        } else {
        // either the page is initially displayed or there is some validation error
        //return $this->render('panel/dealer/dealerForm', ['model' => $model]);
        return $this->renderAjax('panel/dealer/dealerForm', [
        'model' => $model,
        ]);
        } */

      //$model = new Category;

      if ($model->load(Yii::$app->request->post())) {
         if ($model->validate()) {
            if ($model->save()) {
               $model->refresh();
               /* Yii::$app->response->format = 'json';
                 return [
                 'message' => 'Success!!!',
                 ]; */
            }
         }
         Yii::$app->response->format = Response::FORMAT_JSON;

         return ActiveForm::validate($model);
      }

      $model->nr = Dealer::getNr();

      return $this->renderAjax('panel/dealer/dealerForm', [
                  'model' => $model,
                  'scenario' => 'create',
      ]);
   }

   public function actionDealeredit() {

      /* $model = new Dealer;
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($model->validate()) {
        if ($model->update()) {

        }
        }

        return ActiveForm::validate($model);
        } */

      if (isset($_POST['Dealer'])) {
         $model = new Dealer;
         if ($model = Dealer::findOne($_POST['Dealer']['id'])) {
            unset($_POST['Dealer']['id']);
            $model->attributes = $_POST['Dealer'];
            $success = false;
            if ($model->validate()) {
               if ($model->update())
                  $success = true;
            }
         }

         Yii::$app->response->format = Response::FORMAT_JSON;

         return ActiveForm::validate($model);
      } else {
         $id = Yii::$app->getRequest()->getQueryParam('id');
         $model = Dealer::findOne($id);
      }

      //$model->scenario = 'edit';
      // either the page is initially displayed or there is some validation error
      //return $this->render('panel/dealer/dealerForm', ['model' => $model]);
      return $this->renderAjax('panel/dealer/dealerForm', [
                  'model' => $model,
                  'scenario' => 'edit',
      ]);
   }

   public function actionLogin() {
      if (!\Yii::$app->user->isGuest) {
         return $this->goHome();
      }

      $model = new LoginForm();
      if ($model->load(Yii::$app->request->post()) && $model->login()) {
         return $this->goBack();
      } else {
         return $this->render('login', [
                     'model' => $model,
         ]);
      }
   }

   public function actionFastdealer() {

      $key = Yii::$app->getRequest()->getQueryParam('key');

      $res = [];
      $kws = explode(' ', $key);

      if (is_numeric($kws[0])) {
         $model = Dealer::findOne($kws[0]);
         if ($model) {
            $res[] = ['id' => $model->id, 'value' => $model->id . '. ' . $model->name . ' (' . $model->company_name . ')'];
         }
      } else {
         foreach ($kws as $kw) {
            if (!is_numeric($kw)) {
               $models = Dealer::find()
                       ->where('name LIKE :query OR company_name LIKE :query2')
                       ->addParams([':query' => '%' . $kw . '%', ':query2' => '%' . $kw . '%'])
                       ->all();
               foreach ($models as $model) {
                  $res[] = ['id' => $model->id, 'value' => $model->id . '. ' . $model->name . ' (' . $model->company_name . ')'];
               }
            }
         }
      }



      Yii::$app->response->format = 'json';
      return $res;
   }

   public function actionLogout() {
      Yii::$app->user->logout();

      return $this->goHome();
   }

}
