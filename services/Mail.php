<?php
/**
 * Copyright (c) 2020.
 *
 * Robert Siennicki (rs@bananahouse.pl)
 * http://bananahouse.pl
 */

namespace frontend\modules\catering\services;

use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\models\CateringParam;
use yii\base\BaseObject;
use Yii;

class Mail extends BaseObject
{
    public $subject;
    public $body;

    public function customize($mail){
        return $mail;
    }

    public function render(){
        return '';
    }

    public function compose(){
        return $this->customize(Yii::$app->mailer->compose())
            ->setSubject($this->subject)
            ->setHtmlBody($this->render());
    }

    public function send(){
        return $this->compose()
            ->send();
    }
}
