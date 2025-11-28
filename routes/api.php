<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FdhClaimStatusController;
use App\Http\Controllers\Api\AmnosendController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// API FDH -----------------------------------------------------------------------------------
Route::post('/fdh/check-claim', [FdhClaimStatusController::class, 'check']);
Route::post('/fdh/check-claim-indiv', [FdhClaimStatusController::class, 'check_indiv']);
Route::get('/fdh-test', [FdhClaimStatusController::class, 'testToken']);

// API AOPOD -----------------------------------------------------------------------------------
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();   
});

Route::post('/amnosend', [AmnosendController::class, 'send']);
