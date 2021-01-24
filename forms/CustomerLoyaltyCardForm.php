<?php
namespace frontend\modules\shop\forms;

use frontend\modules\shop\models\ShopCustomer;
use frontend\modules\shop\models\ShopLoyaltyCard;
use frontend\modules\user\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\validators\Validator;

class CustomerLoyaltyCardForm extends Model
{
    public $code;

    public function rules()
    {
        return [
            ['code', 'required'],
            ['code', 'validateCode']
        ];
    }

    public function attributeLabels()
    {
        return [
            'code'=>Yii::t('frontend', 'numer karty')
        ];
    }

    public function validateCode($attribute)
    {
        $card = ShopLoyaltyCard::find()->andWhere(['code'=>$this->code])->one();
        if (!$card) {
            $this->addError($attribute, 'Błędny numer karty');
        }

        $isUsed = $card->assignedCustomer;
        if ($isUsed) {
            $this->addError($attribute, 'Karta jest już przypisana do innego klienta');
        }
    }
}
