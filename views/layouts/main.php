<?php


use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
   <head>
      <meta charset="<?= Yii::$app->charset ?>">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?= Html::csrfMetaTags() ?>
      <title><?= Html::encode($this->title) ?></title>
      <?php $this->head() ?>
   </head>
   <body>

      <?php $this->beginBody() ?>





      <div class="wrap">
         <?php
         NavBar::begin([
             'brandLabel' => 'My Company',
             'brandUrl' => Yii::$app->homeUrl,
             'options' => [
                 'class' => 'navbar-default',
             ],
         ]);
         echo Nav::widget([
             'options' => ['class' => 'navbar-nav navbar-right'],
             'items' => [
                 ['label' => 'Panel', 'url' => ['/site'], 'active'=>(Yii::$app->controller->id=='site')],
                 Yii::$app->user->isGuest ? '' : ['label' => 'My Account', 'url' => [Url::to('/user/account')]],
                 Yii::$app->user->isGuest ?
                         ['label' => 'Login', 'url' => ['/user/login']] :
                         ['label' => 'Logout (' . Yii::$app->user->identity->username . ')',
                     'url' => ['/user/logout'],
                     'linkOptions' => ['data-method' => 'post']],
             ],
         ]);
         NavBar::end();
         ?>

         <div class="container">

            <?= $content ?>
         </div>
      </div>



      <?php $this->endBody() ?>
   </body>
</html>
<?php $this->endPage() ?>
