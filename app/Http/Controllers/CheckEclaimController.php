<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\EclaimStatus;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

class CheckEclaimController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_check !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'ตรวจสอบข้อมูล'], 403);
                }
                return $next($request);
            }
        ])->except(['sync_eclaim_extension']);
    }
    // หน้าจอหลัก eclaim_status
    public function eclaim_status(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-01');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        if ($start_date > $end_date) {
            $end_date = $start_date;
        }
        $hipdata = $request->has('hipdata') ? $request->hipdata : Session::get('eclaim_hipdata');
        $patient_type = $request->patient_type ?: 'OP';
        if ($patient_type === 'OPD') {
            $patient_type = 'OP';
        }
        if ($patient_type === 'IPD') {
            $patient_type = 'IP';
        }

        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);
        Session::put('eclaim_hipdata', $hipdata);

        if ($request->ajax()) {
            $activePatientType = $request->patient_type ?: $patient_type;

            $query = DB::table('eclaim_status')
                ->where(function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('vstdate', [$start_date, $end_date])
                      ->orWhereBetween('dchdate', [$start_date, $end_date]);
                });

            $recordsTotal = DB::table('eclaim_status')
                ->where(function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('vstdate', [$start_date, $end_date])
                      ->orWhereBetween('dchdate', [$start_date, $end_date]);
                });

            if (!empty($hipdata)) {
                $query->where('hipdata', $hipdata);
                $recordsTotal->where('hipdata', $hipdata);
            }

            // Filter by patient_type (OP / IP)
            if ($activePatientType === 'OP' || $activePatientType === 'OPD') {
                $query->whereIn('patient_type', ['OP', 'OPD']);
                $recordsTotal->whereIn('patient_type', ['OP', 'OPD']);
            } elseif ($activePatientType === 'IP' || $activePatientType === 'IPD') {
                $query->whereIn('patient_type', ['IP', 'IPD']);
                $recordsTotal->whereIn('patient_type', ['IP', 'IPD']);
            }

            // Global search filter (HN, AN, ptname, eclaim_no, etc.)
            if ($request->has('search') && !empty($request->search['value'])) {
                $searchValue = $request->search['value'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('hn', 'like', "%{$searchValue}%")
                      ->orWhere('an', 'like', "%{$searchValue}%")
                      ->orWhere('ptname', 'like', "%{$searchValue}%")
                      ->orWhere('eclaim_no', 'like', "%{$searchValue}%")
                      ->orWhere('cid', 'like', "%{$searchValue}%");
                });
            }

            // Filter by status code clicked from cards (passed from frontend)
            if ($request->has('status_filter') && $request->status_filter !== '') {
                $sf = (string)$request->status_filter;
                if ($sf === '4') {
                    $query->where(function($q) {
                        $q->where('status', 'like', '4%')
                          ->orWhere('status', 'like', '%ผ่าน%')
                          ->orWhere('status', 'like', '%Statement%')
                          ->orWhere('status', 'like', '%(A)%');
                    });
                } elseif ($sf === '3') {
                    $query->where(function($q) {
                        $q->where('status', 'like', '3%')
                          ->orWhere('status', 'like', '%ติด C%')
                          ->orWhere('status', 'like', '%(C)%');
                    });
                } elseif ($sf === '2') {
                    $query->where(function($q) {
                        $q->where('status', 'like', '2%')
                          ->orWhere('status', 'like', '%ไม่ผ่านการตรวจสอบขั้นต้น%');
                    });
                } elseif ($sf === '0') {
                    $query->where(function($q) {
                        $q->where('status', 'like', '0%')
                          ->orWhere('status', 'like', '%รอส่ง%');
                    });
                } elseif ($sf === '1') {
                    $query->where(function($q) {
                        $q->where('status', 'like', '1%')
                          ->orWhere('status', 'like', '%ส่งไปยังสปสช%');
                    });
                } else {
                    $query->where('status', 'like', $sf . '%');
                }
            }

            $recordsTotalVal = $recordsTotal->count();
            $recordsFiltered = $query->count();

            // Ordering
            if ($request->has('order') && !empty($request->order)) {
                $columns = [
                    0 => 'eclaim_no',
                    1 => 'hipdata',
                    2 => 'cid',
                    3 => 'hn',
                    4 => 'an',
                    5 => 'ptname',
                    6 => 'vstdate',
                    7 => 'vsttime',
                    8 => 'dchdate',
                    9 => 'dchtime',
                    10 => 'status',
                    11 => 'recorder',
                    12 => 'claim_amount'
                ];
                $orderCol = $request->order[0]['column'];
                $orderDir = $request->order[0]['dir'];
                if (isset($columns[$orderCol])) {
                    $query->orderBy($columns[$orderCol], $orderDir);
                }
            } else {
                $query->orderBy('vstdate', 'desc');
            }

            // Pagination
            $start = $request->start ?? 0;
            $length = $request->length ?? 50;
            $data = $query->offset($start)->limit($length)->get();

            // Calculate dynamic summary for this date range (overall - do not filter by patient_type)
            $sumExpr = "CASE 
                WHEN status LIKE '4%' OR status LIKE '%ผ่าน%' OR status LIKE '%Statement%' OR status LIKE '%(A)%' THEN '4'
                WHEN status LIKE '3%' OR status LIKE '%ติด C%' OR status LIKE '%(C)%' THEN '3'
                WHEN status LIKE '2%' OR status LIKE '%ไม่ผ่านการตรวจสอบขั้นต้น%' THEN '2'
                WHEN status LIKE '0%' OR status LIKE '%รอส่ง%' THEN '0'
                ELSE '1'
            END";

            $sumQuery = DB::table('eclaim_status')
                ->select(
                    DB::raw("({$sumExpr}) as status_code"),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(claim_amount) as sum_amount')
                )
                ->where(function ($q) use ($start_date, $end_date) {
                    $q->whereBetween('vstdate', [$start_date, $end_date])
                      ->orWhereBetween('dchdate', [$start_date, $end_date]);
                });

            if (!empty($hipdata)) {
                $sumQuery->where('hipdata', $hipdata);
            }
            $summaryData = $sumQuery->groupBy(DB::raw("({$sumExpr})"))->get()->keyBy('status_code');

            return response()->json([
                "draw" => intval($request->draw),
                "recordsTotal" => $recordsTotalVal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $data,
                "summary" => $summaryData
            ]);
        }

        // ดึงรายการ hipdata ทั้งหมดที่มีในตารางมาทำตัวกรอง
        $hipdata_list = DB::table('eclaim_status')
            ->distinct()
            ->whereNotNull('hipdata')
            ->where('hipdata', '<>', '')
            ->orderBy('hipdata')
            ->pluck('hipdata');

        // คำนวณยอดรวมแยกตามสถานะ (ตัวเลขตัวแรกของ status: 0, 1, 2, 3, 4)
        $sumExpr = "CASE 
            WHEN status LIKE '4%' OR status LIKE '%ผ่าน%' OR status LIKE '%Statement%' OR status LIKE '%(A)%' THEN '4'
            WHEN status LIKE '3%' OR status LIKE '%ติด C%' OR status LIKE '%(C)%' THEN '3'
            WHEN status LIKE '2%' OR status LIKE '%ไม่ผ่านการตรวจสอบขั้นต้น%' THEN '2'
            WHEN status LIKE '0%' OR status LIKE '%รอส่ง%' THEN '0'
            ELSE '1'
        END";

        $sumQuery = DB::table('eclaim_status')
            ->select(
                DB::raw("({$sumExpr}) as status_code"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(claim_amount) as sum_amount')
            )
            ->where(function ($q) use ($start_date, $end_date) {
                $q->whereBetween('vstdate', [$start_date, $end_date])
                  ->orWhereBetween('dchdate', [$start_date, $end_date]);
            });

        if (!empty($hipdata)) {
            $sumQuery->where('hipdata', $hipdata);
        }

        $summary = $sumQuery->groupBy(DB::raw("({$sumExpr})"))
            ->get()
            ->keyBy('status_code');

        return view('check.eclaim_status', compact('start_date', 'end_date', 'summary', 'hipdata_list', 'hipdata', 'patient_type'));
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

    // ฟังก์ชันรับ API จาก Chrome Extension (ดึงจากหน้าเว็บ e-Claim Client/home - ใช้งานฟรี)
    public function sync_eclaim_extension(Request $request)
    {
        $payload = $request->all();
        $hospcode_incoming = $payload['hospcode'] ?? null;

        // ดึงรหัสสถานพยาบาลของตัวเองจาก main_setting มาเทียบ
        $hospital_code_local = DB::table('main_setting')->where('name', 'hospital_code')->value('value');

        if (!$hospital_code_local) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่ได้ตั้งค่ารหัสสถานพยาบาลในระะบบ RiMS'], 500);
        }

        if (!$hospcode_incoming) {
            $hospcode_incoming = $hospital_code_local ?: '10989';
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

            $pt = trim((string)($item['patient_type'] ?? ''));
            if ($pt === 'OPD') $pt = 'OP';
            if ($pt === 'IPD') $pt = 'IP';

            $statusRaw = trim((string)($item['status'] ?? ''));
            $mappedStatus = $statusRaw ?: '1=ส่งไปยังสปสช.';
            if (stripos($statusRaw, 'ติด C') !== false || stripos($statusRaw, '(C)') !== false) {
                $mappedStatus = '3=ไม่ผ่านการตรวจสอบจากสปสช.(C)';
            } elseif (stripos($statusRaw, 'ออก Statement') !== false || stripos($statusRaw, 'ผ่านการตรวจสอบ') !== false || stripos($statusRaw, 'ผ่าน A') !== false || stripos($statusRaw, '(A)') !== false) {
                $mappedStatus = '4=ผ่านการตรวจสอบจากสปสช.(A)';
            } elseif (stripos($statusRaw, 'ไม่ผ่านการตรวจสอบขั้นต้น') !== false) {
                $mappedStatus = '2=ไม่ผ่านการตรวจสอบขั้นต้น';
            } elseif (stripos($statusRaw, 'รอส่ง') !== false) {
                $mappedStatus = '0=ผ่านการตรวจสอบขั้นต้น รอส่ง';
            } elseif (preg_match('/^[0-4]=/', $statusRaw)) {
                $mappedStatus = $statusRaw;
            }

            EclaimStatus::updateOrCreate(
                [
                    'eclaim_no' => $item['eclaim_no'] ?? null,
                ],
                [
                    'hospcode' => $hospcode_incoming ?: $hospital_code_local,
                    'patient_type' => $pt ?: ($item['patient_type'] ?? null),
                    'hipdata' => $item['hipdata'] ?? null,
                    'cid' => $item['cid'] ?? null,
                    'ptname' => $item['ptname'] ?? null,
                    'hn' => $item['hn'] ?? null,
                    'an' => $item['an'] ?? null,
                    'vstdate' => $this->formatDateThaiToSql($item['vstdate'] ?? null),
                    'vsttime' => $item['vsttime'] ?? null,
                    'dchdate' => $this->formatDateThaiToSql($item['dchdate'] ?? null),
                    'dchtime' => $item['dchtime'] ?? null,
                    'status' => $mappedStatus,
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

    /**
     * ทำความสะอาดและกรอง Cookie Token สำหรับเชื่อมต่อ e-Claim สปสช.
     */
    protected function cleanToken($rawToken)
    {
        $token = trim((string)$rawToken);
        if (empty($token)) {
            return '';
        }

        if (strpos($token, ';') !== false && (stripos($token, 'JSESSIONID=') !== false || stripos($token, 'STEEXWDE=') !== false || stripos($token, 'ACCESS_TOKEN=') !== false)) {
            $pairs = explode(';', $token);
            $cleanPairs = [];
            $seenKeys = [];

            foreach ($pairs as $p) {
                $p = trim($p);
                if (empty($p)) continue;
                $parts = explode('=', $p, 2);
                $k = trim($parts[0]);
                $v = isset($parts[1]) ? trim($parts[1]) : '';

                if (
                    strpos($k, '_ga') === 0 ||
                    $k === '_gid' ||
                    $k === '_gat' ||
                    $k === '_gcl_au' ||
                    strpos($k, '__') === 0 ||
                    $k === 'REFRESH_TOKEN' ||
                    $k === 'KC_RESTART' ||
                    stripos($k, '_LEGACY') !== false ||
                    isset($seenKeys[$k])
                ) {
                    continue;
                }

                $seenKeys[$k] = true;
                $cleanPairs[] = "{$k}={$v}";
            }
            if (!empty($cleanPairs)) {
                return implode('; ', $cleanPairs);
            }
        }

        if (preg_match('/JSESSIONID=([a-zA-Z0-9_\-]+)/i', $token, $m)) {
            return $m[1];
        }

        $token = preg_replace('/^JSESSIONID=\s*/i', '', $token);
        $token = preg_replace('/;.*$/', '', $token);
        return trim($token, " \t\n\r\0\x0B;\"'");
    }

    /**
     * Browser Headers จำลอง Request จาก Chrome สำหรับ e-Claim
     */
    protected function getEclaimBrowserHeaders($token)
    {
        $cleaned = $this->cleanToken($token);
        $cookieHeader = (stripos($cleaned, 'JSESSIONID=') !== false || stripos($cleaned, 'STEEXWDE=') !== false)
            ? $cleaned
            : 'JSESSIONID=' . $cleaned;

        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'th-TH,th;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => 'https://eclaim.nhso.go.th/webComponent/check_data/CheckDataAction.do',
            'Cookie' => $cookieHeader,
        ];
    }

    /**
     * ดึง Session Token ที่ใช้งานได้ของระบบ
     */
    protected function getActiveEclaimToken()
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value');

        // 1. Token ประจำตัว User
        if (auth()->check()) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                $token = DB::table('users')->where('id', auth()->id())->value('eclaim_session_token');
                if ($token) return $this->cleanToken($token);
            }
            if (Session::has('eclaim_session_token')) {
                return $this->cleanToken(Session::get('eclaim_session_token'));
            }
        }

        // 2. Token จาก User อื่นที่มีการล็อกอินล่าสุด
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
            $latestUserToken = DB::table('users')
                ->whereNotNull('eclaim_session_token')
                ->where('eclaim_session_token', '<>', '')
                ->orderBy('eclaim_session_time', 'desc')
                ->value('eclaim_session_token');
            if ($latestUserToken) return $this->cleanToken($latestUserToken);
        }

        // 3. Token จากส่วนกลาง
        $globalToken = DB::table('main_setting')->where('name', 'eclaim_session_token')->value('value')
            ?: (\Illuminate\Support\Facades\Cache::get('eclaim_session_token_' . $hospcode)
            ?: (\Illuminate\Support\Facades\Cache::get('eclaim_session_token_global')));

        return $globalToken ? $this->cleanToken($globalToken) : null;
    }

    /**
     * ตรวจสอบสถานะการเชื่อมต่อ e-Claim Bot (ThaiD Session)
     */
    public function getBotStatus(Request $request)
    {
        $token = $this->getActiveEclaimToken();
        if (!$token) {
            return response()->json([
                'connected' => false,
                'message' => 'ยังไม่ได้เชื่อมต่อ Session e-Claim สปสช.'
            ]);
        }

        $user = auth()->check() ? auth()->user()->name : 'ผู้ใช้งาน e-Claim';
        $time = date('Y-m-d H:i:s');

        // ดึงข้อมูล session_user ล่าสุด
        if (auth()->check() && \Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_user')) {
            $u = DB::table('users')->where('id', auth()->id())->first(['eclaim_session_user', 'eclaim_session_time']);
            if ($u && $u->eclaim_session_user) {
                $user = $u->eclaim_session_user;
                $time = $u->eclaim_session_time ?: $time;
            }
        }

        // ถ้าชื่อที่ดึงมามีข้อความประกาศ หรือยาวผิดปกติ ให้แทนที่ด้วยชื่อผู้ใช้จริง
        if (mb_strlen($user) > 40 || stripos($user, 'หน่วยบริการ') !== false || stripos($user, 'ประกาศ') !== false || stripos($user, 'link') !== false) {
            $user = auth()->check() ? auth()->user()->name : 'ผู้ใช้งาน e-Claim';
            if (auth()->check() && \Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_user')) {
                DB::table('users')->where('id', auth()->id())->update(['eclaim_session_user' => $user]);
            }
        }



        try {
            $headers = $this->getEclaimBrowserHeaders($token);
            $probeUrl = 'https://eclaim.nhso.go.th/webComponent/check_data/CheckDataAction.do';
            $res = Http::withHeaders($headers)->withoutVerifying()->timeout(8)->post($probeUrl, [
                'date' => '01/01/2567',
                'hddFind' => '1',
                'npage' => '1'
            ]);
            $html = (string)$res->body();

            if (
                $res->status() === 200 &&
                stripos($html, 'Error Page') === false &&
                stripos($html, 'คุณไม่มีสิทธิ์') === false &&
                stripos($html, 'frmErr') === false
            ) {
                return response()->json([
                    'connected' => true,
                    'user' => $user,
                    'connected_at' => $time,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('eClaim getBotStatus probe error: ' . $e->getMessage());
        }

        return response()->json([
            'connected' => false,
            'user' => $user,
            'message' => 'Session หมดอายุ หรือไม่สามารถเชื่อมต่อ e-Claim ได้ (กรุณาสแกน ThaiD ใหม่)'
        ]);
    }

    /**
     * ดึงข้อมูลสถานะ E-Claim สปสช. อัตโนมัติด้วย Session จากตาราง users
     */
    public function autoPullEclaimStatus(Request $request)
    {
        $token = $this->getActiveEclaimToken();
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'ยังไม่ได้เชื่อมต่อ Session e-Claim สปสช. กรุณากดสแกน ThaiD QR ก่อนครับ'
            ], 400);
        }

        $startDateInput = $request->start_date ?: date('Y-m-d');
        $endDateInput = $request->end_date ?: date('Y-m-d');
        $schemeFilter = trim((string)$request->hipdata);
        $patientTypeFilter = trim((string)$request->patient_type); // OPD / IPD / ALL

        $startDate = $this->formatDateThaiToSql($startDateInput) ?: $startDateInput;
        $endDate = $this->formatDateThaiToSql($endDateInput) ?: $endDateInput;

        $hcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';

        $totalFound = 0;
        $insertedCount = 0;
        $updatedCount = 0;
        $stats = [
            'total' => 0,
            'inserted' => 0,
            'updated' => 0,
            'opd' => 0,
            'ipd' => 0,
            'by_scheme' => [],
            'by_status' => [],
        ];

        // 1. ลองรัน Client Crawler (ดึงจากหน้า Client/home - "ทุกรายการ") ก่อน
        try {
            $crawlerRes = \App\Helpers\PlaywrightHelper::runEclaimClientCrawler([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'cookies' => $token
            ]);

            if (!empty($crawlerRes['success']) && !empty($crawlerRes['dom_rows'])) {
                foreach ($crawlerRes['dom_rows'] as $cols) {
                    if (count($cols) < 6) continue;

                    $eclaimNo = $cols[1] ?? null;
                    if (!$eclaimNo || strlen($eclaimNo) < 4) continue;

                    $personTypeRaw = strtoupper($cols[2] ?? '');
                    $ptType = ($personTypeRaw === 'IP' || $personTypeRaw === 'IPD') ? 'IP' : 'OP';
                    $hip = $cols[3] ?? null;
                    $cid = $cols[4] ?? null;
                    $ptname = $cols[5] ?? null;
                    $hn = $cols[6] ?? null;
                    $an = $cols[7] ?? null;
                    $vstdate = $this->formatDateThaiToSql($cols[8] ?? null);
                    $vsttime = $cols[9] ?? null;
                    $dchdate = $this->formatDateThaiToSql($cols[10] ?? null);
                    $dchtime = $cols[11] ?? null;
                    $statusRaw = $cols[12] ?? null;
                    $recorder = $cols[13] ?? null;
                    $tran_id = $cols[14] ?? null;
                    $net_charge = isset($cols[15]) && $cols[15] !== '' ? (float)str_replace(',', '', $cols[15]) : null;
                    $claim_amount = isset($cols[16]) && $cols[16] !== '' ? (float)str_replace(',', '', $cols[16]) : null;
                    $rep = $cols[17] ?? null;
                    $stm = $cols[18] ?? null;
                    $seq = $cols[19] ?? null;
                    $check_detail = $cols[20] ?? null;
                    $deny_warning = $cols[21] ?? null;

                    // Filter scheme/patient_type if requested
                    if ($schemeFilter && $hip && stripos($hip, $schemeFilter) === false) continue;
                    if ($patientTypeFilter === 'OPD' && $ptType !== 'OP') continue;
                    if ($patientTypeFilter === 'IPD' && $ptType !== 'IP') continue;

                    $mappedStatus = $statusRaw ?: '1=ส่งไปยังสปสช.';
                    if (stripos($statusRaw, 'ติด C') !== false || stripos($statusRaw, '(C)') !== false) {
                        $mappedStatus = '3=ไม่ผ่านการตรวจสอบจากสปสช.(C)';
                    } elseif (stripos($statusRaw, 'ออก Statement') !== false || stripos($statusRaw, 'ผ่านการตรวจสอบ') !== false || stripos($statusRaw, 'ผ่าน A') !== false || stripos($statusRaw, '(A)') !== false) {
                        $mappedStatus = '4=ผ่านการตรวจสอบจากสปสช.(A)';
                    } elseif (stripos($statusRaw, 'ไม่ผ่านการตรวจสอบขั้นต้น') !== false) {
                        $mappedStatus = '2=ไม่ผ่านการตรวจสอบขั้นต้น';
                    } elseif (stripos($statusRaw, 'รอส่ง') !== false) {
                        $mappedStatus = '0=ผ่านการตรวจสอบขั้นต้น รอส่ง';
                    }

                    $existing = DB::table('eclaim_status')->where('eclaim_no', $eclaimNo)->first();
                    $saveData = [
                        'eclaim_no' => $eclaimNo,
                        'hospcode' => $hcode,
                        'patient_type' => $ptType,
                        'hipdata' => $hip,
                        'cid' => $cid,
                        'ptname' => $ptname,
                        'hn' => $hn,
                        'an' => $an,
                        'vstdate' => $vstdate,
                        'vsttime' => $vsttime,
                        'dchdate' => $dchdate,
                        'dchtime' => $dchtime,
                        'status' => $mappedStatus,
                        'recorder' => $recorder,
                        'tran_id' => $tran_id,
                        'net_charge' => $net_charge,
                        'claim_amount' => $claim_amount,
                        'rep' => $rep,
                        'stm' => $stm,
                        'seq' => $seq,
                        'check_detail' => $check_detail,
                        'deny_warning' => $deny_warning,
                        'channel' => 'ThaiD Auto',
                        'updated_at' => now(),
                    ];

                    if ($existing) {
                        DB::table('eclaim_status')->where('eclaim_no', $eclaimNo)->update($saveData);
                        $updatedCount++;
                    } else {
                        $saveData['created_at'] = now();
                        DB::table('eclaim_status')->insert($saveData);
                        $insertedCount++;
                    }

                    $totalFound++;
                    if ($ptType === 'IP') $stats['ipd']++; else $stats['opd']++;
                    $scKey = $hip ?: 'ไม่ระบุสิทธิ';
                    $stats['by_scheme'][$scKey] = ($stats['by_scheme'][$scKey] ?? 0) + 1;
                    $stKey = mb_substr($mappedStatus, 0, 30);
                    $stats['by_status'][$stKey] = ($stats['by_status'][$stKey] ?? 0) + 1;
                }

                if ($totalFound > 0) {
                    $stats['total'] = $totalFound;
                    $stats['inserted'] = $insertedCount;
                    $stats['updated'] = $updatedCount;
                    return response()->json([
                        'status' => 'success',
                        'message' => "ดึงข้อมูลจาก e-Claim สำเร็จทั้งหมด {$totalFound} รายการ (เพิ่มใหม่ {$insertedCount}, อัปเดต {$updatedCount})",
                        'stats' => $stats
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Playwright crawler fallback: ' . $e->getMessage());
        }

        // 2. Fallback: ดึงข้อมูลผ่าน Web Component CheckDataAction.do
        // สร้างรายการวันที่ระหว่าง $startDate ถึง $endDate
        $dates = [];
        $current = strtotime($startDate);
        $last = strtotime($endDate);
        if ($current > $last) {
            $temp = $current;
            $current = $last;
            $last = $temp;
        }

        while ($current <= $last) {
            $d = date('Y-m-d', $current);
            $thYear = (int)date('Y', $current) + 543;
            $thDate = date('d/m/', $current) . $thYear;
            $dates[$d] = $thDate;
            $current = strtotime('+1 day', $current);
        }

        $headers = $this->getEclaimBrowserHeaders($token);

        foreach ($dates as $sqlDate => $thDateStr) {
            $page = 1;
            $maxPages = 20; // safety limit

            while ($page <= $maxPages) {
                try {
                    $res = Http::withHeaders($headers)
                        ->withoutVerifying()
                        ->timeout(25)
                        ->asForm()
                        ->post('https://eclaim.nhso.go.th/webComponent/check_data/CheckDataAction.do', [
                            'pid' => '',
                            'hn' => '',
                            'an' => '',
                            'date' => $thDateStr,
                            'hddFind' => '1',
                            'npage' => (string)$page
                        ]);

                    $html = (string)$res->body();

                    if (stripos($html, 'ไม่พบข้อมูล') !== false || strlen($html) < 2000) {
                        break;
                    }

                    $dom = new \DOMDocument();
                    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
                    $xpath = new \DOMXPath($dom);
                    $trList = $xpath->query('//table//tr');

                    $rowsFoundOnPage = 0;

                    foreach ($trList as $tr) {
                        $tds = $xpath->query('./td', $tr);
                        if ($tds->length < 10) continue;

                        $cols = [];
                        foreach ($tds as $td) {
                            $cols[] = trim(preg_replace('/\s+/', ' ', $td->textContent));
                        }

                        $rep = $cols[1] ?? null;
                        $rowHcode = $cols[2] ?? $hcode;
                        $eclaimNo = $cols[5] ?? null;
                        $hn = $cols[6] ?? null;
                        $an = $cols[7] ?? null;
                        $cid = $cols[8] ?? null;
                        $ptname = $cols[9] ?? null;
                        $personTypeRaw = strtoupper($cols[10] ?? '');
                        $ptType = ($personTypeRaw === 'IP' || !empty($an)) ? 'IPD' : 'OPD';
                        $dateadmRaw = $cols[11] ?? null;
                        $datedscRaw = $cols[12] ?? null;
                        $pdx = $cols[13] ?? null;
                        $drg = $cols[14] ?? null;
                        $chkflag = $cols[17] ?? null;
                        $statusText = $cols[18] ?? null;
                        $errorCode = $cols[19] ?? null;

                        if (empty($hn) && empty($an) && empty($eclaimNo)) continue;

                        $rowsFoundOnPage++;

                        // กรองตามประเภทผู้ป่วย (ถ้ามีเลือก)
                        if (!empty($patientTypeFilter) && $patientTypeFilter !== 'ALL' && $patientTypeFilter !== 'ทั้งหมด') {
                            $normFilter = ($patientTypeFilter === 'OP' || $patientTypeFilter === 'OPD') ? 'OPD' : 'IPD';
                            if ($ptType !== $normFilter) continue;
                        }

                        // แปลง Status ให้ตรงกับมาตรฐานของ RiMS (0, 1, 2, 3, 4)
                        $mappedStatus = '1=ส่งไปยังสปสช.';
                        if (stripos($statusText, 'ติด C') !== false || stripos($statusText, '(C)') !== false || $chkflag === '4') {
                            $mappedStatus = '3=ไม่ผ่านการตรวจสอบจากสปสช.(C)';
                        } elseif (stripos($statusText, 'ออก Statement') !== false || stripos($statusText, 'ผ่านการตรวจสอบ') !== false || stripos($statusText, 'ผ่าน A') !== false || stripos($statusText, '(A)') !== false || $chkflag === '1') {
                            $mappedStatus = '4=ผ่านการตรวจสอบจากสปสช.(A)';
                        } elseif (stripos($statusText, 'ไม่ผ่านการตรวจสอบขั้นต้น') !== false || $chkflag === '2') {
                            $mappedStatus = '2=ไม่ผ่านการตรวจสอบขั้นต้น';
                        } elseif (stripos($statusText, 'รอส่ง') !== false || $chkflag === '0') {
                            $mappedStatus = '0=ผ่านการตรวจสอบขั้นต้น รอส่ง';
                        } elseif (preg_match('/^[0-4]=/', $statusText)) {
                            $mappedStatus = $statusText;
                        } elseif (!empty($statusText)) {
                            $mappedStatus = $statusText;
                        }

                        // แยกวัน-เวลา
                        $vstdate = $sqlDate;
                        $vsttime = null;
                        if (!empty($dateadmRaw)) {
                            $dtParts = explode(' ', trim($dateadmRaw));
                            if (!empty($dtParts[0])) $vstdate = $this->formatDateThaiToSql($dtParts[0]) ?: $vstdate;
                            if (!empty($dtParts[1])) $vsttime = strlen($dtParts[1]) == 5 ? $dtParts[1] . ':00' : $dtParts[1];
                        }

                        $dchdate = null;
                        $dchtime = null;
                        if (!empty($datedscRaw)) {
                            $dtParts = explode(' ', trim($datedscRaw));
                            if (!empty($dtParts[0])) $dchdate = $this->formatDateThaiToSql($dtParts[0]);
                            if (!empty($dtParts[1])) $dchtime = strlen($dtParts[1]) == 5 ? $dtParts[1] . ':00' : $dtParts[1];
                        }

                        // กำหนดกลุ่มสิทธิเบื้องต้น
                        $hipdata = $schemeFilter ?: 'UCS';

                        // 🔍 Smart Matching เพื่อป้องกัน Record ซ้ำ
                        $existing = null;
                        if (!empty($an)) {
                            $existing = EclaimStatus::where('an', $an)->first();
                        }
                        if (!$existing && !empty($eclaimNo)) {
                            $existing = EclaimStatus::where('eclaim_no', $eclaimNo)->first();
                        }
                        if (!$existing && !empty($hn) && !empty($vstdate)) {
                            $q = EclaimStatus::where('hn', $hn)->where('vstdate', $vstdate);
                            if (!empty($vsttime)) {
                                $q->where('vsttime', 'like', substr($vsttime, 0, 5) . '%');
                            }
                            $existing = $q->first();
                        }

                        $payloadData = [
                            'hospcode' => $rowHcode ?: $hcode,
                            'eclaim_no' => $eclaimNo ?: ($existing ? $existing->eclaim_no : null),
                            'patient_type' => $ptType,
                            'hipdata' => $existing && $existing->hipdata ? $existing->hipdata : $hipdata,
                            'cid' => $cid ?: ($existing ? $existing->cid : null),
                            'ptname' => $ptname ?: ($existing ? $existing->ptname : null),
                            'hn' => $hn ?: ($existing ? $existing->hn : null),
                            'an' => $an ?: ($existing ? $existing->an : null),
                            'vstdate' => $vstdate,
                            'vsttime' => $vsttime ?: ($existing ? $existing->vsttime : null),
                            'dchdate' => $dchdate ?: ($existing ? $existing->dchdate : null),
                            'dchtime' => $dchtime ?: ($existing ? $existing->dchtime : null),
                            'status' => $mappedStatus ?: ($existing ? $existing->status : '1=ส่งไปยังสปสช.'),
                            'rep' => $rep ?: ($existing ? $existing->rep : null),
                            'deny_warning' => $errorCode ?: ($existing ? $existing->deny_warning : null),
                            'check_detail' => $statusText ?: ($existing ? $existing->check_detail : null),
                            'channel' => 'ThaiD Auto',
                        ];

                        if ($existing) {
                            $existing->update($payloadData);
                            $updatedCount++;
                        } else {
                            EclaimStatus::create($payloadData);
                            $insertedCount++;
                        }

                        $totalFound++;
                        if ($ptType === 'IPD') $stats['ipd']++; else $stats['opd']++;
                        $effectiveHip = $payloadData['hipdata'] ?: 'ไม่ระบุ';
                        $stats['by_scheme'][$effectiveHip] = ($stats['by_scheme'][$effectiveHip] ?? 0) + 1;
                        $effectiveStatus = $payloadData['status'] ?: 'ไม่ระบุ';
                        $stats['by_status'][$effectiveStatus] = ($stats['by_status'][$effectiveStatus] ?? 0) + 1;
                    }

                    if ($rowsFoundOnPage < 50) {
                        break; // No more pages for this date
                    }

                    $page++;
                } catch (\Exception $e) {
                    Log::error("eClaim autoPull error on date $thDateStr page $page: " . $e->getMessage());
                    break;
                }
            }
        }

        $stats['total'] = $totalFound;
        $stats['inserted'] = $insertedCount;
        $stats['updated'] = $updatedCount;

        if ($totalFound === 0) {
            return response()->json([
                'status' => 'info',
                'message' => 'เชื่อมต่อ e-Claim สำเร็จ แต่ไม่พบข้อมูลในช่วงวันที่เลือก (' . implode(', ', array_values($dates)) . ')',
                'stats' => $stats
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => "ดึงข้อมูลจาก E-Claim สำเร็จรวม {$totalFound} รายการ (เพิ่มใหม่ {$insertedCount}, อัปเดต {$updatedCount})",
            'stats' => $stats
        ]);
    }
}

