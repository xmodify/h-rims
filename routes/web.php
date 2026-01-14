<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BudgetYearController;
use App\Http\Controllers\Admin\MainSettingController;
use App\Http\Controllers\Admin\LookupIcodeController;
use App\Http\Controllers\Admin\LookupWardController;
use App\Http\Controllers\Admin\LookupHospcodeController;
use App\Http\Controllers\NotifyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\IpdController;
use App\Http\Controllers\ClaimOpController;
use App\Http\Controllers\ClaimIpController;
use App\Http\Controllers\MishosController;
use App\Http\Controllers\DebtorController;

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
    Route::resource('budget_year', BudgetYearController::class)->parameters(['LEAVE_YEAR_ID' => 'LEAVE_YEAR_ID']);
});

#################################################################################################

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('home') ;
});

Auth::routes();

//home-----------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::post('nhso_endpoint_pull', [HomeController::class, 'nhso_endpoint_pull']);
Route::get('nhso_endpoint_pull/{vstdate}/{cid}',[HomeController::class,'nhso_endpoint_pull_indiv']);
Route::get('nhso_endpoint_pull_yesterday', [HomeController::class, 'nhso_endpoint_pull_yesterday'])->name('nhso_endpoint_pull_yesterday');
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
Route::post('import/stm_ucs_updateReceipt',[ImportController::class,'stm_ucs_updateReceipt']);
Route::match(['get','post'],'import/stm_ucs_detail',[ImportController::class,'stm_ucs_detail']);
Route::match(['get','post'],'import/stm_ucs_kidney',[ImportController::class,'stm_ucs_kidney'])->name('stm_ucs_kidney');
Route::post('import/stm_ucs_kidney_save',[ImportController::class,'stm_ucs_kidney_save']);
Route::post('import/stm_ucs_kidney_updateReceipt',[ImportController::class,'stm_ucs_kidney_updateReceipt']);
Route::match(['get','post'],'import/stm_ucs_kidneydetail',[ImportController::class,'stm_ucs_kidneydetail']);

Route::match(['get','post'],'import/stm_ofc',[ImportController::class,'stm_ofc'])->name('stm_ofc');
Route::post('import/stm_ofc_save',[ImportController::class,'stm_ofc_save']);
Route::post('import/stm_ofc_updateReceipt',[ImportController::class,'stm_ofc_updateReceipt']);
Route::match(['get','post'],'import/stm_ofc_detail',[ImportController::class,'stm_ofc_detail']);

Route::match(['get','post'],'import/stm_ofc_csop',[ImportController::class,'stm_ofc_csop'])->name('stm_ofc_csop');
Route::post('import/stm_ofc_csop_save',[ImportController::class,'stm_ofc_csop_save']);
Route::post('import/stm_ofc_csop_updateReceipt',[ImportController::class,'stm_ofc_csop_updateReceipt']);
Route::match(['get','post'],'import/stm_ofc_csopdetail',[ImportController::class,'stm_ofc_csopdetail']);

Route::match(['get','post'],'import/stm_ofc_cipn',[ImportController::class,'stm_ofc_cipn'])->name('stm_ofc_cipn');
Route::post('import/stm_ofc_cipn_save',[ImportController::class,'stm_ofc_cipn_save']);
Route::post('import/stm_ofc_cipn_updateReceipt',[ImportController::class,'stm_ofc_cipn_updateReceipt']);
Route::match(['get','post'],'import/stm_ofc_cipndetail',[ImportController::class,'stm_ofc_cipndetail']);

Route::match(['get','post'],'import/stm_ofc_kidney',[ImportController::class,'stm_ofc_kidney'])->name('stm_ofc_kidney');
Route::post('import/stm_ofc_kidney_save',[ImportController::class,'stm_ofc_kidney_save']);
Route::post('import/stm_ofc_kidney_updateReceipt',[ImportController::class,'stm_ofc_kidney_updateReceipt']);
Route::match(['get','post'],'import/stm_ofc_kidneydetail',[ImportController::class,'stm_ofc_kidneydetail']);
Route::match(['get','post'],'import/stm_lgo',[ImportController::class,'stm_lgo'])->name('stm_lgo');
Route::post('import/stm_lgo_save',[ImportController::class,'stm_lgo_save']);
Route::post('import/stm_lgo_updateReceipt',[ImportController::class,'stm_lgo_updateReceipt']);
Route::match(['get','post'],'import/stm_lgo_detail',[ImportController::class,'stm_lgo_detail']);
Route::match(['get','post'],'import/stm_lgo_kidney',[ImportController::class,'stm_lgo_kidney'])->name('stm_lgo_kidney');
Route::post('import/stm_lgo_kidney_save',[ImportController::class,'stm_lgo_kidney_save']);
Route::post('import/stm_lgo_kidney_updateReceipt',[ImportController::class,'stm_lgo_kidney_updateReceipt']);
Route::match(['get','post'],'import/stm_lgo_kidneydetail',[ImportController::class,'stm_lgo_kidneydetail']);
Route::match(['get','post'],'import/stm_sss_kidney',[ImportController::class,'stm_sss_kidney'])->name('stm_sss_kidney');
Route::post('import/stm_sss_kidney_save',[ImportController::class,'stm_sss_kidney_save']);
Route::post('import/stm_sss_kidney_updateReceipt',[ImportController::class,'stm_sss_kidney_updateReceipt']);
Route::match(['get','post'],'import/stm_sss_kidneydetail',[ImportController::class,'stm_sss_kidneydetail']);

//Check------------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'check/nhso_endpoint',[CheckController::class,'nhso_endpoint']);
Route::match(['get','post'],'check/fdh_claim_status',[CheckController::class,'fdh_claim_status']);
Route::post('check/drug_cat_nhso_save',[CheckController::class,'drug_cat_nhso_save']);
Route::get('check/drug_cat',[CheckController::class,'drug_cat'])->name('drug_cat');;
Route::get('check/drug_cat_non_nhso',[CheckController::class,'drug_cat_non_nhso']);
Route::get('check/drug_cat_nhso_price_notmatch_hosxp',[CheckController::class,'drug_cat_nhso_price_notmatch_hosxp']);
Route::get('check/drug_cat_nhso_tmt_notmatch_hosxp',[CheckController::class,'drug_cat_nhso_tmt_notmatch_hosxp']);
Route::get('check/drug_cat_nhso_code24_notmatch_hosxp',[CheckController::class,'drug_cat_nhso_code24_notmatch_hosxp']);
Route::get('check/drug_cat_herb',[CheckController::class,'drug_cat_herb']);
Route::get('check/pttype',[CheckController::class,'pttype']);
Route::get('check/nhso_subinscl',[CheckController::class,'nhso_subinscl']);

//OPD------------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'opd/oppp_visit',[OpdController::class,'oppp_visit']);
Route::match(['get','post'],'opd/diag_sepsis',[OpdController::class,'diag_sepsis']);
Route::match(['get','post'],'opd/diag_stroke',[OpdController::class,'diag_stroke']);
Route::match(['get','post'],'opd/diag_stemi',[OpdController::class,'diag_stemi']);
Route::match(['get','post'],'opd/diag_pneumonia',[OpdController::class,'diag_pneumonia']);

//Ipd-------------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'ipd/wait_doctor_dchsummary',[IpdController::class,'wait_doctor_dchsummary']);
Route::match(['get','post'],'ipd/wait_icd_coder',[IpdController::class,'wait_icd_coder']);
Route::match(['get','post'],'ipd/dchsummary',[IpdController::class,'dchsummary']);
Route::match(['get','post'],'ipd/dchsummary_audit',[IpdController::class,'dchsummary_audit']);

//Claim_OP -------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'claim_op/ucs_incup',[ClaimOpController::class,'ucs_incup']);
Route::match(['get','post'],'claim_op/ucs_inprovince',[ClaimOpController::class,'ucs_inprovince']);
Route::match(['get','post'],'claim_op/ucs_inprovince_va',[ClaimOpController::class,'ucs_inprovince_va']);
Route::match(['get','post'],'claim_op/ucs_outprovince',[ClaimOpController::class,'ucs_outprovince']);
Route::match(['get','post'],'claim_op/ucs_kidney',[ClaimOpController::class,'ucs_kidney']);
Route::match(['get','post'],'claim_op/stp_incup',[ClaimOpController::class,'stp_incup']);
Route::match(['get','post'],'claim_op/stp_outcup',[ClaimOpController::class,'stp_outcup']);
Route::match(['get','post'],'claim_op/ofc',[ClaimOpController::class,'ofc']);
Route::match(['get','post'],'claim_op/ofc_kidney',[ClaimOpController::class,'ofc_kidney']);
Route::match(['get','post'],'claim_op/lgo',[ClaimOpController::class,'lgo']);
Route::match(['get','post'],'claim_op/lgo_kidney',[ClaimOpController::class,'lgo_kidney']);
Route::match(['get','post'],'claim_op/bkk',[ClaimOpController::class,'bkk']);
Route::match(['get','post'],'claim_op/bmt',[ClaimOpController::class,'bmt']);
Route::match(['get','post'],'claim_op/sss_ppfs',[ClaimOpController::class,'sss_ppfs']);
Route::match(['get','post'],'claim_op/sss_fund',[ClaimOpController::class,'sss_fund']);
Route::match(['get','post'],'claim_op/sss_kidney',[ClaimOpController::class,'sss_kidney']);
Route::match(['get','post'],'claim_op/sss_hc',[ClaimOpController::class,'sss_hc']);
Route::match(['get','post'],'claim_op/rcpt',[ClaimOpController::class,'rcpt']);
Route::match(['get','post'],'claim_op/act',[ClaimOpController::class,'act']);

// Claim_IP -----------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'claim_ip/ucs_incup',[ClaimIpController::class,'ucs_incup']);
Route::match(['get','post'],'claim_ip/ucs_outcup',[ClaimIpController::class,'ucs_outcup']);
Route::match(['get','post'],'claim_ip/stp',[ClaimIpController::class,'stp']);
Route::match(['get','post'],'claim_ip/ofc',[ClaimIpController::class,'ofc']);
Route::match(['get','post'],'claim_ip/lgo',[ClaimIpController::class,'lgo']);
Route::match(['get','post'],'claim_ip/bkk',[ClaimIpController::class,'bkk']);
Route::match(['get','post'],'claim_ip/bmt',[ClaimIpController::class,'bmt']);
Route::match(['get','post'],'claim_ip/sss',[ClaimIpController::class,'sss']);
Route::match(['get','post'],'claim_ip/sss_hc',[ClaimIpController::class,'sss_hc']);
Route::match(['get','post'],'claim_ip/gof',[ClaimIpController::class,'gof']);
Route::match(['get','post'],'claim_ip/rcpt',[ClaimIpController::class,'rcpt']);
Route::match(['get','post'],'claim_ip/act',[ClaimIpController::class,'act']);

// Mishos -------------------------------------------------------------------------------------------------------------------------
Route::match(['get','post'],'mishos/ucs_ae',[MishosController::class,'ucs_ae']);
Route::match(['get','post'],'mishos/ucs_walkin',[MishosController::class,'ucs_walkin']);
Route::match(['get','post'],'mishos/ucs_herb',[MishosController::class,'ucs_herb']);
Route::match(['get','post'],'mishos/ucs_telemed',[MishosController::class,'ucs_telemed']);
Route::match(['get','post'],'mishos/ucs_rider',[MishosController::class,'ucs_rider']);
Route::match(['get','post'],'mishos/ucs_gdm',[MishosController::class,'ucs_gdm']);
Route::match(['get','post'],'mishos/ucs_drug_clopidogrel',[MishosController::class,'ucs_drug_clopidogrel']);
Route::match(['get','post'],'mishos/ucs_drug_sk',[MishosController::class,'ucs_drug_sk']);
Route::match(['get','post'],'mishos/ucs_ins',[MishosController::class,'ucs_ins']);
Route::match(['get','post'],'mishos/ucs_palliative',[MishosController::class,'ucs_palliative']);
Route::match(['get','post'],'mishos/ucs_ppfs_fp',[MishosController::class,'ucs_ppfs_fp']);
Route::match(['get','post'],'mishos/ucs_ppfs_prt',[MishosController::class,'ucs_ppfs_prt']);
Route::match(['get','post'],'mishos/ucs_ppfs_ida',[MishosController::class,'ucs_ppfs_ida']);
Route::match(['get','post'],'mishos/ucs_ppfs_ferrofolic',[MishosController::class,'ucs_ppfs_ferrofolic']);
Route::match(['get','post'],'mishos/ucs_ppfs_fluoride',[MishosController::class,'ucs_ppfs_fluoride']);
Route::match(['get','post'],'mishos/ucs_ppfs_anc',[MishosController::class,'ucs_ppfs_anc']);
Route::match(['get','post'],'mishos/ucs_ppfs_postnatal',[MishosController::class,'ucs_ppfs_postnatal']);
Route::match(['get','post'],'mishos/ucs_ppfs_fittest',[MishosController::class,'ucs_ppfs_fittest']);
Route::match(['get','post'],'mishos/ucs_ppfs_scr',[MishosController::class,'ucs_ppfs_scr']);

// Debtor -------------------------------------------------------------------------------------------------------------------------
Route::get('debtor',[DebtorController::class,'index']);    
Route::match(['get','post'],'debtor/check_income',[DebtorController::class,'_check_income']);
Route::match(['get','post'],'debtor/check_income_detail',[DebtorController::class,'_check_income_detail']);
Route::match(['get','post'],'debtor/check_nondebtor',[DebtorController::class,'_check_nondebtor']);
Route::match(['get','post'],'debtor/summary',[DebtorController::class,'_summary']);
Route::match(['get','post'],'debtor/summary_pdf',[DebtorController::class,'_summary_pdf']);
Route::get('debtor/forget_search', function() { Session::forget('search'); return redirect()->back();});
Route::match(['get','post'],'debtor/1102050101_103',[DebtorController::class,'_1102050101_103']);
Route::post('debtor/1102050101_103_confirm',[DebtorController::class,'_1102050101_103_confirm']);
Route::delete('debtor/1102050101_103_delete',[DebtorController::class,'_1102050101_103_delete']);
Route::put('debtor/1102050101_103/update/{vn}',[DebtorController::class,'_1102050101_103_update']);
Route::get('debtor/1102050101_103_daily_pdf',[DebtorController::class,'_1102050101_103_daily_pdf']);
Route::get('debtor/1102050101_103_indiv_excel',[DebtorController::class,'_1102050101_103_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_109',[DebtorController::class,'_1102050101_109']);
Route::post('debtor/1102050101_109_confirm',[DebtorController::class,'_1102050101_109_confirm']);
Route::delete('debtor/1102050101_109_delete',[DebtorController::class,'_1102050101_109_delete']);
Route::put('debtor/1102050101_109/update/{vn}',[DebtorController::class,'_1102050101_109_update']);
Route::get('debtor/1102050101_109_daily_pdf',[DebtorController::class,'_1102050101_109_daily_pdf']);
Route::get('debtor/1102050101_109_indiv_excel',[DebtorController::class,'_1102050101_109_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_201',[DebtorController::class,'_1102050101_201']);
Route::post('debtor/1102050101_201_confirm',[DebtorController::class,'_1102050101_201_confirm']);
Route::delete('debtor/1102050101_201_delete',[DebtorController::class,'_1102050101_201_delete']);
Route::get('debtor/1102050101_201_daily_pdf',[DebtorController::class,'_1102050101_201_daily_pdf']);
Route::get('debtor/1102050101_201_indiv_excel',[DebtorController::class,'_1102050101_201_indiv_excel']);
Route::post('debtor/1102050101_201_average_receive',[DebtorController::class, '_1102050101_201_average_receive']);  
Route::match(['get','post'],'debtor/1102050101_203',[DebtorController::class,'_1102050101_203']);
Route::post('debtor/1102050101_203_confirm',[DebtorController::class,'_1102050101_203_confirm']);
Route::delete('debtor/1102050101_203_delete',[DebtorController::class,'_1102050101_203_delete']);
Route::put('debtor/1102050101_203/update/{vn}',[DebtorController::class,'_1102050101_203_update']);
Route::get('debtor/1102050101_203_daily_pdf',[DebtorController::class,'_1102050101_203_daily_pdf']);
Route::get('debtor/1102050101_203_indiv_excel',[DebtorController::class,'_1102050101_203_indiv_excel']);
Route::post('debtor/1102050101_203_average_receive',[DebtorController::class, '_1102050101_203_average_receive']); 
Route::match(['get','post'],'debtor/1102050101_209',[DebtorController::class,'_1102050101_209']);
Route::post('debtor/1102050101_209_confirm',[DebtorController::class,'_1102050101_209_confirm']);
Route::delete('debtor/1102050101_209_delete',[DebtorController::class,'_1102050101_209_delete']);
Route::get('debtor/1102050101_209_daily_pdf',[DebtorController::class,'_1102050101_209_daily_pdf']);
Route::get('debtor/1102050101_209_indiv_excel',[DebtorController::class,'_1102050101_209_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_216',[DebtorController::class,'_1102050101_216']);
Route::post('debtor/1102050101_216_confirm_kidney',[DebtorController::class,'_1102050101_216_confirm_kidney']);
Route::post('debtor/1102050101_216_confirm_cr',[DebtorController::class,'_1102050101_216_confirm_cr']);
Route::post('debtor/1102050101_216_confirm_anywhere',[DebtorController::class,'_1102050101_216_confirm_anywhere']);
Route::delete('debtor/1102050101_216_delete',[DebtorController::class,'_1102050101_216_delete']);
Route::get('debtor/1102050101_216_daily_pdf',[DebtorController::class,'_1102050101_216_daily_pdf']);
Route::get('debtor/1102050101_216_indiv_excel',[DebtorController::class,'_1102050101_216_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_301',[DebtorController::class,'_1102050101_301']);
Route::post('debtor/1102050101_301_confirm',[DebtorController::class,'_1102050101_301_confirm']);
Route::delete('debtor/1102050101_301_delete',[DebtorController::class,'_1102050101_301_delete']);
Route::get('debtor/1102050101_301_daily_pdf',[DebtorController::class,'_1102050101_301_daily_pdf']);
Route::get('debtor/1102050101_301_indiv_excel',[DebtorController::class,'_1102050101_301_indiv_excel']);
Route::post('debtor/1102050101_301_average_receive',[DebtorController::class, '_1102050101_301_average_receive']);   
Route::match(['get','post'],'debtor/1102050101_303',[DebtorController::class,'_1102050101_303']);
Route::post('debtor/1102050101_303_confirm',[DebtorController::class,'_1102050101_303_confirm']);
Route::delete('debtor/1102050101_303_delete',[DebtorController::class,'_1102050101_303_delete']);
Route::put('debtor/1102050101_303/update/{vn}',[DebtorController::class,'_1102050101_303_update']);
Route::get('debtor/1102050101_303_daily_pdf',[DebtorController::class,'_1102050101_303_daily_pdf']);
Route::get('debtor/1102050101_303_indiv_excel',[DebtorController::class,'_1102050101_303_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_307',[DebtorController::class,'_1102050101_307']);
Route::post('debtor/1102050101_307_confirm',[DebtorController::class,'_1102050101_307_confirm']);
Route::post('debtor/1102050101_307_confirm_ip',[DebtorController::class,'_1102050101_307_confirm_ip']);
Route::delete('debtor/1102050101_307_delete',[DebtorController::class,'_1102050101_307_delete']);
Route::put('debtor/1102050101_307/update/{vn}',[DebtorController::class,'_1102050101_307_update']);
Route::get('debtor/1102050101_307_daily_pdf',[DebtorController::class,'_1102050101_307_daily_pdf']);
Route::get('debtor/1102050101_307_indiv_excel',[DebtorController::class,'_1102050101_307_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_309',[DebtorController::class,'_1102050101_309']);
Route::post('debtor/1102050101_309_confirm',[DebtorController::class,'_1102050101_309_confirm']);
Route::delete('debtor/1102050101_309_delete',[DebtorController::class,'_1102050101_309_delete']);
Route::put('debtor/1102050101_309/update/{vn}',[DebtorController::class,'_1102050101_309_update']);
Route::get('debtor/1102050101_309_daily_pdf',[DebtorController::class,'_1102050101_309_daily_pdf']);
Route::get('debtor/1102050101_309_indiv_excel',[DebtorController::class,'_1102050101_309_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_401',[DebtorController::class,'_1102050101_401']);
Route::post('debtor/1102050101_401_confirm',[DebtorController::class,'_1102050101_401_confirm']);
Route::delete('debtor/1102050101_401_delete',[DebtorController::class,'_1102050101_401_delete']);
Route::put('debtor/1102050101_401/update/{vn}',[DebtorController::class,'_1102050101_401_update']);
Route::get('debtor/1102050101_401_daily_pdf',[DebtorController::class,'_1102050101_401_daily_pdf']);
Route::get('debtor/1102050101_401_indiv_excel',[DebtorController::class,'_1102050101_401_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_501',[DebtorController::class,'_1102050101_501']);
Route::post('debtor/1102050101_501_confirm',[DebtorController::class,'_1102050101_501_confirm']);
Route::delete('debtor/1102050101_501_delete',[DebtorController::class,'_1102050101_501_delete']);
Route::put('debtor/1102050101_501/update/{vn}',[DebtorController::class,'_1102050101_501_update']);
Route::get('debtor/1102050101_501_daily_pdf',[DebtorController::class,'_1102050101_501_daily_pdf']);
Route::get('debtor/1102050101_501_indiv_excel',[DebtorController::class,'_1102050101_501_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_503',[DebtorController::class,'_1102050101_503']);
Route::post('debtor/1102050101_503_confirm',[DebtorController::class,'_1102050101_503_confirm']);
Route::delete('debtor/1102050101_503_delete',[DebtorController::class,'_1102050101_503_delete']);
Route::put('debtor/1102050101_503/update/{vn}',[DebtorController::class,'_1102050101_503_update']);
Route::get('debtor/1102050101_503_daily_pdf',[DebtorController::class,'_1102050101_503_daily_pdf']);
Route::get('debtor/1102050101_503_indiv_excel',[DebtorController::class,'_1102050101_503_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_701',[DebtorController::class,'_1102050101_701']);
Route::post('debtor/1102050101_701_confirm',[DebtorController::class,'_1102050101_701_confirm']);
Route::delete('debtor/1102050101_701_delete',[DebtorController::class,'_1102050101_701_delete']);
Route::get('debtor/1102050101_701_daily_pdf',[DebtorController::class,'_1102050101_701_daily_pdf']);
Route::get('debtor/1102050101_701_indiv_excel',[DebtorController::class,'_1102050101_701_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_702',[DebtorController::class,'_1102050101_702']);
Route::post('debtor/1102050101_702_confirm',[DebtorController::class,'_1102050101_702_confirm']);
Route::delete('debtor/1102050101_702_delete',[DebtorController::class,'_1102050101_702_delete']);
Route::get('debtor/1102050101_702_daily_pdf',[DebtorController::class,'_1102050101_702_daily_pdf']);
Route::get('debtor/1102050101_702_indiv_excel',[DebtorController::class,'_1102050101_702_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_106',[DebtorController::class,'_1102050102_106']);
Route::post('debtor/1102050102_106_confirm',[DebtorController::class,'_1102050102_106_confirm']);
Route::post('debtor/1102050102_106_confirm_iclaim',[DebtorController::class,'_1102050102_106_confirm_iclaim']);
Route::delete('debtor/1102050102_106_delete',[DebtorController::class,'_1102050102_106_delete']);
Route::put('debtor/1102050102_106/update/{vn}',[DebtorController::class,'_1102050102_106_update']);
Route::get('debtor/1102050102_106_daily_pdf',[DebtorController::class,'_1102050102_106_daily_pdf']);
Route::get('debtor/1102050102_106_indiv_excel',[DebtorController::class,'_1102050102_106_indiv_excel']);
Route::get('debtor/1102050102_106/tracking/{vn}',[DebtorController::class,'_1102050102_106_tracking']);
Route::post('debtor/1102050102_106/tracking_insert',[DebtorController::class,'_1102050102_106_tracking_insert']);
Route::put('debtor/1102050102_106/tracking_update/{tracking_id}',[DebtorController::class,'_1102050102_106_tracking_update']);
Route::match(['get','post'],'debtor/1102050102_108',[DebtorController::class,'_1102050102_108']);
Route::post('debtor/1102050102_108_confirm',[DebtorController::class,'_1102050102_108_confirm']);
Route::delete('debtor/1102050102_108_delete',[DebtorController::class,'_1102050102_108_delete']);
Route::put('debtor/1102050102_108/update/{vn}',[DebtorController::class,'_1102050102_108_update']);
Route::get('debtor/1102050102_108_daily_pdf',[DebtorController::class,'_1102050102_108_daily_pdf']);
Route::get('debtor/1102050102_108_indiv_excel',[DebtorController::class,'_1102050102_108_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_110',[DebtorController::class,'_1102050102_110']);
Route::post('debtor/1102050102_110_confirm',[DebtorController::class,'_1102050102_110_confirm']);
Route::delete('debtor/1102050102_110_delete',[DebtorController::class,'_1102050102_110_delete']);
Route::put('debtor/1102050102_110/update/{vn}',[DebtorController::class,'_1102050102_110_update']);
Route::get('debtor/1102050102_110_daily_pdf',[DebtorController::class,'_1102050102_110_daily_pdf']);
Route::get('debtor/1102050102_110_indiv_excel',[DebtorController::class,'_1102050102_110_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_602',[DebtorController::class,'_1102050102_602']);
Route::post('debtor/1102050102_602_confirm',[DebtorController::class,'_1102050102_602_confirm']);
Route::delete('debtor/1102050102_602_delete',[DebtorController::class,'_1102050102_602_delete']);
Route::put('debtor/1102050102_602/update/{vn}',[DebtorController::class,'_1102050102_602_update']);
Route::get('debtor/1102050102_602_daily_pdf',[DebtorController::class,'_1102050102_602_daily_pdf']);
Route::get('debtor/1102050102_602_indiv_excel',[DebtorController::class,'_1102050102_602_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_801',[DebtorController::class,'_1102050102_801']);
Route::post('debtor/1102050102_801_confirm',[DebtorController::class,'_1102050102_801_confirm']);
Route::delete('debtor/1102050102_801_delete',[DebtorController::class,'_1102050102_801_delete']);
Route::put('debtor/1102050102_801/update/{vn}',[DebtorController::class,'_1102050102_801_update']);
Route::get('debtor/1102050102_801_daily_pdf',[DebtorController::class,'_1102050102_801_daily_pdf']);
Route::get('debtor/1102050102_801_indiv_excel',[DebtorController::class,'_1102050102_801_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_803',[DebtorController::class,'_1102050102_803']);
Route::post('debtor/1102050102_803_confirm',[DebtorController::class,'_1102050102_803_confirm']);
Route::delete('debtor/1102050102_803_delete',[DebtorController::class,'_1102050102_803_delete']);
Route::put('debtor/1102050102_803/update/{vn}',[DebtorController::class,'_1102050102_803_update']);
Route::get('debtor/1102050102_803_daily_pdf',[DebtorController::class,'_1102050102_803_daily_pdf']);
Route::get('debtor/1102050102_803_indiv_excel',[DebtorController::class,'_1102050102_803_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_202',[DebtorController::class,'_1102050101_202']);
Route::post('debtor/1102050101_202_confirm',[DebtorController::class,'_1102050101_202_confirm']);
Route::delete('debtor/1102050101_202_delete',[DebtorController::class,'_1102050101_202_delete']);
Route::get('debtor/1102050101_202_daily_pdf',[DebtorController::class,'_1102050101_202_daily_pdf']);
Route::get('debtor/1102050101_202_indiv_excel',[DebtorController::class,'_1102050101_202_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_217',[DebtorController::class,'_1102050101_217']);
Route::post('debtor/1102050101_217_confirm',[DebtorController::class,'_1102050101_217_confirm']);
Route::delete('debtor/1102050101_217_delete',[DebtorController::class,'_1102050101_217_delete']);
Route::get('debtor/1102050101_217_daily_pdf',[DebtorController::class,'_1102050101_217_daily_pdf']);
Route::get('debtor/1102050101_217_indiv_excel',[DebtorController::class,'_1102050101_217_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_302',[DebtorController::class,'_1102050101_302']);
Route::post('debtor/1102050101_302_confirm',[DebtorController::class,'_1102050101_302_confirm']);
Route::delete('debtor/1102050101_302_delete',[DebtorController::class,'_1102050101_302_delete']);
Route::put('debtor/1102050101_302/update/{an}',[DebtorController::class,'_1102050101_302_update']);
Route::get('debtor/1102050101_302_daily_pdf',[DebtorController::class,'_1102050101_302_daily_pdf']);
Route::get('debtor/1102050101_302_indiv_excel',[DebtorController::class,'_1102050101_302_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_304',[DebtorController::class,'_1102050101_304']);
Route::post('debtor/1102050101_304_confirm',[DebtorController::class,'_1102050101_304_confirm']);
Route::delete('debtor/1102050101_304_delete',[DebtorController::class,'_1102050101_304_delete']);
Route::put('debtor/1102050101_304/update/{an}',[DebtorController::class,'_1102050101_304_update']);
Route::get('debtor/1102050101_304_daily_pdf',[DebtorController::class,'_1102050101_304_daily_pdf']);
Route::get('debtor/1102050101_304_indiv_excel',[DebtorController::class,'_1102050101_304_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_308',[DebtorController::class,'_1102050101_308']);
Route::post('debtor/1102050101_308_confirm',[DebtorController::class,'_1102050101_308_confirm']);
Route::delete('debtor/1102050101_308_delete',[DebtorController::class,'_1102050101_308_delete']);
Route::put('debtor/1102050101_308/update/{an}',[DebtorController::class,'_1102050101_308_update']);
Route::get('debtor/1102050101_308_daily_pdf',[DebtorController::class,'_1102050101_308_daily_pdf']);
Route::get('debtor/1102050101_308_indiv_excel',[DebtorController::class,'_1102050101_308_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_310',[DebtorController::class,'_1102050101_310']);
Route::post('debtor/1102050101_310_confirm',[DebtorController::class,'_1102050101_310_confirm']);
Route::delete('debtor/1102050101_310_delete',[DebtorController::class,'_1102050101_310_delete']);
Route::put('debtor/1102050101_310/update/{an}',[DebtorController::class,'_1102050101_310_update']);
Route::get('debtor/1102050101_310_daily_pdf',[DebtorController::class,'_1102050101_310_daily_pdf']);
Route::get('debtor/1102050101_310_indiv_excel',[DebtorController::class,'_1102050101_310_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_402',[DebtorController::class,'_1102050101_402']);
Route::post('debtor/1102050101_402_confirm',[DebtorController::class,'_1102050101_402_confirm']);
Route::delete('debtor/1102050101_402_delete',[DebtorController::class,'_1102050101_402_delete']);
Route::get('debtor/1102050101_402_daily_pdf',[DebtorController::class,'_1102050101_402_daily_pdf']);
Route::get('debtor/1102050101_402_indiv_excel',[DebtorController::class,'_1102050101_402_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_502',[DebtorController::class,'_1102050101_502']);
Route::post('debtor/1102050101_502_confirm',[DebtorController::class,'_1102050101_502_confirm']);
Route::delete('debtor/1102050101_502_delete',[DebtorController::class,'_1102050101_502_delete']);
Route::put('debtor/1102050101_502/update/{an}',[DebtorController::class,'_1102050101_502_update']);
Route::get('debtor/1102050101_502_daily_pdf',[DebtorController::class,'_1102050101_502_daily_pdf']);
Route::get('debtor/1102050101_502_indiv_excel',[DebtorController::class,'_1102050101_502_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_504',[DebtorController::class,'_1102050101_504']);
Route::post('debtor/1102050101_504_confirm',[DebtorController::class,'_1102050101_504_confirm']);
Route::delete('debtor/1102050101_504_delete',[DebtorController::class,'_1102050101_504_delete']);
Route::put('debtor/1102050101_504/update/{an}',[DebtorController::class,'_1102050101_504_update']);
Route::get('debtor/1102050101_504_daily_pdf',[DebtorController::class,'_1102050101_504_daily_pdf']);
Route::get('debtor/1102050101_504_indiv_excel',[DebtorController::class,'_1102050101_504_indiv_excel']);
Route::match(['get','post'],'debtor/1102050101_704',[DebtorController::class,'_1102050101_704']);
Route::post('debtor/1102050101_704_confirm',[DebtorController::class,'_1102050101_704_confirm']);
Route::delete('debtor/1102050101_704_delete',[DebtorController::class,'_1102050101_704_delete']);
Route::put('debtor/1102050101_704/update/{an}',[DebtorController::class,'_1102050101_704_update']);
Route::get('debtor/1102050101_704_daily_pdf',[DebtorController::class,'_1102050101_704_daily_pdf']);
Route::get('debtor/1102050101_704_indiv_excel',[DebtorController::class,'_1102050101_704_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_107',[DebtorController::class,'_1102050102_107']);
Route::post('debtor/1102050102_107_confirm',[DebtorController::class,'_1102050102_107_confirm']);
Route::post('debtor/1102050102_107_confirm_iclaim',[DebtorController::class,'_1102050102_107_confirm_iclaim']);
Route::delete('debtor/1102050102_107_delete',[DebtorController::class,'_1102050102_107_delete']);
Route::put('debtor/1102050102_107/update/{an}',[DebtorController::class,'_1102050102_107_update']);
Route::get('debtor/1102050102_107/tracking/{an}',[DebtorController::class,'_1102050102_107_tracking']);
Route::post('debtor/1102050102_107/tracking_insert',[DebtorController::class,'_1102050102_107_tracking_insert']);
Route::put('debtor/1102050102_107/tracking_update/{tracking_id}',[DebtorController::class,'_1102050102_107_tracking_update']);
Route::get('debtor/1102050102_107_daily_pdf',[DebtorController::class,'_1102050102_107_daily_pdf']);
Route::get('debtor/1102050102_107_indiv_excel',[DebtorController::class,'_1102050102_107_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_109',[DebtorController::class,'_1102050102_109']);
Route::post('debtor/1102050102_109_confirm',[DebtorController::class,'_1102050102_109_confirm']);
Route::delete('debtor/1102050102_109_delete',[DebtorController::class,'_1102050102_109_delete']);
Route::put('debtor/1102050102_109/update/{an}',[DebtorController::class,'_1102050102_109_update']);
Route::get('debtor/1102050102_109_daily_pdf',[DebtorController::class,'_1102050102_109_daily_pdf']);
Route::get('debtor/1102050102_109_indiv_excel',[DebtorController::class,'_1102050102_109_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_111',[DebtorController::class,'_1102050102_111']);
Route::post('debtor/1102050102_111_confirm',[DebtorController::class,'_1102050102_111_confirm']);
Route::delete('debtor/1102050102_111_delete',[DebtorController::class,'_1102050102_111_delete']);
Route::get('debtor/1102050102_111_daily_pdf',[DebtorController::class,'_1102050102_111_daily_pdf']);
Route::get('debtor/1102050102_111_indiv_excel',[DebtorController::class,'_1102050102_111_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_603',[DebtorController::class,'_1102050102_603']);
Route::post('debtor/1102050102_603_confirm',[DebtorController::class,'_1102050102_603_confirm']);
Route::delete('debtor/1102050102_603_delete',[DebtorController::class,'_1102050102_603_delete']);
Route::put('debtor/1102050102_603/update/{an}',[DebtorController::class,'_1102050102_603_update']);
Route::get('debtor/1102050102_603_daily_pdf',[DebtorController::class,'_1102050102_603_daily_pdf']);
Route::get('debtor/1102050102_603_indiv_excel',[DebtorController::class,'_1102050102_603_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_802',[DebtorController::class,'_1102050102_802']);
Route::post('debtor/1102050102_802_confirm',[DebtorController::class,'_1102050102_802_confirm']);
Route::delete('debtor/1102050102_802_delete',[DebtorController::class,'_1102050102_802_delete']);
Route::get('debtor/1102050102_802_daily_pdf',[DebtorController::class,'_1102050102_802_daily_pdf']);
Route::get('debtor/1102050102_802_indiv_excel',[DebtorController::class,'_1102050102_802_indiv_excel']);
Route::match(['get','post'],'debtor/1102050102_804',[DebtorController::class,'_1102050102_804']);
Route::post('debtor/1102050102_804_confirm',[DebtorController::class,'_1102050102_804_confirm']);
Route::delete('debtor/1102050102_804_delete',[DebtorController::class,'_1102050102_804_delete']);
Route::get('debtor/1102050102_804_daily_pdf',[DebtorController::class,'_1102050102_804_daily_pdf']);
Route::get('debtor/1102050102_804_indiv_excel',[DebtorController::class,'_1102050102_804_indiv_excel']);

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
