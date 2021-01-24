<?php

namespace frontend\modules\catering\controllers\order;

use common\models\UserFriend;
use frontend\modules\bc\forms\UploadFieldForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\i18n\models\i18nDictCountry;
use frontend\modules\i18n\models\i18nDictLocale;
use Yii;

use yii\base\Action;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use common\models\TempUploads;

class PayAction extends Action
{
    public function run($token)
    {
        $order = CateringOrder::find()
            ->andWhere(['token'=>$token])
            ->andWhere(['status'=>0])
            ->one();
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $order->pay_type = CateringOrder::PAYMENT_TYPE_PRZELEWY24;
        if (!$order->save()) {
            throw new NotFoundHttpException();
        }

        $merchantId = 102248;
        $salt = "dce7d963c20b4668";
        $version = "3.2";

        $data = [];
        $data['p24_currency'] = 'PLN';
        $data['p24_country'] = 'PL';
        $data['p24_session_id'] = $token;
        $data['p24_amount'] = ($order->topay - $order->wallet) * 100;
        $data['p24_description'] = 'Zamowienie numer #' . $order->id;
        $data['p24_email'] = $order->email;
        $data['p24_merchant_id'] = $merchantId;
        $data['p24_pos_id'] = $merchantId;
        $crc = md5($data["p24_session_id"] . "|" . $merchantId . "|" . $data["p24_amount"] . "|" . $data["p24_currency"] . "|" . $salt);
        $data['p24_sign'] = $crc;
        $data['p24_api_version'] = $version;
        $data['p24_encoding'] = 'Windows-1250';
        $data['p24_url_cancel'] = Yii::getAlias('@frontendUrl').Url::to(['/catering/order/summary', 'token'=>$order->token, 'status'=>0]);
        $data['p24_url_return'] = Yii::getAlias('@frontendUrl').Url::to(['/catering/order/summary', 'token'=>$order->token, 'status'=>1]);
        $data['p24_url_status'] = Yii::getAlias('@frontendUrl/przelewy24/status');

        return $this->controller->render('pay', [
            'order'=>$order,
            'data'=>$data,
        ]);
    }
}
