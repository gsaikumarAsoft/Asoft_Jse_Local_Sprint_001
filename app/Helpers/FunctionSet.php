<?php


namespace App\Helpers;

use App\BrokerClient;
use App\BrokerClientOrder;
use App\BrokerClientPermission;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\BrokerUser;
use App\BrokerUserPermission;
use App\ForeignBroker;
use App\LocalBroker;
use Request;
use App\LogActivity as LogActivityModel;
use App\Mail\LocalBrokerClient;
use App\Mail\LocalBrokerTrader;
use App\Mail\LocalBrokerUser;
use App\Role;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class FunctionSet
{


    function createBrokerOrder($request, $local_broker_id, $status){
        $mytime = Carbon::now();
        $broker_client_order = new BrokerClientOrder();
        $broker_client_order->local_broker_id  = $local_broker_id;
        $broker_client_order->foreign_broker_id = '1';
        $broker_client_order->handling_instructions = $request->handling_instructions;
        $broker_client_order->order_quantity = $request->quantity;
        $broker_client_order->order_type = $request->order_type;
        $broker_client_order->order_status = $status;
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
        $broker_client_order->market_order_number = $request->market_order_number;
        $broker_client_order->stop_price = $request->stop_price;
        $broker_client_order->expiration_date = $request->expiration_date;
        $broker_client_order->time_in_force = $request->time_in_force;
        $broker_client_order->save();

    }
    function defineLocalBroker($id)
    {
        $b = LocalBroker::find($id)->with('user')->first();
        return $b['user'];
    }
    function getUserRole($id)
    {

        $user = User::with('roles')->find($id);
        return $user;
    }


    function getUser($id)
    {

        // return $id;
        $user = User::select('name')->where('id', $id)->get();
        return $user;
    }

    function getSettlementUserByEmail($email)
    {

        // return $id;
        $user = BrokerSettlementAccount::where('email', $email)->get();
        return $user;
    }

    function getUserAll($id)
    {

        // return $id;
        $user = User::where('id', $id)->first();
        return $user;
    }


    function getLocalBrokerById($id)
    {
        $broker = User::find($id)->first();
        return $broker;
    }
    function getSettlementData($id)
    {
        $settlement = BrokerSettlementAccount::find($id);
        return $settlement;
    }

    function getForeignBrokerById($id)
    {
        $broker = ForeignBroker::find($id);
        $user = User::where('id', $broker->user_id)->get();
        return $user;
    }
    function createBrokerTradingAccount($account_details)
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

    function addPermission($account_id, $permissions, $target)
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

    function createBrokerClient($request)
    {

        
        $local_broker = LocalBroker::with('user')->where('user_id', $request->local_broker_id)->first();
        
        if ($request->id) {
            LogActivity::addToLog('Update Client Details');
            $broker_client = BrokerClient::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email, 'status' => 'Unverified', 'open_orders' => $request->open_orders, 'jcsd' => $request->jcsd, 'account_balance' => $request->account_balance]

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
            // $broker_client->user_id = $broker_trader->id;
            $broker_client->name = request('name');
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
            Mail::to($local_broker->user['email'])->send(new LocalBrokerClient($request));
        }
    }

    public function createBrokerUser($request)
    {

        
        $local_broker = $this->getUserAll($request->local_broker_id);
        // return $local_broker->email;

        if ($request->id) {
            LogActivity::addToLog('Update User Details');
            $broker = User::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email, 'status' => 'Unverified']

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
            $user->hash = $hash;
            $user->save();
            $user->roles()->attach($role_OPRB);
            $request['id'] = $user->id;
            $request['p'] = $pass;

            
            $broker_user = new BrokerUser();
            $broker_user->user_id = $user->id;
            $broker_user->dma_broker_id = $request->local_broker_id;;
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

    function rand_pass($length)
    {

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }
    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
