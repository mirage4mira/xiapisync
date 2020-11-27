<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    public static $platforms = [
        1 => 'SHOPEE',
        2 => 'LAZADA'
    ];

    protected $fillable = ['platform_shop_id','platform'];

    public function settings(){
        return $this->hasmany('App\ShopSetting');
    }

    public function getShopInfo(){
        $shopInfo = [];
        if($this->platform === self::$platforms[1]){
            $path = '/api/v1/shop/get';
            $data = [
                'partner_id' => shopee_partner_id(),
                'shopid' => $this->platform_shop_id,
                'timestamp' => time(),
            ];
            $responseData = shopee_http_post($path,$data)->json();
            $shopInfo['id'] = $this->id;
            $shopInfo['platform'] = $this->platform;
            $shopInfo['platform_shop_id'] = $responseData['shop_id'];
            $shopInfo['shop_name'] = $responseData['shop_name'];
            $shopInfo['shop_country'] = $responseData['country'];
        }
        return $shopInfo;

    }
}
