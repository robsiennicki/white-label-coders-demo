<?php
/**
 * Copyright (c) 2020.
 *
 * Robert Siennicki (rs@bananahouse.pl)
 * http://bananahouse.pl
 */

namespace frontend\modules\catering\services;

use frontend\modules\catering\models\CateringParam;
use yii\base\BaseObject;

class Param extends BaseObject
{
    public static $params = false;

    public static function get($name='', $default=null, $type = null)
    {
        if (self::$params===false) {
            self::initParams();
        }

        if (!isset(self::$params[$name])) {
            return $default;
        }

        $param = self::$params[$name];
        if ($type===null) {
            switch ($param['type']) {
                case 0: $type = 'valueDbl'; break;
                case 1: $type = 'valueStr'; break;
                case 2: $type = 'valueTxt'; break;
                case 3: $type = 'valueBool'; break;
            }
        }

        return $param[$type] ?? $default;
    }

    public static function initParams()
    {
        self::$params = CateringParam::find()
            ->indexBy('name')
            ->all();
    }
}
