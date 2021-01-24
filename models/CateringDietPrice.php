<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDietPrice extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_prices}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdp.*')
            ->from(['cdp'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }
}
