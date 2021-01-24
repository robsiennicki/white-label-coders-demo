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

class GoogleMaps extends BaseObject
{
    public static function getCoord($address)
    {
        $address = str_replace(' ', '+', $address);
        $address = preg_replace('/[^\PC\s]/u', '', $address);
        $address = urlencode($address);

        $geocode = file_get_contents('https://maps.google.com/maps/api/geocode/json?key=' . Configure::read('App.google-geokey') . '&address=' . $address . '&sensor=false');
        $lat = 0;
        $lng = 0;
        try {
            $output = json_decode($geocode, true, 512, JSON_THROW_ON_ERROR);
            if (!empty($output['results'][0]['geometry']['location']['lat'])) {
                $lat = $output['results'][0]['geometry']['location']['lat'];
                $lng = $output['results'][0]['geometry']['location']['lng'];
            }
        } catch (\JsonException $e) {
            return null;
        }

        return (object)['lat'=>$lat, 'lng'=>$lng];
    }
}
