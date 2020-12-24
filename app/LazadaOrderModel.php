<?php

namespace App;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class LazadaOrderModel extends Model
{
    public $byCreated;
    public $start_time;
    public $end_time;
    public $orders;

    public function __construct($byCreated = true, Carbon $start_time, Carbon $end_time,$shop)
    {
        $this->byCreated = $byCreated;
        $this->start_time = $start_time->toIso8601String();
        $this->end_time = $end_time->toIso8601String();
        $this->shop = $shop;
    }

    public function getOrders($status = null){
        $offset = 0;
        $limit = 100;
        $hasOrders = true;
        $orders = [];
        while($hasOrders){

            $data = [];
            for($i =0; $i < 5;$i++){
                
                $d = ['offset' => $offset, 'limit' => $limit];
                if($status ) $d['status'] = $status;
                if($this->byCreated){
                    $d['created_before'] = $this->end_time;
                    $d['created_after']= $this->start_time;
                }else{
                    $d['update_before'] = $this->end_time;
                    $d['update_after']= $this->start_time;
                }
                // dd($d);
                $data [] = $d;
                $offset += $limit;
            }

            $response = lazada_multiple_async_request('/orders/get',$data,"GET",$this->shop);
            
            foreach($response as $singleResponse){
                if(isset($singleResponse['data']['orders']) && count($singleResponse['data']['orders'])){
                    $_orders = $singleResponse['data']['orders'];
                    foreach($_orders as $order){
                        $orders[] = $order;
                    }
                }else{
                    $hasOrders = false;
                }
            }
        }
        $this->orders = $orders;        
        return $this;
    }

    public function getOrdersItems(){
        $ordersIdChunk = collect($this->orders)->pluck('order_id')->chunk(100)->toArray();

        $data = [];
        foreach($ordersIdChunk as $ordersId){
            $d['order_ids'] = '['. implode(", ",$ordersId) .']';
            $data [] = $d;
        }

        // dd($data);
        $response = lazada_multiple_async_request('/orders/items/get',$data,"GET",$this->shop);
        
        $orders = [];
        foreach($response as $singleResponse){
            
            if(isset($singleResponse['data'])){
                $_orders = $singleResponse['data'];
                foreach($_orders as $order){
                    $orders[] = $order;
                }
            }
        }

        foreach($orders as $order){
            foreach($this->orders as $key => $_order){
                if($order['order_id'] == $_order['order_id']){
                    $this->orders[$key]['_items'] = $order['order_items'];
                }
            }
        }
        return $this;
    }
}
