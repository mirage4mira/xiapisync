<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Shop;
use App\ShopSetting;
use Auth;

class ShopController extends Controller
{
    public function signIn(){

        $path = '/api/v1/shop/auth_partner';

        $platform = 'SHOPEE';
        $platformAuthToken = generate_token();
        session()->put('platform_auth_token',$platformAuthToken);
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

        $token = session()->pull('platform_auth_token');

        if($platformAuthToken !== $token)abort(404);

        $shopExistedWithNewUser = false;
        $shop = Shop::firstOrNew(['platform_shop_id' => $request->shop_id,'platform' => $platform]);
        if($shop->id){
            if($shop->users()->where('users.id',Auth::id())->count()){
                return redirect('/')->with('errors',['Error: Shop already been added']);
            }else{
                $shopExistedWithNewUser = true;
            }
        }else{
            $shop->save();
        }
        Auth::user()->shops()->attach($shop);
        Auth::user()->current_shop_id = $shop->id;
        Auth::user()->save();
        
        setShopsSession();
        if($shopExistedWithNewUser){
            setShopSettingSession();
        }
        
        if(!$shop->settings()->count()) return redirect('/shop-settings-setup');
        return redirect('/');
    }
}
