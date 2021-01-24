<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringDietCalory;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

class Calculation extends BaseObject
{
    /**
     * @var CartItem
     */
    public $cartItem = null;
    /**
     * @var CateringDiet
     */
    public $diet = null;
    public $calory = null;
    public $daysSelected = [];
    public $ecoPackages = false;
    public $juiceShot = false;
    public $quantity = 1;

    public $days = 0;
    public $freeDays = 0;
    public $dailyPackagePrice = 0;
    public $dailyDietPrice = 0;
    public $dailyShotPrice = 0;
    public $dailyDiscount = 0;

    public static function createFromCart(CartItem $item): ?self
    {
        $calculation = new self;

        $calculation->cartItem = $item;
        $calculation->diet = $item->diet;
        $calculation->calory = (int) $item->options->calory;
        $calculation->daysSelected = $item->options->getSelectedDays();
        $calculation->ecoPackages = (bool) $item->options->eco_package;
        $calculation->juiceShot = (bool) $item->options->juice_shot;
        $calculation->quantity = (int) $item->options->count;

        $calculation->recalculate();

        return $calculation;
    }

    public function __toString(): string
    {
        try {
            return json_encode([
                'days' => $this->days,
                'freeDays' => $this->freeDays,
                'quantity' => $this->quantity,
                'dailyDiscount' => $this->getDailyDiscount() * $this->quantity,
                'discount' => $this->getDiscount() * $this->quantity,
                'dailyTotalPrice' => $this->getDailyPrice() * $this->quantity,
                'totalPrice' => $this->getTotalPrice(),
            ], JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
        }

        return '';
    }

    private $caloryModel = false;
    public function getCalory(): CateringDietCalory
    {
        if($this->caloryModel !== false){
            return $this->caloryModel;
        }

        $this->caloryModel = $this->diet->getDietCalories()->andWhere(['id'=>$this->calory])->one();

        return $this->caloryModel;
    }

    public function recalculate(): void
    {
        $this->days = count($this->daysSelected);

        $calory = $this->getCalory();
        if (!$calory) {
            throw new \Exception();
        }

        $prices = array_filter($this->diet->getDietPrices()->orderBy('days DESC')->all(), static function ($item) use ($calory) {
            return $item->kcal===$calory->value;
        });

        $priceMax = 0;
        $priceMin = 0;
        foreach ($prices as $price) {
            if ($priceMax < $price->price) {
                $priceMax = $price->price;
            }

            if ($this->days <= $price->days) {
                $priceMin = $price->price;
            }
        }

        $extraDays = 1;
        $minToExtraDay = Param::get('extra-dates');

        $freeDays = $this->days > $minToExtraDay ? $extraDays : 0;
        $dailyPackagePrice = $this->ecoPackages && $this->diet->allow_bio_package ? (float) $this->diet->bio_package : 0;
        $dailyJuiceShot = $this->juiceShot ? (float) CateringDiet::JUICE_SHOT_PRICE : 0;
        $dailyDietPrice = $priceMax;
        $dailyDiscount = $priceMax - $priceMin;

        $this->freeDays = $freeDays;
        $this->dailyPackagePrice = $dailyPackagePrice;
        $this->dailyShotPrice = $dailyJuiceShot;
        $this->dailyDietPrice = $dailyDietPrice;
        $this->dailyDiscount = $dailyDiscount;

        Discount::applyToCalculation($this);
    }

    public function getDailyDiscount(): float
    {
        return $this->dailyDiscount;
    }

    public function getDiscount(): float
    {
        return $this->getDailyDiscount() * $this->days;
    }

    public function getDailyDietPrice(): float
    {
        return $this->dailyDietPrice;
    }

    public function getDietPrice(): float
    {
        return $this->getDailyDietPrice() * $this->days;
    }

    public function getDailyShotPrice(): float
    {
        return $this->dailyShotPrice;
    }

    public function getShotPrice(): float
    {
        return $this->getDailyShotPrice() * $this->days;
    }

    public function getDailyPackagePrice(): float
    {
        return $this->dailyPackagePrice;
    }

    public function getPackagePrice(): float
    {
        return $this->getDailyPackagePrice() * $this->days;
    }

    public function getDailyPrice(): float
    {
        return $this->getDailyDietPrice() + $this->getDailyPackagePrice() + $this->getDailyShotPrice() - $this->getDailyDiscount();
    }

    public function getPrice(): float
    {
        return $this->getDailyPrice() * $this->days;
    }

    public function getTotalPrice(): float
    {
        return $this->getPrice() * $this->quantity;
    }
}
