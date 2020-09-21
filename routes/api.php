<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::namespace('\App\Http\Controllers\Api\V1')->prefix('v1')->group(function () {
    Route::namespace("Client")->prefix("client")->middleware(['auth:api'])->group(function () {
        return '1';
        // Route::post("", "ClientController@create");
        // Route::get("all", "ClientController@all");
    });
});
