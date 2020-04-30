<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;


    protected function authenticated(Request $request, $user)
    {
        $user = User::with('roles')->find($user->id);
        // $user_role = $user[0]->roles[0]->name;
        return $user[0];
        // switch ($user_role) {   
        //     case 'ADMB':
        //         return redirect('/broker');
        //         break;
        //     case 'ADMD':
        //         return redirect('/jse-admin');
        //         break;
        //         case 'OPRB':
        //             return redirect('/operator');
        //         break;
        // }


        
    // if ( $user->isAdmin() ) {// do your magic here
    //     return redirect()->route('dashboard');
    // }
    
    //  return redirect('/');
    }
    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    //protected $redirectTo = '/admin';
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }
    }