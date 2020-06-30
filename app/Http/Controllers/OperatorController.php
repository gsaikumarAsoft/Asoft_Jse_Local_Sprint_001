<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerClientOrder;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\BrokerUser;
use App\Helpers\FunctionSet;
use App\LocalBroker;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
    }

    public function index()
    {
        return view('operators.index');
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
        //Broker Operator
        $user = auth()->user();

        // Fetch All Broker Clients that are tied to the operators local broker
        $client_accounts = BrokerClient::where('local_broker_id', $user->local_broker_id)->get();
        if (count($client_accounts) > 0) {
            // Sort through clients to get their id then search database for order
            foreach ($client_accounts as $value) {
                $json_decoded = json_decode($value);
                $clients[] = $json_decoded->id;
                $orders = BrokerClientOrder::whereIn('broker_client_id', $clients)->get();
            }
        } else {
            $orders = [];
        }
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
        $trading = BrokerTradingAccount::with('settlement_account')->find($request->trading_account)->first();

        //Settlement Account Information
        $settlement = BrokerSettlementAccount::find($trading->broker_settlement_account_id)->first();


        // // Client Account Information
        $c_account = BrokerClient::find($request['client_trading_account'])->first();


        // Calculations Before Creating A New Order
        $order_value = $request->price * $request->quantity;
        $settlement_available = $settlement->account_balance - $settlement->amount_allocated;
        $client_available = $c_account->account_balance - $c_account->open_orders;
        // //==================================================

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

            return $this->HelperClass->createBrokerOrder($request, $local_broker_id, 'Submitted', $request->client_trading_account);
            // return response()->json(['isvalid' => true, 'errors' => 'SEND NewOrderSingle() request to the RESTful API!']);
        }
    }
}
