<?php

namespace app\models;

use yii\data\ActiveDataProvider;
use Yii;

/**
 * This is the model class for table "dealer".
 *
 * @property string $id
 * @property string $name
 * @property string $company_name
 * @property string $address
 * @property string $phone
 * @property string $area
 */
class Dealer extends \yii\db\ActiveRecord {

   /**
    * @inheritdoc
    */
   public static function tableName() {
      return 'dealer';
   }

   /**
    * @inheritdoc
    */
   public function rules() {
      return [

          [['nr', 'name',], 'required'],
          [['name', 'company_name', 'area'], 'string', 'max' => 255],
          [['nr'], 'integer'],
          [['nr'], 'unique'],
          [['address'], 'string', 'max' => 512],
          [['phone'], 'string', 'max' => 32],
          [['comment'], 'string', 'max' => 255]
      ];
   }

   /**
    * @inheritdoc
    */
   public function attributeLabels() {
      return [
          'id' => 'ID',
          'nr' => 'Nr',
          'name' => 'Name',
          'company_name' => 'Company',
          'address' => 'Address',
          'phone' => 'Phone',
          'area' => 'Area',
          'comment' => 'Comment',
      ];
   }

   /*
     public function search() {
     $dataProvider = new ActiveDataProvider([
     'query' => Dealer::find(),
     'pagination' => [
     'pageSize' => 100,
     ],
     ]);
     return $dataProvider;
     }
    */

   public function search($params) {
      $query = Dealer::find();


      $dataProvider = new ActiveDataProvider([
          'query' => $query,
          'pagination' => [
              'pageSize' => 500,
          ],
      ]);

      /**
       * Setup your sorting attributes
       * Note: This is setup before the $this->load($params)
       * statement below
       */
      if (!($this->load($params))) {
         return $dataProvider;
      }

      $query->andFilterWhere([
          'id' => $this->id,
      ]);


      $query->andFilterWhere(['like', 'nr', $this->nr]);
      $query->andFilterWhere(['like', 'name', $this->name]);
      $query->andFilterWhere(['like', 'company_name', $this->company_name]);
      $query->andFilterWhere(['like', 'address', $this->address]);
      $query->andFilterWhere(['like', 'phone', $this->phone]);
      $query->andFilterWhere(['like', 'area', $this->area]);
      $query->andFilterWhere(['like', 'comment', $this->comment]);

      //

      return $dataProvider;
   }

   static function getNr() {
      $sql = "SELECT MAX(nr) FROM dealer";
      return Yii::$app->db->createCommand($sql)->queryColumn()[0] + 1;
   }

   static function exportArrayNumeric($sqlRange) {
      $columns = ['id', 'name', 'company_name', 'address', 'phone', 'area'];

      $query = (new \yii\db\Query())->select($columns)->from('dealer');
      //if ($sqlRange != '')
      //   $query->where($sqlRange);
      $command = $query->createCommand();

      $reader = $command->query();

      $reader->setFetchMode(\PDO::FETCH_NUM);

      return $reader;
   }

}
