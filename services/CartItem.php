<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use frontend\modules\catering\forms\CartMenuAlternativeForm;
use frontend\modules\catering\forms\CartOptionsForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringMeal;
use frontend\modules\catering\models\CateringMealType;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\base\BaseObject;

/**
 * Class CartItem
 * @package frontend\modules\catering\services
 */
class CartItem extends BaseObject
{

    /**
     * @var Cart
     */
    public $cart;

    /**
     * @var string
     */
    public $uuid;

    /**
     * @var string
     */
    public $type;

    /**
     * @var CateringDiet
     */
    public $diet;

    /**
     * @var CartOptionsForm
     */
    public $options;

    public $calculation = false;

    public $changes = [];

    public function init(): void
    {
        if (!$this->uuid) {
            $this->uuid = Uuid::uuid4()->toString();
        }
    }

    public function getDiet(): CateringDiet
    {
        return $this->diet;
    }

    public function getSelectedDays(string $format = null): array
    {
        $dates = $this->options->getSelectedDays();

        $objects = [];
        foreach ($dates as $date) {
            $date = new \DateTime($date);
            $objects[] = $format!==null ? $date->format($format) : $date;
        }

        return $objects;
    }

    public function getStartDate(): \DateTime
    {
        $dates = $this->getSelectedDays();

        return $dates[0];
    }

    public function getEndDate(): \DateTime
    {
        $dates = $this->getSelectedDays();

        return $dates[count($dates)-1];
    }

    public function getCalculation($force = false): ?Calculation
    {
        if ($this->calculation===false || $force) {
            $this->calculation = Calculation::createFromCart($this, $this->cart);
        }

        return $this->calculation;
    }

    public function changeMenu(CartMenuAlternativeForm $formModel): self
    {
        $menuId = $formModel->date.'-'.$formModel->meal_type;

        foreach ($this->changes as $menuIndex => $menu) {
            if ($menu['id']===$menuId) {
                unset($this->changes[$menuIndex]);
            }
        }

        $this->changes = array_values($this->changes);

        list($dietId, $mealId) = explode('-', $formModel->meal_id);

        if ($formModel->meal_id!=='0') {
            $this->changes[] = [
                'id'=>$menuId,
                'date'=>$formModel->date,
                'meal_type'=>(int)$formModel->meal_type,
                'diet_id'=>(int)$dietId,
                'meal_id'=>(int)$mealId,
            ];
        }

        $this->cart->toCookie();

        return $this;
    }

    public function getMenuChange(\DateTime $date, CateringMealType $mealType): ?CateringMeal
    {
        foreach ($this->changes as $menu) {
            if ($menu['meal_type'] === $mealType->id && $menu['date']===$date->format('Y-m-d')) {
                return CateringMeal::find()->andWhere(['id'=>$menu['meal_id']])->one();
            }
        }

        return null;
    }
}
