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
use App\Mail\LocalBrokerClient;
use App\Mail\LocalBrokerUser;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use OrderStatus;

class FunctionSet
{

    public function __construct()
    {

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

    public function IsOrderOpened($orderStatus)
    {
        return ($orderStatus != OrderStatus::Expired &&
            $orderStatus != OrderStatus::Cancelled &&
            $orderStatus != OrderStatus::Rejected &&
            $orderStatus != OrderStatus::Failed);
    }

    public function cancelOrder($order)
    {
        $mytime = Carbon::now();
        $cancel_cordid = "ORD" . $mytime->format('YmdH') . "N" . rand(100, 1000); //Create a New cancel order id

        //Trading Account Information
        $trading = BrokerTradingAccount::with('settlement_account')->find($order->trading_account_id)->first();

        //Settlement Account Information
        $settlement = BrokerSettlementAccount::find($trading->broker_settlement_account_id)->first();
        $url = $this->fix_wrapper_url("api/OrderManagement/OrderCancelRequest");
        $data = array(
            'BeginString' => 'FIX.4.2',
            'TargetCompID' => $trading->target_comp_id,
            'SenderCompID' => $trading->sender_comp_id,
            'SenderSubID' => $trading->trading_account_number,
            'Host' => $trading->socket,
            'OrderID' => $cancel_cordid,
            "OrigClOrdID" => $order->clordid,
            "OrderQty" => $order->quantity,
            'BuyorSell' => $this->jsonStrip(json_decode($order->side, true), 'fix_value'),
            "ClientID" => $trading->trading_account_number,
            'Port' => (int) $trading->port,
            'Symbol' => $this->jsonStrip(json_decode($order->symbol, true), 'text'),
            'ClientID' => $trading->trading_account_number,
            'AccountType' => 'CL',
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
        // return $result;
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
            // =======================================================================================
            // "TargetCompID" => "CIBC_TEST",
            // "SenderCompID" => "JSE_TST2",
            // // "SenderSubID" => "BARITA",
            // "Host" => "20.156.185.101",
            // "Port" => 6544,
            // ========================================================================================
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
            $data['stopPx'] = (int) $request->stop_price;
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
        $data['text'] = 'Submitted';
        $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
        // ================================================================================================

        $fix_status = json_decode($result, true);
        // return $fix_status;
        switch ($fix_status['result']) {
            case "Session could not be established with CIBC. Order number {0}":
                $this->LogActivity->addToLog('Order Failed:' . $fix_status['result'] . '-' . $request->client_order_number);
                $data['text'] = 'Failed: ' . $fix_status['result'] . '-' . $request->client_order_number;
                $order = DB::table('broker_client_orders')
                    ->where('id', $broker_client_order->id)
                    ->update(['order_status' => 'Failed']);
                $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $fix_status['result'] . '-' . $request->client_order_number]);

                break;
            case "Please Check the endpoint /MessageDownload/Download for message queue":
                // If the order is successfull create a log
                $this->LogActivity->addToLog('Order Successfull' . '-' . $request->client_order_number);
                $this->executionBalanceUpdate($sender_sub_id);
                return response()->json(['isvalid' => true, 'errors' => 'SENT NewOrderSingle() request to the RESTful API!']);
                break;
            default:
                // If the response fails create a record in the audit log and in the execution reports as well
                $data['text'] = "Order Failed: " . $fix_status['result'];
                $this->LogActivity->addToLog('Order Failed For: ' . $request->client_order_number . '. Message: ' . $data['text']);
                $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
                return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $data['text']]);
                break;
        }

        // return $fix_status['result'];
        // if ($fix_status['status'] === 400) {
        //     // If the response fails create a record in the audit log and in the execution reports as well
        //     $data['text'] = "Order Failed: " . $fix_status['status'] . '.  ' . $fix_status['title'] . ' TraceID = [' . $fix_status['traceId'] . ']';
        //     $this->LogActivity->addToLog('Order Failed For: ' . $request->client_order_number . '. Message: ' . $data['text']);
        //     $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
        //     return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $data['text']]);
        // }

        // if ($fix_status['result'] === "Please Check the endpoint /MessageDownload/Download for message queue") {
        //     // If the order is successfull create a log
        //     $this->LogActivity->addToLog('Order Successfull');
        //     $this->executionBalanceUpdate($sender_sub_id);
        //     return response()->json(['isvalid' => true, 'errors' => 'SEND NewOrderSingle() request to the RESTful API!']);
        // } else {
        //     $this->LogActivity->addToLog('Order Failed:' . $fix_status['result']);
        //     $data['text'] = 'Failed';
        //     $order = DB::table('broker_client_orders')
        //         ->where('id', $broker_client_order->id)
        //         ->update(['order_status' => 'Failed']);
        //     $this->logExecution(['executionReports' => [$data]]); //Create a record in the execution report
        //     return response()->json(['isvalid' => false, 'errors' => 'ORDER BLOCKED: ' . $fix_status['result']]);
        // }
    }
    public function logExecution($request)
    {
        $execution_report = $request['executionReports'];
        // return $execution_report;
        // BrokerOrderExecutionReport::truncate();
        foreach ($execution_report as $report) {
            $clients[] = $report;
            // return $report['OrderID'];
            // $broker_order_execution_report = BrokerOrderExecutionReport::updateOrCreate(
            // ['clOrdID' => $report['clOrdID'] ],
            // ['orderID' => $report['orderID'], 'text' => $report['text'], 'ordRejRes' => $report['ordRejRes'], 'status' => $report['status'],'buyorSell' => $report['buyorSell'],'securitySubType' => 0,'time' => $report['time'],'ordType' => $report['ordType'],'orderQty' => $report['orderQty'], 'timeInForce' => $report['timeInForce'], 'symbol' => $report['symbol'], 'qTradeacc' => $report['qTradeacc'], 'price' => $report['price'], 'stopPx' => $report['stopPx'], 'execType' => $report['execType'], 'senderSubID' => $report['senderSubID'], 'seqNum' => $report['seqNum'], 'sendingTime' => $report['sendingTime'], 'messageDate' => $report['messageDate'] ]
            // );
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
            $broker_order_execution_report->stopPx = $report['stopPx'] ?? $report['stopPx'];
            $broker_order_execution_report->execType = $report['execType'] ?? 0;
            $broker_order_execution_report->senderSubID = $report['senderSubID'] ?? $report['SenderSubID'];
            $broker_order_execution_report->seqNum = $report['seqNum'] ?? 0;
            $broker_order_execution_report->sendingTime = $report['sendingTime'] ?? 0;
            $broker_order_execution_report->messageDate = $report['messageDate'] ?? 0;
            $broker_order_execution_report->save();
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

    function LocalBrokerPick($id)
    {
        $local_broker = LocalBroker::find($id);
        $broker = User::find($local_broker->user_id);
        return $broker;
    }
    function getLocalBrokerById($id)
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

            // For future Sprint
            // $broker_trader = new User();

            // $broker_trader->local_broker_id = $request->local_broker_id;
            // $broker_trader->name = $request->name;
            // $broker_trader->email = $request->email;
            // $broker_trader->password = bcrypt('password');
            // $broker_trader->status = 'Un-Verified';
            // $broker_trader->save();
            // $request['id'] = $broker_trader->id;
            // $broker_trader->roles()->attach($role_TRDB);
            // ========================================

            //Create Broker Client

            // return $local_broker['id'];
            // $role_TRDB = Role::where('name', 'TRDB')->first();
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

        // $broker_owner = LocalBroker::where('user_id', $local_broker->id)->first();
        // return $request['local_broker_id'];
        $broker_user = $local_broker['user'];

        if ($request->id) {
            LogActivity::addToLog('Update Client Details');
            $b = BrokerClient::find($request->id);
            $b->update(
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'status' => 'Unverified',
                    'open_orders' => $request->open_orders,
                    'jcsd' => $request->jcsd,
                    'account_balance' => $request->account_balance,
                ]

            );
        } else {
            //Notify Local Broker that
            // For future Sprint
            // $broker_trader = new User();

            // $broker_trader->local_broker_id = $request->local_broker_id;
            // $broker_trader->name = $request->name;
            // $broker_trader->email = $request->email;
            // $broker_trader->password = bcrypt('password');
            // $broker_trader->status = 'Un-Verified';
            // $broker_trader->save();
            // $request['id'] = $broker_trader->id;
            // $broker_trader->roles()->attach($role_TRDB);
            // ========================================

            //Create Broker Client

            // return $local_broker['id'];
            // $role_TRDB = Role::where('name', 'TRDB')->first();
            $broker_client = new BrokerClient;
            $broker_client->local_broker_id = $local_broker->id;
            $broker_client->name = $request->name;
            $broker_client->email = $request->email;
            $broker_client->orders_limit = $request->account_balance;
            $broker_client->account_balance = $request->account_balance;
            $broker_client->open_orders = $request->open_orders;
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

    public function orderStatus($id)
    {
        // check current broker client order status
        $order_status = BrokerClientOrder::select('order_status')->where('id', $id)->first();
        return $order_status;
    }

    public function executionBalanceUpdate($sender_sub_id)
    {
        // Call fix and return excutiun report for the required SensderSubID
        //$url = "http://35.155.69.248:8020/api/messagedownload/download";
        $url = $this->fix_wrapper_url("api/messagedownload/download");
        $data = array(
            'BeginString' => 'FIX.4.2',
            "SenderSubID" => $sender_sub_id,
            "seqNum" => 0,
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
        $total_reports = count($account);

        return $request;
        //Store Execution reports for above sender_Sub_id to database before updating account balances
        $this->logExecution($request);

        //Find the very last exucution sequence number for this particular broker
        $seq_last = DB::table('broker_client_order_execution_reports')->orderBy('id', 'desc')->limit(1)->get();
        // Check latest sequence number coming from the fix
        //  $incomingSeq =  $account[$total_reports]['seqNum'];

        //  if($seq_last){
        //      return $seq_last[0]->seqNum.' = '.$incomingSeq;
        //  }

        // iterate through all reports and update accounts as required
        foreach ($account as $key => $value) {
            $order_number = $account[$key]['clOrdID'];
            $sender_sub_id = $account[$key]['senderSubID'];
            $price = $account[$key]['price'];
            $quantity = $account[$key]['orderQty'];
            $order_status = $account[$key]['status'];
            // return $order_number;
            $jcsd = str_replace('JCSD', "", $account[$key]['qTradeacc']);
            // Define The broker client
            // $broker_client = BrokerClientOrder::where('client_order_number', $order_number)->first();
            $broker_client = BrokerClient::where('jcsd', $jcsd)->first();

            //Find the broker order linked to this execution report (account number)
            $order = BrokerClientOrder::where('clordid', $order_number)->first();

            //Find the broker settlement account linked to this execution report (account number (senderSubID)
            $settlement_account = DB::table('broker_trading_accounts')->where('trading_account_number', $sender_sub_id)
                ->select('broker_trading_accounts.broker_settlement_account_id as trading_id', 'broker_trading_accounts.trading_account_number', 'broker_settlement_accounts.*')
                ->join('broker_settlement_accounts', 'broker_trading_accounts.broker_settlement_account_id', 'broker_settlement_accounts.id')
                ->get();

            $array = json_decode(json_encode($settlement_account), true);


            if ($order && $broker_client) {
                // return $order;
                $od = $order;
                $bc = $broker_client;
                return $bc;
                if ($od->id) {
                    $o = $this->orderStatus($od->id);
                    // Define the open order amount
                    $op_or = $bc->open_orders - ($quantity * $price);
                    $fil_or = $bc->filled_orders + ($quantity * $price);
                    // if ($array) {
                    $sa = $array[0];
                    $settlement_allocated = $sa['amount_allocated'] - ($quantity * $price);
                    $settlement_fil_ord = $bc->filled_orders + ($quantity * $price);
                    //If offer is (Rejected, Cancelled, Expired)
                    // return $status;
                    if ($status === "C" || $status === "4" || $status === "8") {

                        // Check if the order is open
                        if ($o->order_status != "C" &&  $o->order_status != "4" &&  $o->order_status != "8" &&  $o->order_status != "2") {

                            // ===========================================================
                            // Set Status To $account[$key]['status]
                            // DB::table('broker_client_orders')
                            //     ->where('id', $od->id)
                            //     ->update(['order_status' => $status]);
                            BrokerClientOrder::updateOrCreate(
                                ['id' => $od->id],
                                ['order_status' => $status]

                            );
                        }
                    } else if ($status === "1") {

                        //If the order was previously (Rejected, Cancelled, Expired Or Previously Filled)
                        if ($o->order_status != "C" &&  $o->order_status != "4" &&  $o->order_status != "8" && $o->order_status != "2") {
                            //Update Broker Client Order Status

                            BrokerClientOrder::updateOrCreate(
                                ['id' => $od->id],
                                ['order_status' => 1]

                            );
                            // DB::table('broker_client_orders')
                            //     ->where('id', $od->id)
                            //     ->update(['order_status' => 1]);
                            BrokerClient::updateOrCreate(
                                ['id' => $bc->id],
                                ['open_orders' => $op_or, 'filled_orders' => $fil_or]
                            );
                            // DB::table('broker_clients')
                            //     ->where('id', $bc->id)
                            //     ->update(['open_orders' => $op_or], ['filled_orders' => $fil_or]);
                        }
                    } else {

                        //If the order was previously (Rejected, Cancelled, Expired Or Previously Filled)
                        if ($o->order_status != "C" &&  $o->order_status != "4" &&  $o->order_status != "8" && $o->order_status != "2") {
                            //The order has been filled
                            // Update Database with required value
                            // DB::table('broker_clients')
                            //     ->where('id', $bc->id)
                            //     ->update(['open_orders' => $op_or], ['filled_orders' => $fil_or]);

                            BrokerClient::updateOrCreate(
                                ['id' => $bc->id],
                                ['open_orders' => $op_or, 'filled_orders' => $fil_or]
                            );

                            //Update Broker Settlement account once the order is filled
                            // DB::table('broker_settlement_accounts')
                            //     ->where('id', $sa['id'])
                            //     ->update(['amount_allocated' => $settlement_allocated], ['filled_orders', $settlement_fil_ord]);
                            BrokerSettlementAccount::updateOrCreate(
                                ['id' => $sa['id']],
                                ['amount_allocated' => $settlement_allocated,  'filled_orders', $settlement_fil_ord]
                            );
                            // DB::table('broker_client_orders')
                            //     ->where('id', $od->id)
                            //     ->update(['order_status' => $status]);
                            // ->update(['order_status' => 2]);
                        }
                    }
                    // }
                }
            }
        }
    }
}
