<?php
namespace frontend\modules\catering\forms;

use common\components\validators\PostcodeValidator;
use frontend\modules\catering\models\CateringAddress;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\shop\models\ShopCustomer;
use frontend\modules\shop\models\ShopLoyaltyCard;
use frontend\modules\user\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\validators\Validator;

class AddressForm extends Model
{
    public $address_id;
    public $postcode;
    public $city;
    public $street;
    public $building_nr;
    public $flat_nr;

    public function rules()
    {
        return [
            ['address_id', 'safe'],
            ['address_id', 'exist', 'targetClass'=>CateringAddress::class, 'targetAttribute'=>'id'],
            ['postcode', 'required'],
            ['postcode', PostcodeValidator::class],
            ['city', 'required'],
            ['street', 'required'],
            ['building_nr', 'required'],
            ['flat_nr', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'address_id'=>Yii::t('frontend', 'Zdefiniowany adres'),
            'street'=>Yii::t('frontend', 'Ulica'),
            'building_nr'=>Yii::t('frontend', 'Nr domu'),
            'flat_nr'=>Yii::t('frontend', 'Nr lokalu'),
            'postcode'=>Yii::t('frontend', 'Kod pocztowy'),
            'city'=>Yii::t('frontend', 'Miejscowość'),
        ];
    }

    public function setAddress($address = null): void
    {
        if(!$address){
            return;
        }

        if(!($address instanceof CateringAddress)){
            $address = CateringAddress::findOne($address);
            if(!$address){
                return;
            }
        }

        $this->address_id = $address->primaryKey;
        $this->postcode = $address->postal_1.'-'.$address->postal_2;
        $this->city = $address->city;
        $this->street = $address->street;
        $this->building_nr = $address->house;
        $this->flat_nr = $address->flat;
    }
}
