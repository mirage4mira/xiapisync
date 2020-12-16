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

    public function __construct(string $status, Carbon $startdate, Carbon $enddate)
    {

        $this->status = $status;
        $this->startdate = $startdate->startOfDay();
        $this->enddate = $enddate->endOfDay();
        $this->timestamp = time();
    }

    private function getDateRanges()
    {
        $date = $this->startdate->copy();
        $this->dayInterval = 14;
        $dayInterval = $this->dayInterval;
        $dateRanges = [];
        $i = 0;

        while ($date->lt($this->enddate)) {
            $dateRanges[$i]['start_date'] = $date->copy()->startOfDay();
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

    public function getOrdersList()
    {

        $path = '/api/v1/orders/get';
        $this->getDateRanges();
        $datas = [];
  
        foreach ($this->dateRanges as $dateRange) {

            if ($this->status === 'PAID') {

                $statuses = ['READY_TO_SHIP', 'SHIPPED','IN_CANCEL', 'COMPLETED'];
                foreach ($statuses as $status) {
                    $data = [
                        'order_status' => $status,
                        'partner_id' => shopee_partner_id(),
                        'shopid' => shopee_shop_id(),
                        'timestamp' => $this->timestamp,
                        'create_time_from' => $dateRange['start_date']->timestamp,
                        'create_time_to' => $dateRange['end_date']->timestamp,
                    ];
                    $datas[] = $data;
                }
            } else {
                $data = [
                    'order_status' => $this->status,
                    'partner_id' => shopee_partner_id(),
                    'shopid' => shopee_shop_id(),
                    'timestamp' => $this->timestamp,
                    'create_time_from' => $dateRange['start_date']->timestamp,
                    'create_time_to' => $dateRange['end_date']->timestamp,
                ];
                $datas[] = $data;
            }
        }
        $contents = shopee_multiple_async_post($path, $datas);
        unset($datas);
 
        $this->orderLists = collect($contents)->pluck('orders')->flatten(1);

        return $this;
    }

    public function getOrdersDetail()
    {
        $cacheName = 'completed_orders_detail_'.Auth::id();

        if(Cache::has($cacheName)){
            $cachedCompletedOrdersDetail = Cache::get($cacheName);
        }else{
            $cachedCompletedOrdersDetail = [];
        }
        $cachedCompletedOrdersDetailOrderSn = collect($cachedCompletedOrdersDetail)->pluck("ordersn")->toArray(); 
        $ordersToGet = [];
        foreach($this->orderLists as $orderList){
            if(!in_array($orderList['ordersn'],$cachedCompletedOrdersDetailOrderSn)){
                $ordersToGet [] = $orderList['ordersn'];
            }
        }

        $ordersDetail = $cachedCompletedOrdersDetail;

        $path = '/api/v1/orders/detail';

        $datas = [];
        \Log::alert(count($ordersToGet));
        $ordersSnChunk = collect($ordersToGet)->chunk(50)->toArray();
        foreach ($ordersSnChunk as $ordersSn) {
            $data = [
                'ordersn_list' => collect($ordersSn)->values()->toArray(),
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
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

        $stocks = Stock::with('costs')->where('shop_id', auth()->user()->current_shop_id)->get();
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
        $cacheName = 'orders_escrow_detail_'.Auth::id();

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
                    'shopid' => shopee_shop_id(),
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
