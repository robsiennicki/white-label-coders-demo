<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use App\Helpers\ArrayHelper;
use App\Services\Params;
use frontend\modules\catering\forms\CustomerAddressForm;
use frontend\modules\catering\models\CateringDietPattern;
use frontend\modules\catering\models\CateringHoliday;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\models\CateringOrderItem;
use frontend\modules\catering\models\CateringOrderItemChange;
use frontend\modules\catering\models\CateringOrderItemDate;
use frontend\modules\catering\models\CateringOrderItemExtraDate;
use frontend\modules\catering\models\CateringOrderItemWithout;
use frontend\modules\catering\models\CateringWallet;
use Yii;
use yii\base\BaseObject;

class Order extends BaseObject
{
    public static $blockedDays = false;

    public static function getBlockedDays(): array
    {
        if (self::$blockedDays===false) {
            self::$blockedDays = CateringHoliday::find()
                ->indexBy('date')
                ->all();
        }

        return self::$blockedDays;
    }

    public static function isBlockedDay(\DateTime $date): bool
    {
        $blockedDays = self::getBlockedDays();

        return isset($blockedDays[$date->format('Y-m-d')]);
    }

    public static function getClosestAvailableDays(int $days = 1, bool $isWeekend = false, \DateTime $start = null, string $format = null, CateringDietPattern $pattern = null, \DateTime $end = null): array
    {
        $paramKeys = [
            1 => 'pon',
            2 => 'wt',
            3 => 'srd',
            4 => 'czw',
            5 => 'pt',
            6 => 'sob',
            7 => 'niedz',
        ];

        $closestBreakpoints = [];

        if($pattern && $pattern->params){
            $params = ArrayHelper::map($pattern->params, 'name', function ($item){
               return $item->type === 0 ? $item->valueDbl : $item->valueStr;
            }, function($item){
                return strpos($item->name, 'dieta-zw')!==false ? 'back' : 'order';
            });

            foreach($params as $type=>$items){
                $groupedItems = [];

                foreach ($items as $key=>$value){
                    foreach ($paramKeys as $day=>$search){
                        if(strpos($key, 'dieta-'.$search)!==false){
                            $groupedItems[$day][ strpos($key, 'godzina')!==false ? 0 : 1 ] = $value;
                        }
                    }
                }

                $params[$type] = $groupedItems;
            }

            $closestBreakpoints = $params['order'];
        }else{
            foreach ($paramKeys as $day=>$search){
                $closestBreakpoints[$day][0] = Param::get('dieta-'.$search.'-godzina');
                $closestBreakpoints[$day][1] = Param::get('dieta-'.$search.'-dni');
            }
        }

        $now = new \DateTime();
        $day = (int)$now->format('N');
        $hour = $now->format('H:i');
        $todayBreakpoint = $closestBreakpoints[$day];
        $nextBreakpoint = $closestBreakpoints[$day === 7 ? 1 : $day+1];
        $firstDate = $now->modify('+'.($todayBreakpoint[0] > $hour ? $todayBreakpoint[1]-1 : $nextBreakpoint[1]).' days');

        $end = $end ?? (new \DateTime())->modify('+90 days');
        $date = $start ?? new \DateTime();
        if($firstDate->format('Y-m-d') > $date->format('Y-m-d')){
            $date = $firstDate;
        }
        $dates = [];
        while (count($dates)<$days && $date->format('Y-m-d') < $end->format('Y-m-d')) {
            $date->modify('+1 day');

            if (!$isWeekend && in_array($date->format('N'), ['6','7'], true)) {
                continue;
            }

            if (self::isBlockedDay($date)) {
                continue;
            }

            $dates[] = $format ? $date->format($format) : clone $date;
        }

        return $dates;
    }

    public static function createFromCart(Cart $cart): CateringOrder
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $discounts = Discount::checkCart($cart);
            if ($discounts['summary']['errors']) {
                throw new \Exception();
            }

            $user = $cart->user ?? User::create($cart->userOptions);

            $order = new CateringOrder();

            $order->link('user', $user);
            $order->email = $cart->orderOptions->email ?: $user->username;
            $order->phone = $cart->orderOptions->phone ?: $user->phonenumber;
            $order->name = $cart->orderOptions->first_name ?: $user->name;
            $order->surname = $cart->orderOptions->last_name ?: $user->surname;

            $order->setAddress(CateringOrder::ADDRESS_DELIVERY_DEFAULT, $cart->deliveryOptions);
            if (!$cart->deliveryOptions->address_id && $cart->orderOptions->custom_weekend_address) {
                $order->setAddress(CateringOrder::ADDRESS_DELIVERY_WEEKEND, $cart->deliveryWeekendOptions);
            }
            if ($cart->orderOptions->is_vat) {
                $order->{'invoice-company'} = $cart->orderOptions->company;
                $order->{'invoice-nip'} = $cart->orderOptions->nip_nr;
                $order->setAddress(CateringOrder::ADDRESS_DELIVERY_INVOICE, $cart->orderOptions->custom_invoice_address ? $cart->invoiceOptions : $cart->deliveryOptions);
            }

            $order->topay = $cart->getTotalPrice();
            $order->discount = $discounts['items'] ? 1 : 0;
            $order->status = 0;
            $order->pay_type = $cart->orderOptions->payment_type;
            $order->wallet = 0;

            if (!$order->save()) {
                throw new \Exception();
            }

            /**
             * @var $cartItem CartItem
             */
            foreach ($cart->items as $cartItem) {
                $discount = $discounts['items'][$cartItem->uuid] ?? null;
                if($discount){
                    $discounts['items'][$cartItem->uuid]['details']->uses += $cartItem->options->count ?? 1;
                }

                for ($i = 0; $i < ($cartItem->options->count ?? 1); $i++) {
                    $orderItem = new CateringOrderItem();

                    $orderItem->price_day = $cartItem->getCalculation()->getDailyPrice();
                    $orderItem->kcal = $cartItem->diet->getDietCalories()->andWhere(['id'=>$cartItem->options->calory])->one()->value;
                    $orderItem->start = $cartItem->getStartDate()->format('Y-m-d');
                    $orderItem->stop = $cartItem->getEndDate()->format('Y-m-d');

                    $orderItem->package_price = $cartItem->getCalculation()->getPackagePrice();
                    $orderItem->package_price_day = $cartItem->getCalculation()->getDailyPackagePrice();
                    $orderItem->package_type = $cartItem->diet->allow_bio_package ? (int) $cartItem->options->eco_package : 0;

                    $orderItem->juice_shot_price = $cartItem->getCalculation()->getShotPrice();
                    $orderItem->juice_shot_price_day = $cartItem->getCalculation()->getDailyShotPrice();

                    $orderItem->tochange_start = $cartItem->getStartDate()->format('Y-m-d');
                    $orderItem->tochange_stop = $cartItem->getEndDate()->modify('+30 days')->format('Y-m-d');

                    $orderItem->comments = $cart->orderOptions->notes;
                    $orderItem->purchaser = $cartItem->options->labels[$i] ?? '';

                    $orderItem->price = $cartItem->getCalculation()->getPrice();

                    $orderItem->code = $discount['details']->value ?? '';

                    $orderItem->link('diet', $cartItem->diet);
                    $orderItem->link('order', $order);

                    foreach ($cartItem->getSelectedDays() as $day) {
                        $orderItemDate = new CateringOrderItemDate();
                        $orderItemDate->date = $day->format('Y-m-d');
                        $orderItemDate->link('orderItem', $orderItem);
                    }

                    $extraDays = self::getClosestAvailableDays($cartItem->getCalculation()->freeDays, (bool) $cartItem->options->days_weekend, $cartItem->getEndDate(), null, null, $cartItem->getEndDate()->modify('+1 month'));
                    foreach ($extraDays as $day) {
                        $orderItemExtraDate = new CateringOrderItemExtraDate();
                        $orderItemExtraDate->date = $day->format('Y-m-d');
                        $orderItemExtraDate->link('orderItem', $orderItem);
                    }

                    if ($cartItem->getDiet()->is_customizable && $cartItem->changes) {
                        foreach ($cartItem->changes as $change) {
                            $orderItemChange = new CateringOrderItemChange();
                            $orderItemChange->to_diet_id = $change['diet_id'];
                            $orderItemChange->meal_id = $change['meal_type'];
                            $orderItemChange->date = $change['date'];
                            $orderItemChange->link('diet', $cartItem->getDiet());
                            $orderItemChange->link('orderItem', $orderItem);
                        }
                    }

                    if ($cartItem->getDiet()->custom_quantity_meals && ($withoutDays = array_merge($cartItem->getSelectedDays(), $extraDays))) {
                        foreach ($withoutDays as $day) {
                            $orderItemWithout = new CateringOrderItemWithout();
                            $orderItemWithout->without = $cartItem->options->without ?? 0;
                            $orderItemWithout->date = $day->format('Y-m-d');
                            $orderItemWithout->is_modified = 0;
                            $orderItemWithout->link('orderItem', $orderItem);
                        }
                    }
                }
            }

            if ($user->wallet > 0) {
                $walletOnly = false;
                $walletPay = (float) $user->wallet;
                if ($user->wallet >= $cart->getTotalPrice()) {
                    $walletOnly = true;
                    $walletPay = $cart->getTotalPrice();
                    $order->status = 1;
                }

                $wallets = Wallet::remove($user, $walletPay);
                Wallet::add($user, CateringWallet::TYPE_REMOVE_DIET_BUY, $walletPay);

                $user->wallet -= $walletPay;
                $order->wallet = $walletPay;
                $order->wallet_admin = $wallets['adminWallet'];
                $order->wallet_refund = $wallets['refundWallet'];
                $order->divideWalletsBetweenItems($wallets);

                if (!$order->save()) {
                    throw new \Exception();
                }
            }

            $user->setDataFromOrder($order);

            if (!$user->save()) {
                throw new \Exception();
            }

            if(isset($discounts['items'])){
                foreach ($discounts['items'] as $discount){
                    if (!$discount['details']->save()) {
                        throw new \Exception();
                    }
                }
            }

            $transaction->commit();

            $cart->destroy();

            return $order;
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    public static function getDeliveryCoord(CustomerAddressForm $formModel): object
    {
        return GoogleMaps::getCoord($formModel->street . ' ' . $formModel->building_nr . ', ' . $formModel->city);
    }
}
