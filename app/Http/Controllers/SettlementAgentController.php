<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettlementAgentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        return view('settlement_agent.index');
    }
}
