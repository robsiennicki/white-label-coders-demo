<?php

namespace frontend\modules\catering\models;

use common\components\behaviors\DateTimeBehavior;
use common\components\helpers\Html;
use frontend\modules\catering\forms\AddressForm;
use frontend\modules\catering\forms\CustomerAddressForm;
use frontend\modules\catering\models\query\CateringDietQuery;
use frontend\modules\catering\services\Order;
use frontend\modules\catering\mails\OrderConfirmationMail;
use frontend\modules\catering\services\Param;
use frontend\modules\user\models\User;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\db\ActiveRecord;

class CateringOrder extends ActiveRecord
{
    const ADDRESS_DELIVERY_DEFAULT = 1;
    const ADDRESS_DELIVERY_WEEKEND = 2;
    const ADDRESS_DELIVERY_INVOICE = 3;

    const PAYMENT_TYPE_PRZELEWY24 = 1;
    const PAYMENT_TYPE_TRANSFER = 2;

    public static function tableName()
    {
        return '{{%diet_orders}}';
    }

    public static function find()
    {
        $model = parent::find();
        $model
            ->select('co.*')
            ->from(['co'=>self::tableName()]);
        return $model;
    }

    public function behaviors()
    {
        return [
            DateTimeBehavior::class,
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert===true) {
            if ($this->token===null) {
                $this->token = $this->id.'_'.Uuid::uuid4()->toString();
                $this->save();
            }
        }

        return parent::afterSave($insert, $changedAttributes);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getItems()
    {
        return $this->hasMany(CateringOrderItem::class, ['diet_order_id' => 'id']);
    }

    public function sendConfirmationMail()
    {
        return (new OrderConfirmationMail(['order'=>$this]))->send();
    }

    public function setAddress(int $type, AddressForm $formModel)
    {
        $suffix = '';
        $field = '';

        if ($type!==self::ADDRESS_DELIVERY_INVOICE) {
            $field = 'address';
            if ($type===self::ADDRESS_DELIVERY_WEEKEND) {
                $suffix = '2';
            }

            $coord = Order::getDeliveryCoord($formModel);

            $this->{'lat'.$suffix} = $coord->lat;
            $this->{'lon'.$suffix} = $coord->lng;

            $hour = CateringDeliveryHour::find()->andWhere(['id'=>$formModel->delivery_hour])->one();

            $this->{$field.$suffix.'-hours'} = $hour->value ?? null;
            $this->{$field.$suffix.'-code'} = $formModel->intercom_code;
            $this->{$field.$suffix.'-info'} = $formModel->delivery_notes;
        } else {
            $field = 'invoice';
        }

        $formModel->setAddress($formModel->address_id);

        $postcode = explode('-', $formModel->postcode);
        $this->{$field.$suffix.'-postal-1'} = $postcode[0];
        $this->{$field.$suffix.'-postal-2'} = $postcode[1];

        $this->{$field.$suffix.'-city'} = $formModel->city;
        $this->{$field.$suffix.'-street'} = $formModel->street;
        $this->{$field.$suffix.'-house'} = $formModel->building_nr;
        $this->{$field.$suffix.'-flat'} = $formModel->flat_nr;
    }

    public function divideWalletsBetweenItems(array $wallets)
    {
        $walletAdmin = $wallets['adminWallet'];
        $walletRefund = $wallets['refundWallet'];
        $adminWalletDate = $wallets['adminWalletDate'];

        if ($walletAdmin != 0 || $walletRefund != 0) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($this->items as $item) {
                    $walletTotal = $walletAdmin + $walletRefund;

                    if ($item->price >= $walletTotal) {
                        $item->wallet_admin = $walletAdmin;
                        $item->wallet_refund = $walletRefund;

                        $walletAdmin = 0;
                        $walletRefund = 0;
                    } else {
                        if ($walletAdmin >= $item->price) {
                            $item->wallet_admin = $item->price;
                            $walletAdmin = $walletAdmin - $item->price;
                        } else {
                            $item->wallet_admin = $walletAdmin;

                            if ($item->price - $walletAdmin <= $walletRefund) {
                                $refundWallet = $item->price - $walletAdmin;
                                $walletRefund -= $refundWallet;
                            } else {
                                $refundWallet = $walletRefund;
                                $walletRefund = 0;
                            }
                            $item->wallet_refund = $refundWallet;
                            $walletAdmin = 0;
                        }
                    }

                    $item->wallet_admin_date = $adminWalletDate->format('Y-m-d');

                    if (!$item->save()) {
                        throw new \Exception();
                    }

                    if ($walletAdmin == 0 && $walletRefund == 0) {
                        break;
                    }
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();

                throw $e;
            }
        }
    }
}
