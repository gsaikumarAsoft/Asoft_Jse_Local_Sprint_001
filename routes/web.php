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

use App\BrokerClient;
use App\BrokerClientOrder;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\Helpers\OrderStatus;
use App\Jobs\ExecutionBalanceUpdate;
use App\LocalBroker;
use App\Mail\BrokerUserAccountVerified;
use App\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;



// External Capabilities
Route::get('/api/', function () {
    return response()->json(['name' => 'DMA 1.5']);
});

Route::get('/DMAMessageDownload', "LightSailController@messageDownload");



Route::post('/update-balances', 'ApplicationController@updateBalances');


Route::get('/home', function () {
    return redirect('/');
});



Route::get('/md-test', "BrokerController@mdTest");

Route::group(['prefix' => '/verify'], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@verifyForeign');
    Route::get('/account/{id}/{action}', 'AccountVerificationController@foreignBrokerUpdate');
    Route::get('/client/{jcsd}/{action}', 'AccountVerificationController@verifyClientDetails');
});

Route::group(['prefix' => '/jse-validation'], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@jseValidation');
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

Route::group(['prefix' => '/verify-settlement-account-foreign', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/{id}/{action}', 'AccountVerificationController@foreignBrokerSettlement');
});



Auth::routes();
Auth::routes(['verify' => true]);


Route::get('/create-user', 'ApplicationController@ok');


Route::group(['prefix' => '/', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', 'ApplicationController@index')->name('home');
});


Route::group(['prefix' => '/profile', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', 'ProfileController@index')->name('home');
    Route::post('/store', "ProfileController@store");
});

Route::group(['prefix' => '/broker', 'middleware' => ['verified', 'App\Http\Middleware\LocalBrokerAdminMiddleware']], function () {
    Route::get('/md-test', "BrokerController@mdTest");
    Route::get('/foreign-brokers', 'ApplicationController@foreignBrokerList');
    Route::get('/reset-unsettled-trades', "BrokerController@resetTrades");
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
    Route::get('/execution', 'BrokerController@execution');
    Route::post('/execution-report', 'BrokerController@logExecution');
    Route::get('/approvals', 'BrokerController@approvals');
    Route::post('/store-broker-client-order', "BrokerController@clientOrder");
    Route::delete('/destroy-broker-client-order/{id}', "BrokerController@destroyOrder");
    Route::post('/store-broker', "UserController@store");
    Route::delete('/user-broker-delete/{id}', 'UserController@destroy');
    Route::post('/store-broker-client', "TraderController@store");
    Route::delete('/client-broker-delete/{id}', 'ClientController@destroy');
    
    Route::get('/manual-cancel-er-for/{id}/{ordStatus}', 'ApplicationController@manualCancelExecutionReport');

    // Route::get('/', 'BrokerController@log');
    Route::get('/broker-list', 'ApplicationController@brokerList');
    Route::get('/foreign-broker-list', 'ApplicationController@foreignBrokerList');
    Route::get('/execution-list-for/{id}/{order_date}', 'ApplicationController@executionListFor');
    
});
Route::group(['prefix' => '/operator', 'middleware' => ['App\Http\Middleware\LocalBrokerOperatorMiddleware']], function () {
    Route::get('/', "OperatorController@index");
    Route::get('/foreign-brokers', 'ApplicationController@foreignBrokerList');
    Route::get('/clients', 'OperatorController@clients');
    Route::get('/execution', 'OperatorController@execution');
    Route::get('/operator-clients', 'OperatorController@clientList');
    Route::get('/trading-accounts', 'OperatorController@traderList');
    Route::post('/store-broker-trader', "TraderController@storeOperatorClient");
    Route::post('/store-operator-client-order', "OperatorController@operatorClientOrder");
    Route::post('/store-broker-client-order', "BrokerController@clientOrder");
    Route::get('/broker-users', 'UserController@index');
    Route::delete('/client-broker-delete/{id}', 'ClientController@destroy');
    Route::delete('/destroy-broker-client-order/{id}', "BrokerController@destroyOrder");
    //Route::get('/broker-trading-accounts', 'OperatorController@operatorTradingAccounts');
    Route::get('/broker-trading-accounts', 'BrokerController@tradingAccounts');
    Route::get('/assigned-trading-accounts', 'BrokerController@operatorTradingAccounts');
    //Route::get('/assigned-trading-accounts', 'OperatorController@operatorTradingAccounts');
    //Route::get('/orders', 'OperatorController@orders');
    Route::get('/orders', 'BrokerController@orders');
    //Route::get('/orders', 'BrokerController@orders');
    Route::get('/foreign-broker-list', 'ApplicationController@foreignBrokerList');
    
    
});

Route::group(['prefix' => '/trader-broker', 'middleware' => ['App\Http\Middleware\LocalBrokerTraderMiddleware']], function () {
    Route::get('/', "BrokerTraderController@index");
    Route::get('/orders', "BrokerTraderController@orderList");
});

Route::group(['prefix' => '/foreign-broker', 'middleware' => ['App\Http\Middleware\ForeignBrokerAdminMiddleware']], function () {
    Route::get('/', "OutboundBrokerController@index");
    Route::get('/settlements', "OutboundBrokerController@settlements");
    Route::get('/tradings', "OutboundBrokerController@trading");
});

Route::group(['prefix' => '/settlement-agent', 'middleware' => ['App\Http\Middleware\SettlementAgentMiddleware']], function () {
    Route::get('/', "SettlementAgentController@index");
});

Route::group(['prefix' => '/jse-admin', 'middleware' => ['App\Http\Middleware\AdminMiddleware']], function () {
    Route::get('internal_audit/', 'ApplicationController@audit');
    Route::get('fetch-broker-messages/', 'ApplicationController@fetchbrokermessages');
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
    Route::get('/expiring-buy-orders', 'ApplicationController@expiringOrders');
    Route::get('/expired-buy-orders', 'ApplicationController@expiredOrders');
    Route::get('/fill-expired-buy-orders', 'ApplicationController@fillExpiredOrders');
    Route::post('/expiring-buy-orders-for/{id}/{order_date}', 'ApplicationController@expiringOrdersFor');
    Route::get('/expiring-orders', 'ApplicationController@expiringOrderList');
    Route::get('/expiring-orders-for/{id}/{order_date}', 'ApplicationController@expiringOrderListFor');
    Route::get('/order-execution', 'ApplicationController@orderExecution');
    Route::get('/execution-list-for/{id}/{order_date}', 'ApplicationController@executionListFor');
    Route::get('/manual-cancel-er-for/{id}/{ordStatus}', 'ApplicationController@manualCancelExecutionReport');
    Route::post('/fill-expired-order', "ApplicationController@fillExpiredOrder");
    
    Route::delete('/destroy-broker-client-order/{id}', "BrokerController@destroyOrder");
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


Route::put('nv9w8yp8rbwg4t/', function () {

    $user = Auth::user();
    // return $user;
    $user = User::with('roles', 'broker')->find($user->id);
    return $user;
});

Route::get('unverified', function () {
    if (auth()->user()) {
        return view('layouts.unverified');
    } else {
        return redirect('/login');
    }
});


Route::get('get-rbc-bai', function () {

    //If we intend to store the data before updating the accounts 
    // $path = 'RBC.json';
    // Storage::disk('public_uploads')->put($path, $file);
    // ===========================================================


    //Check if user has been authenticted before running updater
    if (auth()->user()) {

        //Check if this user is the JSE Admin
        $user = Auth::user()->getRoleNames();

        if ($user[0] === "ADMD") { //Only run if this is the JSE ADMIN Account
            //$file_date = date('Ymd');
            $file_date = date('Ymd', time() -5 * 60 * 60);
            
            $remote_file_path = "/upload/BALTRN3_" . $file_date . ".001";
            $contents = Storage::disk('sftp')->get($remote_file_path);

            echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>';
            echo '<script type="text/javascript">',
                '
                var data = ' . $contents . '; 
                let accounts = data.accounts;
                $.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN":  "' . csrf_token() . '",
                    }   
                });
                for(i=0; i<accounts.length; i++){
                    // alert(accounts[i]);
                    $.ajax({
                        type:"POST",
                        url: "/update-balances",
                        data: accounts[i],
                        success:function(data) {
                           
                        }
                     });
                }
                alert("Settlements Accounts Have Been Updated");
                
            ; ',
                '</script>';
        } else {
            return "You will need to Login with the JSE Admin account to update balances";
        }
    } else {
        return "Please Login to update balances <a href='login'>Login</a>";
    }


    // try {

    //     $connection = ssh2_connect($host, $port);

    //     if (!$connection) {

    //         throw new \Exception("Could not connect to $host on port $port");
    //     }

    //     $auth  = ssh2_auth_password($connection, $username, $password);

    //     if (!$auth) {

    //         throw new \Exception("Could not authenticate with username $username and password ");
    //     }

    //     $sftp = ssh2_sftp($connection);

    //     if (!$sftp) {

    //         throw new \Exception("Could not initialize SFTP subsystem.");
    //     }

    //     $stream = fopen("ssh2.sftp://" . $sftp . $remote_file_path, 'r');

    //     if (!$stream) {

    //         throw new \Exception("Could not open file: ");
    //     }

    //     $contents = stream_get_contents($stream);

    //     // print_r($contents);
    //     $path = public_path() . '/uploads/new.json';
    //     Storage::disk('public_uploads')->put($path, $contents);
    //     // return false;
    //     // }
    //     // file_put_contents("documents/rbc7.json",($contents));

    //     @fclose($stream);

    //     $connection = NULL;
    // } catch (Exception $e) {

    //     echo "Error due to :" . $e->getMessage();
    // }

    // echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>';
    // echo '<script type="text/javascript">',
    //     '
    //     var data = ' . $contents . '; 
    //     let accounts = data.accounts;
    //     $.ajaxSetup({
    //         headers: {
    //             "X-CSRF-TOKEN":  "' . csrf_token() . '",
    //         }
    //     });
    //     for(i=0; i<accounts.length; i++){
    //         // ..
    //         $.ajax({
    //             type:"POST",
    //             url: "/update-balances",
    //             data: accounts[i],
    //             success:function(data) {

    //             }
    //          });
    //     }
    //     alert("JCSD Accounts Have Been Updated");

    // ; ',
    //     '</script>';
});


Route::get('refresh_order_status', function () {
    //If we intend to store the data before updating the accounts 
    // $path = 'RBC.json';
    // Storage::disk('public_uploads')->put($path, $file);
    // ===========================================================


    //Check if user has been authenticted before running updater
    if (auth()->user()) {

        //Check if this user is the JSE Admin
        $user = Auth::user()->getRoleNames();

        if ($user[0] === "ADMD") { //Only run if this is the JSE ADMIN Account           

            return "You will need to Login with the JSE Admin account to update balances";
        } else {
            return "You will need to Login with the JSE Admin account to refresh orders statuses";
        }
    } else {
        return "Please Login to access this function <a href='login'>Login</a>";
    }


});