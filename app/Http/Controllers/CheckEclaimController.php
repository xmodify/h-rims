<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\EclaimStatus;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class CheckEclaimController extends Controller
{
    // หน้าจอหลัก eclaim_status
    public function eclaim_status(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        // อัปเดตค่าเก็บใน Session เผื่อครั้งถัดไป
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        // ดึงข้อมูลจากฐานข้อมูล (ดึงรวมทั้ง OPD และ IPD จากตารางเดียวเลย เพราะเราบันทึกรวมกันมารอไว้แล้ว)
        $sql = DB::table('eclaim_status')
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('vstdate', [$start_date, $end_date])
                    ->orWhereBetween('dchdate', [$start_date, $end_date]);
            })
            ->orderBy('vstdate', 'desc')
            ->get();

        // คำนวณยอดรวมแยกตามสถานะ (ตัวเลขตัวแรกของ status: 0, 1, 2, 3, 4)
        $summary = DB::table('eclaim_status')
            ->select(
                DB::raw('SUBSTRING(status, 1, 1) as status_code'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(claim_amount) as sum_amount')
            )
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('vstdate', [$start_date, $end_date])
                    ->orWhereBetween('dchdate', [$start_date, $end_date]);
            })
            ->groupBy(DB::raw('SUBSTRING(status, 1, 1)'))
            ->get()
            ->keyBy('status_code');

        return view('check.eclaim_status', compact('start_date', 'end_date', 'sql', 'summary'));
    }

    // ฟังก์ชันรับการ Import Excel
    public function import_eclaim_excel(Request $request)
    {
        set_time_limit(300);

        $this->validate($request, [
            'file' => 'required'
        ]);

        $files = $request->file('file');

        if (!is_array($files)) {
            $files = [$files];
        }

        $successCount = 0;
        $errorMessages = [];

        foreach ($files as $the_file) {
            try {
                $spreadsheet = IOFactory::load($the_file->getRealPath());
                $sheet        = $spreadsheet->setActiveSheetIndex(0);
                $row_limit    = $sheet->getHighestDataRow();
                $row_range    = range(2, $row_limit); // ข้าม Header แถว 1

                foreach ($row_range as $row) {
                    // A=row, B=EClaim No, C=ประเภทผู้ป่วย, D=สิทธิประโยชน์, E=หมายเลขบัตร, F=ชื่อผู้ป่วย, G=HN, H=AN
                    // I=วันที่เข้ารับบริการ, J=เวลาเข้ารับบริการ, K=วันที่จำหน่าย, L=เวลาจำหน่าย
                    // M=สถานะข้อมูล, N=ชื่อผู้บันทึก, O=Tran ID, P=ค่าใช้จ่ายสุทธิ, Q=ยอดขอเรียกเก็บ, 
                    // R=REP, S=STM, T=SEQ, U=รายละเอียดการตรวจสอบ, V=Deny/Warning, W=Channel
                    $eclaim_no = trim((string)$sheet->getCell('B' . $row)->getValue());
                    if (empty($eclaim_no)) continue; // ข้ามบรรทัดว่าง

                    $patient_type = trim((string)$sheet->getCell('C' . $row)->getValue());
                    $hipdata = trim((string)$sheet->getCell('D' . $row)->getValue());
                    $cid = trim((string)$sheet->getCell('E' . $row)->getValue());
                    $ptname = trim((string)$sheet->getCell('F' . $row)->getValue());
                    $hn = trim((string)$sheet->getCell('G' . $row)->getValue());
                    $an = trim((string)$sheet->getCell('H' . $row)->getValue());

                    $vstdate = $this->formatDateThaiToSql($sheet->getCell('I' . $row)->getValue());
                    $vsttime_raw = $sheet->getCell('J' . $row)->getValue();
                    $vsttime = ($vsttime_raw !== 'null' && !empty($vsttime_raw)) ? (is_numeric($vsttime_raw) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($vsttime_raw)->format('H:i:s') : $vsttime_raw) : null;

                    // Date Parsing - dchdate (K), dchtime (L)
                    $dchdate = $this->formatDateThaiToSql($sheet->getCell('K' . $row)->getValue());
                    $dchtime_raw = $sheet->getCell('L' . $row)->getValue();
                    $dchtime = ($dchtime_raw !== 'null' && !empty($dchtime_raw)) ? (is_numeric($dchtime_raw) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dchtime_raw)->format('H:i:s') : $dchtime_raw) : null;

                    $status = trim((string)$sheet->getCell('M' . $row)->getValue());
                    $recorder = trim((string)$sheet->getCell('N' . $row)->getValue());
                    $tran_id = trim((string)$sheet->getCell('O' . $row)->getValue());
                    $net_charge = (float)$sheet->getCell('P' . $row)->getValue();
                    $claim_amount = (float)$sheet->getCell('Q' . $row)->getValue();
                    $rep = trim((string)$sheet->getCell('R' . $row)->getValue());
                    $stm = trim((string)$sheet->getCell('S' . $row)->getValue());
                    $seq = trim((string)$sheet->getCell('T' . $row)->getValue());
                    $check_detail = trim((string)$sheet->getCell('U' . $row)->getValue());
                    $deny_warning = trim((string)$sheet->getCell('V' . $row)->getValue());
                    $channel = trim((string)$sheet->getCell('W' . $row)->getValue()) ?: 'Excel';

                    // Clean out 'null' strings that might be from Excel
                    foreach (['tran_id', 'hn', 'an', 'rep', 'stm', 'seq', 'check_detail', 'deny_warning', 'recorder'] as $f) {
                        if ($$f === 'null') $$f = null;
                    }

                    // For scientific notations in EClaim / CID, try expanding them just in case
                    if (is_numeric($eclaim_no) && stripos($eclaim_no, 'E') !== false) {
                        $eclaim_no = number_format((float)$eclaim_no, 0, '', '');
                    }
                    if (is_numeric($cid) && stripos($cid, 'E') !== false) {
                        $cid = number_format((float)$cid, 0, '', '');
                    }
                    if (is_numeric($seq) && stripos($seq, 'E') !== false) {
                        $seq = number_format((float)$seq, 0, '', '');
                    }

                    $hospital_code_local = DB::table('main_setting')->where('name', 'hospital_code')->value('value');

                    // การ Insert/Update (เช็คจาก eclaim_no หรือ tran_id)
                    EclaimStatus::updateOrCreate(
                        [
                            'eclaim_no' => $eclaim_no,
                        ],
                        [
                            'hospcode' => $hospital_code_local,
                            'patient_type' => $patient_type,
                            'hipdata' => $hipdata,
                            'cid' => $cid,
                            'ptname' => $ptname,
                            'hn' => $hn,
                            'an' => $an,
                            'vstdate' => $vstdate,
                            'vsttime' => $vsttime,
                            'dchdate' => $dchdate,
                            'dchtime' => $dchtime,
                            'status' => $status,
                            'recorder' => $recorder,
                            'tran_id' => $tran_id,
                            'net_charge' => $net_charge,
                            'claim_amount' => $claim_amount,
                            'rep' => $rep,
                            'stm' => $stm,
                            'seq' => $seq,
                            'check_detail' => $check_detail,
                            'deny_warning' => $deny_warning,
                            'channel' => $channel,
                        ]
                    );
                    $successCount++;
                }
            } catch (Exception $e) {
                $errorMessages[] = "ขัดข้องที่ไฟล์ " . $the_file->getClientOriginalName() . ": " . $e->getMessage();
            }
        }

        if (count($errorMessages) > 0) {
            return back()->withErrors($errorMessages)->with('success', "นำเข้าข้อมูลสำเร็จ " . $successCount . " รายการ (บางไฟล์มีปัญหา)");
        }

        return redirect()->back()->with('success', "นำเข้าข้อมูล E-Claim สำเร็จรวม " . $successCount . " รายการ");
    }

    // ฟังก์ชันรับ API จาก Chrome Extension
    public function sync_eclaim_extension(Request $request)
    {
        $payload = $request->all();
        $hospcode_incoming = $payload['hospcode'] ?? null;

        // ดึงรหัสสถานพยาบาลของตัวเองจาก main_setting มาเทียบ
        $hospital_code_local = DB::table('main_setting')->where('name', 'hospital_code')->value('value');

        if ($hospcode_incoming && $hospital_code_local && $hospcode_incoming !== $hospital_code_local) {
            return response()->json([
                'status' => 'error',
                'message' => "รหัสสถานพยาบาลไม่ตรงกัน (E-Claim: $hospcode_incoming, RiMS: $hospital_code_local)"
            ], 403);
        }

        if (!isset($payload['data']) || !is_array($payload['data'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data format'], 400);
        }

        $successCount = 0;
        foreach ($payload['data'] as $item) {
            // Check if eclaim_no is valid before proceeding to prevent accidental empty creation
            if (empty($item['eclaim_no'])) {
                continue;
            }

            EclaimStatus::updateOrCreate(
                [
                    'eclaim_no' => $item['eclaim_no'] ?? null,
                ],
                [
                    'hospcode' => $hospcode_incoming ?: $hospital_code_local,
                    'patient_type' => $item['patient_type'] ?? null,
                    'hipdata' => $item['hipdata'] ?? null,
                    'cid' => $item['cid'] ?? null,
                    'ptname' => $item['ptname'] ?? null,
                    'hn' => $item['hn'] ?? null,
                    'an' => $item['an'] ?? null,
                    'vstdate' => $this->formatDateThaiToSql($item['vstdate'] ?? null),
                    'vsttime' => $item['vsttime'] ?? null,
                    'dchdate' => $this->formatDateThaiToSql($item['dchdate'] ?? null),
                    'dchtime' => $item['dchtime'] ?? null,
                    'status' => $item['status'] ?? null,
                    'recorder' => $item['recorder'] ?? null,
                    'tran_id' => $item['tran_id'] ?? null,
                    'net_charge' => isset($item['net_charge']) && $item['net_charge'] !== '' ? (float)str_replace(',', '', $item['net_charge']) : null,
                    'claim_amount' => isset($item['claim_amount']) && $item['claim_amount'] !== '' ? (float)str_replace(',', '', $item['claim_amount']) : null,
                    'rep' => $item['rep'] ?? null,
                    'stm' => $item['stm'] ?? null,
                    'seq' => $item['seq'] ?? null,
                    'check_detail' => $item['check_detail'] ?? null,
                    'deny_warning' => $item['deny_warning'] ?? null,
                    'channel' => $item['channel'] ?? 'Extension',
                ]
            );
            $successCount++;
        }

        return response()->json([
            'status' => 'success',
            'count' => $successCount,
            'message' => "ซิงก์ข้อมูล E-Claim จาก Extension สำเร็จ $successCount รายการ"
        ]);
    }

    // Helper: แปลงวันที่จากไทย (DD/MM/YYYY) เป็น SQL (YYYY-MM-DD)
    private function formatDateThaiToSql($dateStr)
    {
        if (empty($dateStr) || $dateStr === 'null' || $dateStr === '-') return null;

        // ถ้ามาเป็นตัวเลข (จาก Excel numeric date)
        if (is_numeric($dateStr)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr)->format('Y-m-d');
        }

        // ถ้ามาเป็น YYYY-MM-DD อยู่แล้ว
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // คาดหวังรูปแบบ DD/MM/YYYY
        $parts = explode('/', $dateStr);
        if (count($parts) === 3) {
            $year = (int)$parts[2];
            // จัดการปี พ.ศ. (ต้องหักออก 543)
            if ($year > 2400) {
                $year -= 543;
            }
            return sprintf('%04d-%02d-%02d', $year, $parts[1], $parts[0]);
        }

        return null;
    }
}
