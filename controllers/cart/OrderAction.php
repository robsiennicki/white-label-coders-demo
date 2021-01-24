<?php

namespace frontend\modules\catering\controllers\cart;

use frontend\modules\catering\forms\CartOrderForm;
use frontend\modules\catering\forms\CustomerAddressForm;
use frontend\modules\catering\forms\CustomerAddressInvoiceForm;
use frontend\modules\catering\forms\CustomerAddressWeekendForm;
use frontend\modules\catering\models\CateringDeliveryHour;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\services\Cart;
use frontend\modules\catering\services\Discount;
use frontend\modules\catering\services\Order;
use frontend\modules\user\forms\RegisterForm;
use frontend\modules\user\forms\LoginForm;
use yii\base\Action;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

class OrderAction extends Action
{
    public function run()
    {
        $cart = Cart::instance();
        $user = Yii::$app->user->identity ?? null;
        $discounts = Discount::checkCart($cart);

        $registerModel = new RegisterForm();
        $loginModel = new LoginForm();
        $addressDeliveryModel = new CustomerAddressForm();
        $addressDeliveryWeekendModel = new CustomerAddressWeekendForm();
        $addressInvoiceModel = new CustomerAddressInvoiceForm();
        $orderModel = new CartOrderForm();

        if ($user) {
            $orderModel->first_name = $user->name;
            $orderModel->last_name = $user->surname;
            $orderModel->email = $user->email;
            $orderModel->phone = $user->phonenumber;

            $addressDeliveryModel->postcode = $user->lifepostal;
            $addressDeliveryModel->city = $user->lifeplace;
            $addressDeliveryModel->building_nr = $user->lifehouse;
            $addressDeliveryModel->flat_nr = $user->lifeflat;
            $addressDeliveryModel->street = $user->lifestreet;
            $addressDeliveryModel->setAddress($discounts['summary']['params']['addresses'][0] ?? null);

            if ($user->invoice_nip) {
                $orderModel->custom_invoice_address = 1;

                $addressInvoiceModel->company = $user->invoice_company;
                $addressInvoiceModel->nip_nr = $user->invoice_nip;
                $addressInvoiceModel->postcode = $user->invoice_postal;
                $addressInvoiceModel->city = $user->invoice_city;
                $addressInvoiceModel->building_nr = $user->invoice_house;
                $addressInvoiceModel->flat_nr = $user->invoice_flat;
                $addressInvoiceModel->street = $user->invoice_street;
            }

            $cart->setUser($user);
        }

        if ($cart->getToPayPrice()<=0) {
            $orderModel->payment_type = CateringOrder::PAYMENT_TYPE_PRZELEWY24;
        }

        $deliveryHoursList = ArrayHelper::map(CateringDeliveryHour::find()->orderBy('value ASC')->all(), 'id', 'value');

        if (isset($_GET['delete']) && ($delete = $cart->getByUUID($_GET['delete']))) {
            $cart->delete($delete);
            return $this->controller->redirect(Url::current(['delete'=>null]));
        }

        $isValid = false;
        if (Yii::$app->request->isPost) {
            $registerModel->load(Yii::$app->request->post());
            $addressDeliveryModel->load(Yii::$app->request->post());
            $addressDeliveryWeekendModel->load(Yii::$app->request->post());
            $addressInvoiceModel->load(Yii::$app->request->post());
            $orderModel->load(Yii::$app->request->post());

            $ajaxValidation = [];
            $isValid = true;
            if (!$user) {
                $isValid = $registerModel->validate() && $isValid;
                $ajaxValidation[] = $registerModel;
            }
            $isValid = $orderModel->validate() && $isValid;
            $ajaxValidation[] = $orderModel;
            $isValid = $addressDeliveryModel->validate() && $isValid;
            $ajaxValidation[] = $addressDeliveryModel;
            if ($orderModel->custom_weekend_address) {
                $isValid = $addressDeliveryWeekendModel->validate() && $isValid;
                $ajaxValidation[] = $addressDeliveryWeekendModel;
            }
            if ($orderModel->is_vat && $orderModel->custom_invoice_address) {
                $isValid = $addressInvoiceModel->validate() && $isValid;
                $ajaxValidation[] = $addressInvoiceModel;
            }

            $cart
                ->setUser($user, $registerModel)
                ->setOrderOptions($orderModel)
                ->setDeliveryOptions($addressDeliveryModel)
                ->setDeliveryWeekendOptions($addressDeliveryWeekendModel)
                ->setInvoiceOptions($addressInvoiceModel);
        }

        if (Yii::$app->request->isAjax && isset($_POST['ajax']) && $_POST['ajax']==='cart-order-form' && $ajaxValidation) {
            $response = [];
            foreach ($ajaxValidation as $validation) {
                $response = array_merge($response, ActiveForm::validate($validation));
            }

            return Json::encode($response);
        }

        $isValid = $cart->validateItems() && $isValid;

        if (Yii::$app->request->isPost && $isValid) {
            try {
                $order = Order::createFromCart($cart);

                try {
                    $order->sendConfirmationMail();
                } catch (\Swift_SwiftException $e) {
                }

                if ($order->pay_type == CateringOrder::PAYMENT_TYPE_PRZELEWY24 && $order->status == 0) {
                    return $this->controller->redirect(['/catering/order/pay', 'token'=>$order->token]);
                }

                return $this->controller->redirect(['/catering/order/summary', 'token'=>$order->token]);
            } catch (\Exception $e) {
            }
        }

        return $this->controller->render('order', [
            'cart'=>$cart,

            'deliveryHoursList'=>$deliveryHoursList,

            'registerModel'=>$registerModel,
            'loginModel'=>$loginModel,
            'addressDeliveryModel'=>$addressDeliveryModel,
            'addressDeliveryWeekendModel'=>$addressDeliveryWeekendModel,
            'addressInvoiceModel'=>$addressInvoiceModel,
            'orderModel'=>$orderModel,
            'discounts'=>$discounts
        ]);
    }
}
