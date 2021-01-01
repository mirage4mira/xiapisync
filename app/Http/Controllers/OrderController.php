<?php

namespace App\Http\Controllers;

use App\LazadaOrderModel;
use Illuminate\Http\Request;
use App\ShopeeOrderModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Auth;

class OrderController extends Controller
{
    public function get(Request $request){

        if(Auth::user()->currentShop->platform == "SHOPEE"){
            $validator = Validator::make($request->all(),[
                'status' => 'required|in:'.implode(',',ShopeeOrderModel::$statuses),
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);
            handleValidatorFails( $request,$validator);
            
            $start_date = new Carbon($request->start_date);
            $end_date = new Carbon($request->end_date);
            
            $cacheName = setShopCacheName('orders_detail');
            $hasCache = Cache::has($cacheName);
            
            if($hasCache)$cache = Cache::get($cacheName);
            
            if(checkLastSyncTime() == true && isset($cache) && $cache['start_date'] == $start_date->format('Ymd') && $cache['end_date'] == $end_date->format('Ymd')){
                $orderDetails = $cache['orders'];  
            }else{
                $orderDetails = (new ShopeeOrderModel($start_date,$end_date))->getOrdersList($request->status)->getOrdersDetail();
                Cache::put($cacheName, ['start_date' => $start_date->format('Ymd'), 'end_date' => $end_date->format('Ymd'), 'orders' => $orderDetails], env('CACHE_DURATION'));
                updateLastSyncTimeCookie();
            }
            return response()->json($orderDetails);
        }
        elseif(Auth::user()->currentShop->platform == "LAZADA"){
            $validator = Validator::make($request->all(),[
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);

            handleValidatorFails( $request,$validator);

            
            $start_date = (new Carbon($request->start_date))->startOfDay();
            $end_date = (new Carbon($request->end_date))->endOfDay();
            
            $cacheName = setShopCacheName('orders_detail');
            $hasCache = Cache::has($cacheName);
            
            if($hasCache)$cache = Cache::get($cacheName);
            
            if(checkLastSyncTime() == true && isset($cache) && $cache['start_date'] == $start_date->format('Ymd') && $cache['end_date'] == $end_date->format('Ymd')){
                $orderDetails = $cache['orders'];  
            }else{
                $orderDetails = (new LazadaOrderModel(true,$start_date,$end_date))->getOrders()->paidOrders()->getOrdersItems()->orders;
                Cache::put($cacheName, ['start_date' => $start_date->format('Ymd'), 'end_date' => $end_date->format('Ymd'), 'orders' => $orderDetails], env('CACHE_DURATION'));
                updateLastSyncTimeCookie();
            }

            return response()->json($orderDetails);
        }
    }
}
