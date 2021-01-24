<?php
/**
 * Copyright (c) 2019.
 *
 * Robert Siennicki (rs@bananahouse.pl)
 * http://bananahouse.pl
 */

namespace frontend\modules\catering\models\query;

use App\Helpers\ArrayHelper;
use frontend\modules\catering\models\CateringDietSpecial;
use yii\db\ActiveQuery;

class CateringDietQuery extends ActiveQuery
{
    public function active(){
        $specialIds = ArrayHelper::getColumn((array)CateringDietSpecial::findBy(), 'id');

        $this->andWhere([$this->getTableNameAndAlias()[1].'.visible'=>1]);
        $this->andWhere([
            'OR',
            ['IS', 'special_action_id', NULL],
            [$this->getTableNameAndAlias()[1].'.special_action_id'=>$specialIds]
        ]);

        return $this;
    }
}
