<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ShopeeProductModel;
use App\Stock;

class ShopeeProductController extends Controller
{
    public function getProductsDetail(){
        $contents =  (new ShopeeProductModel)->getItemsDetail();
        $defaultStockSettings =  Stock::getDefaultStockSettings();
        
        $default_cogs_percentage = $defaultStockSettings->where('setting','default_cogs_percentage')->first()->value;
        $default_prep_cost = $defaultStockSettings->where('setting','default_prep_cost')->first()->value;
        $default_days_to_supply = $defaultStockSettings->where('setting','default_days_to_supply')->first()->value;
        $default_safety_stock = $defaultStockSettings->where('setting','default_safety_stock')->first()->value;

        $stocks = Stock::where('shop_id',getShopSession()['id'])->get();  
        foreach($contents as $key1 => $content){
            foreach($stocks as $stock){
                if($content['item']['item_id'] == $stocks['platform_item_id']){
                    if(!empty($content['item']['variations'])){
                        foreach($content['item']['variations'] as $key2 => $variation){
                            if($variation['variation_id'] == $stock['platform_variation_id']){
                                $contents[$key1]['item']['variations'][$key2]['_append']['cost'] = $stock->cost;
                                $contents[$key1]['item']['variations'][$key2]['_append']['prep_cost'] = $stock->prep_cost;
                                $contents[$key1]['item']['variations'][$key2]['_append']['inbound'] = $stock->inbound;
                                $contents[$key1]['item']['variations'][$key2]['_append']['safety_stock'] = $stock->safely_stock;
                                $contents[$key1]['item']['variations'][$key2]['_append']['days_to_supply'] = $stock->days_to_supply;
                            }else{
                                $contents[$key1]['item']['variations'][$key2]['_append']['cost_percentage'] = $default_cogs_percentage;
                                $contents[$key1]['item']['variations'][$key2]['_append']['prep_cost'] = $default_prep_cost;
                                $contents[$key1]['item']['variations'][$key2]['_append']['inbound'] = 0;
                                $contents[$key1]['item']['variations'][$key2]['_append']['safety_stock'] = $default_safety_stock;
                                $contents[$key1]['item']['variations'][$key2]['_append']['days_to_supply'] = $default_days_to_supply;                                
                            }
                        }
                    }else{
                        $contents[$key1]['item']['_append']['cost'] = $stock->cost;
                        $contents[$key1]['item']['_append']['prep_cost'] = $stock->prep_cost;
                        $contents[$key1]['item']['_append']['inbound'] = $stock->inbound;
                        $contents[$key1]['item']['_append']['safety_stock'] = $stock->safely_stock;
                        $contents[$key1]['item']['_append']['days_to_supply'] = $stock->days_to_supply;
                    }
                }
            }
            if(!isset($contents[$key1]['item']['_append'])){
                $contents[$key1]['item']['_append']['cost_percentage'] = $default_cogs_percentage;
                $contents[$key1]['item']['_append']['prep_cost'] = $default_prep_cost;
                $contents[$key1]['item']['_append']['inbound'] = 0;
                $contents[$key1]['item']['_append']['safety_stock'] = $default_safety_stock;
                $contents[$key1]['item']['_append']['days_to_supply'] = $default_days_to_supply;
                \Log::alert($content);
            }
            \Log::alert($contents);
        }

        return response()->json($contents);
    }
}
