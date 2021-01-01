<?php

namespace App;

use App\Jobs\SaveLazadaCompressedImg;
use Exception;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\DispatchesJobs;

class LazadaProductModel extends Model
{
    use DispatchesJobs;
    public $shop;

    public function __construct($shop = null)
    {
        if(!$shop) $shop = Shop::where('platform','LAZADA')->where('id',Auth()->user()->current_shop_id)->first();
        $this->shop = $shop;
    }

    public function getProducts(){
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
            $this->createInitialStockAndCost($products);
            return $products;
    }

    public function createInitialStockAndCost($products){

        $stocks = LazadaStock::select('id', 'platform_item_id', 'platform_sku_id','platform_seller_sku')->where('shop_id', Auth::user()? Auth::user()->current_shop_id : $this->shop->id)->get();
        foreach ($products as $product) {
            $item_stock = null;
            foreach ($stocks as $stock) {
                if ($stock->platform_item_id == $product['item_id'] && $stock->platform_sku_id == $product['skus'][0]['SkuId'] && $stock->platform_seller_sku == $product['skus'][0]['SellerSku']) {
                    $item_stock = $stock;
                    break;
                }
            }
            if (!$item_stock) {
                $item_stock = LazadaStock::create(['shop_id' => Auth::user()->current_shop_id,'platform_item_id' => $product['item_id'], 'platform_sku_id' => $product['skus'][0]['SkuId'], 'platform_seller_sku' => $product['skus'][0]['SellerSku'], 'safety_stock' => 0]);
            }
            if(!$item_stock->compressed_img_path || !file_exists(storage_path($item_stock->compressed_img_path))){
                $this->dispatch(new SaveLazadaCompressedImg($product,$item_stock,auth()->user()->current_shop_id));
            }
            if(!$item_stock->costs()->count()){
                StockCost::create(['stock_id' => $item_stock->id,'stock_table_name' => 'lazada_stocks', 'cost' => 0]);
            }
            
        }
    }

    public function getDetailedProducts($cached = true){
        $months = 3;
        $daysInMonth = 30;
        $start_date = now()->subDays($months * $daysInMonth)->startOfDay();
        
        $cacheName = setShopCacheName('items_detail',$this->shop);

        if(checkLastSyncTime() == true && Cache::has($cacheName) && $cached == true){
            $products = Cache::get($cacheName);
        }else{
            
            $products = collect($this->getProducts())->sortBy(function($product){
                return $product['attributes']['name'];
            })->values()->toArray();

            Cache::put($cacheName,$products, env('CACHE_DURATION'));
            
            updateLastSyncTimeCookie();
        }

        $ordersDetailCacheName = setShopCacheName('orders_detail_for_items_detail');
        Cache::pull($ordersDetailCacheName);
        if(Cache::has($ordersDetailCacheName)){
            $orderDetails = Cache::get($ordersDetailCacheName);
        }else{
            $orderDetails = (new LazadaOrderModel(true,$start_date,now()))->getOrders()->paidOrders()->getOrdersItems()->orders;
            Cache::put($ordersDetailCacheName,$orderDetails,now()->endOfDay());
        }
        
        $stocks = LazadaStock::with(['costs', 'inbound_orders','stock_syncs'])->where('shop_id', Auth::user()->current_shop_id)->get();
        
        foreach($products as $key => $product){
            foreach($stocks as $stock){
                if($product['item_id'] == $stock['platform_item_id']){
                    $cost = $stock->costs->sortByDesc('from_date')->first();
                    $products[$key]['_append']['stock_id'] = $stock->id;
                    $products[$key]['_append']['cost'] = $cost->cost;
                    $products[$key]['_append']['costs'] = $stock->costs->sortBy('from_date')->values()->toArray();
                    $products[$key]['_append']['inbound'] = $stock->inbound_orders->where('stock_received',0)->values()->toArray();
                    $products[$key]['_append']['safety_stock'] = $stock->safety_stock;
                    $products[$key]['_append']['days_to_supply'] = $stock->days_to_supply;
                    $products[$key]['_append']['stock_syncs'] = $stock->stock_syncs;
                    $products[$key]['_append']['image_path'] = $stock->compressed_img_path;

                    $totalQtySold = 0;
                    $totalSales = 0;
                    $totalProfit = 0;
                    $totalCost = 0;

                    foreach($orderDetails as $orderDetail){
                        foreach($orderDetail['_items'] as $item){
                            if($item['sku'] == $stock['platform_seller_sku']){
                                $totalQtySold += 1;

                                $sales = $item['paid_price'];
                                $totalSales += $sales; 

                                $cost = $item['_append']['cost'];
                                $totalCost += $cost;

                                $fees = 0;
                                $totalProfit += $sales - $cost - $fees;

                            }
                        }
                    }



                    $products[$key]['_append']['avg_monthly_quantity'] = round($totalQtySold/$months,1);
                    $products[$key]['_append']['avg_monthly_sales'] = round($totalSales/$months,2);
                    $products[$key]['_append']['avg_monthly_cost'] = round($totalCost/$months,2);
                    $products[$key]['_append']['avg_monthly_profit'] = round($totalProfit/$months,2);


                    $additional_stock_required = Ceil(($products[$key]['_append']['avg_monthly_quantity'] - ($products[$key]['skus'][0]['quantity'] - $stock->safety_stock + ($stock->inbound_orders->sum('pivot.quantity') / ($stock->days_to_supply?$stock->days_to_supply:1) * $daysInMonth)))/$daysInMonth * $stock->days_to_supply);
                    $products[$key]['_append']['low_on_stock'] =  ($additional_stock_required > 0);
                    $products[$key]['_append']['additional_stock_required'] = ($additional_stock_required > 0)? $additional_stock_required: null;
                    break;
                }
            }
        }
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
                
                $results = (lazada_multiple_async_request('/brands/get',$requestData,'GET',$this->shop));
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

    public function updatePriceQuantity($updateLazadaItem){
        $c = new LazopClient(getLazadaRestApiUrl($this->shop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
        $request = new LazopRequest('/product/price_quantity/update');
        $xml = View::make('lazada-xml.update-price-quantity',['items' => [$updateLazadaItem]])->render();
        $request->addApiParam('payload',$xml);
        $response = json_decode($c->execute($request, getLazadaAccessToken($this->shop)),true);
        
        if($response['code'] > 0){
            throw new Exception("Lazada rest api error!");
        }
        $this->updateCachedPriceQuantity($updateLazadaItem);
    }

    public function updateCachedPriceQuantity($updateLazadaItem){
        $cacheName = setShopCacheName('items_detail',$this->shop);
        if(Cache::has($cacheName)){
            $products = Cache::get($cacheName);
        }else{
            return;
        }
        foreach($products as $key => $product){
            if($product['item_id'] == $updateLazadaItem['item_id']){
                if(isset($updateLazadaItem['price']))$products[$key]['skus'][0]['price'] = $updateLazadaItem['price']; 
                if(isset($updateLazadaItem['latest_quantity'])) $products[$key]['skus'][0]['quantity'] = $updateLazadaItem['latest_quantity'];
                
                break;
            }
        }
        Cache::put($cacheName,$products, env('CACHE_DURATION'));
    }

    public function getCategoryAttributes($category_id){
        $c = new LazopClient(getLazadaRestApiUrl($this->shop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            $_request = new LazopRequest('/category/attributes/get','GET');
            $_request->addApiParam('primary_category_id',$category_id);
            $response = json_decode($c->execute($_request, getLazadaAccessToken($this->shop)),true);
            if($response['code'] > 0){
                throw new Exception("Error getting category attributes");
            }
            return $response['data'];
    }
    public function migrateImages($imagesUrl){
        $c = new LazopClient(getLazadaRestApiUrl($this->shop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
        $_request = new LazopRequest('/images/migrate');
        
        $xml = View::make('lazada-xml.migrate-images',['imageUrls' => $imagesUrl])->render();
        $_request->addApiParam('payload',$xml);
        $response = json_decode($c->execute($_request, getLazadaAccessToken($this->shop)),true);
        
        if($response['code'] > 0){
            throw new Exception("Error migrating images!");
        }

        $batch_id = $response['batch_id'];

        $_request = new LazopRequest('/image/response/get','GET');
        $_request->addApiParam('batch_id',$batch_id);

        $data = retry(5,function() use($c,$_request){
            $response = json_decode($c->execute($_request, getLazadaAccessToken($this->shop)),true);
            if($response['code'] > 0){
                throw new Exception("Error getting images url!");
            }
            return $response['data'];
        },500);
        
        return $data['images'];
    }
}
