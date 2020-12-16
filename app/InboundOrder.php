<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InboundOrder extends Model
{   
    protected $fillable = [
        'shop_id',
        'supplier_name',
        'payment_date',
        'reference',
        'days_to_supply',
    ];
    public function stocks(){
        return $this->belongsToMany('App\Stock')->withPivot('quantity')->withPivot('cost')->withTimestamps();
    }

    public function getDaysToArriveAttribute(){
        return now()->diffInDays(\Carbon\Carbon::parse($this->payment_date)->addDays($this->days_to_supply),false);
    }
}
