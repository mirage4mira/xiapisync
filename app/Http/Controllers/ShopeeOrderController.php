<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ShopeeOrderModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ShopeeOrderController extends Controller
{
    public function getOrdersEsrowDetail(Request $request){

        $validator = Validator::make($request->all(),[
            'status' => 'required|in:'.implode(',',ShopeeOrderModel::$statuses),
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        
        handleValidatorFails( $request,$validator);
        

        $orderDetails = (new ShopeeOrderModel($request->status,new Carbon($request->start_date),new Carbon($request->end_date)))->getOrdersEscrowDetail();
        return response()->json($orderDetails);
    }
}
