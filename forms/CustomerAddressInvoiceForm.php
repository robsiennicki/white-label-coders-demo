<?php
namespace frontend\modules\catering\forms;

use frontend\modules\shop\models\ShopCustomer;
use frontend\modules\shop\models\ShopLoyaltyCard;
use frontend\modules\user\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\validators\Validator;

class CustomerAddressInvoiceForm extends AddressForm
{
    public $company;
    public $nip_nr;

    public function rules()
    {
        $rules = parent::rules();

        foreach ($rules as $ruleIndex=>$rule) {
            if ($rule[1]==='required') {
                $rules[$ruleIndex]['whenClient'] = CartOrderForm::JS_VALIDATION_WHEN_IS_VAT_AND_CUSTOM_ADDRESS;
            }
        }

        return $rules;
    }
}
