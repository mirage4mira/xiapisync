<?php

namespace App;

use App\Scopes\AuthorizedShopScope;
use App\Scopes\CurrentShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class InboundOrder extends Model
{   
    protected $fillable = [
        'shop_id',
        'supplier_name',
        'payment_date',
        'reference',
        'days_to_supply',
    ];
    public function shopee_stocks(){
        return $this->belongsToMany('App\ShopeeStock','inbound_order_stock','inbound_order_id','stock_id')->where('stock_table_name','shopee_stocks')->withPivot('quantity')->withPivot('cost')->withTimestamps();
    }

    public function lazada_stocks(){
        return $this->belongsToMany('App\LazadaStock','inbound_order_stock','inbound_order_id','stock_id')->where('stock_table_name','lazada_stocks')->withPivot('quantity')->withPivot('cost')->withTimestamps();
    }

    public function getDaysToArriveAttribute(){
        return now()->diffInDays(\Carbon\Carbon::parse($this->payment_date)->addDays($this->days_to_supply),false);
    }

    protected static function booted()
    {
        static::addGlobalScope(new CurrentShopScope);
        static::addGlobalScope(new AuthorizedShopScope);
    }
}
