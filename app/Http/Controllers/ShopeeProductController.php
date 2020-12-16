<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\ShopeeProductModel;
use App\Stock;
use Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventoryTemplateExport;
use App\Imports\InventoryImport;
use App\StockCost;
use Symfony\Contracts\Service\Attribute\Required;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
}
