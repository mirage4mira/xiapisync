<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockCost extends Model
{
    protected $fillable = ['stock_id','from_date','cost'];
    
    protected $casts = [
        'cost' => 'float',
    ];
}
