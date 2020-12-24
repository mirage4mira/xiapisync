<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\ShopeeProductModel;
use App\Stock;
use App\Shop;
use App\StockSync;
use Auth;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryTemplateExport;
use App\Imports\InventoryImport;
use App\LazadaProductModel;
use App\StockCost;
use Symfony\Contracts\Service\Attribute\Required;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Async;

class ShopeeProductController extends Controller
{
    public function getProductsDetail()
    {
        $shopeeProductModel = new ShopeeProductModel(); 
        
        $products = $shopeeProductModel->getDetailedItemsDetail();

        return response()->json($products);
    }

    function update(Request $request)
    {
        $data = json_decode($request->data);

        $validator = Validator::make((array)$data, [
            'price' => 'required|numeric|gte:0.1',
            // 'inbound' => 'required|numeric',
            'available' => 'required|numeric',
            'reserved' => 'required|numeric',
            'days_to_supply' => 'required|numeric',
            'item_id' => 'required|numeric',
            'variation_id' => 'required|numeric',
            'stock_id' => 'required|numeric',

        ]);

        handleValidatorFails($request, $validator);


        if (!$data->stock_id) {
            $stock = Stock::create([
                'shop_id' => auth()->user()->current_shop_id,
                'platform_item_id' => $data->item_id,
                'platform_variation_id' => $data->variation_id,
                // 'inbound' => $data->inbound,
                'safety_stock' => $data->reserved,
                'days_to_supply' => $data->days_to_supply
            ]);
        } else {
            $stock = tap(Stock::where(['id' => $data->stock_id])->where(['shop_id' => auth()->user()->current_shop_id]))
                ->update([
                    // 'inbound' => $data->inbound,
                    'safety_stock' => $data->reserved,
                    'days_to_supply' => $data->days_to_supply
                ])->first();
        }
        $shopeeProductModel = new ShopeeProductModel();
        if ($data->update_stock_count == true) {
            $updateStockData = ['product_id' => $stock->platform_item_id, 'stock_quantity' => $data->available];
            if ($stock->platform_variation_id) $updateStockData['variation_id'] = $stock->platform_variation_id;
            $shopeeProductModel->updateStock($updateStockData);
        }


        if ($data->update_price == true) {
            $updatePriceData = ['product_id' => $stock->platform_item_id, 'price' => $data->price];
            if ($stock->platform_variation_id) $updatePriceData['variation_id'] = $stock->platform_variation_id;
            $shopeeProductModel->updatePrice($updatePriceData);
        }
        return response()->json();
    }

    function updateCost(Request $request){
        
        DB::transaction(function() use ($request){
            $stock = Stock::where('shop_id',auth()->user()->current_shop_id)->where('platform_item_id',$request->item_id)->where('platform_variation_id',$request->variation_id)->first();
            $stock->costs()->where('from_date','<>','1970-01-01')->delete();
            foreach($request->all()['costs'] as $cost){
                StockCost::updateOrCreate(['stock_id' => $stock->id,'from_date' => Carbon::parse($cost['from_date'])->format("Y-m-d")],['cost' => $cost['cost']]);
            }
        });
        return response()->json($request->all());
    }
    
    function importExcel(Request $request){
        if($request->hasFile('excel')){

            $request->validate([
                'excel' => 'required|mimes:xlsx',
            ]);

            $sheets = Excel::import(new InventoryImport(),$request->file('excel'));
            // dd($sheets);
            // dd(123);
            return redirect('/inventory')->with('success_msgs',['Inventory successfully updated!']);
        }
    }

    function downloadExcelTemplate(){
        $rows = [];
        $rows[] = ['Stock ID','Item Name','Item Variation & SKU','Days To Supply','Safety Stock','Cost (initial)','Cost (now)'];
        $stocks = Stock::with('costs')->where('shop_id',auth()->user()->current_shop_id)->get();
        $products = (new ShopeeProductModel)->getDetailedItemsDetail();;
        // dd($stocks);
        foreach($stocks as $stock){
            foreach($products as $product){
                if($product['item_id'] == $stock->platform_item_id){
                    $item_name = $product['name'];
                    $item_sku = $product['item_sku'];
                    $variation_name = null;
                    $variation_sku = null;
                    if($stock->platform_variation_id){
                        foreach($product['variations'] as $variation){
                            if($variation['variation_id'] == $stock->platform_variation_id){
                                $variation_name = $variation['name'];
                                $variation_sku = $variation['variation_sku'];
                                break;
                            }
                        }
                    } 
                    break;    
                } 
            }
            $rows[] =[
                $stock->id,
                $item_name,
                $variation_name.($item_sku.$variation_sku?'['.$item_sku.$variation_sku.']':''),
                (string)$stock->days_to_supply,
                (string)$stock->safety_stock,
                (string)$stock->costs->where('from_date','1970-01-01')->first()->cost,
                (string)$stock->costs->where('from_date',date("Y-m-d"))->first()? $stock->costs->where('from_date',date("Y-m-d"))->first()->cost: '',
            ];
        }

        // }
        // dd($rows);git
        $excel = Excel::download( new InventoryTemplateExport($rows),'update-inventory-template.xlsx');
 
        return $excel;
    }

    function syncItemsWithLazadaMapBySku(Request $request){
        $lazadaShop = Shop::find($request->mapped_shop_id);
        $lazadaShopModel = new LazadaProductModel($lazadaShop);
        $lazadaProducts = $lazadaShopModel->getProducts();
        // dd($lazadaProducts);
        $shopeeProductModel = new ShopeeProductModel;
        $shopeeItems = $shopeeProductModel->getDetailedItemsDetail();

        foreach($shopeeItems as $item){
            if($item['variations']){
                foreach($item['variations'] as $variation){
                    foreach($lazadaProducts as $lzdProduct){
                        if($item['item_sku'].$variation['variation_sku'] == $lzdProduct['skus'][0]['SellerSku']){
                            $stock = Stock::where('platform_item_id',$item['item_id'])->where('platform_variation_id',$variation['variation_id'])->where('shop_id',Auth::user()->current_shop_id)->first();
                            $stockSync = StockSync::updateOrCreate(['stock_id' => $stock->id,'mapped_shop_id' => $request->mapped_shop_id],
                            [
                                'item_id' => $lzdProduct['item_id'],
                                'seller_sku' => $lzdProduct['skus'][0]['SellerSku'],
                                'sku_id' => $lzdProduct['skus'][0]['SkuId'],
                                'sync' => true,
                                'last_sync_time' => now(),
                            ]);
                            break;
                        }
                    }
                }
            }else{
                foreach($lazadaProducts as $lzdProduct){
                    if($item['item_sku'] == $lzdProduct['skus'][0]['SellerSku']){
                        $stock = Stock::where('platform_item_id',$item['item_id'])->where('platform_variation_id',0)->where('shop_id',Auth::user()->current_shop_id)->first();
                        $stockSync = StockSync::updateOrCreate(['stock_id' => $stock->id,'mapped_shop_id' => $request->mapped_shop_id],
                        [
                            'item_id' => $lzdProduct['item_id'],
                            'seller_sku' => $lzdProduct['skus'][0]['SellerSku'],
                            'sku_id' => $lzdProduct['skus'][0]['SkuId'],
                            'sync' => true,
                            'last_sync_time' => now(),
                        ]);
                        break;
                    }
                }
            }
        }
        return redirect()->back();
    }

    function saveSyncItemsWithLazada(Request $request){
        $data = ($request->all());
        foreach($data['item_id'] as $key => $item_id){
            if($data['lazada_product_id'][$key]){

                $stock = Stock::where('platform_item_id',$data['item_id'][$key])->where('platform_variation_id',$data['variation_id'][$key])->where('shop_id',Auth::user()->current_shop_id)->first();
                $stockSync = StockSync::updateOrCreate(['stock_id' => $stock->id,'mapped_shop_id' => $data['mapped_shop_id']],
                [
                    'item_id' => $data['lazada_product_id'][$key],
                    'seller_sku' => $data['lazada_seller_sku'][$key],
                    'sku_id' => $data['lazada_sku_id'][$key],
                    'sync' => true,
                    'last_sync_time' => now(),
                ]);
            }else{
                $stock = Stock::where('platform_item_id',$data['item_id'][$key])->where('platform_variation_id',$data['variation_id'][$key])->where('shop_id',Auth::user()->current_shop_id)->first();
                StockSync::where(['stock_id' => $stock->id,'mapped_shop_id' => $data['mapped_shop_id']])->delete();
            }
        }
        return redirect()->back();
    }
    
    function syncItemsWithLazada(Request $request){
        $lazadaShops = collect(getShopsSession())->where('platform','LAZADA')->toArray();
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
        $shopeeItems = $shopeeProductModel->getDetailedItemsDetail();
        return view('sync-items.index',compact('lazadaShops','shopeeItems','shop','products'));
    }

    function addItemsToLazada(){
        $lazadaShops = collect(getShopsSession())->where('platform','LAZADA')->toArray();
        
        $shopeeProductModel = new ShopeeProductModel;
        $shopeeItems = $shopeeProductModel->getDetailedItemsDetail();
        return view('add-items.index',compact('lazadaShops','shopeeItems'));
    }

    function createLazadaItems(Request $request){

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

        $shopeeItemsChunk = $shopeeItems->chunk(10)->toArray();
        // foreach($shopeeItems as $key => $item){
        //     foreach($shopeeCategories as $category){
        //         // dump($item['category_id'],$category['category_id']);
        //         if($item['category_id'] == $category['category_id']){
        //             $shopeeItems[$key]['_append']['category_name'] = $category['category_name']; 
        //             break;
        //         }
        //     }
        // }
        $shop_id = $lazadaShop->id;
        return view('add-items.create',compact('categories','shopeeItemsChunk','shop_id'));
    }

    function exportItemsToLazada(Request $request){
        $lazadaShop = Shop::find($request->shop_id);
        $c = new LazopClient(getLazadaRestApiUrl($lazadaShop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
        $errors = [];
        $newItemsData = $request->except(['shop_id','_token']);

        foreach($newItemsData['name'] as $rowId => $name){
            $newItemData = [];
            foreach($newItemsData as $key => $d){
                if(isset($d[$rowId])){
                    $newItemData[$key] = $d[$rowId];
                }
            }
            // dd($newItemData);
            $_request = new LazopRequest('/images/migrate');
            
            $xml = View::make('lazada-xml.migrate-images',['imageUrls' => $newItemData['__images__']])->render();
            $_request->addApiParam('payload',$xml);
            $batch_id = json_decode($c->execute($_request, getLazadaAccessToken($lazadaShop)),true)['batch_id'];
            

            $_request = new LazopRequest('/image/response/get','GET');
            $_request->addApiParam('batch_id',$batch_id);

            $data = retry(5,function() use($c,$_request,$lazadaShop){
                return json_decode($c->execute($_request, getLazadaAccessToken($lazadaShop)),true)['data'];
            },500);
            
            $newItemData['Images'] = collect($data['images'])->pluck('url')->toArray();
            unset($newItemData['__images__']);
            
            $_request = new LazopRequest('/category/attributes/get','GET');
            $_request->addApiParam('primary_category_id',$newItemData['primary_category_id']);
            $categoryAttr = json_decode($c->execute($_request, getLazadaAccessToken($lazadaShop)),true)['data'];
            
            $xml = View::make('lazada-xml.create-product',['item' => $newItemData,'categoryAttr' => $categoryAttr])->render();
            $_request = new LazopRequest('/product/create');
            $_request->addApiParam('payload',$xml);
            
            $rawResponse = $c->execute($_request, getLazadaAccessToken($lazadaShop));
            \Log::alert($rawResponse);
            $response = json_decode($rawResponse,true);
            if(!isset($response['data'])){
                $errors [] = "Failed to create item ".$name.".";

                continue;
            }else{
                $data = $response['data']; 
            }
            $stock = Stock::where('shop_id',Auth::user()->current_shop_id)
                ->where('platform_item_id',$newItemsData['item_id'][$rowId])
                ->where('platform_variation_id',$newItemsData['variation_id'][$rowId])
                ->first();

            StockSync::create([
                'platform' => 'LAZADA',
                'stock_id' => $stock->id,
                'item_id' => $data['item_id'],
                'seller_sku' => $data['sku_list'][0]['seller_sku'],
                'sku_id' => $data['sku_list'][0]['sku_id'],
                'sync' => false,
                'last_sync_time' => now(),
                'create_time' => now(),
            ]);

            
        }
        if(count($errors)){
            return redirect('/add-items-with-lazada')->withErrors($errors);
        }
        return redirect('/add-items-with-lazada')->with('success_msgs',['Item successfully added!']);
        
    }

    function getCategoryAttribute(Request $request){
        $lazadaShop = Shop::find($request->shop_id);

        $cacheName = 'lazada_category_attr_'.$request->category_id;
        if(Cache::has($cacheName)){
            $categoryAttr = Cache::get($cacheName);
        }else{

            $c = new LazopClient(getLazadaRestApiUrl($lazadaShop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            $_request = new LazopRequest('/category/attributes/get','GET');
            $_request->addApiParam('primary_category_id',$request->category_id);
            $categoryAttr = json_decode($c->execute($_request, getLazadaAccessToken($lazadaShop)),true)['data'];
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

        $brands = LazadaProductModel::getBrands();

        return View::make('add-items.components.item-attribute-input',compact('shopeeItem','categoryAttr','inputArrayNumber','primary_category_id','brands'))->render();
        // return View::make('add-items.components.item-attribute-input',compact('shopeeItem','categoryAttr'))->render();
    }
}
