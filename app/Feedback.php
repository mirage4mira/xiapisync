<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $guarded = [];    
    protected $table = 'feedbacks';

    public function user(){
        return $this->belongsTo('App\User');
    }
}
