<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ShopeeProductModel extends Model
{   

    public $timestamp;
    public $itemsList;

    public function __construct(){
        $this->timestamp = time();
    }

    public function getItemsList(){
        $path = '/api/v1/items/get';

        $data = [
            'pagination_offset' => 0,
            'pagination_entries_per_page' => 100,
            'partner_id' => shopee_partner_id(),
            'shopid' => shopee_shop_id(),
            'timestamp' => $this->timestamp,
        ];
        $responseData = shopee_http_post($path,$data)->json();
        $this->itemsList = $responseData['items']; 
        return $responseData;
    }

    public function getItemsDetail(){
        $path = '/api/v1/item/get';
        $this->getItemsList();
        $datas = [];
        foreach($this->itemsList as $itemList){
            $data = [
                'item_id' => intval($itemList['item_id']),
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $datas [] = $data;
        }
    $contents = shopee_multiple_async_post($path,$datas);
        return $contents;
    }

    public static function updateStockData(int $product_id, int $stock_quantity, int $variation_id = null){
        return get_defined_vars();
    }

    public function updateStock(array $updateStockData){
        $updateStockPath = '/api/v1/items/update_stock';
        $updateVariationStockPath = '/api/v1/items/update_variation_stock';
        
        $updatedItems = [];
        foreach($updateStockData as $stockData){
            $data = [
                'item_id' => $stockData['product_id'],
                'stock' => $stockData['stock_quantity'],
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            if(isset($stockData['variation_id'])){
                $data['variation_id'] = $stockData['variation_id'];
                $responseData = shopee_http_post($updateVariationStockPath,$data)->json();
            }else{
                $responseData = shopee_http_post($updateStockPath,$data)->json();
            }
            $updatedItems[] = $responseData['item']; 
        }
    }
}
