<?php

namespace frontend\modules\catering\controllers\cart;

use common\models\UserFriend;
use frontend\modules\bc\forms\UploadFieldForm;
use frontend\modules\catering\forms\CartOptionsForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringDiscount;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\services\Calculation;
use frontend\modules\catering\services\Cart;
use frontend\modules\catering\services\CartItem;
use frontend\modules\catering\services\Discount;
use frontend\modules\catering\services\Order;
use frontend\modules\catering\services\Param;
use Yii;

use yii\base\Action;
use yii\web\NotFoundHttpException;

class OptionsAction extends Action
{
    public function run($id=null, $slug=null)
    {
        $cart = Cart::instance();

        $type = $_GET['type'] === 'custom-diet' ? 'custom-diet' : 'defined-diet';

        if ($type=='custom-diet') {
            $diet = CateringDiet::find()
                ->active()
                ->andWhere(['is_customizable'=>1])
                ->one();
        } else {
            $diet = CateringDiet::find()
                ->active()
                ->andWhere(['id'=>$id])
                ->one();
        }

        if (!$diet) {
            throw new NotFoundHttpException();
        }

        $blockedDays = Order::getBlockedDays();
        $orderedDays = $cart->getSelectedDays('Y-m-d');

        $calories = $diet->dietCalories;

        $optionsModel = new CartOptionsForm();
        $optionsModel->calory = $calories[0]->id;

        $calendarStart = Order::getClosestAvailableDays(1, true, null, null, $diet->dietPattern ? $diet->dietPattern : null);
        $closestDay = Order::getClosestAvailableDays($diet->default_duration > 0 ? $diet->default_duration : Param::get('dieta-domyslnie'), (bool) $optionsModel->days_weekend, null, 'Y-m-d', $diet->dietPattern ? $diet->dietPattern : null);

        $optionsModel->days_start = $closestDay[0];
        $optionsModel->days_selected = implode(',', $closestDay);
        $optionsModel->days_count = count(explode(',', $optionsModel->days_selected));

        $cartItem = new CartItem([
            'type'=>$type,
            'diet'=>$diet,
            'cart'=>$cart
        ]);

        $notAllowed = $diet->isNotAllowed($cart);

        if ($optionsModel->load($_POST)) {
            $cartItem->options = $optionsModel;

            $discount = Discount::checkCartItems($cartItem, $cart);

            if ($optionsModel->validate()) {
                if ($notAllowed){
                    $optionsModel->addError('count', $notAllowed);
                }elseif ($cartItem->getCalculation()===null) {
                    $optionsModel->addError('count', 'Błąd przeliczania cen, spróbuj ponownie.');
                } elseif ($discount['errors']) {
                    $optionsModel->addError('discount_code', $discount['errors'][0]['error']);
                } else {
                    $_SESSION['cart-config'] = $cartItem->uuid;
                    $_SESSION['event-dataLayer'] = [
                        'event' => 'addToCart',
                        'ecommerce' => [
                            'currencyCode' => 'PLN',
                            'add' => [
                                'products' => [
                                    [
                                        "id" => $cartItem->diet->primaryKey,
                                        "name" => $cartItem->diet->name,
                                        "price" => $cartItem->getCalculation()->getDailyPrice(),
                                        "variant" => $cartItem->diet->getDietCalories()->andWhere(['id'=>$cartItem->options->calory])->one()->value ?? null,
                                        "quantity" => count($cartItem->getSelectedDays())
                                    ]
                                ]
                            ]
                        ]
                    ];
                    $cart->add($cartItem);

                    if ($cartItem->type=='custom-diet') {
                        return $this->controller->redirect(['/catering/cart/menu']);
                    } else {
                        return $this->controller->redirect(['/catering/cart/order']);
                    }
                }
            }
        }

        return $this->controller->render('options', [
            'cartItem'=>$cartItem,
            'calories'=>$calories,
            'optionsModel'=>$optionsModel,
            'calendarStart'=>$calendarStart[0]->format('Y-m-d'),
            'calendarStop'=>(new \DateTime())->modify('+90 days')->format('Y-m-d'),
            'blockedDays'=>$blockedDays,
            'discount'=>$discount,
            'notAllowed'=>$notAllowed,
            'orderedDays'=>$orderedDays,
        ]);
    }
}
