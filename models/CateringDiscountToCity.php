<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDiscountToCity extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_discounts_to_cities}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdtc.*')
            ->from(['cdtc'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }
}
