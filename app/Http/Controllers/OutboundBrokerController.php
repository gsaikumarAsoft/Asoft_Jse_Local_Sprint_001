<?php

namespace App\Http\Controllers;

use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\ForeignBroker;
use Illuminate\Http\Request;

class OutboundBrokerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        return view('outbound.index');
    }
    public function settlements(){

        $user = auth()->user();
        $a = BrokerSettlementAccount::with('foreign_broker')->where('foreign_broker_id', $user->id)->get();
        return view('outbound.settlements')->with('accounts', $a);
    }
    public function trading(){

        $user = auth()->user();
        $foreign_broker = ForeignBroker::where('user_id', $user->id)->first();
        $a = BrokerTradingAccount::with('foreign_broker')->where('foreign_broker_id', $foreign_broker->id)->get();
        return view('outbound.trading')->with('accounts', $a);
    }
}
