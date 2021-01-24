<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringRegionCity extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_regions_cities}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('crc.*')
            ->from(['crc'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getRegion()
    {
        return $this->hasOne(CateringRegion::class, ['id'=>'diet_region_id']);
    }

    public function getCity()
    {
        return $this->hasOne(CateringCity::class, ['id'=>'city_id']);
    }
}
