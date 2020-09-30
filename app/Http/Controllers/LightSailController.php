<?php

namespace App\Http\Controllers;

use App\Jobs\ExecutionBalanceUpdate;
use App\LocalBroker;
use Illuminate\Http\Request;

class LightSailController extends Controller
{
    public function messageDownload()
    {
        // Call The Execution Balance Update Job
        /*
        * Run FIX Message Download Api
        - Import new execution reports only
        - Update the status of orders based on the execution report for this specific broker
        - Update Account Balances based on (REJECTED,CANCELLED,NEW,FILLED,PARTIALLYFILLED)
    */

        //Define all sender sub ids

        $data = LocalBroker::with('user')->get();
 
        //Loop through list of local brokers
        //Local broker names are used as the sender sub id when requesting message download
        foreach ($data as $d) {

            //SenderSubId Value
            $senderSubID = $d->user->name;

            $executionBalanceUpdate = new ExecutionBalanceUpdate($senderSubID);

            //Call Execution Balance Update Job
            $this->dispatch($executionBalanceUpdate);
        }
    }
}
