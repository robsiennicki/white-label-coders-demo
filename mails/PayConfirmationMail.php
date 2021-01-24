<?php
/**
 * Copyright (c) 2020.
 *
 * Robert Siennicki (rs@bananahouse.pl)
 * http://bananahouse.pl
 */

namespace frontend\modules\catering\mails;

use frontend\modules\catering\services\Param;
use yii\base\BaseObject;
use Yii;

class PayConfirmationMail extends OrderConfirmationMail
{
    public function render(){
        $body = Param::get('pay-body');
        $view = Yii::$app->view->render('//mails/partials/order-details', ['order'=>$this->order, 'diets'=>$this->diets]);
        $body = str_replace(array('{{diets}}', '{{nr}}'), array($view, $this->order->id), $body);

        return $body;
    }
}
