<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FdhClaimStatusController;
use App\Http\Controllers\Api\NhsoEndpointController;
use App\Http\Controllers\Api\AopodSendController;

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
Route::get('fdh/get-check-list', [FdhClaimStatusController::class, 'getCheckList'])->name('api.fdh.get_check_list');
Route::post('fdh/check-chunk', [FdhClaimStatusController::class, 'checkChunk'])->name('api.fdh.check_chunk');
Route::post('fdh/check-claim', [FdhClaimStatusController::class, 'check'])->name('api.fdh.check_claim');
Route::post('fdh/check-claim-indiv', [FdhClaimStatusController::class, 'check_indiv'])->name('api.fdh.check_claim_indiv');
Route::post('fdh/check-claim-lastdays', [FdhClaimStatusController::class, 'checkLastDays'])->name('api.fdh.check_claim_lastdays');
Route::post('fdh/log-manual-check', [FdhClaimStatusController::class, 'logManualCheck'])->name('api.fdh.log_manual_check');

// API NHSO -----------------------------------------------------------------------------------
Route::get('nhso/testconnection', [NhsoEndpointController::class, 'testConnection'])->name('api.nhso.testconnection');
Route::get('nhso/get-pull-list', [NhsoEndpointController::class, 'getPullList'])->name('api.nhso.get_pull_list');
Route::post('nhso/pull-chunk', [NhsoEndpointController::class, 'pullChunk'])->name('api.nhso.pull_chunk');
Route::post('nhso/log-manual-pull', [NhsoEndpointController::class, 'logManualPull'])->name('api.nhso.log_manual_pull');
Route::post('nhso_endpoint_pull_yesterday', [NhsoEndpointController::class, 'pullYesterday'])->name('nhso_endpoint_pull_yesterday');

// API AOPOD -----------------------------------------------------------------------------------
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/amnosend', [AopodSendController::class, 'send']);

// API E-Claim ---------------------------------------------------------------------------------
Route::post('/eclaim/sync', [\App\Http\Controllers\CheckEclaimController::class, 'sync_eclaim_extension']);
