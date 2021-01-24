<?php

namespace frontend\modules\catering\controllers\cart;

use common\models\UserFriend;
use frontend\modules\bc\forms\UploadFieldForm;
use frontend\modules\catering\forms\CartMenuAlternativeForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\services\Cart;
use frontend\modules\i18n\models\i18nDictCountry;
use frontend\modules\i18n\models\i18nDictLocale;
use Yii;

use yii\base\Action;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use common\models\TempUploads;

class MenuSelectAction extends Action
{
    public function run()
    {
        $cart = Cart::instance();

        $alternativeModel = new CartMenuAlternativeForm();
        if ($alternativeModel->load($_POST) && $alternativeModel->validate()) {
            $cartItem = $cart->getByUUID($alternativeModel->cart_item_uuid);
            if (!$cartItem) {
                throw new NotFoundHttpException();
            }

            $cartItem->changeMenu($alternativeModel);

            return 1;
        }

        throw new NotFoundHttpException();
    }
}
