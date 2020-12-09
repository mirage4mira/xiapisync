<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ShopeeOrderModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

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

        $cacheName = 'orders_detail_'.$start_date->format('Ymd').'_'.$end_date->format('Ymd');
        if(Cache::has($cacheName)){
            $orderDetails =  Cache::get($cacheName);
        }else{
            $orderDetails = (new ShopeeOrderModel($request->status,$start_date,$end_date))->getOrdersList()->getOrdersDetail();
        }
        return response()->json($orderDetails);
    }
}
