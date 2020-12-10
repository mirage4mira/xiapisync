<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ShopeeOrderModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Auth;

class ShopeeOrderController extends Controller
{
    public function getOrdersEsrowDetail(Request $request){

        $validator = Validator::make($request->all(),[
            'status' => 'required|in:'.implode(',',ShopeeOrderModel::$statuses),
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        
        handleValidatorFails( $request,$validator);
        
        $start_date = new Carbon($request->start_date);
        $end_date = new Carbon($request->end_date);
        
        $cacheName = 'orders_detail_'.Auth::id();
        $hasCache = Cache::has($cacheName);
        
        if($hasCache)$cache = Cache::get($cacheName);
        
        if(checkLastSyncTime() == false){
            $orderDetails = (new ShopeeOrderModel($request->status,$start_date,$end_date))->getOrdersList()->getOrdersDetail();
        }
        elseif(isset($cache) && $cache['start_date'] == $start_date->format('Ymd') && $cache['end_date'] == $end_date->format('Ymd')){
            $orderDetails = $cache['orders'];  
        }else{
            $orderDetails = (new ShopeeOrderModel($request->status,$start_date,$end_date))->getOrdersList()->getOrdersDetail();
        }
        return response()->json($orderDetails);
    }
}
