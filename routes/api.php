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
Route::middleware(['throttle:60,1'])->group(function () {
    // API AOPOD (Scheduled Task - Protected by Localhost / Secret Key)
    Route::post('/amnosend', [AopodSendController::class, 'send']);

    // API NHSO Auto Pull Yesterday (Scheduled Task - Protected by Localhost / Secret Key)
    Route::post('nhso_endpoint_pull_yesterday', [NhsoEndpointController::class, 'pullYesterday'])->name('nhso_endpoint_pull_yesterday');

    // API E-Claim Extension Sync
    Route::post('/eclaim/sync', [\App\Http\Controllers\ImportEclaimController::class, 'sync_eclaim_extension']);
    Route::post('/eclaim/session-sync', [\App\Http\Controllers\EclaimBotController::class, 'saveSessionFromExtension']);

    // API HOSFIN GL Microservice Sync (Protected by GL Token)
    Route::match(['get', 'post'], '/hosfin/gl/sync', [\App\Http\Controllers\Api\HosfinGlSyncController::class, 'sync'])->name('api.hosfin.gl.sync');
    Route::get('/hosfin/gl/status', [\App\Http\Controllers\Api\HosfinGlSyncController::class, 'status'])->name('api.hosfin.gl.status');

    // Sanctum Authenticated User
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });
});

