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

    public function __construct( Carbon $startdate, Carbon $enddate,$shop = null)
    {

        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->timestamp = time();
        $this->shop = $shop;
    }

    private function getDateRanges($reserve_time = false)
    {

        $date = $this->startdate->copy();
        $this->dayInterval = 14;
        $dayInterval = $this->dayInterval;
        $dateRanges = [];
        $i = 0;

        while ($date->lt($this->enddate)) {
            $dateRanges[$i]['start_date'] = $date->copy();
            if(!$reserve_time){
                $dateRanges[$i]['start_date'] = $dateRanges[$i]['start_date']->startOfDay();
            }

            if ($date->diffInDays($this->enddate) < $dayInterval) {
                $dayInterval = $date->diffInDays($this->enddate);
            }
            $date->addDays($dayInterval);
            $dateRanges[$i]['end_date'] = $date->copy()->endOfDay();
            $date->addDays(1);
            $i++;
        }

        $this->dateRanges = $dateRanges;
    }

    public function getOrdersList2(){
        $path = '/api/v1/orders/basics';
        $this->getDateRanges();
        $datas = [];
        
        foreach ($this->dateRanges as $dateRange) {


            $data = [
                'partner_id' => shopee_partner_id(),
                'shopid' => intval(shopee_shop_id($this->shop)),
                'timestamp' => $this->timestamp,
                'update_time_from' => $dateRange['start_date']->timestamp,
                'update_time_to' => $dateRange['end_date']->timestamp,
            ];
            $datas[] = $data;
        }
    $contents = shopee_multiple_async_post($path, $datas);
        unset($datas);

        $this->orderLists = collect($contents)->pluck('orders')->flatten(1);
        return $this;
    }

    public function getOrdersList($_status)
    {
        $path = '/api/v1/orders/get';
        $this->getDateRanges();
        $datas = [];

        foreach ($this->dateRanges as $dateRange) {

            if ($_status === 'PAID') {
                $statuses = ['READY_TO_SHIP', 'SHIPPED','IN_CANCEL', 'TO_CONFIRM_RECEIVE','COMPLETED'];
                foreach ($statuses as $status) {
                    $data = [
                        'order_status' => $status,
                        'partner_id' => shopee_partner_id(),
                        'shopid' => intval(shopee_shop_id($this->shop)),
                        'timestamp' => $this->timestamp,
                        'create_time_from' => $dateRange['start_date']->timestamp,
                        'create_time_to' => $dateRange['end_date']->timestamp,
                    ];
                    $datas[] = $data;
                }
            } else {
                $data = [
                    'order_status' => $_status,
                    'partner_id' => shopee_partner_id(),
                    'shopid' => intval(shopee_shop_id($this->shop)),
                    'timestamp' => $this->timestamp,
                    'create_time_from' => $dateRange['start_date']->timestamp,
                    'create_time_to' => $dateRange['end_date']->timestamp,
                ];
                $datas[] = $data;
            }
        }
        \Log::alert($datas);
        $contents = shopee_multiple_async_post($path, $datas);
        unset($datas);
 
        $this->orderLists = collect($contents)->pluck('orders')->flatten(1);

        return $this;
    }

    public function getOrdersDetail()
    {
        $cacheName = setShopCacheName('completed_orders_detail',$this->shop);

        if(Cache::has($cacheName)){
            $cachedCompletedOrdersDetail = Cache::get($cacheName);
        }else{
            $cachedCompletedOrdersDetail = [];
        }
        $cachedCompletedOrdersDetailOrderSn = collect($cachedCompletedOrdersDetail)->pluck("ordersn")->toArray(); 
        // \Log::alert(count($cachedCompletedOrdersDetailOrderSn));
        \Log::alert(collect($this->orderLists)->where('ordersn','201223CNDEXFKR')->count());
        $ordersToGet = [];
        foreach($this->orderLists as $orderList){
            if(!in_array($orderList['ordersn'],$cachedCompletedOrdersDetailOrderSn)){
                $ordersToGet [] = $orderList['ordersn'];
            }
        }
        // \Log::alert(count($ordersToGet));
        
        $ordersDetail = $cachedCompletedOrdersDetail;

        $path = '/api/v1/orders/detail';

        $datas = [];

        $ordersSnChunk = collect($ordersToGet)->chunk(50)->toArray();
        foreach ($ordersSnChunk as $ordersSn) {
            $data = [
                'ordersn_list' => collect($ordersSn)->values()->toArray(),
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id($this->shop),
                'timestamp' => $this->timestamp,
            ];
            $datas[] = $data;
        }

        $responseData = shopee_multiple_async_post($path, $datas);

        foreach ($responseData as $data) {
            foreach ($data['orders'] as $orderDetail) {
                $ordersDetail[] = $orderDetail;
                if($orderDetail['order_status'] == "COMPLETED"){
                    $cachedCompletedOrdersDetail[] = $orderDetail; 
                }
            }
        }

        Cache::put($cacheName,$cachedCompletedOrdersDetail,now()->addYear());

        $ordersEscrowDetail = $this->getOrdersEscrowDetail();

        $ordersListOrderSn = $this->orderLists->pluck('ordersn')->toArray();
        $ordersDetail = collect($ordersDetail)->filter(function($orderDetail) use ($ordersListOrderSn){
            // \Log::alert($orderDetail);
            return in_array($orderDetail['ordersn'],$ordersListOrderSn);
        })->values()->toArray();
        // \Log::alert($ordersDetail);
        if($this->shop){
            $shop_id = $this->shop->id;
        }else{
            $shop_id = auth()->user()->current_shop_id;
        }
        $stocks = ShopeeStock::with('costs')->where('shop_id', $shop_id)->get();
  
        foreach ($ordersDetail as $key => $orderDetail) {
            $item_count = 0;
            $item_amount = 0;
            foreach ($ordersEscrowDetail as $orderEscrowDetail) {
                if ($orderDetail['ordersn'] === $orderEscrowDetail['ordersn']) {
                    $ordersDetail[$key]['_escrow_detail'] = $orderEscrowDetail;
                    break;
                }
            }
            foreach ($orderDetail['items'] as $key2 => $item) {
                $item_count +=  $item['variation_quantity_purchased'];
                $item_amount += $item['variation_quantity_purchased'] * $item['variation_discounted_price'];
                foreach ($stocks as $stock) {
                    if ($item['item_id'] == $stock->platform_item_id && $item['variation_id'] == $stock->platform_variation_id) {
                        $cost = $stock->costs->where('from_date', '<=', gmdate("Y-m-d", $orderDetail['create_time']))->sortByDesc('from_date')->first();
                        $ordersDetail[$key]['items'][$key2]['_append']['cost'] = $cost->cost;
                        break;
                    }
                    else{
                        $ordersDetail[$key]['items'][$key2]['_append']['cost'] = 0;
                    }
                }
            }
            $ordersDetail[$key]['_append']['item_count'] = $item_count;
            $ordersDetail[$key]['_append']['item_amount'] = $item_amount;
        }

        foreach($ordersDetail as $key => $orderDetail){
            if($orderDetail['_escrow_detail']['activity']){
                foreach($orderDetail['_escrow_detail']['activity'] as $activity){
                    $ordersDetail[$key]['_append']['item_amount'] += $activity['discounted_price'];
                }
            }
        }


        return $ordersDetail;
    }

    public function getOrdersEscrowDetail()
    {   
        $cacheName = setShopCacheName('orders_escrow_detail',$this->shop);

        if(Cache::has($cacheName)){
            $orders_escrow_detail = Cache::get($cacheName);
        }else{
            $orders_escrow_detail = [];
        }
        $orders_escrow_detail_ordersn = collect($orders_escrow_detail)->pluck('ordersn')->toArray();

        $orders_escrow_detail_ordersn_to_get = [];


        foreach($this->orderLists as $orderList){
            if(!in_array($orderList['ordersn'],$orders_escrow_detail_ordersn)){
                $orders_escrow_detail_ordersn_to_get [] = $orderList['ordersn'];
            }
        }
        
        if(count($orders_escrow_detail_ordersn_to_get)){
            $path = '/api/v1/orders/my_income';

            $datas = [];
            foreach ($orders_escrow_detail_ordersn_to_get as $ordersn) {
                $data = [
                    'ordersn' => $ordersn,
                    'partner_id' => shopee_partner_id(),
                    'shopid' => intval(shopee_shop_id($this->shop)),
                    'timestamp' => $this->timestamp,
                ];
                $datas[] = $data;
            }

            $responseData = shopee_multiple_async_post($path, $datas);
        
            foreach ($responseData as $data) {
                $orders_escrow_detail[] = $data['order'];
            }
        }

        Cache::put($cacheName,$orders_escrow_detail,now()->addYear());

        return $orders_escrow_detail;
    }

    public function getOrdersIncomeDetail()
    {

        $orderDetails = [];
        $orderLists = self::getOrdersList();
        $path = '/api/v1/orders/income';

        $datas = [];
        foreach ($orderLists as $key => $orderList) {
            $data = [
                'ordersn' => $orderList['ordersn'],
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $datas[] = $data;
        }
        $contents = shopee_multiple_async_post($path, $datas);

        return $contents;
    }
}
