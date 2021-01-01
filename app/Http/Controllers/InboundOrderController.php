<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use App\LazadaStock;
use App\ShopeeStock;
use App\InboundOrder;
use App\LazadaProductModel;
use App\ShopeeProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundOrderController extends Controller
{
    public function index(){

        if(auth()->user()->currentShop->platform == "SHOPEE"){
            $inboundOrders = InboundOrder::with('shopee_stocks')->where('shop_id',\Auth::user()->current_shop_id)->get()->sortByDesc(function($o){ return $o->days_to_arrive;});
            
            $shopeeProductModel = new ShopeeProductModel(); 
            $products = $shopeeProductModel->getDetailedItemsDetail();
            return view('inventory.inbound.index',['inboundOrders'=> $inboundOrders,'products'=> $products]);
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            $inboundOrders = InboundOrder::with('lazada_stocks')->where('shop_id',\Auth::user()->current_shop_id)->get()->sortByDesc(function($o){ return $o->days_to_arrive;});
            
            $lazadaProductModel = new LazadaProductModel(); 
            $products = $lazadaProductModel->getDetailedProducts();
            return view('inventory.inbound.index',['inboundOrders'=> $inboundOrders,'products'=> $products]);
        }
    }

    public function create(){
        if(auth()->user()->currentShop->platform == "SHOPEE"){
        $inboundOrders = InboundOrder::with('shopee_stocks')->where('shop_id',\Auth::user()->current_shop_id)->get();
        $shopeeProductModel = new ShopeeProductModel(); 
        $products = $shopeeProductModel->getDetailedItemsDetail();
        $suppliers = $inboundOrders->pluck('supplier_name')->unique()->sort()->toArray();
        return view('inventory.inbound.create_edit',['products'=> $products,'suppliers' => $suppliers,'inbound_order' => null]);
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            $inboundOrders = InboundOrder::with('lazada_stocks')->where('shop_id',\Auth::user()->current_shop_id)->get();
            $lazadaProductModel = new LazadaProductModel(); 
            $products = $lazadaProductModel->getDetailedProducts();
            $suppliers = $inboundOrders->pluck('supplier_name')->unique()->sort()->toArray();
            return view('inventory.inbound.create_edit',['products'=> $products,'suppliers' => $suppliers,'inbound_order' => null]);
        
        }
    }
    
    public function store(Request $request){
        $inboundOrder = null;
        DB::transaction(function() use ($request,&$inboundOrder){
            $inboundOrder = InboundOrder::create([
                'shop_id' => auth()->user()->current_shop_id,
                'supplier_name' => $request->supplier_name,
                'payment_date' => Carbon::parse($request->payment_date)->format('Y-m-d'),
                'reference' => $request->reference,
                'days_to_supply' => $request->days_to_supply,
            ]);
            if($request->items_id){
                foreach($request->items_id as $key => $item_id){
                    if(auth()->user()->currentShop->platform == "SHOPEE"){
                        $stock = ShopeeStock::with('costs')->where('shop_id',\Auth::user()->current_shop_id)
                        ->where('platform_item_id',$item_id)
                        ->where('platform_variation_id',$request->variations_id[$key])
                        ->first();
                        
                        $inboundOrder->shopee_stocks()->attach($stock,['quantity' => $request->quantities[$key],'cost' => $request->costs[$key]]);
                    }elseif(auth()->user()->currentShop->platform == "LAZADA"){
                        $stock = LazadaStock::with('costs')->where('shop_id',\Auth::user()->current_shop_id)
                        ->where('platform_item_id',$item_id)
                        ->first();
                        
                        $inboundOrder->lazada_stocks()->attach($stock,['quantity' => $request->quantities[$key],'cost' => $request->costs[$key],'stock_table_name' => 'lazada_stocks']);
                        
                    }
                }
            }
        });

        return redirect('/inventory/inbound')->with('success_msgs',['Inbound Order Successfully Added!']);
    }

    public function show(Request $request,$id){
        if(auth()->user()->currentShop->platform == "SHOPEE"){
            $inboundOrders = InboundOrder::with('shopee_stocks')->where('shop_id',\Auth::user()->current_shop_id)->get();
            $inboundOrder = InboundOrder::findOrFail($id);

            $shopeeProductModel = new ShopeeProductModel(); 
            $products = $shopeeProductModel->getDetailedItemsDetail();
            $suppliers = $inboundOrders->pluck('supplier_name')->unique()->sort()->toArray();
            return view('inventory.inbound.create_edit',['products'=> $products,'suppliers' => $suppliers,'inbound_order' => $inboundOrder]);
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            $inboundOrders = InboundOrder::with('lazada_stocks')->where('shop_id',\Auth::user()->current_shop_id)->get();
            $inboundOrder = InboundOrder::findOrFail($id);
            $lazadaProductModel = new LazadaProductModel(); 
            $products = $lazadaProductModel->getDetailedProducts();
            $suppliers = $inboundOrders->pluck('supplier_name')->unique()->sort()->toArray();
            return view('inventory.inbound.create_edit',['products'=> $products,'suppliers' => $suppliers,'inbound_order' => $inboundOrder]);
        
        }
    }

    public function update(Request $request,$id){

    $inboundOrder = InboundOrder::findOrFail($id);

       DB::transaction(function() use ($request,&$inboundOrder){
            if(auth()->user()->currentShop->platform == "SHOPEE"){
                $inboundOrder->shopee_stocks()->detach();
            }elseif(auth()->user()->currentShop->platform == "LAZADA"){
                $inboundOrder->lazada_stocks()->detach();
            }
            $inboundOrder->shop_id = auth()->user()->current_shop_id;
            $inboundOrder->supplier_name = $request->supplier_name;
            $inboundOrder->payment_date = Carbon::parse($request->payment_date)->format('Y-m-d');
            $inboundOrder->reference = $request->reference;
            $inboundOrder->days_to_supply = $request->days_to_supply;
            $inboundOrder->save();

            if($request->items_id){
                if(auth()->user()->currentShop->platform == "SHOPEE"){

                    foreach($request->items_id as $key => $item_id){
                        $stock = ShopeeStock::with('costs')->where('shop_id',\Auth::user()->current_shop_id)
                            ->where('platform_item_id',$item_id)
                            ->where('platform_variation_id',$request->variations_id[$key])
                            ->first();
        
                        $inboundOrder->shopee_stocks()->attach($stock,['quantity' => $request->quantities[$key],'cost' => $request->costs[$key]]);
                    }
                }elseif(auth()->user()->currentShop->platform == "LAZADA"){
                    
                    foreach($request->items_id as $key => $item_id){
                        $stock = LazadaStock::with('costs')->where('shop_id',\Auth::user()->current_shop_id)
                            ->where('platform_item_id',$item_id)
                            ->first();
               
                        $inboundOrder->lazada_stocks()->attach($stock,['quantity' => $request->quantities[$key],'cost' => $request->costs[$key],'stock_table_name' => 'lazada_stocks']);
                    }
                }
            }
    });
    return redirect('/inventory/inbound')->with("success_msgs",["Inbound Order Successfully Updated"]);

    }

    public function destroy(Request $request,$id){
        
        $inboundOrder = InboundOrder::findOrFail($id);

        if(auth()->user()->currentShop->platform == "SHOPEE"){

            $inboundOrder->shopee_stocks()->detach();
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            $inboundOrder->lazada_stocks()->detach();

        }
        $inboundOrder->delete();

        return redirect('/inventory/inbound')->with("success_msgs",["Inbound Order Successfully Deleted"]);
    }

    public function received(Request $request,$id){
        $inboundOrder = InboundOrder::with('shopee_stocks','lazada_stocks')->findOrFail($id);
        $inboundOrder->stock_received = $request->received;
        $inboundOrder->save();

        $shopeeProductModel = new ShopeeProductModel();
        $lazadaProductModel = new lazadaProductModel();

        if(auth()->user()->currentShop->platform == "SHOPEE"){

            $items_id = $inboundOrder->shopee_stocks->pluck('platform_item_id')->toArray();
            $items = $shopeeProductModel->getItemsDetail($items_id);
    
            foreach($inboundOrder->shopee_stocks as $stock){
                if($stock->platform_variation_id){
                    $_variation = null;
                    foreach($items as $item){
                        foreach($item['variations'] as $variation){
                            if($item['item_id'] == $stock->platform_item_id && $variation['variation_id'] == $stock->platform_variation_id){
                                // $_item = $item;
                                $_variation = $variation;
                                break;
                            }
                        }
                    }
                    if($inboundOrder->stock_received){
                        $stock_quantity = $_variation['stock'] + $stock->pivot->quantity;
                    }else{
                        $stock_quantity = $_variation['stock'] - $stock->pivot->quantity;
                    }
                    $shopeeProductModel->updateStock(['product_id' => $stock->platform_item_id,'variation_id'=>$stock->platform_variation_id,'stock_quantity' =>$stock_quantity ]);

                    foreach($stock->stock_syncs as $stock_sync){
                        $lazadaStock = LazadaStock::find($stock_sync->lazada_stock_id);
                        $lazadaProductModel->updatePriceQuantity(['item_id'=> $lazadaStock->platform_item_id,'sku_id' => $lazadaStock->platform_sku_id ,'seller_sku' => $lazadaStock->platform_seller_sku,'latest_quantity' => $stock_quantity]);
                    }

                }else{
                    $_item = null;
                    foreach($items as $item){
                        if($item['item_id'] == $stock->platform_item_id){
                            $_item = $item;
                            break; 
                        }
                    }
                    if($inboundOrder->stock_received){
                        $stock_quantity = $_item['stock'] + $stock->pivot->quantity;
                    }else{
                        $stock_quantity = $_item['stock'] - $stock->pivot->quantity;
                    }
                    $shopeeProductModel->updateStock(['product_id'=> $stock->platform_item_id,'stock_quantity' => $stock_quantity]);
                    
                    foreach($stock->stock_syncs as $stock_sync){
                        $lazadaStock = LazadaStock::find($stock_sync->lazada_stock_id);
                        $lazadaProductModel->updatePriceQuantity(['item_id'=> $lazadaStock->platform_item_id,'sku_id' => $lazadaStock->platform_sku_id ,'seller_sku' => $lazadaStock->platform_seller_sku,'latest_quantity' => $stock_quantity]);
                    }
                }
            }
        }elseif(auth()->user()->currentShop->platform == "LAZADA"){
            
    
            $items_id = $inboundOrder->lazada_stocks->pluck('platform_item_id')->toArray();
            $items = $lazadaProductModel->getProducts();
    
            foreach($inboundOrder->lazada_stocks as $stock){

                    $_item = null;
                    foreach($items as $item){
                        if($item['item_id'] == $stock->platform_item_id){
                            $_item = $item;
                            break; 
                        }
                    }
                    if($inboundOrder->stock_received){
                        $stock_quantity = $_item['skus'][0]['quantity'] + $stock->pivot->quantity;
                    }else{
                        $stock_quantity = $_item['skus'][0]['quantity'] - $stock->pivot->quantity;
                    }
                    $lazadaProductModel->updatePriceQuantity(['item_id'=> $stock->platform_item_id,'sku_id' => $stock->platform_sku_id ,'seller_sku' => $stock->platform_seller_sku,'latest_quantity' => $stock_quantity]);
                    
                    foreach($stock->stock_syncs as $stock_sync){
                        $shopeeStock = ShopeeStock::find($stock_sync->shopee_stock_id);
                        $updateStockData = ['product_id'=> $shopeeStock->platform_item_id,'stock_quantity' => $stock_quantity];
                        if($shopeeStock->platform_variation_id)$updateStockData['variation_id'] = $shopeeStock->platform_variation_id; 
                        $shopeeProductModel->updateStock($updateStockData);
                    }
                }
        }
        return response()->json();
    }
}
