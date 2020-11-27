<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = ['shop_id','setting','value'];
    
    public static $settingsAndValidations = [
        'default_cogs_percentage' => 'required|numeric|min:0|max:100',
        'default_prep_cost' => 'required|numeric|min:0',
        'default_days_to_supply' => 'required|integer|min:0',
        'default_safely_stock' => 'required|integer|min:0',
    ];

    public static function getSettingsKey(){
        return array_keys(self::$settingsAndValidations);
    }

    public static function getSettingValue(string $setting){
        return self::where('setting',$setting)->where('shop_id',getShopSession()['id'])->first()->value;
    }

    public static function getSettingsValue(array $settings){
        return self::whereIn('setting',$settings)->where('shop_id',getShopSession()['id'])->get();
    }
}
