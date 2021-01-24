<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringDietCalory;
use frontend\modules\catering\models\CateringDietChanges;
use frontend\modules\catering\models\CateringDietMeal;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use frontend\modules\site\helpers\BH;

class Diet extends BaseObject
{
    public static function read(string $key): ?string
    {
        return \Yii::$app->params['legacy'][$key] ?? null;
    }

    public function getMealsChanges(CateringDietCalory $calory, array $meals, \DateTime $date): array
    {
        $mealIdsFromMeals = array_unique(array_values(ArrayHelper::getColumn($meals, 'meal_id')));
        $mealIdsFromOnChanges = ArrayHelper::getColumn((array) $calory->diet->getDietChanges()->andWhere(['on_change'=>1, 'date'=>$date->format('Y-m-d')])->groupBy('meal_id')->all(), 'meal_id');
        $mealIds = [];
        foreach ($mealIdsFromMeals as $mealId){
            if(!in_array($mealId, $mealIdsFromOnChanges)){
                continue;
            }

            $mealIds[] = $mealId;
        }

        $changesQuery = CateringDietChanges::find()
            ->from([
                'cdc'=>CateringDietChanges::find()
                    ->joinWith(['dietMeal'=>function ($q) {
                        return $q->joinWith('meal');
                    }, 'diet'=>function ($q) {
                        return $q->joinWith('dietCalories');
                    }])
                    ->andWhere(['to_change'=>1, 'cdc.date'=>$date->format('Y-m-d'), 'cdc.meal_id'=>$mealIds, 'cdcal.value'=>$calory->value])
                    ->union(
                        CateringDietChanges::find()
                            ->joinWith(['dietMeal'=>function ($q) {
                                return $q->joinWith('meal');
                            }, 'diet'=>function ($q) {
                                return $q->joinWith('dietCalories');
                            }])
                            ->andWhere(['on_change'=>1, 'cdc.date'=>$date->format('Y-m-d'), 'cdc.meal_id'=>$mealIds, 'cdc.diet_id'=>$calory->diet_id])
                    )
            ])
            ->joinWith(['dietMeal'=>function ($q) {
                return $q->joinWith('meal');
            }])
            ->orderBy('TRIM(cm.name) ASC');

        $changes = $changesQuery->all();

        return ArrayHelper::index($changes, null, 'meal_id');
    }
}
