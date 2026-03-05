<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FdhClaimStatusController;
use App\Http\Controllers\Api\NhsoEndpointController;
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
Route::get('fdh/testtoken', [FdhClaimStatusController::class, 'testToken']);
Route::post('fdh/check-claim', [FdhClaimStatusController::class, 'check'])->name('api.fdh.check_claim');
Route::post('fdh/check-claim-indiv', [FdhClaimStatusController::class, 'check_indiv'])->name('api.fdh.check_claim_indiv');
Route::post('fdh/check-claim-lastdays', [FdhClaimStatusController::class, 'checkLastDays'])->name('api.fdh.check_claim_lastdays');

// API NHSO -----------------------------------------------------------------------------------
Route::post('nhso_endpoint_pull', [NhsoEndpointController::class, 'pull'])->name('nhso_endpoint_pull');
Route::post('nhso_endpoint_pull_indiv', [NhsoEndpointController::class, 'pullIndiv'])->name('nhso_endpoint_pull_indiv');
Route::post('nhso_endpoint_pull_yesterday', [NhsoEndpointController::class, 'pullYesterday'])->name('nhso_endpoint_pull_yesterday');

// API AOPOD -----------------------------------------------------------------------------------
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/amnosend', [AmnosendController::class, 'send']);

// API E-Claim ---------------------------------------------------------------------------------
Route::post('/eclaim/sync', [\App\Http\Controllers\CheckEclaimController::class, 'sync_eclaim_extension']);
