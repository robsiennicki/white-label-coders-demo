<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use frontend\modules\catering\forms\CartOptionsForm;
use frontend\modules\catering\forms\CartOrderForm;
use frontend\modules\catering\forms\CustomerAddressForm;
use frontend\modules\catering\forms\CustomerAddressInvoiceForm;
use frontend\modules\catering\forms\CustomerAddressWeekendForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\user\forms\RegisterForm;
use Yii;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

class Cart extends BaseObject
{
    public $items = [];

    /**
     * @var \frontend\modules\user\models\User
     */
    public $user;

    /**
     * @var RegisterForm
     */
    public $userOptions;

    /**
     * @var CustomerAddressForm
     */
    public $deliveryOptions;

    /**
     * @var CustomerAddressWeekendForm
     */
    public $deliveryWeekendOptions;

    /**
     * @var CustomerAddressInvoiceForm
     */
    public $invoiceOptions;

    /**
     * @var CartOrderForm
     */
    public $orderOptions;

    public static function instance(): self
    {
        $cartArray = $_SESSION['cart'] ?? [];

        $cart = new self;
        if ($cartArray) {
            foreach ($cartArray['items'] as $item) {
                $diet = CateringDiet::find()
                    ->active()
                    ->andWhere(['id'=>$item['diet']['id']])
                    ->one();
                if (!$diet) {
                    continue;
                }

                $item['diet'] = $diet;
                $item['options'] = new CartOptionsForm($item['options']);

                $cartItem = new CartItem($item);
                $cartItem->cart = $cart;

                $cart->items[] = $cartItem;
            }
        }

        $cart->toCookie();

        return $cart;
    }

    public function toCookie(): void
    {
        setcookie('cart-quantity', (string) count($this->items), time()+3600*24*30, '/', str_replace('zamow.', '', basename(Yii::getAlias('@frontendUrl'))), false, true);

        $_SESSION['cart'] = ArrayHelper::toArray($this, [
            CartItem::class => [
                'uuid',
                'type',
                'diet',
                'options',
                'changes'
            ],
            CateringDiet::class => [
                'id'
            ],
        ]);
    }

    public function destroy(): void
    {
        $cart = new self();
        $cart->toCookie();
    }

    public function add(CartItem $item): self
    {
        $item->cart = $this;

        $this->items[] = $item;
        $this->toCookie();

        return $this;
    }

    public function getByUUID(string $uuid): ?CartItem
    {
        foreach ($this->items as $item) {
            if ($item->uuid === $uuid) {
                return $item;
            }
        }

        return null;
    }

    public function delete(CartItem $item): bool
    {
        $idDeleted = false;
        foreach ($this->items as $k=>$v) {
            if ($v->uuid===$item->uuid) {
                unset($this->items[$k]);
                $this->toCookie();
                $idDeleted = true;
                break;
            }
        }

        $itemsByChildren = ArrayHelper::map($this->items, 'uuid', 'uuid', 'diet.is_children');
        if(isset($itemsByChildren[1]) && count($itemsByChildren[1]) > 0 && (!isset($itemsByChildren[0]) || !count($itemsByChildren[0]))){
            $this->items = [];
            $this->toCookie();
        }

        return $idDeleted;
    }

    public function getDiscount(): float
    {
        $discount = 0;
        foreach ($this->items as $item) {
            $discount += $item->getCalculation()->getDiscount();
        }

        return $discount;
    }

    public function getDietPrice(): float
    {
        $price = 0;
        foreach ($this->items as $item) {
            $price += $item->getCalculation()->getDietPrice();
        }

        return $price;
    }

    public function getPackagePrice(): float
    {
        $price = 0;
        foreach ($this->items as $item) {
            $price += $item->getCalculation()->getPackagePrice();
        }

        return $price;
    }

    public function getTotalPrice(): float
    {
        $price = 0;
        foreach ($this->items as $item) {
            $price += $item->getCalculation()->getTotalPrice();
        }

        return $price;
    }

    public function getToPayPrice(): float
    {
        return $this->getTotalPrice() - $this->getFromUserWallet();
    }

    public function getFromUserWallet(): float
    {
        if (!$this->user) {
            return 0;
        }

        if ($this->user->wallet > $this->getTotalPrice()) {
            return $this->getTotalPrice();
        }

        return (float) $this->user->wallet;
    }

    public function hasWeekendDelivery(): bool
    {
        $weekendDelivery = false;
        foreach ($this->items as $item) {
            if ($item->options->days_weekend==="1") {
                $weekendDelivery = true;
            }
        }

        return $weekendDelivery;
    }

    public function setUser(\frontend\modules\user\models\User $user = null, RegisterForm $formModel = null): self
    {
        $this->user = $user;
        $this->userOptions = $formModel;

        return $this;
    }

    public function setOrderOptions(CartOrderForm $formModel): self
    {
        $this->orderOptions = $formModel;

        return $this;
    }

    public function setDeliveryOptions(CustomerAddressForm $formModel): self
    {
        $this->deliveryOptions = $formModel;

        return $this;
    }

    public function setDeliveryWeekendOptions(CustomerAddressWeekendForm $formModel): self
    {
        $this->deliveryWeekendOptions = $formModel;

        return $this;
    }

    public function setInvoiceOptions(CustomerAddressInvoiceForm $formModel): self
    {
        $this->invoiceOptions = $formModel;

        return $this;
    }

    public function validateItems(): bool
    {
        $errors = [];
        if(empty($this->items)){
            $errors[] = 'Brak diet w koszyku';
        }

        $discounts = Discount::checkCart($this);
        foreach ($discounts as $discount) {
            if ($discount->errors) {
                $errors = array_merge($errors, $discount->errors);
            }
        }

        return !$errors;
    }

    public function getSelectedDays(string $format = null): array
    {
        $selected = [];
        foreach($this->items as $item){
            $selected = array_merge($selected, (array)$item->getSelectedDays($format));
        }

        return array_values(array_unique($selected));
    }
}
