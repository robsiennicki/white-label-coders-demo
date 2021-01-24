<?php
/**
 * Copyright (c) 2020.
 *
 * Robert Siennicki (rs@bananahouse.pl)
 * http://bananahouse.pl
 */

namespace frontend\modules\catering\models;

use yii\base\Model;

class CateringDietSpecial extends Model {
    public static $offers = [
        [
            'id'=>1,
            'slug'=>'wielkanoc',
            'name'=>'Wielkanoc',
            'intro_title'=>'',
            'intro_content'=>'',
            'date_from'=>'2020-04-11',
            'date_to'=>'2020-04-11',
            'date_days'=>1,
            'date_saturday_on'=>true,
            'date_sunday_on'=>false,
            'edit_dates'=>false,
            'edit_variants'=>true,
            'footer'=>true,
            'footer_title'=>'OFERTA WIELKANOCNA',
            'footer_desc'=>'Przed nami smakowite święta! Przygotowaliśmy dla Was 3 zestawy świątecznych potraw. Zamówiony zestaw otrzymacie w sobotę 11.04. A Ty który zestaw wybierasz? Wielkanoc Gai, Wielkanoc Kuby czy Wegetariańską Wielkanoc Joanny? Śpiesz się - zamówienia przyjmujemy do czwartku, do godziny 16.00.',
            'footer_btn'=>'Zamów do domu',
            'active_from'=>'2020-04-02 00:00:00',
            'active_to'=>'2020-04-09 17:30:00',
            'show_menu'=>false,
            'show_on_list'=>false,
        ],
        [
            'id'=>2,
            'slug'=>'dla-juniora',
            'name'=>'Dla Juniora',
            'intro_title'=>'',
            'intro_content'=>'',
            'date_from'=>NULL,
            'date_to'=>NULL,
            'date_days'=>NULL,
            'date_saturday_on'=>NULL,
            'date_sunday_on'=>NULL,
            'edit_dates'=>true,
            'edit_variants'=>false,
            'footer'=>false,
            'footer_title'=>'MENU OBIADOWE DLA JUNIORA',
            'footer_desc'=>'MENU OBIADOWE polecamy dla dzieci, młodzieży i dorosłych, którzy samodzielnie przygotowują śniadania i kolacje, ale nie mają czasu lub pomysłu na OBIAD. MENU OBIADOWE możesz zamówić jako dodatek do każdej z naszych diet.',
            'footer_btn'=>'ZAMÓW DO DOMU',
            'active_from'=>'2020-04-02 00:00:00',
            'active_to'=>'2120-04-09 17:30:00',
            'show_menu'=>true,
            'show_on_list'=>true,
            'meals'=>[
                '2'=>[
                    'name'=>'Zupa'
                ],
                '3'=>[
                    'name'=>'Obiad'
                ]
            ],
        ],
        [
            'id'=>3,
            'slug'=>'shoty',
            'name'=>'Shoty',
            'intro_title'=>'',
            'intro_content'=>'',
            'date_from'=>NULL,
            'date_to'=>NULL,
            'date_days'=>NULL,
            'date_saturday_on'=>NULL,
            'date_sunday_on'=>NULL,
            'edit_dates'=>true,
            'edit_variants'=>false,
            'footer'=>false,
            'footer_title'=>'',
            'footer_desc'=>'',
            'footer_btn'=>'',
            'active_from'=>'2020-04-02 00:00:00',
            'active_to'=>'2120-04-09 17:30:00',
            'show_menu'=>false,
            'show_on_list'=>true,
            'meals'=>null,
        ],
        [
            'id'=>4,
            'slug'=>'swieta',
            'name'=>'Święta',
            'intro_title'=>'',
            'intro_content'=>'',
            'date_from'=>'2020-12-24',
            'date_to'=>'2020-12-26',
            'date_days'=>3,
            'date_saturday_on'=>true,
            'date_sunday_on'=>true,
            'edit_dates'=>false,
            'edit_variants'=>false,
            'footer'=>false,
            'footer_title'=>'MENU NA WIGILIĘ I ŚWIĘTA',
            'footer_desc'=>'',
            'footer_btn'=>'Zamów do domu',
            'active_from'=>'2020-11-30 11:00:00',
            'active_to'=>'2020-12-21 16:00:00',
            'show_menu'=>false,
            'show_on_list'=>false,
            'meals'=>null,
        ],
        [
            'id'=>5,
            'slug'=>'sylwester',
            'name'=>'Sylwester i Nowy Rok',
            'intro_title'=>'',
            'intro_content'=>'',
            'date_from'=>'2020-12-31',
            'date_to'=>'2020-12-31',
            'date_days'=>1,
            'date_saturday_on'=>true,
            'date_sunday_on'=>true,
            'edit_dates'=>false,
            'edit_variants'=>false,
            'footer'=>false,
            'footer_title'=>'MENU NA WIGILIĘ I ŚWIĘTA',
            'footer_desc'=>'',
            'footer_btn'=>'Zamów do domu',
            'active_from'=>'2020-12-21 16:00:00',
            'active_to'=>'2020-12-28 16:00:00',
            'show_menu'=>false,
            'show_on_list'=>false,
            'meals'=>null,
        ],
    ];

    public static function getFirst(){
        return self::getBy();
    }

    public static function findBy($key=NULL, $value=NULL, $object = false){
        $offers = [];
        foreach(self::$offers as $offerTmp){
            if(date('Y-m-d H:i:s')<$offerTmp['active_from'] || date('Y-m-d H:i:s')>$offerTmp['active_to']){
                continue;
            }

            if(!$key || $offerTmp[$key]==$value){
                $offers[] = $object ? (object) $offerTmp : $offerTmp;
            }
        }

        return $offers;
    }

    public static function getBy($key=NULL, $value=NULL, $object=false){
        $offer = self::findBy($key, $value, $object);

        return $offer ? $offer[0] : null;
    }

    public static function getById($id, $object=false){
        return self::getBy('id', $id, $object);
    }

    public static function getBySlug($slug, $object=false){
        return self::getBy('slug', $slug, $object);
    }

    public static function getOptionsList(){
        $list = [];
        foreach(self::$offers as $offer){
            $list[$offer['id']] = $offer['name'];
        }

        return $list;
    }
}
