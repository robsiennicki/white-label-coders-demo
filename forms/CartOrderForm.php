<?php
namespace frontend\modules\catering\forms;

use common\components\validators\NipNumberValidator;
use common\components\validators\PhoneValidator;
use common\components\validators\PostcodeValidator;
use frontend\modules\catering\models\CateringDeliveryHour;
use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\validators\Validator;

class CartOrderForm extends Model
{
    const JS_VALIDATION_WHEN_IS_VAT = 'function(){ return $(\'[name*="[is_vat]"][value="1"]:checked\').length; }';
    const JS_VALIDATION_WHEN_IS_VAT_AND_CUSTOM_ADDRESS = 'function(){ return $(\'[name*="[is_vat]"][value="1"]:checked\').length && $(\'[name*="[custom_invoice_address]"][value="1"]:checked\').length; }';
    const JS_VALIDATION_WHEN_IS_WEEKEND = 'function(){ return $(\'[name*="[custom_weekend_address]"][value="1"]:checked\').length; }';

    public $custom_weekend_address = 0;

    public $first_name;
    public $last_name;

    public $email;
    public $phone;

    public $notes;

    public $payment_type;

    public $is_vat = 0;
    public $custom_invoice_address = 0;

    public $rule_regulations;

    public $company;
    public $nip_nr;

    public function rules()
    {
        return [
            ['custom_weekend_address', 'safe'],

            ['first_name', 'required', 'when'=>function () {
                return Yii::$app->user->identity;
            }],
            ['last_name', 'required', 'when'=>function () {
                return Yii::$app->user->identity;
            }],

            ['email', 'required', 'when'=>function () {
                return Yii::$app->user->identity;
            }],
            ['email', 'email'],

            ['phone', 'required', 'when'=>function () {
                return Yii::$app->user->identity;
            }],
            ['phone', PhoneValidator::class],

            ['notes', 'safe'],

            ['payment_type', 'required', 'message'=>'Wybierz sposób płatności'],

            ['is_vat', 'safe'],
            ['custom_invoice_address', 'safe'],

            ['rule_regulations', 'required', 'message'=>'Zaakceptuj regulamin'],

            ['company', 'required', 'when'=>function () {
                return $this->is_vat;
            }, 'whenClient'=>self::JS_VALIDATION_WHEN_IS_VAT],
            ['nip_nr', 'required', 'when'=>function () {
                return $this->is_vat;
            }, 'whenClient'=>self::JS_VALIDATION_WHEN_IS_VAT],
            ['nip_nr', NipNumberValidator::class]
        ];
    }

    public function attributeLabels()
    {
        return [
            'notes'=>Yii::t('frontend', 'Uwagi do zamówienia'),

            'first_name'=>Yii::t('frontend', 'Imię'),
            'last_name'=>Yii::t('frontend', 'Nazwisko'),

            'payment_type'=>Yii::t('frontend', 'Sposób płatności'),

            'rule_regulations'=>Yii::t('frontend', 'Zapoznałem się i akceptuję regulamin'),

            'company'=>Yii::t('frontend', 'Nazwa firmy'),
            'nip_nr'=>Yii::t('frontend', 'NIP'),
        ];
    }
}
