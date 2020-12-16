<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Support\Facades\Http;
use App\ShopeeOrderModel;
use App\ShopeeProductModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;

Route::webhooks('/get-shopee-push');
Auth::routes();


    Route::group(['middleware' => ['auth']], function () {
        Route::get('/sign-in-platform','ShopController@signIn');
        Route::get('/add-shop','ShopController@addShop');

        Route::get('/test',function(){
            $c = new LazopClient('https://api.lazada.com.my/rest',env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
$request = new LazopRequest('/seller/get','GET');
dd($c->execute($request, '50000101723izwjqbuRf9bCxBnRWfGmypzg1a7221e1RumFzAqrFPRXqt5XHL0'));
            // $client = new LazopClient("https://auth.lazada.com/rest", env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            // $request = new LazopRequest("/auth/token/create");
            // $request->addApiParam("code", "0_124331_uGWN2GA5tWW9wVWoqNTc1VK837004");
            // "access_token" => "50000101723izwjqbuRf9bCxBnRWfGmypzg1a7221e1RumFzAqrFPRXqt5XHL0"
            // "country" => "my"
            // "refresh_token" => "500011016231lDeircUrFWDix6KUwDoISky164c80e4EsVmfyArTgwQwx4OHCG"
            // "account_platform" => "seller_center"
            // "refresh_expires_in" => 2592000
            // "country_user_info" => array:1 [▶]
            // "expires_in" => 604800
            // "account" => "asctest11@mailinator.com"
            // "code" => "0"
            // "request_id" => "0b1d89d116078209178171430"
            // $response = $client->execute($request);
            dd(json_decode($response,true));
            
            dd(Cookie::get('last_sync_time'));
            Auth::logout();
            session()->flush();
            return redirect('/login');
        });
        Route::get('test-error',function(){
            throw new Exception('Test');
        });
        
        Route::group(['middleware' => ['check.got.shop']],function(){   

            Route::get('/shop-settings-setup', function () {  return view('shop-settings-setup'); });
            Route::post('/shop-settings-setup', 'ShopSettingController@create' );

            Route::group(['middleware' => ['check.settings.key']],function(){
                Route::post('/get-orders-esrow-detail', 'ShopeeOrderController@getOrdersEsrowDetail');
                Route::post('/get-products-detail', 'ShopeeProductController@getProductsDetail');
                Route::get('/', function () {           return view('dashboard.homepage'); });
                Route::get('/inventory', function () {         return view('inventory')->with('minimizeSidebar',true); });
                Route::post('/inventory/inbound/{id}/received', 'InboundOrderController@received');
                Route::resource('/inventory/inbound', 'InboundOrderController');
                Route::post('/inventory/update-item', 'ShopeeProductController@update');
                Route::post('/inventory/update-cost', 'ShopeeProductController@updateCost');
                Route::get('/inventory/download-excel-template', 'ShopeeProductController@downloadExcelTemplate');
                Route::post('/inventory/import-excel', 'ShopeeProductController@ImportExcel');
            });
        });
    });
