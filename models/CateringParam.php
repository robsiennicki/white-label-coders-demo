<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringParam extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%params}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cp.*')
            ->from(['cp'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }
}
