<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Debtor_1102050101_103;
use App\Models\Debtor_1102050101_109;
use App\Models\Debtor_1102050101_201;
use App\Models\Debtor_1102050101_202;
use App\Models\Debtor_1102050101_203;
use App\Models\Debtor_1102050101_209;
use App\Models\Debtor_1102050101_216;
use App\Models\Debtor_1102050101_217;
use App\Models\Debtor_1102050101_301;
use App\Models\Debtor_1102050101_302;
use App\Models\Debtor_1102050101_303;
use App\Models\Debtor_1102050101_304;
use App\Models\Debtor_1102050101_307;
use App\Models\Debtor_1102050101_308;
use App\Models\Debtor_1102050101_309;
use App\Models\Debtor_1102050101_310;
use App\Models\Debtor_1102050101_401;
use App\Models\Debtor_1102050101_402;
use App\Models\Debtor_1102050101_501;
use App\Models\Debtor_1102050101_502;
use App\Models\Debtor_1102050101_503;
use App\Models\Debtor_1102050101_504;
use App\Models\Debtor_1102050101_701;
use App\Models\Debtor_1102050101_702;
use App\Models\Debtor_1102050101_703;
use App\Models\Debtor_1102050101_704;
use App\Models\Debtor_1102050102_106;
use App\Models\Debtor_1102050102_106_tracking;
use App\Models\Debtor_1102050102_107;
use App\Models\Debtor_1102050102_107_tracking;
use App\Models\Debtor_1102050102_108;
use App\Models\Debtor_1102050102_109;
use App\Models\Debtor_1102050102_110;
use App\Models\Debtor_1102050102_111;
use App\Models\Debtor_1102050102_602;
use App\Models\Debtor_1102050102_603;
use App\Models\Debtor_1102050102_801;
use App\Models\Debtor_1102050102_802;
use App\Models\Debtor_1102050102_803;
use App\Models\Debtor_1102050102_804;

class DebtorController extends Controller
{
    //Check Login---------------------------------------------------------------------
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_debtor !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'ลูกหนี้ค่ารักษา'], 403);
                }
                return $next($request);
            }
        ]);
    }
    //index---------------------------------------------------------------------------
    public function index()
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');

        return view('debtor.index', compact('hospital_name', 'hospital_code'));
    }
    //_check_income---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
    public function _check_income(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: date('Y-m-d', strtotime("-1 day"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("-1 day"));

        // Validate and format dates to prevent SQL Injection
        $start_date = date('Y-m-d', strtotime($start_date));
        $end_date = date('Y-m-d', strtotime($end_date));

        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        $check_income = DB::connection('hosxp')->select("
            SELECT o.op_income,o.op_paid,v.vn_income,v.vn_paid,v.vn_rcpt,v.vn_ppfs,
                v.vn_income - v.vn_rcpt - v.vn_ppfs AS vn_debtor,
                IF(v.vn_income <> o.op_income, 'Resync VN', 'Success') AS status_check
            FROM(
                SELECT 
                    SUM(CASE 
                        WHEN i.vn IS NULL THEN v.income 
                        ELSE IFNULL(ems.income, 0)
                    END) AS vn_income,
                    SUM(CASE 
                        WHEN i.vn IS NULL THEN v.paid_money 
                        ELSE IFNULL(ems.paid_money, 0)
                    END) AS vn_paid,
                    SUM(IFNULL(rc.rcpt_money,0)) AS vn_rcpt,
                    SUM(IFNULL(pp.ppfs_price,0)) AS vn_ppfs
                FROM ovst o
                INNER JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN ipt i ON i.vn = o.vn
                LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                        GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    AND EXISTS (SELECT 1 FROM ovst WHERE vn = r.vn) -- OPD receipts only
                    GROUP BY r.vn) rc ON rc.vn = o.vn
                LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS ppfs_price
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.ppfs = 'Y' 
                    WHERE op.vstdate BETWEEN '{$start_date}' AND '{$end_date}' AND op.paidst IN ('02')
                    GROUP BY op.vn) pp ON pp.vn = o.vn 
                LEFT JOIN (
                    SELECT op.vn, SUM(op.sum_price) AS income,
                           SUM(op.sum_price) AS paid_money -- No paidst filter for EMS
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.ems = 'Y'
                    WHERE op.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
                    AND (op.an IS NULL OR op.an = '')
                    GROUP BY op.vn
                ) ems ON ems.vn = o.vn
                WHERE o.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
                ) v
                
            CROSS JOIN
            
            (SELECT SUM(op.sum_price) AS op_income,
                SUM(CASE WHEN op.paidst IN ('01','03') THEN op.sum_price ELSE 0 END) AS op_paid
                FROM ovst o
                INNER JOIN opitemrece op ON op.vn = o.vn
                LEFT JOIN ipt i ON i.vn = o.vn
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                WHERE o.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
                AND (i.vn IS NULL OR (i.vn IS NOT NULL AND li.ems = 'Y'))
                ) o");

        $check_income_pttype = DB::connection('hosxp')->select("
            SELECT p.hipdata_code AS inscl,
                CASE WHEN p.hipdata_code IN ('A1','CSH') THEN 'ชำระเงิน'
                    WHEN p.hipdata_code IN ('A9','INS') THEN 'พรบ.'
                    WHEN p.hipdata_code = 'BKK' THEN 'กทม.'
                    WHEN p.hipdata_code = 'PTY' THEN 'พัทยา'
                    WHEN p.hipdata_code = 'BMT' THEN 'ขสมก.'
                    WHEN p.hipdata_code = 'KKT' THEN 'กกต.'
                    WHEN p.hipdata_code = 'GOF' THEN 'เบิกต้นสังกัด'
                    WHEN p.hipdata_code = 'LGO' THEN 'อปท.'
                    WHEN p.hipdata_code = 'NRD' THEN 'ต่างด้าวไม่ขึ้นทะเบียน'
                    WHEN p.hipdata_code = 'NRH' THEN 'ต่างด้าวขึ้นทะเบียน'
                    WHEN p.hipdata_code = 'OFC' THEN 'กรมบัญชีกลาง'
                    WHEN p.hipdata_code = 'SSI' THEN 'ปกส.ทุพพลภาพ'
                    WHEN p.hipdata_code = 'SSS' THEN 'ปกส.'
                    WHEN p.hipdata_code = 'STP' THEN 'ผู้มีปัญหาสถานะสิทธิ'
                    WHEN p.hipdata_code = 'UCS' THEN 'ประกันสุขภาพ'
                    WHEN p.hipdata_code = 'SRT' THEN 'การรถไฟแห่งประเทศไทย'
                    WHEN p.hipdata_code = 'NHS' THEN 'สิทธิ สปสช.'
                    ELSE 'คนไข้ไม่ลงสิทธิ' END AS pttype_group,
                COUNT(DISTINCT o_split.vn) AS vn,
                SUM(IFNULL(vi.income,0)) AS income,
                SUM(IFNULL(vi.paid_money,0)) AS paid_money,
                SUM(IFNULL(rc.rcpt_money,0)) AS rcpt_money,
                SUM(IFNULL(pp.ppfs_price,0)) AS ppfs,
                SUM(IFNULL(vi.income,0)) - SUM(IFNULL(rc.rcpt_money,0)) - SUM(IFNULL(pp.ppfs_price,0)) AS debtor
            FROM (
                SELECT DISTINCT o.vn, o.pttype AS main_pttype, IFNULL(vp.pttype, o.pttype) AS pttype
                FROM ovst o 
                LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                WHERE o.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
            ) o_split
            LEFT JOIN ipt i ON i.vn = o_split.vn
            LEFT JOIN pttype p ON p.pttype = o_split.pttype

            -- 1. Subquery ค่าใช้จ่าย
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype, 
                    SUM(CASE 
                        WHEN i.vn IS NULL THEN op.sum_price 
                        WHEN i.vn IS NOT NULL AND li.ems = 'Y' THEN op.sum_price 
                        ELSE 0 
                    END) AS income,
                    SUM(CASE 
                        WHEN (i.vn IS NOT NULL AND li.ems = 'Y') THEN op.sum_price 
                        WHEN op.paidst IN ('01','03') AND i.vn IS NULL THEN op.sum_price 
                        ELSE 0 END) AS paid_money
                FROM opitemrece op
                LEFT JOIN ipt i ON i.vn = op.vn
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                WHERE op.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
                AND (op.an IS NULL OR op.an = '')
                GROUP BY op.vn, op.pttype
            ) vi ON vi.vn = o_split.vn AND vi.pttype = o_split.pttype

            -- 2. Subquery ใบเสร็จ (Merged Original + Orphan)
            LEFT JOIN (
                SELECT 
                    r.vn, 
                    IF(vp.pttype IS NOT NULL, r.pttype, o.pttype) AS mapped_pttype,
                    SUM(r.total_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM ovst o
                INNER JOIN rcpt_print r ON r.vn = o.vn
                LEFT JOIN visit_pttype vp ON vp.vn = r.vn AND vp.pttype = r.pttype
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE o.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
                AND a.rcpno IS NULL                                        
                GROUP BY r.vn, mapped_pttype
            ) rc ON rc.vn = o_split.vn AND rc.mapped_pttype = o_split.pttype

            -- 3. Subquery PPFS
            LEFT JOIN (
                SELECT op.vn, op.pttype, SUM(op.sum_price) AS ppfs_price
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.ppfs = 'Y' 
                WHERE op.vstdate BETWEEN '{$start_date}' AND '{$end_date}' AND op.paidst IN ('02')
                GROUP BY op.vn, op.pttype
            ) pp ON pp.vn = o_split.vn AND pp.pttype = o_split.pttype

            GROUP BY p.hipdata_code
            ORDER BY p.hipdata_code");

        $check_income_ipd = DB::connection('hosxp')->select("
            SELECT o.op_income,o.op_paid,v.an_income,v.an_paid,v.an_rcpt,v.an_income-v.an_rcpt AS an_debtor,
            IF(v.an_income <> o.op_income, 'Resync AN', 'Success') AS status_check
            FROM (SELECT SUM(income) AS an_income,SUM(paid_money) AS an_paid,SUM(rcpt_money) AS an_rcpt
                FROM an_stat 
                WHERE dchdate BETWEEN '{$start_date}' AND '{$end_date}') v
            CROSS JOIN
                (SELECT SUM(o.sum_price) AS op_income,SUM(CASE WHEN o.paidst IN ('01','03') THEN o.sum_price ELSE 0 END) AS op_paid
                FROM opitemrece o 
                INNER JOIN an_stat a ON a.an = o.an 
                WHERE a.dchdate BETWEEN '{$start_date}' AND '{$end_date}') o"
        );

        $check_income_ipd_pttype = DB::connection('hosxp')->select("
            SELECT p.hipdata_code AS inscl,
                CASE WHEN p.hipdata_code IN ('A1','CSH') THEN 'ชำระเงิน'
                    WHEN p.hipdata_code IN ('A9','INS') THEN 'พรบ.'
                    WHEN p.hipdata_code = 'BKK' THEN 'กทม.'
                    WHEN p.hipdata_code = 'PTY' THEN 'พัทยา'
                    WHEN p.hipdata_code = 'BMT' THEN 'ขสมก.'
                    WHEN p.hipdata_code = 'KKT' THEN 'กกต.'
                    WHEN p.hipdata_code = 'GOF' THEN 'เบิกต้นสังกัด'
                    WHEN p.hipdata_code = 'LGO' THEN 'อปท.'
                    WHEN p.hipdata_code = 'NRD' THEN 'ต่างด้าวไม่ขึ้นทะเบียน'
                    WHEN p.hipdata_code = 'NRH' THEN 'ต่างด้าวขึ้นทะเบียน'
                    WHEN p.hipdata_code = 'OFC' THEN 'กรมบัญชีกลาง'
                    WHEN p.hipdata_code = 'SSI' THEN 'ปกส.ทุพพลภาพ'
                    WHEN p.hipdata_code = 'SSS' THEN 'ปกส.'
                    WHEN p.hipdata_code = 'STP' THEN 'ผู้มีปัญหาสถานะสิทธิ'
                    WHEN p.hipdata_code = 'UCS' THEN 'ประกันสุขภาพ'
                    WHEN p.hipdata_code = 'SRT' THEN 'การรถไฟแห่งประเทศไทย'
                    WHEN p.hipdata_code = 'NHS' THEN 'สิทธิ สปสช.'
                    ELSE 'ไม่พบเงื่อนไข' END AS pttype_group,
                SUM(ip.num_an) AS an,
                SUM(IFNULL(v_inc.income,0)) AS income,
                SUM(IFNULL(v_inc.paid_money,0)) AS paid_money,
                SUM(IFNULL(rc.rcpt_money,0)) AS rcpt_money,
                SUM(IFNULL(v_inc.income,0)) - SUM(IFNULL(rc.rcpt_money,0)) AS debtor
            FROM (
                SELECT DISTINCT i.an, ipt_p.pttype, 1 AS num_an
                FROM ipt i
                INNER JOIN ipt_pttype ipt_p ON ipt_p.an = i.an
                WHERE i.dchdate BETWEEN '{$start_date}' AND '{$end_date}'
            ) ip
            INNER JOIN ipt i ON i.an = ip.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype

            -- 1. Subquery รายได้ (หัก EMS ออก)
            LEFT JOIN (
                SELECT 
                    op.an, op.pttype, 
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN op.paidst IN ('01','03') THEN op.sum_price ELSE 0 END) AS paid_money
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                WHERE op.an IS NOT NULL AND op.an <> ''
                AND (li.ems IS NULL OR li.ems <> 'Y')
                GROUP BY op.an, op.pttype
            ) v_inc ON v_inc.an = ip.an AND v_inc.pttype = ip.pttype

            -- 2. Subquery ใบเสร็จ (Optimization Map สิทธิใบเสร็จที่ผิดเข้าสิทธิหลักของ Admit)
            LEFT JOIN (
                SELECT 
                    r.vn AS an, 
                    IF(ipt_p.pttype IS NOT NULL, r.pttype, 
                       (SELECT pttype FROM ipt_pttype WHERE an = r.vn ORDER BY pttype_number LIMIT 1)
                    ) AS mapped_pttype,
                    SUM(r.total_amount) AS rcpt_money,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM ipt i
                INNER JOIN rcpt_print r ON r.vn = i.an
                LEFT JOIN ipt_pttype ipt_p ON ipt_p.an = r.vn AND ipt_p.pttype = r.pttype
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE i.dchdate BETWEEN '{$start_date}' AND '{$end_date}'
                AND a.rcpno IS NULL                       
                GROUP BY r.vn, mapped_pttype
            ) rc ON rc.an = ip.an AND rc.mapped_pttype = ip.pttype

            WHERE i.dchdate BETWEEN '{$start_date}' AND '{$end_date}'
            GROUP BY p.hipdata_code
            ORDER BY p.hipdata_code");

        return view('debtor._check_income', compact(
            'start_date',
            'end_date',
            'check_income',
            'check_income_pttype',
            'check_income_ipd_pttype',
            'check_income_ipd'
        ));
    }
    //_check_income_detail--------------------------------------------------------------------------------------------------------------------------
    public function _check_income_detail(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $type = $request->type; // opd | ipd

        // Priority: 1. Fetch Request 2. Session Defaults 3. Yesterday format 
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d', strtotime("-1 day"));
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d', strtotime("-1 day"));

        // Validate and format dates to prevent SQL Injection
        $start_date = date('Y-m-d', strtotime($start_date));
        $end_date = date('Y-m-d', strtotime($end_date));

        if ($type === 'opd') {
            // ---------------- OPD ----------------
            // Compare vn_stat.income vs Total opitemrece.sum_price
            $data = DB::connection('hosxp')->select("
                SELECT date_serv, anvn, hn, SUM(vn_stat_inc) AS income, SUM(op_inc) AS sum_price, ROUND(SUM(vn_stat_inc) - SUM(op_inc), 2) AS diff
                FROM (
                    -- Section 1: VN_STAT Income (Truth from HOSxP summary table)
                    SELECT o.vstdate AS date_serv, o.vn AS anvn, o.hn, v.income AS vn_stat_inc, 0 AS op_inc
                    FROM ovst o
                    INNER JOIN vn_stat v ON v.vn = o.vn
                    LEFT JOIN ipt i ON i.vn = o.vn
                    WHERE o.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
                    AND i.vn IS NULL
                    
                    UNION ALL
                    
                    -- Section 2: OP Income (All items in opitemrece related to this VN)
                    SELECT o.vstdate AS date_serv, o.vn AS anvn, o.hn, 0 AS vn_stat_inc, SUM(op.sum_price) AS op_inc
                    FROM ovst o
                    INNER JOIN opitemrece op ON op.vn = o.vn
                    LEFT JOIN ipt i ON i.vn = o.vn
                    WHERE o.vstdate BETWEEN '{$start_date}' AND '{$end_date}'
                    AND i.vn IS NULL
                    GROUP BY o.vn
                ) t
                GROUP BY anvn
                HAVING ROUND(SUM(vn_stat_inc), 2) <> ROUND(SUM(op_inc), 2)
                ORDER BY ABS(SUM(vn_stat_inc) - SUM(op_inc)) DESC
            ");
        } else {
            // ---------------- IPD ----------------
            // Compare an_stat.income vs Total opitemrece.sum_price
            $data = DB::connection('hosxp')->select("
                SELECT date_serv, anvn, hn, SUM(an_stat_inc) AS income, SUM(op_inc) AS sum_price, ROUND(SUM(an_stat_inc) - SUM(op_inc), 2) AS diff
                FROM (
                    -- Section 1: AN_STAT Income (Truth from HOSxP summary table)
                    SELECT a.dchdate AS date_serv, a.an AS anvn, a.hn, a.income AS an_stat_inc, 0 AS op_inc
                    FROM an_stat a
                    WHERE a.dchdate BETWEEN '{$start_date}' AND '{$end_date}'
                    
                    UNION ALL
                    
                    -- Section 2: OP Income (All items in opitemrece related to this AN)
                    SELECT a.dchdate AS date_serv, a.an AS anvn, a.hn, 0 AS an_stat_inc, SUM(op.sum_price) AS op_inc
                    FROM an_stat a
                    INNER JOIN opitemrece op ON op.an = a.an
                    WHERE a.dchdate BETWEEN '{$start_date}' AND '{$end_date}'
                    GROUP BY a.an
                ) t
                GROUP BY anvn
                HAVING ROUND(SUM(an_stat_inc), 2) <> ROUND(SUM(op_inc), 2)
                ORDER BY ABS(SUM(an_stat_inc) - SUM(op_inc)) DESC
            ");
        }

        return response()->json($data);
    }
    public function _check_nondebtor(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: date('Y-m-d', strtotime('-1 day'));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime('-1 day'));

        $check = DB::connection('hosxp')->select(
            "
            SELECT * FROM (SELECT 'OPD' AS dep,v.vstdate AS serv_date,v.vn AS vnan,v.hn,
                    CONCAT(pt.pname,pt.fname,' ',pt.lname) AS ptname,p.hipdata_code,p.name AS pttype,
                    vp.hospmain,v.pdx,IFNULL(inc.income,0) AS income,v.paid_money,
                    IFNULL(rc.rcpt_money,0) AS rcpt_money, IFNULL(pp.ppfs_price,0) AS ppfs_price,pp.ppfs_list,
                    IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(pp.ppfs_price,0) AS debtor
                FROM vn_stat v
                LEFT JOIN ipt i ON i.vn = v.vn
                LEFT JOIN visit_pttype vp ON vp.vn = v.vn
                LEFT JOIN pttype p ON p.pttype = vp.pttype
                LEFT JOIN patient pt ON pt.hn = v.hn
                LEFT JOIN (SELECT op.vn,op.pttype,SUM(op.sum_price) AS income
                    FROM opitemrece op
                    WHERE op.vstdate BETWEEN ? AND ?
                    GROUP BY op.vn, op.pttype) inc ON inc.vn = v.vn AND inc.pttype = vp.pttype
                LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                        GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno                 
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn) rc ON rc.vn = v.vn
                LEFT JOIN ( SELECT op.vn, SUM(CASE WHEN li.ppfs = 'Y' THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    GROUP_CONCAT(DISTINCT sd.`name` ORDER BY sd.`name`) AS ppfs_list
                    FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON li.icode = op.icode
                    LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                    WHERE li.ppfs = 'Y' AND op.vstdate BETWEEN ? AND ? GROUP BY op.vn) pp ON pp.vn = v.vn
                WHERE v.vstdate BETWEEN ? AND ?
                AND (i.an IS NULL OR i.an = '')
                AND IFNULL(inc.income,0) <> 0
                AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(pp.ppfs_price,0)) > 0
                AND v.vn NOT IN ( SELECT vn FROM hrims.debtor_1102050101_103 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_109 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_201 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_203 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_209 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_216 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_301 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_303 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_307 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_309 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_401 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_501 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_503 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_701 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050101_702 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050102_106 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050102_108 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050102_110 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050102_602 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050102_801 WHERE vstdate BETWEEN ? AND ?
                    UNION ALL SELECT vn FROM hrims.debtor_1102050102_803 WHERE vstdate BETWEEN ? AND ?
                ) GROUP BY v.vn
                    
                UNION ALL    
                    
                SELECT 'IPD' AS dep,i.dchdate AS serv_date,i.an AS vnan,i.hn,
                    CONCAT(pt.pname, pt.fname, ' ', pt.lname) AS ptname,
                    p.hipdata_code,p.name AS pttype,ip.hospmain,a.pdx,
                    IFNULL(inc.income,0) AS income,a.paid_money,
                    IFNULL(rc.rcpt_money,0) AS rcpt_money,0 AS ppfs_price,NULL AS ppfs_list,
                    IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0) AS debtor
                FROM ipt i
                LEFT JOIN ipt_pttype ip ON ip.an = i.an
                LEFT JOIN an_stat a ON a.an = i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN patient pt ON pt.hn = i.hn
                LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                    FROM opitemrece o
                    INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = 'Y' AND i2.dchdate BETWEEN ? AND ?
                    GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
                LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                        GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn) rc ON rc.an = i.an
                WHERE i.dchdate BETWEEN ? AND ?
                AND IFNULL(inc.income,0) <> 0
                AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)) > 0
                AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_202 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_217 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_302 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_304 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_307 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_308 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_310 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_402 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_502 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_504 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050101_704 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050102_107 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050102_109 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050102_111 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050102_603 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050102_802 WHERE dchdate BETWEEN ? AND ?
                    UNION ALL SELECT an FROM hrims.debtor_1102050102_804 WHERE dchdate BETWEEN ? AND ?
                )  GROUP BY a.an ) x
            ORDER BY dep DESC,hipdata_code, serv_date ",
            [
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                // Subqueries OPD (21 tables * 2 parameters)
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                // Subqueries IPD (17 tables * 2 parameters)
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date,
                $start_date,
                $end_date
            ]
        );

        return view('debtor._check_nondebtor', compact('start_date', 'end_date', 'check'));
    }
    //_summary-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function _summary(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');

        $_1102050101_103 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_103 
            WHERE vstdate BETWEEN ? AND ? ', [$start_date, $end_date]);
        $_1102050101_109 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_109 
            WHERE vstdate BETWEEN ? AND ? ', [$start_date, $end_date]);
        $_1102050101_201 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_201 
            WHERE vstdate BETWEEN ? AND ? ', [$start_date, $end_date]);
        $_1102050101_203 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_203
            WHERE vstdate BETWEEN ? AND ? ', [$start_date, $end_date]);
        $_1102050101_209 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_209             
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_216 = DB::select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,
                SUM(IFNULL(s.receive_total,0)+CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END) AS receive
            FROM debtor_1102050101_216 d   
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, 
                SUM(receive_total) - SUM(receive_pp) AS receive_total
                FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid
                AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT cid,datetimeadm AS vstdate,SUM(receive_total) AS receive_total
                FROM stm_ucs_kidney GROUP BY cid, datetimeadm) sk ON sk.cid = d.cid AND sk.vstdate = d.vstdate 
            WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_301 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_301 
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_303 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_303 
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_307 = DB::select('
            SELECT COUNT(DISTINCT IF(an IS NOT NULL AND an <> "", an, vn)) AS anvn,
                SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_307
            WHERE COALESCE(dchdate, vstdate) BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_309 = DB::select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,
                SUM(IFNULL(d.receive,0)) + SUM(IFNULL(s.receive,0)) AS receive
            FROM debtor_1102050101_309 d 
            LEFT JOIN (SELECT cid,vstdate,SUM(IFNULL(amount,0)+ IFNULL(epopay,0) + IFNULL(epoadm,0)) AS receive
                FROM stm_sss_kidney GROUP BY cid, vstdate) s ON s.cid = d.cid AND s.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_401 = DB::select("
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,
                SUM(IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+IFNULL(csop.amount,0)
                + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) AS receive
            FROM debtor_1102050101_401 d 
            LEFT JOIN (SELECT hn,vstdate,LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total
                FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(amount) AS amount
                FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = d.hn
                AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn,vstdate,SUM(amount) AS amount FROM stm_ofc_csop
                WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn AND hd.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date]);
        $_1102050101_501 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_501 
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_503 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_503 
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_701 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_701
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_702 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_702
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_703 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_703 
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050102_106 = DB::connection('hosxp')->select("
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,
                SUM(IFNULL(d.receive,0) + IFNULL(r.total_amount,0)) AS receive
            FROM hrims.debtor_1102050102_106 d
            LEFT JOIN (SELECT r.vn, r.bill_date, SUM(r.total_amount) AS total_amount,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn, r.bill_date) r ON r.vn = d.vn AND r.bill_date > d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date]);
        $_1102050102_108 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050102_108 
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050102_110 = DB::select("
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,
                SUM(IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+IFNULL(csop.amount,0)
                + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) AS receive
            FROM debtor_1102050102_110 d   
            LEFT JOIN (SELECT hn,vstdate,LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total
                FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(amount) AS amount
                FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = d.hn
                AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn,vstdate,SUM(amount) AS amount FROM stm_ofc_csop
                WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn AND hd.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date]);
        $_1102050102_602 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050102_602 
            WHERE vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050102_801 = DB::select('
            SELECT COUNT(DISTINCT a.vn) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive) AS receive
                FROM (SELECT d.vn,d.debtor,IFNULL(s.compensate_treatment,0)+ CASE WHEN d.kidney > 0
                THEN IFNULL(k.compensate_kidney,0) ELSE 0 END AS receive
            FROM debtor_1102050102_801 d   
            LEFT JOIN (SELECT hn,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment
                FROM stm_lgo GROUP BY hn, vstdate, LEFT(vsttime,5)) s ON s.hn = d.hn
                AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5) 
            LEFT JOIN (SELECT hn, datetimeadm AS vstdate,SUM(compensate_kidney) AS compensate_kidney
                FROM stm_lgo_kidney GROUP BY hn, datetimeadm) k ON k.hn = d.hn  AND k.vstdate = d.vstdate  
            WHERE d.vstdate BETWEEN ? AND ?) a', [$start_date, $end_date]);
        $_1102050102_803 = DB::select("
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,
                SUM(IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+IFNULL(csop.amount,0)
                + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) AS receive
            FROM debtor_1102050102_803 d   
            LEFT JOIN (SELECT hn,vstdate,LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total
                FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(amount) AS amount
                FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = d.hn
                AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn,vstdate,SUM(amount) AS amount FROM stm_ofc_csop
                WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn AND hd.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date]);
        $_1102050101_202 = DB::select('
            SELECT COUNT(an) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive_ip_compensate_pay),0) AS receive
                FROM (SELECT d.an,d.debtor,stm.receive_ip_compensate_pay FROM debtor_1102050101_202 d
            LEFT JOIN (SELECT an, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay
                FROM stm_ucs  GROUP BY an) stm ON stm.an = d.an    
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a', [$start_date, $end_date]);
        $_1102050101_217 = DB::select('
            SELECT COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive) AS receive
            FROM (SELECT d.an,d.debtor, (IFNULL(s.receive_total,0)-IFNULL(s.receive_ip_compensate_pay,0))
                    + IFNULL(k.receive_total,0) AS receive
                FROM debtor_1102050101_217 d
                LEFT JOIN (SELECT an,SUM(receive_total) AS receive_total,SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay
                    FROM stm_ucs GROUP BY an) s ON s.an = d.an
                LEFT JOIN (SELECT d2.an, SUM(sk.receive_total) AS receive_total FROM debtor_1102050101_217 d2
                    JOIN stm_ucs_kidney sk ON sk.cid = d2.cid AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                    WHERE d2.dchdate BETWEEN ? AND ? GROUP BY d2.an) k ON k.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an, d.debtor) AS a', [$start_date, $end_date, $start_date, $end_date]);
        $_1102050101_302 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_302    
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_304 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_304    
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_308 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_308    
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_310 = DB::select('
            SELECT COUNT(DISTINCT d.an) AS anvn, SUM(d.debtor) AS debtor,
                SUM(IFNULL(d.receive,0) + IFNULL(stm.stm_receive,0)) AS receive
            FROM debtor_1102050101_310 d
            LEFT JOIN (
                SELECT d2.an, SUM(IFNULL(s.amount,0) + IFNULL(s.epopay,0) + IFNULL(s.epoadm,0)) AS stm_receive
                FROM debtor_1102050101_310 d2
                JOIN stm_sss_kidney s ON s.hn = d2.hn AND s.vstdate BETWEEN d2.regdate AND d2.dchdate
                GROUP BY d2.an
            ) stm ON stm.an = d.an
            WHERE d.dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_402 = DB::select('
            SELECT COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (SELECT d.an,MAX(d.debtor) AS debtor,IFNULL(stm.receive_total,0)+IFNULL(cipn.gtotal,0)
                    + CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(kd.amount,0) ELSE 0 END AS receive_total
                FROM debtor_1102050101_402 d 
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total
                    FROM stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal
                    FROM stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount FROM debtor_1102050101_402 d2
                    JOIN stm_ofc_csop c  ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) kd ON kd.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) a ', [$start_date, $end_date]);
        $_1102050101_502 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_502    
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_504 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_504    
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050101_704 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_704    
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050102_107 = DB::connection('hosxp')->select("
            SELECT COUNT(DISTINCT d.an) AS anvn,SUM(d.debtor) AS debtor, 
                SUM(IFNULL(d.receive,0) + IFNULL(r.total_amount,0)) AS receive
            FROM hrims.debtor_1102050102_107 d
            LEFT JOIN (SELECT r.vn, r.bill_date, SUM(r.total_amount) AS total_amount
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn, r.bill_date) r ON r.vn = d.an AND r.bill_date > d.dchdate
            WHERE d.dchdate BETWEEN ? AND ?", [$start_date, $end_date]);
        $_1102050102_109 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_109   
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050102_111 = DB::select('
            SELECT COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (SELECT d.an,MAX(d.debtor) AS debtor,IFNULL(stm.receive_total,0)+IFNULL(cipn.gtotal,0)
                    + CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(kd.amount,0) ELSE 0 END AS receive_total
            FROM debtor_1102050102_111 d    
            LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total
                    FROM stm_ofc GROUP BY an) stm ON stm.an = d.an
            LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal
                FROM stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
            LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount FROM debtor_1102050102_111 d2
                JOIN stm_ofc_csop c  ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                GROUP BY d2.an) kd ON kd.an = d.an
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) a ', [$start_date, $end_date]);
        $_1102050102_603 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_603  
            WHERE dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        $_1102050102_802 = DB::select('
            SELECT COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (SELECT d.an,MAX(d.debtor) AS debtor,IFNULL(stm.compensate_treatment,0)
                + CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(stm_k.amount,0) ELSE 0 END AS receive_total
                FROM debtor_1102050102_802 d 
                LEFT JOIN (SELECT an,SUM(compensate_treatment) AS compensate_treatment
                    FROM stm_lgo GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(k.compensate_kidney) AS amount FROM debtor_1102050102_802 d2
                    JOIN stm_lgo_kidney k ON k.cid = d2.cid AND k.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                    WHERE d2.dchdate BETWEEN ? AND ? GROUP BY d2.an) stm_k ON stm_k.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) a', [$start_date, $end_date, $start_date, $end_date]);
        $_1102050102_804 = DB::select('
            SELECT COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (SELECT d.an,MAX(d.debtor) AS debtor,IFNULL(stm.receive_total,0)+IFNULL(cipn.gtotal,0)
                    + CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(kd.amount,0) ELSE 0 END AS receive_total
                FROM debtor_1102050102_804 d    
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total
                    FROM stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal
                    FROM stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount FROM debtor_1102050102_804 d2
                    JOIN stm_ofc_csop c  ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) kd ON kd.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) a ', [$start_date, $end_date]);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('_1102050101_103', $_1102050101_103);
        $request->session()->put('_1102050101_109', $_1102050101_109);
        $request->session()->put('_1102050101_201', $_1102050101_201);
        $request->session()->put('_1102050101_203', $_1102050101_203);
        $request->session()->put('_1102050101_209', $_1102050101_209);
        $request->session()->put('_1102050101_216', $_1102050101_216);
        $request->session()->put('_1102050101_301', $_1102050101_301);
        $request->session()->put('_1102050101_303', $_1102050101_303);
        $request->session()->put('_1102050101_307', $_1102050101_307);
        $request->session()->put('_1102050101_309', $_1102050101_309);
        $request->session()->put('_1102050101_401', $_1102050101_401);
        $request->session()->put('_1102050101_501', $_1102050101_501);
        $request->session()->put('_1102050101_503', $_1102050101_503);
        $request->session()->put('_1102050101_701', $_1102050101_701);
        $request->session()->put('_1102050101_702', $_1102050101_702);
        $request->session()->put('_1102050101_703', $_1102050101_703);
        $request->session()->put('_1102050102_106', $_1102050102_106);
        $request->session()->put('_1102050102_108', $_1102050102_108);
        $request->session()->put('_1102050102_110', $_1102050102_110);
        $request->session()->put('_1102050102_602', $_1102050102_602);
        $request->session()->put('_1102050102_801', $_1102050102_801);
        $request->session()->put('_1102050102_803', $_1102050102_803);
        $request->session()->put('_1102050101_202', $_1102050101_202);
        $request->session()->put('_1102050101_217', $_1102050101_217);
        $request->session()->put('_1102050101_302', $_1102050101_302);
        $request->session()->put('_1102050101_304', $_1102050101_304);
        $request->session()->put('_1102050101_308', $_1102050101_308);
        $request->session()->put('_1102050101_310', $_1102050101_310);
        $request->session()->put('_1102050101_402', $_1102050101_402);
        $request->session()->put('_1102050101_502', $_1102050101_502);
        $request->session()->put('_1102050101_504', $_1102050101_504);
        $request->session()->put('_1102050101_704', $_1102050101_704);
        $request->session()->put('_1102050102_107', $_1102050102_107);
        $request->session()->put('_1102050102_109', $_1102050102_109);
        $request->session()->put('_1102050102_111', $_1102050102_111);
        $request->session()->put('_1102050102_603', $_1102050102_603);
        $request->session()->put('_1102050102_802', $_1102050102_802);
        $request->session()->put('_1102050102_804', $_1102050102_804);
        $request->session()->save();

        return view('debtor._summary', compact(
            'start_date',
            'end_date',
            '_1102050101_103',
            '_1102050101_109',
            '_1102050101_201',
            '_1102050101_203',
            '_1102050101_209',
            '_1102050101_216',
            '_1102050101_301',
            '_1102050101_303',
            '_1102050101_307',
            '_1102050101_309',
            '_1102050101_401',
            '_1102050101_501',
            '_1102050101_503',
            '_1102050101_701',
            '_1102050101_702',
            '_1102050101_703',
            '_1102050102_106',
            '_1102050102_108',
            '_1102050102_110',
            '_1102050102_602',
            '_1102050102_801',
            '_1102050102_803',
            '_1102050101_202',
            '_1102050101_217',
            '_1102050101_302',
            '_1102050101_304',
            '_1102050101_308',
            '_1102050101_310',
            '_1102050101_402',
            '_1102050101_502',
            '_1102050101_504',
            '_1102050101_704',
            '_1102050102_107',
            '_1102050102_109',
            '_1102050102_111',
            '_1102050102_603',
            '_1102050102_802',
            '_1102050102_804'
        ));
    }
    //_summary_pdf--------------------------------------------------------------------------------------------------------------------------------------------------
    public function _summary_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $_1102050101_103 = Session::get('_1102050101_103');
        $_1102050101_109 = Session::get('_1102050101_109');
        $_1102050101_201 = Session::get('_1102050101_201');
        $_1102050101_203 = Session::get('_1102050101_203');
        $_1102050101_209 = Session::get('_1102050101_209');
        $_1102050101_216 = Session::get('_1102050101_216');
        $_1102050101_301 = Session::get('_1102050101_301');
        $_1102050101_303 = Session::get('_1102050101_303');
        $_1102050101_307 = Session::get('_1102050101_307');
        $_1102050101_309 = Session::get('_1102050101_309');
        $_1102050101_401 = Session::get('_1102050101_401');
        $_1102050101_501 = Session::get('_1102050101_501');
        $_1102050101_503 = Session::get('_1102050101_503');
        $_1102050101_701 = Session::get('_1102050101_701');
        $_1102050101_702 = Session::get('_1102050101_702');
        $_1102050101_703 = Session::get('_1102050101_703');
        $_1102050102_106 = Session::get('_1102050102_106');
        $_1102050102_108 = Session::get('_1102050102_108');
        $_1102050102_110 = Session::get('_1102050102_110');
        $_1102050102_602 = Session::get('_1102050102_602');
        $_1102050102_801 = Session::get('_1102050102_801');
        $_1102050102_803 = Session::get('_1102050102_803');
        $_1102050101_202 = Session::get('_1102050101_202');
        $_1102050101_217 = Session::get('_1102050101_217');
        $_1102050101_302 = Session::get('_1102050101_302');
        $_1102050101_304 = Session::get('_1102050101_304');
        $_1102050101_308 = Session::get('_1102050101_308');
        $_1102050101_310 = Session::get('_1102050101_310');
        $_1102050101_402 = Session::get('_1102050101_402');
        $_1102050101_502 = Session::get('_1102050101_502');
        $_1102050101_504 = Session::get('_1102050101_504');
        $_1102050101_704 = Session::get('_1102050101_704');
        $_1102050102_107 = Session::get('_1102050102_107');
        $_1102050102_109 = Session::get('_1102050102_109');
        $_1102050102_111 = Session::get('_1102050102_111');
        $_1102050102_603 = Session::get('_1102050102_603');
        $_1102050102_802 = Session::get('_1102050102_802');
        $_1102050102_804 = Session::get('_1102050102_804');
        $pdf = PDF::loadView('debtor._summary_pdf', compact(
            'hospital_name',
            'hospital_code',
            'start_date',
            'end_date',
            '_1102050101_103',
            '_1102050101_109',
            '_1102050101_201',
            '_1102050101_203',
            '_1102050101_209',
            '_1102050101_216',
            '_1102050101_301',
            '_1102050101_303',
            '_1102050101_307',
            '_1102050101_309',
            '_1102050101_401',
            '_1102050101_501',
            '_1102050101_503',
            '_1102050101_701',
            '_1102050101_702',
            '_1102050101_703',
            '_1102050102_106',
            '_1102050102_108',
            '_1102050102_110',
            '_1102050102_602',
            '_1102050102_801',
            '_1102050102_803',
            '_1102050101_202',
            '_1102050101_217',
            '_1102050101_302',
            '_1102050101_304',
            '_1102050101_308',
            '_1102050101_310',
            '_1102050101_402',
            '_1102050101_502',
            '_1102050101_504',
            '_1102050101_704',
            '_1102050102_107',
            '_1102050102_109',
            '_1102050102_111',
            '_1102050102_603',
            '_1102050102_802',
            '_1102050102_804'
        ))
            ->setPaper('A4', 'landscape');
        return @$pdf->stream();
    }
    ##############################################################################################################################################################
    //_1102050101_103--------------------------------------------------------------------------------------------------------------
    public function _1102050101_103(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $debtor = Debtor_1102050101_103::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')
            ->get()
            ->map(function ($item) {
                $item->balance = $item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0) - $item->debtor;
                if ($item->balance >= -0.01) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // REMOVED session('debtor') TO PREVENT BLOAT
        $request->session()->save();

        return view('debtor.1102050101_103', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }



    //_1102050101_103_search_ajax-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_search_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $data = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,o.vstdate,
                o.vsttime,v.pdx,p.`name` AS pttype,vp.hospmain,p.hipdata_code,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS debtor,
                "ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype    
            LEFT JOIN (SELECT op.vn,op.pttype,SUM(op.sum_price) AS income
                FROM opitemrece op WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "") 
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_103 WHERE vn IS NOT NULL)
            AND vp.pttype IN (' . $pttype_checkup . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }
    //_1102050101_103_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,o.vstdate,
                o.vsttime,v.pdx,p.`name` AS pttype,vp.hospmain,p.hipdata_code,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0) AS debtor,
                "ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype    
            LEFT JOIN (SELECT op.vn,op.pttype,SUM(op.sum_price) AS income
                FROM opitemrece op WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money, 
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "") 
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0) - IFNULL(rc.rcpt_money,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_103 WHERE vn IS NOT NULL)
            AND vp.pttype IN (' . $pttype_checkup . ')
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate,o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_103::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_103_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_103::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_103::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_103_lock(Request $request, $vn)
    {
        Debtor_1102050101_103::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_103_unlock(Request $request, $vn)
    {
        Debtor_1102050101_103::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_103_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_103::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_103_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_103  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_103_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_103_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_103_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050101_103::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')
            ->get()
            ->map(function ($item) {
                $item->balance = $item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0) - $item->debtor;
                if ($item->balance >= -0.01) {
                    $item->days = 0;
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        return view('debtor.1102050101_103_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    //1102050101_103_bulk_adj--------------------------------------------------------------------------------------------------------------
    public function _1102050101_103_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_103::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $request->bulk_adj_date ?: date('Y-m-d');
                $row->adj_note = $request->bulk_adj_note ?: 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    ##############################################################################################################################################################
    //_1102050101_109--------------------------------------------------------------------------------------------------------------
    public function _1102050101_109(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        $debtor = Debtor_1102050101_109::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS receive_no_manual'))
            ->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->balance = ($item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0)) - $item->debtor;
                if ($item->balance >= -0.01) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // REMOVED session('debtor') TO PREVENT BLOAT
        $request->session()->save();

        return view('debtor.1102050101_109', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }



    //_1102050101_109_search_ajax-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_search_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $data = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,p.cid,CONCAT(p.pname, p.fname, " ", p.lname) AS ptname,o.vstdate,v.pdx,
                o.vsttime,p1.`name` AS pttype,vp.hospmain,p1.hipdata_code,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(ems.claim_price,0) AS debtor,ems.claim_list AS claim_list,
                "ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient p ON p.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p1 ON p1.pttype = vp.pttype
            LEFT JOIN (SELECT op.vn,op.pttype, SUM(op.sum_price) AS income
                FROM opitemrece op 
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price,
                GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list    
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems = "Y"
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) ems ON ems.vn = o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_109 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }
    //_1102050101_109_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,p.cid,CONCAT(p.pname, p.fname, " ", p.lname) AS ptname,o.vstdate,v.pdx,
                o.vsttime,p1.`name` AS pttype,vp.hospmain,p1.hipdata_code,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(ems.claim_price,0) AS debtor,ems.claim_list AS claim_list,
                "ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient p ON p.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p1 ON p1.pttype = vp.pttype
            LEFT JOIN (SELECT op.vn,op.pttype, SUM(op.sum_price) AS income
                FROM opitemrece op 
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price,
                GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list    
                FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems = "Y"
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) ems ON ems.vn = o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_109 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ') 
            GROUP BY o.vn, vp.pttype    
            ORDER BY o.vstdate,o.oqueue', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_109::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_109_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        $count_request = count($checkbox);
        $deleted = Debtor_1102050101_109::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if ($deleted == 0) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการถูก Lock ไว้');
        } elseif ($deleted < $count_request) {
            return redirect()->back()->with('warning', 'ลบลูกหนี้เรียบร้อย ' . $deleted . ' รายการ (บางรายการถูก Lock ไม่สามารถลบได้)');
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ' . $deleted . ' รายการ');
    }
    //_1102050101_109_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_109::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //_1102050101_109_unlock-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_unlock(Request $request, $vn)
    {
        $item = Debtor_1102050101_109::findOrFail($vn);
        $item->update([
            'debtor_lock' => NULL
        ]);
        return redirect()->back()->with('success', 'Unlock เรียบร้อย');
    }
    //_1102050101_109_lock-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_lock(Request $request, $vn)
    {
        $item = Debtor_1102050101_109::findOrFail($vn);
        $item->update([
            'debtor_lock' => 'Y'
        ]);
        return redirect()->back()->with('success', 'Lock เรียบร้อย');
    }
    //1102050101_109_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_109  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_109_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_109_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_109_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050101_109::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS receive_no_manual'))
            ->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->balance = ($item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0)) - $item->debtor;
                if ($item->balance >= -0.01) {
                    $item->days = 0;
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        return view('debtor.1102050101_109_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_201--------------------------------------------------------------------------------------------------------------
    public function _1102050101_201(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,s.receive_pp,d.repno,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change,
                    d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days,
                    (d.income - d.rcpt_money - d.other - d.ppfs - d.debtor) AS balance
                FROM debtor_1102050101_201 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change,
                    d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days,
                    (d.income - d.rcpt_money - d.other - d.ppfs - d.debtor) AS balance
                FROM debtor_1102050101_201 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // REMOVED session('debtor') TO PREVENT BLOAT
        $request->session()->save();
        
        return view('debtor.1102050101_201', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }

    public function _1102050101_201_search_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
                o.vstdate,o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.other_price,0) AS other, IFNULL(inc.ppfs_price,0) AS ppfs,
                IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(inc.other_price,0)- IFNULL(inc.ppfs_price,0) AS debtor,
                inc.other_list, inc.ppfs_list, "ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn        
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT op.vn, 
                       SUM(CASE WHEN op.pttype = os.pttype THEN op.sum_price ELSE 0 END) AS income,
                       SUM(CASE WHEN (li.ppfs IS NULL OR li.ppfs = "") AND li.icode IS NOT NULL THEN op.sum_price ELSE 0 END) AS other_price,
                       SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                       GROUP_CONCAT(DISTINCT CASE WHEN (li.ppfs IS NULL OR li.ppfs = "") AND li.icode IS NOT NULL THEN sd.`name` END) AS other_list,
                       GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                INNER JOIN ovst os ON os.vn = op.vn
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode  
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn
            ) inc ON inc.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(inc.other_price,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL")
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_201 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype 
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_201_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_201_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
                o.vstdate,o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,
                IFNULL(inc.income,0) AS income, IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(inc.other_price,0) AS other, IFNULL(inc.ppfs_price,0) AS ppfs,
                IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(inc.other_price,0)- IFNULL(inc.ppfs_price,0) AS debtor,
                inc.other_list, inc.ppfs_list, "ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn        
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT op.vn, 
                       SUM(CASE WHEN op.pttype = os.pttype THEN op.sum_price ELSE 0 END) AS income,
                       SUM(CASE WHEN (li.ppfs IS NULL OR li.ppfs = "") AND li.icode IS NOT NULL THEN op.sum_price ELSE 0 END) AS other_price,
                       SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                       GROUP_CONCAT(DISTINCT CASE WHEN (li.ppfs IS NULL OR li.ppfs = "") AND li.icode IS NOT NULL THEN sd.`name` END) AS other_list,
                       GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                INNER JOIN ovst os ON os.vn = op.vn
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode  
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn
            ) inc ON inc.vn = o.vn
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(inc.other_price,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL")
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_201 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype 
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_201::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_201_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_201_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_201::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_201::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_201_lock(Request $request, $vn)
    {
        Debtor_1102050101_201::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_201_unlock(Request $request, $vn)
    {
        Debtor_1102050101_201::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_201_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_201_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_201  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_201_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_201_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_201_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change,
                    d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_201 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change,
                    d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_201 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        return view('debtor.1102050101_201_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    //_1102050101_201_average_receive-------------------------------------------------------------------------------------------------------   
    public function _1102050101_201_average_receive(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date',
            'repno' => 'required|string',
            'total_receive' => 'required|numeric|min:0.01',
            'receive_date' => 'required|date',
        ]);

        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;
        $repno = $request->repno;
        $total = (float) $request->total_receive;
        $receive_date = $request->receive_date;

        // ดึงข้อมูล
        $rows = DB::table('debtor_1102050101_201')
            ->whereBetween('vstdate', [$dateStart, $dateEnd])
            ->get();

        $count = $rows->count();
        if ($count === 0) {
            return response()->json([
                'status' => 'error',
                'message' => "ไม่พบข้อมูล"
            ]);
        }

        // ===== 1) คำนวณน้ำหนักตาม debtor =====
        $sumDebtor = $rows->sum('debtor');

        $items = [];
        foreach ($rows as $row) {

            // น้ำหนักตามสัดส่วน debtor
            $weight = $row->debtor / $sumDebtor;

            // ยอดที่ควรได้รับตามสัดส่วน
            $assign = round($total * $weight, 2);

            $items[] = [
                'vn' => $row->vn,
                'assign' => $assign,
            ];
        }

        // ===== 2) ปรับ diff ให้ผลรวมตรง total_receive =====
        $sumAssigned = array_sum(array_column($items, 'assign'));
        $diff = round($total - $sumAssigned, 2);

        $i = 0;
        while (abs($diff) >= 0.01) {

            // เพิ่มทีละ 1 สตางค์ให้ record ตามลำดับ
            if ($diff > 0) {
                $items[$i]['assign'] = round($items[$i]['assign'] + 0.01, 2);
                $diff = round($diff - 0.01, 2);
            }
            // หรือลดทีละ 1 สตางค์
            else {
                if ($items[$i]['assign'] > 0.01) {
                    $items[$i]['assign'] = round($items[$i]['assign'] - 0.01, 2);
                    $diff = round($diff + 0.01, 2);
                }
            }

            $i = ($i + 1) % $count;
        }

        // ===== 3) บันทึกจริงลงฐานข้อมูล =====
        foreach ($items as $it) {
            DB::table('debtor_1102050101_201')
                ->where('vn', $it['vn'])
                ->update([
                    'receive' => $it['assign'],
                    'repno' => $repno,
                    'receive_date' => $receive_date,
                    'status' => 'กระทบยอดแล้ว',
                ]);
        }

        $finalSum = array_sum(array_column($items, 'assign'));

        return response()->json([
            'status' => 'success',
            'message' => "
                วันที่ : <b>{$dateStart}</b> ถึง <b>{$dateEnd}</b><br>
                จำนวน Visit : <b>{$count}</b><br>
                ยอดชดเชย : <b>" . number_format($total, 2) . "</b><br>
                ยอดที่จัดสรรได้จริง : <b>" . number_format($finalSum, 2) . "</b> ✔ ตรง 100%
            "
        ]);
    }
    ##############################################################################################################################################################
    //_1102050101_203--------------------------------------------------------------------------------------------------------------
    public function _1102050101_203(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge,d.charge_date,d.charge_no,d.receive,d.receive_date,
					d.receive_no,d.repno,s.receive_pp,s.repno AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_203 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge,d.charge_date,d.charge_no,d.receive,d.receive_date,
					d.receive_no,d.repno,s.receive_pp,s.repno AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_203 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // REMOVED session('debtor') TO PREVENT BLOAT
        $request->session()->save();

        return view('debtor.1102050101_203', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_203_search_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
                o.vstdate,o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(ch.other_price,0) AS other,IFNULL(ch.ppfs_price,0) AS ppfs,
                IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)- IFNULL(ch.ppfs_price,0) AS debtor,
                ch.other_list,ch.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn        
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT op.vn, op.pttype,SUM(op.sum_price) AS income
                FROM opitemrece op
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN (SELECT op.vn,SUM(CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN op.sum_price ELSE 0 END) AS other_price,
                SUM( CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN sd.`name` END) AS other_list,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode  
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) ch ON ch.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL")
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs ="")) 
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_203 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype 
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_203_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
                o.vstdate,o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(ch.other_price,0) AS other,IFNULL(ch.ppfs_price,0) AS ppfs,
                IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)- IFNULL(ch.ppfs_price,0) AS debtor,
                ch.other_list,ch.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn        
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT op.vn, op.pttype,SUM(op.sum_price) AS income
                FROM opitemrece op
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN (SELECT op.vn,SUM(CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN op.sum_price ELSE 0 END) AS other_price,
                SUM( CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN sd.`name` END) AS other_list,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode  
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) ch ON ch.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL")
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs ="")) 
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_203 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype 
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_203::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_203_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_203::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_203::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_203_lock(Request $request, $vn)
    {
        Debtor_1102050101_203::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_203_unlock(Request $request, $vn)
    {
        Debtor_1102050101_203::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_203_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_203::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_203_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_203  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_203_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_203_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_203_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge,d.charge_date,d.charge_no,d.receive,d.receive_date,
					d.receive_no,d.repno,s.receive_pp,s.repno AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_203 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge,d.charge_date,d.charge_no,d.receive,d.receive_date,
					d.receive_no,d.repno,s.receive_pp,s.repno AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_203 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        return view('debtor.1102050101_203_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    //_1102050101_203_average_receive-------------------------------------------------------------------------------------------------------   
    public function _1102050101_203_average_receive(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date',
            'repno' => 'required|string',
            'total_receive' => 'required|numeric|min:0.01',
        ]);

        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;
        $repno = $request->repno;
        $total = (float) $request->total_receive;

        // ดึงข้อมูล
        $rows = DB::table('debtor_1102050101_203')
            ->whereBetween('vstdate', [$dateStart, $dateEnd])
            ->get();

        $count = $rows->count();
        if ($count === 0) {
            return response()->json([
                'status' => 'error',
                'message' => "ไม่พบข้อมูล"
            ]);
        }

        // ===== 1) คำนวณน้ำหนักตาม debtor =====
        $sumDebtor = $rows->sum('debtor');

        $items = [];
        foreach ($rows as $row) {

            // น้ำหนักตามสัดส่วน debtor
            $weight = $row->debtor / $sumDebtor;

            // ยอดที่ควรได้รับตามสัดส่วน
            $assign = round($total * $weight, 2);

            $items[] = [
                'vn' => $row->vn,
                'assign' => $assign,
            ];
        }

        // ===== 2) ปรับ diff ให้ผลรวมตรง total_receive =====
        $sumAssigned = array_sum(array_column($items, 'assign'));
        $diff = round($total - $sumAssigned, 2);

        $i = 0;
        while (abs($diff) >= 0.01) {

            // เพิ่มทีละ 1 สตางค์ให้ record ตามลำดับ
            if ($diff > 0) {
                $items[$i]['assign'] = round($items[$i]['assign'] + 0.01, 2);
                $diff = round($diff - 0.01, 2);
            }
            // หรือลดทีละ 1 สตางค์
            else {
                if ($items[$i]['assign'] > 0.01) {
                    $items[$i]['assign'] = round($items[$i]['assign'] - 0.01, 2);
                    $diff = round($diff + 0.01, 2);
                }
            }

            $i = ($i + 1) % $count;
        }

        // ===== 3) บันทึกจริงลงฐานข้อมูล =====
        foreach ($items as $it) {
            DB::table('debtor_1102050101_203')
                ->where('vn', $it['vn'])
                ->update([
                    'receive' => $it['assign'],
                    'repno' => $repno,
                    'status' => 'กระทบยอดแล้ว',
                ]);
        }

        $finalSum = array_sum(array_column($items, 'assign'));

        return response()->json([
            'status' => 'success',
            'message' => "
                วันที่ : <b>{$dateStart}</b> ถึง <b>{$dateEnd}</b><br>
                จำนวน Visit : <b>{$count}</b><br>
                ยอดชดเชย : <b>" . number_format($total, 2) . "</b><br>
                ยอดที่จัดสรรได้จริง : <b>" . number_format($finalSum, 2) . "</b> ✔ ตรง 100%
            "
        ]);
    }
    ##############################################################################################################################################################
    //_1102050101_209--------------------------------------------------------------------------------------------------------------
    public function _1102050101_209(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: "''";

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.cid, d.ptname, d.hipdata_code, d.pttype, d.hospmain, d.pdx, d.income,  
                    d.rcpt_money, d.ppfs, d.pp, d.other, d.debtor,d.receive AS receive_manual, d.repno AS repno_manual, s.receive_pp, d.receive, s.repno, s.repno AS repno_pp, d.status, d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno,
                    CASE WHEN (IFNULL(d.receive,0)  + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_209 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.cid, d.ptname, d.hipdata_code, d.pttype, d.hospmain, d.pdx, d.income,
                     d.rcpt_money, d.ppfs, d.pp, d.other, d.debtor,d.receive AS receive_manual, d.repno AS repno_manual, s.receive_pp, d.receive, s.repno, s.repno AS repno_pp, d.status, d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno,
                    CASE WHEN (IFNULL(d.receive,0)  + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_209 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // REMOVED session('debtor') TO PREVENT BLOAT
        $request->session()->save();

        return view('debtor.1102050101_209', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_209_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: "''";

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
                o.vstdate,o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(ch.other_price,0) AS other,IFNULL(ch.ppfs_price,0) AS ppfs,
                IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)- IFNULL(ch.ppfs_price,0) AS debtor,
                ch.other_list,ch.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn        
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT op.vn, op.pttype,SUM(op.sum_price) AS income
                FROM opitemrece op
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN (SELECT op.vn,SUM(CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN op.sum_price ELSE 0 END) AS other_price,
                SUM( CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN sd.`name` END) AS other_list,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode  
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) ch ON ch.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL","SSS")            
            AND v.pdx IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND p.pttype NOT IN (' . $pttype_sss_fund . ')
            AND p.pttype NOT IN (' . $pttype_sss_ae . ')
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_209 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype 
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_209_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_209_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: "''";
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,
                o.vstdate,o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(inc.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(ch.other_price,0) AS other,IFNULL(ch.ppfs_price,0) AS ppfs,
                IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(ch.other_price,0)- IFNULL(ch.ppfs_price,0) AS debtor,
                ch.other_list,ch.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn        
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT op.vn, op.pttype,SUM(op.sum_price) AS income
                FROM opitemrece op
                WHERE op.vstdate BETWEEN ? AND ? 
                GROUP BY op.vn, op.pttype) inc ON inc.vn = o.vn AND inc.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN (SELECT op.vn,SUM(CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN op.sum_price ELSE 0 END) AS other_price,
                SUM( CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs IS NULL OR li.ppfs = "" THEN sd.`name` END) AS other_list,
                GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode  
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) ch ON ch.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(inc.income,0)-IFNULL(rc.rcpt_money,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL","SSS")            
            AND v.pdx IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND p.pttype NOT IN (' . $pttype_sss_fund . ')
            AND p.pttype NOT IN (' . $pttype_sss_ae . ')
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_209 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype 
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_209::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_209_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_209_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_209::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_209::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_209_lock(Request $request, $vn)
    {
        Debtor_1102050101_209::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_209_unlock(Request $request, $vn)
    {
        Debtor_1102050101_209::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_209_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_209_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_209 d               
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY d.vstdate ', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_209_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    public function _1102050101_209_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.cid, d.ptname, d.hipdata_code, d.pttype, d.hospmain, d.pdx, d.income,  
                    d.rcpt_money, d.ppfs, d.pp, d.other, d.debtor,d.receive AS receive_manual, d.repno AS repno_manual, s.receive_pp, d.receive, s.repno, s.repno AS repno_pp, d.status, d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno,
                    CASE WHEN (IFNULL(d.receive,0)  + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_209 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.cid, d.ptname, d.hipdata_code, d.pttype, d.hospmain, d.pdx, d.income,
                     d.rcpt_money, d.ppfs, d.pp, d.other, d.debtor,d.receive AS receive_manual, d.repno AS repno_manual, s.receive_pp, d.receive, s.repno, s.repno AS repno_pp, d.status, d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno,
                    CASE WHEN (IFNULL(d.receive,0)  + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_209 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        return view('debtor.1102050101_209_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_216--------------------------------------------------------------------------------------------------------------
    public function _1102050101_216(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,MAX(d.an) AS an,MAX(d.cid) AS cid,MAX(d.ptname) AS ptname,MAX(d.hipdata_code) AS hipdata_code,MAX(d.pttype) AS pttype,
                    MAX(d.hospmain) AS hospmain,MAX(d.pdx) AS pdx,MAX(d.income) AS income,MAX(d.rcpt_money) AS rcpt_money,
                    MAX(d.kidney) AS kidney,MAX(d.cr) AS cr, MAX(d.anywhere) AS anywhere,MAX(d.ppfs) AS ppfs,MAX(d.debtor) AS debtor,
                    IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END AS receive,
                    MAX(s.repno) AS repno,MAX(sk.repno) AS rid,MAX(d.debtor_lock) AS debtor_lock,
                    IFNULL(MAX(s.round_no), MAX(sk.round_no)) AS stm_round_no,
                    IFNULL(MAX(s.receipt_date), MAX(sk.receipt_date)) AS stm_receipt_date,
                    IFNULL(MAX(s.receive_no), MAX(sk.receive_no)) AS stm_receive_no,
                    MAX(d.status) AS status, MAX(d.adj_inc) AS adj_inc, MAX(d.adj_dec) AS adj_dec, MAX(d.adj_note) AS adj_note, MAX(d.adj_date) AS adj_date,
                    MAX(d.charge_date) AS charge_date, MAX(d.charge_no) AS charge_no, MAX(d.charge) AS charge,
                    MAX(d.receive_date) AS receive_date, MAX(d.receive_no) AS receive_no,
                    CASE WHEN (IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END
                    + IFNULL(MAX(d.adj_inc),0) - IFNULL(MAX(d.adj_dec),0) - MAX(d.debtor)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), MAX(d.vstdate)) END AS days
                FROM debtor_1102050101_216 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,
                    SUM(receive_total) - SUM(receive_pp) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,SUM(receive_total) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs_kidney GROUP BY cid, datetimeadm) sk ON sk.cid = d.cid AND sk.vstdate = d.vstdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?
                GROUP BY d.vn', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,MAX(d.an) AS an,MAX(d.cid) AS cid,MAX(d.ptname) AS ptname,MAX(d.hipdata_code) AS hipdata_code,MAX(d.pttype) AS pttype,
                    MAX(d.hospmain) AS hospmain,MAX(d.pdx) AS pdx,MAX(d.income) AS income,MAX(d.rcpt_money) AS rcpt_money,
                    MAX(d.kidney) AS kidney,MAX(d.cr) AS cr, MAX(d.anywhere) AS anywhere,MAX(d.ppfs) AS ppfs,MAX(d.debtor) AS debtor,
                    IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END AS receive,
                    MAX(s.repno) AS repno,MAX(sk.repno) AS rid,MAX(d.debtor_lock) AS debtor_lock,
                    IFNULL(MAX(s.round_no), MAX(sk.round_no)) AS stm_round_no,
                    IFNULL(MAX(s.receipt_date), MAX(sk.receipt_date)) AS stm_receipt_date,
                    IFNULL(MAX(s.receive_no), MAX(sk.receive_no)) AS stm_receive_no,
                    MAX(d.status) AS status, MAX(d.adj_inc) AS adj_inc, MAX(d.adj_dec) AS adj_dec, MAX(d.adj_note) AS adj_note, MAX(d.adj_date) AS adj_date,
                    MAX(d.charge_date) AS charge_date, MAX(d.charge_no) AS charge_no, MAX(d.charge) AS charge,
                    MAX(d.receive_date) AS receive_date, MAX(d.receive_no) AS receive_no,
                    CASE WHEN (IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END
                    + IFNULL(MAX(d.adj_inc),0) - IFNULL(MAX(d.adj_dec),0) - MAX(d.debtor)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), MAX(d.vstdate)) END AS days
                FROM debtor_1102050101_216 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,
                    SUM(receive_total) - SUM(receive_pp) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,sum(receive_total) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs_kidney GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?
                GROUP BY d.vn', [$start_date, $end_date]);
        }

        $debtor_search_kidney = [];
        $debtor_search_cr = [];
        $debtor_search_anywhere = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // REMOVED session('debtor') TO PREVENT BLOAT
        $request->session()->save();

        return view('debtor.1102050101_216', compact(
            'start_date',
            'end_date',
            'search',
            'debtor',
            'debtor_search_kidney',
            'debtor_search_cr',
            'debtor_search_anywhere',
            'count_tab1'
        ));
    }


    public function _1102050101_216_search_kidney_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $data = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an, pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(combined.claim_price,0) AS kidney_amount,IFNULL(combined.claim_price,0) AS debtor, combined.claim_list,"ยืนยันลูกหนี้" AS status
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS claim_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL")
            AND IFNULL(rc.rcpt_money,0) <> IFNULL(combined.claim_price,0)
            AND combined.claim_price > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE kidney IS NOT NULL) 
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }

    public function _1102050101_216_search_cr_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $data = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(combined.claim_price,0) AS uc_amount,IFNULL(combined.claim_price,0) AS debtor,combined.claim_list,
                IF(oe.moph_finance_upload_status IS NOT NULL, "Y", "") AS send_claim,"ยืนยันลูกหนี้" AS status 
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN (li.uc_cr = "Y" OR li.herb32 = "Y") THEN op.sum_price ELSE 0 END) AS claim_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN (li.uc_cr = "Y" OR li.herb32 = "Y") THEN sd.`name` END) AS claim_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL")
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND IFNULL(rc.rcpt_money,0) <> IFNULL(combined.claim_price,0)
            AND combined.claim_price > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE cr IS NOT NULL) 
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }

    public function _1102050101_216_search_anywhere_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $data = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(combined.other_price,0) AS other,IFNULL(combined.ppfs_price,0) AS ppfs,
                IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)-IFNULL(combined.ppfs_price,0) AS debtor,
                combined.other_list,combined.ppfs_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS send_claim,"ยืนยันลูกหนี้" AS status 
            FROM ovst o   
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                        SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL")
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")  
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE anywhere IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }

    //_1102050101_216_confirm_kidney-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_confirm_kidney(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox_kidney' => 'required|array',
        ], [
            'checkbox_kidney.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_kidney'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an, pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(combined.claim_price,0) AS kidney_amount,IFNULL(combined.claim_price,0) AS debtor, combined.claim_list,"ยืนยันลูกหนี้" AS status
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS claim_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS claim_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL")
            AND IFNULL(rc.rcpt_money,0) <> IFNULL(combined.claim_price,0)
            AND combined.claim_price > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE kidney IS NOT NULL) 
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            $check = Debtor_1102050101_216::where('vn', $row->vn)->first();
            if ($check) {
                $check->update([
                    'kidney' => $row->debtor,
                    'debtor' => (float)($check->cr ?? 0) + (float)($check->anywhere ?? 0) + (float)$row->debtor + (float)($check->ppfs ?? 0),
                ]);
            } else {
                Debtor_1102050101_216::insert([
                    'vn' => $row->vn,
                    'hn' => $row->hn,
                    'cid' => $row->cid,
                    'ptname' => $row->ptname,
                    'vstdate' => $row->vstdate,
                    'vsttime' => $row->vsttime,
                    'pttype' => $row->pttype,
                    'hospmain' => $row->hospmain,
                    'hipdata_code' => $row->hipdata_code,
                    'pdx' => $row->pdx,
                    'income' => $row->income,
                    'rcpt_money' => $row->rcpt_money,
                    'kidney' => $row->debtor,
                    'debtor' => $row->debtor,
                    'status' => $row->status,
                ]);
            }
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_216_confirm_cr-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_confirm_cr(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox_cr' => 'required|array',
        ], [
            'checkbox_cr.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_cr'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(combined.claim_price,0) AS uc_amount,IFNULL(combined.claim_price,0) AS debtor,combined.claim_list,
                IF(oe.moph_finance_upload_status IS NOT NULL, "Y", "") AS send_claim,"ยืนยันลูกหนี้" AS status 
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN (li.uc_cr = "Y" OR li.herb32 = "Y") THEN op.sum_price ELSE 0 END) AS claim_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN (li.uc_cr = "Y" OR li.herb32 = "Y") THEN sd.`name` END) AS claim_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "") 
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL")            
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND IFNULL(rc.rcpt_money,0) <> IFNULL(combined.claim_price,0)
            AND combined.claim_price > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE cr IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            $check = Debtor_1102050101_216::where('vn', $row->vn)->first();
            if ($check) {
                $check->update([
                    'cr' => $row->debtor,
                    'debtor' => (float)($check->kidney ?? 0) + (float)($check->anywhere ?? 0) + (float)$row->debtor + (float)($check->ppfs ?? 0),
                ]);
            } else {
                Debtor_1102050101_216::insert([
                    'vn' => $row->vn,
                    'hn' => $row->hn,
                    'cid' => $row->cid,
                    'ptname' => $row->ptname,
                    'vstdate' => $row->vstdate,
                    'vsttime' => $row->vsttime,
                    'pttype' => $row->pttype,
                    'hospmain' => $row->hospmain,
                    'hipdata_code' => $row->hipdata_code,
                    'pdx' => $row->pdx,
                    'income' => $row->income,
                    'rcpt_money' => $row->rcpt_money,
                    'cr' => $row->debtor,
                    'debtor' => $row->debtor,
                    'status' => $row->status,
                ]);
            }
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_216_confirm_anywhere-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_confirm_anywhere(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox_anywhere' => 'required|array',
        ], [
            'checkbox_anywhere.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_anywhere'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(combined.other_price,0) AS other,IFNULL(combined.ppfs_price,0) AS ppfs,
                IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)-IFNULL(combined.ppfs_price,0) AS debtor,
                combined.other_list,combined.ppfs_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS send_claim,"ยืนยันลูกหนี้" AS status 
            FROM ovst o   
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                        SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)) > 0
            AND p.hipdata_code IN ("UCS","WEL")
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")  
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE anywhere IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            $check = Debtor_1102050101_216::where('vn', $row->vn)->first();
            if ($check) {
                $check->update([
                    'anywhere' => $row->debtor,
                    'ppfs' => $row->ppfs,
                    'debtor' => (float)($check->kidney ?? 0) + (float)($check->cr ?? 0) + (float)$row->debtor + (float)$row->ppfs,
                ]);
            } else {
                Debtor_1102050101_216::insert([
                    'vn' => $row->vn,
                    'hn' => $row->hn,
                    'cid' => $row->cid,
                    'ptname' => $row->ptname,
                    'vstdate' => $row->vstdate,
                    'vsttime' => $row->vsttime,
                    'pttype' => $row->pttype,
                    'hospmain' => $row->hospmain,
                    'hipdata_code' => $row->hipdata_code,
                    'pdx' => $row->pdx,
                    'income' => $row->income,
                    'rcpt_money' => $row->rcpt_money,
                    'anywhere' => $row->debtor,
                    'ppfs' => $row->ppfs,
                    'debtor' => $row->debtor,
                    'status' => $row->status,
                ]);
            }
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_216_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_216::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_216::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_216_lock(Request $request, $vn)
    {
        Debtor_1102050101_216::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_216_unlock(Request $request, $vn)
    {
        Debtor_1102050101_216::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_216_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT a.vstdate, COUNT(DISTINCT a.vn) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive) AS receive
            FROM (SELECT d.vn,d.vstdate,d.debtor,IFNULL(s.receive_total,0) + CASE  WHEN d.kidney > 0
                    THEN IFNULL(sk.receive_total,0) ELSE 0 END AS receive
                FROM debtor_1102050101_216 d
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,
                    SUM(receive_total) - SUM(receive_pp) AS receive_total
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate, SUM(receive_total) AS receive_total
                    FROM stm_ucs_kidney GROUP BY cid, datetimeadm) sk ON sk.cid = d.cid AND sk.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?) a
                GROUP BY a.vstdate ORDER BY a.vstdate ', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_216_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    public function _1102050101_216_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,MAX(d.an) AS an,MAX(d.cid) AS cid,MAX(d.ptname) AS ptname,MAX(d.hipdata_code) AS hipdata_code,MAX(d.pttype) AS pttype,
                    MAX(d.hospmain) AS hospmain,MAX(d.pdx) AS pdx,MAX(d.income) AS income,MAX(d.rcpt_money) AS rcpt_money,
                    MAX(d.kidney) AS kidney,MAX(d.cr) AS cr, MAX(d.anywhere) AS anywhere,MAX(d.ppfs) AS ppfs,MAX(d.debtor) AS debtor,
                    IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END AS receive,
                    MAX(s.repno) AS repno,MAX(sk.repno) AS rid,MAX(d.debtor_lock) AS debtor_lock,
                    IFNULL(MAX(s.round_no), MAX(sk.round_no)) AS stm_round_no,
                    IFNULL(MAX(s.receipt_date), MAX(sk.receipt_date)) AS stm_receipt_date,
                    IFNULL(MAX(s.receive_no), MAX(sk.receive_no)) AS stm_receive_no,
                    MAX(d.status) AS status, MAX(d.adj_inc) AS adj_inc, MAX(d.adj_dec) AS adj_dec, MAX(d.adj_note) AS adj_note, MAX(d.adj_date) AS adj_date,
                    MAX(d.charge_date) AS charge_date, MAX(d.charge_no) AS charge_no, MAX(d.charge) AS charge,
                    MAX(d.receive_date) AS receive_date, MAX(d.receive_no) AS receive_no,
                    CASE WHEN (IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END
                    + IFNULL(MAX(d.adj_inc),0) - IFNULL(MAX(d.adj_dec),0) - MAX(d.debtor)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), MAX(d.vstdate)) END AS days
                FROM debtor_1102050101_216 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,
                    SUM(receive_total) - SUM(receive_pp) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,SUM(receive_total) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs_kidney GROUP BY cid, datetimeadm) sk ON sk.cid = d.cid AND sk.vstdate = d.vstdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?
                GROUP BY d.vn', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,MAX(d.an) AS an,MAX(d.cid) AS cid,MAX(d.ptname) AS ptname,MAX(d.hipdata_code) AS hipdata_code,MAX(d.pttype) AS pttype,
                    MAX(d.hospmain) AS hospmain,MAX(d.pdx) AS pdx,MAX(d.income) AS income,MAX(d.rcpt_money) AS rcpt_money,
                    MAX(d.kidney) AS kidney,MAX(d.cr) AS cr, MAX(d.anywhere) AS anywhere,MAX(d.ppfs) AS ppfs,MAX(d.debtor) AS debtor,
                    IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END AS receive,
                    MAX(s.repno) AS repno,MAX(sk.repno) AS rid,MAX(d.debtor_lock) AS debtor_lock,
                    IFNULL(MAX(s.round_no), MAX(sk.round_no)) AS stm_round_no,
                    IFNULL(MAX(s.receipt_date), MAX(sk.receipt_date)) AS stm_receipt_date,
                    IFNULL(MAX(s.receive_no), MAX(sk.receive_no)) AS stm_receive_no,
                    MAX(d.status) AS status, MAX(d.adj_inc) AS adj_inc, MAX(d.adj_dec) AS adj_dec, MAX(d.adj_note) AS adj_note, MAX(d.adj_date) AS adj_date,
                    MAX(d.charge_date) AS charge_date, MAX(d.charge_no) AS charge_no, MAX(d.charge) AS charge,
                    MAX(d.receive_date) AS receive_date, MAX(d.receive_no) AS receive_no,
                    CASE WHEN (IFNULL(MAX(s.receive_total),0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(MAX(sk.receive_total),0) ELSE 0 END
                    + IFNULL(MAX(d.adj_inc),0) - IFNULL(MAX(d.adj_dec),0) - MAX(d.debtor)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), MAX(d.vstdate)) END AS days
                FROM debtor_1102050101_216 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,
                    SUM(receive_total) - SUM(receive_pp) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,sum(receive_total) AS receive_total,MAX(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs_kidney GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?
                GROUP BY d.vn', [$start_date, $end_date]);
        }

        return view('debtor.1102050101_216_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_301--------------------------------------------------------------------------------------------------------------
    public function _1102050101_301(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        
        $query = DB::table('debtor_1102050101_301 as d')
            ->leftJoin(DB::raw('(SELECT cid, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp, MAX(repno) AS repno FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) as s'), function($join) {
                $join->on('s.cid', '=', 'd.cid')
                     ->on('s.vstdate', '=', 'd.vstdate')
                     ->on('s.vsttime5', '=', DB::raw('LEFT(d.vsttime,5)'));
            })
            ->select('d.*', 's.receive_pp', DB::raw('IF(s.receive_pp <> "", s.repno, "") AS repno_pp'),
                DB::raw('CASE WHEN (IFNULL(d.receive, 0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor, 0)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days')
            )
            ->whereBetween('d.vstdate', [$start_date, $end_date]);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('d.ptname', 'LIKE', "%{$search}%")
                  ->orWhere('d.hn', 'LIKE', "%{$search}%");
            });
        }

        $debtor = $query->get();
        $count_tab1 = $debtor->count();
        $debtor_search = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050101_301', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_301_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(combined.other_price,0) AS other,IFNULL(combined.ppfs_price,0)  AS ppfs,
                IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)-IFNULL(combined.ppfs_price,0) AS debtor,
                combined.other_list,combined.ppfs_list,"ยืนยันลูกหนี้" AS status
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                        SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)) > 0
            AND p.hipdata_code = "SSS"            
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_301 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_sss_fund . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_301_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_301_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(combined.other_price,0) AS other,IFNULL(combined.ppfs_price,0)  AS ppfs,
                IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)-IFNULL(combined.ppfs_price,0) AS debtor,
                combined.other_list,combined.ppfs_list,"ยืนยันลูกหนี้" AS status
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                        SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)) > 0
            AND p.hipdata_code = "SSS"            
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_301 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_sss_fund . ')
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_301::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_301_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_301_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_301::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_301::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_301_lock(Request $request, $vn)
    {
        Debtor_1102050101_301::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_301_unlock(Request $request, $vn)
    {
        Debtor_1102050101_301::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_301_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_301_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_301  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_301_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_301_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_301_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $query = DB::table('debtor_1102050101_301 as d')
            ->leftJoin(DB::raw('(SELECT cid, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp, MAX(repno) AS repno FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) as s'), function($join) {
                $join->on('s.cid', '=', 'd.cid')
                     ->on('s.vstdate', '=', 'd.vstdate')
                     ->on('s.vsttime5', '=', DB::raw('LEFT(d.vsttime,5)'));
            })
            ->select('d.*', 's.receive_pp', DB::raw('IF(s.receive_pp <> "", s.repno, "") AS repno_pp'),
                DB::raw('CASE WHEN (IFNULL(d.receive, 0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor, 0)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days')
            )
            ->whereBetween('d.vstdate', [$start_date, $end_date]);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('d.ptname', 'LIKE', "%{$search}%")
                  ->orWhere('d.hn', 'LIKE', "%{$search}%");
            });
        }

        $debtor = $query->get();

        return view('debtor.1102050101_301_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    //_1102050101_301_average_receive-------------------------------------------------------------------------------------------------------   
    public function _1102050101_301_average_receive(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date',
            'repno' => 'required|string',
            'total_receive' => 'required|numeric|min:0.01',
            'receive_date' => 'required|date',
        ]);

        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;
        $repno = $request->repno;
        $total = (float) $request->total_receive;
        $receive_date = $request->receive_date;

        // ดึงข้อมูล
        $rows = DB::table('debtor_1102050101_301')
            ->whereBetween('vstdate', [$dateStart, $dateEnd])
            ->get();

        $count = $rows->count();
        if ($count === 0) {
            return response()->json([
                'status' => 'error',
                'message' => "ไม่พบข้อมูล"
            ]);
        }

        // ===== 1) คำนวณน้ำหนักตาม debtor =====
        $sumDebtor = $rows->sum('debtor');

        $items = [];
        foreach ($rows as $row) {

            // น้ำหนักตามสัดส่วน debtor
            $weight = $row->debtor / $sumDebtor;

            // ยอดที่ควรได้รับตามสัดส่วน
            $assign = round($total * $weight, 2);

            $items[] = [
                'vn' => $row->vn,
                'assign' => $assign,
            ];
        }

        // ===== 2) ปรับ diff ให้ผลรวมตรง total_receive =====
        $sumAssigned = array_sum(array_column($items, 'assign'));
        $diff = round($total - $sumAssigned, 2);

        $i = 0;
        while (abs($diff) >= 0.01) {

            // เพิ่มทีละ 1 สตางค์ให้ record ตามลำดับ
            if ($diff > 0) {
                $items[$i]['assign'] = round($items[$i]['assign'] + 0.01, 2);
                $diff = round($diff - 0.01, 2);
            }
            // หรือลดทีละ 1 สตางค์
            else {
                if ($items[$i]['assign'] > 0.01) {
                    $items[$i]['assign'] = round($items[$i]['assign'] - 0.01, 2);
                    $diff = round($diff + 0.01, 2);
                }
            }

            $i = ($i + 1) % $count;
        }

        // ===== 3) บันทึกจริงลงฐานข้อมูล =====
        foreach ($items as $it) {
            DB::table('debtor_1102050101_301')
                ->where('vn', $it['vn'])
                ->update([
                    'receive' => $it['assign'],
                    'repno' => $repno,
                    'receive_date' => $receive_date,
                    'status' => 'กระทบยอดแล้ว',
                ]);
        }

        $finalSum = array_sum(array_column($items, 'assign'));

        return response()->json([
            'status' => 'success',
            'message' => "
                วันที่ : <b>{$dateStart}</b> ถึง <b>{$dateEnd}</b><br>
                จำนวน Visit : <b>{$count}</b><br>
                ยอดชดเชย : <b>" . number_format($total, 2) . "</b><br>
                ยอดที่จัดสรรได้จริง : <b>" . number_format($finalSum, 2) . "</b> ✔ ตรง 100%
            "
        ]);
    }
    ##############################################################################################################################################################
    //_1102050101_303--------------------------------------------------------------------------------------------------------------
    public function _1102050101_302_average_receive(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date',
            'repno' => 'required|string',
            'total_receive' => 'required|numeric|min:0.01',
            'receive_date' => 'required|date',
        ]);

        $dateStart = $request->date_start;
        $dateEnd = $request->date_end;
        $repno = $request->repno;
        $total = (float) $request->total_receive;
        $receive_date = $request->receive_date;

        // ดึงข้อมูล
        $rows = DB::table('debtor_1102050101_302')
            ->whereBetween('dchdate', [$dateStart, $dateEnd])
            ->get();

        $count = $rows->count();
        if ($count === 0) {
            return response()->json([
                'status' => 'error',
                'message' => "ไม่พบข้อมูล"
            ]);
        }

        // ===== 1) คำนวณน้ำหนักตาม debtor =====
        $sumDebtor = $rows->sum('debtor');
        if($sumDebtor <= 0) {
            return response()->json(['status' => 'error', 'message' => "ยอดรวมลูกหนี้เป็น 0"]);
        }

        $items = [];
        foreach ($rows as $row) {
            $weight = $row->debtor / $sumDebtor;
            $assign = round($total * $weight, 2);
            $items[] = [
                'an' => $row->an,
                'assign' => $assign,
            ];
        }

        // ===== 2) ปรับ diff ให้ผลรวมตรง total_receive =====
        $sumAssigned = array_sum(array_column($items, 'assign'));
        $diff = round($total - $sumAssigned, 2);
        $i = 0;
        while (abs($diff) >= 0.01) {
            if ($diff > 0) {
                $items[$i]['assign'] = round($items[$i]['assign'] + 0.01, 2);
                $diff = round($diff - 0.01, 2);
            } else {
                if ($items[$i]['assign'] > 0.01) {
                    $items[$i]['assign'] = round($items[$i]['assign'] - 0.01, 2);
                    $diff = round($diff + 0.01, 2);
                }
            }
            $i = ($i + 1) % $count;
        }

        // ===== 3) บันทึกจริงลงฐานข้อมูล =====
        foreach ($items as $it) {
            DB::table('debtor_1102050101_302')
                ->where('an', $it['an'])
                ->update([
                    'receive' => $it['assign'],
                    'repno' => $repno,
                    'receive_date' => $receive_date,
                    'status' => 'กระทบยอดแล้ว',
                ]);
        }
        
        $finalSum = array_sum(array_column($items, 'assign'));

        return response()->json([
            'status' => 'success',
            'message' => "
                วันที่ : <b>{$dateStart}</b> ถึง <b>{$dateEnd}</b><br>
                จำนวน AN : <b>{$count}</b><br>
                ยอดชดเชย : <b>" . number_format($total, 2) . "</b><br>
                ยอดที่จัดสรรได้จริง : <b>" . number_format($finalSum, 2) . "</b> ✔ ตรง 100%
            "
        ]);
    }

    public function _1102050101_303(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $count_tab1 = DB::table('debtor_1102050101_303')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->where(function($q) use ($search) {
                    $q->where('ptname', 'LIKE', "%{$search}%")
                      ->orWhere('hn', 'LIKE', "%{$search}%");
                })
                ->count();
        } else {
            $count_tab1 = DB::table('debtor_1102050101_303')
                ->whereBetween('vstdate', [$start_date, $end_date])
                ->count();
        }
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.an,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no, 
                    d.receive ,d.repno,s.receive_pp,IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                    CASE WHEN (IFNULL(d.receive, 0)  + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor, 0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_303 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.an,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no, 
                    d.receive ,d.repno,s.receive_pp,IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                    CASE WHEN (IFNULL(d.receive, 0)  + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor, 0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_303 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // session('debtor') removed to prevent memory bloat
        $request->session()->save();

        return view('debtor.1102050101_303', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_303_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: "''";

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,v.paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(combined.other_price,0) AS other,IFNULL(combined.ppfs_price,0)  AS ppfs,
                IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)-IFNULL(combined.ppfs_price,0) AS debtor,
                combined.other_list,combined.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                        SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(combined.income,0)-IFNULL(v.paid_money,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)) > 0
            AND p.hipdata_code = "SSS"            
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_303 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_sss_fund . ')
            AND p.pttype NOT IN (' . $pttype_sss_ae . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_303_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(combined.income,0) AS income,v.paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(combined.other_price,0) AS other,IFNULL(combined.ppfs_price,0)  AS ppfs,
                IFNULL(combined.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)-IFNULL(combined.ppfs_price,0) AS debtor,
                combined.other_list,combined.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            INNER JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                        SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                        SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                        GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op 
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) combined ON combined.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(combined.income,0)-IFNULL(v.paid_money,0)-IFNULL(rc.rcpt_money,0)-IFNULL(combined.other_price,0)) > 0
            AND p.hipdata_code = "SSS"            
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_303 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_sss_fund . ')
            AND p.pttype NOT IN (' . $pttype_sss_ae . ')
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_303::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_303_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_303::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_303::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_303_lock(Request $request, $vn)
    {
        Debtor_1102050101_303::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_303_unlock(Request $request, $vn)
    {
        Debtor_1102050101_303::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_303_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_303::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_303_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_303  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_303_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_303_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_303_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, CASE WHEN (IFNULL(d.receive, 0)  + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor, 0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days,
                    s.receive_pp, IF(s.receive_pp <>"", s.repno, "") AS repno_pp
                FROM debtor_1102050101_303 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.*, CASE WHEN (IFNULL(d.receive, 0)  + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor, 0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days,
                    s.receive_pp, IF(s.receive_pp <>"", s.repno, "") AS repno_pp
                FROM debtor_1102050101_303 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        return view('debtor.1102050101_303_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_307--------------------------------------------------------------------------------------------------------------
    public function _1102050101_307(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        $query = DB::table('debtor_1102050101_307')
            ->select('*', 
                DB::raw('receive AS receive_manual'), 
                DB::raw('repno AS repno_manual'), 
                DB::raw('IFNULL(vstdate, dchdate) as visit_date'), 
                DB::raw('IFNULL(vsttime, dchtime) as visit_time'),
                DB::raw('0 AS receive_pp'),
                DB::raw('"" AS repno_pp'),
                DB::raw('CASE WHEN (IFNULL(receive, 0) + IFNULL(adj_inc, 0) - IFNULL(adj_dec, 0) - IFNULL(debtor, 0)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), IFNULL(vstdate, dchdate)) END AS days')
            )
            ->whereBetween(DB::raw('IFNULL(vstdate, dchdate)'), [$start_date, $end_date]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ptname', 'like', '%' . $search . '%')
                    ->orWhere('hn', 'like', '%' . $search . '%')
                    ->orWhere('an', 'like', '%' . $search . '%');
            });
        }

        $debtor = $query->orderBy(DB::raw('IFNULL(vstdate, dchdate)'))->get();
        $count_tab1 = $debtor->count();

        $debtor_search = [];
        $debtor_search_ip = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050101_307', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'debtor_search_ip', 'count_tab1'));
    }



    public function _1102050101_307_search_op_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate, o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other, IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_307 WHERE vn IS NOT NULL)
            AND p.pttype IN (' . $pttype_sss_fund . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }

    public function _1102050101_307_search_ip_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: "''";

        $debtor_search_ip = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(total.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(total.other_price,0) AS other,
                COALESCE(total.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(total.other_price,0) AS debtor,total.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT 
                    o.an, o.pttype,
                    SUM(o.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN o.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.name END) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems sd ON sd.icode = o.icode
                GROUP BY o.an, o.pttype
            ) total ON total.an = i.an AND total.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND i.dchdate BETWEEN ? AND ?            
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_307 WHERE an IS NOT NULL)
			AND ip.pttype IN (' . $pttype_sss_fund . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date]
        );

        return response()->json($debtor_search_ip);
    }
    //_1102050101_307_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = "'" . implode("','", $checkbox) . "'"; // แปลงเป็น string สำหรับ SQL IN พร้อมใส่ single quote

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate, o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other, IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_307 WHERE vn IS NOT NULL)
            AND p.pttype IN (' . $pttype_sss_fund . ')
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_307::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_307_confirm_ip-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_confirm_ip(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $request->validate([
            'checkbox_ip' => 'required|array',
        ], [
            'checkbox_ip.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox_ip = $request->input('checkbox_ip'); // รับ array
        $checkbox_string = "'" . implode("','", $checkbox_ip) . "'"; // แปลงเป็น string สำหรับ SQL IN พร้อมใส่ single quote

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(total.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(total.other_price,0) AS other,
                COALESCE(total.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(total.other_price,0) AS debtor,total.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (
                SELECT 
                    o.an, o.pttype,
                    SUM(o.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN o.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.name END) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems sd ON sd.icode = o.icode
                GROUP BY o.an, o.pttype
            ) total ON total.an = i.an AND total.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND i.dchdate BETWEEN ? AND ?            
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_307 WHERE an IS NOT NULL)
			AND ip.pttype IN (' . $pttype_sss_fund . ') 
            AND i.an IN (' . $checkbox_string . ')
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050101_307::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox_ip) || !is_array($checkbox_ip)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_307_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_307::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_307::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_307_lock(Request $request, $vn)
    {
        Debtor_1102050101_307::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_307_unlock(Request $request, $vn)
    {
        Debtor_1102050101_307::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    public function _1102050101_307_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_307::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_307_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT COALESCE(dchdate, vstdate) AS vstdate,
                COUNT(DISTINCT IF(an IS NOT NULL AND an <> "", an, vn)) AS anvn,
                SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_307 
            WHERE COALESCE(dchdate, vstdate) BETWEEN ? AND ?
            GROUP BY COALESCE(dchdate, vstdate)
            ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_307_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_307_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_307_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $query = DB::table('debtor_1102050101_307')
            ->select("*", 
                DB::raw("IFNULL(vstdate, dchdate) as visit_date"), 
                DB::raw("IFNULL(vsttime, dchtime) as visit_time"),
                DB::raw("0 AS receive_pp"),
                DB::raw("'' AS repno_pp"),
                DB::raw("CASE WHEN (IFNULL(receive, 0) + IFNULL(adj_inc, 0) - IFNULL(adj_dec, 0) - IFNULL(debtor, 0)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), IFNULL(vstdate, dchdate)) END AS days")
            )
            ->whereBetween(DB::raw("IFNULL(vstdate, dchdate)"), [$start_date, $end_date]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ptname', 'like', '%' . $search . '%')
                    ->orWhere('hn', 'like', '%' . $search . '%')
                    ->orWhere('an', 'like', '%' . $search . '%');
            });
        }

        $debtor = $query->orderBy(DB::raw("IFNULL(vstdate, dchdate)"))->get();

        return view('debtor.1102050101_307_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_309--------------------------------------------------------------------------------------------------------------
    public function _1102050101_309(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vstdate,d.vsttime,d.vn,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.pdx,d.hospmain,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,IFNULL(d.receive,0)+IFNULL(s.receive,0) AS receive,d.repno,
                    s.repno AS rid,d.debtor_lock,d.status,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                    s.round_no AS stm_round_no, s.receipt_date AS stm_receipt_date, s.receive_no AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(s.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_309 d   
                LEFT JOIN (SELECT cid,vstdate,SUM(IFNULL(amount,0)+ IFNULL(epopay,0)+ IFNULL(epoadm,0)) AS receive,
                    GROUP_CONCAT(DISTINCT rid) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_sss_kidney GROUP BY cid, vstdate) s ON s.cid = d.cid AND s.vstdate = d.vstdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vstdate,d.vsttime,d.vn,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.pdx,d.hospmain,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,IFNULL(d.receive,0)+IFNULL(s.receive,0) AS receive,d.repno,
                    s.repno AS rid,d.debtor_lock,d.status,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                    s.round_no AS stm_round_no, s.receipt_date AS stm_receipt_date, s.receive_no AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(s.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_309 d   
                LEFT JOIN (SELECT cid,vstdate,SUM(IFNULL(amount,0)+ IFNULL(epopay,0)+ IFNULL(epoadm,0)) AS receive,
                    GROUP_CONCAT(DISTINCT rid) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_sss_kidney GROUP BY cid, vstdate) s ON s.cid = d.cid AND s.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $count_tab1 = count($debtor);
        $debtor_search = [];
        $debtor_search_ae = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050101_309', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'debtor_search_ae', 'count_tab1'));
    }



    public function _1102050101_309_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.kidney_price,0) AS kidney,
                IFNULL(total.kidney_price,0) AS debtor,total.kidney_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI")
            AND IFNULL(rc.rcpt_money,0) < IFNULL(total.kidney_price,0)
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_309 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }

    public function _1102050101_309_search_ae_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value');

        $debtor_search_ae = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate, o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other, IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_309 WHERE vn IS NOT NULL)
            AND p.pttype IN (' . $pttype_sss_ae . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search_ae);
    }
    //_1102050101_309_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.kidney_price,0) AS kidney,
                IFNULL(total.kidney_price,0) AS debtor,total.kidney_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI")
            AND IFNULL(rc.rcpt_money,0) < IFNULL(total.kidney_price,0)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_309::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'kidney' => $row->kidney,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_309_confirm_ae-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_confirm_ae(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_ae = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value');

        $request->validate([
            'checkbox_ae' => 'required|array',
        ], [
            'checkbox_ae.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_ae'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate, o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other, IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" OR li.kidney = "Y" THEN sd.`name` END) AS other_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0)) > 0
            AND p.pttype IN (' . $pttype_sss_ae . ')
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_309::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_309_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_309::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_309::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_309_lock(Request $request, $vn)
    {
        Debtor_1102050101_309::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_309_unlock(Request $request, $vn)
    {
        Debtor_1102050101_309::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_309_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_309::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_309_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM (SELECT d.vstdate,d.vsttime,d.vn,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,
            d.pdx,d.hospmain,d.income,d.rcpt_money,d.kidney,d.debtor,
            s.amount+s.epopay+s.epoadm AS receive,s.rid AS repno,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive AS receive_manual, d.repno AS repno_manual
            FROM debtor_1102050101_309 d   
            LEFT JOIN stm_sss_kidney s ON s.cid=d.cid AND s.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?) AS a GROUP BY vstdate ORDER BY vsttime', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_309_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_309_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_309_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $query = "
            SELECT d.vstdate,d.vsttime,d.vn,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.pdx,d.hospmain,
                d.income,d.rcpt_money,d.kidney,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,IFNULL(d.receive,0)+IFNULL(s.receive,0) AS receive,d.repno,
                s.repno AS rid,d.debtor_lock,d.status,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,
                d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                s.round_no AS stm_round_no, s.receipt_date AS stm_receipt_date, s.receive_no AS stm_receive_no,
                CASE WHEN (IFNULL(d.receive,0) + IFNULL(s.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
            FROM debtor_1102050101_309 d   
            LEFT JOIN (SELECT cid,vstdate,SUM(IFNULL(amount,0)+ IFNULL(epopay,0)+ IFNULL(epoadm,0)) AS receive,
                GROUP_CONCAT(DISTINCT rid) AS repno,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_sss_kidney GROUP BY cid, vstdate) s ON s.cid = d.cid AND s.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?
        ";

        $params = [$start_date, $end_date];

        if ($search) {
            $query .= ' AND (d.ptname LIKE ? OR d.hn LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $debtor = DB::select($query, $params);

        return view('debtor.1102050101_309_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_401--------------------------------------------------------------------------------------------------------------
    public function _1102050101_401(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        if ($search) {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                    d.income,d.rcpt_money,d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,
                    d.charge,d.receive_date,d.receive_no,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note, 
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,stm.repno,csop.rid,hd.rid_hd,d.debtor_lock,
                    CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_401 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                    GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                    ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                    FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%'))
                AND d.vstdate BETWEEN ? AND ?", [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                    d.income,d.rcpt_money,d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,
                    d.charge,d.receive_date,d.receive_no,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,stm.repno,csop.rid,hd.rid_hd,d.debtor_lock,
                    CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_401 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                    GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                    ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                    FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date]);
        }

        $count_tab1 = count($debtor);
        $debtor_search = [];

        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);
        Session::put('search', $search);

        return view('debtor.1102050101_401', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_401_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(total.kidney_price,0) AS kidney,IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS ofc,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "OFC"             
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_401 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ')   
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_401_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(total.kidney_price,0) AS kidney,IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS ofc,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "OFC"            
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_401 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ') 
            AND o.vn IN (' . $checkbox_string . ')   
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_401::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'ofc' => $row->ofc,
                'kidney' => $row->kidney,
                'ppfs' => $row->ppfs,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_401_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_401::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_401::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_401_lock(Request $request, $vn)
    {
        Debtor_1102050101_401::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_401_unlock(Request $request, $vn)
    {
        Debtor_1102050101_401::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_401_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_401::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_401_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select("
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,
                SUM(IFNULL(receive,0)+IFNULL(receive_total,0)+IFNULL(csop_amount,0)
                + CASE WHEN kidney > 0 THEN IFNULL(hd_amount,0) ELSE 0 END) AS receive
            FROM (SELECT d.vstdate,d.vn,d.kidney,d.debtor,d.receive,stm.receive_total,
                    csop.amount AS csop_amount,hd.amount AS hd_amount
                FROM debtor_1102050101_401 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total
                    FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(amount) AS amount
                    FROM hrims.stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = d.hn
                    AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount FROM hrims.stm_ofc_csop
                    WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn AND hd.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?) a
            GROUP BY vstdate ORDER BY vstdate", [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_401_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_401_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_401_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $query = "
            SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                d.income,d.rcpt_money,d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,
                d.charge,d.receive_date,d.receive_no,
                d.adj_inc, d.adj_dec, d.adj_date, d.adj_note, 
                (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,stm.repno,csop.rid,hd.rid_hd,d.debtor_lock,
                CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
            FROM debtor_1102050101_401 d   
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                GROUP_CONCAT(repno) AS repno,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                GROUP_CONCAT(rid) AS rid,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
            WHERE d.vstdate BETWEEN ? AND ?
        ";

        $params = [$start_date, $end_date];

        if ($search) {
            $query .= " AND (d.ptname LIKE ? OR d.hn LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $debtor = DB::select($query, $params);

        return view('debtor.1102050101_401_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_501--------------------------------------------------------------------------------------------------------------
    public function _1102050101_501(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        $debtor = Debtor_1102050101_501::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                // Balance logic: (receive + adj_inc - adj_dec) - debtor
                $item->balance = ((float)$item->receive + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
                
                if ($item->balance >= -0.01) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        $count_tab1 = count($debtor);
        $debtor_search = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050101_501', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_501_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an, pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, IFNULL(total.other_price,0) AS other, total.other_list,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0) AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "NRH"    
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_501 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_501_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an, pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, IFNULL(total.other_price,0) AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0) AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "NRH"    
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_501 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_501::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_501_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_501::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_501::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_501_lock(Request $request, $vn)
    {
        Debtor_1102050101_501::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_501_unlock(Request $request, $vn)
    {
        Debtor_1102050101_501::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_501_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_501::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_501_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_501  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_501_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_501_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_501_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050101_501::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->balance = ((float)$item->receive + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
                $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                return $item;
            });

        return view('debtor.1102050101_501_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_503--------------------------------------------------------------------------------------------------------------
    public function _1102050101_503(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        $debtor = Debtor_1102050101_503::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                // Balance logic: (receive + adj_inc - adj_dec) - debtor
                $item->balance = ((float)$item->receive + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
                
                if ($item->balance >= -0.01) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        $count_tab1 = count($debtor);
        $debtor_search = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050101_503', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_503_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an, pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, IFNULL(total.other_price,0) AS other, total.other_list,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0) AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "NRH"    
            AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                OR vp.hospmain IS NULL OR vp.hospmain ="")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_503 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_503_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an, pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, IFNULL(total.other_price,0) AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0) AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "NRH"    
            AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                OR vp.hospmain IS NULL OR vp.hospmain ="")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_503 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_503::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_503_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_503::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_503::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_503_lock(Request $request, $vn)
    {
        Debtor_1102050101_503::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_503_unlock(Request $request, $vn)
    {
        Debtor_1102050101_503::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_503_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_503::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_503_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_503  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_503_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_503_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_503_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050101_503::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->balance = ((float)$item->receive + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
                $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                return $item;
            });

        return view('debtor.1102050101_503_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_701--------------------------------------------------------------------------------------------------------------
    public function _1102050101_701(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno
                FROM debtor_1102050101_701 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno
                FROM debtor_1102050101_701 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = [];

        $debtor = collect($debtor)->map(function ($item) {
            $item->balance = ((float)$item->receive  + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
            $item->days = ($item->balance >= -0.01) ? 0 : Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
            return $item;
        });

        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050101_701', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_701_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other,IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status 
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v  ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" THEN sd.`name` END) AS other_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?    
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "STP"
            AND vp.hospmain IN ( SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_701 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }

    //_1102050101_701_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_701_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other,IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                "ยืนยันลูกหนี้" AS status 
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v  ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?    
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "STP"
            AND vp.hospmain IN ( SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_701 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_701::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_701_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_701_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_701::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_701::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_701_lock(Request $request, $vn)
    {
        Debtor_1102050101_701::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_701_unlock(Request $request, $vn)
    {
        Debtor_1102050101_701::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_701_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_701_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_701  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_701_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_701_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_701_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050101_701::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->balance = ((float)$item->receive + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
                $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                return $item;
            });

        return view('debtor.1102050101_701_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_702--------------------------------------------------------------------------------------------------------------
    public function _1102050101_702(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno
                FROM debtor_1102050101_702 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock, d.adj_inc, d.adj_dec, d.adj_note, d.adj_date, d.debtor_change, d.charge_date, d.charge_no, d.charge, d.receive_date, d.receive_no, d.receive, d.repno
                FROM debtor_1102050101_702 d   
                LEFT JOIN ( SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(receive_pp) AS receive_pp,MAX(repno) AS repno
                    FROM stm_ucs GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid 
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = [];

        $debtor = collect($debtor)->map(function ($item) {
            $item->balance = ((float)$item->receive  + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
            $item->days = ($item->balance >= -0.01) ? 0 : Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
            return $item;
        });

        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050101_702', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050101_702_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other,IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status 
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v  ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" THEN sd.`name` END) AS other_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs = "Y" THEN sd.`name` END) AS ppfs_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?    
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "STP"
            AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y") OR vp.hospmain IS NULL OR vp.hospmain ="")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_702 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }

    //_1102050101_702_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_702_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other,IFNULL(total.ppfs_price,0)  AS ppfs,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                "ยืนยันลูกหนี้" AS status 
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v  ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    SUM(CASE WHEN li.ppfs = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (SELECT r.vn,SUM( r.total_amount ) AS rcpt_money
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?    
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code = "STP"
            AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                OR vp.hospmain IS NULL OR vp.hospmain ="")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_702 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_702::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'ppfs' => $row->ppfs,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_702_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_702_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_702::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_702::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_702_lock(Request $request, $vn)
    {
        Debtor_1102050101_702::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_702_unlock(Request $request, $vn)
    {
        Debtor_1102050101_702::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_702_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_702_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_702  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_702_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_702_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_702_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050101_702::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->balance = ((float)$item->receive + (float)$item->adj_inc - (float)$item->adj_dec) - (float)$item->debtor;
                $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                return $item;
            });

        return view('debtor.1102050101_702_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_106--------------------------------------------------------------------------------------------------------------
    public function _1102050102_106(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value');

        if ($search) {
            $debtor = DB::connection('hosxp')->select("
                    SELECT d.vstdate,d.vsttime,d.hn,d.vn,d.ptname,d.mobile_phone_number, d.pttype,d.hospmain,
                    d.pdx,d.income,d.paid_money,d.rcpt_money,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.debtor_lock, 
                    IF(r.total_amount IS NOT NULL, 'กระทบยอดแล้ว', d.status) AS status,
                    d.charge_date,d.charge_no,d.charge,d.receive_date, d.receive_no,  
                    IF(d.receive IS NOT NULL AND d.receive > 0, d.receive, IFNULL(r.total_amount,0) - d.rcpt_money) AS receive,
                    d.adj_inc, d.adj_dec, d.adj_note, d.adj_date,
                    d.repno, r.rcpno, r.total_amount,IFNULL(t.visit,0) AS visit,
                    CASE WHEN (IF(d.receive IS NOT NULL AND d.receive > 0, d.receive, IFNULL(r.total_amount,0) - d.rcpt_money)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.05 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM hrims.debtor_1102050102_106 d
                LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS total_amount,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL   
                    GROUP BY r.vn) r ON r.vn = d.vn
                LEFT JOIN (SELECT vn, COUNT(vn) AS visit  FROM hrims.debtor_1102050102_106_tracking
                    GROUP BY vn) t ON t.vn = d.vn
                WHERE (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%')) 
                AND d.vstdate BETWEEN ? AND ?", [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::connection('hosxp')->select("
                SELECT d.vstdate,d.vsttime,d.hn,d.vn,d.cid,d.ptname,d.mobile_phone_number, d.pttype,d.hospmain,
                    d.pdx,d.income,d.paid_money,d.rcpt_money,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.debtor_lock, 
                    IF(r.total_amount IS NOT NULL, 'กระทบยอดแล้ว', d.status) AS status,
                    d.charge_date,d.charge_no,d.charge,d.receive_date, d.receive_no,  
                    IF(d.receive IS NOT NULL AND d.receive > 0, d.receive, IFNULL(r.total_amount,0) - d.rcpt_money) AS receive,
                    d.adj_inc, d.adj_dec, d.adj_note, d.adj_date,
                    d.repno, r.rcpno, r.total_amount,IFNULL(t.visit,0) AS visit,
                    CASE WHEN (IF(d.receive IS NOT NULL AND d.receive > 0, d.receive, IFNULL(r.total_amount,0) - d.rcpt_money)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.05 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM hrims.debtor_1102050102_106 d
                LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS total_amount,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                    WHERE a.rcpno IS NULL   
                    GROUP BY r.vn) r ON r.vn = d.vn
                LEFT JOIN (SELECT vn, COUNT(vn) AS visit  FROM hrims.debtor_1102050102_106_tracking
                    GROUP BY vn) t ON t.vn = d.vn
                WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date]);
        }

        // Lazy Loading: Defaults to empty, search via AJAX
        $debtor_search = [];
        $debtor_search_iclaim = [];

        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050102_106', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'debtor_search_iclaim', 'count_tab1'));
    }

    public function _1102050102_106_search_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $data = DB::connection('hosxp')->select('
            SELECT o.vstdate, o.vsttime, o.oqueue,o.vn, o.an,o.hn,v.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,
                IFNULL(inc.paid_money,0) AS paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, IFNULL(inc.paid_money,0) - IFNULL(rc.rcpt_money,0) AS debtor,
                rc.rcpno,p2.arrear_date,p2.amount AS arrear_amount,fd.deposit_amount,fd1.debit_amount,"ยืนยันลูกหนี้" AS status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN patient_arrear p2 ON p2.vn = o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                SUM(CASE WHEN op.paidst IN ("00","01","03") THEN op.sum_price ELSE 0 END) AS paid_money
                FROM opitemrece op 
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn) inc ON inc.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.vn = o.vn       
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN hospcode h ON h.hospcode = vp.hospmain
            WHERE (o.an IS NULL OR o.an = "")
            AND IFNULL(inc.paid_money,0) > 0
            AND IFNULL(inc.paid_money,0) - IFNULL(rc.rcpt_money,0) > 0
            AND o.vstdate BETWEEN ? AND ?
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_106 WHERE vstdate BETWEEN ? AND ?)
            GROUP BY o.vn
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }

    public function _1102050102_106_iclaim_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value');

        $data = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.oqueue,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,GROUP_CONCAT(s.`name`) AS other_list,
                IFNULL(SUM(o1.sum_price),0) AS other,v.income-v.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
            LEFT JOIN s_drugitems s ON s.icode = o1.icode	
            WHERE (o.an IS NULL OR o.an ="") 
                AND vp.pttype = ?
                AND o.vstdate BETWEEN ? AND ?
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_106 WHERE vstdate BETWEEN ? AND ?)
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue', [$pttype_iclaim, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }


    //_1102050102_106_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox');
        $checkbox_string = implode(",", $checkbox);

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vstdate, o.vsttime, o.oqueue,o.vn, o.an,o.hn,v.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,
                IFNULL(inc.paid_money,0) AS paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, IFNULL(inc.paid_money,0) - IFNULL(rc.rcpt_money,0) AS debtor,
                rc.rcpno,p2.arrear_date,p2.amount AS arrear_amount,fd.deposit_amount,fd1.debit_amount,"ยืนยันลูกหนี้" AS status
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN patient_arrear p2 ON p2.vn = o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS income,
                SUM(CASE WHEN op.paidst IN ("00","01","03") THEN op.sum_price ELSE 0 END) AS paid_money
                FROM opitemrece op 
                WHERE op.vn IN (' . $checkbox_string . ')
                GROUP BY op.vn) inc ON inc.vn = o.vn
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                AND r.vn IN (' . $checkbox_string . ')
                GROUP BY r.vn) rc ON rc.vn = o.vn       
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN hospcode h ON h.hospcode = vp.hospmain
            WHERE o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn
            ORDER BY o.vstdate, o.oqueue ');

        foreach ($debtor as $row) {
            Debtor_1102050102_106::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'an' => $row->an,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'paid_money' => $row->paid_money,
                'rcpt_money' => $row->rcpt_money,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_106_confirm_iclaim-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_confirm_iclaim(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value');
        $request->validate([
            'checkbox_iclaim' => 'required|array',
        ], [
            'checkbox_iclaim.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_iclaim'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.oqueue,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,o.vsttime,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.paid_money,v.rcpt_money,
                GROUP_CONCAT(s.`name`) AS other_list,IFNULL(SUM(o1.sum_price),0) AS other,
                v.income-v.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor,"ยืนยันลูกหนี้" AS status
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
            LEFT JOIN s_drugitems s ON s.icode = o1.icode	
            WHERE o.vn IN (' . $checkbox_string . ') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue');

        foreach ($debtor as $row) {
            Debtor_1102050102_106::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'an' => $row->an,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'paid_money' => $row->paid_money,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_106_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_106::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_106::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_106_lock(Request $request, $vn)
    {
        Debtor_1102050102_106::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_106_unlock(Request $request, $vn)
    {
        Debtor_1102050102_106::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_106_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_106::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_106_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::connection('hosxp')->select("
            SELECT d.vstdate,COUNT(DISTINCT d.vn) AS anvn, SUM(d.debtor) AS debtor,
                SUM(IF(d.receive IS NOT NULL AND d.receive > 0, d.receive,IFNULL(r.total_amount,0))) AS receive,
                SUM(IFNULL(d.adj_inc,0)) AS adj_inc, SUM(IFNULL(d.adj_dec,0)) AS adj_dec
            FROM hrims.debtor_1102050102_106 d
            LEFT JOIN (SELECT r.vn, r.bill_date, SUM(r.total_amount) AS total_amount,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn, r.bill_date) r ON r.vn = d.vn AND r.bill_date > d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?
            GROUP BY d.vstdate ORDER BY d.vstdate", [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_106_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_106_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_106_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');

        $debtor = DB::connection('hosxp')->select("
            SELECT d.vstdate,d.vsttime,d.hn,d.vn,d.cid,d.ptname,d.mobile_phone_number, d.pttype,d.hospmain,
                d.pdx,d.income,d.paid_money,d.rcpt_money,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.debtor_lock, 
                IF(r.total_amount IS NOT NULL, 'กระทบยอดแล้ว', d.status) AS status,
                d.charge_date,d.charge_no,d.charge,d.receive_date, d.receive_no,  
                IF(d.receive IS NOT NULL AND d.receive > 0, d.receive, IFNULL(r.total_amount,0) - d.rcpt_money) AS receive,
                d.adj_inc, d.adj_dec, d.adj_note, d.adj_date,
                d.repno, r.rcpno, r.total_amount,IFNULL(t.visit,0) AS visit,
                CASE WHEN (IF(d.receive IS NOT NULL AND d.receive > 0, d.receive, IFNULL(r.total_amount,0) - d.rcpt_money)
                + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.05 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
            FROM hrims.debtor_1102050102_106 d
            LEFT JOIN (SELECT r.vn, SUM(r.total_amount) AS total_amount,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL   
                GROUP BY r.vn) r ON r.vn = d.vn
            LEFT JOIN (SELECT vn, COUNT(vn) AS visit  FROM hrims.debtor_1102050102_106_tracking
                GROUP BY vn) t ON t.vn = d.vn
            WHERE d.vstdate BETWEEN ? AND ?
            ORDER BY d.vstdate", [$start_date, $end_date]);

        return view('debtor.1102050102_106_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    //_1102050102_106_tracking-------------------------------------------------------------------------------------------------------   
    public function _1102050102_106_tracking(Request $request, $vn)
    {
        $debtor = DB::select('
            SELECT * FROM debtor_1102050102_106 WHERE vn = ?', [$vn]);

        $tracking = DB::select('
            SELECT * FROM debtor_1102050102_106_tracking WHERE vn = ?', [$vn]);

        return view('debtor.1102050102_106_tracking', compact('debtor', 'tracking'));
    }
    //_1102050102_106_tracking_insert--------------------------------------------------------------------------------------------------
    public function _1102050102_106_tracking_insert(Request $request)
    {
        $item = new Debtor_1102050102_106_tracking;
        $item->vn = $request->input('vn');
        $item->tracking_date = $request->input('tracking_date');
        $item->tracking_type = $request->input('tracking_type');
        $item->tracking_no = $request->input('tracking_no');
        $item->tracking_officer = $request->input('tracking_officer');
        $item->tracking_note = $request->input('tracking_note');
        $item->save();

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //_1102050102_106_tracking_update-------------------------------------------------------------------------------------------------
    public function _1102050102_106_tracking_update(Request $request, $tracking_id)
    {
        Debtor_1102050102_106_tracking::where('tracking_id', $tracking_id)
            ->update([
                'tracking_date' => $request->input('tracking_date'),
                'tracking_type' => $request->input('tracking_type'),
                'tracking_no' => $request->input('tracking_no'),
                'tracking_officer' => $request->input('tracking_officer'),
                'tracking_note' => $request->input('tracking_note')
            ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    ##############################################################################################################################################################
    //_1102050102_108--------------------------------------------------------------------------------------------------------------
    public function _1102050102_108(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        $debtor = Debtor_1102050102_108::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                if (($item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0) - $item->debtor) >= -0.01) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050102_108', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050102_108_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.kidney_price,0) AS kidney,
                IFNULL(total.other_price,0)  AS other,IFNULL(total.ppfs_price,0) AS ppfs, 
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.kidney_list,total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v  ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code IN ("BFC","GOF","PVT","WVO")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_108 WHERE vn IS NOT NULL)
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050102_108_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_108_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.kidney_price,0) AS kidney,
                IFNULL(total.other_price,0)  AS other,IFNULL(total.ppfs_price,0) AS ppfs, 
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)-IFNULL(total.ppfs_price,0) AS debtor,
                total.kidney_list,total.other_list,total.ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v  ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND p.hipdata_code IN ("BFC","GOF","PVT","WVO")
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_108 WHERE vn IS NOT NULL)
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050102_108::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_108_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_108_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_108::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_108::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_108_lock(Request $request, $vn)
    {
        Debtor_1102050102_108::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_108_unlock(Request $request, $vn)
    {
        Debtor_1102050102_108::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_108_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_108_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_108::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_108_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_108_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_108  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_108_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_108_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_108_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050102_108::select('*', DB::raw('receive AS receive_manual'), DB::raw('repno AS repno_manual'))->whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->balance = ($item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0)) - (float)$item->debtor;
                if ($item->balance >= -0.01) {
                    $item->days = 0; 
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        return view('debtor.1102050102_108_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_110--------------------------------------------------------------------------------------------------------------
    public function _1102050102_110(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        if ($search) {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                    d.income,d.rcpt_money, d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.charge_date,d.charge_no,
                    d.charge,d.receive_date,d.receive_no, d.receive AS receive_manual, d.repno AS repno_manual,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,d.repno,stm.repno AS repno_ofc,csop.rid,hd.rid_hd,d.debtor_lock,
                    CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_110 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                    GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                    ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                    FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%'))
                AND d.vstdate BETWEEN ? AND ?", [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                    d.income,d.rcpt_money, d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.charge_date,d.charge_no,
                    d.charge,d.receive_date,d.receive_no, d.receive AS receive_manual, d.repno AS repno_manual,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                    d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,d.repno,stm.repno AS repno_ofc,csop.rid,hd.rid_hd,d.debtor_lock,
                    CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_110 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                    GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                    ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                    FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date]);
        }

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050102_110', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }

    public function _1102050102_110_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(total.kidney_price,0) AS kidney,IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS ofc,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status,
                0 AS receive_manual, 0 AS receive, 0 AS adj_inc, 0 AS adj_dec, NULL AS adj_date, NULL AS adj_note, NULL AS charge_date, NULL AS charge_no, 0 AS charge, NULL AS receive_date, NULL AS receive_no, NULL AS repno
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("BMT","KKT","SRT")         
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_110 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ')   
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050102_110_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_110_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(total.kidney_price,0) AS kidney,IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS ofc,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("BMT","KKT","SRT")         
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_110 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ')   
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050102_110::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'ofc' => $row->ofc,
                'kidney' => $row->kidney,
                'ppfs' => $row->ppfs,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_110_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_110_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_110::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_110::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_110_lock(Request $request, $vn)
    {
        Debtor_1102050102_110::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_110_unlock(Request $request, $vn)
    {
        Debtor_1102050102_110::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_110_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_110_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_110::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_110_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_110_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select("
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,
                SUM(IFNULL(receive,0)+IFNULL(receive_total,0)+IFNULL(csop_amount,0)
                + CASE WHEN kidney > 0 THEN IFNULL(hd_amount,0) ELSE 0 END) AS receive
            FROM (SELECT d.vstdate,d.vn,d.kidney,d.debtor,d.receive,stm.receive_total,
                    csop.amount AS csop_amount,hd.amount AS hd_amount
                FROM debtor_1102050102_110 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total
                    FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(amount) AS amount
                    FROM hrims.stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = d.hn
                    AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount FROM hrims.stm_ofc_csop
                    WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn AND hd.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?) a
            GROUP BY vstdate ORDER BY vstdate", [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_110_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050103_110_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_110_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = DB::select("
            SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                d.income,d.rcpt_money, d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.charge_date,d.charge_no,
                d.charge,d.receive_date,d.receive_no, d.receive AS receive_manual, d.repno AS repno_manual,
                (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,d.repno,stm.repno AS repno_ofc,csop.rid,hd.rid_hd,d.debtor_lock,
                CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
            FROM debtor_1102050102_110 d   
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                GROUP_CONCAT(repno) AS repno FROM stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                GROUP_CONCAT(rid) AS rid FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd
                FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
            WHERE d.vstdate BETWEEN ? AND ?
            AND (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%'))
            ORDER BY d.vstdate", [$start_date, $end_date, $search, $search]);

        return view('debtor.1102050102_110_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_602--------------------------------------------------------------------------------------------------------------
    public function _1102050102_602(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        $debtor = Debtor_1102050102_602::whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->receive_manual = $item->receive; // Original manual value
                $item->repno_manual = $item->repno; // Original manual value
                if (($item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0) - $item->debtor) >= -0.01) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050102_602', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }

    public function _1102050102_602_counts_ajax(Request $request)
    {
        // Method body removed as it is now redundant. 
        // Returning empty counts to prevent errors if frontend still calls it briefly during transition.
        return response()->json(['tab1' => 0, 'tab2' => 0]); 
    }

    public function _1102050102_602_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other,total.other_list,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0) AS debtor,"ยืนยันลูกหนี้" AS status,
                0 AS receive_manual, 0 AS receive, 0 AS adj_inc, 0 AS adj_dec, NULL AS adj_date, NULL AS adj_note,
                NULL AS charge_date, NULL AS charge_no, 0 AS charge, NULL AS receive_date, NULL AS receive_no, NULL AS repno
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0           
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_602 WHERE vn IS NOT NULL)
            AND vp.pttype IN (' . $pttype_act . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050102_602_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.other_price,0) AS other,total.other_list,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0) AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ovst o  
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.ems = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0           
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_602 WHERE vn IS NOT NULL)
            AND vp.pttype IN (' . $pttype_act . ')
            AND o.vn IN (' . $checkbox_string . ') 
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050102_602::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_602_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_602::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_602::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_602_lock(Request $request, $vn)
    {
        Debtor_1102050102_602::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_602_unlock(Request $request, $vn)
    {
        Debtor_1102050102_602::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_602_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_602::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_602_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_602  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_602_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_602_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_602_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = Debtor_1102050102_602::whereBetween('vstdate', [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname', 'like', '%' . $search . '%');
                $query->orwhere('hn', 'like', '%' . $search . '%');
            })
            ->orderBy('vstdate')->get()
            ->map(function ($item) {
                $item->receive_manual = $item->receive; 
                $item->repno_manual = $item->repno; 
                if (($item->receive + ($item->adj_inc ?? 0) - ($item->adj_dec ?? 0) - $item->debtor) >= -0.01) {
                    $item->days = 0; 
                } else {
                    $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        return view('debtor.1102050102_602_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_801--------------------------------------------------------------------------------------------------------------
    public function _1102050102_801(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        if ($search) {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,
                    d.rcpt_money,d.lgo,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,d.charge,d.receive_date,
                    d.receive_no,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,IFNULL(s.compensate_treatment,0) AS receive_lgo,
                    CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END AS receive_kidney,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,IFNULL(d.receive,0) + IFNULL(s.compensate_treatment,0)
                    + CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END AS receive,
                    d.status,s.repno,sk.repno AS rid,d.debtor_lock,
                    IFNULL(s.round_no, sk.round_no) AS stm_round_no,
                    IFNULL(s.receipt_date, sk.receipt_date) AS stm_receipt_date,
                    IFNULL(s.receive_no, sk.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(s.compensate_treatment,0)
                    + CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END) 
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_801 d  
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                    GROUP_CONCAT(DISTINCT NULLIF(repno,'')) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_lgo GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,SUM(compensate_kidney) AS receive_total, GROUP_CONCAT(DISTINCT NULLIF(repno,'')) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_lgo_kidney WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid, datetimeadm) sk ON sk.cid = d.cid AND sk.vstdate = d.vstdate
                LEFT JOIN (SELECT hn,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp FROM stm_ucs
                    GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%'))
                AND d.vstdate BETWEEN ? AND ?", [$start_date, $end_date, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,
                    d.rcpt_money,d.lgo,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,d.charge,d.receive_date,
                    d.receive_no,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,IFNULL(s.compensate_treatment,0) AS receive_lgo,
                    CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END AS receive_kidney,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,IFNULL(d.receive,0) + IFNULL(s.compensate_treatment,0)
                    + CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END AS receive,
                    d.status,s.repno,sk.repno AS rid,d.debtor_lock,
                    IFNULL(s.round_no, sk.round_no) AS stm_round_no,
                    IFNULL(s.receipt_date, sk.receipt_date) AS stm_receipt_date,
                    IFNULL(s.receive_no, sk.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(s.compensate_treatment,0)
                    + CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END) 
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_801 d   
                LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                    GROUP_CONCAT(DISTINCT NULLIF(repno,'')) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_lgo GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid
                    AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,SUM(compensate_kidney) AS receive_total, GROUP_CONCAT(DISTINCT NULLIF(repno,'')) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_lgo_kidney WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid, datetimeadm) sk ON sk.cid = d.cid AND sk.vstdate = d.vstdate
                LEFT JOIN (SELECT hn,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp FROM stm_ucs
                    GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date, $start_date, $end_date]);
        }

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050102_801', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050102_801_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.kidney_price,0) AS kidney,
                IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS lgo,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO"            
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_801 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }

    //_1102050102_801_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,
                IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(total.kidney_price,0) AS kidney,
                IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS lgo,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO"            
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_801 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ')
            AND o.vn IN (' . $checkbox_string . ')
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050102_801::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'lgo' => $row->lgo,
                'kidney' => $row->kidney,
                'ppfs' => $row->ppfs,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }

    //_1102050102_801_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_801::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_801::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_801_lock(Request $request, $vn)
    {
        Debtor_1102050102_801::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_801_unlock(Request $request, $vn)
    {
        Debtor_1102050102_801::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_801_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_801::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_801_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select("
            SELECT a.vstdate,COUNT(DISTINCT a.vn) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive) AS receive
                FROM (SELECT d.vn,d.vstdate,d.debtor,IFNULL(s.compensate_treatment,0)+CASE WHEN d.kidney > 0
                THEN IFNULL(k.receive_total,0) ELSE 0 END AS receive
            FROM debtor_1102050102_801 d   
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment
                FROM stm_lgo GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid
                AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT cid,datetimeadm AS vstdate,SUM(compensate_kidney) AS receive_total
                FROM stm_lgo_kidney WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid, datetimeadm ) k ON k.cid = d.cid AND k.vstdate = d.vstdate 
            WHERE d.vstdate BETWEEN ? AND ?) a
            GROUP BY a.vstdate ORDER BY a.vstdate", [$start_date, $end_date, $start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_801_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_801_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_801_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = DB::select("
            SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,
                d.rcpt_money,d.lgo,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,d.charge,d.receive_date,
                d.receive_no,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,IFNULL(s.compensate_treatment,0) AS receive_lgo,
                CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END AS receive_kidney,
                IFNULL(su.receive_pp,0) AS receive_ppfs,IFNULL(d.receive,0) + IFNULL(s.compensate_treatment,0)
                + CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END AS receive,
                d.status,s.repno,sk.repno AS rid,d.debtor_lock,
                IFNULL(s.round_no, sk.round_no) AS stm_round_no,
                IFNULL(s.receipt_date, sk.receipt_date) AS stm_receipt_date,
                IFNULL(s.receive_no, sk.receive_no) AS stm_receive_no,
                CASE WHEN (IFNULL(d.receive,0) + IFNULL(s.compensate_treatment,0)
                + CASE WHEN d.kidney > 0 THEN IFNULL(sk.receive_total,0) ELSE 0 END) 
                + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
            FROM debtor_1102050102_801 d  
            LEFT JOIN (SELECT cid,vstdate,LEFT(vsttime,5) AS vsttime5,SUM(compensate_treatment) AS compensate_treatment,
                GROUP_CONCAT(DISTINCT NULLIF(repno,'')) AS repno,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_lgo GROUP BY cid, vstdate, LEFT(vsttime,5)) s ON s.cid = d.cid
                AND s.vstdate = d.vstdate AND s.vsttime5 = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT cid,datetimeadm AS vstdate,SUM(compensate_kidney) AS receive_total, GROUP_CONCAT(DISTINCT NULLIF(repno,'')) AS repno,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_lgo_kidney WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid, datetimeadm) sk ON sk.cid = d.cid AND sk.vstdate = d.vstdate
            LEFT JOIN (SELECT hn,vstdate,LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp FROM stm_ucs
                GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn
                AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
            WHERE (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%'))
            AND d.vstdate BETWEEN ? AND ?", [$start_date, $end_date, $search, $search, $start_date, $end_date]);

        return view('debtor.1102050102_801_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_803--------------------------------------------------------------------------------------------------------------
    public function _1102050102_803(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        if ($search) {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                    d.income,d.rcpt_money, d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,
                    d.charge,d.receive_date,d.receive_no,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,(IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,d.repno,stm.repno AS repno_ofc,csop.rid,hd.rid_hd,d.debtor_lock,
                    CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_803 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc WHERE vstdate BETWEEN ? AND ? GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                    GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                    ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                    FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%'))
                AND d.vstdate BETWEEN ? AND ?", [$start_date, $end_date, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select("
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                    d.income,d.rcpt_money, d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,
                    d.charge,d.receive_date,d.receive_no,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,(IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,d.repno,stm.repno AS repno_ofc,csop.rid,hd.rid_hd,d.debtor_lock,
                    CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_803 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                    GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc WHERE vstdate BETWEEN ? AND ? GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                    GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                    ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                    FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                    AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
                WHERE d.vstdate BETWEEN ? AND ?", [$start_date, $end_date, $start_date, $end_date]);
        }

        $debtor_search = [];
        $count_tab1 = count($debtor);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // Removed session('debtor')
        $request->session()->save();

        return view('debtor.1102050102_803', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'count_tab1'));
    }


    public function _1102050102_803_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(total.kidney_price,0) AS kidney,IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS ofc,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("BKK","PTY")        
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_803 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ')   
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050102_803_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,IFNULL(total.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,
                IFNULL(total.kidney_price,0) AS kidney,IFNULL(total.ppfs_price,0) AS ppfs,IFNULL(total.other_price,0)  AS other,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.kidney_price,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS ofc,
                IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.ppfs_price,0)-IFNULL(total.other_price,0) AS debtor,
                total.kidney_list,total.ppfs_list,total.other_list,oe.upload_datetime AS claim,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN (
                SELECT 
                    op.vn, op.pttype,
                    SUM(op.sum_price) AS income,
                    SUM(CASE WHEN li.kidney = "Y" THEN op.sum_price ELSE 0 END) AS kidney_price,
                    SUM(CASE WHEN li.ppfs   = "Y" THEN op.sum_price ELSE 0 END) AS ppfs_price,
                    SUM(CASE WHEN li.ems    = "Y" THEN op.sum_price ELSE 0 END) AS other_price,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.kidney = "Y" THEN sd.`name` END) AS kidney_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ppfs   = "Y" THEN sd.`name` END) AS ppfs_list,
                    GROUP_CONCAT(DISTINCT CASE WHEN li.ems    = "Y" THEN sd.`name` END) AS other_list
                FROM opitemrece op
                LEFT JOIN hrims.lookup_icode li ON li.icode = op.icode
                LEFT JOIN s_drugitems sd ON sd.icode = op.icode
                WHERE op.vstdate BETWEEN ? AND ?
                GROUP BY op.vn, op.pttype
            ) total ON total.vn = o.vn AND total.pttype = vp.pttype
            LEFT JOIN (
                SELECT r.vn,SUM( r.total_amount ) AS rcpt_money,
                GROUP_CONCAT( r.rcpno ORDER BY r.rcpno ) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL
                GROUP BY r.vn
            ) rc ON rc.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
            WHERE (o.an IS NULL OR o.an = "")
            AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("BKK","PTY")        
            AND (IFNULL(total.income,0)-IFNULL(rc.rcpt_money,0)-IFNULL(total.other_price,0)) > 0
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_803 WHERE vn IS NOT NULL)
            AND p.pttype NOT IN (' . $pttype_checkup . ') 
            AND o.vn IN (' . $checkbox_string . ')  
            GROUP BY o.vn, vp.pttype
            ORDER BY o.vstdate, o.oqueue', [$start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050102_803::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'vstdate' => $row->vstdate,
                'vsttime' => $row->vsttime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'ofc' => $row->ofc,
                'kidney' => $row->kidney,
                'ppfs' => $row->ppfs,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_803_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_803::whereIn('vn', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('vn')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_803::whereIn('vn', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_803_lock(Request $request, $vn)
    {
        Debtor_1102050102_803::where('vn', $vn)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_803_unlock(Request $request, $vn)
    {
        Debtor_1102050102_803::where('vn', $vn)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_803_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_803::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_803_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select("
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,
                SUM(IFNULL(receive,0)+IFNULL(receive_total,0)+IFNULL(csop_amount,0)
                + CASE WHEN kidney > 0 THEN IFNULL(hd_amount,0) ELSE 0 END) AS receive
            FROM (SELECT d.vstdate,d.vn,d.kidney,d.debtor,d.receive,stm.receive_total,
                    csop.amount AS csop_amount,hd.amount AS hd_amount
                FROM debtor_1102050102_803 d   
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(receive_total) AS receive_total
                    FROM hrims.stm_ofc GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                    AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime,SUM(amount) AS amount
                    FROM hrims.stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop ON csop.hn = d.hn
                    AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
                LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount FROM hrims.stm_ofc_csop
                    WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn AND hd.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?) a
            GROUP BY vstdate ORDER BY vstdate", [$start_date, $end_date,]);

        $pdf = PDF::loadView('debtor.1102050102_803_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050103_803_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_803_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $search = Session::get('search');

        $debtor = DB::select("
            SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.cid,d.ptname,d.hipdata_code, d.pttype,d.hospmain,d.pdx,
                d.income,d.rcpt_money, d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.receive AS receive_manual, d.repno AS repno_manual,d.charge_date,d.charge_no,
                d.charge,d.receive_date,d.receive_no,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,(IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                + IFNULL(csop.amount,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END ) AS receive,
                IFNULL(su.receive_pp,0) AS receive_ppfs,d.status,d.repno,stm.repno AS repno_ofc,csop.rid,hd.rid_hd,d.debtor_lock,
                CONCAT_WS(CHAR(44), stm.round_no, csop.round_no, hd.round_no) AS stm_round_no,
                CONCAT_WS(CHAR(44), stm.receipt_date, csop.receipt_date, hd.receipt_date) AS stm_receipt_date,
                CONCAT_WS(CHAR(44), stm.receive_no, csop.receive_no, hd.receive_no) AS stm_receive_no,
                CASE WHEN (IFNULL(d.receive,0)+IFNULL(stm.receive_total,0)+ IFNULL(csop.amount,0)
                + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) + CASE WHEN d.kidney > 0 THEN IFNULL(hd.amount,0) ELSE 0 END) >= IFNULL(d.debtor,0) - 0.01
                THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
            FROM debtor_1102050102_803 d   
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(receive_total) AS receive_total,
                GROUP_CONCAT(repno) AS repno,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_ofc WHERE vstdate BETWEEN ? AND ? GROUP BY hn, vstdate, LEFT(vsttime,5)) stm ON stm.hn = d.hn
                AND stm.vstdate = d.vstdate AND stm.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime, SUM(amount) AS amount,
                GROUP_CONCAT(rid) AS rid,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_ofc_csop WHERE sys <> 'HD' GROUP BY hn, vstdate, LEFT(vsttime,5)) csop 
                ON csop.hn = d.hn  AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime,5)
            LEFT JOIN (SELECT hn, vstdate,SUM(amount) AS amount, GROUP_CONCAT(rid) AS rid_hd,
                GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                FROM stm_ofc_csop WHERE sys = 'HD' GROUP BY hn, vstdate) hd ON hd.hn = d.hn  AND hd.vstdate = d.vstdate
            LEFT JOIN (SELECT hn, vstdate, LEFT(vsttime,5) AS vsttime5, SUM(receive_pp) AS receive_pp 
                FROM stm_ucs GROUP BY hn, vstdate, LEFT(vsttime,5)) su ON su.hn = d.hn 
                AND su.vstdate = d.vstdate AND su.vsttime5 = LEFT(d.vsttime,5)
            WHERE (d.ptname LIKE CONCAT('%', ?, '%') OR d.hn LIKE CONCAT('%', ?, '%'))
            AND d.vstdate BETWEEN ? AND ?", [$start_date, $end_date, $search, $search, $start_date, $end_date]);

        return view('debtor.1102050102_803_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_202--------------------------------------------------------------------------------------------------------------
    public function _1102050101_202(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno, stm.round_no AS stm_round_no, stm.receipt_date AS stm_receipt_date, stm.receive_no AS stm_receive_no,
                CASE WHEN (IFNULL(stm.receive_total,0) + IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0
                   ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_202 d
                LEFT JOIN ( SELECT an,MAX(fund_ip_payrate) AS fund_ip_payrate,SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                    SUM(receive_total) AS receive_total,MAX(repno) AS repno, GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs GROUP BY an) stm ON stm.an = d.an
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$search, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno, stm.round_no AS stm_round_no, stm.receipt_date AS stm_receipt_date, stm.receive_no AS stm_receive_no,
                CASE WHEN (IFNULL(stm.receive_total,0) + IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0
                   ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_202 d
                LEFT JOIN ( SELECT an,MAX(fund_ip_payrate) AS fund_ip_payrate,SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                    SUM(receive_total) AS receive_total,MAX(repno) AS repno, GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM stm_ucs GROUP BY an) stm ON stm.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$start_date, $end_date]);
        }

        $debtor_search = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_202', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_202_counts_ajax-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_counts_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $tab1 = DB::table('debtor_1102050101_202')
            ->whereBetween('dchdate', [$start_date, $end_date])
            ->count('an');

        $tab2 = DB::connection('hosxp')->select('
            SELECT COUNT(DISTINCT i.an) AS total
            FROM ipt i
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("UCS","WEL") 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_202 WHERE an IS NOT NULL)', [$start_date, $end_date]);

        return response()->json([
            'tab1' => $tab1,
            'tab2' => $tab2[0]->total ?? 0
        ]);
    }
    //_1102050101_202_search_ajax-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_search_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $data = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(cr.cr_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(cr.cr_price,0) AS debtor,cr.cr_list AS other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS cr_price,GROUP_CONCAT(DISTINCT COALESCE(s.name, n.name)) AS cr_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode 
                LEFT JOIN nondrugitems n ON n.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode 
                WHERE (li.uc_cr = "Y" OR li.kidney = "Y" OR n.nhso_adp_code IN ("S1801","S1802"))
            GROUP BY o.an, o.pttype) cr ON cr.an = i.an AND cr.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("UCS","WEL") 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_202 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }
    //_1102050101_202_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(cr.cr_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(cr.cr_price,0) AS debtor,cr.cr_list AS other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS cr_price,GROUP_CONCAT(DISTINCT COALESCE(s.name, n.name)) AS cr_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode 
                LEFT JOIN nondrugitems n ON n.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode 
                WHERE (li.uc_cr = "Y" OR li.kidney = "Y" OR n.nhso_adp_code IN ("S1801","S1802"))
                GROUP BY o.an, o.pttype) cr ON cr.an = i.an AND cr.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("UCS","WEL") 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_202 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_202::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_202_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_202::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_202::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_202_lock(Request $request, $an)
    {
        Debtor_1102050101_202::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_202_unlock(Request $request, $an)
    {
        Debtor_1102050101_202::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_202_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,
            SUM(debtor) AS debtor,SUM(receive_ip_compensate_pay) AS receive
            FROM (SELECT d.dchdate,d.an,d.debtor,stm.receive_ip_compensate_pay FROM debtor_1102050101_202 d
            LEFT JOIN ( SELECT an,MAX(fund_ip_payrate) AS fund_ip_payrate,SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,
                SUM(receive_total) AS receive_total,MAX(repno) AS repno
                FROM stm_ucs GROUP BY an) stm ON stm.an = d.an    
            WHERE d.dchdate BETWEEN ? AND ?
            GROUP BY d.an) AS a
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_202_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_202_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_202_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_202_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_217--------------------------------------------------------------------------------------------------------------
    public function _1102050101_217(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*,d.receive AS receive_manual, d.repno AS repno_manual, (IFNULL(stm.receive_total,0)-IFNULL(stm.receive_ip_compensate_pay,0))+IFNULL(k.receive_total,0) AS receive,
                    stm.repno,k.repno AS repno_kidney, CONCAT_WS(CHAR(44), stm.round_no, k.round_no) AS stm_round_no, CONCAT_WS(CHAR(44), stm.receipt_date, k.receipt_date) AS stm_receipt_date, CONCAT_WS(CHAR(44), stm.receive_no, k.receive_no) AS stm_receive_no, CASE WHEN ((IFNULL(stm.receive_total,0) - IFNULL(stm.receive_ip_compensate_pay,0))
                    + IFNULL(k.receive_total,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_217 d
                LEFT JOIN (SELECT an,MAX(fund_ip_payrate) AS fund_ip_payrate,SUM(receive_total) AS receive_total,
                    SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,MAX(repno) AS repno, GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no FROM stm_ucs
                    GROUP BY an) stm  ON stm.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(sk.receive_total) AS receive_total,MAX(sk.repno) AS repno, MAX(sk.round_no) AS round_no, MAX(sk.receipt_date) AS receipt_date, MAX(sk.receive_no) AS receive_no FROM debtor_1102050101_217 d2
                    JOIN stm_ucs_kidney sk ON sk.cid = d2.cid AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                    WHERE d2.dchdate BETWEEN ? AND ? GROUP BY d2.an) k ON k.an = d.an
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?', [$start_date, $end_date, $search, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.*,d.receive AS receive_manual, d.repno AS repno_manual, (IFNULL(stm.receive_total,0)-IFNULL(stm.receive_ip_compensate_pay,0))+IFNULL(k.receive_total,0) AS receive,
                    stm.repno,k.repno AS repno_kidney, CONCAT_WS(CHAR(44), stm.round_no, k.round_no) AS stm_round_no, CONCAT_WS(CHAR(44), stm.receipt_date, k.receipt_date) AS stm_receipt_date, CONCAT_WS(CHAR(44), stm.receive_no, k.receive_no) AS stm_receive_no, CASE WHEN ((IFNULL(stm.receive_total,0) - IFNULL(stm.receive_ip_compensate_pay,0))
                    + IFNULL(k.receive_total,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_217 d
                LEFT JOIN (SELECT an,MAX(fund_ip_payrate) AS fund_ip_payrate,SUM(receive_total) AS receive_total,
                    SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay,MAX(repno) AS repno, GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no FROM stm_ucs
                    GROUP BY an) stm  ON stm.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(sk.receive_total) AS receive_total,MAX(sk.repno) AS repno, MAX(sk.round_no) AS round_no, MAX(sk.receipt_date) AS receipt_date, MAX(sk.receive_no) AS receive_no FROM debtor_1102050101_217 d2
                    JOIN stm_ucs_kidney sk ON sk.cid = d2.cid AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                    WHERE d2.dchdate BETWEEN ? AND ? GROUP BY d2.an) k ON k.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an', [$start_date, $end_date, $start_date, $end_date]);
        }

        $debtor_search = []; // Lazy load

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_217', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }

    public function _1102050101_217_counts_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $tab1 = DB::table('debtor_1102050101_217')
            ->whereBetween('dchdate', [$start_date, $end_date])
            ->count();

        $tab2 = DB::connection('hosxp')->table('ipt as i')
            ->join('ipt_pttype as ip', 'ip.an', '=', 'i.an')
            ->join('pttype as p', 'p.pttype', '=', 'ip.pttype')
            ->join('opitemrece as o', 'o.an', '=', 'i.an')
            ->leftJoin('hrims.lookup_icode as li', 'li.icode', '=', 'o.icode')
            ->leftJoin('nondrugitems as n', 'n.icode', '=', 'o.icode')
            ->where('i.confirm_discharge', 'Y')
            ->whereIn('p.hipdata_code', ['UCS', 'WEL'])
            ->whereBetween('i.dchdate', [$start_date, $end_date])
            ->whereRaw('(li.uc_cr = "Y" OR li.kidney = "Y" OR n.nhso_adp_code IN ("S1801","S1802"))')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('hrims.debtor_1102050101_217')
                    ->whereRaw('hrims.debtor_1102050101_217.an = i.an');
            })
            ->distinct('i.an')
            ->count('i.an');

        return response()->json([
            'tab1' => $tab1,
            'tab2' => $tab2
        ]);
    }

    public function _1102050101_217_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                IFNULL(inc.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(cr.cr_price,0) AS cr,
                IFNULL(cr.cr_price,0) AS debtor,cr.cr_list,ict.ipt_coll_status_type_name,i.data_ok
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an 
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income   
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            INNER JOIN (SELECT o.an,SUM(o.sum_price) AS cr_price,GROUP_CONCAT(DISTINCT COALESCE(s.name, n.name)) AS cr_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.confirm_discharge = "Y" AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN nondrugitems n ON n.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE (li.uc_cr = "Y" OR li.kidney = "Y" OR n.nhso_adp_code IN ("S1801","S1802"))
                GROUP BY o.an) cr ON cr.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("UCS","WEL")
            AND i.dchdate BETWEEN ? AND ?            
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_217 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($debtor_search);
    }
    //_1102050101_217_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_217_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(',', array_fill(0, count($checkbox), '?')); // สร้าง placeholders (?,?,?)
        $params = array_merge([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date], $checkbox); // รวม parameters

        $debtor = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                IFNULL(inc.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(cr.cr_price,0) AS cr,
                IFNULL(cr.cr_price,0) AS debtor,cr.cr_list,ict.ipt_coll_status_type_name,i.data_ok
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an 
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income   
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            INNER JOIN (SELECT o.an,SUM(o.sum_price) AS cr_price,GROUP_CONCAT(DISTINCT COALESCE(s.name, n.name)) AS cr_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.confirm_discharge = "Y" AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN nondrugitems n ON n.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE (li.uc_cr = "Y" OR li.kidney = "Y" OR n.nhso_adp_code IN ("S1801","S1802"))
                GROUP BY o.an) cr ON cr.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("UCS","WEL")
            AND i.dchdate BETWEEN ? AND ?            
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_217 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate', $params);

        foreach ($debtor as $row) {
            Debtor_1102050101_217::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'cr' => $row->cr,
                'debtor' => $row->debtor,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_217_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_217_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_217::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_217::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_217_lock(Request $request, $an)
    {
        Debtor_1102050101_217::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_217_unlock(Request $request, $an)
    {
        Debtor_1102050101_217::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_217_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_217_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT a.dchdate AS vstdate,COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive) AS receive
            FROM (SELECT d.an,d.dchdate,d.debtor,(IFNULL(stm.receive_total,0) - IFNULL(stm.receive_ip_compensate_pay,0))
                    + IFNULL(k.receive_total,0) AS receive
                FROM debtor_1102050101_217 d
                LEFT JOIN (SELECT an,SUM(receive_total) AS receive_total,SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay
                    FROM stm_ucs GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(sk.receive_total) AS receive_total FROM debtor_1102050101_217 d2
                    JOIN stm_ucs_kidney sk ON sk.cid = d2.cid AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                    WHERE d2.dchdate BETWEEN ? AND ? GROUP BY d2.an) k ON k.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?) a
                GROUP BY a.dchdate ORDER BY a.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_217_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_217_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_217_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_217_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_302--------------------------------------------------------------------------------------------------------------
    public function _1102050101_302(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_302 d
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_302 d
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                    ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "SSS"
            AND i.dchdate BETWEEN ? AND ?
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_302 WHERE an IS NOT NULL)
            AND ip.pttype NOT IN (' . $pttype_sss_fund . ')
			AND ip.pttype NOT IN (' . $pttype_sss_72 . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_302', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_302_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "SSS"
            AND i.dchdate BETWEEN ? AND ?
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_302 WHERE an IS NOT NULL)
            AND ip.pttype NOT IN (' . $pttype_sss_fund . ')
			AND ip.pttype NOT IN (' . $pttype_sss_72 . ') 
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050101_302::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_302_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_302::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_302::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_302_lock(Request $request, $an)
    {
        Debtor_1102050101_302::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_302_unlock(Request $request, $an)
    {
        Debtor_1102050101_302::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_302_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_302::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_302_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_302    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_302_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_302_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_302_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_302_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_304--------------------------------------------------------------------------------------------------------------
    public function _1102050101_304(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       d.receive AS receive_manual,
                       d.repno AS repno_manual,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_304 d
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       d.receive AS receive_manual,
                       d.repno AS repno_manual,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_304 d
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "SSS"
            AND i.dchdate BETWEEN ? AND ?
            AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_304 WHERE an IS NOT NULL)
            AND ip.pttype NOT IN (' . $pttype_sss_fund . ')
			AND ip.pttype NOT IN (' . $pttype_sss_72 . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_304', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_304_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "SSS"
            AND i.dchdate BETWEEN ? AND ?
            AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss = "Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_304 WHERE an IS NOT NULL)
            AND ip.pttype NOT IN (' . $pttype_sss_fund . ')
			AND ip.pttype NOT IN (' . $pttype_sss_72 . ') 
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050101_304::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_304_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_304::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_304::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_304_lock(Request $request, $an)
    {
        Debtor_1102050101_304::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_304_unlock(Request $request, $an)
    {
        Debtor_1102050101_304::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_304_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_304::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_304_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_304    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_304_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_304_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_304_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_304_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_308--------------------------------------------------------------------------------------------------------------
    public function _1102050101_308(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_308 d
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_308 d
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "SSS"
            AND i.dchdate BETWEEN ? AND ?            
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_308 WHERE an IS NOT NULL)
			AND ip.pttype IN (' . $pttype_sss_72 . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_308', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_308_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                    ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "SSS"
            AND i.dchdate BETWEEN ? AND ?            
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_308 WHERE an IS NOT NULL)
			AND ip.pttype IN (' . $pttype_sss_72 . ') 
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050101_308::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_308_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_308::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_308::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_308_lock(Request $request, $an)
    {
        Debtor_1102050101_308::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_308_unlock(Request $request, $an)
    {
        Debtor_1102050101_308::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_308_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_308::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_308_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_308    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_308_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_308_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_308_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_308_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_310--------------------------------------------------------------------------------------------------------------
    public function _1102050101_310(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                       (IFNULL(d.receive,0) + IFNULL(stm.stm_receive,0)) AS receive,
                       stm.stm_round_no, stm.stm_receipt_date, stm.stm_receive_no,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.stm_receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_310 d
                LEFT JOIN (
                    SELECT d2.an, SUM(IFNULL(s.amount,0) + IFNULL(s.epopay,0) + IFNULL(s.epoadm,0)) AS stm_receive,
                           GROUP_CONCAT(DISTINCT s.round_no) AS stm_round_no,
                           GROUP_CONCAT(DISTINCT s.receipt_date) AS stm_receipt_date,
                           GROUP_CONCAT(DISTINCT s.receive_no) AS stm_receive_no
                    FROM debtor_1102050101_310 d2
                    JOIN stm_sss_kidney s ON s.hn = d2.hn AND s.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an
                ) stm ON stm.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                       (IFNULL(d.receive,0) + IFNULL(stm.stm_receive,0)) AS receive,
                       stm.stm_round_no, stm.stm_receipt_date, stm.stm_receive_no,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.stm_receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_310 d
                LEFT JOIN (
                    SELECT d2.an, SUM(IFNULL(s.amount,0) + IFNULL(s.epopay,0) + IFNULL(s.epoadm,0)) AS stm_receive,
                           GROUP_CONCAT(DISTINCT s.round_no) AS stm_round_no,
                           GROUP_CONCAT(DISTINCT s.receipt_date) AS stm_receipt_date,
                           GROUP_CONCAT(DISTINCT s.receive_no) AS stm_receive_no
                    FROM debtor_1102050101_310 d2
                    JOIN stm_sss_kidney s ON s.hn = d2.hn AND s.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an
                ) stm ON stm.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                IFNULL(inc.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(kid.kidney_price,0) AS kidney,
                IFNULL(kid.kidney_price,0) AS debtor,kid.kidney_list,ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an 
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income   
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            INNER JOIN (SELECT op.an,SUM(op.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list
                FROM opitemrece op
                INNER JOIN ipt i4 ON i4.an = op.an AND i4.confirm_discharge = "Y" AND i4.dchdate BETWEEN ? AND ?
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.kidney = "Y"
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                GROUP BY op.an) kid ON kid.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("SSS","SSI")
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_310 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype 
            ORDER BY i.ward, i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_310', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_310_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                IFNULL(inc.income,0) AS income,IFNULL(rc.rcpt_money,0) AS rcpt_money,IFNULL(kid.kidney_price,0) AS kidney,
                IFNULL(kid.kidney_price,0) AS debtor,kid.kidney_list,ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an 
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income   
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            INNER JOIN (SELECT op.an,SUM(op.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list
                FROM opitemrece op
                INNER JOIN ipt i4 ON i4.an = op.an AND i4.confirm_discharge = "Y" AND i4.dchdate BETWEEN ? AND ?
                INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND li.kidney = "Y"
                LEFT JOIN s_drugitems s ON s.icode = op.icode
                GROUP BY op.an) kid ON kid.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("SSS","SSI")
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_310 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ')
            GROUP BY i.an, ip.pttype 
            ORDER BY i.ward, i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_310::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'kidney' => $row->kidney,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_310_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_310::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_310::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_310_lock(Request $request, $an)
    {
        Debtor_1102050101_310::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_310_unlock(Request $request, $an)
    {
        Debtor_1102050101_310::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_310_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_310::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_310_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT a.dchdate AS vstdate,COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (
                SELECT d.dchdate, d.an, MAX(d.debtor) AS debtor, (IFNULL(MAX(d.receive),0) + IFNULL(stm.stm_receive,0)) AS receive_total
                FROM debtor_1102050101_310 d
                LEFT JOIN (
                    SELECT d2.an, SUM(IFNULL(s.amount,0) + IFNULL(s.epopay,0) + IFNULL(s.epoadm,0)) AS stm_receive
                    FROM debtor_1102050101_310 d2
                    JOIN stm_sss_kidney s ON s.hn = d2.hn AND s.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an
                ) stm ON stm.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?
                GROUP BY d.dchdate, d.an
            ) a
            GROUP BY a.dchdate ORDER BY a.dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_310_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_310_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_310_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_310_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_402--------------------------------------------------------------------------------------------------------------
    public function _1102050101_402(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.cid,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,d.status,
                    d.receive AS receive_manual,d.repno AS repno_manual,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 THEN IFNULL(csop.amount,0) ELSE 0 END) AS receive,
                    stm.repno,cipn.rid AS cipn_rid,csop.rid AS csop_rid, 
                    CONCAT_WS(CHAR(44), stm.round_no, cipn.round_no, csop.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, cipn.receipt_date, csop.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, cipn.receive_no, csop.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 THEN IFNULL(csop.amount,0) ELSE 0 END
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0)) >= IFNULL(d.debtor,0) - 0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM hrims.debtor_1102050101_402 d  
                LEFT JOIN (SELECT an,SUM(receive_total) AS receive_total,GROUP_CONCAT(repno) AS repno, 
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an,SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount,GROUP_CONCAT(c.rid) AS rid,
                    MAX(c.round_no) AS round_no, MAX(c.receipt_date) AS receipt_date, MAX(c.receive_no) AS receive_no 
                    FROM hrims.debtor_1102050101_402 d2 
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an 
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?', [$search, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.cid,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,d.status,
                    d.receive AS receive_manual,d.repno AS repno_manual,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 THEN IFNULL(csop.amount,0) ELSE 0 END) AS receive,
                    stm.repno,cipn.rid AS cipn_rid,csop.rid AS csop_rid, 
                    CONCAT_WS(CHAR(44), stm.round_no, cipn.round_no, csop.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, cipn.receipt_date, csop.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, cipn.receive_no, csop.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0)
                    + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 THEN IFNULL(csop.amount,0) ELSE 0 END
                    + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0)) >= IFNULL(d.debtor,0) - 0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM hrims.debtor_1102050101_402 d    
                LEFT JOIN (SELECT an,SUM(receive_total) AS receive_total,GROUP_CONCAT(repno) AS repno, 
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an,SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid, 
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount,GROUP_CONCAT(c.rid) AS rid,
                    MAX(c.round_no) AS round_no, MAX(c.receipt_date) AS receipt_date, MAX(c.receive_no) AS receive_no 
                    FROM hrims.debtor_1102050101_402 d2 
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an 
                WHERE d.dchdate BETWEEN ? AND ? ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "OFC"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_402 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_402', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_402_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_402_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "OFC"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_402 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_402::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'kidney' => $row->kidney,
                'debtor' => $row->debtor,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_402_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_402_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_402::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_402::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_402_lock(Request $request, $an)
    {
        Debtor_1102050101_402::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_402_unlock(Request $request, $an)
    {
        Debtor_1102050101_402::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050101_402_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_402_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT a.dchdate AS vstdate,COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (SELECT d.dchdate,d.an, MAX(d.debtor) AS debtor, IFNULL(stm.receive_total,0)
                    + IFNULL(cipn.gtotal,0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(csop.amount,0) ELSE 0 END AS receive_total
                FROM debtor_1102050101_402 d    
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total
                    FROM stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount FROM debtor_1102050101_402 d2
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.dchdate, d.an) a
                GROUP BY a.dchdate ORDER BY a.dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_402_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_402_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_402_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_402_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_502--------------------------------------------------------------------------------------------------------------
    public function _1102050101_502(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_502 d
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_502 d
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "NRH"
            AND i.dchdate BETWEEN ? AND ?
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_502 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype
            ', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_502', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_502_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "NRH"
            AND i.dchdate BETWEEN ? AND ?
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_502 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype
            ', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_502::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_502_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_502::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_502::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_502_lock(Request $request, $an)
    {
        Debtor_1102050101_502::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_502_unlock(Request $request, $an)
    {
        Debtor_1102050101_502::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_502_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_502::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_502_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_502    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_502_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_502_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_502_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_502_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_504--------------------------------------------------------------------------------------------------------------
    public function _1102050101_504(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_504 d
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_504 d
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "NRH"
            AND i.dchdate BETWEEN ? AND ?
            AND (ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                OR ip.hospmain IS NULL OR ip.hospmain ="")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_504 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype
            ', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_504', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_504_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "NRH"
            AND i.dchdate BETWEEN ? AND ?
            AND (ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                OR ip.hospmain IS NULL OR ip.hospmain ="")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_504 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype
            ', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        foreach ($debtor as $row) {
            Debtor_1102050101_504::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_504_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_504::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_504::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_504_lock(Request $request, $an)
    {
        Debtor_1102050101_504::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_504_unlock(Request $request, $an)
    {
        Debtor_1102050101_504::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_504_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_504::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_504_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_504   
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_504_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_504_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_504_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_504_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050101_704--------------------------------------------------------------------------------------------------------------
    public function _1102050101_704(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_704 d
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, 
                       IFNULL(d.receive,0) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc, 0) - IFNULL(d.adj_dec, 0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_704 d
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "STP"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_704 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050101_704', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050101_704_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "STP"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_704 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050101_704::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050101_704_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050101_704::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050101_704::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050101_704_lock(Request $request, $an)
    {
        Debtor_1102050101_704::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050101_704_unlock(Request $request, $an)
    {
        Debtor_1102050101_704::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050101_704_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_704::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050101_704_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_704   
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050101_704_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050101_704_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_704_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050101_704_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_107--------------------------------------------------------------------------------------------------------------
    public function _1102050102_107(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::connection('hosxp')->select('
                SELECT d.*, r.total_amount, IFNULL(d.receive,0) + IFNULL(r.total_amount,0) AS receive,
                    IFNULL(t.visit,0) AS visit,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(r.total_amount,0)) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0) >= -0.05
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM hrims.debtor_1102050102_107 d
                LEFT JOIN (SELECT r.vn,r.bill_date,SUM(r.total_amount) AS total_amount,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                    WHERE a.rcpno IS NULL AND r.bill_date > ?
                    GROUP BY r.vn, r.bill_date) r ON r.vn = d.an AND r.bill_date > d.dchdate
                LEFT JOIN (SELECT an, COUNT(an) AS visit FROM hrims.debtor_1102050102_107_tracking GROUP BY an) t ON t.an = d.an
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?', [$start_date, $search, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::connection('hosxp')->select('
                SELECT d.*, r.total_amount, IFNULL(d.receive,0) + IFNULL(r.total_amount,0) AS receive,
                    IFNULL(t.visit,0) AS visit,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(r.total_amount,0)) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0) >= -0.05
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM hrims.debtor_1102050102_107 d
                LEFT JOIN (SELECT r.vn,r.bill_date,SUM(r.total_amount) AS total_amount,
                    GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                    WHERE a.rcpno IS NULL AND r.bill_date > ?
                    GROUP BY r.vn, r.bill_date) r ON r.vn = d.an AND r.bill_date > d.dchdate
                LEFT JOIN (SELECT an, COUNT(an) AS visit FROM hrims.debtor_1102050102_107_tracking GROUP BY an) t ON t.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?', [$start_date, $start_date, $end_date]);
        }

        // Lazy Loading: Defaults to empty, fetched via AJAX
        $debtor_search = [];
        $debtor_search_iclaim = [];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050102_107', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search', 'debtor_search_iclaim'));
    }

    public function _1102050102_107_search_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $data = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.regdate, i.regtime,i.dchdate, i.dchtime,i.hn, i.vn, i.an,p.cid,
                CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,p.mobile_phone_number,a.age_y,
                p1.`name` AS pttype,ip.hospmain,p1.hipdata_code,a.pdx,i.adjrw,a.income,
                IFNULL(inc.paid_money,0) AS paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, 
                IFNULL(inc.paid_money,0) - IFNULL(rc.rcpt_money,0) AS debtor,
                rc.rcpno,p2.arrear_date,p2.amount AS arrear_amount,fd.deposit_amount,fd1.debit_amount,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN an_stat a ON a.an = i.an
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p1 ON p1.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN patient p ON p.hn = i.hn
            LEFT JOIN patient_arrear p2 ON p2.an = i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN (SELECT op.an, SUM(CASE WHEN op.paidst IN ("00","01","03") THEN op.sum_price ELSE 0 END) AS paid_money
                FROM opitemrece op 
                INNER JOIN ipt i2 ON i2.an = op.an AND i2.dchdate BETWEEN ? AND ?
                GROUP BY op.an) inc ON inc.an = i.an
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL 
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN hospcode h ON h.hospcode = ip.hospmain
            WHERE i.dchdate BETWEEN ? AND ?
            AND IFNULL(inc.paid_money,0) > 0
            AND IFNULL(inc.paid_money,0) - IFNULL(rc.rcpt_money,0) > 0
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_107 WHERE dchdate BETWEEN ? AND ?)
            GROUP BY i.an
            ORDER BY i.dchdate', [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }

    public function _1102050102_107_iclaim_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value');

        $data = DB::connection('hosxp')->select('
            SELECT * FROM (SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.vn,i.hn,i.an,
                CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,p.mobile_phone_number,a.pdx,p.cid,a.age_y,p1.name AS pttype,
                ip.hospmain,p1.hipdata_code,i.adjrw,a.income,a.paid_money ,a.rcpt_money,IFNULL(SUM(o1.sum_price),0) AS other,
                a.income-a.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor
            FROM ipt i 
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p1 ON p1.pttype=ip.pttype
            LEFT JOIN patient p ON p.hn=i.hn
            LEFT JOIN opitemrece o1 ON o1.an=i.an AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
            WHERE i.confirm_discharge = "Y" 
            AND ip.pttype = ?
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_107 WHERE dchdate BETWEEN ? AND ?) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate) AS a WHERE debtor <> "0"', [$pttype_iclaim, $start_date, $end_date, $start_date, $end_date]);

        return response()->json($data);
    }

    public function _1102050102_107_counts_ajax(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value');

        // Fast count for Tab 2
        $count2 = DB::connection('hosxp')->table('ipt as i')
            ->leftJoin(DB::raw('(SELECT op.an, SUM(CASE WHEN op.paidst IN ("00","01","03") THEN op.sum_price ELSE 0 END) as paid_money FROM opitemrece op INNER JOIN ipt i2 ON i2.an = op.an AND i2.dchdate BETWEEN "'.$start_date.'" AND "'.$end_date.'" GROUP BY op.an) inc'), 'inc.an', '=', 'i.an')
            ->leftJoin(DB::raw('(SELECT r.vn as an, SUM(r.total_amount) as rcpt_money FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE a.rcpno IS NULL GROUP BY r.vn) rc'), 'rc.an', '=', 'i.an')
            ->whereBetween('i.dchdate', [$start_date, $end_date])
            ->where(DB::raw('IFNULL(inc.paid_money,0)'), '>', 0)
            ->where(DB::raw('IFNULL(inc.paid_money,0) - IFNULL(rc.rcpt_money,0)'), '>', 0)
            ->whereNotIn('i.an', function($query) use ($start_date, $end_date) {
                $query->select('an')->from('hrims.debtor_1102050102_107')->whereBetween('dchdate', [$start_date, $end_date]);
            })
            ->count('i.an');

        // Fast count for Tab 3
        $count3 = DB::connection('hosxp')->table('ipt as i')
            ->leftJoin('ipt_pttype as ip', 'ip.an', '=', 'i.an')
            ->where('i.confirm_discharge', 'Y')
            ->where('ip.pttype', $pttype_iclaim)
            ->whereBetween('i.dchdate', [$start_date, $end_date])
            ->whereNotIn('i.an', function($query) use ($start_date, $end_date) {
                $query->select('an')->from('hrims.debtor_1102050102_107')->whereBetween('dchdate', [$start_date, $end_date]);
            })
            ->count('i.an');

        return response()->json([
            'tab2' => $count2,
            'tab3' => $count3
        ]);
    }

    //_1102050102_107_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox');
        $checkbox_string = implode(",", $checkbox);

        $debtor = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.regdate, i.regtime,i.dchdate, i.dchtime,i.hn, i.vn, i.an,p.cid,
                CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,p.mobile_phone_number,a.age_y,
                p1.`name` AS pttype,ip.hospmain,p1.hipdata_code,a.pdx,i.adjrw,a.income,
                IFNULL(inc.paid_money,0) AS paid_money,
                IFNULL(rc.rcpt_money,0) AS rcpt_money, 
                IFNULL(inc.paid_money,0) - IFNULL(rc.rcpt_money,0) AS debtor,
                rc.rcpno,p2.arrear_date,p2.amount AS arrear_amount,fd.deposit_amount,fd1.debit_amount,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN an_stat a ON a.an = i.an
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p1 ON p1.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN patient p ON p.hn = i.hn
            LEFT JOIN patient_arrear p2 ON p2.an = i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN (SELECT op.an, SUM(CASE WHEN op.paidst IN ("00","01","03") THEN op.sum_price ELSE 0 END) AS paid_money
                FROM opitemrece op 
                WHERE op.an IN (' . $checkbox_string . ')
                GROUP BY op.an) inc ON inc.an = i.an
            LEFT JOIN (SELECT r.vn AS an, SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno 
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                WHERE a.rcpno IS NULL AND r.vn IN (' . $checkbox_string . ')
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN hospcode h ON h.hospcode = ip.hospmain
            WHERE i.an IN (' . $checkbox_string . ')
            GROUP BY i.an');

        foreach ($debtor as $row) {
            Debtor_1102050102_107::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'an' => $row->an,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'paid_money' => $row->paid_money,
                'rcpt_money' => $row->rcpt_money,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_107_confirm_iclaim-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_confirm_iclaim(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'checkbox_iclaim' => 'required|array',
        ], [
            'checkbox_iclaim.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_iclaim');
        $checkbox_string = implode(",", $checkbox);

        $debtor = DB::connection('hosxp')->select('
            SELECT * FROM (SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.vn,i.hn,i.an,
                CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,p.mobile_phone_number,a.pdx,p.cid,a.age_y,p1.name AS pttype,
                ip.hospmain,p1.hipdata_code,i.adjrw,a.income,a.paid_money,a.rcpt_money,IFNULL(SUM(o1.sum_price),0) AS other,
                a.income-a.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p1 ON p1.pttype=ip.pttype
            LEFT JOIN patient p ON p.hn=i.hn
            LEFT JOIN opitemrece o1 ON o1.an=i.an AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
            WHERE i.confirm_discharge = "Y" 
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate ) AS a');

        foreach ($debtor as $row) {
            Debtor_1102050102_107::insert([
                'vn' => $row->vn,
                'hn' => $row->hn,
                'an' => $row->an,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'income' => $row->income,
                'paid_money' => $row->paid_money,
                'rcpt_money' => $row->rcpt_money,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_107_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_107::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_107::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_107_lock(Request $request, $an)
    {
        Debtor_1102050102_107::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_107_unlock(Request $request, $an)
    {
        Debtor_1102050102_107::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_107_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_107::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_107_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::connection('hosxp')->select("
            SELECT d.dchdate AS vstdate,COUNT(DISTINCT d.vn) AS anvn,
                SUM(d.debtor) AS debtor,SUM(IFNULL(d.receive,0) + IFNULL(r.total_amount,0)) AS receive
            FROM hrims.debtor_1102050102_107 d
            LEFT JOIN (SELECT r.vn, r.bill_date,SUM(r.total_amount) AS total_amount
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn, r.bill_date) r ON r.vn = d.vn  AND r.bill_date <> d.dchdate
            WHERE d.dchdate BETWEEN ? AND ?
            GROUP BY d.dchdate ORDER BY d.dchdate", [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_107_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_107_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_107_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050102_107_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    //1102050102_107_tracking-------------------------------------------------------------------------------------------------------  
    public function _1102050102_107_tracking(Request $request, $an)
    {
        $debtor = DB::select('
            SELECT * FROM debtor_1102050102_107 WHERE an = ?', [$an]);

        $tracking = DB::select('
            SELECT * FROM debtor_1102050102_107_tracking WHERE an = ?', [$an]);

        return view('debtor.1102050102_107_tracking', compact('debtor', 'tracking'));
    }
    //1102050102_107_tracking_insert-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_tracking_insert(Request $request)
    {
        $item = new Debtor_1102050102_107_tracking;
        $item->vn = $request->input('vn');
        $item->an = $request->input('an');
        $item->tracking_date = $request->input('tracking_date');
        $item->tracking_type = $request->input('tracking_type');
        $item->tracking_no = $request->input('tracking_no');
        $item->tracking_officer = $request->input('tracking_officer');
        $item->tracking_note = $request->input('tracking_note');
        $item->save();

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //_1102050102_107_tracking_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_tracking_update(Request $request, $tracking_id)
    {
        Debtor_1102050102_107_tracking::where('tracking_id', $tracking_id)
            ->update([
                'tracking_date' => $request->input('tracking_date'),
                'tracking_type' => $request->input('tracking_type'),
                'tracking_no' => $request->input('tracking_no'),
                'tracking_officer' => $request->input('tracking_officer'),
                'tracking_note' => $request->input('tracking_note')
            ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    ##############################################################################################################################################################
    //_1102050102_109--------------------------------------------------------------------------------------------------------------
    public function _1102050102_109(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_109 d
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_109 d
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        // REMOVED session('debtor') TO PREVENT BLOAT
        $request->session()->save();

        return view('debtor.1102050102_109', compact('start_date', 'end_date', 'search', 'debtor'));
    }

    public function _1102050102_109_search_ajax(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date');
        $end_date = $request->end_date ?: Session::get('end_date');

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "GOF"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_109 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        return response()->json($debtor_search);
    }
    //_1102050102_109_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "GOF"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_109 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050102_109::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_109_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_109::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_109::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_109_lock(Request $request, $an)
    {
        Debtor_1102050102_109::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_109_unlock(Request $request, $an)
    {
        Debtor_1102050102_109::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_109_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_109::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_109_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM Debtor_1102050102_109 
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_109_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_109_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_109_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050102_109_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_111--------------------------------------------------------------------------------------------------------------
    public function _1102050102_111(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.cid,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,d.status,
                    d.receive AS receive_manual,d.repno AS repno_manual,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END) AS receive,
                    stm.repno, cipn.rid AS cipn_rid, csop.rid AS csop_rid, 
                    CONCAT_WS(CHAR(44), stm.round_no, cipn.round_no, csop.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, cipn.receipt_date, csop.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, cipn.receive_no, csop.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_111 d    
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM hrims.stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an, SUM(c.amount) AS amount, GROUP_CONCAT(c.rid) AS rid,
                    MAX(c.round_no) AS round_no, MAX(c.receipt_date) AS receipt_date, MAX(c.receive_no) AS receive_no 
                    FROM debtor_1102050102_111 d2 
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an       
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?', [$search, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.cid,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,d.status,
                    d.receive AS receive_manual,d.repno AS repno_manual,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END) AS receive,
                    stm.repno, cipn.rid AS cipn_rid, csop.rid AS csop_rid, 
                    CONCAT_WS(CHAR(44), stm.round_no, cipn.round_no, csop.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, cipn.receipt_date, csop.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, cipn.receive_no, csop.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_111 d    
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM hrims.stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an, SUM(c.amount) AS amount, GROUP_CONCAT(c.rid) AS rid,
                    MAX(c.round_no) AS round_no, MAX(c.receipt_date) AS receipt_date, MAX(c.receive_no) AS receive_no 
                    FROM debtor_1102050102_111 d2 
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an                     
                WHERE d.dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("BMT","KKT")
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_111 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050102_111', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050102_111_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_111_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("BMT","KKT")
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_111 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050102_111::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'kidney' => $row->kidney,
                'debtor' => $row->debtor,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_111_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_111_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_111::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_111::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_111_lock(Request $request, $an)
    {
        Debtor_1102050102_111::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_111_unlock(Request $request, $an)
    {
        Debtor_1102050102_111::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050102_111_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_111_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT a.dchdate AS vstdate,COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (SELECT d.dchdate,d.an, MAX(d.debtor) AS debtor, IFNULL(stm.receive_total,0)
                    + IFNULL(cipn.gtotal,0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(csop.amount,0) ELSE 0 END AS receive_total
            FROM debtor_1102050102_111 d    
            LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total
                    FROM stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount FROM debtor_1102050102_111 d2
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.dchdate, d.an) a
                GROUP BY a.dchdate ORDER BY a.dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_111_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_111_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_111_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050102_111_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }

    ##############################################################################################################################################################
    //_1102050102_603--------------------------------------------------------------------------------------------------------------
    public function _1102050102_603(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_603 d                
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, CASE WHEN (IFNULL(d.receive,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_603 d                
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_603 WHERE an IS NOT NULL)
            AND p.pttype IN (' . $pttype_act . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050102_603', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050102_603_confirm------------------------------------------------------------------------------------------------------- 
    public function _1102050102_603_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw, 
                COALESCE(inc.income,0) AS income,a.income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(oth.other_price,0) AS other,
                COALESCE(inc.income,0)-COALESCE(rc.rcpt_money,0)-COALESCE(oth.other_price,0) AS debtor,oth.other_list,
                ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an     
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS other_price,GROUP_CONCAT(DISTINCT s.name ) AS other_list
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode WHERE li.kidney = "Y"
                GROUP BY o.an, o.pttype) oth ON oth.an = i.an AND oth.pttype = ip.pttype
            WHERE i.confirm_discharge = "Y"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_603 WHERE an IS NOT NULL)
            AND p.pttype IN (' . $pttype_act . ') 
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate, i.an, ip.pttype',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050102_603::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'other' => $row->other,
                'debtor' => $row->debtor,
                'status' => $row->status,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_603_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_603_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_603::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_603::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_603_lock(Request $request, $an)
    {
        Debtor_1102050102_603::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_603_unlock(Request $request, $an)
    {
        Debtor_1102050102_603::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //_1102050102_603_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_603_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_603::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }
    //1102050102_603_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_603_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_603 
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_603_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_603_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_603_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050102_603_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    //_1102050102_802--------------------------------------------------------------------------------------------------------------
    public function _1102050102_802(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                       IFNULL(sk.amount,0) AS kidney, st.repno AS stm_repno, st.round_no AS stm_round_no, 
                       st.receipt_date AS stm_receipt_date, st.receive_no AS stm_receive_no,
                       IFNULL(st.total,0) AS receive_lgo,
                       IFNULL(sk.amount,0) AS receive_kidney,
                       (IFNULL(d.receive,0) + IFNULL(st.total,0) + IFNULL(sk.amount,0)) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(st.total,0) + IFNULL(sk.amount,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_802 d
                LEFT JOIN (SELECT d2.an, SUM(k.compensate_kidney) AS amount 
                           FROM debtor_1102050102_802 d2 
                           JOIN stm_lgo_kidney k ON k.cid = d2.cid AND k.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                           GROUP BY d2.an) sk ON sk.an = d.an
                LEFT JOIN (SELECT an, MAX(repno) AS repno, SUM(compensate_treatment) AS total, 
                            GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                           FROM stm_lgo GROUP BY an) st ON st.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?
                AND (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                ORDER BY d.dchdate
            ', [$start_date, $end_date, $search, $search, $search]);
        } else {
            $debtor = DB::select('
                SELECT d.*, d.receive AS receive_manual, d.repno AS repno_manual,
                       IFNULL(sk.amount,0) AS kidney, st.repno AS stm_repno, st.round_no AS stm_round_no, 
                       st.receipt_date AS stm_receipt_date, st.receive_no AS stm_receive_no,
                       IFNULL(st.total,0) AS receive_lgo,
                       IFNULL(sk.amount,0) AS receive_kidney,
                       (IFNULL(d.receive,0) + IFNULL(st.total,0) + IFNULL(sk.amount,0)) AS receive,
                       CASE WHEN (IFNULL(d.receive,0) + IFNULL(st.total,0) + IFNULL(sk.amount,0) + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                       THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_802 d
                LEFT JOIN (SELECT d2.an, SUM(k.compensate_kidney) AS amount 
                           FROM debtor_1102050102_802 d2 
                           JOIN stm_lgo_kidney k ON k.cid = d2.cid AND k.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                           GROUP BY d2.an) sk ON sk.an = d.an
                LEFT JOIN (SELECT an, MAX(repno) AS repno, SUM(compensate_treatment) AS total, 
                                  GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                           FROM stm_lgo GROUP BY an) st ON st.an = d.an
                WHERE d.dchdate BETWEEN ? AND ?
                ORDER BY d.dchdate
            ', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "LGO"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_802 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050102_802', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050102_802_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_802_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code = "LGO"
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_802 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050102_802::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'kidney' => $row->kidney,
                'debtor' => $row->debtor,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_802_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_802_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_802::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_802::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_802_lock(Request $request, $an)
    {
        Debtor_1102050102_802::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_802_unlock(Request $request, $an)
    {
        Debtor_1102050102_802::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }

    //1102050102_802_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_802_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT a.dchdate AS vstdate,COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive) AS receive
                FROM (SELECT d.an,d.dchdate,MAX(d.debtor) AS debtor,IFNULL(s.compensate_treatment,0)+ CASE WHEN MAX(d.kidney) > 0
                THEN IFNULL(k.compensate_kidney,0) ELSE 0 END AS receive
            FROM debtor_1102050102_802 d   
            LEFT JOIN (SELECT an,SUM(compensate_treatment) AS compensate_treatment
                FROM stm_lgo GROUP BY an) s ON s.an = d.an
            LEFT JOIN (SELECT d2.an,SUM(sk.compensate_kidney) AS compensate_kidney
                FROM debtor_1102050102_802 d2 JOIN stm_lgo_kidney sk ON sk.cid = d2.cid
                AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                WHERE d2.dchdate BETWEEN ? AND ? GROUP BY d2.an) k ON k.an = d.an
            WHERE d.dchdate BETWEEN ? AND ?
            GROUP BY d.an, d.dchdate) a
            GROUP BY a.dchdate ORDER BY a.dchdate', [$start_date, $end_date, $start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_802_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_802_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_802_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050102_802_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }
    ##############################################################################################################################################################
    public function _1102050102_804(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search = $request->search ?: Session::get('search');

        if ($search) {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.cid,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,d.status,
                    d.receive AS receive_manual,d.repno AS repno_manual,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END) AS receive,
                    stm.repno, cipn.rid AS cipn_rid, csop.rid AS csop_rid, 
                    CONCAT_WS(CHAR(44), stm.round_no, cipn.round_no, csop.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, cipn.receipt_date, csop.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, cipn.receive_no, csop.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_804 d    
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM hrims.stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an, SUM(c.amount) AS amount, GROUP_CONCAT(c.rid) AS rid,
                    MAX(c.round_no) AS round_no, MAX(c.receipt_date) AS receipt_date, MAX(c.receive_no) AS receive_no 
                    FROM debtor_1102050102_804 d2 
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an       
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?', [$search, $search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.cid,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,
                    d.income,d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,d.status,
                    d.receive AS receive_manual,d.repno AS repno_manual,d.adj_inc,d.adj_dec,d.adj_date,d.adj_note,
                    (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END) AS receive,
                    stm.repno, cipn.rid AS cipn_rid, csop.rid AS csop_rid, 
                    CONCAT_WS(CHAR(44), stm.round_no, cipn.round_no, csop.round_no) AS stm_round_no,
                    CONCAT_WS(CHAR(44), stm.receipt_date, cipn.receipt_date, csop.receipt_date) AS stm_receipt_date,
                    CONCAT_WS(CHAR(44), stm.receive_no, cipn.receive_no, csop.receive_no) AS stm_receive_no,
                    CASE WHEN (IFNULL(d.receive,0) + IFNULL(stm.receive_total,0) + IFNULL(cipn.gtotal,0) + CASE WHEN d.kidney > 0 
                    THEN IFNULL(csop.amount,0) ELSE 0 END + IFNULL(d.adj_inc,0) - IFNULL(d.adj_dec,0) - IFNULL(d.debtor,0)) >= -0.01 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_804 d    
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total, GROUP_CONCAT(repno) AS repno,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no
                    FROM hrims.stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal, GROUP_CONCAT(rid) AS rid,
                    GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an, SUM(c.amount) AS amount, GROUP_CONCAT(c.rid) AS rid,
                    MAX(c.round_no) AS round_no, MAX(c.receipt_date) AS receipt_date, MAX(c.receive_no) AS receive_no 
                    FROM debtor_1102050102_804 d2 
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an                     
                WHERE d.dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("BKK","PTY") 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_804 WHERE an IS NOT NULL)
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->put('search', $search);
        $request->session()->put('debtor', $debtor);
        $request->session()->save();

        return view('debtor.1102050102_804', compact('start_date', 'end_date', 'search', 'debtor', 'debtor_search'));
    }
    //_1102050102_804_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_804_confirm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
            'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN

        $debtor = DB::connection('hosxp')->select(
            '
            SELECT w.name AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname, pt.fname, " ", pt.lname) AS ptname,a.age_y,
                p.name AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                COALESCE(inc.income,0) AS income,COALESCE(rc.rcpt_money,0) AS rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,
                COALESCE(inc.income,0) - COALESCE(rc.rcpt_money,0) AS debtor,kidney.kidney_list,ict.ipt_coll_status_type_name,
                i.data_ok,"ยืนยันลูกหนี้" AS status
            FROM ipt i
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN ward w ON w.ward = i.ward
            LEFT JOIN an_stat a ON a.an = i.an         
            LEFT JOIN ipt_coll_stat ic ON ic.an = i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id = ic.ipt_coll_status_type_id
            LEFT JOIN (SELECT o.an,o.pttype,SUM(o.sum_price) AS income
                FROM opitemrece o
                INNER JOIN ipt i2 ON i2.an = o.an AND i2.confirm_discharge = "Y" AND i2.dchdate BETWEEN ? AND ?
                GROUP BY o.an, o.pttype) inc ON inc.an = i.an AND inc.pttype = ip.pttype
            LEFT JOIN (SELECT r.vn AS an,SUM(r.total_amount) AS rcpt_money,
                GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                GROUP BY r.vn) rc ON rc.an = i.an
            LEFT JOIN (SELECT o.an,SUM(o.sum_price) AS kidney_price,GROUP_CONCAT(DISTINCT s.name) AS kidney_list
                FROM opitemrece o
                INNER JOIN ipt i4 ON i4.an = o.an AND i4.dchdate BETWEEN ? AND ?
                LEFT JOIN hrims.lookup_icode li ON li.icode = o.icode
                LEFT JOIN s_drugitems s ON s.icode = o.icode
                WHERE li.kidney = "Y"
                GROUP BY o.an) kidney ON kidney.an = i.an
            WHERE i.confirm_discharge = "Y"
            AND p.hipdata_code IN ("BKK","PTY") 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_804 WHERE an IS NOT NULL)
            AND i.an IN (' . $checkbox_string . ') 
            GROUP BY i.an, ip.pttype
            ORDER BY i.ward, i.dchdate',
            [$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
        );

        foreach ($debtor as $row) {
            Debtor_1102050102_804::insert([
                'an' => $row->an,
                'vn' => $row->vn,
                'hn' => $row->hn,
                'cid' => $row->cid,
                'ptname' => $row->ptname,
                'regdate' => $row->regdate,
                'regtime' => $row->regtime,
                'dchdate' => $row->dchdate,
                'dchtime' => $row->dchtime,
                'pttype' => $row->pttype,
                'hospmain' => $row->hospmain,
                'hipdata_code' => $row->hipdata_code,
                'pdx' => $row->pdx,
                'adjrw' => $row->adjrw,
                'income' => $row->income,
                'rcpt_money' => $row->rcpt_money,
                'kidney' => $row->kidney,
                'debtor' => $row->debtor,
            ]);
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
    //_1102050102_804_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_804_delete(Request $request)
    {
        $checkbox = $request->input('checkbox_d');

        if (empty($checkbox) || !is_array($checkbox)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่จะลบ');
        }

        $all_items = Debtor_1102050102_804::whereIn('an', $checkbox)->get();
        $locked_items = $all_items->where('debtor_lock', 'Y')->count();
        $deletable_items = $all_items->where('debtor_lock', '!=', 'Y')->pluck('an')->toArray();

        if (count($deletable_items) > 0) {
            Debtor_1102050102_804::whereIn('an', $deletable_items)->delete();
        }

        if ($locked_items == count($checkbox)) {
            return redirect()->back()->with('error', 'ไม่สามารถลบรายการได้ เนื่องจากรายการที่เลือกถูกล็อคทั้งหมด');
        } elseif ($locked_items > 0) {
            return redirect()->back()->with('warning', "ลบรายการสำเร็จ " . count($deletable_items) . " รายการ (ข้ามรายการที่ถูกล็อค " . $locked_items . " รายการ)");
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย');
    }

    public function _1102050102_804_lock(Request $request, $an)
    {
        Debtor_1102050102_804::where('an', $an)->update(['debtor_lock' => 'Y']);
        return redirect()->back();
    }

    public function _1102050102_804_unlock(Request $request, $an)
    {
        Debtor_1102050102_804::where('an', $an)->update(['debtor_lock' => NULL]);
        return redirect()->back();
    }


    //1102050102_804_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_804_daily_pdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT a.dchdate AS vstdate,COUNT(DISTINCT a.an) AS anvn,SUM(a.debtor) AS debtor,SUM(a.receive_total) AS receive
            FROM (SELECT d.dchdate,d.an, MAX(d.debtor) AS debtor, IFNULL(stm.receive_total,0)
                    + IFNULL(cipn.gtotal,0)+CASE WHEN MAX(d.kidney) > 0 THEN IFNULL(csop.amount,0) ELSE 0 END AS receive_total
                FROM debtor_1102050102_804 d    
                LEFT JOIN (SELECT an, SUM(receive_total) AS receive_total
                    FROM stm_ofc GROUP BY an) stm ON stm.an = d.an
                LEFT JOIN (SELECT an, SUM(gtotal) AS gtotal 
                    FROM hrims.stm_ofc_cipn GROUP BY an) cipn ON cipn.an = d.an
                LEFT JOIN (SELECT d2.an,SUM(c.amount) AS amount FROM debtor_1102050101_402 d2
                    JOIN hrims.stm_ofc_csop c ON c.hn = d2.hn AND c.vstdate BETWEEN d2.regdate AND d2.dchdate
                    GROUP BY d2.an) csop ON csop.an = d.an
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.dchdate, d.an) a
                GROUP BY a.dchdate ORDER BY a.dchdate', [$start_date, $end_date]);

        $pdf = PDF::loadView('debtor.1102050102_804_daily_pdf', compact('hospital_name', 'hospital_code', 'start_date', 'end_date', 'debtor'))
            ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
    //1102050102_804_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_804_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');

        return view('debtor.1102050102_804_indiv_excel', compact('start_date', 'end_date', 'debtor'));
    }

    //#####################################################################################################################
    public function lock_debtor(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->status !== 'admin' && $user->allow_debtor_lock !== 'Y') {
            return response()->json([
                'ok' => false,
                'message' => 'คุณไม่มีสิทธิ์ใช้งาน Lock ลูกหนี้'
            ], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        $start = $request->start_date;
        $end = $request->end_date;

        // ตารางที่ใช้ vstdate
        $vstTables = [
            'debtor_1102050101_103',
            'debtor_1102050101_109',
            'debtor_1102050101_201',
            'debtor_1102050101_203',
            'debtor_1102050101_209',
            'debtor_1102050101_216',
            'debtor_1102050101_301',
            'debtor_1102050101_303',
            'debtor_1102050101_307',
            'debtor_1102050101_309',
            'debtor_1102050101_401',
            'debtor_1102050101_501',
            'debtor_1102050101_503',
            'debtor_1102050101_701',
            'debtor_1102050101_702',
            'debtor_1102050101_703',
            'debtor_1102050102_106',
            'debtor_1102050102_108',
            'debtor_1102050102_110',
            'debtor_1102050102_602',
            'debtor_1102050102_801',
            'debtor_1102050102_803'
        ];

        // ตารางที่ใช้ dchdate
        $dchTables = [
            'debtor_1102050101_202',
            'debtor_1102050101_217',
            'debtor_1102050101_302',
            'debtor_1102050101_304',
            'debtor_1102050101_308',
            'debtor_1102050101_310',
            'debtor_1102050101_402',
            'debtor_1102050101_502',
            'debtor_1102050101_504',
            'debtor_1102050101_704',
            'debtor_1102050102_107',
            'debtor_1102050102_109',
            'debtor_1102050102_111',
            'debtor_1102050102_603',
            'debtor_1102050102_802',
            'debtor_1102050102_804'
        ];

        $affected = 0;

        DB::beginTransaction();
        try {

            foreach ($vstTables as $table) {
                $affected += DB::table($table)
                    ->whereBetween('vstdate', [$start, $end])
                    ->where(function ($q) {
                        $q->whereNull('debtor_lock')
                            ->orWhere('debtor_lock', '!=', 'Y');
                    })
                    ->update([
                        'debtor_lock' => 'Y',
                        'updated_at' => now()
                    ]);
            }

            foreach ($dchTables as $table) {
                $affected += DB::table($table)
                    ->whereBetween('dchdate', [$start, $end])
                    ->where(function ($q) {
                        $q->whereNull('debtor_lock')
                            ->orWhere('debtor_lock', '!=', 'Y');
                    })
                    ->update([
                        'debtor_lock' => 'Y',
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'start_date' => $start,
                'end_date' => $end,
                'rows' => $affected,
                'tables' => count($vstTables) + count($dchTables)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function get_budget_years()
    {
        $years = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();
        return response()->json($years);
    }

    public function get_dashboard_data(Request $request)
    {
        ini_set('max_execution_time', 300);
        $code = $request->code;
        $table_name = "debtor_" . $code;

        // Verify table exists
        $tableExists = DB::select("SHOW TABLES LIKE '{$table_name}'");
        if (empty($tableExists)) {
            return response()->json(['error' => 'Data table not found'], 404);
        }

        // Determine date column (vstdate for OP, dchdate for IP)
        $columns = DB::select("SHOW COLUMNS FROM {$table_name}");
        $date_col = 'vstdate';
        foreach ($columns as $column) {
            if ($column->Field == 'dchdate') {
                $date_col = 'dchdate';
                break;
            }
        }

        // Special case for 307 which uses COALESCE in summary
        $filter_date_col = $date_col;
        if ($code == '1102050101_307') {
            $filter_date_col = "COALESCE(dchdate, vstdate)";
        }

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        $budget_year = $request->budget_year ?: $budget_year_now;
        $year_data = DB::table('budget_year')->where('LEAVE_YEAR_ID', $budget_year)->first();
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b = $year_data->DATE_END ?? null;

        if ($budget_year == $budget_year_now) {
            $end_date_b = date('Y-m-d');
        }

        $month_case = 'CASE 
            WHEN MONTH(' . $filter_date_col . ')=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
            WHEN MONTH(' . $filter_date_col . ')=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(' . $filter_date_col . ')+543, 2))
        END';

        $sql = "";

        // Optimization: Use UNION ALL to separate debtor sum and receive sum for better performance 
        // across 1-year date ranges, specifically for high-volume OPD accounts.
        $params = [];
        switch ($code) {
            case '1102050101_216':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, 0 AS receive_total, YEAR({$date_col}) AS y, MONTH({$date_col}) AS m
                            FROM {$table_name} WHERE {$date_col} BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.' . $date_col, $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(s.receive_total,0) - IFNULL(s.receive_pp,0)) AS receive_total, YEAR(d.{$date_col}) AS y, MONTH(d.{$date_col}) AS m
                            FROM stm_ucs s JOIN {$table_name} d ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5)
                            WHERE d.{$date_col} BETWEEN ? AND ? AND s.vstdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.' . $date_col, $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(sk.receive_total,0)) AS receive_total, YEAR(d.{$date_col}) AS y, MONTH(d.{$date_col}) AS m
                            FROM stm_ucs_kidney sk JOIN {$table_name} d ON sk.cid = d.cid AND sk.datetimeadm = d.vstdate
                            WHERE d.{$date_col} BETWEEN ? AND ? AND sk.datetimeadm BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050101_309':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, SUM(IFNULL(receive,0)) AS receive_total, YEAR({$date_col}) AS y, MONTH({$date_col}) AS m
                            FROM {$table_name} WHERE {$date_col} BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.' . $date_col, $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(sk.amount,0)+ IFNULL(sk.epopay,0) + IFNULL(sk.epoadm,0)) AS receive_total, YEAR(d.{$date_col}) AS y, MONTH(d.{$date_col}) AS m
                            FROM stm_sss_kidney sk JOIN {$table_name} d ON sk.cid = d.cid AND sk.vstdate = d.vstdate
                            WHERE d.{$date_col} BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050101_401':
            case '1102050102_110':
            case '1102050102_803':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, SUM(IFNULL(receive,0)) AS receive_total, YEAR({$date_col}) AS y, MONTH({$date_col}) AS m
                            FROM {$table_name} WHERE {$date_col} BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.' . $date_col, $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(s.receive_total,0)) AS receive_total, YEAR(d.{$date_col}) AS y, MONTH(d.{$date_col}) AS m
                            FROM stm_ofc s JOIN {$table_name} d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5)
                            WHERE d.{$date_col} BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.' . $date_col, $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(c.amount,0)) AS receive_total, YEAR(d.{$date_col}) AS y, MONTH(d.{$date_col}) AS m
                            FROM stm_ofc_csop c JOIN {$table_name} d ON c.hn = d.hn AND c.vstdate = d.vstdate AND LEFT(c.vsttime,5) = LEFT(d.vsttime,5)
                            WHERE c.sys <> 'HD' AND d.{$date_col} BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.' . $date_col, $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(h.amount,0)) AS receive_total, YEAR(d.{$date_col}) AS y, MONTH(d.{$date_col}) AS m
                            FROM stm_ofc_csop h JOIN {$table_name} d ON h.hn = d.hn AND h.vstdate = d.vstdate
                            WHERE d.kidney > 0 AND h.sys = 'HD' AND d.{$date_col} BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050102_106':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, SUM(IFNULL(receive,0)) AS receive_total, YEAR(vstdate) AS y, MONTH(vstdate) AS m
                            FROM hrims.{$table_name} WHERE vstdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.vstdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(r.total_amount,0)) AS receive_total, YEAR(d.vstdate) AS y, MONTH(d.vstdate) AS m
                            FROM rcpt_print r 
                            LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                            JOIN hrims.{$table_name} d ON r.vn = d.vn
                            WHERE d.vstdate BETWEEN ? AND ? AND r.bill_date > d.vstdate AND a.rcpno IS NULL
                            GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050102_801':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, 0 AS receive_total, YEAR(vstdate) AS y, MONTH(vstdate) AS m
                            FROM {$table_name} WHERE vstdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.vstdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(s.compensate_treatment,0)) AS receive_total, YEAR(d.vstdate) AS y, MONTH(d.vstdate) AS m
                            FROM stm_lgo s JOIN {$table_name} d ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) = LEFT(d.vsttime,5)
                            WHERE d.vstdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.vstdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(k.compensate_kidney,0)) AS receive_total, YEAR(d.vstdate) AS y, MONTH(d.vstdate) AS m
                            FROM stm_lgo_kidney k JOIN {$table_name} d ON k.hn = d.hn AND k.datetimeadm = d.vstdate
                            WHERE d.kidney > 0 AND d.vstdate BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050101_202':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, 0 AS receive_total, YEAR(dchdate) AS y, MONTH(dchdate) AS m
                            FROM {$table_name} WHERE dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(stm.receive_ip_compensate_pay,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_ucs stm JOIN {$table_name} d ON stm.an = d.an
                            WHERE d.dchdate BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050101_217':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, 0 AS receive_total, YEAR(dchdate) AS y, MONTH(dchdate) AS m
                            FROM {$table_name} WHERE dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(s.receive_total,0)-IFNULL(s.receive_ip_compensate_pay,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_ucs s JOIN {$table_name} d ON s.an = d.an
                            WHERE d.dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(k.receive_total,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_ucs_kidney k JOIN {$table_name} d ON k.cid = d.cid AND k.datetimeadm BETWEEN d.regdate AND d.dchdate
                            WHERE d.dchdate BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050101_402':
            case '1102050102_111':
            case '1102050102_804':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, 0 AS receive_total, YEAR(dchdate) AS y, MONTH(dchdate) AS m
                            FROM {$table_name} WHERE dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(stm.receive_total,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_ofc stm JOIN {$table_name} d ON stm.an = d.an
                            WHERE d.dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(cipn.gtotal,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_ofc_cipn cipn JOIN {$table_name} d ON cipn.an = d.an
                            WHERE d.dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(kd.amount,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_ofc_csop kd JOIN {$table_name} d ON kd.hn = d.hn AND kd.vstdate BETWEEN d.regdate AND d.dchdate
                            WHERE d.kidney > 0 AND d.dchdate BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050102_107':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, SUM(IFNULL(receive,0)) AS receive_total, YEAR(dchdate) AS y, MONTH(dchdate) AS m
                            FROM hrims.{$table_name} WHERE dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(r.total_amount,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM rcpt_print r 
                            LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
                            JOIN hrims.{$table_name} d ON r.vn = d.an
                            WHERE d.dchdate BETWEEN ? AND ? AND r.bill_date > d.dchdate AND a.rcpno IS NULL
                            GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            case '1102050102_802':
                $sql = "SELECT month_name, SUM(claim_price) AS claim_price, SUM(receive_total) AS receive_total FROM (
                            SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, 0 AS receive_total, YEAR(dchdate) AS y, MONTH(dchdate) AS m
                            FROM {$table_name} WHERE dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(stm.compensate_treatment,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_lgo stm JOIN {$table_name} d ON stm.an = d.an
                            WHERE d.dchdate BETWEEN ? AND ? AND stm.dchdate BETWEEN ? AND ? GROUP BY y, m
                            UNION ALL
                            SELECT " . str_replace($filter_date_col, 'd.dchdate', $month_case) . " AS month_name, 0 AS claim_price, SUM(IFNULL(stm_k.compensate_kidney,0)) AS receive_total, YEAR(d.dchdate) AS y, MONTH(d.dchdate) AS m
                            FROM stm_lgo_kidney stm_k JOIN {$table_name} d ON stm_k.hn = d.hn AND stm_k.datetimeadm BETWEEN d.regdate AND d.dchdate
                            WHERE d.kidney > 0 AND d.dchdate BETWEEN ? AND ? AND stm_k.datetimeadm BETWEEN ? AND ? GROUP BY y, m
                        ) AS a GROUP BY y, m ORDER BY y, m";
                $params = [$start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b, $start_date_b, $end_date_b];
                break;
            default:
                // Standard query for other codes (Simple Sum)
                $sql = "SELECT {$month_case} AS month_name, SUM(debtor) AS claim_price, IFNULL(SUM(receive),0) AS receive_total
                        FROM {$table_name}
                        WHERE {$filter_date_col} BETWEEN ? AND ?
                        GROUP BY YEAR({$filter_date_col}), MONTH({$filter_date_col})
                        ORDER BY YEAR({$filter_date_col}), MONTH({$filter_date_col})";
                $params = [$start_date_b, $end_date_b];
                break;
        }

        $connection = in_array($code, ['1102050102_106', '1102050102_107']) ? 'hosxp' : config('database.default');
        $sum_month = DB::connection($connection)->select($sql, $params);

        return response()->json([
            'month' => collect($sum_month)->pluck('month_name'),
            'claim_price' => collect($sum_month)->pluck('claim_price'),
            'receive_total' => collect($sum_month)->pluck('receive_total'),
            'budget_year' => $budget_year
        ]);
    }

    public function _1102050101_201_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_201::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_202_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_202::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_209_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_209::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_216_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_216::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_217_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_217::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_301_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_301::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_402_update(Request $request, $an)
    {
        $item = Debtor_1102050101_402::where('an', $an)->firstOrFail();
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_701_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_701::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_702_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_702::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050102_111_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_111::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050102_802_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_802::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050102_804_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_804::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
            'adj_inc' => $request->input('adj_inc'),
            'adj_dec' => $request->input('adj_dec'),
            'adj_date' => $request->input('adj_date'),
            'adj_note' => $request->input('adj_note'),
        ]);
        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function _1102050101_109_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_109::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $request->bulk_adj_date ?: date('Y-m-d');
                $row->adj_note = $request->bulk_adj_note ?: 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_201_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_201::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm = \DB::table('stm_ucs')->where('cid', $row->cid)->where('vstdate', $row->vstdate)
                    ->where(\DB::raw('LEFT(vsttime, 5)'), '=', substr($row->vsttime, 0, 5))
                    ->sum('receive_pp');

                $total_received = (float)$row->receive + (float)$stm;
                $diff = (float)$row->debtor - $total_received;
                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_202_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = DB::table('debtor_1102050101_202 as d')
                ->leftJoin(DB::raw('(SELECT an, SUM(receive_total) as receive_total FROM stm_ucs GROUP BY an) stm'), 'stm.an', '=', 'd.an')
                ->where('d.an', $id)
                ->where('d.debtor_lock', 'Y')
                ->select('d.*', 'stm.receive_total')
                ->first();

            if ($row) {
                $total_received = (float)($row->receive_total ?? 0) + (float)($row->receive ?? 0);
                $diff = (float)$row->debtor - $total_received;
                
                if ($diff > 0) {
                    $adj_inc = $diff;
                    $adj_dec = 0;
                } else {
                    $adj_inc = 0;
                    $adj_dec = abs($diff);
                }

                \App\Models\Debtor_1102050101_202::where('an', $id)->update([
                    'adj_inc' => $adj_inc,
                    'adj_dec' => $adj_dec,
                    'adj_date' => $adj_date,
                    'adj_note' => $adj_note,
                ]);
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_203_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_203::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm = \DB::table('stm_ucs')->where('cid', $row->cid)->where('vstdate', $row->vstdate)
                    ->where(\DB::raw('LEFT(vsttime, 5)'), '=', substr($row->vsttime, 0, 5))
                    ->sum('receive_pp');

                $total_received = (float)$row->receive + (float)$stm;
                $diff = (float)$row->debtor - $total_received;
                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }


    public function _1102050101_209_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_209::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }

        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }

        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_216_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_216::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                // Fetch dynamic STM totals for this patient
                $stm = DB::selectOne('
                    SELECT 
                        (SELECT IFNULL(SUM(receive_total), 0) - IFNULL(SUM(receive_pp), 0) 
                         FROM stm_ucs 
                         WHERE cid = ? AND vstdate = ? AND LEFT(vsttime, 5) = LEFT(?, 5)) as ucs_receive,
                        (SELECT IFNULL(SUM(receive_total), 0) 
                         FROM stm_ucs_kidney 
                         WHERE cid = ? AND datetimeadm = ?) as kidney_receive
                ', [$row->cid, $row->vstdate, $row->vsttime, $row->cid, $row->vstdate]);
                
                $receive = (float)($stm->ucs_receive ?? 0);
                if ($row->kidney > 0) {
                    $receive += (float)($stm->kidney_receive ?? 0);
                }

                // Balance = (receive + adj_inc - adj_dec) - debtor
                // To make Balance = 0, we need: adj_inc - adj_dec = debtor - receive
                $diff = (float)$row->debtor - $receive;

                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }

                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }

        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }

        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_109_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_109::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อย ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_217_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        foreach ($ids as $id) {
            $row = DB::table('debtor_1102050101_217 as d')
                ->leftJoin(DB::raw('(SELECT an, SUM(receive_total) - SUM(receive_ip_compensate_pay) as stm_total FROM stm_ucs GROUP BY an) stm'), 'stm.an', '=', 'd.an')
                ->leftJoin(DB::raw('(SELECT d2.an, SUM(sk.receive_total) as kidney_total FROM debtor_1102050101_217 d2 JOIN stm_ucs_kidney sk ON sk.cid = d2.cid AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate GROUP BY d2.an) k'), 'k.an', '=', 'd.an')
                ->where('d.an', $id)
                ->where('d.debtor_lock', 'Y')
                ->select('d.*', DB::raw('IFNULL(stm.stm_total,0) + IFNULL(k.kidney_total,0) as stm_receive'))
                ->first();

            if ($row) {
                $total_received = (float)($row->stm_receive ?? 0) + (float)($row->receive ?? 0);
                $diff = (float)$row->debtor - $total_received;
                
                if ($diff > 0) {
                    $adj_inc = $diff;
                    $adj_dec = 0;
                } else {
                    $adj_inc = 0;
                    $adj_dec = abs($diff);
                }

                \App\Models\Debtor_1102050101_217::where('an', $id)->update([
                    'adj_inc' => $adj_inc,
                    'adj_dec' => $adj_dec,
                    'adj_date' => $adj_date,
                    'adj_note' => $adj_note,
                ]);
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_301_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_301::where('vn', $id)->first();
            if ($row && $row->debtor_lock == 'Y') {
                $adj_val = (float)$row->debtor - (float)$row->receive;

                if ($adj_val >= 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว');
    }

    public function _1102050101_302_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_302::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = date('Y-m-d');
                $row->adj_note = 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_303_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_303::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $balance = (float)$row->receive - (float)$row->debtor;
                $adj_val = 0 - $balance;
                if ($adj_val > 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . count($ids) . ' รายการ');
    }

    public function _1102050101_304_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_304::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $request->bulk_adj_date ?: date('Y-m-d');
                $row->adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_307_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_307::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $balance = (float)$row->receive - (float)$row->debtor;
                $adj_val = 0 - $balance;
                if ($adj_val > 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . count($ids) . ' รายการ');
    }

    public function _1102050101_308_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_308::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;
                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . count($ids) . ' รายการ');
    }

    public function _1102050101_309_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_309::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm = DB::table('stm_sss_kidney')->where('cid', $row->cid)->where('vstdate', $row->vstdate)->sum(DB::raw('IFNULL(amount,0)+ IFNULL(epopay,0)+ IFNULL(epoadm,0)'));
                $balance = ((float)$row->receive + (float)$stm) - (float)$row->debtor;
                $adj_val = 0 - $balance;
                if ($adj_val > 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . count($ids) . ' รายการ');
    }

    public function _1102050101_310_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_310::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                // ดึงยอด STM ล่าสุดมาคำนวณด้วยเพื่อให้ยอดคงเหลือเป็น 0 จริงๆ
                $stm = DB::selectOne('
                    SELECT SUM(IFNULL(s.amount,0) + IFNULL(s.epopay,0) + IFNULL(s.epoadm,0)) AS stm_receive
                    FROM stm_sss_kidney s 
                    WHERE s.hn = ? AND s.vstdate BETWEEN ? AND ?
                ', [$row->hn, $row->regdate, $row->dchdate]);
                
                $total_receive = (float)$row->receive + (float)($stm->stm_receive ?? 0);
                $diff = (float)$row->debtor - $total_receive;

                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . count($ids) . ' รายการ');
    }

    public function _1102050101_401_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_401::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm1 = DB::table('stm_ofc')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where(DB::raw('LEFT(vsttime,5)'), substr($row->vsttime, 0, 5))->sum('receive_total');
                $stm2 = DB::table('stm_ofc_csop')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where(DB::raw('LEFT(vsttime,5)'), substr($row->vsttime, 0, 5))->where('sys', '<>', 'HD')->sum('amount');
                $stm3 = 0;
                if ($row->kidney > 0) {
                    $stm3 = DB::table('stm_ofc_csop')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where('sys', 'HD')->sum('amount');
                }
                
                $balance = ((float)$row->receive + (float)$stm1 + (float)$stm2 + (float)$stm3) - (float)$row->debtor;
                $adj_val = 0 - $balance;
                if ($adj_val > 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . count($ids) . ' รายการ');
    }

    public function _1102050101_402_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_402::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm = \DB::table('stm_ofc')->where('an', $id)->sum('receive_total');
                $cipn = \DB::table('stm_ofc_cipn')->where('an', $id)->sum('gtotal');
                $csop = \DB::table('stm_ofc_csop')->join('debtor_1102050101_402', 'debtor_1102050101_402.hn', '=', 'stm_ofc_csop.hn')
                    ->where('debtor_1102050101_402.an', $id)
                    ->whereBetween('stm_ofc_csop.vstdate', [\DB::raw('debtor_1102050101_402.regdate'), \DB::raw('debtor_1102050101_402.dchdate')])
                    ->sum('stm_ofc_csop.amount');
                
                $total_received = (float)($row->receive ?? 0) 
                    + (float)$stm 
                    + (float)$cipn 
                    + (float)($row->kidney > 0 ? $csop : 0)
                    + (float)($row->receive_manual ?? 0);
                $diff = (float)$row->debtor - $total_received;
                
                if ($diff >= 0) {
                    $adj_inc = $diff;
                    $adj_dec = 0;
                } else {
                    $adj_inc = 0;
                    $adj_dec = abs($diff);
                }

                \App\Models\Debtor_1102050101_402::where('an', $id)->update([
                    'adj_inc' => $adj_inc,
                    'adj_dec' => $adj_dec,
                    'adj_date' => $adj_date,
                    'adj_note' => $adj_note,
                ]);
                $adjusted_count++;
            }
        }
        
        if ($adjusted_count > 0) {
             return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
        } else {
             return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
    }

    public function _1102050101_501_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_501::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;
                $diff = (float)$row->debtor - $receive;
                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_502_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_502::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - $receive;
                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_503_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_503::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;
                $diff = (float)$row->debtor - $receive;
                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_504_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_504::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - $receive;
                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_701_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_701::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $balance = (float)$row->receive - (float)$row->debtor;
                $adj_val = 0 - $balance;
                if ($adj_val > 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_702_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_702::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $balance = (float)$row->receive - (float)$row->debtor;
                $adj_val = 0 - $balance;
                if ($adj_val > 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_704_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050101_704::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - $receive;
                if ($diff >= 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_106_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = DB::connection('hosxp')->selectOne("
                SELECT d.*, IFNULL(r.total_amount,0) AS total_bill
                FROM hrims.debtor_1102050102_106 d
                LEFT JOIN (
                    SELECT r.vn, SUM(r.total_amount) AS total_amount
                    FROM rcpt_print r
                    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                    WHERE a.rcpno IS NULL
                    GROUP BY r.vn
                ) r ON r.vn = d.vn
                WHERE d.vn = ?
            ", [$id]);

            if ($row && $row->debtor_lock == 'Y') {
                $receive = 0;
                if ($row->receive > 0) {
                    $receive = $row->receive;
                } else {
                    $receive = max(0, (float)$row->total_bill - (float)$row->rcpt_money);
                }

                $diff = (float)$row->debtor - (float)$receive;
                $update_data = [
                    'adj_date' => $adj_date,
                    'adj_note' => $adj_note
                ];

                if ($diff > 0) {
                    $update_data['adj_inc'] = $diff;
                    $update_data['adj_dec'] = 0;
                } else {
                    $update_data['adj_inc'] = 0;
                    $update_data['adj_dec'] = abs($diff);
                }

                \App\Models\Debtor_1102050102_106::where('vn', $id)->update($update_data);
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_107_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_107::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = date('Y-m-d');
                $row->adj_note = 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_108_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_108::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }


    public function _1102050102_110_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_110::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm1 = DB::table('stm_ofc')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where(DB::raw('LEFT(vsttime,5)'), substr($row->vsttime, 0, 5))->sum('receive_total');
                $stm2 = DB::table('stm_ofc_csop')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where(DB::raw('LEFT(vsttime,5)'), substr($row->vsttime, 0, 5))->where('sys', '<>', 'HD')->sum('amount');
                $stm3 = 0;
                if ($row->kidney > 0) {
                    $stm3 = DB::table('stm_ofc_csop')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where('sys', 'HD')->sum('amount');
                }
                
                $receive = (float)$row->receive + (float)$stm1 + (float)$stm2 + (float)$stm3;

                // Check current balance
                $current_balance = ($receive + (float)($row->adj_inc ?? 0) - (float)($row->adj_dec ?? 0)) - (float)$row->debtor;
                if (abs($current_balance) < 0.01) {
                    continue;
                }

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        
        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_111_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_111::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm_total = \DB::table('stm_ofc')->where('an', $id)->sum('receive_total');
                $stm_cipn = \DB::table('stm_ofc_cipn')->where('an', $id)->sum('gtotal');
                $stm_csop = 0;
                if ($row->kidney > 0) {
                    $stm_csop = \DB::table('stm_ofc_csop')
                        ->where('hn', $row->hn)
                        ->whereBetween('vstdate', [$row->regdate, $row->dchdate])
                        ->sum('amount');
                }
                $receive = (float)$row->receive + (float)$stm_total + (float)$stm_cipn + (float)$stm_csop;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = date('Y-m-d');
                $row->adj_note = 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_602_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_602::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                // Detect receive field (ไม่เอา ppfs มาคำนวณตามที่แจ้ง)
                $receive = (float)$row->receive;

                // Check current balance
                $current_balance = ($receive + (float)($row->adj_inc ?? 0) - (float)($row->adj_dec ?? 0)) - (float)$row->debtor;
                if (abs($current_balance) < 0.01) {
                    continue;
                }

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        
        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_603_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_603::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $receive = (float)$row->receive;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = date('Y-m-d');
                $row->adj_note = 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_801_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_801::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm_lgo = \DB::table('stm_lgo')->where('cid', $row->cid)->where('vstdate', $row->vstdate)
                    ->where(\DB::raw('LEFT(vsttime,5)'), substr($row->vsttime, 0, 5))
                    ->sum('compensate_treatment');
                $stm_kidney = 0;
                if ($row->kidney > 0) {
                     $stm_kidney = \DB::table('stm_lgo_kidney')->where('cid', $row->cid)
                        ->where('datetimeadm', $row->vstdate)
                        ->sum('receive_total');
                }
                $receive = (float)$row->receive + (float)$stm_lgo + (float)$stm_kidney;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        
        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_802_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_802::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm_lgo = \DB::table('stm_lgo')->where('an', $id)->sum('pay');
                $stm_kidney = \DB::table('stm_lgo_kidney')->where('cid', $row->cid)
                    ->whereBetween('datetimeadm', [$row->regdate, $row->dchdate])
                    ->sum('compensate_kidney');
                $receive = (float)$row->receive + (float)$stm_lgo + (float)$stm_kidney;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = date('Y-m-d');
                $row->adj_note = 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_803_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_803::where('vn', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm1 = DB::table('stm_ofc')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where(DB::raw('LEFT(vsttime,5)'), substr($row->vsttime, 0, 5))->sum('receive_total');
                $stm2 = DB::table('stm_ofc_csop')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where(DB::raw('LEFT(vsttime,5)'), substr($row->vsttime, 0, 5))->where('sys', '<>', 'HD')->sum('amount');
                $stm3 = 0;
                if ($row->kidney > 0) {
                    $stm3 = DB::table('stm_ofc_csop')->where('hn', $row->hn)->where('vstdate', $row->vstdate)->where('sys', 'HD')->sum('amount');
                }
                
                $receive = (float)$row->receive + (float)$stm1 + (float)$stm2 + (float)$stm3;
                
                // Check current balance
                $current_balance = ($receive + (float)($row->adj_inc ?? 0) - (float)($row->adj_dec ?? 0)) - (float)$row->debtor;
                if (abs($current_balance) < 0.01) {
                    continue;
                }

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = $adj_date;
                $row->adj_note = $adj_note;
                $row->save();
                $adjusted_count++;
            }
        }
        
        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_804_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        $adjusted_count = 0;
        foreach ($ids as $id) {
            $row = \App\Models\Debtor_1102050102_804::where('an', $id)->where('debtor_lock', 'Y')->first();
            if ($row) {
                $stm_total = \DB::table('stm_ofc')->where('an', $id)->sum('receive_total');
                $stm_cipn = \DB::table('stm_ofc_cipn')->where('an', $id)->sum('gtotal');
                $stm_csop = 0;
                if ($row->kidney > 0) {
                    $stm_csop = \DB::table('stm_ofc_csop')
                        ->where('hn', $row->hn)
                        ->whereBetween('vstdate', [$row->regdate, $row->dchdate])
                        ->sum('amount');
                }
                $receive = (float)$row->receive + (float)$stm_total + (float)$stm_cipn + (float)$stm_csop;

                $diff = (float)$row->debtor - (float)$receive;
                if ($diff > 0) {
                    $row->adj_inc = $diff;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($diff);
                }
                $row->adj_date = date('Y-m-d');
                $row->adj_note = 'Bulk Adjustment to Balance 0';
                $row->save();
                $adjusted_count++;
            }
        }
        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }
}
