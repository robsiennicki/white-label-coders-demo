<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use AuthComponent;
use City;
use DateTime;
use DietOrderDiet;
use DietRegion;
use DietRegionCity;
use frontend\modules\catering\models\CateringCity;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringDiscount;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\models\CateringOrderItem;
use frontend\modules\catering\models\CateringPricingPrices;
use frontend\modules\catering\models\CateringRegionCity;
use frontend\modules\site\helpers\BH;
use Yii;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

class Discount extends BaseObject
{
    public static function getCodeInfo(CateringDiscount $discountModel): ?array
    {
        if (!$discountModel) {
            return null;
        }

        $infos = [];
        if($address = $discountModel->address){
            $infos[] = Yii::t('frontend', 'Adres odbioru został zdefiniowany jako {address}', [
                'address' => $address->getFullAdress(),
            ]);
        }

        return $infos;
    }

    public static function checkCart(Cart $cart = null): ?array
    {
        return self::checkCartItems(null, $cart);
    }

    public static function checkCartItems(CartItem $cartItem = null, Cart $cart = null): ?array
    {
        $response = [];
        $params = [
            'addresses'=>[],
            'uses'=>[],
        ];

        $items = $cart->items ?? [];
        if($cartItem){
            $items[] = $cartItem;
        }

        if ($items) {
            foreach ($items as $item) {
                $discount = self::checkCartItem($item, $cart, $params);
                if (!$discount) {
                    continue;
                }

                if($discount['details']->address && !isset($params['addresses'][$discount['details']->address_id])){
                    $params['addresses'][$discount['details']->address_id] = $discount['details']->address;
                }

                isset($params['uses'][$discount['details']->primaryKey]) ? $params['uses'][$discount['details']->primaryKey]++ : $params['uses'][$discount['details']->primaryKey] = 1;

                $response['items'][$item->uuid] = $discount;
                $response['summary']['errors'] = array_merge($response['summary']['errors'] ?? [], $discount['errors']);
                $response['summary']['infos'] = array_merge($response['summary']['infos'] ?? [], $discount['infos']);
            }
        }

        $response['summary']['params'] = [
            'addresses' => array_values($params['addresses'])
        ];

        if($cartItem){
            return $response['items'][$cartItem->uuid];
        }

        return $response;
    }

    public static function checkCartItem(CartItem $cartItem, Cart $cart = null, array $params = []): ?array
    {
        $discount = [
            'details'=>null,
            'errors'=>[],
            'infos'=>[],
        ];

        $code = preg_replace('#[\xC2\xA0]#', '', $cartItem->options->discount_code);
        $code = preg_replace('/\s+/', '', $code);

        if (!$code) {
            return null;
        }

        $discountModel = CateringDiscount::find()
            ->andWhere(['value'=>$code])
            ->andWhere(['<=', 'start', date('Y-m-d')])
            ->andWhere([
                'OR',
                ['stop'=>'0000-00-00'],
                ['>=', 'stop', date('Y-m-d')]
            ])
            ->orderBy('id DESC')
            ->one();

        if (!$discountModel) {
            return self::_validationError($discount, 'Kod rabatowy nie istnieje');
        }

        $discount['details'] = $discountModel;

        $discount['infos'] = self::getCodeInfo($discountModel);

        $isCartItem = $cart->getByUUID($cartItem->uuid) ? 1 : 0;
        $selectedDays = count($cartItem->getSelectedDays());
        $cartItemsCount = count($cart->items) + ($isCartItem ? 0 : 1);

        if (($address = $discountModel->address) && !isset($params['addresses'][$discountModel->address_id]) && count((array)$params['addresses']) > 0){
            return self::_validationError($discount, Yii::t('app', 'W jednym zamówieniu można użyć kodów rabatowych z takim samym zdefiniowanym adresem', [
                'address' => $address->getFullAdress()
            ]));
        }

        if ($discountModel->min_days>0 && $selectedDays<$discountModel->min_days) {
            return self::_validationError($discount, Yii::t('app', 'Minimalna ilość dni diety dla podanego kodu rabatowego wynosi: {min_days}', [
                'min_days' => $discountModel->min_days
            ]));
        }

        if ($discountModel->max_diet_in_cart>0 && $cartItemsCount>$discountModel->max_diet_in_cart) {
            return self::_validationError($discount, Yii::t('app', 'Kod rabatowy z maksymalną ilością diet w koszyku: {max_diet_in_cart}', [
                'max_diet_in_cart' => $discountModel->max_diet_in_cart
            ]));
        }

        if ($discountModel->max_uses_per_user>0 || $discountModel->register_days_ago_from || $discountModel->register_days_ago_to) {
            if (!Yii::$app->user->identity) {
                return self::_validationError($discount, Yii::t('app', 'Kod rabatowy dostępny tylko dla zalogowanych użytkowników'));
            }

            if ($discountModel->register_days_ago_from || $discountModel->register_days_ago_to) {
                $registerDate = new DateTime(Yii::$app->user->identity->created);
                $now = new DateTime();
                $diff = $registerDate->diff($now)->format('%a');

                if ($discountModel->register_days_ago_from && $discountModel->register_days_ago_from>$diff) {
                    return self::_validationError($discount, Yii::t('app', 'Kod rabatowy dostępny tylko w trakcie akcji specjalnych'));
                }

                if ($discountModel->register_days_ago_to && $discountModel->register_days_ago_to<$diff) {
                    return self::_validationError($discount, Yii::t('app', 'Kod rabatowy dostępny tylko w trakcie akcji specjalnych'));
                }
            }

            if ($discountModel->max_uses_per_user>0) {
                $discountCount = CateringOrderItem::find()
                    ->joinWith('order', false)
                    ->andWhere(['code'=>$code, 'user_id'=>Yii::$app->user->id])
                    ->groupBy('co.id')
                    ->count();

                if ($discountCount>=$discountModel->max_uses_per_user) {
                    return self::_validationError($discount, Yii::t('app', 'Kod rabatowy został już użyty'));
                }
            }
        }

        if ($isCartItem && $cart->deliveryOptions && $cart->deliveryOptions->postcode) {
            if ($cities = $discountModel->cities) {
                $exists = CateringCity::find()
                    ->andWhere(['city'=>ArrayHelper::getColumn($cities, 'id'), 'postal'=>$cart->deliveryOptions->postcode])
                    ->exists();
                if (!$exists) {
                    return self::_validationError($discount, Yii::t('app', 'Kod rabatowy jest ograniczony terytorialnie. Posiłki można zamówić pod adres: {places}', [
                        'places' => implode(', ', ArrayHelper::getColumn($cities, 'city'))
                    ]));
                }
            }

            if ($regions = $discountModel->regions) {
                $exists = CateringRegionCity::find()
                    ->joinWith('region', false)
                    ->joinWith('city', false)
                    ->andWhere(['cr.id'=>ArrayHelper::getColumn($regions, 'id'), 'cc.postal'=>$cart->deliveryOptions->postcode])
                    ->exists();
                if (!$exists) {
                    return self::_validationError($discount, Yii::t('app', 'Kod rabatowy jest ograniczony terytorialnie. Posiłki można zamówić pod adres: {places}', [
                        'places' => implode(', ', ArrayHelper::getColumn($regions, 'name'))
                    ]));
                }
            }
        }

        return $discount;
    }

    private static function _validationError(array $response, ?string $error): array
    {
        $response['errors'][] = is_array($error) ? $error : [
            'error' => $error
        ];

        return $response;
    }

    public static function applyToCalculation(Calculation $calculation){
        $discount = self::checkCartItems($calculation->cartItem, $calculation->cartItem->cart);
        if(!$discount && $discount['errors']){
            return;
        }

        $freeDays = $calculation->freeDays;
        $dailyPackagePrice = $calculation->dailyPackagePrice;
        $dailyJuiceShot = $calculation->dailyShotPrice;
        $dailyDiscount = $calculation->dailyDiscount;
        $dailyDietPrice = $calculation->dailyDietPrice;
        $dailyDietPriceWithDiscount = $dailyDietPrice - $dailyDiscount;

        $discount = $discount['details'];

        if($discount->extra_days > 0 && $calculation->days >= $discount->min_days){
            $freeDays = $discount->extra_days;
        }

        if ($discount->type == 4) {
            $dailyDiscount += $dailyDietPriceWithDiscount - ($dailyDietPriceWithDiscount * (100 - $discount->percentage) / 100);
        }elseif ($discount->type == 2 || $discount->type == 3) {
            $prices = CateringPricingPrices::find()
                ->andWhere(['diet_pricing_id'=>$discount->diet_pricing_id, 'kcal'=>$calculation->getCalory()->value, 'diet_id'=>$calculation->diet->primaryKey])
                ->all();
            if($prices){
                $priceMax = 0;
                $priceMin = 0;
                foreach ($prices as $price) {
                    if ($priceMax < $price->price) {
                        $priceMax = $price->price;
                    }

                    if ($calculation->days <= $price->days) {
                        $priceMin = $price->price;
                    }
                }

                if($dailyDietPriceWithDiscount > $priceMin){
                    $dailyDiscount = $dailyDietPrice - $priceMin;
                }
            }
        }

        $calculation->freeDays = $freeDays;
        $calculation->dailyPackagePrice = $dailyPackagePrice;
        $calculation->dailyShotPrice = $dailyJuiceShot;
        $calculation->dailyDietPrice = $dailyDietPrice;
        $calculation->dailyDiscount = $dailyDiscount;
    }
}
