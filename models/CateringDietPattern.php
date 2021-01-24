<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDietPattern extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_patterns}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdpatt.*')
            ->from(['cdpatt'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getParams()
    {
        return $this->hasMany(CateringDietPatternParam::class, ['diet_pattern_id'=>'id']);
    }
}
