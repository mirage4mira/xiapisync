<?php

namespace App\Console\Commands;

use App\LazadaProductModel;
use Illuminate\Console\Command;
use App\StockSync;
use App\LazadaOrderModel;
use App\ShopeeStock;
use App\Shop;
use App\ShopeeOrderModel;
use App\ShopeeProductModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;

class SyncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:syncProducts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shops = \App\Shop::all();

        foreach($shops as $shop){
            if($shop->platform == 'SHOPEE'){

                $items = ShopeeStock::join('stock_syncs','shopee_stocks.id','=','stock_syncs.shopee_stock_id')
                ->join('lazada_stocks','stock_syncs.lazada_stock_id','=','lazada_stocks.id')
                ->join('shops','lazada_stocks.shop_id','=','shops.id')
                ->select(
                    'shopee_stocks.*',
                    'lazada_stocks.shop_id as mapped_shop_id',
                    'lazada_stocks.item_id as mapped_item_id',
                    'lazada_stocks.sku_id as mapped_sku_id',
                    'lazada_stocks.seller_sku as mapped_seller_sku',
                    'stock_syncs.last_sync_time',
                    'shops.platform as mapped_shop_platform',
                    'stock_syncs.id as stock_syncs_id'
                )
                ->where('stocks.shop_id',$shop->id)
                ->get();

                #get sync time
                $oldestLastSyncTime = $items->min(function($item){
                    return Carbon::parse($item->last_sync_time);
                });

                $new_last_sync_time = now();

                
                #get orders
                $shopeeOrderModel = new ShopeeOrderModel(Carbon::parse($oldestLastSyncTime),$new_last_sync_time,$shop);
                $shopeeOrders = $shopeeOrderModel->getOrdersList("ALL")->getOrdersDetail();
                $cancelledShopeeOrders = collect($shopeeOrderModel->getOrdersList2()->getOrdersDetail())->filter(function($order){
                    return $order['order_status'] == "CANCELLED";
                });

                $LazadaOrderModel = new LazadaOrderModel(true,Carbon::parse($oldestLastSyncTime),$new_last_sync_time,$shop);
                $createdLazadaOrders = $LazadaOrderModel->getOrders()->getOrdersItems()->orders;
                
                $LazadaOrderModel = new LazadaOrderModel(false,Carbon::parse($oldestLastSyncTime),$new_last_sync_time,$shop);
                $cancelledLazadaOrders = $LazadaOrderModel->getOrders('cancelled')->getOrdersItems()->orders;
                
                #get products
                $mappedLazadaShopIds = $items->filter(function($item){
                    return $item->mapped_shop_platform == "LAZADA";
                })->pluck('mapped_shop_id')->unique();
    

                $lazadaProductsByShopId = [];
                foreach($mappedLazadaShopIds as $shop_id){
                    $shop = Shop::find($shop_id);
                    $lazadaProductModel = new LazadaProductModel($shop);
                    $_lazadaProducts = $lazadaProductModel->getProducts();
                    $lazadaProductsByShopId[$shop_id] = $_lazadaProducts; 
                }

                $shopeeProductModel = new ShopeeProductModel($shop);
                $shopeeProductModel->getItemsList();
                $shopeeProducts = $shopeeProductModel->getItemsDetail();    
                
                foreach($items as $key => $item){
                    $updateLazadaItem['item_id'] = $item->mapped_item_id;
                    $updateLazadaItem['sku_id'] = $item->mapped_sku_id;
                    $updateLazadaItem['seller_sku'] = $item->mapped_seller_sku;
                    $updateLazadaItem['sales_qty'] = 0;
    
                    foreach($shopeeOrders as $order){
                        if(Carbon::parse($order['create_time'])->gte(Carbon::parse($item->last_sync_time)) && Carbon::parse($order['create_time'])->lte($new_last_sync_time)){
                            foreach($order['items'] as $orderItem){
                                if($orderItem['item_id'] == $item['platform_item_id'] && $orderItem['variation_id']  == $item['platform_variation_id']){
                                    $updateLazadaItem['sales_qty'] += $orderItem['variation_quantity_purchased'];
                                    break;
                                }
                            }
                        }
                    } 
    
                    foreach($cancelledShopeeOrders as $order){
                        if(Carbon::parse($order['update_time'])->gte(Carbon::parse($item->last_sync_time)) && Carbon::parse($order['update_time'])->lte($new_last_sync_time)){
                            foreach($order['items'] as $orderItem){
                                if($orderItem['item_id'] == $item['platform_item_id'] && $orderItem['variation_id']  == $item['platform_variation_id']){
                                    $updateLazadaItem['sales_qty'] -= $orderItem['variation_quantity_purchased'];
                                    break;
                                }
                            }
                        }
                    }
                    
                    foreach($lazadaProductsByShopId[$item->mapped_shop_id] as $lazadaProduct){
                        if($updateLazadaItem['item_id'] == $lazadaProduct['item_id']){
                            $updateLazadaItem['quantity'] = $lazadaProduct['skus'][0]['quantity'];
                            $updateLazadaItem['latest_quantity'] = $lazadaProduct['skus'][0]['quantity'] - $updateLazadaItem['sales_qty'];
                            if($updateLazadaItem['latest_quantity'] < 0){
                                $updateLazadaItem['latest_quantity'] = 0;
                            }
                            break;
                        }
                    }

                    if($updateLazadaItem['sales_qty'] > 0){
                        $lazadaShop = Shop::find($item->mapped_shop_id);
                        $lazadaProductModel = new LazadaProductModel($lazadaShop);
                        $lazadaProductModel->updatePriceQuantity($updateLazadaItem);
                        // $c = new LazopClient(getLazadaRestApiUrl($lazadaShop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
                        // $request = new LazopRequest('/product/price_quantity/update');
                        // $xml = View::make('lazada-xml.update-price-quantity',['items' => [$updateLazadaItem]])->render();
                        // $request->addApiParam('payload',$xml);
                        // $c->execute($request, getLazadaAccessToken($lazadaShop));
                    }

                    $updateShopeeItem['platform_item_id'] = $item['platform_item_id'];
                    $updateShopeeItem['platform_variation_id'] = $item['platform_variation_id'];
                    $updateShopeeItem['sales_qty'] = 0;
                    foreach($createdLazadaOrders as $lazadaOrder){
                        foreach($lazadaOrder['_items'] as $lazadaItem){
                            if($lazadaItem['sku'] == $item['mapped_seller_sku']){
                                $updateShopeeItem['sales_qty'] += 1;
                                break;
                            }
                        }
                    }
                    
                    foreach($cancelledLazadaOrders as $lazadaOrder){
                        foreach($lazadaOrder['_items'] as $lazadaItem){
                            if($lazadaItem['sku'] == $item['mapped_seller_sku']){
                                $updateShopeeItem['sales_qty'] -= 1;
                                break;
                            }
                        }
                    }

                    foreach($shopeeProducts as $shopeeProduct){
                        if(!empty($shopeeProduct['variations'])){
                            foreach($shopeeProduct['variations'] as $variation){
                                if($updateShopeeItem['platform_item_id'] == $shopeeProduct['item_id'] && $updateShopeeItem['platform_variation_id'] == $variation['variation_id']){
                               
                                    $updateShopeeItem['quantity'] = $variation['stock'];
                                    $updateShopeeItem['latest_quantity'] = $variation['stock'] - $updateShopeeItem['sales_qty'];
                                    if($updateShopeeItem['latest_quantity'] < 0){
                                        $updateShopeeItem['latest_quantity'] = 0;
                                    }         
                                    break;                       
                                }     
                            }
                        }else{
                            if($updateShopeeItem['platform_item_id'] == $shopeeProduct['item_id']){
                                $updateShopeeItem['quantity'] = $shopeeProduct['stock'];
                                $updateShopeeItem['latest_quantity'] = $shopeeProduct['stock'] - $updateShopeeItem['sales_qty'];
                                if($updateShopeeItem['latest_quantity'] < 0){
                                    $updateShopeeItem['latest_quantity'] = 0;
                                }
                                break;
                            }
                        }
                    }
                    if($updateShopeeItem['sales_qty']){
                        $data['product_id'] = $updateShopeeItem['platform_item_id'];
                        $data['variation_id'] = $updateShopeeItem['platform_variation_id'];
                        $data['stock_quantity'] = $updateShopeeItem['latest_quantity'];
                        $shopeeProductModel->updateStock($data);
                    }

                    $stock_sync = StockSync::find($item->stock_syncs_id);
                    $stock_sync->last_sync_time = $new_last_sync_time;
                    $stock_sync->save();
                }

            }
            
            // #update lazada items
            
            // $mappedLazadaShopIds = $items->filter(function($item){
            //     return $item->mapped_shop_platform == "LAZADA";
            // })->pluck('mapped_shop_id')->unique();

            // $lazadaProductsByShopId = [];
            // foreach($mappedLazadaShopIds as $shop_id){
            //     $shop = Shop::find($shop_id);
            //     $lazadaProductModel = new LazadaProductModel($shop);
            //     $_lazadaProducts = $lazadaProductModel->getProducts();
            //     $lazadaProductsByShopId[$shop_id] = $_lazadaProducts; 
            // }

            // $lazadaItemsToUpdateByShopId = [];
            // foreach($items as $key => $item){
            //     $lazadaItemsToUpdate['item_id'] = $item->mapped_item_id;
            //     $lazadaItemsToUpdate['sku_id'] = $item->mapped_sku_id;
            //     $lazadaItemsToUpdate['seller_sku'] = $item->mapped_seller_sku;
            //     $lazadaItemsToUpdate['sales_qty'] = 0;

            //     foreach($shopeeOrders as $order){
            //         if(Carbon::parse($order['create_time'])->gte(Carbon::parse($item->last_sync_time)) && Carbon::parse($order['create_time'])->lte($new_last_sync_time)){
            //             foreach($order['items'] as $orderItem){
            //                 if($orderItem['item_id'] == $item['platform_item_id'] && $orderItem['variation_id']  == $item['platform_variation_id']){
            //                     $lazadaItemsToUpdate['sales_qty'] += $orderItem['variation_quantity_purchased'];
            //                     break;
            //                 }
            //             }
            //         }
            //     } 

            //     foreach($cancelledShopeeOrders as $order){
            //         if(Carbon::parse($order['update_time'])->gte(Carbon::parse($item->last_sync_time)) && Carbon::parse($order['update_time'])->lte($new_last_sync_time)){
            //             foreach($order['items'] as $orderItem){
            //                 if($orderItem['item_id'] == $item['platform_item_id'] && $orderItem['variation_id']  == $item['platform_variation_id']){
            //                     $lazadaItemsToUpdate['sales_qty'] -= $orderItem['variation_quantity_purchased'];
            //                     break;
            //                 }
            //             }
            //         }
            //     }


            //     $lazadaItemsToUpdateByShopId [$item->mapped_shop_id][] = $lazadaItemsToUpdate; 
            // }
  
            // foreach($lazadaItemsToUpdateByShopId as $shop_id =>  $lazadaItemsToUpdate){
            //     foreach($lazadaItemsToUpdate as $key => $lazadaItemToUpdate){
            //         foreach($lazadaProductsByShopId as $shop_id2 => $lazadaProducts){
            //             if($shop_id == $shop_id2){
            //                 foreach($lazadaProducts as $lazadaProduct){
            //                     if($lazadaItemToUpdate['item_id'] == $lazadaProduct['item_id']){
            //                         $lazadaItemsToUpdateByShopId[$shop_id][$key]['quantity'] = $lazadaProduct['skus'][0]['quantity'];
            //                         $lazadaItemsToUpdateByShopId[$shop_id][$key]['latest_quantity'] = $lazadaProduct['skus'][0]['quantity'] - $lazadaItemToUpdate['sales_qty'];
            //                         if($lazadaItemsToUpdateByShopId[$shop_id][$key]['latest_quantity'] < 0){
            //                             $lazadaItemsToUpdateByShopId[$shop_id][$key]['latest_quantity'] = 0;
            //                         }
            //                         break;
            //                     }
            //                 }
            //             }
            //         }
            //     }
            // }
            // // dd(123);
            // // dd($lazadaItemsToUpdateByShopId);
            // // dd($lazadaItemsToUpdateByShopId);

            // foreach($lazadaItemsToUpdateByShopId as $shop_id =>  $lazadaItemsToUpdate){
            //     $lazadaItemsToUpdate = collect($lazadaItemsToUpdate)->filter(function($lazadaItems){
            //         return $lazadaItems['sales_qty'] > 0;
            //     })->toArray();
            //     // dd($lazadaItemsToUpdate);
            //     $shop = Shop::find($shop_id);
            //     $c = new LazopClient(getLazadaRestApiUrl($shop),env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            //     $request = new LazopRequest('/product/price_quantity/update');
            //     $xml = View::make('lazada-xml.update-price-quantity',['items' => $lazadaItemsToUpdate])->render();
            //     $request->addApiParam('payload',$xml);
            //     // dd($xml);
            //     $c->execute($request, getLazadaAccessToken($shop));
            // }

            // #update Shopee Items
            // $LazadaOrderModel = new LazadaOrderModel(true,Carbon::parse($oldestLastSyncTime),$new_last_sync_time,$shop);
            // $createdLazadaOrders = $LazadaOrderModel->getOrders()->getOrdersItems()->orders;
            
            // $LazadaOrderModel = new LazadaOrderModel(false,Carbon::parse($oldestLastSyncTime),$new_last_sync_time,$shop);
            // $cancelledLazadaOrders = $LazadaOrderModel->getOrders('cancelled')->getOrdersItems()->orders;

            // $updateShopeeItems = [];
            // foreach($items as $item){
            //     $updateShopeeItem['platform_item_id'] = $item['platform_item_id'];
            //     $updateShopeeItem['platform_variation_id'] = $item['platform_variation_id'];
            //     $updateShopeeItem['sales_qty'] = 0;
            //     foreach($createdLazadaOrders as $lazadaOrder){
            //         foreach($lazadaOrder['_items'] as $lazadaItem){
            //             if($lazadaItem['sku'] == $item['mapped_seller_sku']){
            //                 $updateShopeeItem['sales_qty'] += 1;
            //                 break;
            //             }
            //         }
            //     }
            //     foreach($cancelledLazadaOrders as $lazadaOrder){
            //         foreach($lazadaOrder['_items'] as $lazadaItem){
            //             if($lazadaItem['sku'] == $item['mapped_seller_sku']){
            //                 $updateShopeeItem['sales_qty'] -= 1;
            //                 break;
            //             }
            //         }
            //     }
            //     $updateShopeeItems [] = $updateShopeeItem;
            // }
            
            
            // $shopeeProductModel = new ShopeeProductModel($shopeeShop);
            // $shopeeProductModel->getItemsList();
            // $shopeeProducts = $shopeeProductModel->getItemsDetail();

            // foreach($updateShopeeItems as $key => $updateShopeeItem){
            //     foreach($shopeeProducts as $shopeeProduct){
            //         if(!empty($shopeeProduct['variations'])){
            //             foreach($shopeeProduct['variations'] as $variation){
            //                 if($updateShopeeItem['platform_item_id'] == $shopeeProduct['item_id'] && $updateShopeeItem['platform_variation_id'] == $variation['variation_id']){
                           
            //                     $updateShopeeItems[$key]['quantity'] = $variation['stock'];
            //                     $updateShopeeItems[$key]['latest_quantity'] = $variation['stock'] - $updateShopeeItem['sales_qty'];
            //                     if($updateShopeeItems[$key]['latest_quantity'] < 0){
            //                         $updateShopeeItems[$key]['latest_quantity'] = 0;
            //                     }         
            //                     break;                       
            //                 }     
            //             }
            //         }else{
            //             if($updateShopeeItem['platform_item_id'] == $shopeeProduct['item_id']){
            //                 $updateShopeeItems[$key]['quantity'] = $shopeeProduct['stock'];
            //                 $updateShopeeItems[$key]['latest_quantity'] = $shopeeProduct['stock'] - $updateShopeeItem['sales_qty'];
            //                 if($updateShopeeItems[$key]['latest_quantity'] < 0){
            //                     $updateShopeeItems[$key]['latest_quantity'] = 0;
            //                 }
            //                 break;
            //             }
            //         }
            //     }
            // }

            // foreach($updateShopeeItems as $updateShopeeItem){
            //     if($updateShopeeItem['sales_qty']){
            //         // dump($updateShopeeItem);
            //         $data['product_id'] = $updateShopeeItem['platform_item_id'];
            //         $data['variation_id'] = $updateShopeeItem['platform_variation_id'];
            //         $data['stock_quantity'] = $updateShopeeItem['latest_quantity'];
            //         $shopeeProductModel->updateStock($data);
            //     }
            // }

            // foreach($items as $item){
            //     $stock_syncs = StockSync::where('stock_id',$item->id)->get();
            //     foreach($stock_syncs as $stock_sync){
            //         $stock_sync->last_sync_time = $new_last_sync_time;
            //         $stock_sync->save();
            //     }
            // }

        }
    }
}
