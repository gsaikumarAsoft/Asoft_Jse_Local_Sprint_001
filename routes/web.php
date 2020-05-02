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

use App\Mail\BrokerUserAccountCreated;
use App\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

Route::group(['prefix' => '/verify'], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@verifyForeign');
    Route::get('/account/{id}/{action}', 'AccountVerificationController@foreignBrokerUpdate');
});

Route::group(['prefix' => '/verify-trading-account'], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@verifyB2B');
    Route::get('/account/{id}/{action}', 'AccountVerificationController@foreignBrokerUpdate');
    Route::get('/b2b/{id}/{action}', 'AccountVerificationController@saveB2B');
});


Route::group(['prefix' => '/verify-broker-user', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@verifyBrokerUser');
});

Route::group(['prefix' => '/verify-broker-trader', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@verifyBrokerTrader');
});

Route::group(['prefix' => '/verify-local-broker', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/{id}/{actions}', 'AccountVerificationController@verifyLocal');
});
Route::group(['prefix' => '/verify-settlement-account', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@verifySettlement');
});



Auth::routes();


Route::get('/create-user', 'ApplicationController@ok');




Route::group(['prefix' => '/', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', 'ApplicationController@index')->name('home');
});


Route::group(['prefix' => '/profile', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', 'ProfileController@index')->name('home');
    Route::post('/store', "ProfileController@store");
});



Route::group(['prefix' => '/broker', 'middleware' => ['App\Http\Middleware\LocalBrokerAdminMiddleware']], function () {
    Route::get('/', "BrokerController@index");
    Route::get('/get-users', 'BrokerController@getUsers');
    Route::get('/local-brokers', 'ApplicationController@brokerList');
    Route::get('/company', 'BrokerController@company');
    Route::get('/users', 'BrokerController@users'); 
    Route::get('/get-users', 'BrokerController@brokerUsers'); 
    Route::get('/broker-users', 'UserController@index');
    Route::get('/broker-clients', 'ClientController@index');
    Route::get('/traders', 'BrokerController@traders');
    Route::get('/trading-accounts', 'BrokerController@traderList');
    Route::get('/broker-trading-accounts', 'BrokerController@tradingAccounts');
    Route::get('/settlements', 'BrokerController@settlements');
    Route::get('/orders', 'BrokerController@orders');
    Route::get('/approvals', 'BrokerController@approvals');
    Route::post('/store-broker-client-order', "BrokerController@clientOrder");
    Route::delete('/destroy-broker-client-order/{id}', "BrokerController@destroyOrder");
    Route::post('/store-broker', "UserController@store");
    Route::delete('/user-broker-delete/{id}', 'UserController@destroy');
    Route::post('/store-broker-client', "TraderController@store");
    Route::delete('/client-broker-delete/{id}', 'ClientController@destroy');

    // Route::get('/', 'BrokerController@log');
    Route::get('/broker-list', 'ApplicationController@brokerList');
    Route::get('/foreign-broker-list', 'ApplicationController@foreignBrokerList');
});
Route::group(['prefix' => '/operator', 'middleware' => ['App\Http\Middleware\LocalBrokerOperatorMiddleware']], function () {
    Route::get('/', "OperatorController@index");
    Route::get('/clients', 'OperatorController@clients');
    Route::get('/operator-clients', 'OperatorController@clientList');
    Route::get('/trading-accounts', 'OperatorController@traderList');
    Route::post('/store-broker-trader', "TraderController@storeOperatorClient");
    Route::post('/store-operator-client-order', "OperatorController@operatorClientOrder");
    Route::get('/broker-users', 'UserController@index');
    Route::delete('/client-broker-delete/{id}', 'ClientController@destroy');
    Route::get('/broker-trading-accounts', 'BrokerController@operatorTradingAccounts');
    // Route::get('/orders', "BrokerTraderController@orderList");
    Route::get('/orders', 'OperatorController@orders');
});

Route::group(['prefix' => '/trader-broker', 'middleware' => ['App\Http\Middleware\LocalBrokerTraderMiddleware']], function () {
    Route::get('/', "BrokerTraderController@index");
    Route::get('/orders', "BrokerTraderController@orderList");
});



Route::group(['prefix' => '/jse-admin', 'middleware' => ['App\Http\Middleware\AdminMiddleware']], function () {
    Route::get('/', "BrokerController@index")->name('jse-admin');
    Route::get('foreign-broker-list/', 'ApplicationController@indexCad');
    Route::get('/foreign-brokers', 'ApplicationController@foreignBrokerList');
    Route::get('/local-broker-list', 'LocalBrokerController@index');
    Route::get('/local-brokers', 'ApplicationController@brokerList');
    Route::get('/settlements', 'ApplicationController@settlements');
    Route::get('/settlement-list', 'ApplicationController@settlementBrokerList');
    Route::post('/settlement-broker-update/{id}', 'ApplicationController@updateLocalBroker');
    Route::post('/store-settlement-broker', "ApplicationController@storeSettlementBroker");
    Route::delete('/settlement-account-delete/{id}', "ApplicationController@destroyBSA");
    Route::post('/store-trading-account', "TradingController@store");
    Route::get('/trader-list', "TraderController@list");
    Route::delete('/trading-account-delete/{id}', "TradingController@destroy");
    // Route::put('/trading-account-update/{id}', "TradingController@update");

    Route::post('/store-local-broker', "ApplicationController@storeLocalBroker");
    Route::delete('/local-broker-delete/{id}', 'ApplicationController@destroyLocalBroker');
    Route::put('/local-broker-update/{id}', 'ApplicationController@updateLocalBroker');

    Route::post('/store-foreign-broker', "ApplicationController@storeForeignBroker");
    Route::delete('/foreign-broker-delete/{id}', 'ApplicationController@destroyForeignBroker');
    Route::put('/foreign-broker-update/{id}', 'ApplicationController@updateForeignBroker');



    Route::get('logActivity', 'ApplicationController@logActivity');

    Route::get('/dma-home', 'BrokerController@index');
    Route::get('/broker-2-broker', 'ApplicationController@b2b');
    Route::get('/', 'BrokerController@log');
});



Route::get('/logout', 'Auth\LoginController@logout');
// // Auth::routes(['verify' => true]);

// // Route::get('/', 'ApplicationController@index');
// // Route::get('/home', 'ApplicationController@index');

// // Route::get('broker-list/Local', 'ApplicationController@index');
// // Route::get('/broker-list', 'ApplicationController@brokerList');
// // Route::post('/store-local-broker', "ApplicationController@storeLocalBroker");
// // Route::delete('/local-broker-delete/{id}', 'ApplicationController@destroyLocalBroker');
// // Route::put('/local-broker-update/{id}', 'ApplicationController@updateLocalBroker');




// // Route::get('foreign-broker-list/', 'ApplicationController@indexCad');
// // Route::get('/foreign-broker-list', 'ApplicationController@foreignBrokerList');


// Route::group(['prefix' => '/', 'middleware' => ['auth', 'verified']], function () {
//     Route::get('/', 'ApplicationController@index')->name('home');
// });





Route::put('nv9w8yp8rbwg4t/', function () {

    $user = Auth::user();
    // return $user;
    $user = User::with('roles','broker')->find($user->id);
    return $user;
});


// Route::group(['prefix' => '/broker', 'middleware' => ['auth', 'verified']], function () {
//     Route::get('/', "BrokerController@index");
//     Route::get('/get-users', 'BrokerController@getUsers');
//     Route::get('/company', 'BrokerController@company');
//     Route::get('/users', 'BrokerController@users');
//     Route::get('/clients', 'BrokerController@clients');
//     Route::get('/orders', 'BrokerController@orders');
//     Route::get('/approvals', 'BrokerController@approvals');
//     Route::post('/store-broker-client-order', "BrokerController@clientOrder");
//     //  Route::get('/','BrokerController@log');


// });

// Route::group(['prefix' => '/jse-admin', 'middleware' => ['auth', 'verified']], function () {
//     Route::get('settlements', 'ApplicationController@settlements');
//     Route::get('/settlement-list', 'ApplicationController@settlementBrokerList');
//     Route::post('/store-settlement-broker', "ApplicationController@storeSettlementBroker");
//     // Route::delete('/settlement-broker-delete/{id}', 'ApplicationController@destroyLocalBroker');
//     // Route::put('/settlement-broker-update/{id}', 'ApplicationController@updateLocalBroker');


//     Route::get('logActivity', 'ApplicationController@logActivity');




//     // Routing For Broker related data
//     Route::get('/dma-home', 'BrokerController@index');
//     Route::get('/broker-2-broker', 'ApplicationController@b2b');


// });




// Route::get('/home', 'HomeController@index')->name('home');
