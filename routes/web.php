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


Route::webhooks('/get-shopee-push');
Auth::routes();


    Route::group(['middleware' => ['auth']], function () {
        Route::get('/sign-in-platform','ShopController@signIn');
        Route::get('/add-shop','ShopController@addShop');

        Route::get('/test',function(){
            // dd(getShopsSession());
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
                Route::get('/inventory/download-excel-template', 'ShopeeProductController@downloadExcelTemplate');
                Route::post('/inventory/import-excel', 'ShopeeProductController@ImportExcel');
            });
        });
    });
