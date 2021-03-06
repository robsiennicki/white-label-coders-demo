<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringDietCalory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_diet_kcals}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cdcal.*')
            ->from(['cdcal'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getDiet()
    {
        return $this->hasOne(CateringDiet::class, ['id'=>'diet_id']);
    }
}
