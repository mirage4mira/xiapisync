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
    public $cacheName;
    public $itemsList = [];

    public function __construct()
    {
        $this->timestamp = time();
        $this->cacheName = setShopUserCacheName('items_detail');
    }

    public function getItemsList()
    {
        $path = '/api/v1/items/get';

        $more = true;

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
                $this->itemsList[] = $item;
            }
            $more = $responseData['more'];
            $i += $paginate;
        }

        $this->itemsList;
        
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
        unset($contents);
        
        $products = collect($products)->sortBy('name')->values()->toArray();

        DB::transaction(function () use ($products) {
            $this->createInitialStockAndCost($products);
        });


        return $products;
    }

    public function getDetailedItemsDetail($cached = true)
    {

        $months = 6;
        $daysInMonth = 30;

        $start_date = now()->subDays($months * $daysInMonth);

        if(checkLastSyncTime() == true && Cache::has($this->cacheName) && $cached == true){
            $products = Cache::get($this->cacheName);
            // $orderDetails = Cache::get($this->cacheName)['orders_detail'];
        }else{
            
            $products = $this->getItemsDetail();
            Cache::put($this->cacheName,$products, env('CACHE_DURATION'));
            
            updateLastSyncTimeCookie();
        }
        
        $ordersDetailCacheName = setShopUserCacheName('orders_detail_for_items_detail');
        
        if(Cache::has($ordersDetailCacheName)){
            $orderDetails = Cache::get($ordersDetailCacheName);
        }else{
            $orderDetails = (new ShopeeOrderModel("PAID",$start_date,now()))->getOrdersList()->getOrdersDetail();
            Cache::put($ordersDetailCacheName,$orderDetails,now()->endOfDay());
        }

        $stocks = Stock::with(['costs', 'inbound_orders'])->where('shop_id', Auth::user()->current_shop_id)->get();

        foreach ($products as $key1 => $product) {
            if (!empty($product['variations'])) {
                foreach ($product['variations'] as $key2 => $variation) {
                    $create_time = Carbon::createFromTimeStamp($variation['create_time']);
                    $daysDiff = $create_time->diffInDays(now());
                    foreach ($stocks as $stock) {
                        if ($variation['variation_id'] == $stock['platform_variation_id']) {
                            $cost = $stock->costs->sortByDesc('from_date')->first();
                            $products[$key1]['variations'][$key2]['_append']['stock_id'] = $stock->id;
                            $products[$key1]['variations'][$key2]['_append']['cost'] = $cost->cost;
                            $products[$key1]['variations'][$key2]['_append']['costs'] = $stock->costs->sortBy('from_date')->values()->toArray();
                            $products[$key1]['variations'][$key2]['_append']['inbound'] = $stock->inbound_orders->where('stock_received',0)->values()->toArray();
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

                               
                                        $sellerfees = $orderDetail['_escrow_detail']['income_details']['seller_transaction_fee'] + $orderDetail['_escrow_detail']['income_details']['service_fee'];
                                        $fees = $sellerfees * $sales /  $orderDetail['_append']['item_amount'];
                                        $totalProfit += $sales - $cost - $fees;

                                    }
                                }
                            }


                            if($daysDiff > ($months * $daysInMonth)){
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_quantity'] = round($totalQtySold/$months,1);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_sales'] = round($totalSales/$months,2);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_cost'] = round($totalCost/$months,2);
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_profit'] = round($totalProfit/$months,2);
                            }else{
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_quantity'] = $totalQtySold? round($totalQtySold/($daysDiff /$daysInMonth ),1):0;
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_sales'] = $totalSales ? round($totalSales/($daysDiff /$daysInMonth ),2): 0;
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_cost'] = $totalCost ? round($totalCost/($daysDiff /$daysInMonth ),2) : 0;
                                $products[$key1]['variations'][$key2]['_append']['avg_monthly_profit'] = $totalProfit? round($totalProfit/($daysDiff /$daysInMonth ),2): 0;
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
                        $products[$key1]['_append']['inbound'] = $stock->inbound_orders->where('stock_received',0)->values()->toArray();
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
                                    
                                    $sellerfees = $orderDetail['_escrow_detail']['income_details']['seller_transaction_fee'] + $orderDetail['_escrow_detail']['income_details']['service_fee'];

                                    $fees = $sellerfees * $sales /  $orderDetail['_append']['item_amount'];
                                    
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
                            $products[$key1]['_append']['avg_monthly_quantity'] =  $totalQtySold ? round($totalQtySold/($daysDiff /$daysInMonth),1) : 0;
                            $products[$key1]['_append']['avg_monthly_sales'] = $totalSales ? round($totalSales/($daysDiff /$daysInMonth),2) : 0;
                            $products[$key1]['_append']['avg_monthly_cost'] = $daysDiff? round($totalCost/($daysDiff /$daysInMonth),2) : 0;
                            $products[$key1]['_append']['avg_monthly_profit'] = $totalProfit ? round($totalProfit/($daysDiff /$daysInMonth),2) : 0;                            
                        }

                        $additional_stock_required = ceil(($products[$key1]['_append']['avg_monthly_quantity'] - ($product['stock'] - $stock->safety_stock + ($stock->inbound_orders->sum('pivot.quantity') / ($stock->days_to_supply?$stock->days_to_supply:1) * $daysInMonth)))/$daysInMonth * $stock->days_to_supply);
                        $products[$key1]['_append']['low_on_stock'] =  ($additional_stock_required > 0);
                        $products[$key1]['_append']['additional_stock_required'] = ($additional_stock_required > 0)? $additional_stock_required: null;
                    }
                }
            }
        }
        \Log::alert(microtime(true) - LARAVEL_START);
        \Log::alert('end');

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
        $this->updateCachedItemsDetail($stockData['product_id'],isset($stockData['variation_id'])?$stockData['variation_id'] : null,intval($stockData['stock_quantity']),null);
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

        if (isset($priceData['variation_id'])) {
            $data['variation_id'] = $priceData['variation_id'];
            $responseData = shopee_http_post($updateVariationPricePath, $data)->json();
        } else {
            $responseData = shopee_http_post($updatePricePath, $data)->json();
        }
        $this->updateCachedItemsDetail($priceData['product_id'],isset($priceData['variation_id'])?$priceData['variation_id'] : null,null, ((float)$priceData['price']));
        return $responseData['item'];
    }
    
    public function updateCachedItemsDetail($item_id,$variation_id,$stock = null, $price = null){
        
        if(Cache::has($this->cacheName)){
            $products = Cache::get($this->cacheName);

            foreach($products as $key1 => $product){
                if(!isset($stockData['variation_id'])){
                    if($product['item_id'] == $item_id){
                        $products[$key1]['stock'] = $stock;
                        break;
                    }
                }else{
                    foreach($product['variations'] as $key2 => $variation){
                        if($product['item_id'] == $item_id && $variation['variation_id'] == $variation_id){
                            $products[$key1]['variations'][$key2]['stock'] = $stock;
                            break;
                        }
                    }
                }
            }
            Cache::put($this->cacheName,$products, env('CACHE_DURATION'));
            
        }
    }

    
    public function getCategories(){
        $path = '/api/v1/item/categories/get';
        $data = [
            'partner_id' => shopee_partner_id(),
            'shopid' => shopee_shop_id(),
            'timestamp' => $this->timestamp,
        ];

        $responseData = shopee_http_post($path, $data)->json();
        
        return $responseData['categories'];
    }



    public function createInitialStockAndCost($products)
    {
        $settings =  getShopSettingSession();
        $default_cogs_percentage = 0;
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
