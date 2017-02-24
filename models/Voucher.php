<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Connection;

/**
 * This is the model class for table "voucher".
 *
 * @property integer $id
 * @property string $serial
 * @property double $preload_value
 * @property string $dealer
 * @property string $assign_date
 */
class Voucher extends \yii\db\ActiveRecord {

   public $range = false;

   /**
    * @inheritdoc
    */
   public static function tableName() {
      return 'voucher';
   }

   /**
    * @inheritdoc
    */
   public function rules() {
      return [
          // [['serial', 'preload_value', 'dealer', 'assign_date'], 'required'],
          [['preload_value'], 'number'],
          [['dealer_id'], 'integer'],
          [['assign_date'], 'safe'],
          [['serial'], 'string', 'max' => 32],
          [['comment'], 'string', 'max' => 255]
      ];
   }

   /**
    * @inheritdoc
    */
   public function attributeLabels() {
      return [
          'id' => 'ID',
          'serial' => 'Serial',
          'preload_value' => 'Value',
          'dealer_id' => 'Dealer',
          'assign_date' => 'Assign Date',
          'comment' => 'Comment',
      ];
   }

   public function search() {
      //die($this->range);

      if ($this->range)
         $query = Voucher::find()->where($this->range);
      else
         $query = Voucher::find();

      $dataProvider = new ActiveDataProvider([
          'query' => $query,
          'pagination' => [
              'pageSize' => 200,
          ],
      ]);
      return $dataProvider;
   }

   public function getDealer() {
      return $this->hasOne('app\models\Dealer', array('id' => 'dealer_id'));
   }

   static function assignCheck($dealerId, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "SELECT COUNT(id) FROM voucher WHERE $sqlRange AND dealer_id!=0";
      $n = Yii::$app->db->createCommand($sql)->queryColumn();
      return $n[0];
   }

   static function assign($dealerId, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "UPDATE voucher SET dealer_id=$dealerId, assign_date=NOW() WHERE $sqlRange AND dealer_id=0";
      $n = Yii::$app->db->createCommand($sql)->execute();
      return $n;
   }

   static function unassign($dealerId, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "UPDATE voucher SET dealer_id=0, assign_date=0 WHERE dealer_id=$dealerId AND $sqlRange";
      $n = Yii::$app->db->createCommand($sql)->execute();
      return $n;
   }

   static function comment($comment, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "UPDATE voucher SET comment=:comment WHERE $sqlRange";
      $n = Yii::$app->db->createCommand($sql, ['comment' => $comment])->execute();
      return $n;
   }

   static function exportArrayNumeric($sqlRange) {

      if (strlen($sqlRange) > 0)
         $sqlRange = ' WHERE ' . $sqlRange;

      $sql = "SELECT voucher.serial, voucher.preload_value AS value, dealer.id, dealer.name, dealer.company_name, voucher.assign_date FROM voucher LEFT JOIN dealer ON dealer.id=voucher.dealer_id" . $sqlRange;

      $reader = Yii::$app->db->createCommand($sql)->query();

      $reader->setFetchMode(\PDO::FETCH_NUM);

      return $reader;
   }

}
