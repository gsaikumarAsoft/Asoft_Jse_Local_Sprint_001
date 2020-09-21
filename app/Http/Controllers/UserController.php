<?php

namespace App\Http\Controllers;

use App\BrokerUser;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Mail\LocalBrokerUser;
use App\Mail\SettlementAccountConfirmation;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function __construct()
    {

        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
    }

    public function index()
    {
        $user = auth()->user();
        $a = BrokerUser::with('user')->where('dma_broker_id', $user->id)->get();
        if (count($a) > 0) {
            foreach ($a as $key => $user) {

                $user = $a[$key]->user;
                $broker_user[$key] = [];
                $broker_user[$key]['id'] = $user->id;
                $broker_user[$key]['name'] = $user->name;
                $broker_user[$key]['email'] = $user->email;
                $broker_user[$key]['status'] = $user->status;
                $broker_user[$key]['permissions'] = $user->permissions;
                $broker_user[$key]['broker_trading_account_id'] = (int) $a[$key]->broker_trading_account_id;
            }
            return $broker_user;
        } else {
            return $a;
        }
    }
    function store(Request $request)
    {
        $this->HelperClass->createBrokerUser($request);
    }
    function destroy($id)
    {

        $b = User::find($id);
        $b->delete();
        LogActivity::addToLog('Deleted Broker User');
    }
}
