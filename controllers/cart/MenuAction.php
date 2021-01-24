<?php

namespace frontend\modules\catering\controllers\cart;

use common\models\UserFriend;
use frontend\modules\bc\forms\UploadFieldForm;
use frontend\modules\catering\forms\CartMenuAlternativeForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringMealType;
use frontend\modules\catering\services\Calculation;
use frontend\modules\catering\services\Cart;
use frontend\modules\catering\services\CartItem;
use frontend\modules\catering\services\Diet;
use frontend\modules\site\helpers\BH;
use Yii;

use yii\base\Action;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use common\models\TempUploads;

class MenuAction extends Action
{
    public function run($day = 1)
    {
        $cart = Cart::instance();

        if (!$_SESSION['cart-config'] || !($cartItem = $cart->getByUUID($_SESSION['cart-config'])) || !$cartItem->diet->is_customizable) {
            throw new NotFoundHttpException();
        }

        $daysSelected = explode(',', $cartItem->options->days_selected);
        $dayNumber = isset($daysSelected[$day-1]) ? $day : 1;
        $daySelected = new \DateTime($daysSelected[$dayNumber-1]);

        $alternativeModel = new CartMenuAlternativeForm();
        $alternativeModel->cart_item_uuid = $cartItem->uuid;
        $alternativeModel->date = $daySelected->format('Y-m-d');

        $meals = $cartItem->getDiet()->getDietMeals()
            ->joinWith(['mealType'])
            ->with(['meal', 'dietChange'])
            ->andWhere(['cdm.date'=>$alternativeModel->date])
            ->orderBy('cmt.sort ASC')
            ->groupBy('cmt.id')
            ->indexBy('meal_id')
            ->all();

        $changes = Diet::getMealsChanges($cartItem->diet->getDietCalories()->andWhere(['id'=>$cartItem->options->calory])->one(), $meals, $daySelected);

        $calculation = Calculation::createFromCart($cartItem, $cart);

        return $this->controller->render('menu', [
            'cartItem'=>$cartItem,

            'alternativeModel'=>$alternativeModel,
            'meals'=>$meals,
            'daysSelected'=>$daysSelected,
            'daySelected'=>$daySelected,
            'dayNumber'=>$dayNumber,
            'calculation'=>$calculation,
            'changes'=>$changes,
        ]);
    }
}
