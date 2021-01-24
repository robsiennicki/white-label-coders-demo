<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringPricingPrices extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_pricing_prices}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('dpi.*')
            ->from(['dpi'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getPricing()
    {
        return $this->hasOne(CateringPricing::class, ['id'=>'diet_pricing_id']);
    }
}
