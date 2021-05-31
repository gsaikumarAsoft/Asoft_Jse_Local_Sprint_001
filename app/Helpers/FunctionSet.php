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
use App\Mail\LocalBrokerUserUpdate;
use App\Role;
use App\User;
use Carbon\Carbon;
use CreateBrokerClientOrderExecutionReports;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
        return config('fixwrapper.base_url') . $path;
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
        Log::debug('CANCELLING | Order: ' . $id . '...');
        $order = BrokerClientOrder::where('clordid', $id)->first();
        Log::debug('PROCESSING | Cancel Order: ' . $id . '...');        
        /*
        $order_ex = BrokerOrderExecutionReport::where('clordid', $id)->where('status', 'Cancel Submitted')->first();
        if (isset($order_ex)) {
            Log::debug('PROCESSING | Cancel Request Already Made: ' . $order_ex  );
            if ($order_ex['status'] === "Cancel Submitted") {
                return response()->json(['isvalid' => false, 'errors' => 'A Cancellation Request for this order has already been submitted']);
            }
        }       
        */
        
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
        $result = curl_exec($ch);

        $info = curl_getinfo($ch);

        if ($info['http_code']!='200') {
            return response()->json(['isvalid' => false, 'errors' => 'API HTTP Status Error: ' + $info['http_code']]);
            Log::debug('ERROR | Order: '+$order->clordid+' Cancel Request - HTTP Status' . $info['http_code'] . '...');                
        }
        
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
        $orig_user_name = auth()->user()->name;

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
        $broker_client_order->created_by = $orig_user_name;
        $broker_client_order->save();

        $side = json_decode($request->side, true);
        $order_value = $request->quantity * $request->price; //New Order Value

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
            'text' => '',
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

        //$log_text = isset($cOTLdata[$postdata]) ? $cOTLdata[$postdata] : 'NULL';        
        Log::debug('PROCESSING | New Order Single: ' . $request->client_order_number . '...');

        $curl_error_codes = array(
            1 => 'CURLE_UNSUPPORTED_PROTOCOL',
            2 => 'CURLE_FAILED_INIT',
            3 => 'CURLE_URL_MALFORMAT',
            4 => 'CURLE_URL_MALFORMAT_USER',
            5 => 'CURLE_COULDNT_RESOLVE_PROXY',
            6 => 'CURLE_COULDNT_RESOLVE_HOST',
            7 => 'CURLE_COULDNT_CONNECT',
            8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
            9 => 'CURLE_REMOTE_ACCESS_DENIED',
            11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
            13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
            14 =>'CURLE_FTP_WEIRD_227_FORMAT',
            15 => 'CURLE_FTP_CANT_GET_HOST',
            17 => 'CURLE_FTP_COULDNT_SET_TYPE',
            18 => 'CURLE_PARTIAL_FILE',
            19 => 'CURLE_FTP_COULDNT_RETR_FILE',
            21 => 'CURLE_QUOTE_ERROR',
            22 => 'CURLE_HTTP_RETURNED_ERROR',
            23 => 'CURLE_WRITE_ERROR',
            25 => 'CURLE_UPLOAD_FAILED',
            26 => 'CURLE_READ_ERROR',
            27 => 'CURLE_OUT_OF_MEMORY',
            28 => 'CURLE_OPERATION_TIMEDOUT',
            30 => 'CURLE_FTP_PORT_FAILED',
            31 => 'CURLE_FTP_COULDNT_USE_REST',
            33 => 'CURLE_RANGE_ERROR',
            34 => 'CURLE_HTTP_POST_ERROR',
            35 => 'CURLE_SSL_CONNECT_ERROR',
            36 => 'CURLE_BAD_DOWNLOAD_RESUME',
            37 => 'CURLE_FILE_COULDNT_READ_FILE',
            38 => 'CURLE_LDAP_CANNOT_BIND',
            39 => 'CURLE_LDAP_SEARCH_FAILED',
            41 => 'CURLE_FUNCTION_NOT_FOUND',
            42 => 'CURLE_ABORTED_BY_CALLBACK',
            43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
            45 => 'CURLE_INTERFACE_FAILED',
            47 => 'CURLE_TOO_MANY_REDIRECTS',
            48 => 'CURLE_UNKNOWN_TELNET_OPTION',
            49 => 'CURLE_TELNET_OPTION_SYNTAX',
            51 => 'CURLE_PEER_FAILED_VERIFICATION',
            52 => 'CURLE_GOT_NOTHING',
            53 => 'CURLE_SSL_ENGINE_NOTFOUND',
            54 => 'CURLE_SSL_ENGINE_SETFAILED',
            55 => 'CURLE_SEND_ERROR',
            56 => 'CURLE_RECV_ERROR',
            58 => 'CURLE_SSL_CERTPROBLEM',
            59 => 'CURLE_SSL_CIPHER',
            60 => 'CURLE_SSL_CACERT',
            61 => 'CURLE_BAD_CONTENT_ENCODING',
            62 => 'CURLE_LDAP_INVALID_URL',
            63 => 'CURLE_FILESIZE_EXCEEDED',
            64 => 'CURLE_USE_SSL_FAILED',
            65 => 'CURLE_SEND_FAIL_REWIND',
            66 => 'CURLE_SSL_ENGINE_INITFAILED',
            67 => 'CURLE_LOGIN_DENIED',
            68 => 'CURLE_TFTP_NOTFOUND',
            69 => 'CURLE_TFTP_PERM',
            70 => 'CURLE_REMOTE_DISK_FULL',
            71 => 'CURLE_TFTP_ILLEGAL',
            72 => 'CURLE_TFTP_UNKNOWNID',
            73 => 'CURLE_REMOTE_FILE_EXISTS',
            74 => 'CURLE_TFTP_NOSUCHUSER',
            75 => 'CURLE_CONV_FAILED',
            76 => 'CURLE_CONV_REQD',
            77 => 'CURLE_SSL_CACERT_BADFILE',
            78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
            79 => 'CURLE_SSH',
            80 => 'CURLE_SSL_SHUTDOWN_FAILED',
            81 => 'CURLE_AGAIN',
            82 => 'CURLE_SSL_CRL_BADFILE',
            83 => 'CURLE_SSL_ISSUER_ERROR',
            84 => 'CURLE_FTP_PRET_FAILED',
            84 => 'CURLE_FTP_PRET_FAILED',
            85 => 'CURLE_RTSP_CSEQ_ERROR',
            86 => 'CURLE_RTSP_SESSION_ERROR',
            87 => 'CURLE_FTP_BAD_FILE_LIST',
            88 => 'CURLE_CHUNK_FAILED');       
        
        $ch = curl_init($url);       
        //curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)  
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds
        // curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        // curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        // curl_setopt($ch, CURLOPT_TIMEOUT, '20L');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
       
        //$result = curl_exec($ch);
        if( ! $result = curl_exec($ch))
        {
            //trigger_error(curl_error($ch));
            $data['text'] = 'Communications Error: API Unreachable!' ;
            $data['status'] = $this->OrderStatus->Failed();
            Log::debug('ORDER: ' . $request->client_order_number . ' Failed | ' . $data['text'] );
            $this->createDMAExecutionReport($data, $broker_client_order);
            // Return funds only if this is a buy order as we deducted funds previously
            $this->releaseFailedOrderFunds(
                $side['fix_value'], 
                $broker_client_order['id'], 
                $this->OrderStatus->Failed(), 
                $broker_client_order['remaining'] - $order_value,
                $settlement->hash,
                $settlement->amount_allocated - $order_value,
                $client_id,
                $client->open_orders - $order_value,                                            
                $order_value,
                $request->client_order_number);
                curl_close($ch);
            return response()->json(['isvalid' => false, 'errors' =>$data['text'] .' for Order#: ' . $request->client_order_number]);
        }     

        Log::debug('ORDER: ' . $request->client_order_number . ' | CURL_EXEC Result: ' . $result );
                    
                // Check if curl INIT FAILED
                if ($result = False) {
                    $data['text'] = 'Communications Error: API Unreachable!' ;
                    $data['status'] = $this->OrderStatus->Failed();
                    Log::debug('ORDER: ' . $request->client_order_number . ' Failed | ' . $data['text'] );

                    $this->createDMAExecutionReport($data, $broker_client_order);
                    // Return funds only if this is a buy order as we deducted funds previously
                    $this->releaseFailedOrderFunds(
                        $side['fix_value'], 
                        $broker_client_order['id'], 
                        $this->OrderStatus->Failed(), 
                        $broker_client_order['remaining'] - $order_value,
                        $settlement->hash,
                        $settlement->amount_allocated - $order_value,
                        $client_id,
                        $client->open_orders - $order_value,                                            
                        $order_value,
                        $request->client_order_number);
                    curl_close($ch);
                    return response()->json(['isvalid' => false, 'errors' =>$data['text'] .' for Order#: ' . $request->client_order_number]);
                } 

        //$http_code = 0;
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);       
        curl_close($ch);

        // Check HTTP status code
        Log::debug('ORDER: ' . $request->client_order_number . '| API HTTP Status Code: '. $http_code);
        
        switch ($http_code) {
            case 200:  # OK
            // ================================================================================================
            $data['text'] = 'Order submitted for routing (Status Pending).';
            $data['status'] = 'Submitted';
            $this->createDMAExecutionReport($data, $broker_client_order);

            $fix_status_result = '';

            //Check Response Content
            Log::debug('ORDER: ' . $request->client_order_number . '| API Response: '. $fix_status_result . '| Response: ' . $result );
            if ( str_contains($fix_status_result, 'Session could not be established')) {           
                $data['text'] = 'Error connecting to '. $trading->target_comp_id .'['. $fix_status_result.']';
                $data['status'] = $this->OrderStatus->Failed();
                $this->createDMAExecutionReport($data, $broker_client_order);
                if ($side['fix_value'] === '1') {
                    // Return funds only if this is a buy order as we deducted funds previously
                    $this->releaseFailedOrderFunds(
                        $side['fix_value'], 
                        $broker_client_order['id'], 
                        $this->OrderStatus->Failed(), 
                        $broker_client_order['remaining'] - $order_value,
                        $settlement->hash,
                        $settlement->amount_allocated - $order_value,
                        $client_id,
                        $client->open_orders - $order_value,                                            
                        $order_value,
                        $request->client_order_number);
                }
                return response()->json(['isvalid' => false, 'errors' => 'Error connecting to ' . $trading->target_comp_id . 'for Order#: ' . $request->client_order_number]);
                // ================================================================================================
            }
            return response()->json(['isvalid' => true, 'errors' => 'Order '. $request->client_order_number. ' submitted to '. $trading->target_comp_id .'!' ]);
            break;
            // ================================================================================================
        
        default:
            //'Unexpected HTTP code: ', $http_code, "\n";
            // ================================================================================================
            $data['text'] = 'Order Submission Failed - Communication Error: [HTTP:'.$http_code.']';
            $data['status'] = $this->OrderStatus->Failed();
            $this->createDMAExecutionReport($data, $broker_client_order);
            return response()->json(['isvalid' => false, 'errors' => $data['text'] ]);            
            break;
            // ================================================================================================
        }

/*
        $fix_status = json_decode($result, true);
        $fix_status_result = $fix_status['result'];
        $log_text = isset($cOTLdata[$result]) ? $cOTLdata[$result] : 'NULL';        
        
        Log::debug('ORDER: ' . $request->client_order_number . '| API Response: '  .  $result );
        $log_text = isset($cOTLdata[$result]) ? $cOTLdata[$result] : 'NULL';        

        $result_len = isset($cOTLdata[$result]) ? count($cOTLdata[$result]) : 0;

        if ($result_len >0) {
            //Default the $fix_status_result variable to the HTTP Response code of the FIX Wrapper request
            $fix_status_result = 'HTTP Response(' . $http_code . ')';   
            
            ///Check HTTP Request Status
            if ($http_code!=200) {
                //$data['text'] = 'Order Submission Failed!';
                $data['text'] = 'Communication Failure (HTTP ' . $http_code . ')';
                $data['status'] = $this->OrderStatus->Failed();
            } else {
                $data['text'] = 'Order Submitted';
                $data['status'] = 'Submitted';
                $fix_status_result = 'HTTP OK (' . $http_code . ')';
            }
            
        } else {
            $fix_status_result = 'Unexpected Server Response!';
            $data['text'] = 'Order Submission Status Pending!';
            //$data['status'] = $this->OrderStatus->Failed();
        }

        //Fetch API Request Response
        if ($result_len > 0) {
            //Update the $fix_status_result variable with the payload returned from FIX Wrapper
            $fix_status_result = $fix_status['result'];
        }
        
        //Check API Request Response
        if ( str_contains($fix_status_result, 'Session could not be established')) {            
            $data['text'] = 'Order Submission Failed!';
            $data['status'] = $this->OrderStatus->Failed();
        }

        $order_value = $request->quantity * $request->price;

        $settlement_allocated = $settlement->amount_allocated - $order_value;
        $client_open_orders = $client->open_orders - $order_value;
        $side = json_decode($request->side, true);
        
        
        if ( $data['status'] = "Failed") {
                $this->LogActivity->addToLog('Order Failed:' . $fix_status_result . '-' . $request->client_order_number);
                $data['text'] = 'Order Submission Failed: ' . $fix_status_result . '-' . $request->client_order_number;
                $data['status'] = 'Session Failed';

                if ($side['fix_value'] === '1') {
                    // Return funds only if this is a buy order as we deducted funds previously
                    $this->releaseFailedOrderFunds(
                        $side['fix_value'], 
                        $broker_client_order['id'], 
                        $this->OrderStatus->Failed(), 
                        $broker_client_order['remaining'] - $order_value,
                        $settlement->hash,
                        $settlement->amount_allocated - $order_value,
                        $client_id,
                        $client->open_orders - $order_value,                                            
                        $order_value,
                        $request->client_order_number);
                }
                $this->createDMAExecutionReport($data, $broker_client_order);
                return response()->json(['isvalid' => false, 'errors' => 'ORDER SUBMISSION FAILED: ' . $fix_status_result . '-' . $request->client_order_number]);
                
        } else {
            $this->LogActivity->addToLog('Order Successfully Submitted:' . $fix_status_result . '-' . $request->client_order_number);
            $this->createDMAExecutionReport($data, $broker_client_order);                
            return response()->json(['isvalid' => true, 'errors' => 'New Order Single Sent!']);
        }
 */
    }

    public function releaseFailedOrderFunds($side_num, 
                                            $broker_client_order_id, 
                                            $new_order_status, 
                                            $new_remaining,
                                            $settlement_hash,
                                            $settlement_allocated,
                                            $client_id,
                                            $new_client_allocated_amount,
                                            $order_value,
                                            $client_order_number
                                        ) {
        // Return funds only if this is a buy order as we deducted funds previously
        if ($side_num === '1') {
            BrokerClientOrder::updateOrCreate(
                ['id' => $broker_client_order_id],
                ['order_status' => $new_order_status, 'remaining' => $new_remaining ]
            );

            BrokerSettlementAccount::updateOrCreate(
                ['hash' => $settlement_hash],
                ['amount_allocated' => $settlement_allocated]
            );

            // Update Broker Clients Open Orders
            BrokerClient::updateOrCreate(
                ['id' => $client_id],
                ['open_orders' => $new_client_allocated_amount]
            );
            $this->LogActivity->addToLog('Failed Order Funds Released:' . $order_value . '-' . $client_order_number);
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
    public function manualLogExecution($report)
    {
        $manual_order_status = $report["status"];
        $er_date = substr($report["messageDate"],0, 10);
        $er_date1 = $er_date . "00:00:00";
        $er_date2 = $er_date . "23:59:59";
        //Only Expired Orders may be Manually Cancelled!
        if ($manual_order_status == 4) {
            $is_order_expired = DB::table('expiring_buy_orders')
                ->where('client_order_id', $report["clOrdID"])
                ->where('expiration_date', '<=', $er_date)
                ->count();
            if ($is_order_expired >=1) {
                Log::debug('FunctionSet@manualLogExecution | Cancelling Order#: ' .$report["clOrdID"]);     
                $this->logExecution($report);
            } else {
                Log::warning('FunctionSet@manualLogExecution | Cannot Cancel Order#: ' .$report["clOrdID"] );
            }
        }

        //Only Expired Orders may be Manually Filled!
        if (($manual_order_status == 1)| ($manual_order_status == 2)) {
            $is_order_expired = DB::table('expiring_buy_orders')
                ->where('client_order_id', $report["clOrdID"])
                ->where('expiration_date', '<', $er_date)
                ->count();
            if ($is_order_expired >=1) {
                Log::debug('FunctionSet@manualLogExecution | Filling Expired Order#: ' .$report["clOrdID"]);     
                $this->logExecution($report);
            } else {
                Log::warning('FunctionSet@manualLogExecution | Cannot Fill Unexpired Order#: ' .$report["clOrdID"] );
            }
        }         
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
        //Log::debug('FunctionSet@logExecution | ER: '. json_encode($report) ); 
        
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

            $records = BrokerOrderExecutionReport::where('senderSubID', array_values($report)[17])
                ->where('clOrdID', array_values($report)[1])
                ->where('seqNum', array_values($report)[20])
                ->where('sendingTime', $time)
                ->count();
            //$record = BrokerOrderExecutionReport::where('senderSubID', array_values($report)[17])->first();    
            if ($records > 0) {
                //Log::debug('FunctionSet@logExecution | Checkpoint : Execution Report Already Processed...' );
                //IF THE RECORD ALREADY EXISTS DO NOTHING TO IT but update the marker order number
                DB::table('broker_client_orders')->where('clordid', $report['clOrdID'])->update(['market_order_number' => $report['orderID']]);
                
            } else {
                //Log::debug('FunctionSet@logExecution | Checkpoint : Processing New Execution Report...' );
                
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

        $log_text = isset($cOTLdata[$postdata]) ? $cOTLdata[$postdata] : 'NULL';        
        Log::debug('FIX API Request | Message Download: ' . $sender_sub_id . ' | ' . $log_text);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
        
        $result = curl_exec($ch);
        curl_close($ch);

        $log_text = isset($cOTLdata[$result]) ? $cOTLdata[$result] : 'NULL';        
        Log::debug('FIX API Response | Message Download: ' . $sender_sub_id . ' | ' . $log_text);


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

        //[DEPRECATED 2021-05-27] Determine variables for use 
        //$order_number = array_values($data)[1];
        //$side = array_values($data)[7];
        //$sender_sub_id = array_values($data)[17];
        //$price = array_values($data)[14];
        //$quantity = array_values($data)[10];
        //$status = array_values($data)[6];
        //$jcsd_num = array_values($data)[13];
        $jcsd_num = $data["qTradeacc"];
        $status = $data["status"];
        $quantity = $data["orderQty"];
        $price = $data["price"];
        $sender_sub_id = $data["senderSubID"];
        $order_number = $data["clOrdID"];
        $side = $data["buyorSell"];
        
        //[DEPRECATED 2021-05-27] // JSE NEW RELEASE AUGUST 29,2020.
        //$original_order_number = array_values($data)[2];        
        //$last_order_quantity = array_values($data)[19];
        //$last_order_price = array_values($data)[18];
        $original_order_number = $data["origClOrdID"];
        if (($status == "1") || ($status == "2") ) {
            $last_order_quantity = $data["lastOrderQty"];
            $last_order_price = $data["lastPrice"];
        }
        
        //[DEPRECATED 2021-05-27] //if ($original_order_number) {
            //CANCEL REQUEST - Identify (either BUY/SELL)
            if ($status == "4") {            
            //Update DMA Records to acurately mathch the cancellation request new order number : initial order number
            BrokerOrderExecutionReport::updateOrCreate(
                ['clordid' => $order_number],
                ['clordid' => $original_order_number]
            );
            $order_number = $original_order_number;
        } else {
            Log::debug('FunctionSet@clientSettlementBalanceUpdate | Checkpoint: ' . $order_number . ' not a CANCEL REQUEST...');
        }
        // Define the clients jcsd number
        $jcsd = str_replace('JCSD', "", $jcsd_num);
        //Find the broker order linked to this execution report (account number)
        $order = BrokerClientOrder::where('clordid', $order_number)->first();
        // // Define The broker client
        $broker_client = BrokerClient::where('jcsd', $jcsd)->first();
        if ($order) {
            Log::debug('FunctionSet@clientSettlementBalanceUpdate | Order found!' );
            //Trading Account Information
            $trading = BrokerTradingAccount::find((int)$order->trading_account_id);
            //Find the broker settlement account linked to this execution report (account number (senderSubID)
            $settlement_account = BrokerSettlementAccount::find($trading->broker_settlement_account_id);

            if ($order && $broker_client) {
                $original_order = $order;
                $trader = $broker_client;

                if ($original_order->id) {
                    $broker_settlement_account = $settlement_account;
                   
                    // Allocated Value of order [Release what was initially allocated per stock]
                    //[DEPRECATED 2021-05-27] $allocated_value_of_order = $quantity * $original_order['price'];
                    $allocated_value_of_order = $original_order['remaining'];
                    if (($status == "1") || ($status == "2") ) {
                        //[DEPRECATED 2021-05-27] //$filled_value = $quantity * $price;
                        $filled_value = $last_order_quantity * $last_order_price;
                    }
                    //SIDE: Determine If The Order Is A Buy Or Sell
                    $side = json_decode($order->side, true);
                    
                    //SIDE: BUY Order
                    if ($side['fix_value'] === '1') {
                        //STATUS UPDATE ONLY - No balance updates required & Order remains "Open"
                        if (
                                $status != $this->OrderStatus->Expired() && 
                                $status != $this->OrderStatus->Failed() && 
                                $status != $this->OrderStatus->Cancelled() && 
                                $status != $this->OrderStatus->Rejected() && 
                                $status != $this->OrderStatus->Filled() && 
                                $status != $this->OrderStatus->PartialFilled()
                            //$status == $this->OrderStatus->Expired() ||
                            //$status == $this->OrderStatus->_New()
                        ) {
                            //STATUS UPDATE ONLY - Update the Order
                            BrokerClientOrder::updateOrCreate(
                                ['id' => $original_order->id],
                                ['order_status' => $status]
                            );
                            return;
                        }                                                
                        // EXPIRED/FAILED/CANCELLED/REJECTED - Process the ER
                        if (
                                $status == $this->OrderStatus->Expired() ||
                                $status == $this->OrderStatus->Failed() || 
                                $status == $this->OrderStatus->Cancelled() || 
                                $status == $this->OrderStatus->Rejected()
                            ) {
                                // EXPIRED/FAILED/CANCELLED/REJECTED - Calculate new values                           
                                $new_order_remaining = 0;
                                $new_settlement_amount_allocated = $broker_settlement_account['amount_allocated'] - $original_order['remaining'];
                                $new_client_open_orders = $trader['open_orders'] - $original_order['remaining'];
                                
                            // EXPIRED/FAILED/CANCELLED/REJECTED - Update the Order 
                            BrokerClientOrder::updateOrCreate(
                                ['id' => $original_order->id],
                                [   'order_status' => $status, 
                                    'remaining' => $new_order_remaining ]
                            );
                            // EXPIRED/FAILED/CANCELLED/REJECTED - Update the Settlement Account 
                            $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                                ['id' => $broker_settlement_account['id']],
                                [   'amount_allocated' => $new_settlement_amount_allocated  ]
                            );
                            // EXPIRED/FAILED/CANCELLED/REJECTED - Update the Client Account
                            $broker_client_account = BrokerClient::updateOrCreate(
                                ['id' => $trader->id],
                                [   'open_orders' => $new_client_open_orders    ]
                            );
                        } 
                        // FILLED/PARTIALLY FILLED - Process the ER
                        if (
                                $status == $this->OrderStatus->Filled() || 
                                $status == $this->OrderStatus->PartialFilled()
                            ) {
                            // FILLED/PARTIALLY FILLED - Calculate new values                           
                            $filled_value = $last_order_quantity * $last_order_price;
                            if ($status == $this->OrderStatus->Filled()) { 
                                $new_order_remaining = 0;
                            } else {
                                $new_order_remaining = $original_order['remaining'] - $filled_value;
                            }
                            $new_order_value_filled = $original_order['value_filled'] + $filled_value;
                            $new_order_amount_filled = $original_order['amount_filled'] + $last_order_quantity;
                            $new_settlement_amount_allocated = $broker_settlement_account['amount_allocated'] - $original_order['remaining'];
                            $new_settlement_filled_orders = $broker_settlement_account['filled_orders'] + $filled_value;
                            $new_client_open_orders = $trader['open_orders'] - $original_order['remaining'];
                            $new_client_filled_orders = $trader->filled_orders + $filled_value;
                            // FILLED/PARTIALLY FILLED - Update the Order
                            $broker_client_order = BrokerClientOrder::updateOrCreate(
                                ['id' => $original_order->id],
                                [   'order_status' => $status, 
                                    'amount_filled' => $new_order_amount_filled,
                                    'value_filled' => $new_order_value_filled,
                                    'remaining' => $new_order_remaining ]
                            );
                            // FILLED/PARTIALLY FILLED - Update the Settlement Account
                            $broker_settlement = BrokerSettlementAccount::updateOrCreate(
                                ['id' => $broker_settlement_account['id']],
                                [   'amount_allocated' => $new_settlement_amount_allocated, 
                                    'filled_orders' => $new_settlement_filled_orders    ]                                    
                            );
                            // FILLED/PARTIALLY FILLED - Update the Client Account
                            $broker_client_account = BrokerClient::updateOrCreate(
                                ['id' => $trader->id],
                                [   'open_orders' => $new_client_open_orders,
                                    'filled_orders' => $new_client_filled_orders    ]
                            );
                        }                        
                    } else {
                    //SIDE: SELL Order
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
        if ($request->id) {
            $role_OPRB = Role::where('name', 'OPRB')->first();
            $pass = $this->rand_pass(8);
            $hash = $this->generateRandomString(20);            
            $user = User::find($request->id);            
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($pass);
            $user->status = 'Unverified';
            $user->status = 'Unverified';
            $user->local_broker_id = $broker_owner['id'];
            $user->hash = $hash;
            $user->save();
            $user->roles()->attach($role_OPRB);            
            //needed for the valudation link in the email
            $request['id'] = $user->id;
            $request['hash'] = $hash;
            $request['p'] = $pass;
            ////////////////////////////////
            LogActivity::addToLog('Updated User Details');            
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
                $broker->givePermissionTo($request->permissions[$i]);
            }
            //Notify Local Broker Admin
            Mail::to($local_broker->email)->send(new LocalBrokerUserUpdate($request));


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
                $user->givePermissionTo($request->permissions[$i]);
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
    
    public function statusFilter($data)
    {
        if ($data === "0") {
          return "NEW";
        } else if ($data === "-1") {
          return "PENDING";
        } else if ($data === "1") {
          return "PARTIALLY FILLED";
        } else if ($data === "2") {
          return "FILLED";
        } else if ($data === "4") {
          return "CANCELLED";
        } else if ($data === "5") {
          return "REPLACED";
        } else if ($data === "C") {
          return "EXPIRED";
        } else if ($data === "Z") {
          return "PRIVATE";
        } else if ($data === "U") {
          return "UNPLACED";
        } else if ($data === "x") {
          return "INACTIVE";
        } else if ($data === "8") {
          return "REJECTED";
        } else if ($data === "Submitted") {
          return "SUBMITTED";
        } else if ($data === "Failed") {
          return "FAILED";
        } else if ($data === "Cancel Submitted") {
          return "CANCEL SUBMITTED";
        } else {
          return "["+$data+"]";
        }
    }
}
