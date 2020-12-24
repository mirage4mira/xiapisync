<?php

namespace App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LazadaProductModel extends Model
{
    public $shop;

    public function __construct($shop = null)
    {
        if(!$shop) $shop = Shop::where('platform','LAZADA')->where('id',Auth()->user()->current_shop_id);
        $this->shop = $shop;
    }

    public  function getProducts(){
        $offset = 0;
            $limit = 20;
            $hasProducts = true;
            $products = [];
            while($hasProducts){

                $data = [];
                for($i =0; $i < 5;$i++){
                    $data [] = ['filter' => 'all','offset' => $offset, 'limit' => $limit];
                    $offset += $limit;
                }
                $response = lazada_multiple_async_request('/products/get',$data,"GET",$this->shop);
                
                foreach($response as $singleResponse){
                    if(isset($singleResponse['data']['products'])){
                        $_products = $singleResponse['data']['products'];
                        foreach($_products as $product){
                            $products[] = $product;
                        }
                    }else{
                        $hasProducts = false;
                    }
                }
            }
            // dd($products);
            return $products;
    } 
    public function getBrands(){
        $brands = [];
        $cacheName = 'lazada_item_brands';
        
        if(Cache::has($cacheName)){
            $brands = Cache::get($cacheName);
        }
        else{
            $i = 0;
            $hasBrands = true;
            while($hasBrands){
                $requestData = [];
                for($_i = 0; $_i < 50; $_i++){
                    $requestData [] = ['offset' => $i, 'limit' => 1000];
                    $i += 1000;
                }
                
                $results = (lazada_multiple_async_request('/brands/get',$requestData,'GET',$lazadaShop));
                foreach($results as $result){
                    // dd($result);
                    $_brands = collect($result['data'])->pluck('name');
                    foreach($_brands as $brand){
                        $brands [] = $brand; 
                    }

                    if(!count($result['data'])){
                        $hasBrands = false;
                    }
                }
            }
            
            Cache::put($cacheName,$brands,31536000);


        }
        return $brands = ['No Brand'];
        return $brands;
    }

}
