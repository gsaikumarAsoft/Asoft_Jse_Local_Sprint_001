<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\BrokerSettlementAccount;
use App\BrokerTradingAccount;
use App\BrokerUser;
use App\ForeignBroker;
use App\Helpers\FunctionSet;
use App\LocalBroker;
use App\Mail\BrokerUserAccountVerified;
use App\Mail\LocalBrokerTradingAccountVerification;
use App\Mail\NewForeignBrokerOnboarding;
use App\Mail\OnboardLocalBrokerOperator;
use App\Mail\SettlementAccountConfirmation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AccountVerificationController extends Controller
{

    public function __construct()
    {
        // $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
    }

    public function foreignBrokerUpdate($id, $action)
    {

        switch ($action) {
            case 'accept':
                $broker = User::updateOrCreate(
                    ['id' => $id],
                    ['status' => 'Verified']

                );
                return view('layouts.approve');
                break;
            case 'reject':
                $broker = User::updateOrCreate(
                    ['id' => $id],
                    ['status' => 'Rejected']

                );
                return view('layouts.rejected');
                break;
        }
    }

    public function verifyForeign($id, $action)
    {
        // return $id;


        switch ($action) {
            case 'accept':


                User::where('hash', $id)
                    ->update(['status' => 'Verified']);

                // );
                // return $broker;
                // return redirect('/logout');
                // Mail::to($broker->email)->send(new NewForeignBrokerOnboarding($broker));
                break;
            case 'reject':
                User::where('hash', $id)
                    ->update(['status' => 'Rejected']);
                break;
        }

        return view('layouts.approve');
    }
    public function verifyLocal($id, $action)
    {

        switch ($action) {
            case 'accept':


                $broker = User::updateOrCreate(
                    ['id' => $id],
                    ['status' => 'Verified']

                );

                Mail::to($broker->email)->send(new OnboardLocalBrokerOperator($broker));
                break;
            case 'reject':
                $broker = User::updateOrCreate(
                    ['id' => $id],
                    ['status' => 'Rejected']

                );
                break;
        }


        return view('layouts.approve');
    }

    public function verifySettlement($id, $action)
    {


        switch ($action) {
            case 'accept':

                $broker = BrokerSettlementAccount::where('hash', $id)
                    ->update(['settlement_agent_status' => 'Verified']);
                $account = DB::table('broker_settlement_accounts')->where('hash', $id)->get();


                if (count($account) <= 0) {
                    return response()->json(['valid' => false, 'message' => 'You are trying to verify an expired account']);
                }
                $account['account'] = $account[0]->account;


                $foreign_broker = $this->HelperClass->getUserAll($account[0]->foreign_broker_id);
                // $account->lo = $lo;
                $account['local_broker'] = $this->HelperClass->getUserAll($account[0]->local_broker_id)['name'];
                $account['foreign_broker'] = $this->HelperClass->getUserAll($account[0]->foreign_broker_id)['name'];
                $account['hash'] = $id;



                $account['user_name'] = $account['foreign_broker'];
                $account['level'] = 'Foreign';

                // DMA ADMIN WEB ISSUES - From Demo Session 2020-07-01
                // - Remove Settlement Account Notification to Foreign Broker Email
                // Mail::to($foreign_broker->email)->send(new SettlementAccountConfirmation($account));

                return view('layouts.approve');
                break;
            case 'reject':
                BrokerSettlementAccount::where('hash', $id)
                    ->update(['settlement_agent_status' => 'Rejected']);

                break;
        }
    }

    public function foreignBrokerSettlement($id, $action)
    {


        switch ($action) {
            case 'accept':

                $broker = BrokerSettlementAccount::where('hash', $id)
                    ->update(['foreign_broker_status' => 'Verified']);
                $account = BrokerSettlementAccount::where('hash', $id)->first();
                $foreign_broker = $this->HelperClass->getUserAll($account->foreign_broker_id);
                // $account->lo = $lo;
                $account->local_broker = $this->HelperClass->getUserAll($account->local_broker_id)['name'];
                $account->foreign_broker = $this->HelperClass->getUserAll($account->foreign_broker_id)['name'];
                $account['user_name'] = $account->foreign_broker;
                $account['level'] = 'Foreign';
                // Mail::to($foreign_broker->email)->send(new SettlementAccountConfirmation($account));
                return view('layouts.approve');
                break;
            case 'reject':
                BrokerSettlementAccount::where('hash', $id)
                    ->update(['foreign_broker_status' => 'Rejected']);
                return view('layouts.rejected');
                break;
        }
    }


    public function verifyB2B($id, $action)
    {

        switch ($action) {
            case 'accept':
                $trading = BrokerTradingAccount::where('hash', $id)->first();
                $foreign_broker = BrokerSettlementAccount::where('id', $trading->broker_settlement_account_id)
                    ->update(['foreign_broker_status' => 'Verified']);
                $account = BrokerSettlementAccount::where('id', $trading->broker_settlement_account_id)->first();

                $trading = DB::table('broker_trading_accounts')->select('target_comp_id', 'sender_comp_id')->where('broker_settlement_account_id', $account['id'])->get();
                if ($account->settlement_agent_status === 'Verified' && $account->foreign_broker_status === 'Verified') {


                    $broker = BrokerTradingAccount::where('hash', $id)
                        ->update(['status' => 'Verified']);
                    $local_broker = $this->HelperClass->getUserAll($account->local_broker_id);
                    $fb = $this->HelperClass->getUserAll($account->foreign_broker_id);
                    $account['broker_name'] = $local_broker->name;
                    $account['foreign_broker_name'] = $fb->name;
                    $account['bank_agent'] = $account['bank_name'];
                    $account['sender_comp_id'] = $trading[0]->sender_comp_id;
                    $account['target_comp_id'] = $trading[0]->target_comp_id;
                    Mail::to($local_broker->email)->send(new LocalBrokerTradingAccountVerification($account));
                }

                break;
            case 'reject':
                BrokerSettlementAccount::where('hash', $id)
                    ->update(['foreign_broker_status' => 'Rejected']);

                break;
        }


        return view('layouts.approve');
    }


    public function verifyBrokerUser($id, $action)
    {

        switch ($action) {
            case 'accept':

                $password = $this->HelperClass->rand_pass(8);
                //Update Status & Create User Account & Email Credentials   
                $broker = User::updateOrCreate(
                    ['hash' => $id],
                    ['status' => 'Verified', 'password' => Hash::make($password)]
                );

                // Find the user we are trying to send credentials to using their hash.
                $user = User::where('hash', $id)->first();
                $user['p'] = $password;


                // Notify the new broker user with their login credentials
                Mail::to($user->email)->send(new BrokerUserAccountVerified($user));

                // $this->HelperClass->createBrokerUserAccount($id);


                break;
            case 'reject':
                $broker = BrokerUser::updateOrCreate(
                    ['id' => $id],
                    ['status' => 'Rejected']

                );
                break;
        }


        return view('layouts.approve');
    }
    public function verifyBrokerTrader($id, $action)
    {

        switch ($action) {
            case 'accept':


                $broker = BrokerClient::updateOrCreate(
                    ['id' => $id],
                    ['status' => 'Verified']

                );
                break;
            case 'reject':
                $broker = BrokerClient::updateOrCreate(
                    ['id' => $id],
                    ['status' => 'Rejected']

                );
                break;
        }


        return view('layouts.approve');
    }

    public function verifyClientDetails($id, $action)
    {

        switch ($action) {
            case 'accept':


                $broker = BrokerClient::updateOrCreate(
                    ['jcsd' => $id],
                    ['status' => 'Verified']

                );
                return view('layouts.approve');
                break;
            case 'reject':
                $broker = BrokerClient::updateOrCreate(
                    ['jcsd' => $id],
                    ['status' => 'Rejected']

                );
                return view('layouts.rejected');
                break;
        }
    }


    public function saveB2B($id, $action)
    {

        switch ($action) {
            case 'accept':


                $broker = BrokerSettlementAccount::updateOrCreate(
                    ['hash' => $id],
                    ['status' => 'Verified']

                );
                return view('layouts.approve');
                break;
            case 'reject':
                $broker = BrokerSettlementAccount::updateOrCreate(
                    ['hash' => $id],
                    ['status' => 'Denied']

                );
                return view('layouts.rejected');
                break;
        }
    }

    public function jseValidation($id, $action)
    {

        switch ($action) {
            case 'accept':


                $broker = User::updateOrCreate(
                    ['hash' => $id],
                    ['status' => 'Verified']

                );

                return redirect('/');
                break;
        }
    }
}
