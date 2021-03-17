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
            'OrderID' => $cancel_order_no, //NEW ORDER NUMBER GENERATED FOR CANCELLATION REQUEST
            "OriginalOrderID" => $order->clordid, //ORDER NUMBER FOR THE ORIGINAL ORDER PLACED
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
        $broker_order_execution_report->clOrdID = $order->clordid;
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

        //Settlement 
        $settlement = BrokerSettlementAccount::find($trading->broker_settlement_account_id);

        // Locate the broker client for this order
        $client = BrokerClient::find($client_id);

        // Validation
        if (!$request->has('symbol')) {
            return response()->json(['isvalid' => false, 'errors' => 'Symbol is required']);
        }
        /*
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
        $broker_client_order->max_floor = $request->max_floor;
        $broker_client_order->display_range = $request->display_range;
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
        if ($request->has('display_range')) {
            $data['displayRange'] = $request->display_range;
        }
        if ($request->has('max_floor')) {
            $data['maxFloor'] = $request->max_floor;
        }
        if ($request->has('expiration_date')) {
            $data['expireDate'] = $request->expiration_date;
        }
        if ($request->has('stop_price')) {
            $data['stopPx'] = floatval($request['stop_price']);
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
        // Check if any error occurred

        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds
        // curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        // curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        // curl_setopt($ch, CURLOPT_TIMEOUT, '20L');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        // ================================================================================================
        $data['text'] = 'Order Submitted Successfully';
        $data['status'] = 'Submitted';
        $this->createDMAExecutionReport($data, $broker_client_order);
        // ================================================================================================



        $fix_status = json_decode($result, true);
        $result_len = isset($cOTLdata[$result]) ? count($cOTLdata[$result]) : 0;
        $fix_status_result = '';

        if ($result_len > 0) {
            $fix_status_result = $fix_status['result'];
        }


        $order_value = $request->quantity * $request->price;

        $settlement_allocated = $settlement->amount_allocated - $order_value;
        $client_open_orders = $client->open_orders - $order_value;
        $side = json_decode($request->side, true);


        switch ($fix_status_result) {
            case "Session could not be established with CIBC. Order number {0}":
                $this->LogActivity->addToLog('Order Failed:' . $fix_status_result . '-' . $request->client_order_number);
                $data['text'] = 'Order Submission Failed: ' . $fix_status_result . '-' . $request->client_order_number;
                $data['status'] = 'Session Failed';

                // Return funds only if this is a buy order as we deducted funds previously
                if ($side['fix_value'] === '1') {
                    BrokerClientOrder::updateOrCreate(
                        ['id', $broker_client_order['id']],
                        ['order_status' => $this->OrderStatus->Failed(), 'remaining' => $broker_client_order['remaining'] - $order_value]
                    );

                    BrokerSettlementAccount::updateOrCreate(
                        ['hash' => $settlement->hash],
                        ['amount_allocated' => $settlement_allocated]
                    );


                    // Update Broker Clients Open Orders
                    BrokerClient::updateOrCreate(
                        ['id' => $client_id],
                        ['open_orders' => $client_open_orders]
                    );
                }


                $this->createDMAExecutionReport($data, $broker_client_order);
                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $fix_status_result . '-' . $request->client_order_number]);
                break;
            case "Please Check the endpoint /MessageDownload/Download for message queue":
                // If the order is successfull create a log
                $this->LogActivity->addToLog('Order Successfull: Please Check the endpoint /MessageDownload/Download for message queue' . '-' . $request->client_order_number);
                // $this->executionBalanceUpdate($sender_sub_id);
                return response()->json(['isvalid' => true, 'errors' => 'New Order Single Sent!']);
                break;
            default:
                // If the response fails create a record in the audit log and in the execution reports as well
                $data['text'] = "Order Submission Failed: " . $fix_status_result;
                $data['status'] = $this->OrderStatus->Failed();
                // ============================================================================================

                if ($side['fix_value'] === '1') {

                    BrokerClientOrder::updateOrCreate(
                        ['id' => $broker_client_order['id']],
                        ['order_status' => $this->OrderStatus->Failed(), 'remaining' => $broker_client_order['remaining'] - $order_value]
                    );
                    //Return Funds Upon Failing To Submit The Order
                    // Update Settlement Account Balances

                    BrokerSettlementAccount::updateOrCreate(
                        ['hash' => $settlement->hash],
                        ['amount_allocated' => $settlement_allocated]
                    );


                    // Update Broker Clients Open Orders
                    BrokerClient::updateOrCreate(
                        ['id' => $client_id],
                        ['open_orders' => $client_open_orders]
                    );
                    $this->LogActivity->addToLog('Order Funds Returned: ' . $request->client_order_number . '. Message: ' . $data['text']);
                }

                $this->LogActivity->addToLog('Order Failed For: ' . $request->client_order_number . '. Message: ' . $data['text']);
                $this->createDMAExecutionReport($data, $broker_client_order);
                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $data['text']]);
                break;
        }
    }
    public function createDMAExecutionReport($data, $order)
    {

        $offset = 5 * 60 * 60;
        $dateFormat = "Y-m-d H:i:s";
        $timeNdate = gmdate($dateFormat, time() - $offset);
        // return $order;
        $broker_order_execution_report = new BrokerOrderExecutionReport();
        $broker_order_execution_report->clOrdID = $order['clordid'];
        $broker_order_execution_report->orderID = "0-00000000-00000-00";
        $broker_order_execution_report->text = $data['text'];
        $broker_order_execution_report->ordRejRes = $data['ordRejRes'] ?? null;
        $broker_order_execution_report->status = $data['status'];
        $broker_order_execution_report->buyorSell = $data['BuyorSell'];
        $broker_order_execution_report->securitySubType = 0;
        $broker_order_execution_report->time = $data['time'] ?? null;
        $broker_order_execution_report->ordType = 2;
        $broker_order_execution_report->orderQty = $data['OrderQty'];
        $broker_order_execution_report->timeInForce = $data['timeInForce'] ?? 0;
        $broker_order_execution_report->symbol = $data['Symbol'];
        $broker_order_execution_report->qTradeacc = $data['Account'];
        $broker_order_execution_report->price = $order['price'];
        $broker_order_execution_report->stopPx = $order['stopPx'] ?? 0;
        $broker_order_execution_report->execType = $data['execType'] ?? 0;
        $broker_order_execution_report->senderSubID = $data['SenderSubID'];
        $broker_order_execution_report->seqNum = $data['seqNum'] ?? 0;
        $broker_order_execution_report->sendingTime = $timeNdate;
        $broker_order_execution_report->messageDate = $timeNdate;
        $broker_order_execution_report->save();
    }
    public function logExecution($report)
    {

        /*
        JSE PAYLOAD Example (Array Positions)
        --------------------------------------------
            
            0"id"
            1"clOrdID"
            2"origClOrdID"
            3"orderID"
            4"text"
            5"ordRejRes"
            6"status"
            7"buyorSell"
            8"time"
            9"ordType"
            10"orderQty"
            11"timeInForce"
            12"symbol"
            13"qTradeacc"
            14"price"
            15"stopPx"
            16"execType"
            17"senderSubID"
            18"lastPrice"
            19"lastOrderQty"
            20"seqNum"
            21"sendingTime"
            22"messageDate"
        
        ----------------------------------------------
        */
        if (array_values($report)[1] === "XXXX") {
            $report['clOrdID'] = array_values($report)[2];
        }
        $execution_report = $report;
        $offset = 5 * 60 * 60;
        $dateFormat = "Y-m-d H:i";
        $timeNdate = gmdate($dateFormat, time() - $offset);
        if ($execution_report) {
            if (count($report) == 21) {
                $time = $timeNdate;
            } else {
                $time = array_values($report)[21];
            }

            $record = BrokerOrderExecutionReport::where('senderSubID', array_values($report)[17])->where('seqNum', array_values($report)[20])->where('sendingTime', $time);
            if ($record->exists()) {
                //IF THE RECORD ALREADY EXISTS DO NOTHING TO IT but update the marker order number
                DB::table('broker_client_orders')->where('clordid', $report['clOrdID'])->update(['market_order_number' => $report['orderID']]);
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
            // }
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
    public function executionBalanceUpdate($sender_sub_id)
    {
        // Call fix and return excutiun report for the required SensderSubID
        $url = $this->fix_wrapper_url("api/messagedownload/download");
        $data = array(
            'BeginString' => 'FIX.4.2',
            "SenderSubID" => $sender_sub_id,
            "seqNum" => 0,
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

        $request = json_decode($result, true);
        $account = $request['executionReports'];

        //Store Execution reports for above sender_Sub_id to database before updating account balances
        return $this->logExecution($request);
    }

    public function clientSettlementBalanceUpdate($data)
    {
        /*
        JSE PAYLOAD Example (Array Positions)
        --------------------------------------------
            
            0"id"
            1"clOrdID"
            2"origClOrdID"
            3"orderID"
            4"text"
            5"ordRejRes"
            6"status"
            7"buyorSell"
            8"time"
            9"ordType"
            10"orderQty"
            11"timeInForce"
            12"symbol"
            13"qTradeacc"
            14"price"
            15"stopPx"
            16"execType"
            17"senderSubID"
            18"lastPrice"
            19"lastOrderQty"
            20"seqNum"
            21"sendingTime"
            22"messageDate"
        
        ----------------------------------------------
        */

        // Determine variables for use
        $order_number = array_values($data)[1];
        $side = array_values($data)[7];
        $sender_sub_id = array_values($data)[17];
        $price = array_values($data)[14];
        $quantity = array_values($data)[10];
        $status = array_values($data)[6];
        $jcsd_num = array_values($data)[13];

        // JSE NEW RELEASE AUGUST 29,2020
        $original_order_number = array_values($data)[2];
        $last_order_quantity = array_values($data)[19];
        $last_order_price = array_values($data)[18];



        if ($original_order_number) {
            // this is a cancel request
            //Update DMA Records to acurately mathch the cancellation request new order number : initial order number
            BrokerOrderExecutionReport::updateOrCreate(
                ['clordid' => $order_number],
                ['clordid' => $original_order_number]
            );

            $order_number = $original_order_number;
        } else {
            $order_number = array_values($data)[1];
        }


        // Define the clients jcsd number
        $jcsd = str_replace('JCSD', "", $jcsd_num);
        //Find the broker order linked to this execution report (account number)
        $order = BrokerClientOrder::where('clordid', $order_number)->first();

        // // Define The broker client
        $broker_client = BrokerClient::where('jcsd', $jcsd)->first();
        if ($order) {
            //Trading Account Information
            $trading = BrokerTradingAccount::find((int)$order->trading_account_id);

            //Find the broker settlement account linked to this execution report (account number (senderSubID)
            $settlement_account = BrokerSettlementAccount::find($trading->broker_settlement_account_id);

            if ($order && $broker_client) {
                $original_order = $order;
                $trader = $broker_client;

                if ($original_order->id) {
                    // $order_status = $this->orderStatus($original_order->id);
                    $broker_settlement_account = $settlement_account;

                    $order_value = $quantity * $price; //ER Order Value

                    // Allocated Value of order [Release what was initially allocated per stock]
                    $allocated_value_of_order = $quantity * $original_order['price'];
                    $filled_value = $quantity * $price;

                    //Determine If The Order Is A Buy Or Sell
                    $side = json_decode($order->side, true);
                    if ($side['fix_value'] === '1') {
                        //Only Update account Balances if this is a buy order
                        if (
                            $status === $this->OrderStatus->Expired() ||
                            $status === $this->OrderStatus->_New()
                        ) {
                            // UPDATE ORDER STATUS ONLY
                            BrokerClientOrder::updateOrCreate(

                                ['id' => $original_order->id],
                                ['order_status' => $status]

                            );
                        } else if ($status === $this->OrderStatus->Cancelled()) {
                            BrokerClientOrder::updateOrCreate(

                                ['id' => $original_order->id],
                                ['order_status' => $status, 'remaining' => $original_order['remaining'] - $allocated_value_of_order]

                            );

                            // Update Settlement Account Balances
                            $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                                ['id' => $broker_settlement_account['id']],
                                ['amount_allocated' => $broker_settlement_account['amount_allocated'] - $allocated_value_of_order]
                            );


                            // Update Broker Clients Open Orders
                            $broker_client_account = BrokerClient::updateOrCreate(
                                ['id' => $trader->id],
                                ['open_orders' => $trader['open_orders'] - $allocated_value_of_order]
                            );
                        } else if ($status === $this->OrderStatus->Failed() || $status === $this->OrderStatus->Rejected()) {
                            BrokerClientOrder::updateOrCreate(

                                ['id' => $original_order->id],
                                ['order_status' => $status, 'remaining' => $original_order['remaining'] - $allocated_value_of_order]

                            );

                            // Update Settlement Account Balances
                            $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                                ['id' => $broker_settlement_account['id']],
                                ['amount_allocated' => $broker_settlement_account['amount_allocated'] - $allocated_value_of_order]
                            );


                            // Update Broker Clients Open Orders
                            $broker_client_account = BrokerClient::updateOrCreate(
                                ['id' => $trader->id],
                                ['open_orders' => $trader['open_orders'] - $allocated_value_of_order]
                            );
                        } else if ($status === $this->OrderStatus->Filled()) {

                            $allocated_value_of_order = $last_order_quantity * $original_order['price'];
                            $filled_value = $last_order_quantity * $last_order_price;

                            // UPDATE THE ORDER STATUS 
                            $broker_client_order = BrokerClientOrder::updateOrCreate(
                                ['id' => $original_order->id],
                                ['order_status' => $status, 'remaining' => $original_order['remaining'] - $allocated_value_of_order]

                            );

                            // Update Settlement Account Balances
                            $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                                ['id' => $broker_settlement_account['id']],
                                ['amount_allocated' => $broker_settlement_account['amount_allocated'] - $allocated_value_of_order, 'filled_orders' => $broker_settlement_account['filled_orders'] + $filled_value]
                            );


                            // Update Broker Clients Open Orders
                            $broker_client_account = BrokerClient::updateOrCreate(
                                ['id' => $trader->id],
                                ['open_orders' => $trader['open_orders'] - $allocated_value_of_order, 'filled_orders' => $trader->filled_orders + $filled_value]
                            );
                        } else if ($status === $this->OrderStatus->PartialFilled()) {
                            $allocated_value_of_order = $last_order_quantity * $original_order['price'];
                            $filled_value = $last_order_quantity * $last_order_price;
                            // UPDATE THE ORDER STATUS 
                            $broker_client_order = BrokerClientOrder::updateOrCreate(
                                ['id' => $original_order->id],
                                ['order_status' => $status, 'remaining' => $original_order['remaining'] - $allocated_value_of_order]

                            );

                            // Update Settlement Account Balances
                            $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                                ['id' => $broker_settlement_account['id']],
                                ['amount_allocated' => $broker_settlement_account['amount_allocated'] - $allocated_value_of_order, 'filled_orders' => $broker_settlement_account['filled_orders'] + $filled_value]
                            );


                            // Update Broker Clients Open Orders
                            $broker_client_account = BrokerClient::updateOrCreate(
                                ['id' => $trader->id],
                                ['open_orders' => $trader['open_orders'] - $allocated_value_of_order, 'filled_orders' => $trader->filled_orders + $filled_value]
                            );
                        }
                    } else {
                        // If the order side is a sell
                        // UPDATE ORDER STATUS ONLY
                        BrokerClientOrder::updateOrCreate(

                            ['id' => $original_order->id],
                            ['order_status' => $status]

                        );
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
