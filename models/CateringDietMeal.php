<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class CateringDietMeal extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_diet_meals}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdm.*')
            ->from(['cdm'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getMealType()
    {
        return $this->hasOne(CateringMealType::class, ['id'=>'meal_id']);
    }

    public function getMeal()
    {
        return $this->hasOne(CateringMeal::class, ['id'=>'dmeal_id']);
    }

    public function getDietChange()
    {
        return $this->hasOne(CateringDietChanges::class, ['date'=>'date', 'meal_id'=>'meal_id', 'diet_id'=>'diet_id'])
            ->andOnCondition(['on_change'=>1]);
    }

    public function getDietChanges()
    {
        return $this->hasMany(CateringDietChanges::class, ['date'=>'date', 'meal_id'=>'meal_id'])
            ->andOnCondition(['to_change'=>1]);
    }
}
