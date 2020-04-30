<?php

namespace App\Http\Controllers;

use App\Helpers\LogActivity;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = auth()->user()->roles[0]->name;

        return view('profile.index')->with('user_account', $user)->with('role', $role);
    }

    public function store(Request $request)
    {
        $role = auth()->user()->roles[0]->name;
        // return $role;

        if ($role === 'ADMD') {

            LogActivity::addToLog('Update Profile Details');
            $broker = User::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'email' => $request->email]

            );
        } else {
            LogActivity::addToLog('Update Profile Details');
            $broker = User::updateOrCreate(
                ['id' => $request->id],
                ['password' => Hash::make($request->password)]

            );
        }

        // return $role;
        // if ($request->id) {
        //     LogActivity::addToLog('Update Profile Details');
        //     $broker = User::updateOrCreate(
        //         ['id' => $request->id],
        //         ['name' => $request->name, 'email' => $request->email, 'status' => 'Unverified']

        //     );
        // }
    }
}
