<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Shop;
use Auth;

class ShopController extends Controller
{
    public function signIn(){

        $path = '/api/v1/shop/auth_partner';

        $platform = 'SHOPEE';
        $platformAuthToken = generate_token();
        session('platform_auth_token',$platformAuthToken);
        $d = \Crypt::encrypt(serialize([$platformAuthToken,$platform]));
        $redirectUrl = URL::to('/add-shop?'.http_build_query(['d'=>$d]));
        $token = hash('sha256',shopee_partner_key().$redirectUrl);
        $shopeeAuthLink = shopee_url().$path.'?'.http_build_query(['id' => shopee_partner_id() ,'token' => $token,'redirect'=>$redirectUrl]);
        return view('sign-in-platform',compact('shopeeAuthLink'));
    }

    public function addShop(Request $request){
        #validation
        $data = \Crypt::decrypt($request->d);
        [$platformAuthToken,$platform] = unserialize($data);
        $token = $request->session()->pull('platform_auth_token');
        $shop = Shop::create(['platform_shop_id' => $request->shop_id,'platform',$platform]);
        // ShopUser::create(['shop_id' => $shop->id,'user_id'=>\Auth::Id()]);
        Auth::user()->shops()->attach($shop);
        setShopSession($shop->getShopInfo());
        return redirect('/');
    }
}
