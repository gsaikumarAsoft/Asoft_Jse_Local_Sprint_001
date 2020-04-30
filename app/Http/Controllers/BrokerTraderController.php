<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BrokerTraderController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        // $this->
    }
    public function index()
    {


        return view('trader.index');
    }

    public function orderList()
    {

        return "Return Orders Here";
    }
}
