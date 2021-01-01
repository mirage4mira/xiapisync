<?php

namespace App;

use App\Scopes\CurrentShopScope;
use App\Scopes\AuthorizedShopScope;
use Illuminate\Database\Eloquent\Model;

class LazadaStock extends Model
{
    protected $guarded = [];

    public function costs(){
        return $this->hasMany('App\StockCost','stock_id')->where('stock_table_name','lazada_stocks');
    }

    public function stock_syncs(){
        return $this->hasMany('App\StockSync');
    }

    public function inbound_orders(){
        return $this->belongsToMany('App\InboundOrder','inbound_order_stock','stock_id')->where('inbound_order_stock.stock_table_name','lazada_stocks')->withPivot('quantity');
    }

    protected static function booted()
    {
        static::addGlobalScope(new CurrentShopScope);
        static::addGlobalScope(new AuthorizedShopScope);
    }

}
