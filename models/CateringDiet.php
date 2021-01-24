<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\models\query\CateringDietQuery;
use frontend\modules\catering\services\Cart;
use Yii;
use yii\db\ActiveRecord;

class CateringDiet extends ActiveRecord
{
    public $special;

    const JUICE_SHOT_PRICE = 7;

    public static function tableName()
    {
        return '{{%diet_diets}}';
    }

    public static function find()
    {
        $model = new CateringDietQuery(static::class);
        $model
            ->select('cd.*')
            ->from(['cd'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function afterFind()
    {
        parent::afterFind();

        if ($this->special_action_id && ($special = CateringDietSpecial::getById($this->special_action_id))) {
            $this->special = (object) $special;
        }
    }

    public function getDietFields()
    {
        return $this->hasMany(CateringDietField::class, ['diet_id'=>'id'])
            ->indexBy('field_id');
    }

    public function getDietCalories()
    {
        return $this->hasMany(CateringDietCalory::class, ['diet_id'=>'id'])
            ->orderBy('value ASC');
    }

    public function getDietMeals()
    {
        return $this->hasMany(CateringDietMeal::class, ['diet_id'=>'id']);
    }

    public function getDietPrices()
    {
        return $this->hasMany(CateringDietPrice::class, ['diet_id'=>'id']);
    }

    public function getDietChanges()
    {
        return $this->hasMany(CateringDietChanges::class, ['diet_id'=>'id']);
    }

    public function getDietPattern()
    {
        return $this->hasOne(CateringDietPattern::class, ['id'=>'diet_pattern_id']);
    }

    public function getThumbUrl()
    {
        $meta = $this->dietFields[CateringDietField::FIELD_IMAGE] ?? null;
        if (!$meta) {
            return;
        }

        $key = null;
        switch ($meta['type_int']) {
            case 3:
                $key = 'value_str';
                break;
        }

        return $key ? ($meta[$key] ?? null) : null;
    }

    public function getLongDesc()
    {
        $content = $this->long_desc;
        $content = preg_replace('/<(style)\b.*?>.*?<\/\1>/si', '', $content);
        $content = strip_tags($content);

        return $content;
    }

    public function getShortDesc($length = 250, $end=null)
    {
        $content = $this->getLongDesc();

        return mb_substr($content, 0, $length).(mb_strlen($content) > $length ? '...' . ($end ? ' '.$end : '') : '');
    }

    public function isNotAllowed(Cart $cart = null){
        return self::checkNotAllowed($this, $cart);
    }

    public static function checkNotAllowed(object $diet, Cart $cart = null){
        $notAllowed = false;
        if($diet->is_children && $cart && !$cart->items){
            $notAllowed = 'Dietę dziecięcą zamówisz tylko razem z dowolną inną dietą.';
        }

        return $notAllowed;
    }
}
