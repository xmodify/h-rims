<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\IpdController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('home') ;
});

Auth::routes();

//home
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::match(['get','post'],'nhso_endpoint_pull{vstdate}/{cid}',[HomeController::class,'nhso_endpoint_pull']);
Route::match(['get','post'],'opd_ucs_all',[HomeController::class,'opd_ucs_all']);
Route::match(['get','post'],'opd_ofc_all',[HomeController::class,'opd_ofc_all']);
Route::match(['get','post'],'opd_non_authen',[HomeController::class,'opd_non_authen']);
Route::match(['get','post'],'opd_non_hospmain',[HomeController::class,'opd_non_hospmain']);
Route::match(['get','post'],'opd_ucs_anywhere',[HomeController::class,'opd_ucs_anywhere']);
Route::match(['get','post'],'opd_ucs_cr',[HomeController::class,'opd_ucs_cr']);
Route::match(['get','post'],'opd_ucs_healthmed',[HomeController::class,'opd_ucs_healthmed']);
Route::match(['get','post'],'opd_ppfs',[HomeController::class,'opd_ppfs']);
Route::match(['get','post'],'ipd_homeward',[HomeController::class,'ipd_homeward']);
Route::match(['get','post'],'ipd_non_dchsummary',[HomeController::class,'ipd_non_dchsummary']);
Route::match(['get','post'],'ipd_finance_chk_opd_wait_transfer',[HomeController::class,'ipd_finance_chk_opd_wait_transfer']);
Route::match(['get','post'],'ipd_finance_chk_wait_rcpt_money',[HomeController::class,'ipd_finance_chk_wait_rcpt_money']);

//Import
Route::match(['get','post'],'import/stm_ucs',[ImportController::class,'stm_ucs'])->name('stm_ucs');
Route::post('import/stm_ucs_save',[ImportController::class,'stm_ucs_save']);

//Ipd
Route::match(['get','post'],'ipd/wait_doctor_dchsummary',[IpdController::class,'wait_doctor_dchsummary']);
Route::match(['get','post'],'ipd/wait_icd_coder',[IpdController::class,'wait_icd_coder']);
Route::match(['get','post'],'ipd/dchsummary',[IpdController::class,'dchsummary']);
Route::match(['get','post'],'ipd/dchsummary_audit',[IpdController::class,'dchsummary_audit']);

// Clear-cache
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('route:clear');
    $exitCode = Artisan::call('view:clear');
    return 'DONE'; //Return anything
    });
