<?php
namespace App\Http\Controllers;

use App\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
class ShopSettingController extends Controller
{


    function create(Request $request){
        $settings = $request->except('_token');
        $validator = Validator::make($request->all(),ShopSetting::$settingsAndValidations);
        handleValidatorFails( $request,$validator);

        $allSettingsKey = ShopSetting::getSettingsKey();

        foreach($settings as $setting => $value){
            if(in_array($setting,$allSettingsKey)){
                $shopSetting = ShopSetting::firstOrNew(['shop_id' =>Auth::user()->current_shop_id ,'setting' => $setting,'value' => $value]);
                $shopSetting->save();
            }
        }

        setShopSettingSession();

        return response()->json();
    }
}
