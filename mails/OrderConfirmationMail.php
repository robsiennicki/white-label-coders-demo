<?php
/**
 * Copyright (c) 2020.
 *
 * Robert Siennicki (rs@bananahouse.pl)
 * http://bananahouse.pl
 */

namespace frontend\modules\catering\mails;

use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\services\Mail;
use frontend\modules\catering\services\Param;
use yii\base\BaseObject;
use Yii;

class OrderConfirmationMail extends Mail
{
    public $order;
    public $diets;

    public function init()
    {
        $this->diets = CateringDiet::find()
            ->indexBy('id')
            ->all();

        if(!($this->order instanceof CateringOrder)){
            $this->order = CateringOrder::findOne(['id'=>$this->order]);
        }

        $this->subject = Param::get('order-title');
    }

    public function render(){
        $body = Param::get('order-body');
        $view = Yii::$app->view->render('//mails/partials/order-details', ['order'=>$this->order, 'diets'=>$this->diets]);
        $body = str_replace(array('{{diets}}', '{{nr}}'), array($view, $this->order->id), $body);

        return $body;
    }

    public function customize($mail){
        return $mail->setTo($this->order->email);
    }
}
