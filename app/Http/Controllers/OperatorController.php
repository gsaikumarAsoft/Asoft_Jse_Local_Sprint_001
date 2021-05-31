<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerClientOrder;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\BrokerUser;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Jobs\ExecutionBalanceUpdate;
use App\LocalBroker;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
        $this->LogActivity = new LogActivity;
    }

    public function index()
    {
        return view('operators.index');
    }
    public function execution()
    {
        $user = auth()->user();

        // return $user->local_broker_id;
        $broker = LocalBroker::where('id', $user['local_broker_id'])->with('user')->first();

        $execution_reports = DB::table('broker_client_order_execution_reports')
            ->where('broker_client_order_execution_reports.senderSubID', $broker->user->name)
            ->select('broker_client_order_execution_reports.*', 'broker_settlement_accounts.account as settlement_account_number', 'broker_settlement_accounts.bank_name as settlement_agent')
            ->join('broker_client_orders', 'broker_client_order_execution_reports.clordid', 'broker_client_orders.client_order_number')
            ->join('broker_trading_accounts', 'broker_client_orders.trading_account_id', 'broker_trading_accounts.id')
            ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
            ->orderBy('messageDate', 'asc')
            ->get();

        return view('brokers.execution')->with('execution_reports', $execution_reports);
    }
    public function clients()
    {
        $operator = auth()->user();
        $operator_clients = BrokerClient::with('permission')->where('local_broker_id', $operator->local_broker_id)->get();
        return view('operators.client')->with('operator_clients', $operator_clients);
    }
    function traderList()
    {

        $local_brokers = LocalBroker::all();
        return $local_brokers;
    }
    function ClientList()
    {

        $operator = auth()->user();
        $clients = BrokerClient::with('local_broker', 'permission')->where('local_broker_id', $operator->local_broker_id)->get();
        return $clients;
    }
    function brokerList()
    {

        $local_brokers = LocalBroker::all();
        return $local_brokers;
    }
    public function orders()
    {
        $user = auth()->user();
        $local_broker = LocalBroker::find($user->local_broker_id);
            //$local_brokers = LocalBroker::all();
            //$foreign_brokers = ForeignBroker::all();

        $broker_admin = User::find($local_broker->user_id);
        // Call The Execution Balance Update Job
        /*
            * Run FIX Message Download Api
            - Import new execution reports only
            - Update the status of orders based on the execution report for this specific broker
            - Update Account Balances based on (REJECTED,CANCELLED,NEW,FILLED,PARTIALLYFILLED)
        */
        $executionBalanceUpdate = new ExecutionBalanceUpdate($broker_admin->name);
        $this->dispatch($executionBalanceUpdate);
        /*--*/

        // Fetch All Broker Clients that are tied to the operators local broker
        $client_accounts = BrokerClient::where('local_broker_id', $user->local_broker_id)->get();
        if (count($client_accounts) > 0) {
            // Sort through clients to get their id then search database for order
            foreach ($client_accounts as $key => $value) {
                $json_decoded = json_decode($value);
                $clients[] = $json_decoded->id;
                $orders = DB::table('broker_client_orders')->select('broker_client_orders.*', 'broker_clients.name as client_name')->whereIn('broker_client_id', $clients)->join('broker_clients', 'broker_client_orders.broker_client_id', 'broker_clients.id')->get();
            }
        } else {
            $orders = [];
        }

        // $executionBalanceUpdate = new ExecutionBalanceUpdate($user);
        return view('operators.order')->with('orders', $orders)->with('client_accounts', $client_accounts);
    }

    public function operatorClientOrder(Request $request)
    {

        //Define The Broker Admin For this Local Broker Operator That is  Making The Order
        $user = auth()->user();
        $user_definition  = LocalBroker::where('id', $user->local_broker_id)->first();
        $local_broker_id = $user_definition->id;
        // ===================================================================


        //Trading Account Information
        $trading = BrokerTradingAccount::find($request['trading_account']);

        //Settlement Account Information
        $settlement = BrokerSettlementAccount::find($trading->broker_settlement_account_id);


        // // Client Account Information
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

                // [Client Open Orders] = [Client Open Orders] + [Order Value]
                $client_open_orders = $broker_client->open_orders + $order_value;


                // Update Settlement ACcount Balances
                BrokerSettlementAccount::updateOrCreate(
                    ['id' => $trading->broker_settlement_account_id],
                    ['amount_allocated' => $settlement_allocated]
                );


                // Update Broker Clients Open Orders
                BrokerClient::updateOrCreate(
                    ['id' => $request['client_trading_account']],
                    ['open_orders' => max($client_open_orders, 0)]
                );

                return $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Submitted', $request->client_trading_account);
                // return response()->json(['isvalid' => true, 'errors' => 'SEND NewOrderSingle() request to the RESTful API!']);
            }
        } else if ($side['fix_value'] === '2') {
            // Do not update balances
            // Create the order in our databases and send order server side using curl
            return $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Submitted', $request->client_trading_account);
        }
    }
    public function operatorTradingAccounts()
    {
        $user = auth()->user();

        $operator_trading_account = DB::table('broker_trading_accounts')->where('broker_users.user_id', $user->id)
                        ->select('users.name as foreign_broker', 'broker_settlement_accounts.bank_name as bank', 'broker_trading_accounts.trading_account_number', 'broker_settlement_accounts.account', 'broker_settlement_accounts.account_balance as balance', 'broker_settlement_accounts.currency', 'broker_trading_accounts.id')
                        ->join('broker_users', 'broker_users.broker_trading_account_id', 'broker_trading_accounts.id')
                        ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
                        ->join('foreign_brokers', 'broker_trading_accounts.foreign_broker_id', 'foreign_brokers.id')
                        ->join('users', 'foreign_brokers.user_id', 'users.id')
                        ->get();
        
        return $operator_trading_account;
    }
}
