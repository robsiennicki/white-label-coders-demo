<?php

namespace frontend\modules\catering\controllers\cart;

use common\models\UserFriend;
use frontend\modules\bc\forms\UploadFieldForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\services\Cart;
use frontend\modules\i18n\models\i18nDictCountry;
use frontend\modules\i18n\models\i18nDictLocale;
use Yii;

use yii\base\Action;
use yii\helpers\Json;
use yii\web\UploadedFile;

use common\models\TempUploads;

class SelectAction extends Action
{
    public function run()
    {
        $cart = Cart::instance();

        $diets = CateringDiet::find()
            ->active()
            ->andWhere(['is_customizable'=>0])
            ->orderBy('sort ASC')
            ->all();

        return $this->controller->render('select', [
            'diets'=>$diets,
            'cart'=>$cart
        ]);
    }
}
