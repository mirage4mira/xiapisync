<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['shop_id','platform_item_id','platform_variation_id','inbound','safety_stock','days_to_supply'];

        
    public static $shopStockSettings = [
        'default_cogs_percentage',
        'default_prep_cost',
        'default_days_to_supply',
        'default_safety_stock',
    ];
    public function costs(){
        return $this->hasMany('App\StockCost');
    }

    public function inbound_orders(){
        return $this->belongsToMany('App\InboundOrder')->withPivot('quantity');
    }

    public function prep_costs(){
        return $this->hasMany('App\StockPrepCost');
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
