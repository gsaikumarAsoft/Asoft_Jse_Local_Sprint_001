<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Mail\LocalBrokerClient;
use App\Mail\LocalBrokerTrader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
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

        $local_broker = $this->HelperClass->getLocalBrokerById($request->local_broker_id);



        if ($request->id) {
            LogActivity::addToLog('Update Client Details');
            $broker = BrokerClient::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email]

            );
        } else {
            $broker = new BrokerClient;
            $broker->local_broker_id = $request->local_broker_id;
            $broker->name = $request->name;
            $broker->email = $request->email;
            $broker->orders_limit = "";
            $broker->open_orders = "";
            $broker->jcsd = "";
            $broker->status = 'Un-Verified';
            // $broker->password = bcrypt('password');
            $broker->save();

            Mail::to($local_broker->email)->send(new LocalBrokerTrader($request));
        }
    }
    function destroy($id)
    {

        $b = BrokerClient::find($id);
        $b->delete();
        LogActivity::addToLog('Deleted Broker Client');
    }
}
