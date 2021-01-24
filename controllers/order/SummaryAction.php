<?php

namespace frontend\modules\catering\controllers\order;

use common\models\UserFriend;
use frontend\modules\bc\forms\UploadFieldForm;
use frontend\modules\catering\models\CateringDiet;
use frontend\modules\catering\models\CateringOrder;
use frontend\modules\catering\services\Param;
use frontend\modules\i18n\models\i18nDictCountry;
use frontend\modules\i18n\models\i18nDictLocale;
use Yii;

use yii\base\Action;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use common\models\TempUploads;

class SummaryAction extends Action
{
    public function run($token)
    {
        $order = CateringOrder::find()
            ->andWhere(['token'=>$token])
            ->one();
        if (!$order) {
            throw new NotFoundHttpException();
        }

        $status = $_GET['status'] ?? 1;

        $cookieName = 'transaction_'.$order->token;
        $sendAnalytics = !isset($_COOKIE[$cookieName]) && $status==1 && ENV_PROD;
        setcookie($cookieName, 1, time()+3600*24*365, '/');

        $company = [
            'name'=>Param::get('company-name-1'),
            'postcode'=>Param::get('company-postal'),
            'city'=>Param::get('company-city'),
            'street'=>Param::get('company-address'),
            'building_nr'=>Param::get('company-house'),
            'flat_nr'=>Param::get('company-flat'),
            'account_number' => Param::get('company-contact-3')
        ];

        return $this->controller->render('summary', [
            'order'=>$order,
            'status'=>$status,
            'company'=>$company,
            'sendAnalytics'=>$sendAnalytics
        ]);
    }
}
