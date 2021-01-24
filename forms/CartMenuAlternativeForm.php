<?php
namespace frontend\modules\catering\forms;

use common\components\validators\PhoneValidator;
use common\components\validators\PostcodeValidator;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\validators\Validator;

class CartMenuAlternativeForm extends Model
{
    public $cart_item_uuid;
    public $date;
    public $meal_type;
    public $meal_id;

    public function rules()
    {
        return [
            ['cart_item_uuid', 'required'],
            ['date', 'required'],
            ['meal_type', 'required'],
            ['meal_id', 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [

        ];
    }
}
