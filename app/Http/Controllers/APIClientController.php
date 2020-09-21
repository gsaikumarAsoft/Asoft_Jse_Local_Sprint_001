<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use Illuminate\Http\Request;

class APIClientController extends Controller
{
    public function __construct()
    {
        $this->res = new ApiResponse;
    }

    public function all(Request $request)
    {
        return "Go";
        // $client = new BrokerClient;
        // $query = $client->newQuery();

        // $query->orderBy('name', 'ASC');
        // $cliets = $query->get();

        // return $this->res->withSuccessData($cliets);
    }
}
