<?php

namespace App\Http\Controllers;

use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\ForeignBroker;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\LocalBroker;
use App\Mail\TradingAccount;
use App\Mail\TradingAccountUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;


class TradingController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
        $this->LogActivity = new LogActivity;
    }
    public function accounts()
    {
        $a = BrokerTradingAccount::all();
        return $a;
    }

    public function store(Request $request)
    {

        // return $request;
        $b2b_hash = $this->HelperClass->generateRandomString(20);
        $settlement = BrokerSettlementAccount::find($request->settlement_account_number);
        $settlement_local_broker = $settlement->local_broker_id;
        $settlement_foreign_broker = $settlement->foreign_broker_id;

        $request_local_broker = LocalBroker::where('user_id', $settlement_local_broker)->first();
        $request_foreign_broker = ForeignBroker::where('user_id', $settlement_foreign_broker)->first();

        $foreign_broker = $this->HelperClass->getUserAll($settlement_foreign_broker);
        $local_broker = $this->HelperClass->getUserAll($settlement_local_broker);
        $settlement_data = $this->HelperClass->getSettlementData($request->settlement_account_number);
        
        if ($request->id) {
            $this->LogActivity::addToLog('Update Broker Trading Account: umir = ' . $request->umir . ', trading_account_number = ' . $request->trading_account_number . ', broker_settlement_account_id = ' . $request->settlement_account_number . ', target_comp_id = ' . $request->target_comp_id . ', sender_comp_id = ' . $request->sender_comp_id . ', socket = ' . $request->socket . ', port = ' . $request->port . ',status = Unverified');

            $b2b = BrokerTradingAccount::find($request->id);
            $b2b->update(
                [
                    'umir' =>   $request->umir,
                    'trading_account_number' =>   $request->trading_account_number,
                    'broker_settlement_account_id' =>   $request->settlement_account_number,
                    'target_comp_id' =>   $request->target_comp_id,
                    'sender_comp_id' =>   $request->sender_comp_id,
                    'socket' =>   $request->socket,
                    'port' =>   $request->port,
                    'status' => 'Unverified'
                ]

            );
            $request['broker_name'] = $foreign_broker->name;
            $request['local_broker_name'] = $local_broker->name;
            $request['settlement_agent'] = $settlement_data['bank_name'];
            $request['settlement_account_number'] = $settlement_data['account'];
            // $request['hash'] = $settlement_data['hash'];
            $request['hash'] = $b2b->hash;
            Mail::to($foreign_broker->email)->send(new TradingAccountUpdated($request));
        } else {
            $broker = new BrokerTradingAccount();
            $broker->local_broker_id =   $request_local_broker->id;
            $broker->foreign_broker_id =  $request_foreign_broker->id;
            $broker->umir =   $request->umir;
            $broker->trading_account_number =   $request->trading_account_number;
            $broker->broker_settlement_account_id =   $request->settlement_account_number;
            $broker->target_comp_id =   $request->target_comp_id;
            $broker->sender_comp_id =   $request->sender_comp_id;
            $broker->socket =   $request->socket;
            $broker->hash = $b2b_hash;
            $broker->status = 'Unverified';
            $broker->port =   $request->port;
            $broker->save();

            $request['broker_name'] = $foreign_broker->name;
            $request['local_broker_name'] = $local_broker->name;
            $request['settlement_agent'] = $settlement_data['bank_name'];
            $request['settlement_account_number'] = $settlement_data['account'];
            // $request['hash'] = $settlement_data['hash'];
            $request['hash'] = $b2b_hash;
            /* 
            Verify that the DMA Provider and Local Broker admin
             that is assigned to a trading account
             is notified via email when it is added
            */
            $this->LogActivity::addToLog('Created New Broker Trading Account: umir = ' . $request->umir . ', trading_account_number = ' . $request->trading_account_number . ', broker_settlement_account_id = ' . $request->settlement_account_number . ', target_comp_id = ' . $request->target_comp_id . ', sender_comp_id = ' . $request->sender_comp_id . ', socket = ' . $request->socket . ', port = ' . $request->port . ',status = Unverified');
            Mail::to($foreign_broker->email)->send(new TradingAccount($request));
        }
    }
    public function destroy($id)
    {

        $b = BrokerTradingAccount::find($id);
        $b->delete();
        LogActivity::addToLog('Deleted Broker Trading Account');
    }
}
