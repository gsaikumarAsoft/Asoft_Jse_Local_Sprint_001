<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerClientOrder;
use App\ExpiringBuyOrder;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\ForeignBroker;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Jobs\ExecutionBalanceUpdate;
use App\Jobs\ManualExecutionBalanceUpdate;
use App\LocalBroker;
use App\LogActivity as AppLogActivity;
use App\Mail\BrokerDetailsUpdate;
use App\Mail\ForeignBrokerRemoval;
use App\Mail\LocalBroker as MailLocalBroker;
use App\Mail\LocalBrokerDetailsUpdate;
use App\Mail\NewForeignBroker;
use App\User;
use App\Mail\SettlementAccountConfirmation;
use App\Mail\SettlementAccountUpdated;
use App\Permission;
use App\Role;
use App\SettlementAccount;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


use App\BrokerUser;

class ApplicationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('auth');
        $this->LogActivity = new LogActivity;
        $this->HelperClass = new FunctionSet;
    }

    function audit()
    {

        $data = AppLogActivity::with('user')->get();
        return $data;
    }

    function fetchbrokermessages()
    {
        DB::table('users') 
        ->join('local_brokers', 'users.id', '=', 'local_brokers.user_id') 
        ->select('users.id', 'users.name') 
        ->where('status', '=', 'Verified') 
        ->orderBy('id') 
        ->chunk(100, function ($broker_users) { 
            foreach ($broker_users as $broker_user) {
                $executionBalanceUpdate = new ExecutionBalanceUpdate($broker_user->name); 
                $executionBalanceUpdate ; 
                //dispatch($executionBalanceUpdate); 
                //$fix_url_helper = new FunctionSet(); 
                //$url = $fix_url_helper. fix_wrapper_url("api/messagedownload/download");

                $url = config('fixwrapper.base_url') . "api/messagedownload/download";
                Log::debug('FETCHING BROKER MESSAGES | Local Broker: ' . $broker_user->name .'| URL: '. $url);

                //$url = config('fix.api.url') . "api/messagedownload/download";
                $data = array(
                    'BeginString' => 'FIX.4.2',
                    "SenderSubID" => $broker_user->name,
                    "seqNum" => 0,
                    'StartTime' => date('Y-m-d', time() -5 * 60 * 60) . " 00:00:00.000",
                    'EndTime' => date('Y-m-d', time() -5 * 60 * 60) . " 23:59:59.000",
                );
                $postdata = json_encode($data);
        
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
                $result = curl_exec($ch);
                curl_close($ch);
                $request = json_decode($result, true);
        
                Log::debug('FETCHED BROKER MESSAGES | Local Broker: ' . $broker_user->name .'| MESSAGES: '. $result);

            } 
        });
    }

    public function mdTest()
    {
        // $user = auth()->user();
        // // Call The Execution Balance Update Job
        // /*
        //     * Run FIX Message Download Api
        //     - Import new execution reports only
        //     - Update the status of orders based on the execution report for this specific broker
        //     - Update Account Balances based on (REJECTED,CANCELLED,NEW,FILLED,PARTIALLYFILLED)
        // */
        // $executionBalanceUpdate = new ExecutionBalanceUpdate($user->name);
        // $this->dispatch($executionBalanceUpdate);
        // /*--*/
        $url = fix_wrapper_url('FIX_API_URL') . "api/messagedownload/download";
        $data = array(
            'BeginString' => 'FIX.4.2',
            "SenderSubID" => 'BARITA',
            "seqNum" => 0,
            'StartTime' => date('Y-m-d', time() -5 * 60 * 60) . " 00:00:00.000",
            'EndTime' => date('Y-m-d', time() -5 * 60 * 60) . " 23:59:59.000",
        );
        // 'StartTime' => date('Y-m-d') . " 11:00:00.000",
        // 'EndTime' => date('Y-m-d') . " 23:30:00.000",
        $postdata = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
        $result = curl_exec($ch);
        curl_close($ch);
        $request = json_decode($result, true);
        return $request;
    }


    function index(Request $request)
    {
        // return 'test';
        $login = auth()->user();
        $user = Auth::user()->getRoleNames();
        if ($user[0] === 'ADMD') {
            return redirect('jse-admin');
        }
        if ($login['status'] === 'Verified' && $user[0] != "ADMD") {
            switch ($user[0]) {
                case 'ADMB':
                    $local_brokers = LocalBroker::all();
                    // return view('local')->with('local_brokers', $local_brokers);/
                    return redirect('broker');
                    break;
                case 'OPRB':
                    return redirect('operator');
                    break;
                case 'TRDB':
                    return redirect('trader-broker');
                    break;
                case 'BRKF':
                    return 'Configuration Screen For Outbound Foreign Broker To Be Designed <a href="/logout">Logout</a>';
                    // return redirect('foreign-broker');
                    break;
                case 'AGTS':
                    return 'Configuration Screen For Settlement Bank To Be Designed <a href="/logout">Logout</a>';
                    // return redirect('settlement-agent');
                    break;
                default:
                    return "Login Complete";
            }
        } else {
            return view('layouts.unverified');
        }
    }


    function indexCad()
    {
        $foreign_brokers = ForeignBroker::all();
        // return $foreign_brokers;
        return view('foreign')->with('foreign_brokers', $foreign_brokers);
    }

    function brokerList()
    {
        $local_brokers = LocalBroker::with('user')->get();
        return $local_brokers;
    }

    function expiringOrderList()
    {
        // $expiring_buy_orders = DB::select('SELECT * FROM expiring_buy_orders');
        $expiring_buy_orders = ExpiringBuyOrder::all();        
        return $expiring_buy_orders;
    }
    function expiringOrderListFor($id, $order_date)
    {        
        $expiring_buy_orders = DB::table('expiring_buy_orders')
            ->where('foreign_broker_user_id', $id)
            ->where('order_date', $order_date)
            ->get();
        
        return $expiring_buy_orders;
    }


    function foreignBrokerList()
    {
        $foreign_brokers = ForeignBroker::with('user')->get();
        return $foreign_brokers;
    }


    function settlementBrokerList()
    {

        $broker_settlement_accounts = BrokerSettlementAccount::with('local_broker', 'foreign_broker')->get();
        return $broker_settlement_accounts;
    }

    function fillExpiredOrder(Request $request)
    {
        Log::debug("Manual Expired Order Fill Request: " 
        . "/n |Order: ". json_encode($request->order_id)
        . "/n |Symbol: ". json_encode($request->order_symbol)
        . ' |Order Quantity: '. json_encode($request->order_quantity)
        . ' |Fill Quantity: '. json_encode($request->quantity)
        . ' |Price: '. json_encode($request->order_price)    
        . ' |Comment: '. json_encode($request->comment)    
        );
        $new_order_status = $request->order_quantity==$request->quantity? 2: 1;

        $this->manualFillExecutionReport($request->order_id, $new_order_status, $request->quantity, $request->order_price, $request->comment );


    }
    function storeLocalBroker(Request $request)
    {
        
        $pass = $this->HelperClass->rand_pass(8);        
        $hash = $this->HelperClass->generateRandomString(20);
        $role_ADMB = Role::where('name', 'ADMB')->first();

        if ($request->id) {
            Log::debug('NEW LOCAL BROKER request.id: ' . $request->id);
            $this->LogActivity::addToLog('Updated Local Broker Details');
            $broker_user = User::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email,  'status' => 'Unverified' ]
            );
            $broker = LocalBroker::updateOrCreate(
                ['user_id' => $request->id],
                ['admin_can_trade' => $request->admin_can_trade]
            );            
            Log::debug('Saved | Local Broker: ' . $broker_user->name .'| email: '. $broker_user->email .'| admin_can_trade:' . $broker->admin_can_trade);
            
            //$request['id'] = $user['id'];
            $request['hash'] = $broker_user->hash;

            Mail::to($request->email)->send(new LocalBrokerDetailsUpdate($request));
        } else {
            Log::debug('Adding | Local Broker: '. $request->name . '| email: '. $request->email .'| admin_can_trade: ' . $request->admin_can_trade) .'...';

            $this->LogActivity::addToLog('Created A Local Broker');
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($pass);
            $user->status = 'Unverified';
            $user->hash = $hash;
            $user->save();
            $user->roles()->attach($role_ADMB);

            $broker = new LocalBroker;
            $broker->user_id = $user->id;
            $broker->admin_can_trade = $request->admin_can_trade;
            
            $broker->save();
            $request['id'] = $user['id'];
            $request['hash'] = $hash;
            
            // Notify Settlement Bank
            Mail::to($request->email)->send(new MailLocalBroker($request, $pass));
        }
        // return $broker;

    }

    public function logActivity()
    {
        return $this->LogActivity->addToLog('Updated Settlement Account Details');
    }
    function storeSettlementBroker(Request $request)
    {
        $local_broker_name = $this->HelperClass->getUser($request->local_broker_id);
        $foreign_broker_name = $this->HelperClass->getUser($request->foreign_broker_id);
        $pass = $this->HelperClass->rand_pass(8);
        $role_AGTS = Role::where('name', 'AGTS')->first();

        if ($request->id) {
            $toggleStatus = 'Unverified';
            $this->LogActivity::addToLog('Updated Settlement Account Details. Account Number: ' . $request->account . ', Balance: ' . $request->account_balance . ', Amount Allocated: ' . $request->amount_allocated);
            if ($request->filled_orders == 0) {
                $toggleStatus = 'Verified';
            }
            $b = BrokerSettlementAccount::find($request->id);

            $b->update(
                [
                    'local_broker_id' => $request->local_broker_id,
                    'foreign_broker_id' => $request->foreign_broker_id,
                    'currency' => $request->currency,
                    'amount_allocated' => $request->amount_allocated,
                    'account_balance' => $request->account_balance,
                    'bank_name' => $request->bank_name,
                    'email' => $request->email,
                    'filled_orders' => $request->filled_orders,
                    'account' => $request->account,
                    'settlement_agent_status' => $toggleStatus,
                ]
            );

            // Update The Settlement Bank password and notify the Settlement Bank of the change
            User::where('name', $request->bank_name)
                ->update(['password' => Hash::make($pass)]);

            $request['password'] = $pass;
            $request['local_broker'] = $local_broker_name[0]->name;
            $request['foreign_broker'] = $foreign_broker_name[0]->name;
            //Notify the bank that the settlement is being updated so they can verify it.
            Mail::to($request->email)->send(new SettlementAccountUpdated($request, ''));
        } else {

            //Check if the user already exists
            if (count($this->HelperClass->getSettlementUserByEmail($request->email)) > 0) {
                $this->LogActivity::addToLog('Updated Settlement Account Details. Account Number: ' . $request->account . ', Balance: ' . $request->account_balance . ', Amount Allocated: ' . $request->amount_allocated);
                // if the user exists update password and send new email
                $u = $this->HelperClass->getSettlementUserByEmail($request->email);
                // return $u;
                $u = $u[0];

                User::updateOrCreate(
                    ['email' => $u->email],
                    ['password' =>  Hash::make($pass)]
                );

                $settlement_hash = $this->HelperClass->generateRandomString(20);
                $user_hash = $this->HelperClass->generateRandomString(20);
                // //Create A Broker Settlement Account
                $broker_settlement_account = new BrokerSettlementAccount;
                $broker_settlement_account->local_broker_id = $request->local_broker_id;
                $broker_settlement_account->foreign_broker_id = $request->foreign_broker_id;
                $broker_settlement_account->bank_name = $request->bank_name;
                $broker_settlement_account->account = $request->account;
                $broker_settlement_account->email = $request->email;
                $broker_settlement_account->hash = $settlement_hash;
                $broker_settlement_account->status = 'Unverified';
                $broker_settlement_account->currency = $request->currency;
                $broker_settlement_account->settlement_agent_status = 'Unverified';
                $broker_settlement_account->foreign_broker_status = 'Unverified';
                $broker_settlement_account->filled_orders = $request->filled_orders;
                $broker_settlement_account->account_balance = $request->account_balance;
                $broker_settlement_account->amount_allocated = $request->amount_allocated;
                $broker_settlement_account->save();

                // $data = [];
                $user['password'] = $pass;
                $user['local_broker'] = $local_broker_name[0]->name;
                $user['foreign_broker'] = $foreign_broker_name[0]->name;
                $user['user_name'] = $request->bank_name;
                $data['name'] = $u->name;
                $user['account'] = $request->account;
                $user['filled_orders'] = $request->filled_orders;
                $user['id'] = $broker_settlement_account->id;
                $user['hash'] = $settlement_hash;
                $user['email'] = $request->email;
                // return $data;
                //Notify the bank that the settlement is being created so they can verify it.
                Mail::to($request->email)->send(new SettlementAccountConfirmation($user));
            } else {

                $settlement_hash = $this->HelperClass->generateRandomString(20);
                $user_hash = $this->HelperClass->generateRandomString(20);
                // //Create A Broker Settlement Account
                $broker_settlement_account = new BrokerSettlementAccount;
                $broker_settlement_account->local_broker_id = $request->local_broker_id;
                $broker_settlement_account->foreign_broker_id = $request->foreign_broker_id;
                $broker_settlement_account->bank_name = $request->bank_name;
                $broker_settlement_account->account = $request->account;
                $broker_settlement_account->email = $request->email;
                $broker_settlement_account->hash = $settlement_hash;
                $broker_settlement_account->status = 'Unverified';
                $broker_settlement_account->currency = $request->currency;
                $broker_settlement_account->settlement_agent_status = 'Unverified';
                $broker_settlement_account->foreign_broker_status = 'Unverified';
                $broker_settlement_account->filled_orders = $request->filled_orders;
                $broker_settlement_account->account_balance = $request->account_balance;
                $broker_settlement_account->amount_allocated = $request->amount_allocated;
                $broker_settlement_account->save();
                // $request['id'] = $broker_settlement_account->id;

                $hash = $this->HelperClass->generateRandomString(20);
                $user = new User();
                $user->name = $request->bank_name;
                $user->email = $request->email;
                $user->password = Hash::make($pass);
                $user->status = 'Unverified';
                $user->hash = $user_hash;
                $user->save();
                $user->roles()->attach($role_AGTS);

                $data = [];
                $data['password'] = $pass;
                $data['local_broker'] = $local_broker_name[0]->name;
                $data['foreign_broker'] = $foreign_broker_name[0]->name;
                $data['user_name'] = $request->bank_name;
                $data['name'] = $user->name;
                $data['account'] = $request->account;
                $data['id'] = $broker_settlement_account->id;
                $data['hash'] = $settlement_hash;
                $data['email'] = $request->email;
                $user['filled_orders'] = $request->filled_orders;
                $data['filled_orders'] = $request->filled_orders;

                //Notify the bank that the settlement is being created so they can verify it.
                Mail::to($request->email)->send(new SettlementAccountConfirmation($data, $user));


                $this->LogActivity::addToLog('Created A New Settlement. Account Number: ' . $request->account . ', Balance: ' . $request->account_balance . ', Amount Allocated: ' . $request->amount_allocated);
            }
        }
    }

    public function orderExecution()
    {
        $execution_reports = DB::table('broker_client_order_execution_reports')
            //->where('broker_client_order_execution_reports.senderSubID', $user->name)
            ->select('broker_client_order_execution_reports.*', 'broker_settlement_accounts.account as settlement_account_number', 'broker_settlement_accounts.bank_name as settlement_agent', 'broker_trading_accounts.foreign_broker_id', 'fu.name as foreign_broker', 'lu.name as local_broker'  )
            ->join('broker_client_orders', 'broker_client_order_execution_reports.clordid', 'broker_client_orders.client_order_number')
            ->join('broker_trading_accounts', 'broker_client_orders.trading_account_id', 'broker_trading_accounts.id')
            ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
            ->join('foreign_brokers', 'broker_trading_accounts.foreign_broker_id', 'foreign_brokers.id')
            ->join('users as fu', 'broker_settlement_accounts.foreign_broker_id', 'fu.id')
            ->join('users as lu', 'broker_settlement_accounts.local_broker_id', 'lu.id')
            ->orderBy('messageDate', 'asc')
            ->get();

        // return $execution_reports;
        return view('brokers.execution')->with('execution_reports', $execution_reports);
    }

    public function executionListFor($id, $report_date)
    {
        //$user = auth()->user();
        
        $execution_reports = DB::table('broker_client_order_execution_reports')
            //->where('broker_client_order_execution_reports.senderSubID', $user->name)
            ->where('broker_settlement_accounts.foreign_broker_id', $id)
            ->where('broker_client_order_execution_reports.messageDate', ">=" ,$report_date)
            ->select('broker_client_order_execution_reports.*', 'broker_settlement_accounts.account as settlement_account_number', 'broker_settlement_accounts.bank_name as settlement_agent', 'broker_trading_accounts.foreign_broker_id', 'fu.name as foreign_broker', 'lu.name as local_broker'  )
            ->join('broker_client_orders', 'broker_client_order_execution_reports.clordid', 'broker_client_orders.client_order_number')
            ->join('broker_trading_accounts', 'broker_client_orders.trading_account_id', 'broker_trading_accounts.id')
            ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
            ->join('foreign_brokers', 'broker_trading_accounts.foreign_broker_id', 'foreign_brokers.id')
            ->join('users as fu', 'broker_settlement_accounts.foreign_broker_id', 'fu.id')
            ->join('users as lu', 'broker_settlement_accounts.local_broker_id', 'lu.id')
            ->orderBy('messageDate', 'asc')
            ->get();

        return $execution_reports;
    }

    public function manualFillExecutionReport($order_id, $new_OrdStatus, $quantity, $price, $comment)
    {
        /*
        status
            1 = Partially filled
            2 = Filled
            4 = Canceled
            */
        //Log::debug('Create ER | Order: ' . $order_id . ' | Status: ' . $new_OrdStatus);
        $last_order_er = DB::table('broker_client_order_execution_reports')
            ->where('clordid', $order_id)
            ->orderBy('messageDate', 'desc')
            ->first();
        //return $last_order_er->ordType;
        $er=json_decode('{}');
        $er->executionReports[]= [ 
            "id"=>$last_order_er->id ,
            "clOrdID"=>$order_id,
            "origClOrdID"=>"",
            "orderID"=>($order_id."-".$new_OrdStatus),
            "text"=>"Manual Fill Comment: " . $comment,
            "ordRejRes"=>$last_order_er->ordRejRes,
            "status"=>$new_OrdStatus,
            "buyorSell"=>$last_order_er->buyorSell,
            "time"=>$last_order_er->time,
            "ordType"=>$last_order_er->ordType,
            "orderQty"=>$last_order_er->orderQty,
            "timeInForce"=>$last_order_er->timeInForce,
            "symbol"=>$last_order_er->symbol,
            "qTradeacc"=>$last_order_er->qTradeacc,
            "price"=>$last_order_er->price,
            "stopPx"=>$last_order_er->stopPx,
            "execType"=>$last_order_er->execType,
            "senderSubID"=>$last_order_er->senderSubID,
            "lastPrice"=>$price,  //the fill price
            "lastOrderQty"=>$quantity,  //the fill quantity
            "seqNum"=>$last_order_er->seqNum,
            "sendingTime"=>date("Y-m-d H:i:s", time() - date("Z")), //"2021-05-18T09:57:00.404",
            //"sendingTime"=>$last_order_er->sendingTime, //"2021-05-18T09:57:00.404",
            "messageDate"=>date("Y-m-d H:i:s", time() - date("Z")) //"2021-05-18T14:57:00.4574177"
            //"messageDate"=>$last_order_er->messageDate //"2021-05-18T14:57:00.4574177"
        ];
        
        $er->restatedOrders= [];
        $er->logonMessages= [];
        $er->rejectMessages= [];
        $er->logoutMessages= [];
        $er->orderCancelRejectMessages= [];
        $er->businessRejectMessages= [];
        $er->orderSequence= []; 
        $er->rejectedMessages= [];         
        //Log::debug('Processing Manual ER: ' . json_encode($er) );

        $manualExecutionBalanceUpdate = new ManualExecutionBalanceUpdate($er);
        $this->dispatch($manualExecutionBalanceUpdate); 
        $results = $manualExecutionBalanceUpdate->job_status;

        $this->manualCancelExecutionReport($order_id, $new_OrdStatus);

        return json_encode($results);
        //return json_encode($manualExecutionBalanceUpdate->job_status);
    }

    public function manualCancelExecutionReport($order_id, $new_OrdStatus)
    {
        /*
            0 = New
            1 = Partially filled
            2 = Filled
            3 = Done for day
            4 = Canceled
            5 = Replaced (Removed/Replaced)
            6 = Pending Cancel (e.g. result of Order Cancel Request <F>)
            7 = Stopped
            8 = Rejected
            9 = Suspended
            A = Pending New
            B = Calculated
            C = Expired
            D = Accepted for bidding
            E = Pending Replace (e.g. result of Order Cancel/Replace Request <G>)
        */
        //Log::debug('Create ER | Order: ' . $order_id . ' | Status: ' . $new_OrdStatus);
        
        $last_order_er = DB::table('broker_client_order_execution_reports')
            ->where('clordid', $order_id)
            ->orderBy('messageDate', 'desc')
            ->first();
        //return $last_order_er->ordType;

        $er=json_decode('{}');
        $er->executionReports[]= [ 
            "id"=>$last_order_er->id ,
            "clOrdID"=>$order_id,
            "origClOrdID"=>$order_id,
            "orderID"=>($order_id."-".$new_OrdStatus),
            "text"=>"Expired Order Manual Update",
            "ordRejRes"=>$last_order_er->ordRejRes,
            "status"=>$new_OrdStatus,
            "buyorSell"=>$last_order_er->buyorSell,
            "time"=>$last_order_er->time,
            "ordType"=>$last_order_er->ordType,
            "orderQty"=>$last_order_er->orderQty,
            "timeInForce"=>$last_order_er->timeInForce,
            "symbol"=>$last_order_er->symbol,
            "qTradeacc"=>$last_order_er->qTradeacc,
            "price"=>$last_order_er->price,
            "stopPx"=>$last_order_er->stopPx,
            "execType"=>$last_order_er->execType,
            "senderSubID"=>$last_order_er->senderSubID,
            //"lastPrice"=>$last_order_er->price,
            //"lastOrderQty"=>$last_order_er->lastOrderQty,
            "seqNum"=>$last_order_er->seqNum,
            "sendingTime"=>date("Y-m-d H:i:s", time() - date("Z")), //"2021-05-18T09:57:00.404",
            //"sendingTime"=>$last_order_er->sendingTime, //"2021-05-18T09:57:00.404",
            "messageDate"=>date("Y-m-d H:i:s", time() - date("Z")) //"2021-05-18T14:57:00.4574177"
            //"messageDate"=>$last_order_er->messageDate //"2021-05-18T14:57:00.4574177"
        ];
        
        $er->restatedOrders= [];
        $er->logonMessages= [];
        $er->rejectMessages= [];
        $er->logoutMessages= [];
        $er->orderCancelRejectMessages= [];
        $er->businessRejectMessages= [];
        $er->orderSequence= []; 
        $er->rejectedMessages= []; 
        //$er = json_encode($er, TRUE);

        //return "Updating order#: "+$order_id+" to Status:"+$new_OrdStatus;        
        //Log::debug('Processing Manual ER: ' . json_encode($er) );

        $manualExecutionBalanceUpdate = new ManualExecutionBalanceUpdate($er);
        $this->dispatch($manualExecutionBalanceUpdate); 
        
        //$wait_cycles = 0;
        //do {
        //    sleep(1);
        //    $wait_cycles++;
        //  } while ($manualExecutionBalanceUpdate->job_status == 0 && $wait_cycles < 20);
        
        
        $results = $manualExecutionBalanceUpdate->job_status;
        
        return json_encode($manualExecutionBalanceUpdate->job_status);

        //$executionBalanceUpdate = new ExecutionBalanceUpdate($broker_admin->name);
        //$this->dispatch($executionBalanceUpdate);        

    }


    function storeForeignBroker(Request $request)
    {

        // return $request;
        $pass = $this->HelperClass->rand_pass(8);
        $role_BRKF = Role::where('name', 'BRKF')->first();

        if ($request->id) {

            // Next Sprint
            // $broker = User::find($id);
            $broker = User::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email, 'status' => 'Unverified']
            );
            $request['hash']  = $broker->hash;
            //Notify the broker that updates are being made to their accout
            Mail::to($request->email)->send(new BrokerDetailsUpdate($request, $broker->hash));
            $this->LogActivity::addToLog('Update Foreign Broker Details');
        } else {

            $hash = $this->HelperClass->generateRandomString(20);
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($pass);
            $user->status = 'Unverified';
            $user->hash = $hash;
            $user->save();
            $user->roles()->attach($role_BRKF);


            $broker = new ForeignBroker;
            $broker->user_id = $user->id;
            $broker->save();
            $request['id'] = $user['id'];
            $request['hash'] = $hash;

            Mail::to($request->email)->send(new NewForeignBroker($request, $pass));
            $this->LogActivity::addToLog('Created New Foreign Broker');
        }
    }



    function destroyLocalBroker($id)
    {

        $b = LocalBroker::find($id);
        $b->delete();
        $this->LogActivity::addToLog('Deleted Local Broker');
    }
    function destroyForeignBroker($id)
    {
        $b = ForeignBroker::find($id);
        $b->delete();
        // $foreign_broker = $this->HelperClass->getForeignBrokerById($id);
        // Mail::to($foreign_broker->email)->send(new ForeignBrokerRemoval($foreign_broker));
    }



    function updateLocalBroker(Request $request, $id)
    {
        $broker                     = LocalBroker::find($id);
        $broker->name               = $request->name;
        $broker->admin_can_trade    = $request->admin_can_trade;
        $broker->email              = $request->email;
        $broker->save();
    }

    function updateForeignBroker(Request $request, $id)
    {
        $broker               = ForeignBroker::find($id);
        $broker->name         = $request->name;
        $broker->email        = $request->email;
        $broker->save();
    }


    function settlements()
    {
        $local_brokers = LocalBroker::select('*')->get();

        return view('accounts')->with('local_brokers', $local_brokers);
    }

    
    function getForeignBrokers()
    {
        $foreign_brokers = LocalBroker::select('*')->get();

        return view('accounts')->with('local_brokers', $local_brokers);
    }

    function brokerConfig()
    {
        $local_brokers = LocalBroker::all();

        return view('broker2broker')->with('local_brokers', $local_brokers);
    }

    function b2b()
    {
        $accounts = BrokerTradingAccount::all();

        return view('broker2broker')->with('accounts', $accounts);
    }
    public function ok()
    {

        return Auth::user()->getDirectPermissions();
        if (Auth::user()->hasPermissionTo(Permission::find(2)->id)) {
            // the user can do everything
            return 'Yes';
        } else {
            return 'No';
        }
    }

    public function destroyBSA($id)
    {

        // return $id;
        $settlement_account = BrokerSettlementAccount::find($id);
        $settlement_account->delete();
    }

    public function updateBalances(Request $request)
    {
        // return $request->settlement_account;
        //Check for accounts availability
        // $account_exists = BrokerClient::where('jcsd', $request->client_JCSD_number)->first();


        //Check If Settlement Accounts Exist
        $account_exists = BrokerSettlementAccount::where('account', $request->settlement_account_number)->first();

        // return $account_exists;
        if ($account_exists) {

            $broker_settlement_account = BrokerSettlementAccount::updateOrCreate(
                ['account' => $request->settlement_account_number],
                ['account_balance' => $request->settlement_account]
            );
        }
    }

    public function expiringOrders()
    {        
        //$expiringOrders = DB::select('SELECT * FROM expiring_buy_orders');
        ///$expiring_buy_orders = DB::table('expiring_buy_orders')
        //    ->where('foreign_broker_user_id', $id)
        //    ->get();
        
        $expiring_buy_orders = [];
        return view('expiring_buy_order')->with('orders', $expiring_buy_orders);
    }
    public function expiredOrders()
    {        
        $expired_buy_orders = [];
        return view('expired_buy_order')->with('orders', $expired_buy_orders);
    }
    public function fillExpiredOrders()
    {        
        $expired_buy_orders = [];
        return view('fill_expired_order')->with('orders', $expired_buy_orders);
    }

    public function expiringOrdersFor(Request $request, $id)
    {     
        //Log::debug('Expiring Orders | Criteria: '. $request . '|...');
        //$expiringOrders = DB::select('SELECT * FROM expiring_buy_orders');
        //$expiring_buy_orders = ExpiringBuyOrder::all();
        //$expiring_buy_orders = ExpiringBuyOrder::where('foreign_broker_user_id', $request-
        
        $expiring_buy_orders = DB::table('expiring_buy_orders')
            ->where('foreign_broker_user_id', $id)
            ->get();
        
        return view('expiring_buy_order')->with('orders', $expiring_buy_orders);
    }

}
