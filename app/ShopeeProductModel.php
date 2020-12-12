<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class ShopeeProductModel extends Model
{

    public $timestamp;
    public $itemsList;

    public function __construct()
    {
        $this->timestamp = time();
    }

    public function getCachedItemsDetail(){
        $cacheName = 'items_detail_'.Auth::id();

        if(checkLastSyncTime() == true && Cache::has($cacheName)){
            $products = Cache::get($cacheName);
        }else{
            $products = $this->getDetailedItemsDetail();
            Cache::put($cacheName, $products, env('CACHE_DURATION'));
            updateLastSyncTimeCookie();
        }
        return $products;
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

        if(!$item_ids){
            $this->getItemsList();
            $item_ids = collect($this->itemsList)->pluck('item_id')->toArray();
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

        return $products;
    }

    public function getDetailedItemsDetail()
    {

        $products =  $this->getItemsDetail();

        DB::transaction(function () use ($products) {
            $this->createInitialStockAndCost($products);
        });
        $months = 6;
        $daysInMonth = 30;
        \Log::alert(now());
        $start_date = now()->subDays($months * $daysInMonth);
        $orderDetails = (new ShopeeOrderModel("PAID",$start_date,now()))->getOrdersList()->getOrdersDetail();

        $stocks = Stock::with(['costs', 'inbound_orders'])->where('shop_id', Auth::user()->current_shop_id)->get();

        foreach ($products as $key1 => $product) {
            if (!empty($product['variations'])) {
                foreach ($product['variations'] as $key2 => $variation) {
                    $create_time = Carbon::createFromTimeStamp($variation['create_time']);
                    $daysDiff = $create_time->diffInDays(now());
                    // \Log::alert($daysDiff);
                    // \Log::alert($create_time);
                    // \Log::alert($start_date);
                    foreach ($stocks as $stock) {
                        if ($variation['variation_id'] == $stock['platform_variation_id']) {
                            $cost = $stock->costs->sortByDesc('from_date')->first();
                            $products[$key1]['variations'][$key2]['_append']['stock_id'] = $stock->id;
                            $products[$key1]['variations'][$key2]['_append']['cost'] = $cost->cost;
                            $products[$key1]['variations'][$key2]['_append']['costs'] = $stock->costs->sortBy('from_date')->values()->toArray();
                            $products[$key1]['variations'][$key2]['_append']['inbound'] = $stock->inbound_orders->sum('pivot.quantity');
                            $products[$key1]['variations'][$key2]['_append']['safety_stock'] = $stock->safety_stock;
                            $products[$key1]['variations'][$key2]['_append']['days_to_supply'] = $stock->days_to_supply;

                            $totalQtySold = 0;
                            $totalSales = 0;
                            $totalProfit = 0;
                            $totalCost = 0;
                            foreach($orderDetails as $orderDetail){
                                foreach($orderDetail['items'] as $item){
                                    if($item['item_id'] == $stock['platform_item_id'] && $item['variation_id'] == $stock['platform_variation_id']){
                                        $totalQtySold += $item['variation_quantity_purchased'];

                                        $sales = $item['variation_discounted_price'] * $item['variation_quantity_purchased'];
                                        $totalSales += $sales; 

                                        $cost = $item['variation_quantity_purchased'] * $item['_append']['cost'];
                                        $totalCost += $cost;
                                        
                                        $fees = ($orderDetail['total_amount'] - $orderDetail['escrow_amount'] )/ $orderDetail['_append']['item_count'] * $item['variation_quantity_purchased']; 
                                        
                                        $totalProfit += $sales - $cost;
                                    }
                                }
                            }
                            if($daysDiff > ($months * $daysInMonth)){
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_quantity'] = round($totalQtySold/$months,1);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_sales'] = round($totalSales/$months,2);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_cost'] = round($totalCost/$months,2);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_profit'] = round($totalProfit/$months,2);
                            }else{
 
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_quantity'] = round($totalQtySold/($daysDiff /$daysInMonth ),1);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_sales'] = round($totalSales/($daysDiff /$daysInMonth ),2);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_cost'] = round($totalCost/($daysDiff /$daysInMonth ),2);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_profit'] = round($totalProfit/($daysDiff /$daysInMonth ),2);
                            }
                            $additional_stock_required = Ceil(($products[$key1]['variations'][$key2]['_append']['avg_monthly_quantity'] - ($variation['stock'] - $stock->safety_stock + ($stock->inbound_orders->sum('pivot.quantity') / ($stock->days_to_supply?$stock->days_to_supply:1) * $daysInMonth)))/$daysInMonth * $stock->days_to_supply);
                            $products[$key1]['variations'][$key2]['_append']['low_on_stock'] =  ($additional_stock_required > 0);
                            $products[$key1]['variations'][$key2]['_append']['additional_stock_required'] = ($additional_stock_required > 0)? $additional_stock_required: null;
                        }
                    }
                }
            } else {
                $create_time = Carbon::createFromTimeStamp($product['create_time']);
                $daysDiff = $create_time->diffInDays(now());
                foreach ($stocks as $stock) {
                    if ($product['item_id'] == $stock['platform_item_id']) {
                        $cost = $stock->costs->sortByDesc('from_date')->first();
                        $products[$key1]['_append']['stock_id'] = $stock->id;
                        $products[$key1]['_append']['cost'] = $cost->cost;
                        $products[$key1]['_append']['costs'] = $stock->costs->sortBy('from_date')->values()->toArray();
                        $products[$key1]['_append']['inbound'] = $stock->inbound_orders->sum('pivot.quantity');
                        $products[$key1]['_append']['safety_stock'] = $stock->safety_stock;
                        $products[$key1]['_append']['days_to_supply'] = $stock->days_to_supply;
                        $products[$key1]['_append']['avg_monthly_quantity'] = 0;
                        $products[$key1]['_append']['avg_monthly_sales'] = 0;
                        $products[$key1]['_append']['avg_monthly_profit'] = 0;

                        $totalQtySold = 0;
                        $totalSales = 0;
                        $totalProfit = 0;
                        $totalCost = 0;
                        foreach($orderDetails as $orderDetail){
                            foreach($orderDetail['items'] as $item){
                                if($item['item_id'] == $stock['platform_item_id'] && $item['variation_id'] == $stock['platform_variation_id']){
                                    $totalQtySold += $item['variation_quantity_purchased'];
                                    $sales = $item['variation_discounted_price'] * $item['variation_quantity_purchased'];
                                    $totalSales += $sales; 
                                    
                                    $cost = $item['variation_quantity_purchased'] * $item['_append']['cost']; 
                                    $totalCost += $cost;
                                    $fees = ($orderDetail['total_amount'] - $orderDetail['escrow_amount'] )/ $orderDetail['_append']['item_count'] * $item['variation_quantity_purchased'];
                                    $totalProfit += $sales - $cost - $fees;
                                }
                            }
                        }
                        if($daysDiff > ($months * $daysInMonth)){
                            $products[$key1]['_append']['avg_monthly_quantity'] = round($totalQtySold/$months,1);
                            $products[$key1]['_append']['avg_monthly_sales'] = round($totalSales/$months,2);
                            $products[$key1]['_append']['avg_monthly_cost'] = round($totalCost/$months,2);
                            $products[$key1]['_append']['avg_monthly_profit'] = round($totalProfit/$months,2);
                        }else{
                            $products[$key1]['_append']['avg_monthly_quantity'] = round($totalQtySold/($daysDiff /$daysInMonth),1);
                            $products[$key1]['_append']['avg_monthly_sales'] = round($totalSales/($daysDiff /$daysInMonth),2);
                            $products[$key1]['_append']['avg_monthly_cost'] = round($totalCost/($daysDiff /$daysInMonth),2);
                            $products[$key1]['_append']['avg_monthly_profit'] = round($totalProfit/($daysDiff /$daysInMonth),2);                            
                        }

                        $additional_stock_required = ceil(($products[$key1]['_append']['avg_monthly_quantity'] - ($product['stock'] - $stock->safety_stock + ($stock->inbound_orders->sum('pivot.quantity') / ($stock->days_to_supply?$stock->days_to_supply:1) * $daysInMonth)))/$daysInMonth * $stock->days_to_supply);
                        $products[$key1]['_append']['low_on_stock'] =  ($additional_stock_required > 0);
                        $products[$key1]['_append']['additional_stock_required'] = ($additional_stock_required > 0)? $additional_stock_required: null;
                    }
                }
            }
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
        // \Log::alert($responseData);
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
        // \Log::alert($responseData);
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
