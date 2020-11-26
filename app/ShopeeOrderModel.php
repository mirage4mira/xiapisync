<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


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
        7 => 'TO_RETURN'
    ];

    public function __construct(string $status,Carbon $startdate,Carbon $enddate){
        $this->status = $status;
        $this->startdate = $startdate;
        $this->enddate = $enddate;
        $this->timestamp = time();
  
    }

    private function getDateRanges(){
        $date = $this->startdate;
        $this->dayInterval = 14;
        $dayInterval = $this->dayInterval;
        $dateRanges = [];
        $i = 0;

        while($date->lt($this->enddate)){
            $dateRanges[$i]['start_date'] = $date->copy()->startOfDay();
            if($date->diffInDays($this->enddate) < $dayInterval){
                $dayInterval = $date->diffInDays($this->enddate) + 1;
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
        $contents = shopee_multiple_async_post($path,$datas);
        $orderLists = collect($contents)->pluck('orders')->flatten(1);
        return $orderLists;
    }

    public function getOrdersDetail(){
        
        $orderLists = self::getOrdersList();
        $orderDetails = [];
        $orderSNs = [];
        $path = '/api/v1/orders/detail';
        $data = [
            'ordersn_list' => array_column($orderLists,'ordersn'),
            'partner_id' => shopee_partner_id(),
            'shopid' => shopee_shop_id(),
            'timestamp' => $this->timestamp,
        ];
        $responseData = shopee_http_post($path,$data)->json();
        if(array_key_exists('orders',$responseData)){
            foreach($responseData['orders'] as $orderDetail){
                $orderDetails[] = $orderDetail;
            }
        }
        return $orderDetails;
    }

    public function getOrdersEscrowDetail(){

        $orderLists = self::getOrdersList();
        $path = '/api/v1/orders/my_income';
        $datas = [];
        foreach($orderLists as $key => $orderList){
            
            $data = [
                'ordersn' => $orderList['ordersn'],
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $datas [] = $data;
        }
        
        $ordersDetails = shopee_multiple_async_post($path,$datas);
        foreach($ordersDetails as $key => $orderDetail){
            foreach($orderLists as $order){
                if($orderDetail['order']['ordersn'] == $order['ordersn']){
                    $ordersDetails[$key]['order']['update_time'] = $order['update_time']; 
                    $ordersDetails[$key]['order']['order_status'] = $order['order_status']; 
                }
            }
        }
        return $ordersDetails;
    
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
