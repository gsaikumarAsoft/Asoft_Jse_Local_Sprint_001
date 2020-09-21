<?php

namespace App\Http\Controllers;

use App\BrokerUser;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Mail\LocalBrokerUser;
use App\Mail\SettlementAccountConfirmation;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public $successStatus = 200;
    public function __construct()
    {

        $this->middleware('auth');
        $this->HelperClass = new FunctionSet;
    }
    /** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function login()
    {
        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            $success['token'] =  $user->createToken('DMA1.5')->accessToken;
            return response()->json(['success' => $success], $this->successStatus);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }
    /** 
     * Register api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('DMA1.5')->accessToken;
        $success['name'] =  $user->name;
        return response()->json(['success' => $success], $this->successStatus);
    }
    /** 
     * details api 
     * 
     * @return \Illuminate\Http\Response 
     */
    public function details()
    {
        $user = Auth::user();
        return response()->json(['success' => $user], $this->successStatus);
    }

    public function index()
    {
        $user = auth()->user();
        $a = BrokerUser::with('user')->where('dma_broker_id', $user->id)->get();
        if (count($a) > 0) {
            foreach ($a as $key => $user) {

                $user = $a[$key]->user;
                $broker_user[$key] = [];
                $broker_user[$key]['id'] = $user->id;
                $broker_user[$key]['name'] = $user->name;
                $broker_user[$key]['email'] = $user->email;
                $broker_user[$key]['status'] = $user->status;
                $broker_user[$key]['permissions'] = $user->permissions;
                $broker_user[$key]['broker_trading_account_id'] = (int) $a[$key]->broker_trading_account_id;
            }
            return $broker_user;
        } else {
            return $a;
        }
    }
    function store(Request $request)
    {
        $this->HelperClass->createBrokerUser($request);
    }
    function destroy($id)
    {

        $b = User::find($id);
        $b->delete();
        LogActivity::addToLog('Deleted Broker User');
    }
}
