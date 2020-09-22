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

Route::post('login', 'API\ClientController@login');

Route::group(['prefix' => 'client', 'middleware' => 'auth:api'], function () {
    Route::post('details', 'API\ClientController@details');
    Route::post('add', 'API\ClientController@add');
    Route::post('update/{id}', 'API\ClientController@update');
});
