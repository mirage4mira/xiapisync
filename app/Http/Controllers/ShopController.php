<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Shop;
use App\ShopToken;
use Auth;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;

class ShopController extends Controller
{
    public function signIn(){

        $path = '/api/v1/shop/auth_partner';

        $platforms = ['SHOPEE','LAZADA'];
        $platformAuthToken = generate_token();
        session()->put('platform_auth_token',$platformAuthToken);

        $redirectDatas = [];
        foreach($platforms as $platform){
            $redirectDatas[] = \Crypt::encrypt(serialize([$platformAuthToken,$platform]));
        }
        $redirectToBaseUrl = '/shop/add';
        // dd($redirectDatas);
        $shopeeRedirectUrl = URL::to($redirectToBaseUrl.'?'.http_build_query(['d'=>$redirectDatas[0]]));
        $shopeeToken = hash('sha256',shopee_partner_key().$shopeeRedirectUrl);
        $shopeeAuthLink = shopee_url().$path.'?'.http_build_query(['id' => shopee_partner_id() ,'token' => $shopeeToken,'redirect'=>$shopeeRedirectUrl]);

        // dd(URL::to($redirectToBaseUrl.'?'.http_build_query(['d'=>$redirectDatas[1]])));
        $lazadaRedirectUrl = 'https://xiapisync.com'.($redirectToBaseUrl.'?'.http_build_query(['d'=>$redirectDatas[1]]));
        // $lazadaRedirectUrl = URL::to($redirectToBaseUrl.'?'.http_build_query(['d'=>$redirectDatas[1]]));
        // $lazadaAuthLink = 'https://auth.lazada.com/oauth/authorize'.'?'.http_build_query(['response_type' => 'code' ,'force_auth' => true,'redirect_uri'=> 'https://xiapisync.com'.$lazadaRedirectUrl,'client_id' => env('LAZADA_APP_KEY')]);

        $lazadaAuthLink = 'https://auth.lazada.com/oauth/authorize'.'?'.http_build_query(['response_type' => 'code' ,'force_auth' => true,'redirect_uri'=> URL::to($lazadaRedirectUrl),'client_id' => env('LAZADA_APP_KEY')]);
        return view('sign-in-platform',compact('shopeeAuthLink','lazadaAuthLink'));
    }

    public function addShop(Request $request){
        #validation
        $data = \Crypt::decrypt($request->d);
        [$platformAuthToken,$platform] = unserialize($data);

        $token = session()->pull('platform_auth_token');

        if($platformAuthToken !== $token)abort(404);
        
        if($platform == "SHOPEE"){
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

        }elseif($platform == "LAZADA"){
            
            $client = new LazopClient("https://auth.lazada.com/rest", env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            $_request = new LazopRequest("/auth/token/create");
            $_request->addApiParam("code",$request->code );
            $response = json_decode($client->execute($_request),true);
            // dd($response);
            $shops = $response['country_user_info'];
            $shopToken = ShopToken::create(collect($response)->except(['country_user_info','code','request_id'])->toArray());
            foreach($shops as $shop){
                $shop = Shop::firstOrNew(['platform_shop_id' => $shop['seller_id'].'.'.$shop['country'],'platform' => $platform,'shop_token_id' =>$shopToken->id ]);
                $shop->save();
                Auth::user()->shops()->attach($shop);
            }
            Auth::user()->current_shop_id = $shop->id;
            Auth::user()->save();
            setShopsSession();
            
        }

        
        // if(!$shop->settings()->count()) return redirect('/shop-settings-setup');
        return redirect('/');
    }

    public function change(Request $request){
        
        $shop = Shop::join('shop_user','shops.id','=','shop_user.shop_id')->where('shops.id',$request->id)->where('shop_user.user_id',Auth()->user()->id)->first();
        if($shop){
            Auth::user()->current_shop_id = $shop->id;
            Auth::user()->save();
        }
        return redirect()->back()->with('success_msgs',['Shop successfully changed']);
    }

}
