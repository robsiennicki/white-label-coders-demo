<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietChangesQuery;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDeliveryHour extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_hours}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdh.*')
            ->from(['cdh'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }
}
