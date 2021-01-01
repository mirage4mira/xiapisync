<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    use SoftDeletes;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded= [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $dates = [
        'deleted_at'
    ];

    public function shops()
    {
        return $this->belongsToMany('App\Shop');
    }

    public function currentShop(){
        return $this->belongsTo('App\Shop','current_shop_id');
    }

    public function updatePlanExpiryDate($payment){
        $remainingDays = now()->diffInDays($this->plan_expiry_date);
        $new_plan_expiry_date = Carbon::parse($payment->pay_time)->addDays(365)->addDays($remainingDays);
        $this->plan_expiry_date = $new_plan_expiry_date;
        $this->save(); 
    }

    public function planExpired(){
        if(Carbon::parse(auth()->user()->plan_expiry_date)->lt(now())){
            return true;   
        }
        return false;
    }
}
