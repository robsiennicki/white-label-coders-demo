<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\forms\AddressForm;
use frontend\modules\catering\forms\CustomerAddressForm;
use frontend\modules\catering\models\query\CateringDietQuery;
use frontend\modules\catering\services\Order;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\db\ActiveRecord;

class CateringOrderItemWithout extends ActiveRecord
{
    const WITHOUT_BREAKFAST = 0;
    const WITHOUT_SUPPER = 1;

    public static function tableName()
    {
        return '{{%diet_order_withouts}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cow.*')
            ->from(['cow'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getOrderItem()
    {
        return $this->hasOne(CateringOrderItem::class, ['id'=>'diet_order_diet_id']);
    }

    public static function getOptionsList()
    {
        return [
            self::WITHOUT_BREAKFAST => 'Śniadania',
            self::WITHOUT_SUPPER => 'Kolacji'
        ];
    }
}
