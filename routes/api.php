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

Route::post('/saleBest/{dateReport}','SalesController@productBestSeller');
Route::post('/saleLess/{dateReport}','SalesController@productLeastSell');
Route::get ('/inventoryStockOrders','InventoryController@stockOrders');
Route::post('/inventoryStockOrders/{idOrders}','InventoryController@stockOrdersId');
Route::post('/calculateInventory','InventoryController@calculateInventory');


