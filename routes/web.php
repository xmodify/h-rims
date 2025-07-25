<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MainSettingController;
use App\Http\Controllers\Admin\LookupIcodeController;
use App\Http\Controllers\Admin\LookupWardController;
use App\Http\Controllers\Admin\LookupHospcodeController;
use App\Http\Controllers\NotifyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DiagController;
use App\Http\Controllers\IpdController;
use App\Http\Controllers\ClaimOpController;
use App\Http\Controllers\ClaimIpController;

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
Route::prefix('admin')->middleware(['auth', 'is_admin'])->name('admin.')->group(function () {
    Route::post('/git-pull', function () {
        try { $output = shell_exec('cd ' . base_path() . ' && git pull origin main 2>&1');
            return response()->json(['output' => $output]);
        } catch (\Exception $e) { return response()->json(['error' => $e->getMessage()], 500);}})->name('git.pull');
    Route::resource('users', UserController::class);
    Route::get('main_setting', [MainSettingController::class, 'index'])->name('main_setting');
    Route::put('main_setting/{id}', [MainSettingController::class, 'update']);
    Route::post('main_setting/up_structure', [MainSettingController::class, 'up_structure'])->name('up_structure');;
    Route::resource('lookup_icode', LookupIcodeController::class)->parameters(['lookup_icode' => 'icode']);
    Route::post('insert_lookup_uc_cr', [LookupIcodeController::class, 'insert_lookup_uc_cr'])->name('insert_lookup_uc_cr');
    Route::post('insert_lookup_ppfs', [LookupIcodeController::class, 'insert_lookup_ppfs'])->name('insert_lookup_ppfs');
    Route::post('insert_lookup_herb32', [LookupIcodeController::class, 'insert_lookup_herb32'])->name('insert_lookup_herb32');
    Route::resource('lookup_ward', LookupWardController::class)->parameters(['lookup_ward' => 'ward']);
    Route::post('insert_lookup_ward', [LookupWardController::class, 'insert_lookup_ward'])->name('insert_lookup_ward');
    Route::resource('lookup_hospcode', LookupHospcodeController::class)->parameters(['lookup_hospcode' => 'hospcode']);
});

#################################################################################################

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('home') ;
});

Auth::routes();

//home-----------------------------------------------------------------------------------------------------------------------------
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('nhso_endpoint_pull', [HomeController::class, 'nhso_endpoint_pull']);
Route::get('nhso_endpoint_pull/{vstdate}/{cid}',[HomeController::class,'nhso_endpoint_pull_indiv']);
Route::match(['get','post'],'opd_ofc',[HomeController::class,'opd_ofc']);
Route::match(['get','post'],'opd_non_authen',[HomeController::class,'opd_non_authen']);
Route::match(['get','post'],'opd_non_hospmain',[HomeController::class,'opd_non_hospmain']);
Route::match(['get','post'],'opd_ucs_anywhere',[HomeController::class,'opd_ucs_anywhere']);
Route::match(['get','post'],'opd_ucs_cr',[HomeController::class,'opd_ucs_cr']);
Route::match(['get','post'],'opd_ucs_herb',[HomeController::class,'opd_ucs_herb']);
Route::match(['get','post'],'opd_ucs_healthmed',[HomeController::class,'opd_ucs_healthmed']);
Route::match(['get','post'],'opd_ppfs',[HomeController::class,'opd_ppfs']);
Route::match(['get','post'],'ipd_homeward',[HomeController::class,'ipd_homeward']);
Route::match(['get','post'],'ipd_non_dchsummary',[HomeController::class,'ipd_non_dchsummary']);
Route::match(['get','post'],'ipd_finance_chk_opd_wait_transfer',[HomeController::class,'ipd_finance_chk_opd_wait_transfer']);
Route::match(['get','post'],'ipd_finance_chk_wait_rcpt_money',[HomeController::class,'ipd_finance_chk_wait_rcpt_money']);

//Import---------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'import/stm_ucs',[ImportController::class,'stm_ucs'])->name('stm_ucs');
Route::post('import/stm_ucs_save',[ImportController::class,'stm_ucs_save']);
Route::match(['get','post'],'import/stm_ucs_kidney',[ImportController::class,'stm_ucs_kidney'])->name('stm_ucs_kidney');
Route::post('import/stm_ucs_kidney_save',[ImportController::class,'stm_ucs_kidney_save']);
Route::match(['get','post'],'import/stm_ofc',[ImportController::class,'stm_ofc'])->name('stm_ofc');
Route::post('import/stm_ofc_save',[ImportController::class,'stm_ofc_save']);
Route::match(['get','post'],'import/stm_ofc_kidney',[ImportController::class,'stm_ofc_kidney'])->name('stm_ofc_kidney');
Route::post('import/stm_ofc_kidney_save',[ImportController::class,'stm_ofc_kidney_save']);

//Diag------------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'diag/sepsis',[DiagController::class,'sepsis']);
Route::match(['get','post'],'diag/stroke',[DiagController::class,'stroke']);
Route::match(['get','post'],'diag/stemi',[DiagController::class,'stemi']);
Route::match(['get','post'],'diag/pneumonia',[DiagController::class,'pneumonia']);

//Ipd-------------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'ipd/wait_doctor_dchsummary',[IpdController::class,'wait_doctor_dchsummary']);
Route::match(['get','post'],'ipd/wait_icd_coder',[IpdController::class,'wait_icd_coder']);
Route::match(['get','post'],'ipd/dchsummary',[IpdController::class,'dchsummary']);
Route::match(['get','post'],'ipd/dchsummary_audit',[IpdController::class,'dchsummary_audit']);

//Claim_OP -------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'claim_op/ucs_incup',[ClaimOpController::class,'ucs_incup']);
Route::match(['get','post'],'claim_op/ucs_inprovince',[ClaimOpController::class,'ucs_inprovince']);
Route::match(['get','post'],'claim_op/ucs_outprovince',[ClaimOpController::class,'ucs_outprovince']);
Route::match(['get','post'],'claim_op/ucs_kidney',[ClaimOpController::class,'ucs_kidney']);
Route::match(['get','post'],'claim_op/stp_incup',[ClaimOpController::class,'stp_incup']);
Route::match(['get','post'],'claim_op/stp_outcup',[ClaimOpController::class,'stp_outcup']);
Route::match(['get','post'],'claim_op/ofc',[ClaimOpController::class,'ofc']);
Route::match(['get','post'],'claim_op/ofc_kidney',[ClaimOpController::class,'ofc_kidney']);

// Claim_IP -----------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'claim_ip/ucs_incup',[ClaimIpController::class,'ucs_incup']);
Route::match(['get','post'],'claim_ip/ucs_outcup',[ClaimIpController::class,'ucs_outcup']);
Route::match(['get','post'],'claim_ip/stp',[ClaimIpController::class,'stp']);
Route::match(['get','post'],'claim_ip/ofc',[ClaimIpController::class,'ofc']);
Route::match(['get','post'],'claim_ip/lgo',[ClaimIpController::class,'lgo']);
Route::match(['get','post'],'claim_ip/bkk',[ClaimIpController::class,'bkk']);
Route::match(['get','post'],'claim_ip/bmt',[ClaimIpController::class,'bmt']);
Route::match(['get','post'],'claim_ip/sss',[ClaimIpController::class,'sss']);
Route::match(['get','post'],'claim_ip/gof',[ClaimIpController::class,'gof']);
Route::match(['get','post'],'claim_ip/rcpt',[ClaimIpController::class,'rcpt']);
Route::match(['get','post'],'claim_ip/act',[ClaimIpController::class,'act']);

//Notify
Route::get('notify_summary',[NotifyController::class,'notify_summary'])->name('notify_summary');

// Clear-cache
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('route:clear');
    $exitCode = Artisan::call('view:clear');
    return 'DONE'; //Return anything
    });
