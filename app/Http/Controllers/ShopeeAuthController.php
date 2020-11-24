<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShopeeAuthController extends Controller
{
    function auth(){
        $path = '/api/v1/shop/auth_partner';

        $host = env('SHOPEE_PLATFORM_BASE_URL');
        $partnerId = env('SHOPEE_PARTNER_ID');
        $partnerKey = env('SHOPEE_PARTNER_KEY');
        
        $redirectUrl = url('/');
        
        $token = hash('sha256',$partnerKey.$redirectUrl);
        
        return Redirect::to($host.$path.'?'.http_build_query(['id'=>$partnerId,'token'=>$token,'redirect'=>$redirectUrl]));
    }
}
