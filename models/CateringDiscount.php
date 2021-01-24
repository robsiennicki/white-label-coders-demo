<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDiscount extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_discounts}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdis.*')
            ->from(['cdis'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getAddress()
    {
        return $this->hasOne(CateringAddress::class, ['id'=>'address_id']);
    }

    public function getCities()
    {
        return $this->hasMany(CateringCity::class, ['id'=>'city_id'])
            ->via('citiesPivot');
    }

    public function getCitiesPivot()
    {
        return $this->hasMany(CateringDiscountToCity::class, ['diet_discount_id'=>'id']);
    }

    public function getRegions()
    {
        return $this->hasMany(CateringRegion::class, ['id'=>'diet_region_id'])
            ->via('regionsPivot');
    }

    public function getRegionsPivot()
    {
        return $this->hasMany(CateringDiscountToRegion::class, ['diet_discount_id'=>'id']);
    }
}
