<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('container', 'API\ContainerAPIController@getAll');
Route::get('container/{id}', 'API\ContainerAPIController@getOverview');

Route::group(['prefix' => 'debug'], function () {
    Route::post('likecontainer', 'API\ParkController@likeContainerDebug');
    Route::get('containerjson', 'API\ParkController@containerJSONDebug');
    Route::get('summary', 'API\HistoryController@summaryDebug');
});

Route::group(['prefix' => 'shifter'], function () {
    Route::post('login', 'API\LoginController@loginShifter');
    Route::post('assign', 'API\ParkController@assignContainerToPark');
    Route::post('change', 'API\ParkController@changePark');
    Route::post('remove', 'API\ParkController@removeContainer');
});
Route::group(['prefix' => 'check'], function () {
    //Route::post('container', 'API\ParkController@getDialogContainer');
    //Route::post('dummy', 'API\ParkController@getDialogDummy');
    Route::post('number', 'API\ParkController@getLikeContainer');
});
Route::group(['prefix' => 'json'], function() {
    Route::get('container', 'API\ParkController@getContainerJson');
    Route::get('trailer', 'API\ParkController@getTrailerJson');
    Route::post('park', 'API\ParkController@getParkJson');
    Route::get('summary', 'API\HistoryController@getSummaryJson');
});
  
  
Route::get('temppark/today/{id}', 'API\ParkController@getCurrent');
Route::post('temppark/update', 'API\ParkController@editContainer');
Route::post('temppark/add', 'API\ParkController@bookPark');
Route::get('temppark/dummy', 'API\ParkController@getDummy');
Route::post('finish', 'API\ParkController@releasePark');
Route::post('cancel', 'API\ParkController@cancelPark');

Route::post('cache', 'API\CacheController@retrieveFile');