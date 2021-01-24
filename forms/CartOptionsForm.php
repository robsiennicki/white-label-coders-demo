<?php
namespace frontend\modules\catering\forms;

use common\components\validators\PhoneValidator;
use common\components\validators\PostcodeValidator;
use frontend\modules\catering\models\CateringDiscount;
use frontend\modules\catering\services\Cart;
use frontend\modules\catering\services\CartItem;
use frontend\modules\catering\services\Discount;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\validators\Validator;

class CartOptionsForm extends Model
{
    public $count = 1;
    public $calory;
    public $without = 0;

    public $days_period;
    public $days_start;
    public $days_count;
    public $days_weekend = 1;
    public $days_selected;

    public $labels;
    public $eco_package;
    public $juice_shot;

    public $discount_code;

    public function rules()
    {
        return [
            ['count', 'required', 'message'=>'Podaj liczbę osób'],
            ['calory', 'required', 'message'=>'Wybierz kaloryczność diety'],
            ['without', 'safe'],

            ['days_start', 'safe'],
            ['days_period', 'safe'],
            ['days_count', 'required', 'when'=>function (self $model) {
                return $model->days_period === -1;
            }, 'whenClient'=>"function(attribute, value){ return $('[name*=\"[days_period]\"]').val()==-1; }"],
            ['days_weekend', 'safe'],
            ['days_selected', 'required', 'message'=>'Wybierz dni'],

            ['labels', 'required', 'whenClient'=>"function(attribute, value){ var id = parseInt(attribute.name.split('[')[1].replace(']', '')); var count = parseInt($('[name*=\"[count]\"]').val()); return count>id; }"],
            ['labels', 'labelsCount'],
            ['eco_package', 'safe'],
            ['juice_shot', 'safe'],

            ['discount_code', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'count'=>Yii::t('frontend', 'Dla ilu osób?'),
            'calory'=>Yii::t('frontend', 'Kaloryczność diety'),

            'days_period'=>Yii::t('frontend', 'Ile dni'),
            'days_count'=>Yii::t('frontend', 'Ile dni'),
            'days_selected'=>Yii::t('frontend', 'Wybrane dni'),

            'labels'=>Yii::t('frontend', 'Imię/nick'),
            'eco_package'=>Yii::t('frontend', 'Ekologiczne opakowanie'),
            'juice_shot'=>Yii::t('frontend', 'Shot Odporność'),
        ];
    }

    public function labelsCount($attr)
    {
        if (count($this->labels)<$this->count) {
            $this->addError($attr, 'Podaj wszystkie imiona');
        }
    }

    public function getSelectedDays()
    {
        return explode(',', $this->days_selected);
    }
}
