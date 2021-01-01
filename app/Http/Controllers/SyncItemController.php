<?php

namespace App\Http\Controllers;

use App\Shop;
use App\StockCost;
use App\StockSync;
use App\LazadaStock;
use App\ShopeeStock;
use App\LazadaProductModel;
use App\ShopeeProductModel;
use Illuminate\Http\Request;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;



class SyncItemController extends Controller
{
    function mapBySku(Request $request){
        if(auth()->user()->currentShop->platform == "SHOPEE"){

            $lazadaShop = Shop::find($request->mapped_shop_id);
            $lazadaShopModel = new LazadaProductModel($lazadaShop);
            $lazadaProducts = $lazadaShopModel->getProducts();
            $shopeeProductModel = new ShopeeProductModel;
            $shopeeItems = $shopeeProductModel->getDetailedItemsDetail();
    
            foreach($shopeeItems as $item){
                if($item['variations']){
                    foreach($item['variations'] as $variation){
                        foreach($lazadaProducts as $lzdProduct){
                            if($item['item_sku'].$variation['variation_sku'] == $lzdProduct['skus'][0]['SellerSku']){
                                $stock = ShopeeStock::where('platform_item_id',$item['item_id'])->where('platform_variation_id',$variation['variation_id'])->where('shop_id',Auth::user()->current_shop_id)->first();                                
                                $stockSync = StockSync::updateOrCreate(['shopee_stock_id' => $stock->id,'lazada_stock_id' => $lzdProduct['append']['stock_id']],
                                [
                                    'sync' => true,
                                    'last_sync_time' => now(),
                                ]);
                                $lazadaStock = LazadaStock::find($lzdProduct['append']['stock_id']);
                                $lazadaStock->safety_stock = $stock->safety_stock;
                                $lazadaStock->days_to_supply = $stock->days_to_supply;
                                $lazadaStock->save();

                                foreach($stock->costs as $cost){
                                    StockCost::updateOrCreate(['stock_table_name' => 'lazada_stocks','stock_id' => $lazadaStock->id,'from_date' => $cost->from_date],['cost' => $cost->cost]);
                                }

                                break;
                            }
                        }
                    }
                }else{
                    foreach($lazadaProducts as $lzdProduct){
                        if($item['item_sku'] == $lzdProduct['skus'][0]['SellerSku']){
                            $stock = ShopeeStock::where('platform_item_id',$item['item_id'])->where('platform_variation_id',0)->where('shop_id',Auth::user()->current_shop_id)->first();
                            $stockSync = StockSync::updateOrCreate(['shopee_stock_id' => $stock->id,'lazada_stock_id' => $lzdProduct['append']['stock_id']],
                            [
                                'sync' => true,
                                'last_sync_time' => now(),
                            ]);
                            $lazadaStock = LazadaStock::find($lzdProduct['append']['stock_id']);
                            $lazadaStock->safety_stock = $stock->safety_stock;
                            $lazadaStock->days_to_supply = $stock->days_to_supply;
                            $lazadaStock->save();

                            
                            foreach($stock->costs as $cost){
                                StockCost::updateOrCreate(['stock_table_name' => 'lazada_stocks','stock_id' => $lazadaStock->id,'from_date' => $cost->from_date],['cost' => $cost->cost]);
                            }
                            break;
                        }
                    }
                }
            }
            return redirect()->back();
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            $shopeeShop = Shop::find($request->mapped_shop_id);
            $shopeeShopModel = new ShopeeProductModel($shopeeShop);
            $shopeeProducts = $shopeeShopModel->getDetailedItemsDetail();
            $lazadaProductModel = new LazadaProductModel;
            $lazadaItems = $lazadaProductModel->getDetailedProducts();
            

            foreach($lazadaItems as $item){

                foreach($shopeeProducts as $shopeeProduct){
                    if(!empty($shopeeProduct['variations'])){
                        foreach($shopeeProduct['variations'] as $variation){
                            if($item['skus'][0]['SellerSku'] == $shopeeProduct['item_sku'].$variation['variation_sku']){
                            
                                $stock = LazadaStock::find($item['_append']['stock_id']);
                                $stockSync = StockSync::updateOrCreate(['lazada_stock_id' => $stock->id,'shopee_stock_id' => $variation['_append']['stock_id']],
                                [
                                    'sync' => true,
                                    'last_sync_time' => now(),
                                ]);
                                $shopeeStock = ShopeeStock::find($shopeeProduct['append']['stock_id']);
                                $shopeeStock->safety_stock = $stock->safety_stock;
                                $shopeeStock->days_to_supply = $stock->days_to_supply;
                                $shopeeStock->save();

                                
                                foreach($stock->costs as $cost){
                                    StockCost::updateOrCreate(['stock_table_name' => 'shopee_stocks','stock_id' => $shopeeStock->id,'from_date' => $cost->from_date],['cost' => $cost->cost]);
                                }

                                break;
                            }                            
                        }
                    }else{

                        if($item['skus'][0]['SellerSku'] == $shopeeProduct['item_sku']){
                            $stock = LazadaStock::find($item['_append']['stock_id']);
                            $stockSync = StockSync::updateOrCreate(['lazada_stock_id' => $stock->id,'shopee_stock_id' => $shopeeProduct['_append']['stock_id']],
                            [
                                'sync' => true,
                                'last_sync_time' => now(),
                            ]);
                            $shopeeStock = ShopeeStock::find($shopeeProduct['append']['stock_id']);
                            $shopeeStock->safety_stock = $stock->safety_stock;
                            $shopeeStock->days_to_supply = $stock->days_to_supply;
                            $shopeeStock->save();

                            foreach($stock->costs as $cost){
                                StockCost::updateOrCreate(['stock_table_name' => 'shopee_stocks','stock_id' => $shopeeStock->id,'from_date' => $cost->from_date],['cost' => $cost->cost]);
                            }

                            break;
                        }
                    }
                }

            }
            return redirect()->back();
        }
    }

    function store(Request $request){

        if(auth()->user()->currentShop->platform == "SHOPEE"){

            $data = ($request->all());
            foreach($data['item_id'] as $key => $item_id){
                if($data['lazada_product_id'][$key]){
    
                    $stock = ShopeeStock::where('platform_item_id',$data['item_id'][$key])->where('platform_variation_id',$data['variation_id'][$key])->where('shop_id',Auth::user()->current_shop_id)->first();
                    $lazadaStock = LazadaStock::where('platform_item_id',$data['lazada_product_id'][$key])->first();
                    $stockSync = StockSync::updateOrCreate(['shopee_stock_id' => $stock->id,'lazada_stock_id' => $lazadaStock->id],
                    [
                        'sync' => true,
                        'last_sync_time' => now(),
                        ]);
                        $lazadaStock->safety_stock = $stock->safety_stock;
                        $lazadaStock->days_to_supply = $stock->days_to_supply;
                        $lazadaStock->save();

                        foreach($stock->costs as $cost){
                            StockCost::updateOrCreate(['stock_table_name' => 'lazada_stocks','stock_id' => $lazadaStock->id,'from_date' => $cost->from_date],['cost' => $cost->cost]);
                        }
                }else{
                        $stock = ShopeeStock::where('platform_item_id',$data['item_id'][$key])->where('platform_variation_id',$data['variation_id'][$key])->where('shop_id',Auth::user()->current_shop_id)->first();
                        StockSync::where(['shopee_stock_id' => $stock->id])->delete();
                }
            }
            return redirect()->back();
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            $data = ($request->all());
            foreach($data['item_id'] as $key => $item_id){
                if($data['shopee_item_code'][$key]){
                    [$shopee_item_id,$shopee_variation_id] = explode("|",$data['shopee_item_code'][$key]); 
                    
                    $stock = LazadaStock::where('platform_item_id',$data['item_id'][$key])->first();
                    $shopeeStock = ShopeeStock::where('platform_item_id',$shopee_item_id)->where('platform_variation_id',$shopee_variation_id)->first();
                    $stockSync = StockSync::updateOrCreate(['lazada_stock_id' => $stock->id,'shopee_stock_id' => $shopeeStock->id],
                    [
                        'sync' => true,
                        'last_sync_time' => now(),
                        ]);

                        $shopeeStock->safety_stock = $stock->safety_stock;
                        $shopeeStock->days_to_supply = $stock->days_to_supply;
                        $shopeeStock->save();

                        foreach($stock->costs as $cost){
                            StockCost::updateOrCreate(['stock_table_name' => 'shopee_stocks','stock_id' => $shopeeStock->id,'from_date' => $cost->from_date],['cost' => $cost->cost]);
                        }
                }else{
                        $stock = LazadaStock::where('platform_item_id',$data['item_id'][$key])->first();
                        
                        StockSync::where(['lazada_stock_id' => $stock->id])->delete();
                }
            }
            return redirect()->back();

        }
    }
    
    function index(Request $request){

        if(auth()->user()->currentShop->platform == "SHOPEE"){

            $shops = collect(getShopsSession())->where('platform','LAZADA')->toArray();
            $shop = null;
            if($request->shop_id){
                $lazadaShop = Shop::find($request->shop_id);
                $shop = $lazadaShop;
    
                $lazadaShopModel = new LazadaProductModel($shop);
                $products = $lazadaShopModel->getProducts();
    
            }else{
                $products = null;
                $shop = null;
            }
            // dd($products);
            $shopeeProductModel = new ShopeeProductModel;
            $currentShopItems = $shopeeProductModel->getDetailedItemsDetail();
            return view('sync-items.index',compact('shop','currentShopItems','shops','products'));
        }
        elseif(auth()->user()->currentShop->platform == "LAZADA"){
            
            $shops = collect(getShopsSession())->where('platform','SHOPEE')->toArray();
            $shop = null;
            if($request->shop_id){

                $shop = Shop::find($request->shop_id);

                $shopeeProductModel = new ShopeeProductModel($shop);
                $products = $shopeeProductModel->getDetailedItemsDetail();
                // dd($products[0]);
            }else{
                $products = null;
                $shop = null;
            }
            $lazadaProductModel = new lazadaProductModel;
            $currentShopItems = $lazadaProductModel->getDetailedProducts();
            return view('sync-items.index',compact('shops','currentShopItems','shop','products'));
        }
    }

    function addItems(Request $request){

        if(auth()->user()->currentShop->platform == "SHOPEE"){
            $shops = collect(getShopsSession())->where('platform','LAZADA')->toArray();
            
            $shopeeProductModel = new ShopeeProductModel;
            $items = $shopeeProductModel->getDetailedItemsDetail();
            $shop_id = $request->shop_id;
            
            return view('add-items.index',compact('shops','items','shop_id'));
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            
            $shops = collect(getShopsSession())->where('platform','SHOPEE')->toArray();
            $lazadaProductModel = new LazadaProductModel;
            $items = $lazadaProductModel->getDetailedProducts();
            $shop_id = $request->shop_id;
            
            return view('add-items.index',compact('shops','items','shop_id'));
        }
    }

    function createItems(Request $request){

        $lazadaShop = Shop::find($request->shop_id);
        $c = new LazopClient(getLazadaRestApiUrl($lazadaShop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
        $_request = new LazopRequest('/category/tree/get','GET');
        $categories = json_decode($c->execute($_request, getLazadaAccessToken($lazadaShop)),true)['data'];
        
        $shopeeProductModel = new ShopeeProductModel; 
        $items_code = collect($request->items_code)->map(function($item_code){
            [$item_id,$variation_id] = explode("|",$item_code);
            return ['item_id' => $item_id,'variation_id' => $variation_id];
        });

        $items_id = collect($items_code)->pluck('item_id')->toArray();

        $shopeeItems = collect($shopeeProductModel->getDetailedItemsDetail())
            ->filter(function($item) use ($items_id){
                if(!in_array($item['item_id'],$items_id))return false;
                else return true;
            })
            ->map(function($item) use ($items_code){
                $item['variations'] = collect($item['variations'])->filter(function($variation) use($item,$items_code){
                    $pass = false;

                    foreach($items_code as $item_code){
                        if($item['item_id'] == $item_code['item_id']){
                            if($variation['variation_id'] == $item_code['variation_id']){
                                $pass = true;
                                break;
                            }
                        }
                    }
                    return $pass;
                })->toArray();
                return $item;
            });

        $shop_id = $lazadaShop->id;
        
        return view('add-items.create',compact('categories','shopeeItems','shop_id'));
        
    }

    function exportItems(Request $request){
        $lazadaShop = Shop::find($request->shop_id);
        $errors = [];
        $newItemsData = $request->except(['shop_id','_token']);

        foreach($newItemsData['name'] as $rowId => $name){
            $newItemData = [];
            foreach($newItemsData as $key => $d){
                if(isset($d[$rowId])){
                    $newItemData[$key] = $d[$rowId];
                }
            }
            
            $lazadaProductModel = new LazadaProductModel($lazadaShop);
            $images = $lazadaProductModel->migrateImages($newItemData['__images__']);

            $newItemData['Images'] = collect($images)->pluck('url')->toArray();
            unset($newItemData['__images__']);
            
            $lazadaProductModel = new LazadaProductModel($lazadaShop);
            $categoryAttr = $lazadaProductModel->getCategoryAttributes($newItemData['primary_category_id']);

            $xml = View::make('lazada-xml.create-product',['item' => $newItemData,'categoryAttr' => $categoryAttr])->render();
            $_request = new LazopRequest('/product/create');
            $_request->addApiParam('payload',$xml);
            $c = new LazopClient(getLazadaRestApiUrl($lazadaShop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            $rawResponse = $c->execute($_request, getLazadaAccessToken($lazadaShop));

            $response = json_decode($rawResponse,true);
            if(!isset($response['data'])){
                $errors [] = "Failed to create item ".$name.".";
                \Log::alert($response);
                continue;
            }else{
                $data = $response['data']; 
            }
            $stock = ShopeeStock::where('shop_id',Auth::user()->current_shop_id)
                ->where('platform_item_id',$newItemsData['item_id'][$rowId])
                ->where('platform_variation_id',$newItemsData['variation_id'][$rowId])
                ->first();

            $lazadaStock = LazadaStock::where('platform_item_id',$data['item_id'])->first();
            StockSync::create([
                'shopee_stock_id' => $stock->id,
                'lazada_stock_id' => $lazadaStock->id,
                'sync' => false,
                'last_sync_time' => now(),
                'create_time' => now(),
            ]);

            
        }
        if(count($errors)){
            return redirect('/sync-items/add')->withErrors($errors);
        }
        return redirect('/sync-items/add')->with('success_msgs',['Item successfully added!']);
        
    }

    function getCategoryAttributeInput(Request $request){
        $lazadaShop = Shop::find($request->shop_id);
        $lazadaProductModel = new LazadaProductModel($lazadaShop);
        $cacheName = 'lazada_category_attr_'.$request->category_id;
        if(Cache::has($cacheName)){
            $categoryAttr = Cache::get($cacheName);
        }else{
            
            $categoryAttr = $lazadaProductModel->getCategoryAttributes($request->category_id);
            Cache::put($cacheName,$categoryAttr,now()->addYear());
        }
        
        $shopeeProductModel = new ShopeeProductModel;
        $shopeeItems = $shopeeProductModel->getDetailedItemsDetail();
        
        $shopeeItem = null;
        if($request->variation_id){
            foreach($shopeeItems as $item){
                foreach($item['variations'] as $variation){
                    if($variation['variation_id'] == $request->variation_id){
                        $shopeeItem = $item;
                        $shopeeItem['_variation'] = $variation;
                    }
                }
            }
        }
        else{
            foreach($shopeeItems as $item){
                if($item['item_id'] == $request->item_id){
                    $shopeeItem = $item;
                    break;
                }
            }
        }
        // return response()->json($categoryAttr);
        $inputArrayNumber = $request->i;
        $primary_category_id = $request->category_id;

        $brands = $lazadaProductModel->getBrands();

        return View::make('add-items.components.item-attribute-input',compact('shopeeItem','categoryAttr','inputArrayNumber','primary_category_id','brands'))->render();
        // return View::make('add-items.components.item-attribute-input',compact('shopeeItem','categoryAttr'))->render();
    }

}
