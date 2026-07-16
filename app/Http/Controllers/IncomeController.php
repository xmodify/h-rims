<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_emr !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'งานเวชระเบียน'], 403);
                }
                return $next($request);
            }
        ]);
    }

    public function opd_income(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

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

        // ดึงหมวดค่ารักษาตั้งต้นทั้งหมดก่อนเพื่อให้แสดงครบทุกหมวด
        $categories = DB::connection('hosxp')
            ->table('drg_chrgitem')
            ->select('drg_chrgitem_id AS group_id', 'drg_chrgitem_name AS group_name')
            ->orderBy('drg_chrgitem_id')
            ->get()
            ->keyBy('group_id')
            ->toArray();

        // ปลดล็อก Session Lock เพื่อให้หน้าเว็บอื่นหรือปุ่มกดเมนูอื่นๆ สามารถโหลดได้ทันทีโดยไม่ติดคิวคอยสคริปต์นี้ทำงานเสร็จ
        session()->save();

        // Query ดึงรายได้รวมของ OPD แยกรายเดือน รายสิทธิ์ และรายหมวด
        $raw_data = DB::connection('hosxp')->select("
            SELECT 
                t.month_num,
                i.drg_chrgitem_id AS group_id,
                SUM(CASE WHEN p.hipdata_code IN ('UCS', 'WEL') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS ucs,
                SUM(CASE WHEN p.hipdata_code = 'STP' AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS stp,
                SUM(CASE WHEN p.hipdata_code = 'OFC' AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS ofc,
                SUM(CASE WHEN p.hipdata_code = 'LGO' AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS lgo,
                SUM(CASE WHEN p.hipdata_code IN ('GOF', 'BMT', 'KKT', 'SRT') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS gov,
                SUM(CASE WHEN p.hipdata_code IN ('SSS', 'SSI') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS sss,
                SUM(CASE WHEN p.hipdata_code IN ('NRD', 'NRH', 'FWF') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS immigrant,
                SUM(CASE WHEN p.hipdata_code NOT IN ('UCS', 'WEL', 'OFC', 'LGO', 'GOF', 'BMT', 'KKT', 'SRT', 'SSS', 'SSI', 'NRD', 'NRH', 'FWF', 'STP') OR p.hipdata_code IS NULL OR t.paidst IN ('01', '03') THEN t.sum_price ELSE 0 END) AS others,
                SUM(t.sum_price) AS total
            FROM (
                SELECT 
                    MONTH(rxdate) AS month_num,
                    income,
                    pttype,
                    paidst,
                    SUM(sum_price) AS sum_price
                FROM opitemrece
                WHERE rxdate BETWEEN ? AND ?
                  AND an IS NULL
                GROUP BY MONTH(rxdate), income, pttype, paidst
            ) t
            INNER JOIN income i ON i.income = t.income
            INNER JOIN pttype p ON p.pttype = t.pttype
            GROUP BY t.month_num, i.drg_chrgitem_id
        ", [$start_date, $end_date]);

        // จัดการโครงสร้างข้อมูลรายเดือนใน PHP
        $months_list = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $year_short = (int)substr($budget_year, -2);
        $prev_year_short = $year_short - 1;
        $months_names = [
            10 => "ต.ค. {$prev_year_short}",
            11 => "พ.ย. {$prev_year_short}",
            12 => "ธ.ค. {$prev_year_short}",
            1 => "ม.ค. {$year_short}",
            2 => "ก.พ. {$year_short}",
            3 => "มี.ค. {$year_short}",
            4 => "เม.ย. {$year_short}",
            5 => "พ.ค. {$year_short}",
            6 => "มิ.ย. {$year_short}",
            7 => "ก.ค. {$year_short}",
            8 => "ส.ค. {$year_short}",
            9 => "ก.ย. {$year_short}"
        ];

        // หากเป็นการเปิดหน้าจอครั้งแรก (ไม่ใช่ AJAX) ให้ส่งเฉพาะโครงสร้าง Blade กลับไปทันที
        if (!$request->ajax() && !$request->wantsJson()) {
            return view('opd.opd_income', compact(
                'budget_year_select',
                'budget_year',
                'categories',
                'months_names'
            ));
        }

        $report_data = [];
        // สร้างโครงสร้างเปล่าก่อน
        foreach ($months_list as $m) {
            $report_data[$m] = [];
            foreach ($categories as $catId => $cat) {
                $report_data[$m][$catId] = (object)[
                    'group_id' => $catId,
                    'group_name' => $cat->group_name,
                    'ucs' => 0, 'stp' => 0, 'ofc' => 0, 'lgo' => 0, 'gov' => 0, 'sss' => 0, 'immigrant' => 0, 'others' => 0, 'total' => 0
                ];
            }
        }

        // ยอดรวมทั้งปี
        $yearly_data = [];
        foreach ($categories as $catId => $cat) {
            $yearly_data[$catId] = (object)[
                'group_id' => $catId,
                'group_name' => $cat->group_name,
                'ucs' => 0, 'stp' => 0, 'ofc' => 0, 'lgo' => 0, 'gov' => 0, 'sss' => 0, 'immigrant' => 0, 'others' => 0, 'total' => 0
            ];
        }

        // กรอกข้อมูลจริงลงไป
        foreach ($raw_data as $row) {
            $m = (int)$row->month_num;
            $catId = (int)$row->group_id;
            if (isset($report_data[$m][$catId])) {
                $report_data[$m][$catId]->ucs = round((double)$row->ucs, 2);
                $report_data[$m][$catId]->stp = round((double)$row->stp, 2);
                $report_data[$m][$catId]->ofc = round((double)$row->ofc, 2);
                $report_data[$m][$catId]->lgo = round((double)$row->lgo, 2);
                $report_data[$m][$catId]->gov = round((double)$row->gov, 2);
                $report_data[$m][$catId]->sss = round((double)$row->sss, 2);
                $report_data[$m][$catId]->immigrant = round((double)$row->immigrant, 2);
                $report_data[$m][$catId]->others = round((double)$row->others, 2);
                $report_data[$m][$catId]->total = round((double)$row->total, 2);
            }

            if (isset($yearly_data[$catId])) {
                $yearly_data[$catId]->ucs = round($yearly_data[$catId]->ucs + (double)$row->ucs, 2);
                $yearly_data[$catId]->stp = round($yearly_data[$catId]->stp + (double)$row->stp, 2);
                $yearly_data[$catId]->ofc = round($yearly_data[$catId]->ofc + (double)$row->ofc, 2);
                $yearly_data[$catId]->lgo = round($yearly_data[$catId]->lgo + (double)$row->lgo, 2);
                $yearly_data[$catId]->gov = round($yearly_data[$catId]->gov + (double)$row->gov, 2);
                $yearly_data[$catId]->sss = round($yearly_data[$catId]->sss + (double)$row->sss, 2);
                $yearly_data[$catId]->immigrant = round($yearly_data[$catId]->immigrant + (double)$row->immigrant, 2);
                $yearly_data[$catId]->others = round($yearly_data[$catId]->others + (double)$row->others, 2);
                $yearly_data[$catId]->total = round($yearly_data[$catId]->total + (double)$row->total, 2);
            }
        }

        // ข้อมูลสำหรับวาดกราฟเส้นแนวโน้มรายเดือนของแต่ละหมวด
        // โครงสร้าง $chart_data[$catId] = array(12 values corresponding to Oct-Sep)
        $chart_data = [];
        
        // เพิ่มข้อมูลของหมวดทั้งหมดรวมกัน (Total of all categories)
        $chart_data['all'] = [];
        foreach ($months_list as $m) {
            $sum_month = 0;
            foreach ($categories as $catId => $cat) {
                $sum_month += $report_data[$m][$catId]->total;
            }
            $chart_data['all'][] = round($sum_month, 2);
        }

        foreach ($categories as $catId => $cat) {
            $chart_data[$catId] = [];
            foreach ($months_list as $m) {
                $chart_data[$catId][] = round($report_data[$m][$catId]->total, 2);
            }
        }

        // หากเป็น AJAX ดึงข้อมูลหลังโหลดหน้าเสร็จ
        $table_html = view('opd.opd_income_table', compact(
            'categories',
            'report_data',
            'yearly_data',
            'months_list',
            'months_names'
        ))->render();

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'chart_data' => $chart_data,
            'categories' => $categories
        ]);
    }

    public function ipd_income(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

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

        // ดึงหมวดค่ารักษาตั้งต้นทั้งหมดก่อนเพื่อให้แสดงครบทุกหมวด
        $categories = DB::connection('hosxp')
            ->table('drg_chrgitem')
            ->select('drg_chrgitem_id AS group_id', 'drg_chrgitem_name AS group_name')
            ->orderBy('drg_chrgitem_id')
            ->get()
            ->keyBy('group_id')
            ->toArray();

        // ปลดล็อก Session Lock เพื่อให้หน้าเว็บอื่นหรือปุ่มกดเมนูอื่นๆ สามารถโหลดได้ทันทีโดยไม่ติดคิวคอยสคริปต์นี้ทำงานเสร็จ
        session()->save();

        // Query ดึงรายได้รวมของ IPD แยกรายเดือน รายสิทธิ์ และรายหมวด (เชื่อมตาราง ipt ตามวันที่จำหน่ายคนไข้ dchdate)
        $raw_data = DB::connection('hosxp')->select("
            SELECT 
                t.month_num,
                i.drg_chrgitem_id AS group_id,
                SUM(CASE WHEN p.hipdata_code IN ('UCS', 'WEL') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS ucs,
                SUM(CASE WHEN p.hipdata_code = 'STP' AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS stp,
                SUM(CASE WHEN p.hipdata_code = 'OFC' AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS ofc,
                SUM(CASE WHEN p.hipdata_code = 'LGO' AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS lgo,
                SUM(CASE WHEN p.hipdata_code IN ('GOF', 'BMT', 'KKT', 'SRT') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS gov,
                SUM(CASE WHEN p.hipdata_code IN ('SSS', 'SSI') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS sss,
                SUM(CASE WHEN p.hipdata_code IN ('NRD', 'NRH', 'FWF') AND t.paidst NOT IN ('01', '03') THEN t.sum_price ELSE 0 END) AS immigrant,
                SUM(CASE WHEN p.hipdata_code NOT IN ('UCS', 'WEL', 'OFC', 'LGO', 'GOF', 'BMT', 'KKT', 'SRT', 'SSS', 'SSI', 'NRD', 'NRH', 'FWF', 'STP') OR p.hipdata_code IS NULL OR t.paidst IN ('01', '03') THEN t.sum_price ELSE 0 END) AS others,
                SUM(t.sum_price) AS total
            FROM (
                SELECT 
                    MONTH(ipt.dchdate) AS month_num,
                    o.income,
                    o.pttype,
                    o.paidst,
                    SUM(o.sum_price) AS sum_price
                FROM opitemrece o
                INNER JOIN ipt ON ipt.an = o.an
                WHERE ipt.dchdate BETWEEN ? AND ?
                  AND o.an IS NOT NULL 
                  AND o.an <> ''
                GROUP BY MONTH(ipt.dchdate), o.income, o.pttype, o.paidst
            ) t
            INNER JOIN income i ON i.income = t.income
            INNER JOIN pttype p ON p.pttype = t.pttype
            GROUP BY t.month_num, i.drg_chrgitem_id
        ", [$start_date, $end_date]);

        // จัดการโครงสร้างข้อมูลรายเดือนใน PHP
        $months_list = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $year_short = (int)substr($budget_year, -2);
        $prev_year_short = $year_short - 1;
        $months_names = [
            10 => "ต.ค. {$prev_year_short}",
            11 => "พ.ย. {$prev_year_short}",
            12 => "ธ.ค. {$prev_year_short}",
            1 => "ม.ค. {$year_short}",
            2 => "ก.พ. {$year_short}",
            3 => "มี.ค. {$year_short}",
            4 => "เม.ย. {$year_short}",
            5 => "พ.ค. {$year_short}",
            6 => "มิ.ย. {$year_short}",
            7 => "ก.ค. {$year_short}",
            8 => "ส.ค. {$year_short}",
            9 => "ก.ย. {$year_short}"
        ];

        // หากเป็นการเปิดหน้าจอครั้งแรก (ไม่ใช่ AJAX) ให้ส่งเฉพาะโครงสร้าง Blade กลับไปทันที
        if (!$request->ajax() && !$request->wantsJson()) {
            return view('ipd.ipd_income', compact(
                'budget_year_select',
                'budget_year',
                'categories',
                'months_names'
            ));
        }

        $report_data = [];
        // สร้างโครงสร้างเปล่าก่อน
        foreach ($months_list as $m) {
            $report_data[$m] = [];
            foreach ($categories as $catId => $cat) {
                $report_data[$m][$catId] = (object)[
                    'group_id' => $catId,
                    'group_name' => $cat->group_name,
                    'ucs' => 0, 'stp' => 0, 'ofc' => 0, 'lgo' => 0, 'gov' => 0, 'sss' => 0, 'immigrant' => 0, 'others' => 0, 'total' => 0
                ];
            }
        }

        // ยอดรวมทั้งปี
        $yearly_data = [];
        foreach ($categories as $catId => $cat) {
            $yearly_data[$catId] = (object)[
                'group_id' => $catId,
                'group_name' => $cat->group_name,
                'ucs' => 0, 'stp' => 0, 'ofc' => 0, 'lgo' => 0, 'gov' => 0, 'sss' => 0, 'immigrant' => 0, 'others' => 0, 'total' => 0
            ];
        }

        // กรอกข้อมูลจริงลงไป
        foreach ($raw_data as $row) {
            $m = (int)$row->month_num;
            $catId = (int)$row->group_id;
            if (isset($report_data[$m][$catId])) {
                $report_data[$m][$catId]->ucs = round((double)$row->ucs, 2);
                $report_data[$m][$catId]->stp = round((double)$row->stp, 2);
                $report_data[$m][$catId]->ofc = round((double)$row->ofc, 2);
                $report_data[$m][$catId]->lgo = round((double)$row->lgo, 2);
                $report_data[$m][$catId]->gov = round((double)$row->gov, 2);
                $report_data[$m][$catId]->sss = round((double)$row->sss, 2);
                $report_data[$m][$catId]->immigrant = round((double)$row->immigrant, 2);
                $report_data[$m][$catId]->others = round((double)$row->others, 2);
                $report_data[$m][$catId]->total = round((double)$row->total, 2);
            }

            if (isset($yearly_data[$catId])) {
                $yearly_data[$catId]->ucs = round($yearly_data[$catId]->ucs + (double)$row->ucs, 2);
                $yearly_data[$catId]->stp = round($yearly_data[$catId]->stp + (double)$row->stp, 2);
                $yearly_data[$catId]->ofc = round($yearly_data[$catId]->ofc + (double)$row->ofc, 2);
                $yearly_data[$catId]->lgo = round($yearly_data[$catId]->lgo + (double)$row->lgo, 2);
                $yearly_data[$catId]->gov = round($yearly_data[$catId]->gov + (double)$row->gov, 2);
                $yearly_data[$catId]->sss = round($yearly_data[$catId]->sss + (double)$row->sss, 2);
                $yearly_data[$catId]->immigrant = round($yearly_data[$catId]->immigrant + (double)$row->immigrant, 2);
                $yearly_data[$catId]->others = round($yearly_data[$catId]->others + (double)$row->others, 2);
                $yearly_data[$catId]->total = round($yearly_data[$catId]->total + (double)$row->total, 2);
            }
        }

        // ข้อมูลสำหรับวาดกราฟเส้นแนวโน้มรายเดือนของแต่ละหมวด
        $chart_data = [];

        // เพิ่มข้อมูลของหมวดทั้งหมดรวมกัน (Total of all categories)
        $chart_data['all'] = [];
        foreach ($months_list as $m) {
            $sum_month = 0;
            foreach ($categories as $catId => $cat) {
                $sum_month += $report_data[$m][$catId]->total;
            }
            $chart_data['all'][] = round($sum_month, 2);
        }

        foreach ($categories as $catId => $cat) {
            $chart_data[$catId] = [];
            foreach ($months_list as $m) {
                $chart_data[$catId][] = round($report_data[$m][$catId]->total, 2);
            }
        }

        // หากเป็น AJAX ดึงข้อมูลหลังโหลดหน้าเสร็จ
        $table_html = view('ipd.ipd_income_table', compact(
            'categories',
            'report_data',
            'yearly_data',
            'months_list',
            'months_names'
        ))->render();

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'chart_data' => $chart_data,
            'categories' => $categories
        ]);
    }
}
