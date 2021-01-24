<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use Yii;
use yii\db\ActiveRecord;

class CateringAddress extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%diet_addresses}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('ca.*')
            ->from(['ca'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getFullAdress(){
        return $this->postal_1.'-'.$this->postal_2.' '.$this->city.', '.$this->street.' '.$this->house.($this->flat ? '/'.$this->flat : '');
    }
}
