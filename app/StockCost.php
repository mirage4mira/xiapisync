<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockCost extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'cost' => 'float',
    ];
}
