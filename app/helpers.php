<?php

use Carbon\Traits\Timestamp;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpParser\Node\Expr\Throw_;

if (! function_exists('get_platforms')) {
    function get_platforms() {
        return [
            1 => 'SHOPEE',
            2 => 'LAZADA'
        ];
    }
}

if (! function_exists('shopee_http')) {
    function shopee_http(string $path,array $data) {
        $partnerKey = shopee_partner_key();
        $signBaseString = shopee_url($path).'|'.json_encode($data);
        $sign = hash_hmac('sha256',$signBaseString,$partnerKey);
        return \Illuminate\Support\Facades\Http::withHeaders(['Content-Type' => 'application/json','Authorization'=> $sign]);
    }
}

if (! function_exists('shopee_http_post')) {
    function shopee_http_post(string $path,array $data) {
        return shopee_http($path,$data)->post(shopee_url($path),$data);
    }
}

function shopee_multiple_async_post(string $path, array $datas,$shop = null){

    $partnerKey = shopee_partner_key();
    $url = shopee_url($path);

    $promises = [];
    $client = new GuzzleHttp\Client();
    foreach($datas as $key => $d){
        $signBaseString = $url.'|'.json_encode($d);
        $sign = hash_hmac('sha256',$signBaseString,$partnerKey);
        
        $headers = ['Authorization'=> $sign,'Content-Type' => 'application/json'];
        $promises[] = $client->postAsync($url,['headers' => $headers,'json' => $d,'timeout' => 60,'connect_timeout' => 60]);
    } 

        // $results = GuzzleHttp\Promise\unwrap($promises);
        $results = GuzzleHttp\Promise\settle($promises)->wait();
        $contents = [];
        foreach($results as $result){
        if(isset($result['value'])){
            $content = json_decode($result['value']->getBody()->getContents(),true);

            if(isset($content['error'])){
                \Log::error($content['error']);
                \Log::error($content['msg']);
            }else{
                $contents[] = $content;
            }
        }
        
    }

    return $contents;
}

function lazada_multiple_async_request(string $path, array $datas, $type,$shop = null){

    $appSecret = env('LAZADA_APP_SECRET');
    
    
    $promises = [];
    $client = new GuzzleHttp\Client();
    foreach($datas as $key => $d){
        $d['app_key'] = env('LAZADA_APP_KEY');
        $d['sign_method'] = "sha256";
        $d['timestamp'] = now()->timestamp * 1000;
        // dd($d['timestamp']);
        
        if($shop){
            $d['access_token'] = getLazadaAccessToken($shop);
            $url = getLazadaRestApiUrl($shop).$path;
        }else{
            $d['access_token'] = getLazadaAccessToken();
            $url = getLazadaRestApiUrl().$path;
        }

        // $d['partner_id'] = 'lazop-sdk-php-20180422';
        $sign = (new Paulwscom\Lazada\LazopClient(' '))->generateSign($path,$d,$appSecret);
        
        $d['sign'] = $sign;
        // unset($d['limit']);
        // unset($d['offset']);
        $headers = ['Authorization'=> $sign,'Content-Type' => 'application/json'];
        // dd($d);
        // $requestData = ['json' => $d,'timeout' => 60,'connect_timeout' => 60];
        $requestData = ['headers' => $headers,'json' => $d,'timeout' => 60,'connect_timeout' => 60];

        if($type == "POST"){
            $promises[] = $client->postAsync($url,$requestData);
        }
        elseif($type == "GET"){
            $promises[] = $client->getAsync($url.'?'.http_build_query($d));
        }
    } 

        // $results = GuzzleHttp\Promise\unwrap($promises);
    $results = GuzzleHttp\Promise\settle($promises)->wait();

    $contents = [];
    foreach($results as $result){
        if(isset($result['value'])){
            $content = json_decode($result['value']->getBody()->getContents(),true);
            
            if($content['code'] > 0){
                \Log::error($content['error']);
                \Log::error($content['msg']);
                
                throw new Exception("Lazada rest API error!");
            }else{
                $contents[] = $content;
            }
        }
        
    }

    return $contents;
}

function handleValidatorFails(\Illuminate\Http\Request $request,\Illuminate\Validation\Validator $validator){
    if($validator->fails()){
        if($request->ajax())
        {
            response()->json(array(
                'success' => false,
                'message' => 'There are incorect values in the form!',
                'errors' => $validator->getMessageBag()->toArray()
            ), 422)->send();

            exit();
        }

        $validator->validate();
        // $this->throwValidationException(

        //     $request, $validator
        // );
    }
}

if(!function_exists('shopee_url')){
    function shopee_url(string $path = ''){
        return env('SHOPEE_PLATFORM_BASE_URL').$path;
    }
}

if(!function_exists('shopee_partner_id')){
    function shopee_partner_id(){
        return intval(env('SHOPEE_PARTNER_ID'));
    }
}

if(!function_exists('shopee_partner_key')){
    function shopee_partner_key(){
        return env('SHOPEE_PARTNER_KEY');
    }
}

if(!function_exists('shopee_shop_id')){
    function shopee_shop_id($shop = null){
        if($shop){
            $currentShopId = $shop->id;
            return intval($shop->platform_shop_id);
        }else{
            $currentShopId = Auth::user()->current_shop_id;
        }
        if($currentShopId && getShopsSession()[$currentShopId]['platform'] === 'SHOPEE'){
            return intval(getShopsSession()[$currentShopId]['platform_shop_id']);
        }else{
            throw new Error('Current shop is not Shopee');
        }
    }
}

if(!function_exists('generate_token')){
    function generate_token(){
        return md5(rand(1, 10) . microtime());
    }
}

function setShopsSession(){
    $user = Auth::user();
    $shops = $user->shops;
    $shopsSession = [];
    foreach($shops as $shop){
        $shopsSession [$shop->id] = $shop->getShopInfo();
    }
    // dd($shopsSession);
    Session::put('available_shops_info',$shopsSession);
}

if(!function_exists('getShopsSession')){
    function getShopsSession(){
        // dd(Session::get('available_shops_info'));
        return Session::get('available_shops_info');
    }
}

function setShopSettingSession(){

    $shopSettings = App\ShopSetting::where('shop_id',Auth::user()->current_shop_id)->get()->toArray();
    $settings = [];
    foreach($shopSettings as $shopSetting){
        $settings[$shopSetting['setting']] = $shopSetting['value']; 
    }

    Session::put('current_shop_settings',$settings);
}

function getShopSettingSession(){
    return Session::get('current_shop_settings');
}

function toClientDateformat(string $date){
    return \Carbon\Carbon::parse($date)->format('m/d/Y');
}

function updateLastSyncTimeCookie(){
    cookie()->queue('last_sync_time', now()->timestamp, env('CACHE_DURATION')/60,null,null,false,false);
}

function checkLastSyncTime(){
    if(!Cookie::has('last_sync_time'))return false; 
    else return true;
}

function deleteLastSyncTime(){
    Cookie::queue(
        Cookie::forget('last_sync_time')
    );
}

function getLazadaRestApiUrl($shop = null){
    return str_replace('{COUNTRY}',getLazadaShopId($shop)['country'],env('LADAZA_API_BASE_URL'));
}

function getLazadaShopId($shop = null){
    if(!$shop){
        $shop = \App\Shop::find(Auth::user()->current_shop_id);
    }

    [$pShopId,$country] = explode('.',$shop->platform_shop_id);
    return ['shop_id' => $pShopId, 'country' => $country];
}

function getLazadaToken($shop = null){
    // dd($shop);
    return $shop ? $shop->shop_token: Auth::user()->currentShop->shop_token;
}

function getLazadaAccessToken($shop = null){
    return getLazadaToken($shop)->access_token;
}

function setShopCacheName($string,$shop = null){
    if($shop){
        $current_shop_id = $shop->id;
    }
    elseif(!Auth::user()->current_shop_id){
        throw new Exception('No current_shop_id');
    }else{
        $current_shop_id = Auth::user()->current_shop_id;
    }

    return $string.'_'.$current_shop_id;
}