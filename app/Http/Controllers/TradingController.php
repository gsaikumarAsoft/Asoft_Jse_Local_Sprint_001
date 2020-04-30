<?php

namespace App\Http\Controllers;

use App\BrokerTradingAccount;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Mail\TradingAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;


class TradingController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
    }
    public function accounts()
    {
        $a = BrokerTradingAccount::all();
        return $a;
    }

    public function store(Request $request)
    {

        // return $request;

        $foreign_broker = $this->HelperClass->getForeignBrokerById($request->foreign_broker_id);
        $local_broker = $this->HelperClass->defineLocalBroker($request->local_broker_id);
        $settlement_data = $this->HelperClass->getSettlementData($request->settlement_account_number);
        // return $settlement_data;
        // return $foreign_broker;
        if ($request->id) {
            LogActivity::addToLog('Update Broker Trading Account Details');
            BrokerTradingAccount::updateOrCreate(
                ['id' => $request->id, 'local_broker_id' =>   $request->local_broker_id],
                ['umir' =>   $request->umir, 'trading_account_number' =>   $request->trading_account_number, 'broker_settlement_account_id' =>   $request->settlement_account_number, 'target_comp_id' =>   $request->target_comp_id, 'sender_comp_id' =>   $request->sender_comp_id,' socket' =>   $request->socket, 'port' =>   $request->port]

            );
        } else {
            $broker = new BrokerTradingAccount();
            $broker->local_broker_id =   $request->local_broker_id;
            $broker->foreign_broker_id =   $request->foreign_broker_id;
            $broker->umir =   $request->umir;
            $broker->trading_account_number =   $request->trading_account_number;
            $broker->broker_settlement_account_id =   $request->settlement_account_number;
            $broker->target_comp_id =   $request->target_comp_id;
            $broker->sender_comp_id =   $request->sender_comp_id;
            $broker->socket =   $request->socket;
            $broker->port =   $request->port;
            $broker->save();

            $request['broker_name'] = $foreign_broker[0]->name;
            $request['local_broker_name'] = $local_broker->name;
            $request['settlement_agent'] = $settlement_data['bank_name'];
            $request['settlement_account_number'] = $settlement_data['account'];
            $request['hash'] = $settlement_data['hash'];
            /* 
            Verify that the DMA Provider and Local Broker admin
             that is assigned to a trading account
             is notified via email when it is added
            */
            // return $foreign_broker;

            // return $request;
            
            Mail::to($foreign_broker[0]->email)->send(new TradingAccount($request));
        }
    }
    public function destroy($id)
    {

        $b = BrokerTradingAccount::find($id);
        $b->delete();
        LogActivity::addToLog('Deleted Broker Trading Account');
    }
}
