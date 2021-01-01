<?php

namespace App;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;

use App\Scopes\AuthorizedShopScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    public static $platforms = [
        1 => 'SHOPEE',
        2 => 'LAZADA'
    ];

    protected $fillable = ['platform_shop_id','platform','shop_token_id'];

    public function settings(){
        return $this->hasmany('App\ShopSetting');
    }

    public function users()
    {
        return $this->belongsToMany('App\User');
    }

    public function shop_token(){
        return $this->belongsTo('App\ShopToken');
    }

    protected static function booted()
    {
        if(auth()->user()->shops()->count()){
            $shop_ids = auth()->user()->shops()->pluck('shops.id');
            static::addGlobalScope('authorized_shops',function(Builder $builder) use ($shop_ids){
                if(auth()->id()){
                    $builder->whereIn('shops.id',$shop_ids);
                }
            });
        }
    }

    public function getShopInfo(){
        
        $shopInfo = [];
        if($this->platform === self::$platforms[1]){
            $path = '/api/v1/shop/get';
            $data = [
                'partner_id' => shopee_partner_id(),
                'shopid' => intval($this->platform_shop_id),
                'timestamp' => time(),
            ];
            $responseData = shopee_http_post($path,$data)->json();
            $shopInfo['id'] = $this->id;
            $shopInfo['platform'] = $this->platform;
            $shopInfo['platform_shop_id'] = $responseData['shop_id'];
            $shopInfo['shop_name'] = $responseData['shop_name'];
            $shopInfo['shop_country'] = $responseData['country'];
        }elseif($this->platform === self::$platforms[2]){
            $c = new LazopClient(getLazadaRestApiUrl($this),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            $request = new LazopRequest('/seller/get','GET');
            $response = json_decode($c->execute($request, getLazadaAccessToken($this)),true)['data'];
            // dd($response);
            $shopInfo['id'] = $this->id;
            $shopInfo['platform'] = $this->platform;
            $shopInfo['platform_shop_id'] = $this->platform_shop_id;
            $shopInfo['shop_name'] = $response['name'];
            $shopInfo['shop_country'] = strtoupper(explode('.',$this->platform_shop_id)[1]);
        }
        return $shopInfo;

    }
}
