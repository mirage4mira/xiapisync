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
        
        Route::group(['middleware' => ['check.got.shop']],function(){
            Route::get('/123',function(){
                (new ShopeeProductModel())->getItemsDetail();
            });

            Route::post('/get-orders-esrow-detail', 'ShopeeOrderController@getOrdersEsrowDetail');
            Route::post('/get-products-detail', 'ShopeeProductController@getProductsDetail');
            Route::get('/shop-settings-setup', function () {  return view('shop-settings-setup'); });
            Route::post('/shop-settings-setup', 'ShopSettingController@create' );
            Route::get('/', function () {           return view('dashboard.homepage'); })->middleware('check.settings.key');
            Route::get('/inventory', function () {         return view('inventory'); })->middleware('check.settings.key');
        });
    });
