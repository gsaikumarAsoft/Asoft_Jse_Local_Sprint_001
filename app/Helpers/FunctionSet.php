<?php

namespace App\Helpers;

use App\BrokerClient;
use App\BrokerClientOrder;
use App\BrokerOrderExecutionReport;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\BrokerUser;
use App\ForeignBroker;
use App\LocalBroker;
use App\Mail\ClientDetailsUpdate;
use App\Mail\LocalBrokerClient;
use App\Mail\LocalBrokerDetailsUpdate;
use App\Mail\LocalBrokerUser;
use App\Role;
use App\User;
use Carbon\Carbon;
use CreateBrokerClientOrderExecutionReports;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class FunctionSet
{

    public function __construct()
    {
        $this->OrderStatus = new OrderStatus;
        $this->LogActivity = new LogActivity;
    }
    public function jsonStrip($value, $field)
    {
        $jsonStr = json_encode($value);

        //Decode the JSON string using json_decode
        $jsonDecoded = json_decode($jsonStr, true);

        //Print out the value we want.
        return $jsonDecoded[$field];
    }

    //set fix wrapper url
    public function fix_wrapper_url($path)
    {
        return env('FIX_API_URL') . $path; //"api/OrderManagement/OrderCancelRequest";
    }

    //check if Order is opened and side = BUY
    public function isOrderOpenedBuy($o)
    {
        return ($o->order_status != $this->OrderStatus->Expired() &&
            $o->order_status != $this->OrderStatus->Cancelled() &&
            $o->order_status != $this->OrderStatus->Rejected() &&
            $o->order_status != $this->OrderStatus->Failed() &&
            $o->side == "BUY");
    }

    public function cancelOrder($id)
    {

        $order = BrokerClientOrder::where('clordid', $id)->first();
        $order_ex = BrokerOrderExecutionReport::where('clordid', $id)->where('status', 'Cancel Submitted')->first();
        if ($order_ex['status'] === "Cancel Submitted") {
            return response()->json(['isvalid' => false, 'errors' => 'A Cancellation Request for this order has already been submitted']);
        }

        $offset = 5 * 60 * 60;
        $dateFormat = "Y-m-d H:i";
        $timeNdate = gmdate($dateFormat, time() - $offset);
        $order = BrokerClientOrder::where('clordid', $id)->first();
        $client = BrokerClient::find($order->broker_client_id);
        $mytime = Carbon::now();
        $cancel_cordid = "ORD" . $mytime->format('YmdH') . "N" . rand(100, 1000); //Create a New cancel order id

        // //Trading Account Information
        $trading = BrokerTradingAccount::with('settlement_account')->find($order->trading_account_id)->first();
        // return $trading->local_broker_id;
        $broker_data = LocalBroker::with('user')->where('id', $trading->local_broker_id)->first();

        // // //Settlement Account Information
        // $settlement = BrokerSettlementAccount::find($trading->broker_settlement_account_id)->first();
        $cancel_order_no = $this->generateOrderNumber(15);
        $url = $this->fix_wrapper_url("api/OrderManagement/OrderCancelRequest");
        $data = array(
            'BeginString' => 'FIX.4.2',
            'TargetCompID' => $trading->target_comp_id,
            'SenderCompID' => $trading->sender_comp_id,
            'SenderSubID' => $trading->trading_account_number,
            'Host' => $trading->socket,
            'OrderID' => $cancel_order_no,
            "OriginalOrderID" => $order->clordid,
            "OrigClOrdID" => $order->clordid,
            "OrderQty" => $order->quantity,
            'BuyorSell' => $this->jsonStrip(json_decode($order->side, true), 'fix_value'),
            "ClientID" => $trading->trading_account_number,
            'Port' => (int) $trading->port,
            'Symbol' => $this->jsonStrip(json_decode($order->symbol, true), 'text'),
            'ClientID' => $trading->trading_account_number,
            'AccountType' => 'CL',
            'StartTime' => "11:00:00.000",
            'EndTime' => "23:30:00.000",
        );

        $postdata = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);

        curl_close($ch);


        $data['text'] = 'Order Cancel Request Submitted';
        $data['status'] = 'Cancel Submitted';


        // Log this cancellation request to the execution reports
        $broker_order_execution_report = new BrokerOrderExecutionReport();
        $broker_order_execution_report->clOrdID = $data['clOrdID'] ?? $data['OrigClOrdID'];
        $broker_order_execution_report->orderID = $data['orderID'] ?? '000000-000000-0';
        $broker_order_execution_report->text = $data['text'];
        $broker_order_execution_report->ordRejRes = $data['ordRejRes'] ?? null;
        $broker_order_execution_report->status = $data['status'] ?? 8;
        $broker_order_execution_report->buyorSell = $data['buyorSell'] ?? $data['BuyorSell'];
        $broker_order_execution_report->securitySubType = 0;
        $broker_order_execution_report->time = $data['time'] ?? null;
        $broker_order_execution_report->ordType = '2';
        $broker_order_execution_report->orderQty = $data['orderQty'] ?? $data['OrderQty'] ?? 0;
        $broker_order_execution_report->timeInForce = $data['timeInForce'] ?? 0;
        $broker_order_execution_report->symbol = $data['symbol'] ?? $data['Symbol'];
        $broker_order_execution_report->qTradeacc = 'JCSD' . $client->jcsd;
        $broker_order_execution_report->price = 0;
        $broker_order_execution_report->stopPx = 0;
        $broker_order_execution_report->execType = 0;
        $broker_order_execution_report->senderSubID = $broker_data->user->name;
        $broker_order_execution_report->seqNum = 0;
        $broker_order_execution_report->sendingTime = $data['sendingTime'] ?? $timeNdate;
        $broker_order_execution_report->messageDate = $data['messageDate'] ?? $timeNdate;
        $broker_order_execution_report->save();

        // return $broker_order_execution_report;
        return response()->json(['isvalid' => true, 'errors' => 'A Cancellation Request for this order has been submitted']);
    }

    public function createBrokerOrder($request, $local_broker_id, $order_status, $client_id)
    {

        // Find Local Broker For This Order & Define the SenderSub Id
        $local_broker = $this->LocalBrokerPick($local_broker_id);
        $sender_sub_id = $local_broker->name;

        $type = json_decode($request->order_type); //Predefine Order Type For JSON ENCODE

        //Locate the Broker Trading Account For This Order
        $trading = BrokerTradingAccount::find($request->trading_account);

        $foreign_broker_id = $this->getForeignBrokerById($trading->foreign_broker_id);

        // Locate the broker client for this order
        $client = BrokerClient::find($client_id);

        //validation
        /*  if (is_null($request->stop_price)) {
            return response()->json(['isvalid' => false, 'errors' => 'The Stop Price is required']);
        }

        if (is_nuill($request->quantity)) {
            return response()->json(['isvalid' => false, 'errors' => 'The Quantity is required']);
        }

        if (is_null($request->price)) {
            return response()->json(['isvalid' => false, 'errors' => 'The Price is required']);
        }

        if ((int) $request->price > (int) $request->stop_price) {
            return response()->json(['isvalid' => false, 'errors' => 'Price cannot be greater than the Stop Price!']);
        } */

        // Store Order to our databases
        $mytime = Carbon::now();
        $broker_client_order = new BrokerClientOrder();
        $broker_client_order->local_broker_id = $local_broker_id;
        $broker_client_order->foreign_broker_id = $foreign_broker_id[0]->id;
        $broker_client_order->handling_instructions = $request->handling_instructions;
        $broker_client_order->order_quantity = $request->quantity;
        $broker_client_order->order_type = $request->order_type;
        $broker_client_order->order_status = $order_status;
        $broker_client_order->order_date = $mytime->toDateTimeString();
        $broker_client_order->currency = $request->currency;
        $broker_client_order->symbol = $request->symbol;
        $broker_client_order->price = $request->price;
        $broker_client_order->value = $request->value;
        $broker_client_order->quantity = $request->quantity;
        $broker_client_order->country = 'Jamaica';
        $broker_client_order->side = $request->side;
        $broker_client_order->status_time = $mytime->toDateTimeString();
        $broker_client_order->client_order_number = $request->client_order_number;
        $broker_client_order->clordid = $request->client_order_number;
        $broker_client_order->market_order_number = $request->market_order_number;
        $broker_client_order->stop_price = $request->stop_price;
        $broker_client_order->expiration_date = $request->expiration_date;
        $broker_client_order->time_in_force = $request->time_in_force;
        $broker_client_order->broker_client_id = $client_id;
        $broker_client_order->remaining = $request->price * $request->quantity;
        $broker_client_order->trading_account_id = $request->trading_account;
        $broker_client_order->save();

        // Send customer order to FIX 4.2 Switch - API Beta Fix Swith PHP Post 4.2
        $url = $this->fix_wrapper_url("api/OrderManagement/NewOrderSingle");
        $data = array(
            'BeginString' => 'FIX.4.2',
            'TargetCompID' => $trading->target_comp_id,
            'SenderCompID' => $trading->sender_comp_id,
            'SenderSubID' => $sender_sub_id,
            'Host' => $trading->socket,
            'Port' => (int) $trading->port,
            'StartTime' => "11:00:00.000",
            'EndTime' => "23:30:00.000",
            'OrderID' => $request->client_order_number,
            'BuyorSell' => $this->jsonStrip(json_decode($request->side, true), 'fix_value'),
            'OrdType' => $this->jsonStrip(json_decode($type, true), 'fix_value'),
            'Symbol' => $this->jsonStrip(json_decode($request->symbol, true), 'text'),
            'Account' => 'JCSD' . $client->jcsd,
            'ClientID' => $trading->trading_account_number,
            'AccountType' => 'CL',
        );

        //Check if this is an iceberg order
        if ($request->has('max_floor') && $request->has('display_range')) {
            $data['maxFloor'] = $request->max_floor;
            $data['displayRange'] = $request->display_range;
        }
        if ($request->has('expiration_date')) {
            $data['expireDate'] = $request->expiration_date;
        }
        if ($request->has('stop_price')) {
            $data['stopPx'] = (int)$request->stop_price;
        }
        if ($request->has('quantity')) {
            $data['OrderQty'] = $request->quantity;
        }
        if ($request->has('time_in_force')) {
            $data['TimeInForce'] = $this->jsonStrip(json_decode($request->time_in_force, true), 'fix_value');
        }
        if ($request->has('price')) {
            $data['Price'] = $request->price;
        }
        if ($request->has('handling_instructions')) {
            $data['HandlInst'] = $this->jsonStrip(json_decode($request->handling_instructions, true), 'fix_value');
        } else {
            $data['HandlInst'] = '1';
        }

        $postdata = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        // ================================================================================================
        $data['text'] = 'Order Submitted Successfully';
        $data['status'] = 'Submitted';
        $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
        // ================================================================================================

        // return $result;
        $fix_status = json_decode($result, true);

        switch ($fix_status['result']) {
            case "Session could not be established with CIBC. Order number {0}":
                $this->LogActivity->addToLog('Order Failed:' . $fix_status['result'] . '-' . $request->client_order_number);
                $data['text'] = 'Order Submission Failed: ' . $fix_status['result'] . '-' . $request->client_order_number;
                $data['status'] = 'Session Failed';
                $order = DB::table('broker_client_orders')
                    ->where('id', $broker_client_order->id)
                    ->update(['order_status' => $this->OrderStatus->Failed()]);
                $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $fix_status['result'] . '-' . $request->client_order_number]);

                break;
            case "Please Check the endpoint /MessageDownload/Download for message queue":
                // If the order is successfull create a log
                $this->LogActivity->addToLog('Order Successfull: Please Check the endpoint /MessageDownload/Download for message queue' . '-' . $request->client_order_number);
                $this->executionBalanceUpdate($sender_sub_id, $trading->trading_account_number);
                // return response()->json(['isvalid' => true, 'errors' => 'SENT NewOrderSingle() request to the RESTful API!']);
                return response()->json(['isvalid' => true, 'errors' => 'New Order Single Sent!']);
                break;
            default:

                // If the response fails create a record in the audit log and in the execution reports as well
                $data['text'] = "Order Submission Failed: " . $fix_status['result'];
                $data['status'] = $this->OrderStatus->Failed();
                // ============================================================================================
                DB::table('broker_client_orders')
                    ->where('id', $broker_client_order->id)
                    ->update(['order_status' => $this->OrderStatus->Failed()]);
                $this->LogActivity->addToLog('Order Failed For: ' . $request->client_order_number . '. Message: ' . $data['text']);
                $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $data['text']]);
                break;
        }
    }
    public function logExecution($request)
    {

        // return $request;

        $execution_report = $request["executionReports"];
        $offset = 5 * 60 * 60;
        $dateFormat = "Y-m-d H:i";
        $timeNdate = gmdate($dateFormat, time() - $offset);
        if ($execution_report) {

            foreach ($execution_report as $report) {
                $clients[] = $report;

                $record = BrokerOrderExecutionReport::where('senderSubID', array_values($report)[16])->where('seqNum', array_values($report)[17])->where('clOrdID', array_values($report)[1])->where('sendingTime', array_values($report)[18]);
                if ($record->exists()) {
                    //IF THE RECORD ALREADY EXISTS DO NOTHING TO IT

                } else {
                    // IF IT IS A NEW RECORD INSERT IT AND UPDATE THE BALANCES
                    $broker_order_execution_report = new BrokerOrderExecutionReport();
                    $broker_order_execution_report->clOrdID = $report['clOrdID'] ?? $report['OrderID'];
                    $broker_order_execution_report->orderID = $report['orderID'] ?? '000000-000000-0';
                    $broker_order_execution_report->text = $report['text'];
                    $broker_order_execution_report->ordRejRes = $report['ordRejRes'] ?? null;
                    $broker_order_execution_report->status = $report['status'] ?? 8;
                    $broker_order_execution_report->buyorSell = $report['buyorSell'] ?? $report['BuyorSell'];
                    $broker_order_execution_report->securitySubType = 0;
                    $broker_order_execution_report->time = $report['time'] ?? null;
                    $broker_order_execution_report->ordType = $report['ordType'] ?? $report['OrdType'];
                    $broker_order_execution_report->orderQty = $report['orderQty'] ?? $report['OrderQty'] ?? 0;
                    $broker_order_execution_report->timeInForce = $report['timeInForce'] ?? 0;
                    $broker_order_execution_report->symbol = $report['symbol'] ?? $report['Symbol'];
                    $broker_order_execution_report->qTradeacc = $report['qTradeacc'] ?? $report['Account'];
                    $broker_order_execution_report->price = $report['price'] ?? $report['Price'];
                    $broker_order_execution_report->stopPx = $report['stopPx'] ?? 0;
                    $broker_order_execution_report->execType = $report['execType'] ?? 0;
                    $broker_order_execution_report->senderSubID = $report['senderSubID'] ?? $report['SenderSubID'];
                    $broker_order_execution_report->seqNum = $report['seqNum'] ?? 0;
                    $broker_order_execution_report->sendingTime = $report['sendingTime'] ?? $timeNdate;
                    $broker_order_execution_report->messageDate = $report['messageDate'] ?? $timeNdate;
                    $broker_order_execution_report->save();

                    // UPDATE THE CLIENT & SETTLEMENT ACCOUNT BALANCES DEPENDING ON THE ACCOUNT STATUS FROM THE ORDER EXECUTION REPORT
                    return $this->clientSettlementBalanceUpdate($report);
                }
            }
        }
        //Uncomment the below to handle reject messages
        // if ($request["rejectMessages"]) {
        //     foreach ($request["rejectMessages"] as $rejRep) {
        //         // return $rejRep;
        //         //Define the order number we want to reject
        //         $order_no = $rejRep['clOrdID'];
        //         $record = BrokerOrderExecutionReport::where('clordid', $order_no)->where('text', $rejRep['text']);
        //         if ($record->exists()) {
        //             //IF THE RECORD ALREADY EXISTS DO NOTHING TO IT

        //         } else {
        //             // return "Reject Record Doesnt Exist";

        //             //Process rejection of orders
        //             //Find the order that was peviously made based on the current $order_no
        //             $order = BrokerClientOrder::where('clordid', $order_no)->first();
        //             $order_total = number_format($order["price"] * $order->quantity, 2, '.', '');
        //             $remaining_balance_of_order = $order["remaining"];

        //             //define the trading, settlement & client account for this order 
        //             $trading_account = BrokerTradingAccount::find($order->trading_account_id);
        //             $settlement_account = BrokerSettlementAccount::find($trading_account->broker_settlement_account_id);
        //             $client = BrokerClient::find($order->broker_client_id);

        //             $broker_order_execution_report = new BrokerOrderExecutionReport();
        //             $broker_order_execution_report->clOrdID = $order_no;
        //             $broker_order_execution_report->orderID = '000000-000000-1';
        //             $broker_order_execution_report->text = $rejRep['text'];
        //             $broker_order_execution_report->ordRejRes = null;
        //             $broker_order_execution_report->status = $this->OrderStatus->Rejected();
        //             $broker_order_execution_report->buyorSell = $this->jsonStrip(json_decode($order->side, true), 'fix_value');
        //             $broker_order_execution_report->securitySubType = 0;
        //             $broker_order_execution_report->time = null;
        //             $broker_order_execution_report->ordType = 99;»
        //             $broker_order_execution_report->orderQty = 0;
        //             $broker_order_execution_report->timeInForce = 0;
        //             $broker_order_execution_report->symbol = null;
        //             $broker_order_execution_report->qTradeacc = 'JCSD' . $client->jcsd;
        //             $broker_order_execution_report->price = 0;
        //             $broker_order_execution_report->stopPx = $rejRep['stopPx'] ?? 0;
        //             $broker_order_execution_report->execType = $rejRep['execType'] ?? 0;
        //             $broker_order_execution_report->senderSubID = $rejRep['senderSubID'];
        //             $broker_order_execution_report->seqNum = $rejRep['seqNum'] ?? 0;
        //             $broker_order_execution_report->sendingTime = $rejRep['sendingTime'] ?? $timeNdate;
        //             $broker_order_execution_report->messageDate = $rejRep['messageDate'] ?? $timeNdate;
        //             $broker_order_execution_report->save();

        //             // return $client;

        //             if ($remaining_balance_of_order === $order_total) {
        //                 //if the balance remaining and the order total are the same the order has not been filled or partially filled
        //                 //just update the order status to rejected
        //                 // Update the status to rejected and remove the remaining balance
        //                 BrokerClientOrder::updateOrCreate(
        //                     ['clordid' => $order_no],
        //                     ['remaining' => '0', 'status' =>  $this->OrderStatus->Rejected()]
        //                 );
        //                 //Write to audit trail
        //             } else if ($remaining_balance_of_order < $order_total & $remaining_balance_of_order > 0) {
        //                 // if the remaining balance is less than the order total: the current order has a partial fill
        //                 // return the partially filled amount the client account balance and settlement account
        //                 // reset the remaining amount to 0;


        //                 //find how much was partially filled
        //                 $partially_filled_amount = $order_total - $remaining_balance_of_order;

        //                 //Return the partally filled amount to the client and settlement account balances.
        //                 // Update Settlement Account Balances
        //                 $bsa = BrokerSettlementAccount::updateOrCreate(
        //                     ['id' => $settlement_account->id],
        //                     ['amount_allocated' => (int) $settlement_account->amount_allocated - $partially_filled_amount, 'account_balance' => (int) $settlement_account->account_balance + $partially_filled_amount, 'filled_orders' => (int) $settlement_account->filled_orders - (int) $partially_filled_amount]

        //                 );
        //                 // // Update Broker Clients Open Orders
        //                 $bc = BrokerClient::updateOrCreate(
        //                     ['id' => $client->id],
        //                     ['open_orders' => $client->open_orders - $partially_filled_amount, 'filled_orders' => $client->filled_orders - $partially_filled_amount]
        //                 );



        //                 BrokerClientOrder::updateOrCreate(
        //                     ['client_order_number' =>  (int)$order_no],
        //                     ['order_status' => $this->OrderStatus->Rejected(), 'remaining' => '0.00']
        //                 );
        //             }
        //         }
        //     }
        // }
    }
    public function executionBalanceUpdate($sender_sub_id, $trading_account_number)
    {
        // Call fix and return excutiun report for the required SensderSubID
        $url = $this->fix_wrapper_url("api/messagedownload/download");
        $data = array(
            'BeginString' => 'FIX.4.2',
            "SenderSubID" => $sender_sub_id,
            "seqNum" => 0,
            'StartTime' => "11:00:00.000",
            'EndTime' => "21:00:00.000",
        );
        $postdata = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        $request = json_decode($result, true);
        $account = $request['executionReports'];

        //Store Execution reports for above sender_Sub_id to database before updating account balances
        return $this->logExecution($request);
    }

    public function clientSettlementBalanceUpdate($data)
    {
        // return $data;

        // iterate through all reports and update accounts as required
        $order_number = array_values($data)[1];
        $side = array_values($data)[6];
        $sender_sub_id = array_values($data)[16];
        $price = array_values($data)[13];
        $quantity = array_values($data)[9];
        $status = array_values($data)[5];
        $jcsd_num = array_values($data)[12];
        // return $order_number;

        //Simulation Data Variables ========================//
        // $jcsd_num = array_values($data)[13];
        // $quantity = array_values($data)[10];
        // $price = array_values($data)[14];
        // $status = array_values($data)[5];
        //    ==================================================


        $jcsd = str_replace('JCSD', "", $jcsd_num);
        // Define The broker client
        // $broker_client = BrokerClientOrder::where('client_order_number', $order_number)->first();
        $broker_client = BrokerClient::where('jcsd', $jcsd)->first();


        //Find the broker order linked to this execution report (account number)
        $order = BrokerClientOrder::where('clordid', $order_number)->first();

        if ($order) {
            //Trading Account Information
            $trading = BrokerTradingAccount::find($order->trading_account_id)->first();

            //Find the broker settlement account linked to this execution report (account number (senderSubID)
            $settlement_account = DB::table('broker_trading_accounts')->where('trading_account_number', $trading["trading_account_number"])
                ->select('broker_trading_accounts.broker_settlement_account_id as trading_id', 'broker_trading_accounts.trading_account_number', 'broker_settlement_accounts.*')
                ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
                ->get();
            $array = json_decode(json_encode($settlement_account), true);
            if ($order && $broker_client) {
                $current_order = $order;
                $bc = $broker_client;
                // return $bc;
                if ($current_order->id) {
                    $order_status = $this->orderStatus($current_order->id);
                    $sa = $array[0];

                    $order_value = $quantity * $price;
                    // return $order_status;
                    // [Settlement Allocated] = [Settlement Allocated] + [Order Value]  
                    $settlement_allocated = (int) $sa['amount_allocated'] + $order_value;
                    // [Client Open Orders] = [Client Open Orders] + [Order Value]
                    $client_open_orders = (int) $bc['open_orders'] + $order_value;

                    //If offer is (Rejected, Cancelled, Expired Or New)
                    if (
                        $status === $this->OrderStatus->Expired() ||
                        $status === $this->OrderStatus->Cancelled() ||
                        $status === $this->OrderStatus->Failed() ||
                        $status === $this->OrderStatus->Rejected() ||
                        $status === $this->OrderStatus->_New()
                    ) {
                        // UPDATE ORDER STATUS ONLY
                        BrokerClientOrder::updateOrCreate(
                            ['id' => $current_order->id],
                            ['order_status' => $status]

                        );
                    } else if ($status === $this->OrderStatus->Filled()) {

                        // UPDATE THE ORDER STATUS 
                        $broker_client_order = BrokerClientOrder::updateOrCreate(
                            ['id' => $current_order->id],
                            ['order_status' => $status]

                        );

                        // Update Settlement Account Balances
                        $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                            ['id' => $sa['id']],
                            ['amount_allocated' => (int) $sa['amount_allocated'] + $order_value, 'account_balance' => (int) $sa['account_balance'] - $order_value, 'filled_orders' => (int) $sa['filled_orders'] + (int) $order_value]
                        );


                        // Update Broker Clients Open Orders
                        $broker_client_account = BrokerClient::updateOrCreate(
                            ['id' => $bc->id],
                            ['open_orders' => $client_open_orders, 'filled_orders' => $bc->filled_orders + (int)$order_value]
                        );
                    } else if ($status === $this->OrderStatus->PartialFilled()) {

                        $order_value = (int)$quantity * $price;
                        $current_order_value = $current_order['price'] * $current_order['quantity'];
                        $current_filled_orders = $sa['filled_orders'];

                        // return $current_order_value;
                        $being_filled = $current_order_value - $order_value;
                        $remaining = $current_order['filled_orders'] + $current_order_value - $order_value;

                        if ($current_order['remaining'] > 0 && $current_order['remaining'] == number_format($current_order_value, 2, '.', '')) {
                            //First Partial Fill
                            $brokerClientOrder = BrokerClientOrder::updateOrCreate(
                                ['id' => $current_order->id],
                                ['order_status' => $status, 'remaining' => $current_order['remaining'] - $remaining]

                            );


                            $brokerSettlement = BrokerSettlementAccount::updateOrCreate(
                                ['id' => $sa['id']],
                                ['amount_allocated' => $sa['filled_orders'] - $current_order_value, 'account_balance' => $sa['account_balance'] - $current_order_value, 'filled_orders' => $sa['filled_orders'] + $current_order_value]
                            );


                            // // Update Broker Clients Open Orders
                            $brokerClient = BrokerClient::updateOrCreate(
                                ['id' => $bc->id],
                                ['open_orders' => $bc['open_orders'] - $current_order_value, 'filled_orders' => $bc->filled_orders + $current_order_value]
                            );
                        } else if ($current_order['remaining'] > 0 && $current_order['remaining'] < number_format($current_order_value, 2, '.', '')) {
                            // UPDATE THE ORDER STATUS 
                            // second partial Fill

                            $brokerClientOrder = BrokerClientOrder::updateOrCreate(
                                ['id' => $current_order->id],
                                ['order_status' => $status, 'remaining' => $current_order['remaining'] - $remaining]

                            );
                        } else {
                            $brokerClientOrder = BrokerClientOrder::updateOrCreate(
                                ['id' => $current_order->id],
                                ['order_status' => $this->OrderStatus->Filled(), 'remaining' => 0.00]

                            );
                        }
                    }
                }
            }
        }
    }
    public function defineLocalBroker($id)
    {
        $b = LocalBroker::find($id)->with('user')->first();
        return $b['user'];
    }
    public function getUserRole($id)
    {

        $user = User::with('roles')->find($id);
        return $user;
    }

    public function getUser($id)
    {

        // return $id;
        $user = User::select('name')->where('id', $id)->get();
        return $user;
    }

    public function getSettlementUserByEmail($email)
    {

        // return $id;
        $user = User::where('email', $email)->get();
        return $user;
    }

    public function getUserAll($id)
    {

        // return $id;
        $user = User::where('id', $id)->first();
        return $user;
    }

    public function LocalBrokerPick($id)
    {
        $local_broker = LocalBroker::find($id);
        $broker = User::find($local_broker->user_id);
        return $broker;
    }
    public function getLocalBrokerById($id)
    {
        $broker = User::find($id)->first();
        return $broker;
    }
    public function getSettlementData($id)
    {
        $settlement = BrokerSettlementAccount::find($id);
        return $settlement;
    }

    public function getForeignBrokerById($id)
    {
        $broker = ForeignBroker::find($id);
        $user = User::where('id', $broker->user_id)->get();
        return $user;
    }
    public function createBrokerTradingAccount($account_details)
    {
        $broker_trading_account = new BrokerTradingAccount();
        $broker_trading_account->local_broker_id = $account_details->local_broker_id;
        $broker_trading_account->foreign_broker_id = $account_details->foreign_broker_id;
        $broker_trading_account->broker_settlement_account_id = $account_details['id'];
        $broker_trading_account->umir = $account_details->umir;
        $broker_trading_account->target_comp_id = $account_details->target_comp_id;
        $broker_trading_account->sender_comp_id = $account_details->sender_comp_id;
        $broker_trading_account->socket = $account_details->socket;
        $broker_trading_account->port = $account_details->port;
        $broker_trading_account->save();
    }

    public function addPermission($account_id, $permissions, $target)
    {

        // return $user;
        // $user->givePermissionTo('create-broker-user');

        $permission_length = count($permissions);
        for ($i = 0; $i < $permission_length; $i++) {
            // $user->givePermissionTo($permissions[$i] . '-' . $target);

            $user = User::find($account_id); // returns an instance of \App\User
            $user->givePermissionTo($permissions[$i] . '-' . $target);
        }
    }

    public function createOperatorClient($request)
    {

        $user = auth()->user();
        $request['local_broker_id'] = $user->local_broker_id;

        $local_broker = LocalBroker::with('user')->where('id', $request->local_broker_id)->first();
        // $broker_owner = LocalBroker::where('user_id', $local_broker->id)->first();
        // return $request['local_broker_id'];
        $broker_user = $local_broker['user'];

        if ($request->id) {
            // If the operator didnt select a status default to unverified
            if (empty($request->operator_status)) {
                $request->status = 'Un-Verified';
            } else {
                $request->status = $request->operator_status;
            }

            LogActivity::addToLog('Update Client Details');
            $broker_client = BrokerClient::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email, 'status' => $request->status, 'jcsd' => $request->jcsd, 'account_balance' => $request->account_balance]

            );
        } else {

            $broker_client = new BrokerClient;
            $broker_client->local_broker_id = $local_broker->id;
            $broker_client->name = $request->name;
            $broker_client->email = $request->email;
            $broker_client->orders_limit = $request->account_balance;
            $broker_client->account_balance = $request->account_balance;
            $broker_client->open_orders = '0';
            $broker_client->jcsd = $request->jcsd;
            $broker_client->status = 'Un-verified';
            $broker_client->save();
            // $broker_client->roles()->attach($role_TRDB);
            $request['id'] = $broker_client->id;

            //Adds Permissions Selected For Sprint Final
            // $this->HelperClass->addPermission($request->permission, $broker_client->id, 'Broker Client');
            Mail::to($broker_user['email'])->send(new LocalBrokerClient($request));
        }
    }

    public function createBrokerClient($request)
    {
        // return $request;
        $local_broker = LocalBroker::with('user')->where('user_id', $request->local_broker_id)->first();

        $broker_user = $local_broker['user'];

        if ($request->id) {
            $this->LogActivity::addToLog('Updated Client Account Details: JCSD: ' . $request->jcsd . ', Balance: ' . $request->account_balance . ', Open Orders: ' . $request->open_orders);
            $b = BrokerClient::find($request->id);
            $b->update(
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'status' => 'Unverified',
                    'open_orders' => $request->open_orders,
                    'filled_orders' => $request->filled_orders,
                    'jcsd' => $request->jcsd,
                    'account_balance' => $request->account_balance,
                ]

            );
            Mail::to($broker_user['email'])->send(new ClientDetailsUpdate($request));
        } else {

            $broker_client = new BrokerClient;
            $broker_client->local_broker_id = $local_broker->id;
            $broker_client->name = $request->name;
            $broker_client->email = $request->email;
            $broker_client->orders_limit = $request->account_balance;
            $broker_client->account_balance = $request->account_balance;
            $broker_client->open_orders = $request->open_orders;
            $broker_client->filled_orders = $request->filled_orders;
            $broker_client->jcsd = $request->jcsd;
            $broker_client->status = 'Un-verified';
            $broker_client->save();
            // $broker_client->roles()->attach($role_TRDB);
            $request['id'] = $broker_client->id;

            //Adds Permissions Selected For Sprint Final
            // $this->HelperClass->addPermission($request->permission, $broker_client->id, 'Broker Client');
            $this->LogActivity::addToLog('Created Client Account: JCSD: ' . $request->jcsd . ', Balance: ' . $request->account_balance . ', Open Orders: ' . $request->open_orders . ', Filed Orders: ' . $request->filled_orders);
            Mail::to($broker_user['email'])->send(new LocalBrokerClient($request));
        }
    }

    public function createBrokerUser($request)
    {

        $local_broker = $this->getUserAll($request->local_broker_id);
        $broker_owner = LocalBroker::where('user_id', $local_broker->id)->first();
        // return $broker_owner;

        if ($request->id) {
            LogActivity::addToLog('Update User Details');
            $broker = User::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email, 'status' => 'Unverified']

            );

            $broker_user = BrokerUser::updateOrCreate(
                ['user_id' => $request->id],
                ['broker_trading_account_id' => $request->broker_trading_account_id]

            );

            $broker->syncPermissions();

            $permission_length = count($request->permissions);
            for ($i = 0; $i < $permission_length; $i++) {
                // $user->givePermissionTo($permissions[$i] . '-' . $target);
                $broker->givePermissionTo($request->permissions[$i]);

                // $user->givePermissionTo($request->permissions[$i]);
                // $user = User::find($user->is); // returns an instance of \App\User

            }
        } else {
            $role_OPRB = Role::where('name', 'OPRB')->first();
            $pass = $this->rand_pass(8);
            $hash = $this->generateRandomString(20);
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($pass);
            $user->status = 'Unverified';
            $user->local_broker_id = $broker_owner['id'];
            $user->hash = $hash;
            $user->save();
            $user->roles()->attach($role_OPRB);
            $request['id'] = $user->id;
            $request['hash'] = $hash;
            $request['p'] = $pass;

            $broker_user = new BrokerUser();
            $broker_user->user_id = $user->id;
            $broker_user->dma_broker_id = $request->local_broker_id;
            $broker_user->broker_trading_account_id = $request->broker_trading_account_id;
            $broker_user->save();

            //Check to see how many permission have been selected to appl to the new broker user
            $permission_length = count($request->permissions);
            for ($i = 0; $i < $permission_length; $i++) {

                //Apply the specific permission for the target type selected
                $user->givePermissionTo($request->permissions[$i]);
                // $user = User::find($user->is); // returns an instance of \App\User

            }

            //Notify Local Broker Admin
            Mail::to($local_broker->email)->send(new LocalBrokerUser($request));
        }
    }

    public function rand_pass($length)
    {

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }
    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function generateOrderNumber($length = 10)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function orderStatus($id)
    {
        // check current broker client order status
        $order_status = BrokerClientOrder::select('order_status')->where('id', $id)->first();
        return $order_status;
    }
}
