<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use App\Models\Stm_ucs;
use App\Models\Stm_ucsexcel;
use App\Models\Stm_ucs_kidney;
use App\Models\Stm_ucs_kidneyexcel;
use App\Models\Stm_ofc;
use App\Models\Stm_ofcexcel;
use App\Models\Stm_ofc_csop;
use App\Models\Stm_ofc_cipn;
use App\Models\Stm_ofc_kidney;
use App\Models\Stm_lgo;
use App\Models\Stm_lgoexcel;
use App\Models\Stm_lgo_kidney;
use App\Models\Stm_lgo_kidneyexcel;
use App\Models\Stm_sss_kidney;

class ImportController extends Controller
{
//Check Login
    public function __construct()
    {
        $this->middleware('auth');
    }

//stm_ucs-----------------------------------------------------------------------------------------------------
    public function stm_ucs(Request $request)
    {
        ini_set('max_execution_time', 300); // 5 นาที

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        /* ---------------- Query หลัก ---------------- */
        $stm_ucs = DB::select("
            SELECT
            IF(SUBSTRING(stm_filename,11) LIKE 'O%','OPD','IPD') AS dep,
            stm_filename,
            round_no,
            COUNT(DISTINCT repno) AS repno,
            COUNT(cid) AS count_cid,
            SUM(charge) AS charge,
            SUM(fund_ip_payrate) AS fund_ip_payrate,
            SUM(receive_total) AS receive_total,
            MAX(receive_no)   AS receive_no,
            MAX(receipt_date) AS receipt_date,
            MAX(receipt_by)   AS receipt_by
            FROM stm_ucs
            WHERE (CAST(SUBSTRING(stm_filename, LOCATE('25', stm_filename), 4) AS UNSIGNED)
                + (CAST(SUBSTRING(stm_filename, LOCATE('25', stm_filename) + 4, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY stm_filename, round_no            
            ORDER BY CASE WHEN round_no IS NOT NULL AND round_no <> '' 
                THEN (CAST(LEFT(round_no,2) AS UNSIGNED) + 2500) * 100
                + CAST(SUBSTRING(round_no,3,2) AS UNSIGNED)
                ELSE CAST(SUBSTRING( stm_filename, LOCATE('25', stm_filename), 6) AS UNSIGNED )END DESC,
            stm_filename DESC, dep DESC ", [$budget_year]);

        return view(
            'import.stm_ucs',
            compact('stm_ucs', 'budget_year_select', 'budget_year')
        );
    }

//stm_ucs_save--------------------------------------------------------------------------------------------------
    public function stm_ucs_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames  = [];

        /* ======================================================
        1) Clear staging
        ====================================================== */
        Stm_ucsexcel::truncate();

        /* ======================================================
        2) Read Excel → insert staging
        ====================================================== */
        foreach ($uploadedFiles as $file) {

            $file_name      = $file->getClientOriginalName();
            $allFileNames[] = $file_name;

            $spreadsheet = IOFactory::load($file->getRealPath());

            // ---------- Sheet2 : round_no ----------
            $sheetRound = $spreadsheet->setActiveSheetIndex(1);
            $round_no   = trim($sheetRound->getCell('A16')->getValue());

            // ---------- Sheet3 + Sheet4 ----------
            foreach ([2, 3] as $sheetIndex) {

                if (!isset($spreadsheet->getAllSheets()[$sheetIndex])) {
                    continue;
                }

                $sheet     = $spreadsheet->setActiveSheetIndex($sheetIndex);
                $row_limit = $sheet->getHighestDataRow();
                $startRow  = 15;

                $buffer = [];

                for ($row = $startRow; $row <= $row_limit; $row++) {

                    if (empty($sheet->getCell('A'.$row)->getValue())) {
                        continue;
                    }

                    // datetime adm
                    $adm = (string) $sheet->getCell('H'.$row)->getValue();
                    $datetimeadm = substr($adm,6,4).'-'.substr($adm,3,2).'-'.substr($adm,0,2).' '.substr($adm,11,8);

                    // datetime dch
                    $dch = (string) $sheet->getCell('I'.$row)->getValue();
                    $datetimedch = substr($dch,6,4).'-'.substr($dch,3,2).'-'.substr($dch,0,2).' '.substr($dch,11,8);

                    // clean comma S..AL
                    $cols = ['S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL'];
                    $clean = [];
                    foreach ($cols as $c) {
                        $clean[$c] = str_replace(',', '', $sheet->getCell($c.$row)->getValue());
                    }

                    $buffer[] = [
                        // ---- identity ----
                        'round_no'  => $round_no,
                        'repno'     => $sheet->getCell('A'.$row)->getValue(),
                        'no'        => $sheet->getCell('B'.$row)->getValue(),
                        'tran_id'   => $sheet->getCell('C'.$row)->getValue(),
                        'hn'        => $sheet->getCell('D'.$row)->getValue(),
                        'an'        => $sheet->getCell('E'.$row)->getValue(),
                        'cid'       => $sheet->getCell('F'.$row)->getValue(),
                        'pt_name'   => $sheet->getCell('G'.$row)->getValue(),

                        // ---- datetime ----
                        'datetimeadm' => $datetimeadm,
                        'vstdate'     => date('Y-m-d', strtotime($datetimeadm)),
                        'vsttime'     => date('H:i:s', strtotime($datetimeadm)),
                        'datetimedch' => $datetimedch,
                        'dchdate'     => date('Y-m-d', strtotime($datetimedch)),
                        'dchtime'     => date('H:i:s', strtotime($datetimedch)),

                        // ---- main ----
                        'maininscl' => $sheet->getCell('J'.$row)->getValue(),
                        'projcode'  => $sheet->getCell('K'.$row)->getValue(),
                        'charge'    => $sheet->getCell('L'.$row)->getValue(),

                        // ---- fund ----
                        'fund_ip_act'    => $sheet->getCell('M'.$row)->getValue(),
                        'fund_ip_adjrw'  => $sheet->getCell('N'.$row)->getValue(),
                        'fund_ip_ps'     => $sheet->getCell('O'.$row)->getValue(),
                        'fund_ip_ps2'    => $sheet->getCell('P'.$row)->getValue(),
                        'fund_ip_ccuf'   => $sheet->getCell('Q'.$row)->getValue(),
                        'fund_ip_adjrw2' => $sheet->getCell('R'.$row)->getValue(),

                        // ---- receive ----
                        'fund_ip_payrate'            => $clean['S'],
                        'fund_ip_salary'             => $clean['T'],
                        'fund_compensate_salary'     => $clean['U'],
                        'receive_op'                 => $clean['V'],
                        'receive_ip_compensate_cal'  => $clean['W'],
                        'receive_ip_compensate_pay'  => $clean['X'],
                        'receive_hc_hc'              => $clean['Y'],
                        'receive_hc_drug'            => $clean['Z'],
                        'receive_ae_ae'              => $clean['AA'],
                        'receive_ae_drug'            => $clean['AB'],
                        'receive_inst'               => $clean['AC'],
                        'receive_dmis_compensate_cal'=> $clean['AD'],
                        'receive_dmis_compensate_pay'=> $clean['AE'],
                        'receive_dmis_drug'          => $clean['AF'],
                        'receive_palliative'         => $clean['AG'],
                        'receive_dmishd'             => $clean['AH'],
                        'receive_pp'                 => $clean['AI'],
                        'receive_fs'                 => $clean['AJ'],
                        'receive_opbkk'              => $clean['AK'],
                        'receive_total'              => $clean['AL'],

                        // ---- other ----
                        'va'         => $sheet->getCell('AM'.$row)->getValue(),
                        'covid'      => $sheet->getCell('AN'.$row)->getValue(),
                        'resources'  => $sheet->getCell('AO'.$row)->getValue(),

                        'stm_filename' => $file_name,
                    ];

                    if (count($buffer) === 1000) {
                        Stm_ucsexcel::insert($buffer);
                        $buffer = [];
                    }
                }

                if ($buffer) {
                    Stm_ucsexcel::insert($buffer);
                }
            }

            unset($spreadsheet);
            gc_collect_cycles();
        }

        /* ======================================================
        3) Merge staging → stm_ucs (transaction สั้น)
        ====================================================== */
        DB::transaction(function () {

            Stm_ucsexcel::whereNotNull('charge')
                ->chunk(1000, function ($rows) {

                    foreach ($rows as $value) {

                        Stm_ucs::updateOrInsert(
                            [
                                'repno' => $value->repno,
                                'no'    => $value->no,
                            ],
                            [
                                'round_no'                    => $value->round_no,
                                'tran_id'                     => $value->tran_id,
                                'hn'                          => $value->hn,
                                'an'                          => $value->an,
                                'cid'                         => $value->cid,
                                'pt_name'                     => $value->pt_name,
                                'datetimeadm'                 => $value->datetimeadm,
                                'vstdate'                     => $value->vstdate,
                                'vsttime'                     => $value->vsttime,
                                'datetimedch'                 => $value->datetimedch,
                                'dchdate'                     => $value->dchdate,
                                'dchtime'                     => $value->dchtime,
                                'maininscl'                   => $value->maininscl,
                                'projcode'                    => $value->projcode,
                                'charge'                      => $value->charge,
                                'fund_ip_act'                 => $value->fund_ip_act,
                                'fund_ip_adjrw'               => $value->fund_ip_adjrw,
                                'fund_ip_ps'                  => $value->fund_ip_ps,
                                'fund_ip_ps2'                 => $value->fund_ip_ps2,
                                'fund_ip_ccuf'                => $value->fund_ip_ccuf,
                                'fund_ip_adjrw2'              => $value->fund_ip_adjrw2,
                                'fund_ip_payrate'             => $value->fund_ip_payrate,
                                'fund_ip_salary'              => $value->fund_ip_salary,
                                'fund_compensate_salary'      => $value->fund_compensate_salary,
                                'receive_op'                  => $value->receive_op,
                                'receive_ip_compensate_cal'   => $value->receive_ip_compensate_cal,
                                'receive_ip_compensate_pay'   => $value->receive_ip_compensate_pay,
                                'receive_hc_hc'               => $value->receive_hc_hc,
                                'receive_hc_drug'             => $value->receive_hc_drug,
                                'receive_ae_ae'               => $value->receive_ae_ae,
                                'receive_ae_drug'             => $value->receive_ae_drug,
                                'receive_inst'                => $value->receive_inst,
                                'receive_dmis_compensate_cal' => $value->receive_dmis_compensate_cal,
                                'receive_dmis_compensate_pay' => $value->receive_dmis_compensate_pay,
                                'receive_dmis_drug'           => $value->receive_dmis_drug,
                                'receive_palliative'          => $value->receive_palliative,
                                'receive_dmishd'              => $value->receive_dmishd,
                                'receive_pp'                  => $value->receive_pp,
                                'receive_fs'                  => $value->receive_fs,
                                'receive_opbkk'               => $value->receive_opbkk,
                                'receive_total'               => $value->receive_total,
                                'va'                          => $value->va,
                                'covid'                       => $value->covid,
                                'resources'                   => $value->resources,
                                'stm_filename'                => $value->stm_filename,
                            ]
                        );
                    }
                });
        });

        /* ======================================================
        4) Clear staging
        ====================================================== */
        Stm_ucsexcel::truncate();

        return redirect()
            ->route('stm_ucs')
            ->with('success', implode(', ', $allFileNames));
    }
//Create stm_ucs_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_ucs_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:20',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_ucs')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }
//stm_ucs_detail---------------------------------------------------------------------------------------------------------------------
public function stm_ucs_detail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        $stm_ucs_list=DB::select('
            SELECT IF(SUBSTRING(stm_filename,11) LIKE "O%","OPD","IPD") AS dep,
            stm_filename,repno,hn,an,pt_name,datetimeadm,datetimedch,projcode,fund_ip_adjrw,
            charge,receive_op,receive_ip_compensate_pay,fund_ip_payrate,receive_total,
            receive_hc_hc,receive_hc_drug,receive_ae_ae,receive_ae_drug,receive_inst,
            receive_palliative,receive_pp,receive_fs
            FROM stm_ucs 
            WHERE DATE(datetimeadm) BETWEEN ? AND ?
            AND SUBSTRING(stm_filename,11) LIKE "O%"
            GROUP BY stm_filename,repno,hn,datetimeadm 
            ORDER BY dep DESC,repno',[$start_date,$end_date]);

        $stm_ucs_list_ip=DB::select('
            SELECT IF(SUBSTRING(stm_filename,11) LIKE "O%","OPD","IPD") AS dep,
            stm_filename,repno,hn,an,pt_name,datetimeadm,datetimedch,projcode,fund_ip_adjrw,
            charge,receive_op,receive_ip_compensate_pay,fund_ip_payrate,receive_total,
            receive_hc_hc,receive_hc_drug,receive_ae_ae,receive_ae_drug,receive_inst,
            receive_palliative,receive_pp,receive_fs
            FROM stm_ucs 
            WHERE DATE(datetimedch) BETWEEN ? AND ?
            AND SUBSTRING(stm_filename,11) LIKE "I%"
            GROUP BY stm_filename,repno,hn,datetimedch 
            ORDER BY dep DESC,repno',[$start_date,$end_date]);

        return view('import.stm_ucs_detail',compact('start_date','end_date','stm_ucs_list','stm_ucs_list_ip'));
    }
//ucs_kidney------------------------------------------------------------------------------------------------------------------------
public function stm_ucs_kidney(Request $request)
    {  
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_ucs_kidney = DB::select("
            SELECT
                stm_filename ,
                round_no,
                COUNT(cid) AS count_cid,
                SUM(charge_total) AS charge_total,
                SUM(receive_total) AS receive_total,
                MAX(receive_no)   AS receive_no,
                MAX(receipt_date) AS receipt_date,
                MAX(receipt_by)   AS receipt_by
            FROM stm_ucs_kidney
            WHERE ((CAST(SUBSTRING(repno, 5, 2) AS UNSIGNED) + 2500)
                + (CAST(SUBSTRING(repno, 7, 2) AS UNSIGNED) >= 10) ) = ?
            GROUP BY repno
            ORDER BY (CAST(SUBSTRING(repno, 5, 2) AS UNSIGNED) + 2500) DESC,
                CAST(SUBSTRING(repno, 7, 2) AS UNSIGNED) DESC,
                repno", [$budget_year]);   

        return view('import.stm_ucs_kidney',compact('stm_ucs_kidney', 'budget_year_select', 'budget_year'));
    }

//ucs_kidney_save------------------------------------------------------------------------------------------------------------------
    public function stm_ucs_kidney_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames  = [];

        // ✅ TRUNCATE นอกทรานแซกชัน (ก่อนเริ่ม)
        Stm_ucs_kidneyexcel::truncate();

        DB::beginTransaction();
        try {
            // ---------- โหลดไฟล์ทั้งหมด ลงตาราง staging ----------
            foreach ($uploadedFiles as $the_file) {
                $file_name       = $the_file->getClientOriginalName();
                $allFileNames[]  = $file_name;

                $spreadsheet = IOFactory::load($the_file->getRealPath());
                $sheet       = $spreadsheet->setActiveSheetIndex(0);
                $row_limit   = $sheet->getHighestDataRow();

                $data = [];
                for ($row = 11; $row <= $row_limit; $row++) {
                    $adm = $sheet->getCell('K'.$row)->getValue();
                    $day  = substr($adm, 0, 2);
                    $mo   = substr($adm, 3, 2);
                    $year = substr($adm, 6, 4);
                    $tm   = substr($adm, 11, 8);
                    $datetimeadm = $year.'-'.$mo.'-'.$day.' '.$tm;

                    $data[] = [
                        'no'            => $sheet->getCell('A'.$row)->getValue(),
                        'repno'         => $sheet->getCell('C'.$row)->getValue(),
                        'hn'            => $sheet->getCell('E'.$row)->getValue(),
                        'an'            => $sheet->getCell('F'.$row)->getValue(),
                        'cid'           => $sheet->getCell('G'.$row)->getValue(),
                        'pt_name'       => $sheet->getCell('H'.$row)->getValue(),
                        'datetimeadm'   => $datetimeadm,
                        'hd_type'       => $sheet->getCell('N'.$row)->getValue(),
                        'charge_total'  => $sheet->getCell('P'.$row)->getValue(),
                        'receive_total' => $sheet->getCell('Q'.$row)->getValue(),
                        'note'          => $sheet->getCell('S'.$row)->getValue(),
                        'stm_filename'  => $file_name,
                    ];
                }

                foreach (array_chunk($data, 1000) as $chunk) {
                    Stm_ucs_kidneyexcel::insert($chunk);
                }
            }

            // ---------- merge เข้าตารางหลัก ----------
            $rows = Stm_ucs_kidneyexcel::whereNotNull('charge_total')->get();

            foreach ($rows as $value) {
                $exists = Stm_ucs_kidney::where('repno', $value->repno)
                            ->where('no', $value->no)
                            ->exists();

                if ($exists) {
                    Stm_ucs_kidney::where('repno', $value->repno)
                        ->where('no', $value->no)
                        ->update([
                            'round_no'      => $value->repno,
                            'datetimeadm'   => $value->datetimeadm,
                            'charge_total'  => $value->charge_total,
                            'receive_total' => $value->receive_total,
                            'stm_filename'  => $value->stm_filename,
                        ]);
                } else {
                    Stm_ucs_kidney::create([
                        'round_no'      => $value->repno,
                        'no'            => $value->no,
                        'repno'         => $value->repno,
                        'hn'            => $value->hn,
                        'an'            => $value->an,
                        'cid'           => $value->cid,
                        'pt_name'       => $value->pt_name,
                        'datetimeadm'   => $value->datetimeadm,
                        'hd_type'       => $value->hd_type,
                        'charge_total'  => $value->charge_total,
                        'receive_total' => $value->receive_total,
                        'note'          => $value->note,
                        'stm_filename'  => $value->stm_filename,
                    ]);
                }
            }

            DB::commit();

            // ✅ TRUNCATE นอกทรานแซกชัน (หลัง commit แล้ว)
            Stm_ucs_kidneyexcel::truncate();

            return redirect()
                ->route('stm_ucs_kidney')
                ->with('success', implode(', ', $allFileNames));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('There was a problem uploading the data!');
        }
    }
 //Create stm_ucs_kidney_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_ucs_kidney_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:30',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_ucs_kidney')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }      
//stm_ucs_kidneydetail------------------------------------------------------------------------------------------------------------------
public function stm_ucs_kidneydetail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        $stm_ucs_kidney_list=DB::select('
            SELECT stm_filename,repno,hn,an,cid,pt_name,datetimeadm,hd_type,charge_total,receive_total,note 
            FROM stm_ucs_kidney WHERE DATE(datetimeadm) BETWEEN ? AND ?
            GROUP BY repno,cid,hd_type,datetimeadm ORDER BY cid,datetimeadm',[$start_date,$end_date]);

        return view('import.stm_ucs_kidneydetail',compact('start_date','end_date','stm_ucs_kidney_list'));
    }
//stm_ofc-----------------------------------------------------------------------------------------------------------------------------
public function stm_ofc(Request $request)
    {  
        ini_set('max_execution_time', 300);

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        /* ---------------- Query หลัก (เหมือน UCS) ---------------- */
        $stm_ofc = DB::select("
            SELECT
            IF(SUBSTRING(stm_filename,11) LIKE 'O%','OPD','IPD') AS dep,
            stm_filename,
            round_no,
            COUNT(DISTINCT repno) AS repno,
            COUNT(cid) AS count_cid,
            SUM(adjrw) AS sum_adjrw,
            SUM(charge) AS sum_charge,
            SUM(act) AS sum_act,
            SUM(receive_room) AS sum_receive_room,
            SUM(receive_instument) AS sum_receive_instument,
            SUM(receive_drug) AS sum_receive_drug,
            SUM(receive_treatment) AS sum_receive_treatment,
            SUM(receive_car) AS sum_receive_car,
            SUM(receive_waitdch) AS sum_receive_waitdch,
            SUM(receive_other) AS sum_receive_other,
            SUM(receive_total) AS sum_receive_total,
            MAX(receive_no)   AS receive_no,
            MAX(receipt_date) AS receipt_date,
            MAX(receipt_by)   AS receipt_by
            FROM stm_ofc
            WHERE (CAST(SUBSTRING(stm_filename, LOCATE('20', stm_filename), 4) AS UNSIGNED) + 543
				+ (CAST(SUBSTRING(stm_filename, LOCATE('20', stm_filename) + 4, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY stm_filename, round_no
            ORDER BY CAST(SUBSTRING(stm_filename, LOCATE('20', stm_filename), 6) AS UNSIGNED ) DESC,   
				CASE WHEN round_no IS NOT NULL AND round_no <> ''
				THEN (CAST(LEFT(round_no,2) AS UNSIGNED) + 2500) * 100
				+ CAST(SUBSTRING(round_no,3,2) AS UNSIGNED)  ELSE 0 END DESC,
				stm_filename DESC, dep DESC ", [$budget_year]);

        return view('import.stm_ofc',compact('stm_ofc', 'budget_year_select', 'budget_year'));
    }

//stm_ofc_save---------------------------------------------------------------------------------------------------------------------------
    public function stm_ofc_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ✅ เปลี่ยน validation ให้รองรับหลายไฟล์และจำกัดไม่เกิน 5
        $this->validate($request, [
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames  = [];

        // ✅ TRUNCATE นอกทรานแซกชัน (ก่อนเริ่มทำงาน)
        Stm_ofcexcel::truncate();

        DB::beginTransaction();
        try {
            // ------------------ อ่านทุกไฟล์ -> ใส่ staging ------------------
            foreach ($uploadedFiles as $the_file) {
                $file_name       = $the_file->getClientOriginalName();
                $allFileNames[]  = $file_name;

                $spreadsheet = IOFactory::load($the_file->getRealPath());
                $sheet       = $spreadsheet->setActiveSheetIndex(0);
                $row_limit   = $sheet->getHighestDataRow();

                // ✅ round_no อยู่ที่ A6 เริ่มอักษรที่ 12 จากซ้าย
                $roundText = $sheet->getCell('A6')->getCalculatedValue();
                $round_no  = trim(mb_substr((string) $roundText, 13, null, 'UTF-8'));  

                $data = [];
                for ($row = 12; $row <= $row_limit; $row++) {

                    // รูปแบบเดิมของคุณ (G,H): dd/mm/yyyy HH:MM:SS
                    $adm  = $sheet->getCell('G'.$row)->getValue();
                    $day  = substr($adm, 0, 2);
                    $mo   = substr($adm, 3, 2);
                    $year = substr($adm, 7, 4);
                    $tm   = substr($adm, 12, 8);
                    $datetimeadm = $year.'-'.$mo.'-'.$day.' '.$tm;

                    $dch     = $sheet->getCell('H'.$row)->getValue();
                    $dchday  = substr($dch, 0, 2);
                    $dchmo   = substr($dch, 3, 2);
                    $dchyear = substr($dch, 7, 4);
                    $dchtime = substr($dch, 12, 8);
                    $datetimedch = $dchyear.'-'.$dchmo.'-'.$dchday.' '.$dchtime;

                    $data[] = [
                        'round_no'              => $round_no,
                        'repno'                 => $sheet->getCell('A'.$row)->getValue(),
                        'no'                    => $sheet->getCell('B'.$row)->getValue(),
                        'hn'                    => $sheet->getCell('C'.$row)->getValue(),
                        'an'                    => $sheet->getCell('D'.$row)->getValue(),
                        'cid'                   => $sheet->getCell('E'.$row)->getValue(),
                        'pt_name'               => $sheet->getCell('F'.$row)->getValue(),
                        'datetimeadm'           => $datetimeadm,
                        'vstdate'               => date('Y-m-d', strtotime($datetimeadm)),
                        'vsttime'               => date('H:i:s', strtotime($datetimeadm)),
                        'datetimedch'           => $datetimedch,
                        'dchdate'               => date('Y-m-d', strtotime($datetimedch)),
                        'dchtime'               => date('H:i:s', strtotime($datetimedch)),
                        'projcode'              => $sheet->getCell('I'.$row)->getValue(),
                        'adjrw'                 => $sheet->getCell('J'.$row)->getValue(),
                        'charge'                => $sheet->getCell('K'.$row)->getValue(),
                        'act'                   => $sheet->getCell('L'.$row)->getValue(),
                        'receive_room'          => $sheet->getCell('M'.$row)->getValue(),
                        'receive_instument'     => $sheet->getCell('N'.$row)->getValue(),
                        'receive_drug'          => $sheet->getCell('O'.$row)->getValue(),
                        'receive_treatment'     => $sheet->getCell('P'.$row)->getValue(),
                        'receive_car'           => $sheet->getCell('Q'.$row)->getValue(),
                        'receive_waitdch'       => $sheet->getCell('R'.$row)->getValue(),
                        'receive_other'         => $sheet->getCell('S'.$row)->getValue(),
                        'receive_total'         => $sheet->getCell('T'.$row)->getValue(),
                        'stm_filename'          => $file_name,
                    ];
                }

                foreach (array_chunk($data, 1000) as $chunk) {
                    Stm_ofcexcel::insert($chunk);
                }
            }

            // ------------------ merge -> ตารางหลัก ------------------
            $stm_ofcexcel = Stm_ofcexcel::whereNotNull('charge')
                ->where('charge', '<>', 'เรียกเก็บ')
                ->get();

            foreach ($stm_ofcexcel as $value) {
                $exists = Stm_ofc::where('repno', $value->repno)
                            ->where('no', $value->no)
                            ->exists();

                if ($exists) {
                    Stm_ofc::where('repno', $value->repno)
                        ->where('no', $value->no)
                        ->update([
                            'round_no'          => $value->round_no,
                            'datetimeadm'       => $value->datetimeadm,
                            'vstdate'           => $value->vstdate,
                            'vsttime'           => $value->vsttime,
                            'datetimedch'       => $value->datetimedch,
                            'dchdate'           => $value->dchdate,
                            'dchtime'           => $value->dchtime,
                            'charge'            => $value->charge,
                            'receive_room'      => $value->receive_room,
                            'receive_instument' => $value->receive_instument,
                            'receive_drug'      => $value->receive_drug,
                            'receive_treatment' => $value->receive_treatment,
                            'receive_car'       => $value->receive_car,
                            'receive_waitdch'   => $value->receive_waitdch,
                            'receive_other'     => $value->receive_other,
                            'receive_total'     => $value->receive_total,
                            'stm_filename'      => $value->stm_filename,
                        ]);
                } else {
                    Stm_ofc::create([
                        'round_no'          => $value->round_no,
                        'repno'              => $value->repno,
                        'no'                 => $value->no,
                        'hn'                 => $value->hn,
                        'an'                 => $value->an,
                        'cid'                => $value->cid,
                        'pt_name'            => $value->pt_name,
                        'datetimeadm'        => $value->datetimeadm,
                        'vstdate'            => $value->vstdate,
                        'vsttime'            => $value->vsttime,
                        'datetimedch'        => $value->datetimedch,
                        'dchdate'            => $value->dchdate,
                        'dchtime'            => $value->dchtime,
                        'projcode'           => $value->projcode,
                        'adjrw'              => $value->adjrw,
                        'charge'             => $value->charge,
                        'act'                => $value->act,
                        'receive_room'       => $value->receive_room,
                        'receive_instument'  => $value->receive_instument,
                        'receive_drug'       => $value->receive_drug,
                        'receive_treatment'  => $value->receive_treatment,
                        'receive_car'        => $value->receive_car,
                        'receive_waitdch'    => $value->receive_waitdch,
                        'receive_other'      => $value->receive_other,
                        'receive_total'      => $value->receive_total,
                        'stm_filename'       => $value->stm_filename,
                    ]);
                }
            }

            DB::commit();

            // ✅ TRUNCATE นอกทรานแซกชัน (หลัง commit)
            Stm_ofcexcel::truncate();

            return redirect()
                ->route('stm_ofc')
                ->with('success', implode(', ', $allFileNames));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('There was a problem uploading the data!');
        }
    }
//Create stm_ofc_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_ofc_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:20',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_ofc')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }
//stm_ofc_detail----------------------------------------------------------------------------------------------------------------
public function stm_ofc_detail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        $stm_ofc_list=DB::select('
            SELECT IF(SUBSTRING(stm_filename,11) LIKE "O%","OPD","IPD") AS dep,stm_filename,repno,
            hn,an,pt_name,datetimeadm,datetimedch,adjrw,charge,act,receive_room,receive_instument,
            receive_drug,receive_treatment,receive_car,receive_waitdch,receive_other,receive_total
            FROM stm_ofc
            WHERE DATE(datetimeadm) BETWEEN ? AND ?
            AND SUBSTRING(stm_filename,11) LIKE "O%"
            GROUP BY stm_filename,repno,hn,datetimeadm 
            ORDER BY dep DESC,repno',[$start_date,$end_date]);

        $stm_ofc_list_ip=DB::select('
            SELECT IF(SUBSTRING(stm_filename,11) LIKE "O%","OPD","IPD") AS dep,stm_filename,repno,
            hn,an,pt_name,datetimeadm,datetimedch,adjrw,charge,act,receive_room,receive_instument,
            receive_drug,receive_treatment,receive_car,receive_waitdch,receive_other,receive_total
            FROM stm_ofc 
            WHERE DATE(datetimedch) BETWEEN ? AND ?
            AND SUBSTRING(stm_filename,11) LIKE "I%"
            GROUP BY stm_filename,repno,hn,datetimeadm 
            ORDER BY dep DESC,repno',[$start_date,$end_date]);

        return view('import.stm_ofc_detail',compact('start_date','end_date','stm_ofc_list','stm_ofc_list_ip'));
    }
//stm_ofc_csop--------------------------------------------------------------------------------------------------------------
    public function stm_ofc_csop(Request $request)
    {  
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_ofc_csop = DB::select("
            SELECT
                stm_filename,
                station,
                COUNT(*) AS count_no,
                round_no,
                SUM(amount) AS amount,
                MAX(receive_no)   AS receive_no,
                MAX(receipt_date) AS receipt_date,
                MAX(receipt_by)   AS receipt_by
            FROM stm_ofc_csop
            WHERE (CAST(LEFT(RIGHT(round_no, 8), 4) AS UNSIGNED) + 543
                + (CAST(SUBSTRING(RIGHT(round_no, 8), 5, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY round_no
            ORDER BY round_no DESC,CAST(LEFT(RIGHT(round_no, 8), 6) AS UNSIGNED) DESC, round_no", [$budget_year]);      

        return view('import.stm_ofc_csop',compact('stm_ofc_csop', 'budget_year_select', 'budget_year'));
    }

//stm_ofc_csop_save-------------------------------------------------------------------------------------------------------------
    public function stm_ofc_csop_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:zip'
        ]);

        $docCounts = []; // [STMdoc => count]

        DB::beginTransaction();

        try {

            foreach ($request->file('files') as $zipFile) {

                $zip = new \ZipArchive;
                if ($zip->open($zipFile->getPathname()) !== true) {
                    continue;
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {

                    $innerName = $zip->statIndex($i)['name'];

                    // ใช้เฉพาะ COCDSTM.xml
                    if (!preg_match('/COCDSTM.*\.xml$/i', $innerName)) {
                        continue;
                    }

                    $xmlString = $zip->getFromIndex($i);
                    if (!$xmlString) {
                        continue;
                    }

                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($xmlString);
                    if ($xml === false || $xml->getName() !== 'STMSTM') {
                        continue;
                    }

                    if (!isset($xml->TBills)) {
                        continue;
                    }

                    $STMdoc = trim((string)$xml->STMdoc);

                    if (!isset($docCounts[$STMdoc])) {
                        $docCounts[$STMdoc] = 0;
                    }

                    /*
                    * ====================================================
                    *  COCDSTM มี TBills ได้หลายชุด (OPD + HD)
                    * ====================================================
                    */
                    foreach ($xml->TBills as $tbills) {

                        if (!isset($tbills->TBill)) {
                            continue;
                        }

                        foreach ($tbills->TBill as $bill) {

                            $invno = trim((string)$bill->invno);
                            if ($invno === '') {
                                continue;
                            }

                            // วันที่รับบริการ
                            $vstdate = null;
                            $vsttime = null;
                            if ((string)$bill->dttran !== '') {
                                try {
                                    $dt = Carbon::parse((string)$bill->dttran);
                                    $vstdate = $dt->toDateString();
                                    $vsttime = $dt->format('H:i:s');
                                } catch (\Exception $e) {}
                            }

                            // ExtP
                            $extpCode   = null;
                            $extpAmount = 0;
                            if (isset($bill->ExtP)) {
                                $extpCode   = (string)$bill->ExtP['code'];
                                $extpAmount = (float)$bill->ExtP;
                            }

                            DB::table('stm_ofc_csop')->updateOrInsert(
                                [
                                    'round_no' => $STMdoc,
                                    'invno'    => $invno,
                                    'station'  => (string)$bill->station,
                                    'sys'      => (string)$bill->sys,
                                    'hdflag'   => (string)$bill->HDflag,
                                ],
                                [
                                    'stm_type'     => 'COCDSTM',
                                    'stm_filename' => $innerName,
                                    'hcode'        => (string)$xml->hcode,
                                    'hname'        => (string)$xml->hname,
                                    'acc_period'   => (string)$xml->AccPeriod,
                                    'hreg'         => (string)$bill->hreg,
                                    'hn'           => (string)$bill->hn,
                                    'pt_name'      => (string)$bill->namepat,
                                    'vstdate'      => $vstdate,
                                    'vsttime'      => $vsttime,
                                    'amount'       => (float)$bill->amount,
                                    'paid'         => (float)$bill->paid,
                                    'extp_code'    => $extpCode,
                                    'extp_amount'  => $extpAmount,
                                    'rid'          => (string)$bill->rid,
                                    'cstat'        => (string)$bill->cstat,
                                    'updated_at'   => now(),
                                ]
                            );

                            $docCounts[$STMdoc]++;
                        }
                    }
                }

                $zip->close();
            }

            DB::commit();

            // ===== สรุปผล =====
            $lines = [];
            foreach ($docCounts as $doc => $count) {
                $lines[] = $doc . ' (' . number_format($count) . ' รายการ)';
            }

            return redirect()
                ->route('stm_ofc_csop')
                ->with('success', implode("\n", $lines));

        } catch (\Throwable $e) {

            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }
//Create stm_ofc_csop_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_ofc_csop_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:30',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_ofc_csop')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }              
//stm_ofc_csopdetail-------------------------------------------------------------------------------------------------------------------
    public function stm_ofc_csopdetail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        $stm_ofc_csop_list=DB::select('
            SELECT stm_filename,hcode,hname,round_no,station,sys,hreg,hn,pt_name,invno,
            vstdate,vsttime,paid,rid,amount,receive_no
            FROM stm_ofc_csop  
            WHERE vstdate BETWEEN ? AND ?
            ORDER BY station ,round_no',[$start_date,$end_date]);

        return view('import.stm_ofc_csopdetail',compact('start_date','end_date','stm_ofc_csop_list'));
    }
//stm_ofc_cipn--------------------------------------------------------------------------------------------------------------
    public function stm_ofc_cipn(Request $request)
    {  
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_ofc_cipn = DB::select("
            SELECT
            stm_filename,
            COUNT(*) AS count_no,
            round_no,
            SUM(gtotal) AS gtotal,
            MAX(receive_no)   AS receive_no,
            MAX(receipt_date) AS receipt_date,
            MAX(receipt_by)   AS receipt_by
            FROM stm_ofc_cipn
            WHERE (CAST(LEFT(RIGHT(round_no, 6), 4) AS UNSIGNED) + 543
                + (CAST(RIGHT(round_no, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY round_no
            ORDER BY CAST(RIGHT(round_no, 6) AS UNSIGNED) DESC, round_no DESC", [$budget_year]);     

        return view('import.stm_ofc_cipn',compact('stm_ofc_cipn', 'budget_year_select', 'budget_year'));
    }

//stm_ofc_cipn_save-------------------------------------------------------------------------------------------------------------
    public function stm_ofc_cipn_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'files'   => 'required|array',
            'files.*' => 'file|mimes:zip'
        ]);

        // ===== ตัวนับผลลัพธ์ =====
        $docCounts = [];

        DB::beginTransaction();

        try {

            foreach ($request->file('files') as $zipFile) {

                $zip = new \ZipArchive;
                if ($zip->open($zipFile->getPathname()) !== true) {
                    continue;
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {

                    $innerName = $zip->statIndex($i)['name'];
                    if (!preg_match('/\.xml$/i', $innerName)) continue;

                    $xmlString = $zip->getFromIndex($i);
                    if (!$xmlString) continue;

                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($xmlString);
                    if ($xml === false) continue;

                    // ===== ต้องเป็น STMLIST =====
                    if ($xml->getName() !== 'STMLIST') continue;

                    // ===== round_no (stmno) =====
                    $round_no = (string)($xml->stmdat->stmno ?? '');
                    if ($round_no === '') continue;

                    // init counter
                    if (!isset($docCounts[$round_no])) {
                        $docCounts[$round_no] = 0;
                    }

                    // ===== loop รายผู้ป่วย =====
                    foreach ($xml->thismonip as $row) {

                        $an = trim((string)$row->an);
                        if ($an === '') continue;

                        // วันที่
                        $datedsc = null;
                        if ((string)$row->datedsc !== '') {
                            try {
                                $datedsc = Carbon::parse((string)$row->datedsc)->toDateString();
                            } catch (\Exception $e) {}
                        }

                        DB::table('stm_ofc_cipn')->updateOrInsert(
                            [
                                'round_no' => $round_no,
                                'an'       => $an,
                            ],
                            [
                                'stm_filename' => basename($innerName),
                                'rid'      => (string)$row->rid ?: null,
                                'namepat'  => (string)$row->namepat,
                                'datedsc'  => $datedsc,
                                'ptype'    => (string)$row->ptype ?: null,
                                'drg'      => (string)$row->drg ?: null,
                                'adjrw'    => (float)$row->adjrw ?: 0,
                                'amreimb'  => (float)$row->amreimb ?: 0,
                                'amlim'    => (float)$row->amlim ?: 0,
                                'pamreim'  => (float)$row->pamreim ?: 0,
                                'gtotal'   => (float)$row->gtotal ?: 0,
                                'updated_at' => now(),
                            ]
                        );

                        // ===== นับผล =====
                        $docCounts[$round_no]++;
                    }
                }

                $zip->close();
            }

            DB::commit();

            // ===== สรุปผล =====
            $lines = [];
            foreach ($docCounts as $doc => $count) {
                $lines[] = $doc . ' (' . number_format($count) . ' รายการ)';
            }

            return redirect()
                ->route('stm_ofc_cipn')  
                ->with('success', implode("\n", $lines));

        } catch (\Throwable $e) {

            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }

//Create stm_ofc_cipn_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_ofc_cipn_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:30',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_ofc_cipn')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }              
//stm_ofc_cipndetail-------------------------------------------------------------------------------------------------------------------
    public function stm_ofc_cipndetail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        $stm_ofc_cipn_list=DB::select('
            SELECT stm_filename,an, namepat,datedsc,ptype,drg,adjrw,amreimb,amlim,pamreim,gtotal,rid,receive_no
            FROM stm_ofc_cipn  
            WHERE datedsc BETWEEN ? AND ?
            ORDER BY datedsc, round_no',[$start_date,$end_date]);

        return view('import.stm_ofc_cipndetail',compact('start_date','end_date','stm_ofc_cipn_list'));
    }

//stm_ofc_kidney--------------------------------------------------------------------------------------------------------------
    public function stm_ofc_kidney(Request $request)
    {  
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_ofc_kidney = DB::select("
            SELECT
                stmdoc,
                station,
                COUNT(*) AS count_no,
                round_no,
                SUM(amount) AS amount,
                MAX(receive_no)   AS receive_no,
                MAX(receipt_date) AS receipt_date,
                MAX(receipt_by)   AS receipt_by
            FROM stm_ofc_kidney
            WHERE (CAST(LEFT(RIGHT(stmdoc, 8), 4) AS UNSIGNED) + 543
                + (CAST(SUBSTRING(RIGHT(stmdoc, 8), 5, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY stmdoc
            ORDER BY stmdoc DESC,CAST(LEFT(RIGHT(stmdoc, 8), 6) AS UNSIGNED) DESC, stmdoc", [$budget_year]);      

        return view('import.stm_ofc_kidney',compact('stm_ofc_kidney', 'budget_year_select', 'budget_year'));
    }

//stm_ofc_kidney_save-------------------------------------------------------------------------------------------------------------
    public function stm_ofc_kidney_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:zip'
        ]);

        $uploadedFiles = $request->file('files');
        $docNames = [];

        DB::beginTransaction();
        try {
            foreach ($uploadedFiles as $zipFile) {
                $zip = new \ZipArchive;
                if ($zip->open($zipFile->getRealPath()) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        $innerName = $stat['name'];

                        // สนใจเฉพาะไฟล์ .xml ด้านใน
                        if (strtolower(pathinfo($innerName, PATHINFO_EXTENSION)) !== 'xml') {
                            continue;
                        }

                        $xmlString = $zip->getFromIndex($i);
                        if (!$xmlString) continue;

                        $xmlObject = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
                        if ($xmlObject === false) continue;

                        $json   = json_encode($xmlObject);
                        $result = json_decode($json, true);

                        $hcode  = $result['hcode']  ?? null;
                        $hname  = $result['hname']  ?? null;
                        $STMdoc = $result['STMdoc'] ?? $innerName;

                        // ✅ นำเข้าเฉพาะไฟล์ STM เท่านั้น
                            if (stripos($STMdoc, 'STM') === false) {
                                continue;
                            }

                        $docNames[] = $STMdoc;

                        $TBills = $result['TBills']['TBill'] ?? [];
                        if (!empty($TBills) && array_keys($TBills) !== range(0, count($TBills) - 1)) {
                            $TBills = [$TBills];
                        }

                        foreach ($TBills as $bill) {
                            $hn     = $bill['hn'] ?? null;
                            $dttran = $bill['dttran'] ?? null;
                            $dttdate = null; $dtttime = null;
                            if ($dttran && strpos($dttran, 'T') !== false) {
                                [$dttdate, $dtttime] = explode('T', $dttran, 2);
                            }

                            if ($hn && $dttdate) {
                                $exists = Stm_ofc_kidney::where('hn', $hn)
                                            ->where('vstdate', $dttdate)
                                            ->exists();

                                $dataRow = [
                                    'round_no'  => $STMdoc,
                                    'hcode'     => $hcode,
                                    'hname'     => $hname,
                                    'stmdoc'    => $STMdoc,
                                    'station'   => $bill['station'] ?? null,
                                    'hreg'      => $bill['hreg'] ?? null,
                                    'hn'        => $hn,
                                    'invno'     => $bill['invno'] ?? null,
                                    'dttran'    => $dttran,
                                    'vstdate'   => $dttdate,
                                    'vsttime'   => $dtttime,
                                    'amount'    => $bill['amount'] ?? null,
                                    'paid'      => $bill['paid'] ?? null,
                                    'rid'       => $bill['rid'] ?? null,
                                    'hdflag'    => $bill['HDflag'] ?? ($bill['hdflag'] ?? null),
                                ];

                                if ($exists) {
                                    Stm_ofc_kidney::where('hn', $hn)
                                        ->where('vstdate', $dttdate)
                                        ->update($dataRow);
                                } else {
                                    Stm_ofc_kidney::insert($dataRow);
                                }
                            }
                        }
                    }
                    $zip->close();
                }
            }

            DB::commit();

            return redirect()
                ->route('stm_ofc_kidney')
                ->with('success', implode(', ', $docNames));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('There was a problem uploading the data!');
        }
    }
//Create stm_ofc_kidney_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_ofc_kidney_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:30',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_ofc_kidney')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }              
//stm_ofc_kidneydetail-------------------------------------------------------------------------------------------------------------------
public function stm_ofc_kidneydetail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        $stm_ofc_kidney_list=DB::select('
            SELECT hcode,hname,stmdoc,station,hreg,hn,invno,dttran,paid,rid,amount,hdflag
            FROM stm_ofc_kidney  WHERE DATE(dttran) BETWEEN ? AND ?
            ORDER BY station ,stmdoc',[$start_date,$end_date]);

        return view('import.stm_ofc_kidneydetail',compact('start_date','end_date','stm_ofc_kidney_list'));
    }
//stm_lgo-----------------------------------------------------------------------------------------------------------------------------
    public function stm_lgo(Request $request)
    {  
        ini_set('max_execution_time', 300);

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_lgo = DB::select("
            SELECT 
            IF(SUBSTRING(stm_filename,14) LIKE 'O%','OPD','IPD') AS dep,
            stm_filename,
            round_no,
            COUNT(DISTINCT repno)       AS repno,
            COUNT(cid)                  AS count_cid,
            SUM(adjrw)                  AS sum_adjrw,
            SUM(payrate)                AS sum_payrate,
            SUM(charge_treatment)       AS sum_charge_treatment,
            SUM(compensate_treatment)   AS sum_compensate_treatment,
            SUM(case_iplg)              AS sum_case_iplg,
            SUM(case_oplg)              AS sum_case_oplg,
            SUM(case_palg)              AS sum_case_palg,
            SUM(case_inslg)             AS sum_case_inslg,
            SUM(case_otlg)              AS sum_case_otlg,
            SUM(case_pp)                AS sum_case_pp,
            SUM(case_drug)              AS sum_case_drug,
            MAX(receive_no)             AS receive_no,
            MAX(receipt_date)           AS receipt_date,
            MAX(receipt_by)             AS receipt_by
            FROM stm_lgo
            WHERE (CAST(LEFT(SUBSTRING_INDEX(SUBSTRING_INDEX(stm_filename, '_', -2), '_', 1 ), 4) AS UNSIGNED)  
				+ (CAST(SUBSTRING(SUBSTRING_INDEX(SUBSTRING_INDEX(stm_filename, '_', -2),'_', 1), 5, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY stm_filename, round_no
            ORDER BY CAST(LEFT(SUBSTRING_INDEX(SUBSTRING_INDEX(stm_filename, '_', -2),'_', 1), 6) AS UNSIGNED) DESC,
				CASE WHEN round_no IS NOT NULL AND round_no <> ''
				THEN (CAST(LEFT(round_no,2) AS UNSIGNED) + 2500) * 100
				+ CAST(SUBSTRING(round_no,3,2) AS UNSIGNED) ELSE 0 END DESC,    
				stm_filename DESC,round_no DESC; ", [$budget_year]);

        return view('import.stm_lgo',compact('stm_lgo', 'budget_year_select', 'budget_year'));
    }

//stm_lgo_save------------------------------------------------------------------------------------------------------------------------
    public function stm_lgo_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ✅ รองรับหลายไฟล์ จำกัดไม่เกิน 5
        $this->validate($request, [
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:xls,xlsx',
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames  = [];

        // ✅ ล้าง staging นอกทรานแซกชัน (ก่อนเริ่ม)
        Stm_lgoexcel::truncate();

        DB::beginTransaction();
        try {

            // ------------------ อ่านทุกไฟล์ -> ใส่ staging ------------------
            foreach ($uploadedFiles as $the_file) {
                $file_name       = $the_file->getClientOriginalName();
                $allFileNames[]  = $file_name;

                $spreadsheet = IOFactory::load($the_file->getRealPath());
                $sheet       = $spreadsheet->setActiveSheetIndex(0);
                $row_limit   = $sheet->getHighestDataRow();

                $data = [];
                for ($row = 8; $row <= $row_limit; $row++) {

                    // I,J เป็น datetime แบบ dd/mm/YYYY HH:MM:SS (ตามโค้ดเดิม)
                    $adm  = $sheet->getCell('I'.$row)->getValue();
                    $day  = substr($adm, 0, 2);
                    $mo   = substr($adm, 3, 2);
                    $year = substr($adm, 6, 4);
                    $tm   = substr($adm, 11, 8);
                    $datetimeadm = $year.'-'.$mo.'-'.$day.' '.$tm;

                    $dch     = $sheet->getCell('J'.$row)->getValue();
                    $dchday  = substr($dch, 0, 2);
                    $dchmo   = substr($dch, 3, 2);
                    $dchyear = substr($dch, 6, 4);
                    $dchtime = substr($dch, 11, 8);
                    $datetimedch = $dchyear.'-'.$dchmo.'-'.$dchday.' '.$dchtime;

                    $data[] = [
                        'repno'                => $sheet->getCell('A'.$row)->getValue(),
                        'no'                   => $sheet->getCell('B'.$row)->getValue(),
                        'tran_id'              => $sheet->getCell('C'.$row)->getValue(),
                        'hn'                   => $sheet->getCell('D'.$row)->getValue(),
                        'an'                   => $sheet->getCell('E'.$row)->getValue(),
                        'cid'                  => $sheet->getCell('F'.$row)->getValue(),
                        'pt_name'              => $sheet->getCell('G'.$row)->getValue(),
                        'dep'                  => $sheet->getCell('H'.$row)->getValue(),
                        'datetimeadm'          => $datetimeadm,
                        'vstdate'              => date('Y-m-d', strtotime($datetimeadm)),
                        'vsttime'              => date('H:i:s', strtotime($datetimeadm)),
                        'datetimedch'          => $datetimedch,
                        'dchdate'              => date('Y-m-d', strtotime($datetimedch)),
                        'dchtime'              => date('H:i:s', strtotime($datetimedch)),
                        'compensate_treatment' => $sheet->getCell('K'.$row)->getValue(),
                        'compensate_nhso'      => $sheet->getCell('L'.$row)->getValue(),
                        'error_code'           => $sheet->getCell('M'.$row)->getValue(),
                        'fund'                 => $sheet->getCell('N'.$row)->getValue(),
                        'service_type'         => $sheet->getCell('O'.$row)->getValue(),
                        'refer'                => $sheet->getCell('P'.$row)->getValue(),
                        'have_rights'          => $sheet->getCell('Q'.$row)->getValue(),
                        'use_rights'           => $sheet->getCell('R'.$row)->getValue(),
                        'main_rights'          => $sheet->getCell('S'.$row)->getValue(),
                        'secondary_rights'     => $sheet->getCell('T'.$row)->getValue(),
                        'href'                 => $sheet->getCell('U'.$row)->getValue(),
                        'hcode'                => $sheet->getCell('V'.$row)->getValue(),
                        'prov1'                => $sheet->getCell('W'.$row)->getValue(),
                        'hospcode'             => $sheet->getCell('X'.$row)->getValue(),
                        'hospname'             => $sheet->getCell('Y'.$row)->getValue(),
                        'proj'                 => $sheet->getCell('Z'.$row)->getValue(),
                        'pa'                   => $sheet->getCell('AA'.$row)->getValue(),
                        'drg'                  => $sheet->getCell('AB'.$row)->getValue(),
                        'rw'                   => $sheet->getCell('AC'.$row)->getValue(),
                        'charge_treatment'     => $sheet->getCell('AD'.$row)->getValue(),
                        'charge_pp'            => $sheet->getCell('AE'.$row)->getValue(),
                        'withdraw'             => $sheet->getCell('AF'.$row)->getValue(),
                        'non_withdraw'         => $sheet->getCell('AG'.$row)->getValue(),
                        'pay'                  => $sheet->getCell('AH'.$row)->getValue(),
                        'payrate'              => $sheet->getCell('AI'.$row)->getValue(),
                        'delay'                => $sheet->getCell('AJ'.$row)->getValue(),
                        'delay_percent'        => $sheet->getCell('AK'.$row)->getValue(),
                        'ccuf'                 => $sheet->getCell('AL'.$row)->getValue(),
                        'adjrw'                => $sheet->getCell('AM'.$row)->getValue(),
                        'act'                  => $sheet->getCell('AN'.$row)->getValue(),
                        'case_iplg'            => $sheet->getCell('AO'.$row)->getValue(),
                        'case_oplg'            => $sheet->getCell('AP'.$row)->getValue(),
                        'case_palg'            => $sheet->getCell('AQ'.$row)->getValue(),
                        'case_inslg'           => $sheet->getCell('AR'.$row)->getValue(),
                        'case_otlg'            => $sheet->getCell('AS'.$row)->getValue(),
                        'case_pp'              => $sheet->getCell('AT'.$row)->getValue(),
                        'case_drug'            => $sheet->getCell('AU'.$row)->getValue(),
                        'deny_iplg'            => $sheet->getCell('AV'.$row)->getValue(),
                        'deny_oplg'            => $sheet->getCell('AW'.$row)->getValue(),
                        'deny_palg'            => $sheet->getCell('AX'.$row)->getValue(),
                        'deny_inslg'           => $sheet->getCell('AY'.$row)->getValue(),
                        'deny_otlg'            => $sheet->getCell('AZ'.$row)->getValue(),
                        'ors'                  => $sheet->getCell('BA'.$row)->getValue(),
                        'va'                   => $sheet->getCell('BB'.$row)->getValue(),
                        'audit_results'        => $sheet->getCell('BC'.$row)->getValue(),
                        'stm_filename'         => $file_name,
                    ];
                }

                foreach (array_chunk($data, 1000) as $chunk) {
                    Stm_lgoexcel::insert($chunk);
                }
            }

            // ------------------ merge -> ตารางหลัก ------------------
            $stm_lgoexcel = Stm_lgoexcel::whereNotNull('charge_treatment')->get();

            foreach ($stm_lgoexcel as $value) {
                $exists = Stm_lgo::where('repno', $value->repno)
                            ->where('no', $value->no)
                            ->exists();

                if ($exists) {
                    Stm_lgo::where('repno', $value->repno)
                        ->where('no', $value->no)
                        ->update([
                            'round_no'             => $value->repno,
                            'datetimeadm'          => $value->datetimeadm,
                            'vstdate'              => $value->vstdate,
                            'vsttime'              => $value->vsttime,
                            'datetimedch'          => $value->datetimedch,
                            'dchdate'              => $value->dchdate,
                            'dchtime'              => $value->dchtime,
                            'compensate_treatment' => $value->compensate_treatment,
                            'compensate_nhso'      => $value->compensate_nhso,
                            'charge_treatment'     => $value->charge_treatment,
                            'charge_pp'            => $value->charge_pp,
                            'payrate'              => $value->payrate,
                            'case_iplg'            => $value->case_iplg,
                            'case_oplg'            => $value->case_oplg,
                            'case_palg'            => $value->case_palg,
                            'case_inslg'           => $value->case_inslg,
                            'case_otlg'            => $value->case_otlg,
                            'case_pp'              => $value->case_pp,
                            'case_drug'            => $value->case_drug,
                            'stm_filename'         => $value->stm_filename,
                        ]);
                } else {
                    Stm_lgo::create([
                        'round_no'             => $value->repno,
                        'repno'                => $value->repno,
                        'no'                   => $value->no,
                        'tran_id'              => $value->tran_id,
                        'hn'                   => $value->hn,
                        'an'                   => $value->an,
                        'cid'                  => $value->cid,
                        'pt_name'              => $value->pt_name,
                        'dep'                  => $value->dep,
                        'datetimeadm'          => $value->datetimeadm,
                        'vstdate'              => $value->vstdate,
                        'vsttime'              => $value->vsttime,
                        'datetimedch'          => $value->datetimedch,
                        'dchdate'              => $value->dchdate,
                        'dchtime'              => $value->dchtime,
                        'compensate_treatment' => $value->compensate_treatment,
                        'compensate_nhso'      => $value->compensate_nhso,
                        'error_code'           => $value->error_code,
                        'fund'                 => $value->fund,
                        'service_type'         => $value->service_type,
                        'refer'                => $value->refer,
                        'have_rights'          => $value->have_rights,
                        'use_rights'           => $value->use_rights,
                        'main_rights'          => $value->main_rights,
                        'secondary_rights'     => $value->secondary_rights,
                        'href'                 => $value->href,
                        'hcode'                => $value->hcode,
                        'prov1'                => $value->prov1,
                        'hospcode'             => $value->hospcode,
                        'hospname'             => $value->hospname,
                        'proj'                 => $value->proj,
                        'pa'                   => $value->pa,
                        'drg'                  => $value->drg,
                        'rw'                   => $value->rw,
                        'charge_treatment'     => $value->charge_treatment,
                        'charge_pp'            => $value->charge_pp,
                        'withdraw'             => $value->withdraw,
                        'non_withdraw'         => $value->non_withdraw,
                        'pay'                  => $value->pay,
                        'payrate'              => $value->payrate,
                        'delay'                => $value->delay,
                        'delay_percent'        => $value->delay_percent,
                        'ccuf'                 => $value->ccuf,
                        'adjrw'                => $value->adjrw,
                        'act'                  => $value->act,
                        'case_iplg'            => $value->case_iplg,
                        'case_oplg'            => $value->case_oplg,
                        'case_palg'            => $value->case_palg,
                        'case_inslg'           => $value->case_inslg,
                        'case_otlg'            => $value->case_otlg,
                        'case_pp'              => $value->case_pp,
                        'case_drug'            => $value->case_drug,
                        'deny_iplg'            => $value->deny_iplg,
                        'deny_oplg'            => $value->deny_oplg,
                        'deny_palg'            => $value->deny_palg,
                        'deny_inslg'           => $value->deny_inslg,
                        'deny_otlg'            => $value->deny_otlg,
                        'ors'                  => $value->ors,
                        'va'                   => $value->va,
                        'audit_results'        => $value->audit_results,
                        'stm_filename'         => $value->stm_filename,
                    ]);
                }
            }

            DB::commit();

            // ✅ ล้าง staging นอกทรานแซกชัน (หลัง commit)
            Stm_lgoexcel::truncate();

            return redirect()
                ->route('stm_lgo')
                ->with('success', implode(', ', $allFileNames));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('There was a problem uploading the data!');
        }
    }
//Create stm_lgo_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_lgo_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:30',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_lgo')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }      
//stm_lgo_detail---------------------------------------------------------------------------------------------------------------
public function stm_lgo_detail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));        

        $stm_lgo_list=DB::select('
            SELECT dep,stm_filename,repno,hn,an,pt_name,datetimeadm,datetimedch,adjrw,
            payrate,charge_treatment,compensate_treatment,
            case_iplg,case_oplg,case_palg,case_inslg,case_otlg,case_pp,case_drug
            FROM stm_lgo WHERE DATE(datetimeadm) BETWEEN ? AND ?
            AND dep = "OP"
            GROUP BY stm_filename,repno,hn,datetimeadm ORDER BY dep DESC,repno',[$start_date,$end_date]);

        $stm_lgo_list_ip=DB::select('
            SELECT dep,stm_filename,repno,hn,an,pt_name,datetimeadm,datetimedch,adjrw,
            payrate,charge_treatment,compensate_treatment,
            case_iplg,case_oplg,case_palg,case_inslg,case_otlg,case_pp,case_drug
            FROM stm_lgo WHERE DATE(datetimedch) BETWEEN ? AND ?
            AND dep = "IP"
            GROUP BY stm_filename,repno,hn,datetimedch ORDER BY dep DESC,repno',[$start_date,$end_date]);

        return view('import.stm_lgo_detail',compact('start_date','end_date','stm_lgo_list','stm_lgo_list_ip'));
    }
//stm_lgo_kidney-------------------------------------------------------------------------------------------------------------------------
    public function stm_lgo_kidney(Request $request)
    {  
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_lgo_kidney = DB::select("
            SELECT
                stm_filename ,
                round_no ,
                COUNT(*) AS count_no,
                SUM(compensate_kidney) AS compensate_kidney,
                MAX(receive_no)   AS receive_no,
                MAX(receipt_date) AS receipt_date,
                MAX(receipt_by)   AS receipt_by
            FROM stm_lgo_kidney
            WHERE ((CAST(SUBSTRING(repno, 7, 2) AS UNSIGNED) + 2500)
                + (CAST(SUBSTRING(repno, 11, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY repno
            ORDER BY (CAST(SUBSTRING(repno, 7, 2) AS UNSIGNED) + 2500) DESC,
                CAST(SUBSTRING(repno, 11, 2) AS UNSIGNED) DESC,
                repno ", [$budget_year]);

        return view('import.stm_lgo_kidney',compact('stm_lgo_kidney', 'budget_year_select', 'budget_year'));
    }

//stm_lgo_kidney_save---------------------------------------------------------------------------------------------------------------
    public function stm_lgo_kidney_save(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ✅ หลายไฟล์ ไม่เกิน 5
        $this->validate($request, [
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:xls,xlsx'
        ]);

        $uploadedFiles = $request->file('files');
        $allFileNames  = [];

        // ✅ ล้าง staging นอกทรานแซกชัน (ก่อนเริ่ม)
        Stm_lgo_kidneyexcel::truncate();

        DB::beginTransaction();
        try {
            // ------------------ อ่านทุกไฟล์ -> ใส่ staging ------------------
            foreach ($uploadedFiles as $the_file) {
                $file_name       = $the_file->getClientOriginalName();
                $allFileNames[]  = $file_name;

                $spreadsheet = IOFactory::load($the_file->getRealPath());
                $sheet       = $spreadsheet->setActiveSheetIndex(0);
                $row_limit   = $sheet->getHighestDataRow();

                $data = [];
                for ($row = 11; $row <= $row_limit; $row++) {
                    // คอลัมน์ G เป็น datetime รูปแบบ dd/mm/YYYY HH:MM:SS ตามโค้ดเดิม
                    $adm  = $sheet->getCell('G'.$row)->getValue();
                    $day  = substr($adm, 0, 2);
                    $mo   = substr($adm, 3, 2);
                    $year = substr($adm, 6, 4);
                    $tm   = substr($adm, 11, 8);
                    $datetimeadm = $year.'-'.$mo.'-'.$day.' '.$tm;

                    $data[] = [
                        'no'                 => $sheet->getCell('A'.$row)->getValue(),
                        'repno'              => $sheet->getCell('B'.$row)->getValue(),
                        'hn'                 => $sheet->getCell('C'.$row)->getValue(),
                        'cid'                => $sheet->getCell('D'.$row)->getValue(),
                        'pt_name'            => $sheet->getCell('E'.$row)->getValue(),
                        'dep'                => $sheet->getCell('F'.$row)->getValue(),
                        'datetimeadm'        => $datetimeadm,
                        'compensate_kidney'  => $sheet->getCell('H'.$row)->getValue(),
                        'note'               => $sheet->getCell('I'.$row)->getValue(),
                        'stm_filename'       => $file_name,
                    ];
                }

                foreach (array_chunk($data, 1000) as $chunk) {
                    Stm_lgo_kidneyexcel::insert($chunk);
                }
            }

            // ------------------ merge -> ตารางหลัก ------------------
            $rows = Stm_lgo_kidneyexcel::whereNotNull('compensate_kidney')->get();

            foreach ($rows as $value) {
                $exists = Stm_lgo_kidney::where('repno', $value->repno)
                            ->where('no', $value->no)
                            ->exists();

                if ($exists) {
                    Stm_lgo_kidney::where('repno', $value->repno)
                        ->where('no', $value->no)
                        ->update([
                            'round_no'              => $value->repno,
                            'repno'                 => $value->repno,
                            'datetimeadm'           => $value->datetimeadm,
                            'compensate_kidney'     => $value->compensate_kidney,
                            'stm_filename'          => $value->stm_filename,
                        ]);
                } else {
                    Stm_lgo_kidney::create([
                        'round_no'           => $value->repno,
                        'no'                 => $value->no,
                        'repno'              => $value->repno,
                        'hn'                 => $value->hn,
                        'cid'                => $value->cid,
                        'pt_name'            => $value->pt_name,
                        'dep'                => $value->dep,
                        'datetimeadm'        => $value->datetimeadm,
                        'compensate_kidney'  => $value->compensate_kidney,
                        'note'               => $value->note,
                        'stm_filename'       => $value->stm_filename,
                    ]);
                }
            }

            DB::commit();

            // ✅ ล้าง staging นอกทรานแซกชัน (หลัง commit)
            Stm_lgo_kidneyexcel::truncate();

            return redirect()
                ->route('stm_lgo_kidney')
                ->with('success', implode(', ', $allFileNames));

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors('There was a problem uploading the data!');
        }
    }
//Create stm_lgo_kidney_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_lgo_kidney_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:30',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_lgo_kidney')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }       
//stm_lgo_kidneydetail------------------------------------------------------------------------------------------------------------
public function stm_lgo_kidneydetail(Request $request)
    {  
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

        $stm_lgo_kidney_list=DB::select('
            SELECT dep,stm_filename,repno,hn,cid,pt_name,
            datetimeadm,compensate_kidney,note 
            FROM stm_lgo_kidney 
            WHERE DATE(datetimeadm) BETWEEN ? AND ?
            GROUP BY stm_filename,repno,cid,datetimeadm 
            ORDER BY dep DESC,repno',[$start_date,$end_date]);

        return view('import.stm_lgo_kidneydetail',compact('start_date','end_date','stm_lgo_kidney_list'));
}
//stm_sss_kidney----------------------------------------------------------------------------------------------------------
    public function stm_sss_kidney(Request $request)
    {  
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        /* ---------------- ปีงบ (dropdown) ---------------- */
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;

        $stm_sss_kidney = DB::select("
            SELECT
                stm_filename,
                station,
                COUNT(*) AS count_no,
                round_no,
                SUM(amount) AS amount,
                SUM(epopay) AS epopay,
                SUM(epoadm) AS epoadm,
                SUM(amount)+SUM(epopay)+SUM(epoadm) AS receive_total,
                MAX(receive_no)   AS receive_no,
                MAX(receipt_date) AS receipt_date,
                MAX(receipt_by)   AS receipt_by
            FROM stm_sss_kidney
            WHERE (CAST(LEFT(RIGHT(round_no, 8), 4) AS UNSIGNED) + 543
                + (CAST(SUBSTRING(RIGHT(round_no, 8), 5, 2) AS UNSIGNED) >= 10)) = ?
            GROUP BY round_no
            ORDER BY round_no DESC, CAST(LEFT(RIGHT(round_no, 8), 6) AS UNSIGNED) DESC, round_no ", [$budget_year]);  

        return view('import.stm_sss_kidney',compact('stm_sss_kidney', 'budget_year_select', 'budget_year'));
    }
//stm_sss_kidney------------------------------------------------------------------------------------------------
    public function stm_sss_kidney_save (Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // ✅ หลายไฟล์ .zip ไม่เกิน 5
        $this->validate($request, [
            'files'   => 'required|array|max:5',
            'files.*' => 'file|mimes:zip',
        ]);

        $uploadedFiles = $request->file('files');
        $docNames = []; // เก็บ STMdoc/ชื่อไฟล์ภายใน zip ไว้แสดงผล

        DB::beginTransaction();
        try {

            foreach ($uploadedFiles as $zipFile) {
                $zip = new \ZipArchive;
                if ($zip->open($zipFile->getRealPath()) !== true) {
                    // เปิด zip ไม่ได้ ข้ามไฟล์นี้
                    continue;
                }

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat      = $zip->statIndex($i);
                    $innerName = $stat['name'];

                    // สนใจเฉพาะไฟล์ .xml ภายใน zip
                    if (strtolower(pathinfo($innerName, PATHINFO_EXTENSION)) !== 'xml') {
                        continue;
                    }

                    $xmlString = $zip->getFromIndex($i);
                    if (!$xmlString) {
                        continue;
                    }

                    $xmlObject = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
                    if ($xmlObject === false) {
                        continue;
                    }

                    $json   = json_encode($xmlObject);
                    $result = json_decode($json, true);

                    // ส่วนหัวเอกสาร
                    $hcode  = $result['hcode']  ?? null;
                    $hname  = $result['hname']  ?? null;
                    $STMdoc = $result['STMdoc'] ?? $innerName;

                    // ✅ นำเข้าเฉพาะไฟล์ STM เท่านั้น
                    if (stripos($STMdoc, 'STM') === false) {
                        continue;
                    }

                    $docNames[] = $STMdoc;

                    // HDBills/HDBill อาจเป็น object เดี่ยว ให้ normalize เป็น array
                    $HDBills = $result['HDBills']['HDBill'] ?? [];
                    if (!empty($HDBills) && array_keys($HDBills) !== range(0, count($HDBills) - 1)) {
                        $HDBills = [$HDBills];
                    }

                    foreach ($HDBills as $bill) {
                        $name = $bill['name'] ?? null;
                        $cid  = $bill['pid']  ?? null;
                        $wkno = $bill['wkno'] ?? null;

                        // TBill อาจเป็น object เดี่ยว ให้ normalize เป็น array
                        $TBills = $bill['TBill'] ?? [];
                        if (!empty($TBills) && array_keys($TBills) !== range(0, count($TBills) - 1)) {
                            $TBills = [$TBills];
                        }

                        foreach ($TBills as $row) {
                            $hreg           = $row['hreg']    ?? null;                           
                            $station        = $row['station'] ?? null;
                            $invno          = $row['invno']   ?? null;
                            $hn             = $row['hn']      ?? null;                           
                            $amount         = $row['amount']  ?? null;
                            $paid           = $row['paid']    ?? null;
                            $rid            = $row['rid']     ?? null;
                            $HDflag         = $row['HDflag']  ?? ($row['hdflag'] ?? null);
                            $dttran         = $row['dttran']  ?? null;

                            // แยกวันที่เวลาแบบ ISO: 2024-07-01T12:34:56
                            $dttdate = null; $dtttime = null;
                            if ($dttran && strpos($dttran, 'T') !== false) {
                                [$dttdate, $dtttime] = explode('T', $dttran, 2);
                            }

                            // EPOs (อาจไม่มี)
                            $epopay = $row['EPOs']['EPOpay'] ?? '';
                            $epoadm = $row['EPOs']['EPOadm'] ?? '';

                            // upsert ตามคีย์เดิม: cid + vstdate
                            if ($cid && $dttdate) {
                                $dataRow = [
                                    'stm_filename'  => $innerName,
                                    'round_no'      => $STMdoc,
                                    'hcode'         => $hcode,
                                    'hname'         => $hname,       
                                    'station'       => $station,
                                    'hreg'          => $hreg,
                                    'hn'            => $hn,
                                    'pt_name'       => $name,
                                    'cid'           => $cid,
                                    'invno'         => $invno,
                                    'dttran'        => $dttran,
                                    'vstdate'       => $dttdate,
                                    'vsttime'       => $dtttime,
                                    'amount'        => $amount,
                                    'epopay'        => $epopay,
                                    'epoadm'        => $epoadm,
                                    'paid'          => $paid,
                                    'rid'           => $rid,
                                    // เก็บชื่อคอลัมน์ให้ตรงกับ schema ของคุณ
                                    'hdflag'        => $HDflag,
                                ];

                                $exists = Stm_sss_kidney::where('cid', $cid)
                                            ->where('vstdate', $dttdate)
                                            ->exists();

                                if ($exists) {
                                    Stm_sss_kidney::where('cid', $cid)
                                        ->where('vstdate', $dttdate)
                                        ->update($dataRow);
                                } else {
                                    Stm_sss_kidney::insert($dataRow);
                                }
                            }
                        }
                    }
                }

                $zip->close();
            }

            DB::commit();

            return redirect()
                ->route('stm_sss_kidney')
                ->with('success', implode(', ', $docNames));

        } catch (\Throwable $e) {
            DB::rollBack();
            // report($e); // ถ้าต้องการ debug
            return back()->withErrors('There was a problem uploading the data!');
        }
    }
//Create stm_sss_kidney_updateReceipt------------------------------------------------------------------------------------------------------------- 
    public function stm_sss_kidney_updateReceipt(Request $request)
    {
        $request->validate([
            'round_no'     => 'required',
            'receive_no'   => 'required|max:30',
            'receipt_date' => 'required|date',
        ]);

        DB::table('stm_sss_kidney')
            ->where('round_no', $request->round_no)
            ->update([
                'receive_no'   => $request->receive_no,
                'receipt_date' => $request->receipt_date,
                'receipt_by'   => auth()->user()->name ?? 'system',
                'updated_at'   => now(),
            ]);

        return response()->json([
            'status'       => 'success',
            'message'      => 'ออกใบเสร็จเรียบร้อยแล้ว',
            'round_no'     => $request->round_no,
            'receive_no'   => $request->receive_no,
            'receipt_date' => $request->receipt_date,
        ]);
    }    
//stm_sss_kidneydetail--------------------------------------------------------------------------------------------------------------
public function stm_sss_kidneydetail(Request $request)
{  
    $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
    $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));

    $stm_sss_kidney_list=DB::select('
        SELECT stm_filename,hcode,hname,stmdoc,station,hreg,hn,cid,pt_name,
        dttran,paid,rid,amount,epopay,epoadm,amount+epopay+epoadm AS receive ,receive_no
        FROM stm_sss_kidney 
        WHERE DATE(dttran) BETWEEN ? AND ?
        ORDER BY station ,stmdoc',[$start_date,$end_date]);

    return view('import.stm_sss_kidneydetail',compact('start_date','end_date','stm_sss_kidney_list'));
}
}
