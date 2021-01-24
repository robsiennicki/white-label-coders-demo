<?php

namespace frontend\modules\catering\controllers\cart;

use frontend\modules\catering\forms\CartOrderForm;
use frontend\modules\catering\forms\CustomerAddressForm;
use frontend\modules\catering\forms\CustomerAddressInvoiceForm;
use frontend\modules\catering\forms\CustomerAddressWeekendForm;
use frontend\modules\catering\models\CateringCity;
use frontend\modules\catering\models\CateringDeliveryHour;
use frontend\modules\catering\services\Cart;
use frontend\modules\user\forms\RegisterForm;
use frontend\modules\user\forms\LoginForm;
use yii\base\Action;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

class OrderAddressAction extends Action
{
    public function run()
    {
        $postcode = $_POST['postcode'] ?? '';
        $city = $_POST['city'] ?? '';
        $street = $_POST['street'] ?? '';

        $cities = CateringCity::find()
            ->andWhere(['postal'=>$postcode, 'visible'=>1])
            ->all();

        $response = ['postcode'=>false, 'cities'=>[], 'streets'=>[]];

        if ($cities) {
            $selectedCity = null;
            $selectedStreet = null;

            $resCities = [];
            foreach ($cities as $cityItem) {
                if (in_array($cityItem->city, ArrayHelper::getColumn($resCities, 'name'))) {
                    continue;
                }

                $selected = $selectedCity===null || $cityItem->city === $city;
                if ($selected) {
                    $selectedCity = count($resCities);
                }

                $resCities[] = ['name'=>$cityItem->city];
            }

            $resCities[ $selectedCity ]['selected'] = true;
            $response['cities'] = $resCities;

            $allowStreet = false;
            $resStreets = [];
            foreach ($cities as $cityItem) {
                if ($cityItem->city !== $resCities[ $selectedCity ]['name']) {
                    continue;
                }

                if (in_array($cityItem->street, ArrayHelper::getColumn($resStreets, 'name'))) {
                    continue;
                }

                if ($cityItem->{'allow-street'}) {
                    $allowStreet = true;
                }

                $selected = $selectedStreet===null || $cityItem->street === $street;
                if ($selected) {
                    $selectedStreet = count($resStreets);
                }

                $resStreets[] = ['name'=>$cityItem->street];
            }

            $resStreets[ $selectedStreet ]['selected'] = true;
            $response['streets'] = $allowStreet ? '*' : $resStreets;


            $response['postcode'] = true;
        }

        return json_encode($response);
    }
}
