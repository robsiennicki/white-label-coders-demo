<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietChangesQuery;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDietChanges extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_changes}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdc.*')
            ->from(['cdc'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getDietMeal()
    {
        return $this->hasOne(CateringDietMeal::class, ['date'=>'date', 'meal_id'=>'meal_id', 'diet_id'=>'diet_id']);
    }

    public function getDiet()
    {
        return $this->hasOne(CateringDiet::class, ['id'=>'diet_id']);
    }
}
