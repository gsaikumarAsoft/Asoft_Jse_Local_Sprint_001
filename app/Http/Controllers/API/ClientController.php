<?php

namespace App\Http\Controllers\API;

use App\BrokerClient;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;

class ClientController extends Controller
{
    public $successStatus = 200;
    public function __construct()
    {
        $this->LogActivity = new LogActivity;
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
            $this->LogActivity::addToLog('API Login Successfull');
            return response()->json(['success' => $success], $this->successStatus);
        } else {
            $this->LogActivity::addToLog('API Login Failed');
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
        return $user;
        return response()->json(['success' => $user], $this->successStatus);
    }

    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'local_broker_id' => 'required',
            'name' => 'required',
            'email' => 'required|email',
            'orders_limit' => 'required',
            'open_orders' => 'required',
            'jcsd' => 'required',
            'account_balance' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }
        $client_record = BrokerClient::where('email', $request->get('email'))->first();
        if ($client_record->exists) {
            $request['message'] = "Duplicate Record Detected";
            $this->LogActivity::addToLog(json_encode($request->all()));
            return response()->json(['error' => "A client account with this E-MAIL already exists"]);
        }
        $broker_client = new BrokerClient();
        $broker_client->local_broker_id = $request->get('local_broker_id');
        $broker_client->name = $request->get('name');
        $broker_client->email = $request->get('email');
        $broker_client->orders_limit = $request->get('orders_limit');
        $broker_client->open_orders = $request->get('open_orders');
        $broker_client->jcsd = $request->get('jcsd');
        $broker_client->status = $request->get('status');
        $broker_client->account_balance = $request->get('account_balance');
        $broker_client->status = 'Un-Verified';
        $broker_client->save();
        $request['message'] = "Client Account Created";
        $this->LogActivity::addToLog(json_encode($request->all()));
        return response()->json(['success' => "New Client Created"], $this->successStatus);
    }


    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'orders_limit' => 'required',
            'open_orders' => 'required',
            'filled_orders' => 'required',
            'jcsd' => 'required',
            'account_balance' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->res->withError($validator->errors()->toArray(), 400);
        }
        $broker_client = BrokerClient::find($id);

        if ($broker_client != null) {
            $broker_client->local_broker_id = $request->get('local_broker_id');
            $broker_client->name = $request->get('name');
            $broker_client->email = $request->get('email');
            $broker_client->orders_limit = $request->get('orders_limit');
            $broker_client->open_orders = $request->get('open_orders');
            $broker_client->jcsd = $request->get('jcsd');
            $broker_client->status = $request->get('status');
            $broker_client->account_balance = $request->get('account_balance');
            $broker_client->save();
            $request['message'] = "Client Account Updated";
            $this->LogActivity::addToLog(json_encode($request->all()));
            return response()->json(['success' => "Client Updated"], $this->successStatus);
        } else {
            return response()->json(['error' => 'Invalid Client'], 401);
        }
    }
}
