<?php

namespace App\Http\Controllers;

use App\BrokerClient;
use App\Http\Controllers\Controller;
use App\Helpers\FunctionSet;
use App\Helpers\LogActivity;
use App\Http\ApiResponse;
use App\Mail\LocalBrokerClient;
use App\Mail\LocalBrokerTrader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
{
    public function __construct()
    {

        $this->res = new ApiResponse;
    }
    /**
     * @OA\Post(
     *     path="/api/v1/customer",
     *     summary="create customer",
     *     description="create ",
     *     tags={"Customer"},
     *     security={ {"bearer": {}} },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                @OA\Property(
     *                   property="name",
     *                   description="name.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="email",
     *                   description="email.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="address",
     *                   description="address.",
     *                   type="string",
     *                ),
     *                @OA\Property(
     *                   property="lat",
     *                   description="lat.",
     *                   type="decimal",
     *                ),
     *                @OA\Property(
     *                   property="long",
     *                   description="long.",
     *                   type="decimal",
     *                ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(response=200,description="successful operation",@OA\JsonContent()),
     *     @OA\Response(response=400,description="validation/server error",),
     *     @OA\Response(response=401,description="validation/server error",)
     * )
     */

    public function create(Request $request)
    {customer-feedback/2/$2y$10$bHu9SHwhcd1JF0i3OQTQ0uOOJxAnxv4AoSfj.BOOi1rM.oh4xm7.6/2020-08-11%2018:47:24.0#

        $client = new BrokerClient;
        $client->local_broker_id = $request->get('local_broker_id');
        $client->name = $request->get('name');
        $client->email = $request->get('email');
        $client->orders_limit = $request->get('orders_limit');
        $client->open_orders = $request->get('open_orders');
        $client->jcsd = $request->get('jcsd');
        $client->status = 'Un-Verified';
        $client->save();

        return $this->res->withSuccessData($client);
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
