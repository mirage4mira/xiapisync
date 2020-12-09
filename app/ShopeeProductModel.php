<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Auth;
use Illuminate\Support\Facades\DB;
class ShopeeProductModel extends Model
{

    public $timestamp;
    public $itemsList;

    public function __construct()
    {
        $this->timestamp = time();
    }

    public function getItemsList()
    {
        $path = '/api/v1/items/get';

        $more = true;
        $itemLists = [];

        $i = 0;
        $paginate = 100;
        while ($more) {

            $data = [
                'pagination_offset' => $i,
                'pagination_entries_per_page' => $paginate,
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $responseData = shopee_http_post($path, $data)->json();
            foreach ($responseData['items'] as $item) {
                $itemLists[] = $item;
            }
            $more = $responseData['more'];
            $i += $paginate;
        }

        $this->itemsList = $itemLists;
        return $responseData;
    }

    public function getItemsDetail($item_ids = null)
    {
        $path = '/api/v1/item/get';
        $cache = false;

        if(!$item_ids){
            $this->getItemsList();
            $item_ids = collect($this->itemsList)->pluck('item_id')->toArray();
            $cache = true;
        }

        $datas = [];

        foreach ($item_ids as $item_id) {
            $data = [
                'item_id' => intval($item_id),
                'partner_id' => shopee_partner_id(),
                'shopid' => shopee_shop_id(),
                'timestamp' => $this->timestamp,
            ];
            $datas[] = $data;
        }
        $products = [];
        $contents = shopee_multiple_async_post($path, $datas);
        foreach ($contents as $content) {
            $products[] = $content['item'];
        }

        $products = collect($products)->sortBy('name')->values()->toArray();
        if($cache){
            Cache::put('items_detail', $products, env('CACHE_DURATION'));
        }
        return $products;
    }

    public function getDetailedItemsDetail()
    {
        if (Cache::has('items_detail')) {
            $products = Cache::get('items_detail');
        } else {
            $products =  $this->getItemsDetail();
        }


        // $default_prep_cost = $settings['default_prep_cost'];
        // $default_days_to_supply = $settings['default_days_to_supply'];
        // $default_safety_stock = $settings['default_safety_stock'];

        // $stocksToCreate = [];
        DB::transaction(function () use ($products) {
            $this->createInitialStockAndCost($products);
        });

        $stocks = Stock::with(['costs', 'inbound_orders'])->where('shop_id', Auth::user()->current_shop_id)->get();

        foreach ($products as $key1 => $product) {
            if (!empty($product['variations'])) {
                foreach ($product['variations'] as $key2 => $variation) {
                    // $stockExisted = false;
                    // $defaultCost = round($products[$key1]['variations'][$key2]['original_price'] * $default_cogs_percentage / 100,2);
                    foreach ($stocks as $stock) {
                        if ($variation['variation_id'] == $stock['platform_variation_id']) {
                            $cost = $stock->costs->sortByDesc('from_date')->first();
                            $products[$key1]['variations'][$key2]['_append']['stock_id'] = $stock->id;
                            $products[$key1]['variations'][$key2]['_append']['cost'] = $cost->cost;
                            $products[$key1]['variations'][$key2]['_append']['costs'] = $stock->costs->sortBy('from_date')->values()->toArray();
                            // $products[$key1]['variations'][$key2]['_append']['prep_cost'] = $stock->prep_cost;
                            $products[$key1]['variations'][$key2]['_append']['inbound'] = $stock->inbound_orders->sum('pivot.quantity');
                            $products[$key1]['variations'][$key2]['_append']['safety_stock'] = $stock->safety_stock;
                            $products[$key1]['variations'][$key2]['_append']['days_to_supply'] = $stock->days_to_supply;
                            // $stockExisted = true;
                        }
                    }
                    // if(!$stockExisted){
                    //     $products[$key1]['variations'][$key2]['_append']['stock_id'] = 0;
                    //     $products[$key1]['variations'][$key2]['_append']['cost'] = $defaultCost;
                    //     $products[$key1]['variations'][$key2]['_append']['costs'] = [['from_date' => '1970-01-01','cost' => $defaultCost ]];
                    //     // $products[$key1]['variations'][$key2]['_append']['prep_cost'] = $default_prep_cost;
                    //     $products[$key1]['variations'][$key2]['_append']['inbound'] = 0;
                    //     $products[$key1]['variations'][$key2]['_append']['safety_stock'] = $default_safety_stock;
                    //     $products[$key1]['variations'][$key2]['_append']['days_to_supply'] = $default_days_to_supply;                                
                    // }


                }
            } else {
                // $defaultCost = round($products[$key1]['original_price'] * $default_cogs_percentage / 100,2);
                // $stockExisted = false;
                foreach ($stocks as $stock) {
                    if ($product['item_id'] == $stock['platform_item_id']) {
                        $cost = $stock->costs->sortByDesc('from_date')->first();
                        $products[$key1]['_append']['stock_id'] = $stock->id;
                        $products[$key1]['_append']['cost'] = $cost->cost;
                        $products[$key1]['_append']['costs'] = $stock->costs->sortBy('from_date')->values()->toArray();
                        // $products[$key1]['_append']['prep_cost'] = $stock->prep_cost;
                        $products[$key1]['_append']['inbound'] = $stock->inbound_orders->sum('pivot.quantity');
                        $products[$key1]['_append']['safety_stock'] = $stock->safety_stock;
                        $products[$key1]['_append']['days_to_supply'] = $stock->days_to_supply;
                        // $stockExisted = true;
                    }
                }
            }

            // if(!$stockExisted){
            //     $products[$key1]['_append']['stock_id'] = 0;
            //     $products[$key1]['_append']['cost'] = $defaultCost;
            //     $products[$key1]['_append']['costs'] = [['from_date' => '1970-01-01','cost' => $defaultCost ]];
            //     // $products[$key1]['_append']['prep_cost'] = $default_prep_cost;
            //     $products[$key1]['_append']['inbound'] = 0;
            //     $products[$key1]['_append']['safety_stock'] = $default_safety_stock;
            //     $products[$key1]['_append']['days_to_supply'] = $default_days_to_supply;
            // }

        }
        return $products;
    }


    public function updateStock(array $stockData)
    {
        $updateStockPath = '/api/v1/items/update_stock';
        $updateVariationStockPath = '/api/v1/items/update_variation_stock';

        $data = [
            'item_id' => $stockData['product_id'],
            'stock' => intval($stockData['stock_quantity']),
            'partner_id' => shopee_partner_id(),
            'shopid' => shopee_shop_id(),
            'timestamp' => $this->timestamp,
        ];

        if (isset($stockData['variation_id'])) {
            $data['variation_id'] = $stockData['variation_id'];
            $responseData = shopee_http_post($updateVariationStockPath, $data)->json();
        } else {
            $responseData = shopee_http_post($updateStockPath, $data)->json();
        }
        \Log::alert($responseData);
        return $responseData['item'];
    }

    public function updatePrice(array $priceData)
    {
        $updatePricePath = '/api/v1/items/update_price';
        $updateVariationPricePath = '/api/v1/items/update_variation_price';


        $data = [
            'item_id' => $priceData['product_id'],
            'price' => ((float)$priceData['price']),
            'partner_id' => shopee_partner_id(),
            'shopid' => shopee_shop_id(),
            'timestamp' => $this->timestamp,
        ];

        if (isset($stockData['variation_id'])) {
            $data['variation_id'] = $stockData['variation_id'];
            $responseData = shopee_http_post($updateVariationPricePath, $data)->json();
        } else {
            $responseData = shopee_http_post($updatePricePath, $data)->json();
        }
        \Log::alert($responseData);
        return $responseData['item'];
    }

    public function createInitialStockAndCost($products)
    {
        $settings =  getShopSettingSession();
        $default_cogs_percentage = $settings['default_cogs_percentage'];
        $stocks = Stock::select('id', 'platform_item_id', 'platform_variation_id')->where('shop_id', Auth::user()->current_shop_id)->get();
        foreach ($products as $product) {
            if (!empty($product['variations'])) {
                foreach ($product['variations'] as $variation) {
                    // $stockCreated = false;
                    $item_stock = null;
                    foreach ($stocks as $stock) {
                        if ($stock->platform_item_id == $product['item_id'] && $stock->platform_variation_id == $variation['variation_id']) {
                            $item_stock = $stock;
                            break;
                        }
                    }
                    if (!$item_stock) {
                        $item_stock = Stock::create(['shop_id' => Auth::user()->current_shop_id, 'platform_item_id' => $product['item_id'], 'platform_variation_id' => $variation['variation_id'], 'safety_stock' => 0]);
                        StockCost::create(['stock_id' => $item_stock->id, 'cost' => round($variation['original_price'] * $default_cogs_percentage / 100, 2)]);
                    }
                }
            } else {
                $item_stock = null;
                foreach ($stocks as $stock) {
                    if ($stock->platform_item_id == $product['item_id'] && $stock->platform_variation_id == 0) {
                        $item_stock = $stock;
                        break;
                    }
                }
                if (!$item_stock) {
                    $item_stock = Stock::create(['shop_id' => Auth::user()->current_shop_id, 'platform_item_id' => $product['item_id'], 'platform_variation_id' => 0, 'safety_stock' => 0]);
                    StockCost::create(['stock_id' => $item_stock->id, 'cost' => round($product['original_price'] * $default_cogs_percentage / 100, 2)]);
                }
            }
        }
    }
}
