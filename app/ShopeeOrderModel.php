<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Auth;

class ShopeeOrderModel extends Model
{   
    public $startDate;
    public $endDate;
    public $status;
    public $dayInterval = 15;
    public $timestamp;
    public $dateRanges;

    public static $statuses = [
        1 => 'ALL',
        2 => 'UNPAID',
        3 => 'READY_TO_SHIP',
        4 => 'COMPLETED',
        5 => 'IN_CANCEL',
        6 => 'CANCELLED',
        7 => 'TO_RETURN',
        8 => 'PAID'
    ];

    public function __construct(string $status,Carbon $startdate,Carbon $enddate){

        $this->status = $status;
        $this->startdate = $startdate->startOfDay();
        $this->enddate = $enddate->endOfDay();
        $this->timestamp = time();
  
    }

    private function getDateRanges(){
        $date = $this->startdate->copy();
        $this->dayInterval = 14;
        $dayInterval = $this->dayInterval;
        $dateRanges = [];
        $i = 0;

        while($date->lt($this->enddate)){
            $dateRanges[$i]['start_date'] = $date->copy()->startOfDay();
            if($date->diffInDays($this->enddate) < $dayInterval){
                $dayInterval = $date->diffInDays($this->enddate);
            }
            $date->addDays($dayInterval);
            $dateRanges[$i]['end_date'] = $date->copy()->endOfDay();
            $date->addDays(1);
            $i++;
        }
        $this->dateRanges = $dateRanges;
    }
    
    public function getOrdersList(){
        $path = '/api/v1/orders/get';
        $this->getDateRanges();
        $orderLists = [];
        $datas = [];
        
        foreach($this->dateRanges as $dateRange){
            
            if($this->status === 'PAID'){
                
                $statuses = ['READY_TO_SHIP','RETRY_SHIP','SHIPPED','TO_CONFIRM_RECEIVE','IN_CANCEL','COMPLETED'];
                foreach($statuses as $status){
                    $data = [
                        'order_status' => $status,
                        'partner_id' => shopee_partner_id(),
                        'shopid' => shopee_shop_id(),
                        'timestamp' => $this->timestamp,
                        'create_time_from' => $dateRange['start_date']->timestamp,
                        'create_time_to' => $dateRange['end_date']->timestamp,
                    ];
                    $datas [] = $data;    
                }

            }else{
                $data = [
                    'order_status' => $this->status,
                    'partner_id' => shopee_partner_id(),
                    'shopid' => shopee_shop_id(),
                    'timestamp' => $this->timestamp,
                    'create_time_from' => $dateRange['start_date']->timestamp,
                    'create_time_to' => $dateRange['end_date']->timestamp,
                ];
                $datas [] = $data;
            }
        }
        $contents = shopee_multiple_async_post($path,$datas);
        $orderLists = collect($contents)->pluck('orders')->flatten(1);
        $this->orderLists = $orderLists; 
        return $this;
    }

    public function getOrdersDetail(){
        
        $orderLists = $this->orderLists;
        $ordersDetail = [];

        $path = '/api/v1/orders/detail';
        
        $datas = [];
        $ordersSnChunk = $orderLists->pluck('ordersn')->chunk(50)->toArray();
        foreach($ordersSnChunk as $ordersSn){
            $data = [
                'ordersn_list' => collect($ordersSn)->values()->toArray(),
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $datas [] = $data;
        }
        
        $responseData = shopee_multiple_async_post($path,$datas);
        // return response()->json();
        foreach($responseData as $data){
            foreach($data['orders'] as $orderDetail){
                $ordersDetail[] = $orderDetail;
            }
        }


        $ordersEscrowDetail = $this->getOrdersEscrowDetail();

        foreach($ordersDetail as $key => $orderDetail){
            foreach($ordersEscrowDetail as $orderEscrowDetail){
                if($orderDetail['ordersn'] === $orderEscrowDetail['ordersn']){
                    $ordersDetail[$key]['_escrow_detail'] = $orderEscrowDetail;
                }
            }
        }
        $cachename = 'orders_detail_'.Auth::id(); 
        Cache::put($cachename, ['start_date' =>$this->startdate->format('Ymd'),'end_date' =>$this->enddate->format('Ymd'),'orders' => $ordersDetail], env('CACHE_DURATION'));
        updateLastSyncTime();
        return $ordersDetail;
    }

    public function getOrdersEscrowDetail(){

        $orderLists = $this->orderLists;
        $path = '/api/v1/orders/my_income';
        $datas = [];
        foreach($orderLists as $orderList){
            
            $data = [
                'ordersn' => $orderList['ordersn'],
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $datas [] = $data;
        }

        $ordersEscrowDetail = [];
        $responseData = shopee_multiple_async_post($path,$datas);
        foreach($responseData as $data){
            $ordersEscrowDetail[] = $data['order'];
        }

        return $ordersEscrowDetail;
    }

    public function getOrdersIncomeDetail(){

        $orderDetails = [];
        $orderLists = self::getOrdersList();
        $path = '/api/v1/orders/income';

        $datas = [];
        foreach($orderLists as $key => $orderList){
            $data = [
                'ordersn' => $orderList['ordersn'],
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $datas[] = $data; 
        }
        $contents = shopee_multiple_async_post($path,$datas);
        
        return $contents;
    }
}
