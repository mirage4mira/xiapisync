<?php

namespace App\Http\Middleware;

use Closure;
use App\ShopSetting;

class CheckSettingsKey
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
        // \Auth::logout();
        // session()->flush();
        // return redirect('');
        $settingsKey = array_keys(getShopSettingSession());
        $allSettingsKey = ShopSetting::getSettingsKey();
        
        foreach($allSettingsKey as $key){
            if(!in_array($key,$settingsKey)){
                return redirect('/shop-settings-setup')->withErrors(['The system is missing settings. Try to reenter the settings again!']);
            }
        }
        return $next($request);
    }
}
