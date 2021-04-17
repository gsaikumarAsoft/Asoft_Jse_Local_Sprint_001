<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\ForeignBroker;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Jobs\ExecutionBalanceUpdate;
use App\LocalBroker;
use App\LogActivity as AppLogActivity;
use App\Mail\BrokerDetailsUpdate;
use App\Mail\ForeignBrokerRemoval;
use App\Mail\LocalBroker as MailLocalBroker;
use App\Mail\LocalBrokerDetailsUpdate;
use App\Mail\NewForeignBroker;
use App\User;
use App\Mail\SettlementAccountConfirmation;
use App\Mail\SettlementAccountUpdated;
use App\Permission;
use App\Role;
use App\SettlementAccount;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ApplicationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('auth');
        $this->LogActivity = new LogActivity;
        $this->HelperClass = new FunctionSet;
    }

    function audit()
    {

        $data = AppLogActivity::with('user')->get();
        return $data;
    }

    function fetchbrokermessages()
    {
        DB::table('users') 
        ->join('local_brokers', 'users.id', '=', 'local_brokers.user_id') 
        ->select('users.id', 'users.name') 
        ->where('status', '=', 'Verified') 
        ->orderBy('id') 
        ->chunk(100, function ($broker_users) { 
            foreach ($broker_users as $broker_user) {
                $executionBalanceUpdate = new ExecutionBalanceUpdate($broker_user->name); 
                $executionBalanceUpdate ; 
                //dispatch($executionBalanceUpdate); 
                //$fix_url_helper = new FunctionSet(); 
                //$url = $fix_url_helper. fix_wrapper_url("api/messagedownload/download");

                $url = config('fixwrapper.base_url') . "api/messagedownload/download";
                Log::debug('FETCHING BROKER MESSAGES | Local Broker: ' . $broker_user->name .'| URL: '. $url);

                //$url = config('fix.api.url') . "api/messagedownload/download";
                $data = array(
                    'BeginString' => 'FIX.4.2',
                    "SenderSubID" => $broker_user->name,
                    "seqNum" => 0,
                    'StartTime' => date('Y-m-d', time() -5 * 60 * 60) . " 00:00:00.000",
                    'EndTime' => date('Y-m-d', time() -5 * 60 * 60) . " 23:59:59.000",
                );
                $postdata = json_encode($data);
        
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
                $result = curl_exec($ch);
                curl_close($ch);
                $request = json_decode($result, true);
        
                Log::debug('FETCHED BROKER MESSAGES | Local Broker: ' . $broker_user->name .'| MESSAGES: '. $result);

            } 
        });
    }

    public function mdTest()
    {
        // $user = auth()->user();
        // // Call The Execution Balance Update Job
        // /*
        //     * Run FIX Message Download Api
        //     - Import new execution reports only
        //     - Update the status of orders based on the execution report for this specific broker
        //     - Update Account Balances based on (REJECTED,CANCELLED,NEW,FILLED,PARTIALLYFILLED)
        // */
        // $executionBalanceUpdate = new ExecutionBalanceUpdate($user->name);
        // $this->dispatch($executionBalanceUpdate);
        // /*--*/
        $url = fix_wrapper_url('FIX_API_URL') . "api/messagedownload/download";
        $data = array(
            'BeginString' => 'FIX.4.2',
            "SenderSubID" => 'BARITA',
            "seqNum" => 0,
            'StartTime' => date('Y-m-d', time() -5 * 60 * 60) . " 00:00:00.000",
            'EndTime' => date('Y-m-d', time() -5 * 60 * 60) . " 23:59:59.000",
        );
        // 'StartTime' => date('Y-m-d') . " 11:00:00.000",
        // 'EndTime' => date('Y-m-d') . " 23:30:00.000",
        $postdata = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json', 'Cache-Control: no-cache'));
        $result = curl_exec($ch);
        curl_close($ch);
        $request = json_decode($result, true);
        return $request;
    }









    function index(Request $request)
    {

        // return 'test';
        $login = auth()->user();
        $user = Auth::user()->getRoleNames();
        if ($user[0] === 'ADMD') {
            return redirect('jse-admin');
        }
        if ($login['status'] === 'Verified' && $user[0] != "ADMD") {
            switch ($user[0]) {
                case 'ADMB':
                    $local_brokers = LocalBroker::all();
                    // return view('local')->with('local_brokers', $local_brokers);/
                    return redirect('broker');
                    break;
                case 'OPRB':
                    return redirect('operator');
                    break;
                case 'TRDB':
                    return redirect('trader-broker');
                    break;
                case 'BRKF':
                    return 'Configuration Screen For Outbound Foreign Broker To Be Designed <a href="/logout">Logout</a>';
                    // return redirect('foreign-broker');
                    break;
                case 'AGTS':
                    return 'Configuration Screen For Settlement Bank To Be Designed <a href="/logout">Logout</a>';
                    // return redirect('settlement-agent');
                    break;
                default:
                    return "Login Complete";
            }
        } else {
            return view('layouts.unverified');
        }
    }


    function indexCad()
    {
        $foreign_brokers = ForeignBroker::all();
        // return $foreign_brokers;
        return view('foreign')->with('foreign_brokers', $foreign_brokers);
    }



    function brokerList()
    {

        $local_brokers = LocalBroker::with('user')->get();
        return $local_brokers;
    }

    function foreignBrokerList()
    {

        $foreign_brokers = ForeignBroker::with('user')->get();
        return $foreign_brokers;
    }


    function settlementBrokerList()
    {

        $broker_settlement_accounts = BrokerSettlementAccount::with('local_broker', 'foreign_broker')->get();
        return $broker_settlement_accounts;
    }



    function storeLocalBroker(Request $request)
    {

        $pass = $this->HelperClass->rand_pass(8);        
        $hash = $this->HelperClass->generateRandomString(20);
        $role_ADMB = Role::where('name', 'ADMB')->first();

        if ($request->id) {
            Log::debug('NEW LOCAL BROKER request.id: ' . $request->id);
            $this->LogActivity::addToLog('Updated Local Broker Details');
            $broker_user = User::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email,  'status' => 'Unverified' ]
            );
            $broker = LocalBroker::updateOrCreate(
                ['user_id' => $request->id],
                ['admin_can_trade' => $request->admin_can_trade]
            );            
            Log::debug('Saved | Local Broker: ' . $broker_user->name .'| email: '. $broker_user->email .'| admin_can_trade:' . $broker->admin_can_trade);
            
            //$request['id'] = $user['id'];
            $request['hash'] = $broker_user->hash;

            Mail::to($request->email)->send(new LocalBrokerDetailsUpdate($request));
        } else {
            Log::debug('Adding | Local Broker: '. $request->name . '| email: '. $request->email .'| admin_can_trade: ' . $request->admin_can_trade) .'...';

            $this->LogActivity::addToLog('Created A Local Broker');
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($pass);
            $user->status = 'Unverified';
            $user->hash = $hash;
            $user->save();
            $user->roles()->attach($role_ADMB);

            $broker = new LocalBroker;
            $broker->user_id = $user->id;
            $broker->admin_can_trade = $request->admin_can_trade;
            
            $broker->save();
            $request['id'] = $user['id'];
            $request['hash'] = $hash;
            
            // Notify Settlement Bank
            Mail::to($request->email)->send(new MailLocalBroker($request, $pass));
        }
        // return $broker;

    }

    public function logActivity()
    {
        return $this->LogActivity->addToLog('Updated Settlement Account Details');
    }
    function storeSettlementBroker(Request $request)
    {
        $local_broker_name = $this->HelperClass->getUser($request->local_broker_id);
        $foreign_broker_name = $this->HelperClass->getUser($request->foreign_broker_id);
        $pass = $this->HelperClass->rand_pass(8);
        $role_AGTS = Role::where('name', 'AGTS')->first();

        if ($request->id) {
            $toggleStatus = 'Unverified';
            $this->LogActivity::addToLog('Updated Settlement Account Details. Account Number: ' . $request->account . ', Balance: ' . $request->account_balance . ', Amount Allocated: ' . $request->amount_allocated);
            if ($request->filled_orders == 0) {
                $toggleStatus = 'Verified';
            }
            $b = BrokerSettlementAccount::find($request->id);

            $b->update(
                [
                    'local_broker_id' => $request->local_broker_id,
                    'foreign_broker_id' => $request->foreign_broker_id,
                    'currency' => $request->currency,
                    'amount_allocated' => $request->amount_allocated,
                    'account_balance' => $request->account_balance,
                    'bank_name' => $request->bank_name,
                    'email' => $request->email,
                    'filled_orders' => $request->filled_orders,
                    'account' => $request->account,
                    'settlement_agent_status' => $toggleStatus,
                ]
            );


            // Update The Settlement Bank password and notify the Settlement Bank of the change
            User::where('name', $request->bank_name)
                ->update(['password' => Hash::make($pass)]);

            $request['password'] = $pass;
            $request['local_broker'] = $local_broker_name[0]->name;
            $request['foreign_broker'] = $foreign_broker_name[0]->name;
            //Notify the bank that the settlement is being updated so they can verify it.
            Mail::to($request->email)->send(new SettlementAccountUpdated($request, ''));
        } else {
            // Sending A Bank Settlement Account Verification Request Email
            // For each Settlement Account Created or Edited for a Local Broker
            // Only add a new email/user for the BANK if it is being added for the 1st time.
            // If the email/user of the BANK already exists, update the password.
            // Always include the Username and Password in every verification request sent to a BANK email/user.

            //Check if the user already exists
            if (count($this->HelperClass->getSettlementUserByEmail($request->email)) > 0) {
                $this->LogActivity::addToLog('Updated Settlement Account Details. Account Number: ' . $request->account . ', Balance: ' . $request->account_balance . ', Amount Allocated: ' . $request->amount_allocated);
                // if the user exists update password and send new email
                $u = $this->HelperClass->getSettlementUserByEmail($request->email);
                // return $u;
                $u = $u[0];

                User::updateOrCreate(
                    ['email' => $u->email],
                    ['password' =>  Hash::make($pass)]

                );

                $settlement_hash = $this->HelperClass->generateRandomString(20);
                $user_hash = $this->HelperClass->generateRandomString(20);
                // //Create A Broker Settlement Account
                $broker_settlement_account = new BrokerSettlementAccount;
                $broker_settlement_account->local_broker_id = $request->local_broker_id;
                $broker_settlement_account->foreign_broker_id = $request->foreign_broker_id;
                $broker_settlement_account->bank_name = $request->bank_name;
                $broker_settlement_account->account = $request->account;
                $broker_settlement_account->email = $request->email;
                $broker_settlement_account->hash = $settlement_hash;
                $broker_settlement_account->status = 'Unverified';
                $broker_settlement_account->currency = $request->currency;
                $broker_settlement_account->settlement_agent_status = 'Unverified';
                $broker_settlement_account->foreign_broker_status = 'Unverified';
                $broker_settlement_account->filled_orders = $request->filled_orders;
                $broker_settlement_account->account_balance = $request->account_balance;
                $broker_settlement_account->amount_allocated = $request->amount_allocated;
                $broker_settlement_account->save();

                // $data = [];
                $user['password'] = $pass;
                $user['local_broker'] = $local_broker_name[0]->name;
                $user['foreign_broker'] = $foreign_broker_name[0]->name;
                $user['user_name'] = $request->bank_name;
                $data['name'] = $u->name;
                $user['account'] = $request->account;
                $user['filled_orders'] = $request->filled_orders;
                $user['id'] = $broker_settlement_account->id;
                $user['hash'] = $settlement_hash;
                $user['email'] = $request->email;
                // return $data;
                //Notify the bank that the settlement is being created so they can verify it.
                Mail::to($request->email)->send(new SettlementAccountConfirmation($user));
            } else {

                $settlement_hash = $this->HelperClass->generateRandomString(20);
                $user_hash = $this->HelperClass->generateRandomString(20);
                // //Create A Broker Settlement Account
                $broker_settlement_account = new BrokerSettlementAccount;
                $broker_settlement_account->local_broker_id = $request->local_broker_id;
                $broker_settlement_account->foreign_broker_id = $request->foreign_broker_id;
                $broker_settlement_account->bank_name = $request->bank_name;
                $broker_settlement_account->account = $request->account;
                $broker_settlement_account->email = $request->email;
                $broker_settlement_account->hash = $settlement_hash;
                $broker_settlement_account->status = 'Unverified';
                $broker_settlement_account->currency = $request->currency;
                $broker_settlement_account->settlement_agent_status = 'Unverified';
                $broker_settlement_account->foreign_broker_status = 'Unverified';
                $broker_settlement_account->filled_orders = $request->filled_orders;
                $broker_settlement_account->account_balance = $request->account_balance;
                $broker_settlement_account->amount_allocated = $request->amount_allocated;
                $broker_settlement_account->save();
                // $request['id'] = $broker_settlement_account->id;


                $hash = $this->HelperClass->generateRandomString(20);
                $user = new User();
                $user->name = $request->bank_name;
                $user->email = $request->email;
                $user->password = Hash::make($pass);
                $user->status = 'Unverified';
                $user->hash = $user_hash;
                $user->save();

                $user->roles()->attach($role_AGTS);

                $data = [];
                $data['password'] = $pass;
                $data['local_broker'] = $local_broker_name[0]->name;
                $data['foreign_broker'] = $foreign_broker_name[0]->name;
                $data['user_name'] = $request->bank_name;
                $data['name'] = $user->name;
                $data['account'] = $request->account;
                $data['id'] = $broker_settlement_account->id;
                $data['hash'] = $settlement_hash;
                $data['email'] = $request->email;
                $user['filled_orders'] = $request->filled_orders;
                $data['filled_orders'] = $request->filled_orders;

                //Notify the bank that the settlement is being created so they can verify it.
                Mail::to($request->email)->send(new SettlementAccountConfirmation($data, $user));


                $this->LogActivity::addToLog('Created A New Settlement. Account Number: ' . $request->account . ', Balance: ' . $request->account_balance . ', Amount Allocated: ' . $request->amount_allocated);
            }
        }
    }




    function storeForeignBroker(Request $request)
    {

        // return $request;
        $pass = $this->HelperClass->rand_pass(8);
        $role_BRKF = Role::where('name', 'BRKF')->first();

        if ($request->id) {

            // Next Sprint
            // $broker = User::find($id);
            $broker = User::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email, 'status' => 'Unverified']

            );
            $request['hash']  = $broker->hash;
            //Notify the broker that updates are being made to their accout
            Mail::to($request->email)->send(new BrokerDetailsUpdate($request, $broker->hash));
            $this->LogActivity::addToLog('Update Foreign Broker Details');
        } else {

            $hash = $this->HelperClass->generateRandomString(20);
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($pass);
            $user->status = 'Unverified';
            $user->hash = $hash;
            $user->save();
            $user->roles()->attach($role_BRKF);


            $broker = new ForeignBroker;
            $broker->user_id = $user->id;
            $broker->save();
            $request['id'] = $user['id'];
            $request['hash'] = $hash;

            Mail::to($request->email)->send(new NewForeignBroker($request, $pass));
            $this->LogActivity::addToLog('Created New Foreign Broker');
        }
    }



    function destroyLocalBroker($id)
    {

        $b = LocalBroker::find($id);
        $b->delete();
        $this->LogActivity::addToLog('Deleted Local Broker');
    }
    function destroyForeignBroker($id)
    {
        $b = ForeignBroker::find($id);
        $b->delete();
        // $foreign_broker = $this->HelperClass->getForeignBrokerById($id);
        // Mail::to($foreign_broker->email)->send(new ForeignBrokerRemoval($foreign_broker));
    }



    function updateLocalBroker(Request $request, $id)
    {
        $broker                     = LocalBroker::find($id);
        $broker->name               = $request->name;
        $broker->admin_can_trade    = $request->admin_can_trade;
        $broker->email              = $request->email;
        $broker->save();
    }

    function updateForeignBroker(Request $request, $id)
    {
        $broker               = ForeignBroker::find($id);
        $broker->name         = $request->name;
        $broker->email        = $request->email;
        $broker->save();
    }


    function settlements()
    {
        $local_brokers = LocalBroker::select('*')->get();

        return view('accounts')->with('local_brokers', $local_brokers);
    }


    function brokerConfig()
    {
        $local_brokers = LocalBroker::all();

        return view('broker2broker')->with('local_brokers', $local_brokers);
    }

    function b2b()
    {
        $accounts = BrokerTradingAccount::all();

        return view('broker2broker')->with('accounts', $accounts);
    }
    public function ok()
    {

        return Auth::user()->getDirectPermissions();
        if (Auth::user()->hasPermissionTo(Permission::find(2)->id)) {
            // the user can do everything
            return 'Yes';
        } else {
            return 'No';
        }
    }

    public function destroyBSA($id)
    {

        // return $id;
        $settlement_account = BrokerSettlementAccount::find($id);
        $settlement_account->delete();
    }

    public function updateBalances(Request $request)
    {
        // return $request->settlement_account;
        //Check for accounts availability
        // $account_exists = BrokerClient::where('jcsd', $request->client_JCSD_number)->first();


        //Check If Settlement Accounts Exist
        $account_exists = BrokerSettlementAccount::where('account', $request->settlement_account_number)->first();

        // return $account_exists;
        if ($account_exists) {
            //If the accounts being passed exists, Upload Their Balances based on their settlement account number
            // $broker_client_account = BrokerClient::updateOrCreate(
            //     ['jcsd' => $request->client_JCSD_number],
            //     ['account_balance' => $request->client_balance]
            // );

            $broker_settlement_account = BrokerSettlementAccount::updateOrCreate(
                ['account' => $request->settlement_account_number],
                ['account_balance' => $request->settlement_account]
            );
            //If the accounts being passed exists, Upload Their Balances based on their JCSD account number
            // $broker_client_account = BrokerClient::updateOrCreate(
            //     ['jcsd' => $request->client_JCSD_number],
            //     ['account_balance' => $request->client_balance]
            // );
            // return $broker_client_account;
        }
    }
}
