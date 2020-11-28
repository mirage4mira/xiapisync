<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    public static $shopStockSettings = [
        'default_cogs_percentage',
        'default_prep_cost',
        'default_days_to_supply',
        'default_safety_stock',
    ];
    public function costs(){
        return $this->hasMany('App\StockCost');
    }

    public function prep_costs(){
        return $this->hasMany('App\StockPrepCost');
    }

    public static function getDefaultStockSettings(){
        // \Log::alert(getShopSettingSession()->whereIn('setting',self::$shopStockSettings));
        return getShopSettingSession()->whereIn('setting',self::$shopStockSettings);
    }

    public function getCostAttribute(){
        $cost = $this->costs()->orderBy('from_date','DESC')->first(); 
        return $cost->cost; 
    }
    
    public function getPrepCostAttribute(){
        $prep_cost = $this->prep_costs()->orderBy('from_date','DESC')->first();
        return $prep_cost->prep_cost;
    }
    
}
