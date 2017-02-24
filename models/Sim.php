<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use app\models\Dealer;
use yii\db\Connection;

/**
 * This is the model class for table "sim".
 *
 * @property integer $id
 * @property string $iccid
 * @property string $msisdn
 * @property double $preload_value
 * @property string $dealer
 * @property string $assign_date
 */
class Sim extends \yii\db\ActiveRecord {

   public $selected = false;
   public $range = false;

   /**
    * @inheritdoc
    */
   public static function tableName() {
      return 'sim';
   }

   /**
    * @inheritdoc
    */
   public function rules() {
      return [
          // [['iccid', 'msisdn', 'preload_value', 'dealer', 'assign_date'], 'required'],
          [['preload_value'], 'number'],
          [['dealer_id'], 'integer'],
          [['assign_date'], 'safe'],
          [['iccid'], 'string', 'max' => 32],
          [['msisdn'], 'string', 'max' => 16],
          [['comment'], 'string', 'max' => 255]
      ];
   }

   /**
    * @inheritdoc
    */
   public function attributeLabels() {
      return [
          'id' => 'ID',
          'iccid' => 'ICCID',
          'msisdn' => 'MSISDN',
          'preload_value' => 'Value',
          'dealer_id' => 'Dealer',
          'assign_date' => 'Assign Date',
          'selected_user_id' => 'Who Selected',
          'comment' => 'Comment',
      ];
   }

   public function search() {
      //die($this->range);

      if ($this->range)
         $query = Sim::find()->where($this->range);
      else
         $query = Sim::find();

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

   public function getUser() {
      return \amnah\yii2\user\models\User::findIdentity($this->selected_user_id);
   }

   static function assignCheck($dealerId, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "SELECT COUNT(id) FROM sim WHERE $sqlRange AND dealer_id!=0";
      $n = Yii::$app->db->createCommand($sql)->queryColumn();
      return $n[0];
   }

   static function assign($dealerId, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "UPDATE sim SET dealer_id=$dealerId, assign_date=NOW() WHERE $sqlRange AND dealer_id=0";
      $n = Yii::$app->db->createCommand($sql)->execute();
      return $n;
   }

   static function unassign($dealerId, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "UPDATE sim SET dealer_id=0, assign_date=0 WHERE dealer_id=$dealerId AND $sqlRange";
      $n = Yii::$app->db->createCommand($sql)->execute();
      return $n;
   }

   static function comment($comment, $sqlRange) {
      $user_id = Yii::$app->user->identity->id;
      $sql = "UPDATE sim SET comment=:comment WHERE $sqlRange";
      $n = Yii::$app->db->createCommand($sql, ['comment' => $comment])->execute();
      return $n;
   }

   static function exportArrayNumeric($sqlRange) {

      if (strlen($sqlRange) > 0)
         $sqlRange = ' WHERE ' . $sqlRange;

      $sql = "SELECT sim.iccid, sim.msisdn, sim.preload_value AS value, dealer.id, dealer.name, dealer.company_name, sim.assign_date, sim.comment FROM sim LEFT JOIN dealer ON dealer.id=sim.dealer_id" . $sqlRange;

      $reader = Yii::$app->db->createCommand($sql)->query();

      $reader->setFetchMode(\PDO::FETCH_NUM);

      return $reader;
   }

}
