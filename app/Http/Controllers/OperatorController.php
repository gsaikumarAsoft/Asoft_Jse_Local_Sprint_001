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

        $operator_clients = BrokerClient::with('permission')->Get();
        // return $operator_clients;
        return view('operators.client')->with('operator_clients', $operator_clients);
        // return view('brokers.client');
    }
    function traderList() 
    {

        $local_brokers = LocalBroker::all();
        return $local_brokers;
    }
    function ClientList()
    {

        $clients = BrokerClient::with('local_broker', 'permission')->get();
        return $clients;
    }
    function brokerList()
    {

        $local_brokers = LocalBroker::all();
        return $local_brokers;
    }
}
