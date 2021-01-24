<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDietField extends ActiveRecord
{
    const FIELD_IMAGE = 2;

    public static function tableName()
    {
        return '{{%diet_diet_fields}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdf.*')
            ->from(['cdf'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }
}
