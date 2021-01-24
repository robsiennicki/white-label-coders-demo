<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use frontend\modules\user\models\User;
use Yii;
use yii\db\ActiveRecord;

class CateringWallet extends ActiveRecord
{
    const TYPE_ADD_ADMIN = 0;
    const TYPE_REMOVE_ADMIN = 1;
    const TYPE_REMOVE_CRON = 2;
    const TYPE_ADD_DIET_CHANGE = 3;
    const TYPE_ADD_DIET_CANCEL = 4;
    const TYPE_REMOVE_DIET_CHANGE = 5;
    const TYPE_REMOVE_CRON_REFUND = 6;
    const TYPE_REMOVE_DIET_BUY = 7;
    const TYPE_REMOVE_USER_WANT = 8;

    public static function tableName()
    {
        return '{{%wallets}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('cw.*')
            ->from(['cw'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
