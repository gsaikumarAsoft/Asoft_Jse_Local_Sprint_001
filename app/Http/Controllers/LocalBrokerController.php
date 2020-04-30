<?php

namespace App\Http\Controllers;

use App\LocalBroker;
use Illuminate\Http\Request;

class LocalBrokerController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
    }


    public function index(){
        $local_brokers = LocalBroker::all();
        return view('local-brokers')->with('foreign_brokers', $local_brokers);
    }
}
