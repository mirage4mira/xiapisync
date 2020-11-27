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
        if(!getShopSession()){
            if(Auth::user()->shops()->count()){
            setShopSession(Auth::user()->shops()->first()->getShopInfo());
            }else{
                return redirect('/sign-in-platform');
            }
        }

        return $next($request);
    }
}
