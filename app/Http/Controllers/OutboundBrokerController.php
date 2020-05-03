<?php

namespace App\Http\Controllers;

use App\BrokerSettlementAccount;
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
}
