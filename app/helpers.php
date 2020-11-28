<?php

use Illuminate\Contracts\Queue\ShouldQueue;

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

function shopee_multiple_async_post(string $path, array $datas){

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

        $this->throwValidationException(

            $request, $validator
        );
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
    function shopee_shop_id(){
        if(getShopSession() && getShopSession()['platform'] === 'SHOPEE'){
            return intval(getShopSession()['platform_shop_id']);
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
if(!function_exists('setShopSession')){
    function setShopSession(array $shopInfo){
        Session::put('current_shop_info',$shopInfo);
    }
}

if(!function_exists('getShopSession')){
    function getShopSession(){
        return Session::get('current_shop_info');
    }
}

function setShopSettingSession(){
    $shopSettings = App\ShopSetting::where('shop_id',getShopSession()['id'])->get()->toArray();
    $settings = [];
    foreach($shopSettings as $shopSetting){
        $settings[$shopSetting['setting']] = $shopSetting['value']; 
    }
    // dd($settings);
    Session::put('current_shop_settings',$settings);
}

function getShopSettingSession(){
    // dd(Session::get('current_shop_settings'));
    return Session::get('current_shop_settings');
}