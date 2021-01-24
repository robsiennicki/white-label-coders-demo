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
class OrderController extends Controller{
    /**
     * @var string
     */
    public $defaultAction = 'summary';

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
            'summary'               => 'frontend\modules\catering\controllers\order\SummaryAction',
            'pay'                   => 'frontend\modules\catering\controllers\order\PayAction',
        ];
    }
}
