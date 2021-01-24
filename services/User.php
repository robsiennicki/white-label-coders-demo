<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use frontend\modules\user\forms\RegisterForm;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;

class User extends BaseObject
{
    public static function create(RegisterForm $formModel): \frontend\modules\user\models\User
    {
        if(!$formModel->validate()){
            throw new \Exception('Model has errors');
        }

        $user = new \frontend\modules\user\models\User();
        $user->name = $formModel->first_name;
        $user->surname = $formModel->last_name;
        $user->username = $user->email = $formModel->email;
        $user->phonenumber = $formModel->phone;
        $user->setPassword($formModel->password);
        $user->role = 'user';
        $user->status = 1;
        if(!$user->save()){
            throw new \Exception('Model not saved');
        }

        return $user;
    }
}
