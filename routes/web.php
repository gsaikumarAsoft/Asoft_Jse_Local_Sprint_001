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
use App\Mail\BrokerUserAccountCreated;
use App\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;


Route::post('/update-balances', 'ApplicationController@updateBalances');


Route::get('/home', function () {
    return redirect('/');
});

Route::get('/home-test', function () {
    $this->OrderStatus = new OrderStatus;
    // Determine variables for use
    $order_number = '20200823013514528';
    $side = '1';
    $sender_sub_id = 'NCB';
    $price = '12.22';
    $quantity = '50';
    $status = '2';
    $jcsd_num = 'JCSD32423';

    // return $order_number;
    // Define the clients jcsd number
    $jcsd = str_replace('JCSD', "", $jcsd_num);

    // // Define The broker client
    $broker_client = BrokerClient::where('jcsd', $jcsd)->first();
    // return $broker_client;

    //Find the broker order linked to this execution report (account number)
    $order = BrokerClientOrder::where('clordid', $order_number)->first();
    // return $order->trading_account_id;
    if ($order) {
        //Trading Account Information
        // return (int)$order->trading_account_id;
        $trading = BrokerTradingAccount::find((int)$order->trading_account_id);
        // return $trading;
        // Log Trading & Settlement Account to activity Log //
        // Settlement & Broker Accounts
        // $this->logActivity::addToLog('Order Update In Progress: Trading Account:' . $order->trading_account_id);
        //

        //Find the broker settlement account linked to this execution report (account number (senderSubID)
        $settlement_account = BrokerSettlementAccount::find($trading->broker_settlement_account_id);
        // return $settlement_account;
        if ($order && $broker_client) {
            $current_order = $order;
            $trader = $broker_client;

            if ($current_order->id) {
                // $order_status = $this->orderStatus($current_order->id);
                $broker_settlement_account = $settlement_account;

                $order_value = $quantity * $price; //ER Order Value

                // [Settlement Allocated] = [Settlement Allocated] + [Order Value]  
                $settlement_allocated = $broker_settlement_account['amount_allocated'] + $order_value;

                // [Client Open Orders] = [Client Open Orders] + [Order Value]
                $client_open_orders = $trader['open_orders'] + $order_value;

                // Allocated Value of order [Release what was initially allocated per stock]
                $allocated_value_of_order = $quantity * $current_order['price'];
                $filled_value = $quantity * $price;

                //Determine If The Order Is A Buy Or Sell
                $side = json_decode($order->side, true);
                // return $side;
                if ($side['fix_value'] === '1') {
                    // return $status;
                    //Only Update account Balances if this is a buy order
                    if (
                        $status === $this->OrderStatus->Expired() ||
                        $status === $this->OrderStatus->_New()
                    ) {
                        // UPDATE ORDER STATUS ONLY
                        BrokerClientOrder::updateOrCreate(

                            ['id' => $current_order->id],
                            ['order_status' => $status]

                        );
                    } else if ($status === $this->OrderStatus->Cancelled()) {
                        BrokerClientOrder::updateOrCreate(

                            ['id' => $current_order->id],
                            ['order_status' => $status, 'remaining' => $current_order['remaining'] - $allocated_value_of_order]

                        );

                        // Update Settlement Account Balances
                        $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                            ['id' => $broker_settlement_account['id']],
                            ['amount_allocated' => $broker_settlement_account['amount_allocated'] - $allocated_value_of_order]
                        );


                        // Update Broker Clients Open Orders
                        $broker_client_account = BrokerClient::updateOrCreate(
                            ['id' => $trader->id],
                            ['open_orders' => $trader['open_orders'] - $allocated_value_of_order]
                        );
                    } else if ($status === $this->OrderStatus->Failed() || $status === $this->OrderStatus->Rejected()) {
                        $order_value = $current_order['quantity'] * $current_order['price'];

                        BrokerClientOrder::updateOrCreate(

                            ['id' => $current_order->id],
                            ['order_status' => $status, 'remaining' => $current_order['remaining'] - $allocated_value_of_order]

                        );

                        // Update Settlement Account Balances
                        $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                            ['id' => $broker_settlement_account['id']],
                            ['amount_allocated' => $broker_settlement_account['amount_allocated'] - $allocated_value_of_order]
                        );


                        // Update Broker Clients Open Orders
                        $broker_client_account = BrokerClient::updateOrCreate(
                            ['id' => $trader->id],
                            ['open_orders' => $trader['open_orders'] - $order_value]
                        );
                    } else if ($status === $this->OrderStatus->Filled()) {
                        // return "Filling";

                        // UPDATE THE ORDER STATUS 
                        $broker_client_order = BrokerClientOrder::updateOrCreate(
                            ['id' => $current_order->id],
                            ['order_status' => $status, 'remaining' => $current_order['remaining'] - $allocated_value_of_order]

                        );

                        // Update Settlement Account Balances
                        $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                            ['id' => $broker_settlement_account['id']],
                            ['amount_allocated' => $broker_settlement_account['amount_allocated'] - $allocated_value_of_order, 'filled_orders' => $broker_settlement_account['filled_orders'] + $filled_value]
                        );


                        // Update Broker Clients Open Orders
                        $broker_client_account = BrokerClient::updateOrCreate(
                            ['id' => $trader->id],
                            ['open_orders' => $trader['open_orders'] - $allocated_value_of_order, 'filled_orders' => $trader->filled_orders + $filled_value]
                        );
                    } else if ($status === $this->OrderStatus->PartialFilled()) {
                        // UPDATE THE ORDER STATUS 
                        $broker_client_order = BrokerClientOrder::updateOrCreate(
                            ['id' => $current_order->id],
                            ['order_status' => $status, 'remaining' =>  $current_order['remaining'] - $allocated_value_of_order]

                        );

                        // Update Settlement Account Balances
                        $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                            ['id' => $broker_settlement_account['id']],
                            ['amount_allocated' =>  $broker_settlement_account['amount_allocated'] - $allocated_value_of_order, 'filled_orders' =>   $broker_settlement_account['filled_orders'] + $filled_value]
                        );


                        // Update Broker Clients Open Orders
                        $broker_client_account = BrokerClient::updateOrCreate(
                            ['id' => $trader->id],
                            ['open_orders' =>   $trader['open_orders'] - $allocated_value_of_order, 'filled_orders' =>    $trader->filled_orders + $filled_value]
                        );
                    }
                } else {
                    // If the order side is a sell
                    // UPDATE ORDER STATUS ONLY
                    BrokerClientOrder::updateOrCreate(

                        ['id' => $current_order->id],
                        ['order_status' => $status]

                    );
                }
            }
        }
    }
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

    // Route::get('/', 'BrokerController@log');
    Route::get('/broker-list', 'ApplicationController@brokerList');
    Route::get('/foreign-broker-list', 'ApplicationController@foreignBrokerList');
});
Route::group(['prefix' => '/operator', 'middleware' => ['App\Http\Middleware\LocalBrokerOperatorMiddleware']], function () {
    Route::get('/', "OperatorController@index");
    Route::get('/clients', 'OperatorController@clients');
    Route::get('/execution', 'OperatorController@execution');
    Route::get('/operator-clients', 'OperatorController@clientList');
    Route::get('/trading-accounts', 'OperatorController@traderList');
    Route::post('/store-broker-trader', "TraderController@storeOperatorClient");
    Route::post('/store-operator-client-order', "OperatorController@operatorClientOrder");
    Route::get('/broker-users', 'UserController@index');
    Route::delete('/client-broker-delete/{id}', 'ClientController@destroy');
    Route::delete('/destroy-broker-client-order/{id}', "BrokerController@destroyOrder");
    Route::get('/broker-trading-accounts', 'BrokerController@operatorTradingAccounts');
    // Route::get('/orders', "BrokerTraderController@orderList");
    Route::get('/orders', 'OperatorController@orders');
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
            $file_date = date('Ymd');
            $remote_file_path = "/upload/BALTRN3_" . $file_date . ".001";
            $contents = Storage::disk('sftp')->get($remote_file_path);

            echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>';
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

    // echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>';
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
