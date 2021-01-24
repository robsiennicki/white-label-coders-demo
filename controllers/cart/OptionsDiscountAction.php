<?php

namespace frontend\modules\catering\controllers\cart;

use common\models\UserFriend;
use frontend\modules\bc\forms\UploadFieldForm;
use frontend\modules\catering\forms\CartOptionsForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\services\Calculation;
use frontend\modules\catering\services\Cart;
use frontend\modules\catering\services\CartItem;
use frontend\modules\catering\services\Discount;
use frontend\modules\catering\services\Order;
use frontend\modules\i18n\models\i18nDictCountry;
use frontend\modules\i18n\models\i18nDictLocale;
use Yii;

use yii\base\Action;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use common\models\TempUploads;

class OptionsDiscountAction extends Action
{
    public function run($id)
    {
        $cart = Cart::instance();

        $diet = CateringDiet::find()
            ->active()
            ->andWhere(['id'=>$id])
            ->one();

        $optionsModel = new CartOptionsForm();

        if ($optionsModel->load($_POST) && $optionsModel->discount_code) {
            $cartItem = new CartItem([
                'diet'=>$diet,
                'options'=>$optionsModel,
            ]);

            $discount = Discount::checkCartItems($cartItem, $cart);

            try {
                $discount['details'] = $discount['details']->attributes;
                return json_encode($discount, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
            }
        }

        throw new NotFoundHttpException();
    }
}
