<?php

namespace App\Http\Controllers;
use App\ShopeeProductModel;
use App\InboundOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Stock;
use Carbon\Carbon;

class InboundOrderController extends Controller
{
    public function index(){
        $inboundOrders = InboundOrder::with('stocks')->where('shop_id',\Auth::user()->current_shop_id)->get()->sortByDesc(function($o){ return $o->days_to_arrive;});
        
        $shopeeProductModel = new ShopeeProductModel(); 
        $products = $shopeeProductModel->getDetailedItemsDetail();
        return view('inventory.inbound.index',['inboundOrders'=> $inboundOrders,'products'=> $products]);
    }

    public function create(){
        $inboundOrders = InboundOrder::with('stocks')->where('shop_id',\Auth::user()->current_shop_id)->get();
        $shopeeProductModel = new ShopeeProductModel(); 
        $products = $shopeeProductModel->getDetailedItemsDetail();
        $suppliers = $inboundOrders->pluck('supplier_name')->unique()->sort()->toArray();
        return view('inventory.inbound.create_edit',['products'=> $products,'suppliers' => $suppliers,'inbound_order' => null]);
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
                $stock = Stock::with('costs')->where('shop_id',\Auth::user()->current_shop_id)
                ->where('platform_item_id',$item_id)
                ->where('platform_variation_id',$request->variations_id[$key])
                        ->first();
    
                    $inboundOrder->stocks()->attach($stock,['quantity' => $request->quantities[$key],'cost' => $request->costs[$key]]);
                }
            }
        });

        return redirect('/inventory/inbound')->with('success_msgs',['Inbound Order Successfully Added!']);
    }

    public function show(Request $request,$id){

        $inboundOrders = InboundOrder::with('stocks')->where('shop_id',\Auth::user()->current_shop_id)->get();
        $inboundOrder = InboundOrder::find($id);
        $shopeeProductModel = new ShopeeProductModel(); 
        $products = $shopeeProductModel->getDetailedItemsDetail();
        $suppliers = $inboundOrders->pluck('supplier_name')->unique()->sort()->toArray();
        return view('inventory.inbound.create_edit',['products'=> $products,'suppliers' => $suppliers,'inbound_order' => $inboundOrder]);
    }

    public function update(Request $request,$id){

    $inboundOrder = InboundOrder::where('id',$id)->where('shop_id',\Auth::user()->current_shop_id)->first();
       DB::transaction(function() use ($request,&$inboundOrder){
            $inboundOrder->stocks()->detach();

            $inboundOrder->shop_id = auth()->user()->current_shop_id;
            $inboundOrder->supplier_name = $request->supplier_name;
            $inboundOrder->payment_date = Carbon::parse($request->payment_date)->format('Y-m-d');
            $inboundOrder->reference = $request->reference;
            $inboundOrder->days_to_supply = $request->days_to_supply;
            $inboundOrder->save();

            if($request->items_id){

                foreach($request->items_id as $key => $item_id){
                    $stock = Stock::with('costs')->where('shop_id',\Auth::user()->current_shop_id)
                        ->where('platform_item_id',$item_id)
                        ->where('platform_variation_id',$request->variations_id[$key])
                        ->first();
    
                    $inboundOrder->stocks()->attach($stock,['quantity' => $request->quantities[$key],'cost' => $request->costs[$key]]);
                }
            }
    });
    return redirect('/inventory/inbound')->with("success_msgs",["Inbound Order Successfully Updated"]);

    }

    public function destroy(Request $request,$id){
        
        $inboundOrder = InboundOrder::where('id',$id)->where('shop_id',\Auth::user()->current_shop_id)->first();
        $inboundOrder->stocks()->detach();
        $inboundOrder->delete();

        return redirect('/inventory/inbound')->with("success_msgs",["Inbound Order Successfully Deleted"]);
    }

    public function received(Request $request,$id){
        $inboundOrder = InboundOrder::where('id',$id)->where('shop_id',\Auth::user()->current_shop_id)->first();
        $inboundOrder->stock_received = $request->received;
        $inboundOrder->save();

        $shopeeProductModel = new ShopeeProductModel();

        $items_id = $inboundOrder->stocks->pluck('platform_item_id')->toArray();
        $items = $shopeeProductModel->getItemsDetail($items_id);

        foreach($inboundOrder->stocks as $stock){
            if($stock->platform_variation_id){
                // $_item = null;
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
                // if($_item){
                $shopeeProductModel->updateStock(['product_id'=> $stock->platform_item_id,'stock_quantity' => $stock_quantity]);
                // }

            }
        }
        return response()->json();
    }
}
