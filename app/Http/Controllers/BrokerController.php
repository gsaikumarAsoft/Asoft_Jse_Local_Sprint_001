<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerClientOrder;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\BrokerUser;
use App\ForeignBroker;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\LocalBroker;
use App\Mail\TradingAccount;
use App\Role;
use Illuminate\Http\Request;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrokerController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
    }
    public function index()
    {
        return view('brokers.index');
    }

    function storeBrokerClient(Request $request)
    {
        $broker_settlement_account = new BrokerSettlementAccount();
        $broker_settlement_account->local_broker_id = $request->local_broker_id;
        $broker_settlement_account->foreign_broker_id = $request->foreign_broker_id;
        $broker_settlement_account->bank_name = $request->bank_name;
        $broker_settlement_account->account = $request->account;
        $broker_settlement_account->email = $request->email;
        $broker_settlement_account->account_balance = '50000.00';
        $broker_settlement_account->amount_allocated = '1200.00';

        // $broker_settlement_account->account_balance = $request->account_balance;
        // $broker_settlement_account->amount_allocated = $request->amount_allocated;
        $broker_settlement_account->save();
        LogActivity::addToLog('Created New Settlement Account');
    }


    public function getUsers()
    {
        $users = Role::where('name', 'client')->first()->users()->get();
        return $users;
    }

    public function company()
    {
        return view('brokers.company');
    }


    public function tradingAccounts()
    {

        $user = auth()->user();

        $user_definition  = LocalBroker::where('user_id', $user->id)->first();

        $broker = DB::table('broker_trading_accounts')->where('broker_trading_accounts.local_broker_id', $user_definition['id'])
            ->select('users.name as foreign_broker', 'broker_settlement_accounts.bank_name as bank', 'broker_trading_accounts.trading_account_number', 'broker_settlement_accounts.account', 'broker_settlement_accounts.account_balance as balance', 'broker_trading_accounts.id')
            ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
            ->join('foreign_brokers', 'broker_trading_accounts.foreign_broker_id', 'foreign_brokers.id')
            ->join('users', 'foreign_brokers.user_id', 'users.id')
            ->get();
        return $broker;
    }

    public function operatorTradingAccounts()
    {

        $user = auth()->user();

        //Local Broker Owner for trading account
        $local_broker_id = $user->local_broker_id;

        $broker = DB::table('broker_trading_accounts')->where('broker_trading_accounts.local_broker_id', $local_broker_id)
            ->select('users.name as foreign_broker', 'broker_settlement_accounts.bank_name as bank', 'broker_trading_accounts.trading_account_number', 'broker_settlement_accounts.account', 'broker_settlement_accounts.account_balance as balance', 'broker_trading_accounts.id')
            ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
            ->join('foreign_brokers', 'broker_trading_accounts.foreign_broker_id', 'foreign_brokers.id')
            ->join('users', 'foreign_brokers.user_id', 'users.id')
            ->get();
        return $broker;
    }

    public function users()
    {


        $broker_users = $data = BrokerUser::with('role')->get();
        return view('brokers.user')->with('broker_users', $broker_users);
    }
    public function brokerUsers()
    {


        $broker_users = $data = BrokerUser::with('role')->get();
        return $broker_users;
    }
    public function traders()
    {

        $u = auth()->user();
        // return $u;
        $broker_traders = LocalBroker::where('user_id', $u['id'])->with('clients')->get();
        // return $broker_traders;
        return view('brokers.client')->with('broker_traders', $broker_traders);
        // return view('brokers.client');
    }
    public function settlements()
    {

        $user = auth()->user();
        $a = BrokerSettlementAccount::with('foreign_broker')->where('local_broker_id', $user->id)->get();
        // return $a;
        return view('brokers.settlements')->with('accounts', $a);
    }
    public function traderList()
    {

        $user = auth()->user();
        // return $user;
        $a = LocalBroker::with('user', 'clients')->get();
        // $a = DB::table('broker_clients')->where()
        return $a;
        // $broker_trader_accounts = BrokerTradingAccount::all();
        // return $broker_trader_accounts;
        // $list = BrokerClient::with('local_broker')->get();
        // return $list;
    }
    public function orders()
    {
        $local_brokers = LocalBroker::all();
        $foreign_brokers = ForeignBroker::all();
        // $orders = BrokerClientOrder::all();
        // $data = DB::table('broker_client_orders')
        //     ->select('broker_client_orders.*', 'foreign_brokers.name as foreign_broker', 'local_brokers.name as local_broker')
        //     ->join('foreign_brokers', 'broker_client_orders.foreign_broker_id', 'foreign_brokers.id')
        //     ->join('local_brokers', 'broker_client_orders.local_broker_id', 'local_brokers.id')
        //     ->get();
        $user = auth()->user();
        // return $user;
        $orders = LocalBroker::with('user', 'order')->where('user_id', $user['id'])->get();
        // return $orders;
        $broker_traders = LocalBroker::where('user_id', $user['id'])->with('clients')->get();
        return view('brokers.order')->with('orders', $orders)->with('client_accounts', $broker_traders);
    }
    public function approvals()
    {
        return view('brokers.approval');
    }
    public function log()
    {
        return view('brokers.log');
    }


    public function destroyOrder($id)
    {
        $order = BrokerClientOrder::find($id);
        $order->delete();
    }
    public function clientOrder(Request $request)
    {
        
        

        //Define The Broker Making The Order
        $user = auth()->user();
        $user_definition  = LocalBroker::where('user_id', $user->id)->first();
        $local_broker_id = $user_definition->id;
        // ===================================================================


        //Trading Account Information
        $trading = BrokerTradingAccount::with('settlement_account')->find($request->trading_account)->first();
        
        //Settlement Account Information
        $settlement = BrokerSettlementAccount::find($trading->broker_settlement_account_id)->first();
        
        
        // Client Account Information
        $c_account = BrokerClient::find($request['client_trading_account'])->first();

        // Calculations Before Creating A New Order
        $order_value = $request->price * $request->quantity;
        $settlement_available = $settlement->account_balance - $settlement->amount_allocated;
        $client_available = $c_account->account_balance - $c_account->open_orders;
        //==================================================

        if ($settlement_available < $order_value) {

            // $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Broker Blocked');

            return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: Insufficient Settlement Funds!']);

        } else if ($client_available < $order_value) {

            // $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Client Blocked');
            return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: Insufficient Client Funds!']);

        } else {

            // [Settlement Allocated] = [Settlement Allocated] + [Order Value] 
            $settlement_allocated = $settlement->amount_allocated + $order_value;

            // [Client Open Orders] = [Client Open Orders] + [Order Value]
            $client_open_orders = $c_account->open_orders + $order_value;


            // Update Settlement ACcount Balances
            BrokerSettlementAccount::updateOrCreate(
                ['id' => $trading->broker_settlement_account_id],
                ['amount_allocated' => $settlement_allocated]
            );


            // Update Broker Clients Open Orders
            BrokerClient::updateOrCreate(
                ['id' => $request['client_trading_account']],
                ['open_orders' => $client_open_orders]
            );

            $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Submitted', $request->client_trading_account);
            return response()->json(['isvalid' => true, 'errors' => 'SEND NewOrderSingle() request to the RESTful API!']);
        }
    }
}