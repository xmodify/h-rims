<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Nhso_Endpoint;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except([
            'ipd_non_dchsummary',
            'ipd_finance_chk_opd_wait_transfer',
            'ipd_finance_chk_wait_rcpt_money',
            'nhso_endpoint_pull_yesterday'
        ]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        ini_set('max_execution_time', 300);

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

        $year_data = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->first(['DATE_BEGIN', 'DATE_END']);

        $start_date = $year_data->DATE_BEGIN ?? null;
        $end_date = $year_data->DATE_END ?? null;

        // 1. Optimized OPD Monitor Query
        $opd_monitor = DB::connection('hosxp')->select('
        SELECT 
            COUNT(vn) AS total,
            SUM(CASE WHEN endpoint IS NOT NULL THEN 1 ELSE 0 END) AS endpoint,
            SUM(CASE WHEN hipdata_code = "OFC" THEN 1 ELSE 0 END) AS ofc,
            SUM(CASE WHEN hipdata_code = "OFC" AND (edc_approve_list_text <> "" OR claim_code_flag = "Y") THEN 1 ELSE 0 END) AS ofc_edc,
            SUM(CASE WHEN (auth_code = "" OR auth_code IS NULL) AND cid NOT LIKE "0%" THEN 1 ELSE 0 END) AS non_authen,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","SSS","STP") AND (hospmain = "" OR hospmain IS NULL) THEN 1 ELSE 0 END) AS non_hmain,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND (hospmain <> "" AND hospmain IS NOT NULL) AND IFNULL(in_province, "N") <> "Y" THEN 1 ELSE 0 END) AS uc_anywhere,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND (hospmain <> "" AND hospmain IS NOT NULL) AND IFNULL(in_province, "N") <> "Y" AND endpoint = "Y" THEN 1 ELSE 0 END) AS uc_anywhere_endpoint,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND IFNULL(in_province, "N") = "Y" AND uc_cr_flag = "Y" THEN 1 ELSE 0 END) AS uc_cr,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND IFNULL(in_province, "N") = "Y" AND uc_cr_flag = "Y" AND endpoint = "Y" THEN 1 ELSE 0 END) AS uc_cr_endpoint,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND IFNULL(in_province, "N") = "Y" AND herb_flag = "Y" THEN 1 ELSE 0 END) AS uc_herb,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND IFNULL(in_province, "N") = "Y" AND herb_flag = "Y" AND endpoint = "Y" THEN 1 ELSE 0 END) AS uc_herb_endpoint,
            SUM(CASE WHEN ppfs_flag = "Y" THEN 1 ELSE 0 END) AS ppfs,
            SUM(CASE WHEN ppfs_flag = "Y" AND endpoint = "Y" THEN 1 ELSE 0 END) AS ppfs_endpoint,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND healthmed_flag = "Y" THEN 1 ELSE 0 END) AS uc_healthmed,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND healthmed_flag = "Y" AND endpoint = "Y" THEN 1 ELSE 0 END) AS uc_healthmed_endpoint
        FROM (
            SELECT o.vn, pt.cid, vp.auth_code, p.hipdata_code, vp.hospmain, os.edc_approve_list_text,
                lh.in_province,
                MAX(CASE WHEN li.ppfs = "Y" THEN "Y" ELSE "N" END) as ppfs_flag,
                MAX(CASE WHEN li.uc_cr = "Y" THEN "Y" ELSE "N" END) as uc_cr_flag,
                MAX(CASE WHEN li.herb32 = "Y" THEN "Y" ELSE "N" END) as herb_flag,
                IF(hms.vn IS NOT NULL, "Y", "N") as healthmed_flag,
                MAX(CASE WHEN vp.Claim_Code IS NOT NULL AND vp.Claim_Code <> "" THEN "Y" ELSE "N" END) as claim_code_flag,
                IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001"),"Y",NULL) AS endpoint
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN ovst_seq os ON os.vn = o.vn
            LEFT JOIN opitemrece ori ON ori.vn = o.vn
            LEFT JOIN hrims.lookup_icode li ON li.icode = ori.icode
            LEFT JOIN hrims.lookup_hospcode lh ON lh.hospcode = vp.hospmain
            LEFT JOIN (
                SELECT h.vn FROM health_med_service h 
                INNER JOIN health_med_service_operation hso ON hso.health_med_service_id = h.health_med_service_id
                GROUP BY h.vn
            ) hms ON hms.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE o.vstdate = DATE(NOW()) AND (o.an = "" OR o.an IS NULL)
            GROUP BY o.vn
        ) AS a');

        $row = $opd_monitor[0] ?? (object) [];
        $opd_total = $row->total ?? 0;
        $endpoint = $row->endpoint ?? 0;
        $ofc = $row->ofc ?? 0;
        $ofc_edc = $row->ofc_edc ?? 0;
        $non_authen = $row->non_authen ?? 0;
        $non_hmain = $row->non_hmain ?? 0;
        $uc_anywhere = $row->uc_anywhere ?? 0;
        $uc_anywhere_endpoint = $row->uc_anywhere_endpoint ?? 0;
        $uc_cr = $row->uc_cr ?? 0;
        $uc_cr_endpoint = $row->uc_cr_endpoint ?? 0;
        $uc_herb = $row->uc_herb ?? 0;
        $uc_herb_endpoint = $row->uc_herb_endpoint ?? 0;
        $uc_healthmed = $row->uc_healthmed ?? 0;
        $uc_healthmed_endpoint = $row->uc_healthmed_endpoint ?? 0;
        $ppfs = $row->ppfs ?? 0;
        $ppfs_endpoint = $row->ppfs_endpoint ?? 0;

        // 2. IPD Stats & Counts
        $ipd_stats = DB::connection('hosxp')->select('
        SELECT 
            COUNT(DISTINCT CASE WHEN confirm_discharge = "N" THEN an END) as admit_now,
            SUM(CASE WHEN confirm_discharge = "N" AND ward_m = "Y" THEN 1 ELSE 0 END) as ward_m,
            SUM(CASE WHEN confirm_discharge = "N" AND ward_f = "Y" THEN 1 ELSE 0 END) as ward_f,
            SUM(CASE WHEN confirm_discharge = "N" AND ward_vip = "Y" THEN 1 ELSE 0 END) as ward_vip,
            SUM(CASE WHEN confirm_discharge = "N" AND ward_lr = "Y" THEN 1 ELSE 0 END) as ward_lr,
            SUM(CASE WHEN confirm_discharge = "N" AND ward_homeward = "Y" THEN 1 ELSE 0 END) as ward_homeward,
            COUNT(DISTINCT CASE WHEN vstdate = DATE(NOW()) AND ward_homeward = "Y" THEN an END) as admit_homeward,
            COUNT(DISTINCT CASE WHEN vstdate = DATE(NOW()) AND ward_homeward = "Y" AND has_endpoint = "Y" THEN an END) as admit_homeward_endpoint
        FROM (
            SELECT i.an, i.ward, i.confirm_discharge, o.vstdate,
                lw.ward_m, lw.ward_f, lw.ward_vip, lw.ward_lr, lw.ward_homeward,
                IF(ep.claimCode IS NOT NULL, "Y", "N") as has_endpoint
            FROM ipt i
            LEFT JOIN ovst o ON o.an = i.an
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN hrims.lookup_ward lw ON lw.ward = i.ward
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate AND (ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001")
            WHERE i.confirm_discharge = "N" OR o.vstdate = DATE(NOW())
        ) AS a')[0];

        $admit_now = $ipd_stats->admit_now;
        $ward_m = $ipd_stats->ward_m;
        $ward_f = $ipd_stats->ward_f;
        $ward_vip = $ipd_stats->ward_vip;
        $ward_lr = $ipd_stats->ward_lr;
        $word_homeward = $ipd_stats->ward_homeward;
        $admit_homeward = $ipd_stats->admit_homeward;
        $admit_homeward_endpoint = $ipd_stats->admit_homeward_endpoint;

        // 3. IPD Summary Diagnostics & Finance
        $ipd_summary = DB::connection('hosxp')->select('
        SELECT 
            SUM(CASE WHEN (dchdate BETWEEN ? AND ?) AND (diag_text_list IS NULL OR diag_text_list = "") THEN 1 ELSE 0 END) AS non_diagtext,
            SUM(CASE WHEN (dchdate BETWEEN ? AND ?) AND (dx IS NOT NULL AND dx <> "") AND (pdx = "" OR pdx IS NULL) THEN 1 ELSE 0 END) AS non_icd10,
            SUM(CASE WHEN confirm_discharge = "N" AND (opd_wait_money <> "0") THEN 1 ELSE 0 END) AS not_transfer,
            SUM(CASE WHEN confirm_discharge = "N" AND (paid_money <> rcpt_money) AND (rcpt_money <> 0) THEN 1 ELSE 0 END) AS wait_paid_money,
            SUM(CASE WHEN confirm_discharge = "N" AND (rcpt_money <> 0) THEN (paid_money - rcpt_money) ELSE 0 END) AS sum_wait_paid_money
        FROM (
            SELECT i.an, i.dchdate, i.confirm_discharge, a.diag_text_list, id.diag_text AS dx, a.pdx, a.opd_wait_money, a.paid_money, a.rcpt_money
            FROM ipt i
            LEFT JOIN an_stat a ON a.an = i.an
            LEFT JOIN ipt_doctor_diag id ON id.an = i.an AND id.diagtype = 1
            WHERE ((i.dchdate BETWEEN ? AND ?) OR i.confirm_discharge = "N")
            AND i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
        ) AS a', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date])[0];

        $non_diagtext = $ipd_summary->non_diagtext;
        $non_icd10 = $ipd_summary->non_icd10;
        $not_transfer = $ipd_summary->not_transfer;
        $wait_paid_money = $ipd_summary->wait_paid_money;
        $sum_wait_paid_money = $ipd_summary->sum_wait_paid_money;

        $bed_qty = DB::table('main_setting')->where('name', 'bed_qty')->value('value') ?: 1;

        // 4. Combined Monthly IPD Statistics
        $monthly_stats = DB::connection('hosxp')->select('
        SELECT 
            CASE 
                WHEN MONTH(a.dchdate)="10" THEN CONCAT("ต.ค. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="11" THEN CONCAT("พ.ย. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="12" THEN CONCAT("ธ.ค. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="1" THEN CONCAT("ม.ค. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="2" THEN CONCAT("ก.พ. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="3" THEN CONCAT("มี.ค. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="4" THEN CONCAT("เม.ย. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="5" THEN CONCAT("พ.ค. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="6" THEN CONCAT("มิ.ย. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="7" THEN CONCAT("ก.ค. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="8" THEN CONCAT("ส.ค. ",YEAR(a.dchdate)+543)
                WHEN MONTH(a.dchdate)="9" THEN CONCAT("ก.ย. ",YEAR(a.dchdate)+543)
            END AS month,
            COUNT(DISTINCT a.an) AS an,
            SUM(a.admdate) AS admdate,
            SUM(i.adjrw) AS adjrw,
            SUM(a.income - a.rcpt_money) AS income_after_rcpt,
            
            -- Normal Stats
            COUNT(DISTINCT CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (1,2)) OR lw.ward_homeward <> "Y") THEN a.an END) as norm_an,
            SUM(CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (1,2)) OR lw.ward_homeward <> "Y") THEN a.admdate ELSE 0 END) as norm_admdate,
            SUM(CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (1,2)) OR lw.ward_homeward <> "Y") THEN i.adjrw ELSE 0 END) as norm_adjrw,
            SUM(CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (1,2)) OR lw.ward_homeward <> "Y") THEN (a.income - a.rcpt_money) ELSE 0 END) as norm_income,
            
            -- Homeward Stats
            COUNT(DISTINCT CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (3)) OR lw.ward_homeward = "Y") THEN a.an END) as home_an,
            SUM(CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (3)) OR lw.ward_homeward = "Y") THEN a.admdate ELSE 0 END) as home_admdate,
            SUM(CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (3)) OR lw.ward_homeward = "Y") THEN i.adjrw ELSE 0 END) as home_adjrw,
            SUM(CASE WHEN (ia.roomno IN (SELECT roomno FROM roomno WHERE roomtype IN (3)) OR lw.ward_homeward = "Y") THEN (a.income - a.rcpt_money) ELSE 0 END) as home_income,
            
            DAY(LAST_DAY(a.dchdate)) as days_in_month
        FROM ipt i
        LEFT JOIN an_stat a ON a.an = i.an
        LEFT JOIN iptadm ia ON ia.an = a.an
        LEFT JOIN hrims.lookup_ward lw ON lw.ward = a.ward
        WHERE a.dchdate BETWEEN ? AND DATE(NOW())
        AND a.pdx NOT IN ("Z290","Z208")
        GROUP BY YEAR(a.dchdate), MONTH(a.dchdate)
        ORDER BY YEAR(a.dchdate), MONTH(a.dchdate)', [$start_date]);

        $ip_all = [];
        $ip_normal = [];
        $ip_homeward = [];

        foreach ($monthly_stats as $stat) {
            $days = $stat->days_in_month;

            $ip_all[] = (object) [
                'month' => $stat->month,
                'an' => $stat->an,
                'admdate' => $stat->admdate,
                'bed_occupancy' => $stat->an > 0 ? round(($stat->admdate * 100) / ($bed_qty * $days), 2) : 0,
                'active_bed' => $stat->an > 0 ? round((($stat->admdate * 100) / ($bed_qty * $days) * $bed_qty) / 100, 2) : 0,
                'cmi' => $stat->an > 0 ? round($stat->adjrw / $stat->an, 2) : 0,
                'adjrw' => round($stat->adjrw, 2),
                'income_rw' => $stat->adjrw > 0 ? round($stat->income_after_rcpt / $stat->adjrw, 2) : 0
            ];

            $ip_normal[] = (object) [
                'month' => $stat->month,
                'an' => $stat->norm_an,
                'admdate' => $stat->norm_admdate,
                'bed_occupancy' => $stat->norm_an > 0 ? round(($stat->norm_admdate * 100) / ($bed_qty * $days), 2) : 0,
                'active_bed' => $stat->norm_an > 0 ? round((($stat->norm_admdate * 100) / ($bed_qty * $days) * $bed_qty) / 100, 2) : 0,
                'cmi' => $stat->norm_an > 0 ? round($stat->norm_adjrw / $stat->norm_an, 2) : 0,
                'adjrw' => round($stat->norm_adjrw, 2),
                'income_rw' => $stat->norm_adjrw > 0 ? round($stat->norm_income / $stat->norm_adjrw, 2) : 0
            ];

            $ip_homeward[] = (object) [
                'month' => $stat->month,
                'an' => $stat->home_an,
                'admdate' => $stat->home_admdate,
                'bed_occupancy' => $stat->home_an > 0 ? round(($stat->home_admdate * 100) / ($bed_qty * $days), 2) : 0,
                'active_bed' => $stat->home_an > 0 ? round((($stat->home_admdate * 100) / ($bed_qty * $days) * $bed_qty) / 100, 2) : 0,
                'cmi' => $stat->home_an > 0 ? round($stat->home_adjrw / $stat->home_an, 2) : 0,
                'adjrw' => round($stat->home_adjrw, 2),
                'income_rw' => $stat->home_adjrw > 0 ? round($stat->home_income / $stat->home_adjrw, 2) : 0
            ];
        }

        $month = array_column($ip_all, 'month');
        $bed_occupancy = array_column($ip_all, 'bed_occupancy');

        return view('home', compact(
            'budget_year_select',
            'budget_year',
            'opd_total',
            'endpoint',
            'ofc',
            'ofc_edc',
            'non_authen',
            'non_hmain',
            'uc_anywhere',
            'uc_anywhere_endpoint',
            'uc_cr',
            'uc_cr_endpoint',
            'uc_herb',
            'uc_herb_endpoint',
            'uc_healthmed',
            'uc_healthmed_endpoint',
            'ppfs',
            'ppfs_endpoint',
            'admit_homeward',
            'admit_homeward_endpoint',
            'non_diagtext',
            'non_icd10',
            'not_transfer',
            'wait_paid_money',
            'sum_wait_paid_money',
            'ip_all',
            'ip_normal',
            'ip_homeward',
            'month',
            'bed_occupancy',
            'admit_now'
        ));
    }
    ###################################################################################################
//Create nhso_endpoint_pull
    public function nhso_endpoint_pull(Request $request)
    {
        set_time_limit(600);

        $vstdate = $request->input('vstdate') ?? now()->format('Y-m-d');
        $hosxp = DB::connection('hosxp')->select('
        SELECT o.vn, o.hn, pt.cid, vp.auth_code
        FROM ovst o
        INNER JOIN visit_pttype vp ON vp.vn = o.vn 
        LEFT JOIN patient pt ON pt.hn = o.hn
        WHERE o.vstdate = ?
        AND pt.cid NOT IN (SELECT cid FROM hrims.nhso_endpoint WHERE vstdate = ? AND cid IS NOT NULL)'
            ,
            [$vstdate, $vstdate]
        );

        $cids = array_column($hosxp, 'cid');
        // ดึง token 
        $token = DB::table('main_setting')
            ->where('name', 'token_authen_kiosk_nhso')
            ->value('value');

        // วนทีละก้อน (chunk) ก้อนละ 20 CID
        foreach (array_chunk($cids, 20) as $chunk) {
            // ดึงข้อมูลที่มีอยู่แล้วสำหรับ chunk นี้มาไว้เช็คทีเดียว
            $existing_claims = Nhso_Endpoint::whereIn('cid', $chunk)
                ->where('vstdate', $vstdate)
                ->pluck('claimType', 'claimCode')
                ->toArray();

            foreach ($chunk as $cid) {
                try {
                    $response = Http::timeout(5)
                        ->withToken($token)
                        ->acceptJson()
                        ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                            'personalId' => $cid,
                            'serviceDate' => $vstdate,
                        ]);

                    if ($response->failed())
                        continue;

                    $result = $response->json();
                    if (!is_array($result) || !isset($result['firstName']) || empty($result['serviceHistories'])) {
                        continue;
                    }

                    foreach ($result['serviceHistories'] as $row) {
                        if (!is_array($row))
                            continue;

                        $claimCode = $row['claimCode'] ?? null;
                        $claimType = $row['service']['code'] ?? null;
                        $sourceChannel = $row['sourceChannel'] ?? '';
                        $serviceDateTime = $row['serviceDateTime'] ?? null;

                        if (!$claimCode)
                            continue;

                        if (isset($existing_claims[$claimCode])) {
                            if ($existing_claims[$claimCode] !== $claimType) {
                                Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
                            }
                        } elseif ($sourceChannel === 'ENDPOINT' || $claimType === 'PG0140001') {
                            Nhso_Endpoint::create([
                                'cid' => $cid,
                                'firstName' => $result['firstName'] ?? null,
                                'lastName' => $result['lastName'] ?? null,
                                'mainInscl' => $result['mainInscl']['id'] ?? null,
                                'mainInsclName' => $result['mainInscl']['name'] ?? null,
                                'subInscl' => $result['subInscl']['id'] ?? null,
                                'subInsclName' => $result['subInscl']['name'] ?? null,
                                'serviceDateTime' => $serviceDateTime,
                                'vstdate' => $serviceDateTime ? date('Y-m-d', strtotime($serviceDateTime)) : $vstdate,
                                'sourceChannel' => $sourceChannel,
                                'claimCode' => $claimCode,
                                'claimType' => $claimType,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error("NHSO Pull logic error for CID: {$cid}", ['msg' => $e->getMessage()]);
                }
            }

            // หน่วงเล็กน้อยระหว่างแต่ละก้อน เพื่อกัน rate limit/ภาระระบบปลายทาง
            usleep(300000);
        }

        return response()->json(['success' => true, 'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ']);
    }
    #################################################################################################################
    public function nhso_endpoint_pull_indiv(Request $request, $vstdate, $cid)
    {
        $token = DB::table('main_setting')
            ->where('name', 'token_authen_kiosk_nhso')
            ->value('value');

        // ตรวจสอบ token
        if (!$token) {
            return response()->json(['error' => 'Token not found'], 500);
        }

        // ส่ง request ไปยัง NHSO API
        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status", [
                'personalId' => $cid,
                'serviceDate' => $vstdate
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'NHSO API request failed', 'message' => $response->body()], 500);
        }

        $result = $response->json();

        if (!isset($result['firstName']) || !isset($result['serviceHistories'])) {
            return response()->json(['error' => 'Invalid data from NHSO API'], 500);
        }

        $firstName = $result['firstName'];
        $lastName = $result['lastName'];
        $mainInscl = $result['mainInscl']['id'] ?? '';
        $mainInsclName = $result['mainInscl']['name'] ?? '';
        $subInscl = $result['subInscl']['id'] ?? '';
        $subInsclName = $result['subInscl']['name'] ?? '';

        $services = $result['serviceHistories'];

        foreach ($services as $row) {
            $serviceDateTime = $row['serviceDateTime'] ?? null;
            $sourceChannel = $row['sourceChannel'] ?? '';
            $claimCode = $row['claimCode'] ?? null;
            $claimType = $row['service']['code'] ?? null;

            if (!$claimCode || !$claimType) {
                continue; // ข้ามรายการที่ข้อมูลไม่ครบ
            }
            if (!($sourceChannel === 'ENDPOINT' || $claimType === 'PG0140001')) {
                continue;
            }

            $indiv = Nhso_Endpoint::firstOrNew([
                'cid' => $cid,
                'claimCode' => $claimCode,
            ]);

            // ถ้าเป็นรายการใหม่ หรือแก้ไขได้ตามเงื่อนไข
            if (!$indiv->exists || $sourceChannel == 'ENDPOINT' || $claimType == 'PG0140001') {
                $indiv->firstName = $firstName;
                $indiv->lastName = $lastName;
                $indiv->mainInscl = $mainInscl;
                $indiv->mainInsclName = $mainInsclName;
                $indiv->subInscl = $subInscl;
                $indiv->subInsclName = $subInsclName;
                $indiv->serviceDateTime = $serviceDateTime;
                $indiv->vstdate = date('Y-m-d', strtotime($serviceDateTime));
                $indiv->sourceChannel = $sourceChannel;
                $indiv->claimType = $claimType;
                $indiv->save();
            }
        }

        return response()->json(['success' => true]);

    }
    ###################################################################################################
//Create nhso_endpoint_pull_yesterday
    public function nhso_endpoint_pull_yesterday()
    {
        set_time_limit(600);
        $vstdate = Carbon::yesterday('Asia/Bangkok')->format('Y-m-d');

        $hosxp = DB::connection('hosxp')->select('
        SELECT o.vn, o.hn, pt.cid, vp.auth_code
        FROM ovst o
        INNER JOIN visit_pttype vp ON vp.vn = o.vn 
        LEFT JOIN patient pt ON pt.hn = o.hn
        LEFT JOIN hrims.nhso_endpoint nep ON nep.vstdate = o.vstdate AND nep.cid = pt.cid
        WHERE o.vstdate = ? AND nep.cid IS NULL'
            ,
            [$vstdate]
        );

        $cids = array_map(static fn($row) => $row->cid, $hosxp);
        $token = DB::table('main_setting')->where('name', 'token_authen_kiosk_nhso')->value('value');

        foreach (array_chunk($cids, 20) as $chunk) {
            $existing_claims = Nhso_Endpoint::whereIn('cid', $chunk)
                ->where('vstdate', $vstdate)
                ->pluck('claimType', 'claimCode')
                ->toArray();

            foreach ($chunk as $cid) {
                try {
                    $response = Http::timeout(5)
                        ->withToken($token)
                        ->acceptJson()
                        ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                            'personalId' => $cid,
                            'serviceDate' => $vstdate,
                        ]);

                    if ($response->failed())
                        continue;

                    $result = $response->json();
                    if (!is_array($result) || !isset($result['firstName']) || empty($result['serviceHistories'])) {
                        continue;
                    }

                    foreach ($result['serviceHistories'] as $row) {
                        if (!is_array($row))
                            continue;

                        $claimCode = $row['claimCode'] ?? null;
                        $claimType = $row['service']['code'] ?? null;
                        $sourceChannel = $row['sourceChannel'] ?? '';
                        $serviceDateTime = $row['serviceDateTime'] ?? null;

                        if (!$claimCode)
                            continue;

                        if (isset($existing_claims[$claimCode])) {
                            if ($existing_claims[$claimCode] !== $claimType) {
                                Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
                            }
                        } elseif ($sourceChannel === 'ENDPOINT' || $claimType === 'PG0140001') {
                            Nhso_Endpoint::create([
                                'cid' => $cid,
                                'firstName' => $result['firstName'] ?? null,
                                'lastName' => $result['lastName'] ?? null,
                                'mainInscl' => $result['mainInscl']['id'] ?? null,
                                'mainInsclName' => $result['mainInscl']['name'] ?? null,
                                'subInscl' => $result['subInscl']['id'] ?? null,
                                'subInsclName' => $result['subInscl']['name'] ?? null,
                                'serviceDateTime' => $serviceDateTime,
                                'vstdate' => $serviceDateTime ? date('Y-m-d', strtotime($serviceDateTime)) : $vstdate,
                                'sourceChannel' => $sourceChannel,
                                'claimCode' => $claimCode,
                                'claimType' => $claimType,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::error("NHSO Pull Yesterday logic error for CID: {$cid}", ['msg' => $e->getMessage()]);
                }
            }
            usleep(300000);
        }

        return response()->json(['success' => true, 'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ']);
    }
    ##############################################################################################
    public function opd_ofc(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sql = DB::connection('hosxp')->select('
        SELECT o.vstdate,o.vsttime,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
        pt.cid,pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        v.pdx,IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
        IFNULL(vp.Claim_Code,os.edc_approve_list_text) AS edc,IF(ppfs.vn IS NOT NULL,"Y",NULL) AS ppfs
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN ovst_seq os ON os.vn = o.vn
        LEFT JOIN vn_stat v ON v.vn = o.vn
        LEFT JOIN (
            SELECT ori.vn 
            FROM opitemrece ori 
            INNER JOIN hrims.lookup_icode li ON li.icode = ori.icode AND li.ppfs = "Y"
            WHERE ori.vstdate BETWEEN ? AND ?
            GROUP BY ori.vn
        ) ppfs ON ppfs.vn=o.vn
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
        WHERE o.vstdate BETWEEN ? AND ? AND p.hipdata_code = "OFC" AND (o.an ="" OR o.an IS NULL)
        GROUP BY o.vn ORDER BY ep.claimCode DESC ,o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('home_detail.opd_ofc', compact('start_date', 'end_date', 'sql'));
    }
    #################################################################################################
    public function opd_non_authen(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sql = DB::connection('hosxp')->select('
        SELECT o.vstdate,o.vsttime,o.oqueue,o.hn,p.cid,p.mobile_phone_number,p.hometel,p1.`name` AS pttype,
        vp.hospmain,k.department,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,v.age_y,
        v.income,v.rcpt_money,v.income-v.paid_money AS debtor
        FROM ovst o
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN patient p ON p.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p1 ON p1.pttype=vp.pttype
        LEFT JOIN kskdepartment k ON k.depcode=o.main_dep
        WHERE o.vstdate BETWEEN ? AND ?        
        AND p.cid NOT LIKE "0%" 
        AND (vp.auth_code ="" OR vp.auth_code IS NULL)  
        GROUP BY o.vn ORDER BY o.vsttime', [$start_date, $end_date]);

        return view('home_detail.opd_non_authen', compact('start_date', 'end_date', 'sql'));
    }
    ##############################################################################################
    public function opd_non_hospmain(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sql = DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        o.vstdate,o.vsttime,o.oqueue,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
        pt.cid,pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,v.income,v.rcpt_money,v.income-v.paid_money AS debtor        
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN vn_stat v ON v.vn = o.vn
        WHERE o.vstdate BETWEEN ? AND ?
        AND p.hipdata_code IN ("UCS","WEL","SSS","STP") 
        AND (vp.hospmain="" OR vp.hospmain IS NULL)
        GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date]);

        return view('home_detail.opd_non_hospmain', compact('start_date', 'end_date', 'sql'));
    }
    ##############################################################################################
    public function opd_ucs_anywhere(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search = DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        et.ucae AS er,p24.project,vp.nhso_ucae_type_code AS ae,
        rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno        
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN er_regist e ON e.vn=o.vn 
        LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")        
        LEFT JOIN (
            SELECT ori.vn, GROUP_CONCAT(DISTINCT ni.nhso_adp_code) as project
            FROM opitemrece ori
            INNER JOIN nondrugitems ni ON ni.icode = ori.icode AND ni.nhso_adp_code IN ("WALKIN","UCEP24")
            WHERE ori.vstdate BETWEEN ? AND ?
            GROUP BY ori.vn
        ) p24 ON p24.vn = o.vn    
        LEFT JOIN vn_stat v ON v.vn = o.vn
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND stm.vstdate = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"     
        WHERE (o.an ="" OR o.an IS NULL) 
        AND o.vstdate BETWEEN ? AND ?
        AND p.hipdata_code IN ("UCS","WEL") 
        AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")
        GROUP BY o.vn ORDER BY o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('home_detail.opd_ucs_anywhere', compact('start_date', 'end_date', 'search'));
    }
    ##############################################################################################
    public function opd_ucs_cr(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search = DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        GROUP_CONCAT(DISTINCT s.`name`) AS claim_list, SUM(o1.sum_price) AS claim_price,
        p24.project, rep.rep_eclaim_detail_nhso AS rep_nhso,
        rep.rep_eclaim_detail_error_code AS rep_error, stm.receive_inst+stm.receive_op+stm.receive_palliative
            +stm.receive_dmis_drug+stm.receive_hc_drug+stm.receive_hc_hc AS receive_total,stm.repno 
        FROM ovst o    
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        INNER JOIN opitemrece o1 ON o1.vn=o.vn
        INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.uc_cr = "Y" 
        LEFT JOIN s_drugitems s ON s.icode = o1.icode
        LEFT JOIN (
            SELECT ori.vn, GROUP_CONCAT(DISTINCT ni.nhso_adp_code) as project
            FROM opitemrece ori
            INNER JOIN nondrugitems ni ON ni.icode = ori.icode AND ni.nhso_adp_code IN ("WALKIN","UCEP24")
            WHERE ori.vstdate BETWEEN ? AND ?
            GROUP BY ori.vn
        ) p24 ON p24.vn = o.vn
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND stm.vstdate = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"       
        WHERE p.hipdata_code IN ("UCS","WEL") 
        AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
        AND (o.an IS NULL OR o.an ="") 
        AND o.vstdate BETWEEN ? AND ?
        GROUP BY o.vn ORDER BY o.vstdate,o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return view('home_detail.opd_ucs_cr', compact('start_date', 'end_date', 'search'));
    }
    ##############################################################################################
    public function opd_ucs_herb(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search = DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        GROUP_CONCAT(DISTINCT s.`name`) AS claim_list, SUM(o1.sum_price) AS claim_price,
        p24.project,rep.rep_eclaim_detail_nhso AS rep_nhso,
        rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_hc_hc AS receive_total,stm.repno 
        FROM ovst o    
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        INNER JOIN opitemrece o1 ON o1.vn=o.vn
        INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.herb32 = "Y" 
        LEFT JOIN s_drugitems s ON s.icode = o1.icode
        LEFT JOIN (
            SELECT ori.vn, GROUP_CONCAT(DISTINCT ni.nhso_adp_code) as project
            FROM opitemrece ori
            INNER JOIN nondrugitems ni ON ni.icode = ori.icode AND ni.nhso_adp_code IN ("WALKIN","UCEP24")
            WHERE ori.vstdate BETWEEN ? AND ?
            GROUP BY ori.vn
        ) p24 ON p24.vn = o.vn
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND stm.vstdate = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"       
        WHERE p.hipdata_code IN ("UCS","WEL") 
        AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
        AND (o.an IS NULL OR o.an ="") 
        AND o.vstdate BETWEEN ? AND ?
        GROUP BY o.vn ORDER BY o.vstdate,o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return view('home_detail.opd_ucs_herb', compact('start_date', 'end_date', 'search'));
    }
    ##############################################################################################
    public function opd_ucs_healthmed(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search = DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.vstdate,o.vsttime,
        o.oqueue,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,k.department ,
			GROUP_CONCAT(DISTINCT hm.operation) AS operation
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN kskdepartment k ON k.depcode = o.cur_dep				
        LEFT JOIN vn_stat v ON v.vn = o.vn
        INNER JOIN (
            SELECT h.vn, CONCAT(h2.health_med_operation_item_name," [",h2.icd10tm,"]") AS operation 
            FROM health_med_service h
            INNER JOIN health_med_service_operation h1 ON h1.health_med_service_id=h.health_med_service_id
            INNER JOIN health_med_operation_item h2 ON h2.health_med_operation_item_id=h1.health_med_operation_item_id
            WHERE h.service_date BETWEEN ? AND ?
        ) hm ON hm.vn=o.vn
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
        WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
        AND p.hipdata_code IN ("UCS","WEL") AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")          
        GROUP BY o.vn ORDER BY ep.claimCode DESC,o.vstdate,o.vsttime', [$start_date, $end_date, $start_date, $end_date]);

        return view('home_detail.opd_ucs_healthmed', compact('start_date', 'end_date', 'search'));
    }

    ##############################################################################################
    public function opd_ppfs(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search = DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,o.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        GROUP_CONCAT(DISTINCT s.`name`) AS claim_list, SUM(o1.sum_price) AS claim_price,
        p24.project,rep.rep_eclaim_detail_nhso AS rep_nhso,
        rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_pp AS receive_total,stm.repno
        FROM ovst o    
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        INNER JOIN opitemrece o1 ON o1.vn=o.vn
        INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.ppfs = "Y" 
        LEFT JOIN s_drugitems s ON s.icode = o1.icode
        LEFT JOIN (
            SELECT ori.vn, GROUP_CONCAT(DISTINCT ni.nhso_adp_code) as project
            FROM opitemrece ori
            INNER JOIN nondrugitems ni ON ni.icode = ori.icode AND ni.nhso_adp_code IN ("WALKIN","UCEP24")
            WHERE ori.vstdate BETWEEN ? AND ?
            GROUP BY ori.vn
        ) p24 ON p24.vn = o.vn
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND stm.vstdate = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"       
        WHERE (o.an IS NULL OR o.an ="") AND o.vstdate BETWEEN ? AND ?
        GROUP BY o.vn ORDER BY o.vstdate,o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return view('home_detail.opd_ppfs', compact('start_date', 'end_date', 'search'));
    }

    ##############################################################################################
    public function ipd_homeward(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sql = DB::connection('hosxp')->select('
        SELECT ep.claimCode,o.vstdate,o.vsttime,o.oqueue,o.hn,p.cid,p.mobile_phone_number,
        p.hometel,p1.`name` AS pttype,vp.hospmain,k.department,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,v.age_y,
        v.income,v.rcpt_money,v.income-v.paid_money AS debtor
        FROM ovst o
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN patient p ON p.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn AND vp.pttype_number = 1
        LEFT JOIN pttype p1 ON p1.pttype=vp.pttype				
        LEFT JOIN kskdepartment k ON k.depcode=o.main_dep
		LEFT JOIN ipt i ON i.an=o.an AND i.ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND (ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001")
        WHERE (i.an IS NOT NULL OR i.an <>"") AND o.vstdate BETWEEN ? AND ?
		GROUP BY o.vn ORDER BY o.vsttime', [$start_date, $end_date]);

        return view('home_detail.ipd_homeward', compact('start_date', 'end_date', 'sql'));
    }
    ##############################################################################################
    public function ipd_non_dchsummary(Request $request)
    {
        $budget_year_now = DB::table('budget_year')
            ->where('DATE_END', '>=', date('Y-m-d'))
            ->where('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');
        $budget_year = $request->budget_year ?: $budget_year_now;
        $year_data = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->first(['DATE_BEGIN', 'DATE_END']);
        $start_date = $year_data->DATE_BEGIN ?? null;
        $end_date = $year_data->DATE_END ?? null;

        $results = DB::connection('hosxp')->select('
        SELECT w.`name` AS ward, i.hn, i.an, id.icd10, a.diag_text_list, d.`name` AS owner_doctor_name,
        i.dchdate, TIMESTAMPDIFF(day, i.dchdate, DATE(NOW())) AS dch_day,
        CASE 
            WHEN (a.diag_text_list = "" OR a.diag_text_list IS NULL) THEN "รอแพทย์สรุป Chart"
            ELSE "รอลงรหัสวินิจฉัยโรค" 
        END AS diag_status,
        CASE 
            WHEN (a.diag_text_list = "" OR a.diag_text_list IS NULL) THEN "non_diagtext"
            ELSE "non_icd10" 
        END AS category
        FROM ipt i
        LEFT JOIN ward w ON w.ward = i.ward 
        LEFT JOIN iptdiag id ON id.an = i.an AND id.diagtype = 1
        LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
        LEFT JOIN doctor d ON d.`code` = il.doctor
        LEFT JOIN an_stat a ON a.an = i.an
        WHERE i.dchdate BETWEEN ? AND ?        
        AND (
            (a.diag_text_list = "" OR a.diag_text_list IS NULL)
            OR 
            ((a.diag_text_list <> "" AND a.diag_text_list IS NOT NULL) AND (id.icd10 = "" OR id.icd10 IS NULL))
        )
        AND i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
        GROUP BY i.an
        ORDER BY d.`name`, dch_day DESC', [$start_date, $end_date]);

        $non_diagtext_list = [];
        $non_icd10_list = [];

        foreach ($results as $row) {
            if ($row->category === 'non_diagtext') {
                $non_diagtext_list[] = $row;
            } else {
                $non_icd10_list[] = $row;
            }
        }

        // Summary by doctor for charts
        $summary_sql = '
            SELECT d.`name` AS owner_doctor_name, 
            SUM(CASE WHEN (a.diag_text_list = "" OR a.diag_text_list IS NULL) THEN 1 ELSE 0 END) as non_diagtext_count,
            SUM(CASE WHEN (a.diag_text_list <> "" AND a.diag_text_list IS NOT NULL) AND (id.icd10 = "" OR id.icd10 IS NULL) THEN 1 ELSE 0 END) as non_icd10_count
            FROM ipt i
            LEFT JOIN iptdiag id ON id.an = i.an AND id.diagtype = 1
            LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
            LEFT JOIN doctor d ON d.`code` = il.doctor
            LEFT JOIN an_stat a ON a.an = i.an
            WHERE i.dchdate BETWEEN ? AND ?
            AND i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
            GROUP BY d.`name`
            HAVING SUM(CASE WHEN (a.diag_text_list = "" OR a.diag_text_list IS NULL) THEN 1 ELSE 0 END) > 0 
            OR SUM(CASE WHEN (a.diag_text_list <> "" AND a.diag_text_list IS NOT NULL) AND (id.icd10 = "" OR id.icd10 IS NULL) THEN 1 ELSE 0 END) > 0
            ORDER BY (SUM(CASE WHEN (a.diag_text_list = "" OR a.diag_text_list IS NULL) THEN 1 ELSE 0 END) + SUM(CASE WHEN (a.diag_text_list <> "" AND a.diag_text_list IS NOT NULL) AND (id.icd10 = "" OR id.icd10 IS NULL) THEN 1 ELSE 0 END)) DESC';

        $summary_stats = DB::connection('hosxp')->select($summary_sql, [$start_date, $end_date]);

        $chart_data = [
            'doctors' => array_column($summary_stats, 'owner_doctor_name'),
            'non_diagtext' => array_column($summary_stats, 'non_diagtext_count'),
            'non_icd10' => array_column($summary_stats, 'non_icd10_count')
        ];

        return view('home_detail.ipd_non_dchsummary', compact('non_diagtext_list', 'non_icd10_list', 'chart_data'));
    }
    ####################################################################################################################
    public function ipd_finance_chk_opd_wait_transfer(Request $request)
    {
        $finance_chk = DB::connection('hosxp')->select('
        SELECT w.`name` AS ward,i1.bedno,i.hn,i.an,i.regdate,p.`name` AS pttype,i2.hospmain,
        i.finance_transfer, a.opd_wait_money,a.item_money,a.uc_money-a.debt_money AS wait_debt_money,
        a.paid_money,a.rcpt_money,a.paid_money-a.rcpt_money AS wait_paid_money
        FROM ipt i
        LEFT JOIN ward w ON w.ward=i.ward
        LEFT JOIN iptadm i1 ON i1.an = i.an
        LEFT JOIN ipt_pttype i2 ON i2.an = i.an AND i2.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=i2.pttype
        LEFT JOIN an_stat a ON a.an=i.an
        WHERE i.confirm_discharge = "N" 
        AND a.opd_wait_money <>"0" GROUP BY i.an 
        ORDER BY a.opd_wait_money DESC,i.ward,wait_paid_money DESC');

        return view('home_detail.ipd_finance_chk', compact('finance_chk'));
    }
    public function ipd_finance_chk_wait_rcpt_money(Request $request)
    {
        $finance_chk = DB::connection('hosxp')->select('
        SELECT w.`name` AS ward,i1.bedno,i.hn,i.an,i.regdate,p.`name` AS pttype,i2.hospmain,
        i.finance_transfer,a.opd_wait_money,a.item_money,a.uc_money-a.debt_money AS wait_debt_money,
        a.paid_money,a.rcpt_money,a.paid_money-a.rcpt_money AS wait_paid_money
        FROM ipt i
        LEFT JOIN ward w ON w.ward=i.ward
        LEFT JOIN iptadm i1 ON i1.an = i.an
        LEFT JOIN ipt_pttype i2 ON i2.an = i.an AND i2.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=i2.pttype
        LEFT JOIN an_stat a ON a.an=i.an
        WHERE i.confirm_discharge = "N" 
        AND (a.paid_money-a.rcpt_money <>"0") GROUP BY i.an 
        ORDER BY a.opd_wait_money DESC,i.ward,wait_paid_money DESC');

        return view('home_detail.ipd_finance_chk', compact('finance_chk'));
    }

}
