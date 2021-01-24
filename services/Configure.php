<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

class Configure extends BaseObject
{
    public static function read(string $key): ?string
    {
        return \Yii::$app->params['legacy'][$key] ?? null;
    }
}
