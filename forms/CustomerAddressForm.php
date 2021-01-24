<?php
namespace frontend\modules\catering\forms;

use frontend\modules\catering\models\CateringCity;
use frontend\modules\catering\models\CateringDeliveryHour;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\shop\models\ShopCustomer;
use frontend\modules\shop\models\ShopLoyaltyCard;
use frontend\modules\user\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\validators\Validator;

class CustomerAddressForm extends AddressForm
{
    private $_address = false;

    public $delivery_hour;
    public $intercom_code;
    public $delivery_notes;

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['delivery_hour', 'required'],
            ['delivery_hour', 'exist', 'targetClass'=>CateringDeliveryHour::class, 'targetAttribute'=>'id'],

            ['intercom_code', 'safe'],
            ['delivery_notes', 'safe'],

            ['postcode', 'exist', 'targetClass'=>CateringCity::class, 'targetAttribute'=>'postal', 'filter'=>function ($query) {
                return $query->andWhere(['visible'=>1]);
            }, 'message'=>'Nieobsługiwany kod pocztowy'],
            ['street', 'validateAddress']
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'delivery_hour'=>Yii::t('frontend', 'Godzina dostawy'),
            'intercom_code'=>Yii::t('frontend', 'Kod domofonu'),
            'delivery_notes'=>Yii::t('frontend', 'Uwagi dla dostawcy'),
        ]);
    }

    public function validateAddress()
    {
        if (!$this->getAddress()) {
            $this->addError('street', 'Nieobsługiwany adres');
        }
    }

    public function getAddress()
    {
        if ($this->_address===false) {
            $this->_address = CateringCity::find()
                ->andWhere(['postal'=>$this->postcode, 'city'=>$this->city, 'visible'=>1])
                ->andWhere([
                    'OR',
                    ['street'=>addslashes($this->street)],
                    ['allow-street'=>1],
                ])
                ->one();
        }

        return $this->_address;
    }
}
