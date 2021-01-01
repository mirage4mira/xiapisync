<?php

namespace App;

use App\Scopes\CurrentShopScope;
use App\Scopes\AuthorizedShopScope;
use Illuminate\Database\Eloquent\Model;

class ShopeeStock extends Model
{
    protected $fillable = ['shop_id','platform_item_id','platform_variation_id','inbound','safety_stock','days_to_supply'];
        
    public function costs(){
        return $this->hasMany('App\StockCost','stock_id')->where('stock_table_name','shopee_stocks');
    }

    public function stock_syncs(){
        return $this->hasMany('App\StockSync');
    }

    public function inbound_orders(){
        return $this->belongsToMany('App\InboundOrder','inbound_order_stock','stock_id')->where('inbound_order_stock.stock_table_name','shopee_stocks')->withPivot('quantity');
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
    
    protected static function booted()
    {
        static::addGlobalScope(new CurrentShopScope);
        static::addGlobalScope(new AuthorizedShopScope);
    }
}
