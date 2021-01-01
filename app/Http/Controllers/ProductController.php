<?php

namespace App\Http\Controllers;


use Auth;
use Async;
use App\Shop;
use App\StockCost;
use App\StockSync;
use Carbon\Carbon;
use App\LazadaStock;
use App\ShopeeStock;
use App\LazadaProductModel;
use App\ShopeeProductModel;
use Illuminate\Http\Request;
use App\Imports\InventoryImport;
use Paulwscom\Lazada\LazopClient;
use Illuminate\Support\Facades\DB;
use Paulwscom\Lazada\LazopRequest;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use App\Exports\InventoryTemplateExport;
use Illuminate\Support\Facades\Validator;
use Symfony\Contracts\Service\Attribute\Required;

class ProductController extends Controller
{
    public function get()
    {
        if(Auth::user()->currentShop->platform == "SHOPEE"){

            $shopeeProductModel = new ShopeeProductModel(); 
            $products = $shopeeProductModel->getDetailedItemsDetail();
            
            
            return response()->json($products);
        }
        elseif(Auth::user()->currentShop->platform == "LAZADA"){
            $LazadaProductModel = new LazadaProductModel(); 
            $products = $LazadaProductModel->getDetailedProducts();
            
            return response()->json($products);
        }
    }

    function update(Request $request)
    {
        $shopeeProductModel = new ShopeeProductModel();
        $lazadaProductModel = new LazadaProductModel();

        if(Auth::user()->currentShop->platform == "SHOPEE"){
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
                $stock = ShopeeStock::create([
                    'shop_id' => auth()->user()->current_shop_id,
                    'platform_item_id' => $data->item_id,
                    'platform_variation_id' => $data->variation_id,
                    // 'inbound' => $data->inbound,
                    'safety_stock' => $data->reserved,
                    'days_to_supply' => $data->days_to_supply
                ]);
            } else {
                $stock = tap(ShopeeStock::where(['id' => $data->stock_id])->where(['shop_id' => auth()->user()->current_shop_id]))
                    ->update([
                        // 'inbound' => $data->inbound,
                        'safety_stock' => $data->reserved,
                        'days_to_supply' => $data->days_to_supply
                    ])->first();
            }
            
            if ($data->update_stock_count == true) {
                $updateStockData = ['product_id' => $stock->platform_item_id, 'stock_quantity' => $data->available];
                if ($stock->platform_variation_id) $updateStockData['variation_id'] = $stock->platform_variation_id;
                $shopeeProductModel->updateStock($updateStockData);

                foreach($stock->stock_syncs as $stock_syncs){
                    $lazadaStock = LazadaStock::find($stock_syncs->lazada_stock_id);
                    $lazadaProductModel->updatePriceQuantity(['item_id' => $lazadaStock->platform_item_id, 'sku_id' => $lazadaStock->platform_sku_id, 'seller_sku' => $lazadaStock->platform_seller_sku,'latest_quantity' => $data->available]);
                }
            }


            if ($data->update_price == true) {
                $updatePriceData = ['product_id' => $stock->platform_item_id, 'price' => $data->price];
                if ($stock->platform_variation_id) $updatePriceData['variation_id'] = $stock->platform_variation_id;
                $shopeeProductModel->updatePrice($updatePriceData);

                foreach($stock->stock_syncs as $stock_syncs){
                    $lazadaStock = LazadaStock::find($stock_syncs->lazada_stock_id);
                    $lazadaProductModel->updatePriceQuantity(['item_id' => $lazadaStock->platform_item_id, 'sku_id' => $lazadaStock->platform_sku_id, 'seller_sku' => $lazadaStock->platform_seller_sku,'latest_quantity' => $data->available]);
                }
            }
            return response()->json();
        }
        elseif(Auth::user()->currentShop->platform == "LAZADA"){
            $data = json_decode($request->data);

            $validator = Validator::make((array)$data, [
                'price' => 'required|numeric|gte:0.5',
                // 'inbound' => 'required|numeric',
                'available' => 'required|numeric',
                'reserved' => 'required|numeric',
                'days_to_supply' => 'required|numeric',
                'item_id' => 'required|numeric',

                'stock_id' => 'required|numeric',

            ]);

            handleValidatorFails($request, $validator);


            if (!$data->stock_id) {
                $stock = LazadaStock::create([
                    'shop_id' => auth()->user()->current_shop_id,
                    'platform_item_id' => $data->item_id,
                    'platform_variation_id' => $data->variation_id,
                    // 'inbound' => $data->inbound,
                    'safety_stock' => $data->reserved,
                    'days_to_supply' => $data->days_to_supply
                ]);
            } else {
                $stock = tap(LazadaStock::where(['id' => $data->stock_id])->where(['shop_id' => auth()->user()->current_shop_id]))
                    ->update([
                        // 'inbound' => $data->inbound,
                        'safety_stock' => $data->reserved,
                        'days_to_supply' => $data->days_to_supply
                    ])->first();
            }
            
            
            $updateStockData = ['item_id' => $stock->platform_item_id, 'sku_id' => $stock->platform_sku_id, 'seller_sku' => $stock->platform_seller_sku];
            
            $updatePriceQuantity = false;
            if ($data->update_stock_count == true) {
                $updateStockData ['latest_quantity'] = $data->available;
                $updatePriceQuantity = true;
            }
            if($data->update_price == true){
                $updateStockData ['price'] = $data->price;
                $updatePriceQuantity = true;
            }
            if($updatePriceQuantity){
                $lazadaProductModel->updatePriceQuantity($updateStockData);
                if($data->update_stock_count){
                    foreach($stock->stock_syncs as $stock_syncs){
                        $shopeeStock = ShopeeStock::find($stock_syncs->shopee_stock_id);
                        $updateStockData = ['product_id' => $shopeeStock->platform_item_id, 'stock_quantity' => $data->available];
                        if ($shopeeStock->platform_variation_id) $updateStockData['variation_id'] = $shopeeStock->platform_variation_id;
                        $shopeeProductModel->updateStock($updateStockData);
                    }
                }
            }

            return response()->json();
        }
    }

    function updateCost(Request $request){
        
        if(Auth::user()->currentShop->platform == "SHOPEE"){

            DB::transaction(function() use ($request){
                $stock = ShopeeStock::where('shop_id',auth()->user()->current_shop_id)->where('platform_item_id',$request->item_id)->where('platform_variation_id',$request->variation_id)->first();
                $stock->costs()->where('from_date','<>','1970-01-01')->delete();
                foreach($request->all()['costs'] as $cost){
                    StockCost::updateOrCreate(['stock_id' => $stock->id,'stock_table_name' => 'shopee_stocks','from_date' => Carbon::parse($cost['from_date'])->format("Y-m-d")],['cost' => $cost['cost']]);
                }
            });
            return response()->json($request->all());
        }elseif(Auth::user()->currentShop->platform == "LAZADA"){
            
            DB::transaction(function() use ($request){
                $stock = LazadaStock::where('shop_id',auth()->user()->current_shop_id)->where('platform_item_id',$request->item_id)->first();
                $stock->costs()->where('from_date','<>','1970-01-01')->delete();
                foreach($request->all()['costs'] as $cost){
                    StockCost::updateOrCreate(['stock_id' => $stock->id,'stock_table_name' => 'lazada_stocks','from_date' => Carbon::parse($cost['from_date'])->format("Y-m-d")],['cost' => $cost['cost']]);
                }
            });
            return response()->json($request->all());
        }
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
        if(auth()->user()->currentShop->platform == "SHOPEE"){

            $rows = [];
            $rows[] = ['Stock ID','Item Name','Item Variation & SKU','Days To Supply','Safety Stock','Cost (initial)','Cost (now)'];
            $stocks = ShopeeStock::with('costs')->where('shop_id',auth()->user()->current_shop_id)->get();
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
    
            $excel = Excel::download( new InventoryTemplateExport($rows),'update-inventory-template.xlsx');
     
            return $excel;
        }
        elseif(auth()->user()->currentShop->platform == "LAZADA"){
            
            $rows = [];
            $rows[] = ['Stock ID','Item Name','Item SKU','Days To Supply','Safety Stock','Cost (initial)','Cost (now)'];
            $stocks = LazadaStock::with('costs')->where('shop_id',auth()->user()->current_shop_id)->get();
            $products = (new LazadaProductModel)->getDetailedProducts();;
            // dd($stocks);
            foreach($stocks as $stock){
                foreach($products as $product){
                    if($product['item_id'] == $stock->platform_item_id){
                        $item_name = $product['attributes']['name'];
                        $item_sku = $product['skus'][0]['SellerSku']; 
                        break;    
                    } 
                }
                $rows[] =[
                    $stock->id,
                    $item_name,
                    $item_sku,
                    (string)$stock->days_to_supply,
                    (string)$stock->safety_stock,
                    (string)$stock->costs->where('from_date','1970-01-01')->first()->cost,
                    (string)$stock->costs->where('from_date',date("Y-m-d"))->first()? $stock->costs->where('from_date',date("Y-m-d"))->first()->cost: '',
                ];
            }
    
            $excel = Excel::download( new InventoryTemplateExport($rows),'update-inventory-template.xlsx');
     
            return $excel;
        }
    }



}
