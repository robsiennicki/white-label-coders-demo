<?php
/**
 * Copyright (c) 2018.
 *
 * Robert Siennicki (rs@bananahouse.pl)
 * http://bananahouse.pl
 */

namespace frontend\modules\catering\controllers;

use common\components\filters\AccessControl;
use yii\filters\VerbFilter;

use common\components\filters\AjaxAccess;
use common\components\yii\Controller;

/**
 * Class LanguageController
 * @package frontend\modules\i18n\controllers
 */
class CartController extends Controller{
    /**
     * @var string
     */
    public $defaultAction = 'select';

    /**
     * Definicje zachowań
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    '*'  => ['get', 'post']
                ],
            ],
            'ajax' => [
                'class' => AjaxAccess::class,
                'only' => [
                    'index'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'select'              => 'frontend\modules\catering\controllers\cart\SelectAction',
            'options'             => 'frontend\modules\catering\controllers\cart\OptionsAction',
            'options-price'       => 'frontend\modules\catering\controllers\cart\OptionsPriceAction',
            'options-discount'    => 'frontend\modules\catering\controllers\cart\OptionsDiscountAction',
            'menu'                => 'frontend\modules\catering\controllers\cart\MenuAction',
            'menu-select'         => 'frontend\modules\catering\controllers\cart\MenuSelectAction',
            'order'               => 'frontend\modules\catering\controllers\cart\OrderAction',
            'order-address'       => 'frontend\modules\catering\controllers\cart\OrderAddressAction',
        ];
    }
}
