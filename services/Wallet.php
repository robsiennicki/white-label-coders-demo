<?php

declare(strict_types=1);

namespace frontend\modules\catering\services;

use frontend\modules\catering\models\CateringWallet;
use Yii;
use yii\base\BaseObject;

class Wallet extends BaseObject
{
    public static function add(\frontend\modules\user\models\User $user, int $type, float $value): CateringWallet
    {
        $wallet = new CateringWallet();
        $wallet->type = $type;
        $wallet->value = $value;
        $wallet->link('user', $user);

        return $wallet;
    }

    public static function remove(\frontend\modules\user\models\User $user, float $value, bool $onlyAdmin = false): array
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $wallets = [0,0];
            $walletsDate = [(new \DateTime())->setTimestamp(0), (new \DateTime())->setTimestamp(0)];

            $types = [];
            if (!$onlyAdmin) {
                $types[] = 0;
            }
            $types[] = 1;

            foreach ($types as $type) {
                $history = CateringWallet::find()
                    ->andWhere(['!=', 'result_value', 0])
                    ->andWhere([
                        'user_id' => $user->id,
                        'can_remove' => $type,
                        'is_add' => 1,
                    ])
                    ->all();

                foreach ($history as $item) {
                    if ($item->result_value <= $value) {
                        $wallets[$type] += $item->result_value;
                        $value -= $item->result_value;
                        $item->result_value = 0;
                    } else {
                        $wallets[$type] += $value;
                        $item->result_value -= $value;
                        $value = 0;
                    }

                    if ($type===1) {
                        $tmpDate = new \DateTime($item->finished);
                        if ($walletsDate[$type] < $tmpDate) {
                            $walletsDate[$type] = $tmpDate;
                        }
                    }

                    if (!$item->save()) {
                        throw new \Exception();
                    }

                    if ($value===0) {
                        break;
                    }
                }

                if ($value===0) {
                    break;
                }
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return ['adminWallet' => $wallets[1], 'refundWallet' => $wallets[0], 'adminWalletDate' => $walletsDate[1]];
    }
}
