<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerClientOrder;
use App\BrokerOrderExecutionReport;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\BrokerUser;
use App\ForeignBroker;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Jobs\ExecutionBalanceUpdate;
use App\LocalBroker;
use App\Mail\TradingAccount;
use App\Role;
use Illuminate\Http\Request;
use App\User;
use Carbon\Carbon;
use CreateBrokerClientOrderExecutionReports;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrokerController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
        $this->LogActivity = new LogActivity;
    }
    public function index()
    {
        return view('brokers.index');
    }

    public function resetTrades()
    {
        $update = DB::table('broker_client_orders')->update(['filled_orders' => 0]);
        return $update;
    }
    function storeBrokerClient(Request $request)
    {
        if ($request->id) {
            // If the operator didnt select a status default to unverified
            if (empty($request->operator_status)) {
                $request->status = 'Un-Verified';
            } else {
                $request->status = $request->operator_status;
            }

            LogActivity::addToLog('Update Client Details');
            $broker_client = BrokerClient::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email, 'status' => $request->status, 'jcsd' => $request->jcsd, 'account_balance' => $request->account_balance]

            );
        } else {

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
        // return $user_definition->id;


        $broker = DB::table('broker_trading_accounts')->where('broker_trading_accounts.local_broker_id', $user_definition->id)
            ->select('users.name as foreign_broker', 'broker_settlement_accounts.bank_name as bank', 'broker_trading_accounts.trading_account_number', 'broker_settlement_accounts.account', 'broker_settlement_accounts.account_balance as balance', 'broker_trading_accounts.id', 'broker_settlement_accounts.currency')
            ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
            ->join('foreign_brokers', 'broker_trading_accounts.foreign_broker_id', 'foreign_brokers.id')
            ->join('users', 'foreign_brokers.user_id', 'users.id')
            ->get();

        // //Find The Trading ACcounts For This Local Broker
        $t = BrokerTradingAccount::where('local_broker_id', $user_definition->id)->get();
        // return $t;
        // $broker = DB::table('broker_trading_accounts')->where('broker_trading_accounts.local_broker_id', $user_definition[0]->id)
        //     ->get();

        return $broker;
    }

    public function operatorTradingAccounts()
    {

        $user = auth()->user();
        $user_definition  = LocalBroker::where('id', $user->local_broker_id)->first();
        // return $user_definition->id;

        //Local Broker Owner for trading account
        $local_broker_id = $user->local_broker_id;


        $broker = DB::table('broker_trading_accounts')->where('broker_trading_accounts.local_broker_id', $user_definition->id)
            ->select('users.name as foreign_broker', 'broker_settlement_accounts.bank_name as bank', 'broker_trading_accounts.trading_account_number', 'broker_settlement_accounts.account', 'broker_settlement_accounts.account_balance as balance', 'broker_settlement_accounts.currency', 'broker_trading_accounts.id')
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
        return view('brokers.client')->with('broker_traders', $broker_traders);
    }
    public function settlements()
    {

        $user = auth()->user();
        $a = BrokerSettlementAccount::with('foreign_broker')->where('local_broker_id', $user->id)->where('settlement_agent_status', 'Verified')->distinct()->get();
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
    }
    public function execution()
    {
        $user = auth()->user();
        // $execution_reports = DB::table('broker_client_order_execution_reports')
        //     ->select('broker_client_order_execution_reports.*', 'broker_settlement_accounts.account as settlement_account_number', 'broker_settlement_accounts.bank_name as settlement_agent')
        //     ->join('broker_client_orders', 'broker_client_order_execution_reports.clordid', 'broker_client_orders.clordid')
        //     ->join('local_brokers', 'broker_client_orders.local_broker_id', 'local_brokers.id')
        //     ->join('broker_trading_accounts', 'local_brokers.id', 'broker_trading_accounts.local_broker_id')
        //     // ->join('users', 'broker_client_order_execution_reports.senderSubID', 'users.name')
        //     ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
        //     ->distinct()
        //     ->get();

        $execution_reports = DB::table('broker_client_order_execution_reports')
            ->where('broker_client_order_execution_reports.senderSubID', $user->name)
            ->select('broker_client_order_execution_reports.*', 'broker_settlement_accounts.account as settlement_account_number', 'broker_settlement_accounts.bank_name as settlement_agent')
            ->join('broker_client_orders', 'broker_client_order_execution_reports.clordid', 'broker_client_orders.client_order_number')
            ->join('broker_trading_accounts', 'broker_client_orders.trading_account_id', 'broker_trading_accounts.id')
            ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
            ->orderBy('messageDate', 'asc')
            ->get();


        // return $execution_reports;
        return view('brokers.execution')->with('execution_reports', $execution_reports);
    }

    public function mdTest()
    {
        return $this->HelperClass->executionBalanceUpdate("BARITA", 'JSE_TRADER3');
    }
    public function logExecution(Request $request)
    {
        $execution_report = $request->executionReports;

        // return $request;
        // BrokerOrderExecutionReport::truncate();
        foreach ($execution_report as $report) {
            $clients[] = $report;


            $broker_order_execution_report = new BrokerOrderExecutionReport();
            $broker_order_execution_report->clOrdID = $report['clOrdID'];
            $broker_order_execution_report->orderID = $report['orderID'];
            $broker_order_execution_report->text = $report['text'];
            $broker_order_execution_report->ordRejRes = $report['ordRejRes'];
            $broker_order_execution_report->status = $report['status'];
            $broker_order_execution_report->buyorSell = $report['buyorSell'];
            $broker_order_execution_report->securitySubType = 0;
            $broker_order_execution_report->time = $report['time'];
            $broker_order_execution_report->ordType = $report['ordType'];
            $broker_order_execution_report->orderQty = $report['orderQty'];
            $broker_order_execution_report->timeInForce = $report['timeInForce'];
            $broker_order_execution_report->symbol = $report['symbol'];
            $broker_order_execution_report->qTradeacc = $report['qTradeacc'];
            $broker_order_execution_report->price = $report['price'];
            $broker_order_execution_report->stopPx = $report['stopPx'];
            $broker_order_execution_report->execType = $report['execType'];
            $broker_order_execution_report->senderSubID = $report['senderSubID'];
            $broker_order_execution_report->seqNum = $report['seqNum'];
            $broker_order_execution_report->sendingTime = $report['sendingTime'];
            $broker_order_execution_report->messageDate = $report['messageDate'];
            $broker_order_execution_report->save();
        }


        return $this->HelperClass->executionBalanceUpdate($clients);
    }
    public function orders()
    {
        $local_brokers = LocalBroker::all();
        $foreign_brokers = ForeignBroker::all();

        $user = auth()->user();
        $orders = LocalBroker::with('user', 'order', 'clients', 'trading')->where('user_id', $user['id'])->orderBy('created_at', 'desc')->get();

        // Call The Execution Balance Update Job
        /*
            * Run FIX Message Download Api
            - Import new execution reports only
            - Update the status of orders based on the execution report for this specific broker
            - Update Account Balances based on (REJECTED,CANCELLED,NEW,FILLED,PARTIALLYFILLED)
        */
        $executionBalanceUpdate = new ExecutionBalanceUpdate($user->name);
        $this->dispatch($executionBalanceUpdate);
        /*--*/

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
        return $this->HelperClass->cancelOrder($id);
    }
    public function clientOrder(Request $request)
    {

        //Define The Broker Making The Order
        $user = auth()->user();
        $user_definition  = LocalBroker::where('user_id', $user->id)->first();
        $local_broker_id = $user_definition->id;
        // ===================================================================


        //Trading Account Information
        $trading = BrokerTradingAccount::find($request['trading_account']);


        //Settlement Account Information
        $settlement = BrokerSettlementAccount::find($trading->broker_settlement_account_id);

        // Client Account Information
        $broker_client = BrokerClient::find($request->client_trading_account);


        // Check if the client account status is approved before continueing the order. If not Kill it
        if ($broker_client->status != 'Verified') {
            $this->LogActivity->addToLog('ORDER BLOCKED: JCSD:' . $broker_client->jcsd . '-' . $broker_client->name . 's account status needs to be updated from ' . $broker_client->status . ' to Verified');
            return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $broker_client->name . 's account status needs to be updated from ' . $broker_client->status . ' to Verified']);
        }

        // Calculations Before Creating A New Order
        $order_value = $request->quantity * $request->price;
        // $settlement_available = $settlement->account_balance - $settlement->amount_allocated;


        //Beta 2
        $settlement_available = $settlement->account_balance - ($settlement->orders_filled + $settlement->amount_allocated);
        $client_available = $broker_client->account_balance - ($broker_client->open_orders + $broker_client->filled_orders);
        //=========================================================================
        // return $settlement_available ."=". $order_value;

        $side = json_decode($request->side, true);

        // If SIDE = BUY
        if ($side['fix_value'] === '1') {
            if ($settlement_available < $order_value) {

                // $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Broker Blocked');

                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: Insufficient Settlement Funds!']);
            } else if ($client_available < $order_value) {

                // $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Client Blocked');
                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: Insufficient Client Funds!']);
            } else {
                // [Settlement Allocated] = [Settlement Allocated] + [Order Value]  
                $settlement_allocated = $settlement->amount_allocated + $order_value;
                $settlement_account_balance = $settlement->account_balance - $order_value;

                // [Client Open Orders] = [Client Open Orders] + [Order Value]
                $client_open_orders = $broker_client->open_orders + $order_value;


                // Update Settlement Account Balances
                BrokerSettlementAccount::updateOrCreate(
                    ['hash' => $settlement->hash],
                    ['amount_allocated' => $settlement_allocated]
                );


                // Update Broker Clients Open Orders
                BrokerClient::updateOrCreate(
                    ['id' => $broker_client->id],
                    ['open_orders' => max($client_open_orders, 0)]
                );
                // Create the order in our databases and send order server side using curl
                return $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Submitted', $request->client_trading_account);
            }
        } else if ($side['fix_value'] === '2') {
            // Do not update balances
            // Create the order in our databases and send order server side using curl
            return $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Submitted', $request->client_trading_account);
        }
    }
}
