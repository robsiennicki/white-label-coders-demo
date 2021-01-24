<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietChangesQuery;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringHoliday extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%holidays}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('ch.*')
            ->from(['ch'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }
}
