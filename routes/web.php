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

use App\Http\Controllers\ShopSettingController;
use Carbon\Carbon;
use App\ShopeeOrderModel;
use App\LazadaProductModel;
use App\ShopeeProductModel;
use Laravel\Ui\Presets\React;
use Paulwscom\Lazada\LazopClient;
use Paulwscom\Lazada\LazopRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Request;

Route::webhooks('/get-shopee-push');
Auth::routes(['verify' => true]);


Route::group(['middleware' => ['auth', 'verified']], function () {

    Route::get('/plan-expired', function () {
        return view('plan-expired');
    });
    Route::get('/payment', 'PaymentController@index');
    Route::post('/payment/stripe', 'PaymentController@payWithStripe');
    
    Route::post('/payment/paypal', ['as' => 'payment', 'uses' => 'PaymentController@payWithpaypal']);
    Route::get('/payment/paypal/status', ['as' => 'status', 'uses' => 'PaymentController@getPaymentStatus']);
    

    Route::group(['middleware' => ['check.plan.expiry']], function () {
        Route::get('/about','AboutController@index');
        Route::get('/shop/sign-in', 'ShopController@signIn');
        Route::get('/shop/add', 'ShopController@addShop');

        Route::get('/docs', function (Request $request) {
            // $shop_id = explode("/",$request->file)[0];
            [$folderName, $shop_id, $filename] = explode("/", $request->file);

            if (in_array($folderName, ['images']) && $shop_id == auth()->user()->current_shop_id) {
                if (file_exists(storage_path($request->file))) {
                    return response()->file(
                        storage_path($request->file)
                    );
                }
            }
        });
        Route::get('/test', function () {
            // $lazadaProductModel = new LazadaProductModel();

            // dd($lazadaProductModel->getProducts()[0]);
            // dd(getShopsSession());
            // dd(setShopsSession());
            // $c = new LazopClient('https://api.lazada.com.my/rest',env('LAZADA_APP_KEY'), env('LAZADA_APP_SECRET'));
            // $request = new LazopRequest('/seller/get','GET');
            // dd($c->execute($request, '50000101903fn9b1cd75debsqf9eDpfszwCIgupamuvkeZlrqcMT1SNn3EgAsS'));
            // $request = new LazopRequest('/category/tree/get',"GET");
            // $request = new LazopRequest('/products/category/tree/get',"GET");
            // dd(json_decode($c->execute($request,"50000101903fn9b1cd75debsqf9eDpfszwCIgupamuvkeZlrqcMT1SNn3EgAsS"),true)["data"][16]);
            // $request->addApiParam("product_name", "garlic powder 100g herb and spice food ingredient grocery");
            // $request = new LazopRequest('/product/category/suggestion/get',"GET");
            // $request->addApiParam("product_name", "Groceries");
            // $request->addApiParam("access_token", "50000101903fn9b1cd75debsqf9eDpfszwCIgupamuvkeZlrqcMT1SNn3EgAsS");
            // dd(json_decode($c->execute($request,"50000101903fn9b1cd75debsqf9eDpfszwCIgupamuvkeZlrqcMT1SNn3EgAsS")));
            // {"access_token":"50000100433yY7bbrNGtjhlv3pPCbZoYllsAtRFZdcsiq11532ec6UVdnwv2Cp","country":"my","refresh_token":"50001100333b1mpacsxgT6JHvyMSc97K2gkQbURfYddU9187928dbNTukyFfx7","country_user_info_list":[{"country":"my","user_id":"69941","seller_id":"33766","short_code":"MY10RW7"}],"account_platform":"seller_center","refresh_expires_in":2216273,"country_user_info":[{"country":"my","user_id":"69941","seller_id":"33766","short_code":"MY10RW7"}],"expires_in":2592000,"account":"asctest11@mailinator.com","code":"0","request_id":"0ba9f84316081966440741963"}
            // $request = new LazopRequest('/seller/get','GET');
            // dd($c->execute($request, '50000101723izwjqbuRf9bCxBnRWfGmypzg1a7221e1RumFzAqrFPRXqt5XHL0'));
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
            // dd(json_decode($response,true));

            // dd(Cookie::get('last_sync_time'));
            Auth::logout();
            session()->flush();
            return redirect('/login');
        });
        Route::get('test-error', function () {
            throw new Exception('Test');
        });

        Route::group(['middleware' => ['check.got.shop']], function () {

            // Route::get('/shop-settings-setup', function () {  return view('shop-settings-setup'); });
            // Route::post('/shop-settings-setup', 'ShopSettingController@create' );

            Route::group(['middleware' => ['check.settings.key']], function () {
                Route::get('/feedback', 'FeedbackController@create');
                Route::post('/feedback', 'FeedbackController@store');
                Route::get('/feedback/sent', 'FeedbackController@sent');


                // Route::post('/payment','PaymentController@paymentProcess');
                // Route::get('/payment','PaymentController@paymentPage');
                Route::get('/user/edit', 'UsersController@edit');
                Route::post('/user/update', 'UsersController@update');

                Route::post('/sync-items/map-by-sku', 'SyncItemController@mapBySku');
                Route::get('/sync-items', 'SyncItemController@index');
                Route::post('/sync-items', 'SyncItemController@store');

                Route::get('/sync-items/add/create', 'SyncItemController@createItems');
                Route::get('/sync-items/add', 'SyncItemController@addItems');
                Route::post('/sync-items/add', 'SyncItemController@exportItems');
                Route::get('/sync-items/add/get-category-attribute-input', 'SyncItemController@getCategoryAttributeInput');

                Route::get('/shop/change', 'ShopController@change');

                Route::post('/orders/get', 'OrderController@get');
                Route::post('/inventory/get', 'ProductController@get');

                Route::get('/', function () {
                    return view('dashboard.homepage');
                });
                Route::get('/inventory', function () {
                    return view('inventory')->with('minimizeSidebar', true);
                });

                Route::post('/inventory/inbound/{id}/received', 'InboundOrderController@received');
                Route::resource('/inventory/inbound', 'InboundOrderController');
                Route::post('/inventory/update-item', 'ProductController@update');
                Route::post('/inventory/update-cost', 'ProductController@updateCost');
                Route::get('/inventory/download-excel-template', 'ProductController@downloadExcelTemplate');
                Route::post('/inventory/import-excel', 'ProductController@ImportExcel');
            });
        });
    });
});
