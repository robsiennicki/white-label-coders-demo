<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDietPatternParam extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_pattern_params}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdpattp.*')
            ->from(['cdpattp'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getPattern()
    {
        return $this->hasOne(CateringDietPattern::class, ['id'=>'diet_pattern_id']);
    }
}
