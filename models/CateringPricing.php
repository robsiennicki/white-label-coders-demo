<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringPricing extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_pricings}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('dprice.*')
            ->from(['dprice'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getPrices()
    {
        return $this->hasMany(CateringPricingPrices::class, ['diet_pricing_id'=>'id']);
    }
}
