<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\forms\AddressForm;
use frontend\modules\catering\forms\CustomerAddressForm;
use frontend\modules\catering\models\query\CateringDietQuery;
use frontend\modules\catering\services\Order;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\db\ActiveRecord;

class CateringOrderItem extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_order_diets}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('coi.*')
            ->from(['coi'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getDiet()
    {
        return $this->hasOne(CateringDiet::class, ['id'=>'diet_id']);
    }

    public function getOrder()
    {
        return $this->hasOne(CateringOrder::class, ['id'=>'diet_order_id']);
    }

    public function getDates()
    {
        return $this->hasMany(CateringOrderItemDate::class, ['diet_order_diet_id'=>'id']);
    }

    public function getExtraDates()
    {
        return $this->hasMany(CateringOrderItemExtraDate::class, ['diet_order_diet_id'=>'id']);
    }

    public function getWithouts()
    {
        return $this->hasMany(CateringOrderItemWithout::class, ['diet_order_diet_id'=>'id']);
    }

    public function getChanges()
    {
        return $this->hasMany(CateringOrderItemChange::class, ['diet_order_diet_id'=>'id']);
    }
}
