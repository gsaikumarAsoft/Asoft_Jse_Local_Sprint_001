<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\LocalBroker;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
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
}
