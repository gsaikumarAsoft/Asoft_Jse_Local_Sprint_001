<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerTradingAccount;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Mail\LocalBrokerTrader;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TraderController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
    }

    public function index()
    {
        $users = BrokerClient::with('local_broker')->get();
        return $users;
    }
    function store(Request $request)
    {
        $this->HelperClass->createBrokerClient($request);
    }
    function storeOperatorClient(Request $request)
    {
       return $this->HelperClass->createOperatorClient($request);
    }
    function destroy($id)
    {

        $b = BrokerClient::find($id);
        $b->delete();
        LogActivity::addToLog('Deleted Broker Client');
    }

    function list()
    {

        $d = BrokerTradingAccount::with('settlement_account')->get();
        foreach ($d as $key => $account) {
            // return $d[$key]->settlement_account;
            // $account = $d[$key];
            $lb = $this->HelperClass->getUserAll($d[$key]->settlement_account['local_broker_id']);
            $fb = $this->HelperClass->getUserAll($d[$key]->settlement_account['foreign_broker_id']);
            // return $lb['name'];
            $d[$key]['local_broker'] = $lb['name'];
            $d[$key]['foreign_broker'] = $fb['name'];
        }
        return $d;

    }
}
