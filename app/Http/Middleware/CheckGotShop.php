<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class CheckGotShop
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {   

        if(!Auth::user()->current_shop_id){
            if(Auth::user()->shops()->count()){
            setShopsSession();
            Auth::user()->current_shop_id = setShopsSession()[0]['id'];
            Auth::user()->save();
            setShopSettingSession();
            }else{
                return redirect('/sign-in-platform');
            }
        }else{
            if(!getShopsSession())setShopsSession();
            if(!getShopSettingSession())setShopSettingSession();

        }

        return $next($request);
    }
}
