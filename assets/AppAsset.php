<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle {

   public $basePath = '@webroot';
   public $baseUrl = '@web';
   public $css = [
       'css/site.css',
       'css/bootstrap-datepicker.css',
       'css/bootstrap-datetimepicker.css',
   ];
   public $js = [
       'js/my.js',
       'js/typeahead.jquery.js',
       'js/jquery.bsAlerts.min.js',
       'js/moment.js',
       'js/bootstrap-datepicker.js',
       'js/bootstrap-datetimepicker.js',
   ];
   public $depends = [
       'yii\web\YiiAsset',
       'yii\bootstrap\BootstrapAsset',
   ];

}
