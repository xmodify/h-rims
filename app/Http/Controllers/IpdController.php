<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class IpdController extends Controller
{
    //Check Login
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
        ])->except(['wait_doctor_dchsummary']);
    }
    #################################################################################################################################
//Create wait_doctor_dchsummary
    public function wait_doctor_dchsummary(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date');
        $end_date = $request->end_date ?: Session::get('end_date');

        // Query 1: Patients waiting for doctor to write chart summary (diag_text_list is empty)
        $non_diagtext_list = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,i.an,iptdiag.icd10,a.diag_text_list,d.`name` AS owner_doctor_name,
            i.dchdate,TIMESTAMPDIFF(day,i.dchdate,DATE(NOW())) AS dch_day
            FROM ipt i
            LEFT JOIN ward w ON w.ward=i.ward        
            LEFT JOIN iptdiag ON iptdiag.an = i.an AND iptdiag.diagtype = "1"
            LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
            LEFT JOIN doctor d ON d.`code` = il.doctor
            LEFT JOIN an_stat a ON a.an=i.an
            WHERE i.dchdate BETWEEN ? AND ?
            AND (a.diag_text_list ="" OR a.diag_text_list IS NULL)
            GROUP BY i.an
            ORDER BY d.`name`,dch_day DESC', [$start_date, $end_date]);

        // Query 2: Patients waiting for ICD10 coding (has diag_text_list but no icd10)
        $non_icd10_list = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,i.an,iptdiag.icd10,a.diag_text_list,d.`name` AS owner_doctor_name,
            i.dchdate,TIMESTAMPDIFF(day,i.dchdate,DATE(NOW())) AS dch_day
            FROM ipt i
            LEFT JOIN ward w ON w.ward=i.ward        
            LEFT JOIN iptdiag ON iptdiag.an = i.an AND iptdiag.diagtype = "1"
            LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
            LEFT JOIN doctor d ON d.`code` = il.doctor
            LEFT JOIN an_stat a ON a.an=i.an
            WHERE i.dchdate BETWEEN ? AND ?
            AND (a.diag_text_list IS NOT NULL AND a.diag_text_list <> "")
            AND (iptdiag.icd10 ="" OR iptdiag.icd10 IS NULL)
            GROUP BY i.an
            ORDER BY d.`name`,dch_day DESC', [$start_date, $end_date]);

        // Query 3: Summary count by doctor for patients waiting for chart summary
        $non_diagtext_sum = DB::connection('hosxp')->select('
            SELECT d.`name` AS owner_doctor_name,COUNT(i.an) AS total
            FROM ipt i     
            LEFT JOIN iptdiag ON iptdiag.an = i.an AND iptdiag.diagtype = "1"
            LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
            LEFT JOIN doctor d ON d.`code` = il.doctor
            LEFT JOIN an_stat a ON a.an=i.an
            WHERE i.dchdate BETWEEN ? AND ?
            AND (a.diag_text_list ="" OR a.diag_text_list IS NULL)
            GROUP BY d.`name` 
            ORDER BY total DESC', [$start_date, $end_date]);

        // Query 4: Summary count by doctor for patients waiting for ICD10
        $non_icd10_sum = DB::connection('hosxp')->select('
            SELECT d.`name` AS owner_doctor_name,COUNT(i.an) AS total
            FROM ipt i     
            LEFT JOIN iptdiag ON iptdiag.an = i.an AND iptdiag.diagtype = "1"
            LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
            LEFT JOIN doctor d ON d.`code` = il.doctor
            LEFT JOIN an_stat a ON a.an=i.an
            WHERE i.dchdate BETWEEN ? AND ?
            AND (a.diag_text_list IS NOT NULL AND a.diag_text_list <> "")
            AND (iptdiag.icd10 ="" OR iptdiag.icd10 IS NULL)
            GROUP BY d.`name` 
            ORDER BY total DESC', [$start_date, $end_date]);

        // Prepare chart data structure
        // Combine all unique doctor names from both queries
        $all_doctors = collect($non_diagtext_sum)->pluck('owner_doctor_name')
            ->merge(collect($non_icd10_sum)->pluck('owner_doctor_name'))
            ->unique()
            ->values()
            ->toArray();

        // Create associative arrays for quick lookup
        $diagtext_by_doctor = collect($non_diagtext_sum)->pluck('total', 'owner_doctor_name')->toArray();
        $icd10_by_doctor = collect($non_icd10_sum)->pluck('total', 'owner_doctor_name')->toArray();

        // Build parallel arrays for chart
        $chart_doctors = [];
        $chart_non_diagtext = [];
        $chart_non_icd10 = [];

        foreach ($all_doctors as $doctor) {
            $chart_doctors[] = $doctor ?? 'ไม่ระบุแพทย์';
            $chart_non_diagtext[] = $diagtext_by_doctor[$doctor] ?? 0;
            $chart_non_icd10[] = $icd10_by_doctor[$doctor] ?? 0;
        }

        // Prepare chart data array 
        $chart_data = [
            'doctors' => $chart_doctors,
            'non_diagtext' => $chart_non_diagtext,
            'non_icd10' => $chart_non_icd10
        ];

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->save();

        return view('home_detail.ipd_non_dchsummary', compact('non_diagtext_list', 'non_icd10_list', 'chart_data'));
    }

    //Create wait_icd_coder
    public function wait_icd_coder(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date');
        $end_date = $request->end_date ?: Session::get('end_date');

        $sql = DB::connection('hosxp')->select('
                SELECT COUNT(an) AS sum_discharge,
                SUM(CASE WHEN (diag_text_list IS NULL OR diag_text_list ="") THEN 1 ELSE 0 END) AS sum_wait_dchsummary,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"") AND (pdx ="" OR pdx IS NULL) THEN 1 ELSE 0 END) AS sum_wait_icd_coder,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"" OR dx2 IS NOT NULL OR dx2 <>"" OR dx3 IS NOT NULL OR dx3 <>""
                    OR dx4 IS NOT NULL OR dx4 <>"" OR dx5 IS NOT NULL OR dx5 <>"") AND pdx <>"" AND pdx IS NOT NULL THEN 1 ELSE 0 END) AS sum_dchsummary,
                SUM(CASE WHEN (dx1_audit IS NOT NULL OR dx1_audit <>"" OR dx2_audit IS NOT NULL OR dx2_audit <>"" OR dx3_audit IS NOT NULL OR dx3_audit <>""
                    OR dx4_audit IS NOT NULL OR dx4_audit <>"" OR dx5_audit IS NOT NULL OR dx5_audit <>"") THEN 1 ELSE 0 END) AS sum_dchsummary_audit
                FROM (SELECT i.an,i.regdate,i.dchdate,id1.diag_text AS dx1,id2.diag_text AS dx2,id3.diag_text AS dx3,id4.diag_text AS dx4,id5.diag_text AS dx5,
                id1.audit_diag_text AS dx1_audit,id2.audit_diag_text AS dx2_audit,id3.audit_diag_text AS dx3_audit,id4.audit_diag_text AS dx4_audit,
                id5.audit_diag_text AS dx5_audit,a.pdx,a.diag_text_list,i.adjrw
                FROM ipt i
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5
                LEFT JOIN an_stat a ON a.an=i.an
                WHERE i.dchdate BETWEEN ? AND ?
                GROUP BY i.an) AS a', [$start_date, $end_date]);
        foreach ($sql as $row) {
            $sum_discharge = $row->sum_discharge;
            $sum_wait_dchsummary = $row->sum_wait_dchsummary;
            $sum_wait_icd_coder = $row->sum_wait_icd_coder;
            $sum_dchsummary = $row->sum_dchsummary;
            $sum_dchsummary_audit = $row->sum_dchsummary_audit;
        }

        $k_value = (float) DB::table('main_setting')->where('name', 'k_value')->value('value');
        $base_rate = (float) DB::table('main_setting')->where('name', 'base_rate')->value('value');
        $base_rate2 = (float) DB::table('main_setting')->where('name', 'base_rate2')->value('value');
        $base_rate_ofc = (float) DB::table('main_setting')->where('name', 'base_rate_ofc')->value('value');
        $base_rate_lgo = (float) DB::table('main_setting')->where('name', 'base_rate_lgo')->value('value');
        $base_rate_sss = (float) DB::table('main_setting')->where('name', 'base_rate_sss')->value('value');
        $adjrw = DB::connection('hosxp')->select('
            SELECT COUNT(an) AS sum_discharge,SUM(adjrw) AS rw_all,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y") 
			    THEN adjrw ELSE 0 END) AS rw_ucs,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
			    THEN adjrw * ? * ? ELSE 0 END) AS rw_receive_ucs,
		    SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE (hmain_ucs IS NULL OR hmain_ucs ="")) 
			    THEN adjrw ELSE 0 END) AS rw_ucs2,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE (hmain_ucs IS NULL OR hmain_ucs =""))
			    THEN adjrw * ? ELSE 0 END) AS rw_receive_ucs2,
		    SUM(CASE WHEN hipdata_code = "OFC" THEN adjrw ELSE 0 END) AS rw_ofc,
            SUM(CASE WHEN hipdata_code = "OFC" THEN adjrw * ? ELSE 0 END) AS rw_receive_ofc,
		    SUM(CASE WHEN hipdata_code = "LGO" THEN adjrw ELSE 0 END) AS rw_lgo,
            SUM(CASE WHEN hipdata_code = "LGO" THEN adjrw * ? ELSE 0 END) AS rw_receive_lgo,
		    SUM(CASE WHEN hipdata_code = "SSS" THEN adjrw ELSE 0 END) AS rw_sss,
            SUM(CASE WHEN hipdata_code = "SSS" THEN adjrw * ? ELSE 0 END) AS rw_receive_sss
            FROM (SELECT i.an,i.regdate,i.dchdate,p.hipdata_code,ip.pttype,ip.hospmain,a.pdx,i.adjrw
                FROM ipt i	
		        LEFT JOIN ipt_pttype ip ON ip.an=i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN an_stat a ON a.an = i.an		        
                WHERE i.dchdate BETWEEN ? AND ?
                GROUP BY i.an) AS a ', [$k_value, $base_rate, $base_rate2, $base_rate_ofc, $base_rate_lgo, $base_rate_sss, $start_date, $end_date]);
        foreach ($adjrw as $row) {
            $rw_all = $row->rw_all;
            $rw_ucs = $row->rw_ucs;
            $rw_receive_ucs = $row->rw_receive_ucs;
            $rw_ucs2 = $row->rw_ucs2;
            $rw_receive_ucs2 = $row->rw_receive_ucs2;
            $rw_ofc = $row->rw_ofc;
            $rw_receive_ofc = $row->rw_receive_ofc;
            $rw_lgo = $row->rw_lgo;
            $rw_receive_lgo = $row->rw_receive_lgo;
            $rw_sss = $row->rw_sss;
            $rw_receive_sss = $row->rw_receive_sss;
        }

        $data = DB::connection('hosxp')->select('
                SELECT w.`name` AS ward,i.an,i.regdate,i.dchdate,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,a.admdate,d.`name` AS owner_doctor_name,
                id1.diag_text AS dx1,d1.`name` AS dx1_doctor,id1.audit_diag_text AS dx1_audit,d1_a.`name` AS dx1_doctor_audit,
                id2.diag_text AS dx2,d2.`name` AS dx2_doctor,id2.audit_diag_text AS dx2_audit,d2_a.`name` AS dx2_doctor_audit,
                id3.diag_text AS dx3,d3.`name` AS dx3_doctor,id3.audit_diag_text AS dx3_audit,d3_a.`name` AS dx3_doctor_audit,
                id4.diag_text AS dx4,d4.`name` AS dx4_doctor,id4.audit_diag_text AS dx4_audit,d4_a.`name` AS dx4_doctor_audit,
                id5.diag_text AS dx5,d5.`name` AS dx5_doctor,id5.audit_diag_text AS dx5_audit,d5_a.`name` AS dx5_doctor_audit,
                id_t1.icd10 AS icd10_t1, GROUP_CONCAT(DISTINCT id_t2.icd10) AS icd10_t2 ,GROUP_CONCAT(DISTINCT id_t3.icd10) AS icd10_t3,
                GROUP_CONCAT(DISTINCT id_t4.icd10) AS icd10_t4,GROUP_CONCAT(DISTINCT id_t5.icd10) AS icd10_t5,i.adjrw      
                FROM ipt i
                LEFT JOIN patient pt ON pt.hn=i.hn
                LEFT JOIN pttype p ON p.pttype=i.pttype
                LEFT JOIN ward w ON w.ward=i.ward                    
                LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
                LEFT JOIN doctor d ON d.`code` = il.doctor						
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1	
                LEFT JOIN doctor d1 ON d1.`code` = id1.doctor_code	
                LEFT JOIN doctor d1_a ON d1_a.`code` = id1.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2	
                LEFT JOIN doctor d2 ON d2.`code` = id2.doctor_code
                LEFT JOIN doctor d2_a ON d2_a.`code` = id2.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3	
                LEFT JOIN doctor d3 ON d3.`code` = id3.doctor_code	
                LEFT JOIN doctor d3_a ON d3_a.`code` = id3.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4	
                LEFT JOIN doctor d4 ON d4.`code` = id4.doctor_code	
                LEFT JOIN doctor d4_a ON d4_a.`code` = id4.audit_doctor_code			
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5	
                LEFT JOIN doctor d5 ON d5.`code` = id5.doctor_code		
                LEFT JOIN doctor d5_a ON d5_a.`code` = id5.audit_doctor_code						
                LEFT JOIN an_stat a ON a.an=i.an			
                LEFT JOIN iptdiag id_t1 ON id_t1.an=i.an AND id_t1.diagtype = 1
                LEFT JOIN iptdiag id_t2 ON id_t2.an=i.an AND id_t2.diagtype = 2
                LEFT JOIN iptdiag id_t3 ON id_t3.an=i.an AND id_t3.diagtype = 3
                LEFT JOIN iptdiag id_t4 ON id_t4.an=i.an AND id_t4.diagtype = 4
                LEFT JOIN iptdiag id_t5 ON id_t5.an=i.an AND id_t5.diagtype = 5
                WHERE i.dchdate BETWEEN ? AND ?
                AND (id1.diag_text <> "" OR id1.diag_text IS NOT NULL) AND (a.pdx = "" OR a.pdx IS NULL)                
                GROUP BY i.an', [$start_date, $end_date]);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->save();

        return view('ipd.dchsummary', compact(
            'start_date',
            'end_date',
            'sum_discharge',
            'sum_wait_dchsummary',
            'sum_wait_icd_coder',
            'sum_dchsummary',
            'sum_dchsummary_audit',
            'rw_all',
            'rw_ucs',
            'rw_receive_ucs',
            'rw_ucs2',
            'rw_receive_ucs2',
            'rw_ofc',
            'rw_receive_ofc',
            'rw_lgo',
            'rw_receive_lgo',
            'rw_sss',
            'rw_receive_sss',
            'data'
        ));
    }

    //Create dchsummary
    public function dchsummary(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date');
        $end_date = $request->end_date ?: Session::get('end_date');

        $sql = DB::connection('hosxp')->select('
            SELECT COUNT(an) AS sum_discharge,
            SUM(CASE WHEN (diag_text_list IS NULL OR diag_text_list ="") THEN 1 ELSE 0 END) AS sum_wait_dchsummary,
            SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"") AND (pdx ="" OR pdx IS NULL) THEN 1 ELSE 0 END) AS sum_wait_icd_coder,
            SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"" OR dx2 IS NOT NULL OR dx2 <>"" OR dx3 IS NOT NULL OR dx3 <>""
                OR dx4 IS NOT NULL OR dx4 <>"" OR dx5 IS NOT NULL OR dx5 <>"") AND pdx <>"" AND pdx IS NOT NULL THEN 1 ELSE 0 END) AS sum_dchsummary,
            SUM(CASE WHEN (dx1_audit IS NOT NULL OR dx1_audit <>"" OR dx2_audit IS NOT NULL OR dx2_audit <>"" OR dx3_audit IS NOT NULL OR dx3_audit <>""
                OR dx4_audit IS NOT NULL OR dx4_audit <>"" OR dx5_audit IS NOT NULL OR dx5_audit <>"") THEN 1 ELSE 0 END) AS sum_dchsummary_audit
            FROM (SELECT i.an,i.regdate,i.dchdate,id1.diag_text AS dx1,id2.diag_text AS dx2,id3.diag_text AS dx3,id4.diag_text AS dx4,id5.diag_text AS dx5,
            id1.audit_diag_text AS dx1_audit,id2.audit_diag_text AS dx2_audit,id3.audit_diag_text AS dx3_audit,id4.audit_diag_text AS dx4_audit,
            id5.audit_diag_text AS dx5_audit,a.pdx,a.diag_text_list,i.adjrw
            FROM ipt i
            LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
            LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2
            LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3
            LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4
            LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5
            LEFT JOIN an_stat a ON a.an=i.an
            WHERE i.dchdate BETWEEN ? AND ?
            GROUP BY i.an) AS a', [$start_date, $end_date]);
        foreach ($sql as $row) {
            $sum_discharge = $row->sum_discharge;
            $sum_wait_dchsummary = $row->sum_wait_dchsummary;
            $sum_wait_icd_coder = $row->sum_wait_icd_coder;
            $sum_dchsummary = $row->sum_dchsummary;
            $sum_dchsummary_audit = $row->sum_dchsummary_audit;
        }

        $k_value = (float) DB::table('main_setting')->where('name', 'k_value')->value('value');
        $base_rate = (float) DB::table('main_setting')->where('name', 'base_rate')->value('value');
        $base_rate2 = (float) DB::table('main_setting')->where('name', 'base_rate2')->value('value');
        $base_rate_ofc = (float) DB::table('main_setting')->where('name', 'base_rate_ofc')->value('value');
        $base_rate_lgo = (float) DB::table('main_setting')->where('name', 'base_rate_lgo')->value('value');
        $base_rate_sss = (float) DB::table('main_setting')->where('name', 'base_rate_sss')->value('value');
        $adjrw = DB::connection('hosxp')->select('
            SELECT COUNT(an) AS sum_discharge,SUM(adjrw) AS rw_all,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y") 
			    THEN adjrw ELSE 0 END) AS rw_ucs,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
			    THEN adjrw * ? * ? ELSE 0 END) AS rw_receive_ucs,
		    SUM(CASE WHEN hipdata_code = "UCS" AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE (hmain_ucs IS NULL OR hmain_ucs ="")) 
			    THEN adjrw ELSE 0 END) AS rw_ucs2,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE (hmain_ucs IS NULL OR hmain_ucs =""))
			    THEN adjrw * ? ELSE 0 END) AS rw_receive_ucs2,
		    SUM(CASE WHEN hipdata_code = "OFC" THEN adjrw ELSE 0 END) AS rw_ofc,
            SUM(CASE WHEN hipdata_code = "OFC" THEN adjrw * ? ELSE 0 END) AS rw_receive_ofc,
		    SUM(CASE WHEN hipdata_code = "LGO" THEN adjrw ELSE 0 END) AS rw_lgo,
            SUM(CASE WHEN hipdata_code = "LGO" THEN adjrw * ? ELSE 0 END) AS rw_receive_lgo,
		    SUM(CASE WHEN hipdata_code = "SSS" THEN adjrw ELSE 0 END) AS rw_sss,
            SUM(CASE WHEN hipdata_code = "SSS" THEN adjrw * ? ELSE 0 END) AS rw_receive_sss
            FROM (SELECT i.an,i.regdate,i.dchdate,p.hipdata_code,ip.pttype,ip.hospmain,a.pdx,i.adjrw
                FROM ipt i	
		        LEFT JOIN ipt_pttype ip ON ip.an=i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN an_stat a ON a.an = i.an		        
                WHERE i.dchdate BETWEEN ? AND ?
                GROUP BY i.an) AS a ', [$k_value, $base_rate, $base_rate2, $base_rate_ofc, $base_rate_lgo, $base_rate_sss, $start_date, $end_date]);
        foreach ($adjrw as $row) {
            $rw_all = $row->rw_all;
            $rw_ucs = $row->rw_ucs;
            $rw_receive_ucs = $row->rw_receive_ucs;
            $rw_ucs2 = $row->rw_ucs2;
            $rw_receive_ucs2 = $row->rw_receive_ucs2;
            $rw_ofc = $row->rw_ofc;
            $rw_receive_ofc = $row->rw_receive_ofc;
            $rw_lgo = $row->rw_lgo;
            $rw_receive_lgo = $row->rw_receive_lgo;
            $rw_sss = $row->rw_sss;
            $rw_receive_sss = $row->rw_receive_sss;
        }

        $data = DB::connection('hosxp')->select('
                SELECT w.`name` AS ward,i.an,i.regdate,i.dchdate,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,a.admdate,d.`name` AS owner_doctor_name,
                id1.diag_text AS dx1,d1.`name` AS dx1_doctor,id1.audit_diag_text AS dx1_audit,d1_a.`name` AS dx1_doctor_audit,
                id2.diag_text AS dx2,d2.`name` AS dx2_doctor,id2.audit_diag_text AS dx2_audit,d2_a.`name` AS dx2_doctor_audit,
                id3.diag_text AS dx3,d3.`name` AS dx3_doctor,id3.audit_diag_text AS dx3_audit,d3_a.`name` AS dx3_doctor_audit,
                id4.diag_text AS dx4,d4.`name` AS dx4_doctor,id4.audit_diag_text AS dx4_audit,d4_a.`name` AS dx4_doctor_audit,
                id5.diag_text AS dx5,d5.`name` AS dx5_doctor,id5.audit_diag_text AS dx5_audit,d5_a.`name` AS dx5_doctor_audit,
                id_t1.icd10 AS icd10_t1, GROUP_CONCAT(DISTINCT id_t2.icd10) AS icd10_t2 ,GROUP_CONCAT(DISTINCT id_t3.icd10) AS icd10_t3,
                GROUP_CONCAT(DISTINCT id_t4.icd10) AS icd10_t4,GROUP_CONCAT(DISTINCT id_t5.icd10) AS icd10_t5,i.adjrw      
                FROM ipt i
                LEFT JOIN patient pt ON pt.hn=i.hn
                LEFT JOIN pttype p ON p.pttype=i.pttype
                LEFT JOIN ward w ON w.ward=i.ward                    
                LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
                LEFT JOIN doctor d ON d.`code` = il.doctor						
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1	
                LEFT JOIN doctor d1 ON d1.`code` = id1.doctor_code	
                LEFT JOIN doctor d1_a ON d1_a.`code` = id1.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2	
                LEFT JOIN doctor d2 ON d2.`code` = id2.doctor_code
                LEFT JOIN doctor d2_a ON d2_a.`code` = id2.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3	
                LEFT JOIN doctor d3 ON d3.`code` = id3.doctor_code	
                LEFT JOIN doctor d3_a ON d3_a.`code` = id3.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4	
                LEFT JOIN doctor d4 ON d4.`code` = id4.doctor_code	
                LEFT JOIN doctor d4_a ON d4_a.`code` = id4.audit_doctor_code			
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5	
                LEFT JOIN doctor d5 ON d5.`code` = id5.doctor_code		
                LEFT JOIN doctor d5_a ON d5_a.`code` = id5.audit_doctor_code						
                LEFT JOIN an_stat a ON a.an=i.an			
                LEFT JOIN iptdiag id_t1 ON id_t1.an=i.an AND id_t1.diagtype = 1
                LEFT JOIN iptdiag id_t2 ON id_t2.an=i.an AND id_t2.diagtype = 2
                LEFT JOIN iptdiag id_t3 ON id_t3.an=i.an AND id_t3.diagtype = 3
                LEFT JOIN iptdiag id_t4 ON id_t4.an=i.an AND id_t4.diagtype = 4
                LEFT JOIN iptdiag id_t5 ON id_t5.an=i.an AND id_t5.diagtype = 5
                WHERE i.dchdate BETWEEN ? AND ?
                AND (id1.an IS NOT NULL OR id1.an <>"") AND a.pdx <> "" AND a.pdx IS NOT NULL                 
                GROUP BY i.an', [$start_date, $end_date]);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->save();

        return view('ipd.dchsummary', compact(
            'start_date',
            'end_date',
            'sum_discharge',
            'sum_wait_dchsummary',
            'sum_wait_icd_coder',
            'sum_dchsummary',
            'sum_dchsummary_audit',
            'rw_all',
            'rw_ucs',
            'rw_receive_ucs',
            'rw_ucs2',
            'rw_receive_ucs2',
            'rw_ofc',
            'rw_receive_ofc',
            'rw_lgo',
            'rw_receive_lgo',
            'rw_sss',
            'rw_receive_sss',
            'data'
        ));
    }
    //Create dchsummary
    public function dchsummary_audit(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date');
        $end_date = $request->end_date ?: Session::get('end_date');

        $sql = DB::connection('hosxp')->select('
                SELECT COUNT(an) AS sum_discharge,
                SUM(CASE WHEN (diag_text_list IS NULL OR diag_text_list ="") THEN 1 ELSE 0 END) AS sum_wait_dchsummary,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"") AND (pdx ="" OR pdx IS NULL) THEN 1 ELSE 0 END) AS sum_wait_icd_coder,
                SUM(CASE WHEN (dx1 IS NOT NULL OR dx1 <>"" OR dx2 IS NOT NULL OR dx2 <>"" OR dx3 IS NOT NULL OR dx3 <>""
                    OR dx4 IS NOT NULL OR dx4 <>"" OR dx5 IS NOT NULL OR dx5 <>"") AND pdx <>"" AND pdx IS NOT NULL THEN 1 ELSE 0 END) AS sum_dchsummary,
                SUM(CASE WHEN (dx1_audit IS NOT NULL OR dx1_audit <>"" OR dx2_audit IS NOT NULL OR dx2_audit <>"" OR dx3_audit IS NOT NULL OR dx3_audit <>""
                    OR dx4_audit IS NOT NULL OR dx4_audit <>"" OR dx5_audit IS NOT NULL OR dx5_audit <>"") THEN 1 ELSE 0 END) AS sum_dchsummary_audit
                FROM (SELECT i.an,i.regdate,i.dchdate,id1.diag_text AS dx1,id2.diag_text AS dx2,id3.diag_text AS dx3,id4.diag_text AS dx4,id5.diag_text AS dx5,
                id1.audit_diag_text AS dx1_audit,id2.audit_diag_text AS dx2_audit,id3.audit_diag_text AS dx3_audit,id4.audit_diag_text AS dx4_audit,
                id5.audit_diag_text AS dx5_audit,a.pdx,a.diag_text_list,i.adjrw
                FROM ipt i
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5
                LEFT JOIN an_stat a ON a.an=i.an
                WHERE i.dchdate BETWEEN ? AND ?
                GROUP BY i.an) AS a', [$start_date, $end_date]);
        foreach ($sql as $row) {
            $sum_discharge = $row->sum_discharge;
            $sum_wait_dchsummary = $row->sum_wait_dchsummary;
            $sum_wait_icd_coder = $row->sum_wait_icd_coder;
            $sum_dchsummary = $row->sum_dchsummary;
            $sum_dchsummary_audit = $row->sum_dchsummary_audit;
        }

        $k_value = (float) DB::table('main_setting')->where('name', 'k_value')->value('value');
        $base_rate = (float) DB::table('main_setting')->where('name', 'base_rate')->value('value');
        $base_rate2 = (float) DB::table('main_setting')->where('name', 'base_rate2')->value('value');
        $base_rate_ofc = (float) DB::table('main_setting')->where('name', 'base_rate_ofc')->value('value');
        $base_rate_lgo = (float) DB::table('main_setting')->where('name', 'base_rate_lgo')->value('value');
        $base_rate_sss = (float) DB::table('main_setting')->where('name', 'base_rate_sss')->value('value');
        $adjrw = DB::connection('hosxp')->select('
            SELECT COUNT(an) AS sum_discharge,SUM(adjrw) AS rw_all,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y") 
			    THEN adjrw ELSE 0 END) AS rw_ucs,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
			    THEN adjrw * ? * ? ELSE 0 END) AS rw_receive_ucs,
		    SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE (hmain_ucs IS NULL OR hmain_ucs ="")) 
			    THEN adjrw ELSE 0 END) AS rw_ucs2,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE (hmain_ucs IS NULL OR hmain_ucs =""))
			    THEN adjrw * ? ELSE 0 END) AS rw_receive_ucs2,
		    SUM(CASE WHEN hipdata_code = "OFC" THEN adjrw ELSE 0 END) AS rw_ofc,
            SUM(CASE WHEN hipdata_code = "OFC" THEN adjrw * ? ELSE 0 END) AS rw_receive_ofc,
		    SUM(CASE WHEN hipdata_code = "LGO" THEN adjrw ELSE 0 END) AS rw_lgo,
            SUM(CASE WHEN hipdata_code = "LGO" THEN adjrw * ? ELSE 0 END) AS rw_receive_lgo,
		    SUM(CASE WHEN hipdata_code = "SSS" THEN adjrw ELSE 0 END) AS rw_sss,
            SUM(CASE WHEN hipdata_code = "SSS" THEN adjrw * ? ELSE 0 END) AS rw_receive_sss
            FROM (SELECT i.an,i.regdate,i.dchdate,p.hipdata_code,ip.pttype,ip.hospmain,a.pdx,i.adjrw
                FROM ipt i	
		        LEFT JOIN ipt_pttype ip ON ip.an=i.an
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN an_stat a ON a.an = i.an		        
                WHERE i.dchdate BETWEEN ? AND ?
                GROUP BY i.an) AS a ', [$k_value, $base_rate, $base_rate2, $base_rate_ofc, $base_rate_lgo, $base_rate_sss, $start_date, $end_date]);
        foreach ($adjrw as $row) {
            $rw_all = $row->rw_all;
            $rw_ucs = $row->rw_ucs;
            $rw_receive_ucs = $row->rw_receive_ucs;
            $rw_ucs2 = $row->rw_ucs2;
            $rw_receive_ucs2 = $row->rw_receive_ucs2;
            $rw_ofc = $row->rw_ofc;
            $rw_receive_ofc = $row->rw_receive_ofc;
            $rw_lgo = $row->rw_lgo;
            $rw_receive_lgo = $row->rw_receive_lgo;
            $rw_sss = $row->rw_sss;
            $rw_receive_sss = $row->rw_receive_sss;
        }

        $data = DB::connection('hosxp')->select('
                SELECT w.`name` AS ward,i.an,i.regdate,i.dchdate,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,a.admdate,d.`name` AS owner_doctor_name,
                id1.diag_text AS dx1,d1.`name` AS dx1_doctor,id1.audit_diag_text AS dx1_audit,d1_a.`name` AS dx1_doctor_audit,
                id2.diag_text AS dx2,d2.`name` AS dx2_doctor,id2.audit_diag_text AS dx2_audit,d2_a.`name` AS dx2_doctor_audit,
                id3.diag_text AS dx3,d3.`name` AS dx3_doctor,id3.audit_diag_text AS dx3_audit,d3_a.`name` AS dx3_doctor_audit,
                id4.diag_text AS dx4,d4.`name` AS dx4_doctor,id4.audit_diag_text AS dx4_audit,d4_a.`name` AS dx4_doctor_audit,
                id5.diag_text AS dx5,d5.`name` AS dx5_doctor,id5.audit_diag_text AS dx5_audit,d5_a.`name` AS dx5_doctor_audit,
                id_t1.icd10 AS icd10_t1, GROUP_CONCAT(DISTINCT id_t2.icd10) AS icd10_t2 ,GROUP_CONCAT(DISTINCT id_t3.icd10) AS icd10_t3,
                GROUP_CONCAT(DISTINCT id_t4.icd10) AS icd10_t4,GROUP_CONCAT(DISTINCT id_t5.icd10) AS icd10_t5,i.adjrw     
                FROM ipt i
                LEFT JOIN patient pt ON pt.hn=i.hn
                LEFT JOIN pttype p ON p.pttype=i.pttype
                LEFT JOIN ward w ON w.ward=i.ward                    
                LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
                LEFT JOIN doctor d ON d.`code` = il.doctor						
                LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1	
                LEFT JOIN doctor d1 ON d1.`code` = id1.doctor_code	
                LEFT JOIN doctor d1_a ON d1_a.`code` = id1.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id2 ON id2.an = i.an	AND id2.diagtype = 2	
                LEFT JOIN doctor d2 ON d2.`code` = id2.doctor_code
                LEFT JOIN doctor d2_a ON d2_a.`code` = id2.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id3 ON id3.an = i.an	AND id3.diagtype = 3	
                LEFT JOIN doctor d3 ON d3.`code` = id3.doctor_code	
                LEFT JOIN doctor d3_a ON d3_a.`code` = id3.audit_doctor_code		
                LEFT JOIN ipt_doctor_diag id4 ON id4.an = i.an	AND id4.diagtype = 4	
                LEFT JOIN doctor d4 ON d4.`code` = id4.doctor_code	
                LEFT JOIN doctor d4_a ON d4_a.`code` = id4.audit_doctor_code			
                LEFT JOIN ipt_doctor_diag id5 ON id5.an = i.an	AND id5.diagtype = 5	
                LEFT JOIN doctor d5 ON d5.`code` = id5.doctor_code		
                LEFT JOIN doctor d5_a ON d5_a.`code` = id5.audit_doctor_code						
                LEFT JOIN an_stat a ON a.an=i.an			
                LEFT JOIN iptdiag id_t1 ON id_t1.an=i.an AND id_t1.diagtype = 1
                LEFT JOIN iptdiag id_t2 ON id_t2.an=i.an AND id_t2.diagtype = 2
                LEFT JOIN iptdiag id_t3 ON id_t3.an=i.an AND id_t3.diagtype = 3
                LEFT JOIN iptdiag id_t4 ON id_t4.an=i.an AND id_t4.diagtype = 4
                LEFT JOIN iptdiag id_t5 ON id_t5.an=i.an AND id_t5.diagtype = 5
                WHERE i.dchdate BETWEEN ? AND ?
                AND ((id1.audit_diag_text IS NOT NULL OR id1.audit_diag_text <>"") 
                  OR (id2.audit_diag_text IS NOT NULL OR id2.audit_diag_text <>"")
                  OR (id3.audit_diag_text IS NOT NULL OR id3.audit_diag_text <>"")
                  OR (id4.audit_diag_text IS NOT NULL OR id4.audit_diag_text <>"")
                  OR (id5.audit_diag_text IS NOT NULL OR id5.audit_diag_text <>""))
                GROUP BY i.an', [$start_date, $end_date]);

        $request->session()->put('start_date', $start_date);
        $request->session()->put('end_date', $end_date);
        $request->session()->save();

        return view('ipd.dchsummary', compact(
            'start_date',
            'end_date',
            'sum_discharge',
            'sum_wait_dchsummary',
            'sum_wait_icd_coder',
            'sum_dchsummary',
            'sum_dchsummary_audit',
            'rw_all',
            'rw_ucs',
            'rw_receive_ucs',
            'rw_ucs2',
            'rw_receive_ucs2',
            'rw_ofc',
            'rw_receive_ofc',
            'rw_lgo',
            'rw_receive_lgo',
            'rw_sss',
            'data'
        ));
    }

    public function ipd_visit(Request $request)
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
        if ($budget_year == $budget_year_now) {
            $end_date = date('Y-m-d');
        } else {
            $end_date = $year_data->DATE_END ?? null;
        }

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
            SUM(a.inc12) AS drug_price,
            SUM(a.inc03) AS lab_price,
            
            -- Normal Stats
            COUNT(DISTINCT CASE WHEN (r.roomtype IN (1,2) OR lw.ward_homeward <> "Y" OR lw.ward_homeward IS NULL) THEN a.an END) as norm_an,
            SUM(CASE WHEN (r.roomtype IN (1,2) OR lw.ward_homeward <> "Y" OR lw.ward_homeward IS NULL) THEN a.admdate ELSE 0 END) as norm_admdate,
            SUM(CASE WHEN (r.roomtype IN (1,2) OR lw.ward_homeward <> "Y" OR lw.ward_homeward IS NULL) THEN i.adjrw ELSE 0 END) as norm_adjrw,
            SUM(CASE WHEN (r.roomtype IN (1,2) OR lw.ward_homeward <> "Y" OR lw.ward_homeward IS NULL) THEN (a.income - a.rcpt_money) ELSE 0 END) as norm_income,
            SUM(CASE WHEN (r.roomtype IN (1,2) OR lw.ward_homeward <> "Y" OR lw.ward_homeward IS NULL) THEN a.inc12 ELSE 0 END) as norm_drug_price,
            SUM(CASE WHEN (r.roomtype IN (1,2) OR lw.ward_homeward <> "Y" OR lw.ward_homeward IS NULL) THEN a.inc03 ELSE 0 END) as norm_lab_price,
            
            -- Homeward Stats
            COUNT(DISTINCT CASE WHEN (r.roomtype IN (3) OR lw.ward_homeward = "Y") THEN a.an END) as home_an,
            SUM(CASE WHEN (r.roomtype IN (3) OR lw.ward_homeward = "Y") THEN a.admdate ELSE 0 END) as home_admdate,
            SUM(CASE WHEN (r.roomtype IN (3) OR lw.ward_homeward = "Y") THEN i.adjrw ELSE 0 END) as home_adjrw,
            SUM(CASE WHEN (r.roomtype IN (3) OR lw.ward_homeward = "Y") THEN (a.income - a.rcpt_money) ELSE 0 END) as home_income,
            SUM(CASE WHEN (r.roomtype IN (3) OR lw.ward_homeward = "Y") THEN a.inc12 ELSE 0 END) as home_drug_price,
            SUM(CASE WHEN (r.roomtype IN (3) OR lw.ward_homeward = "Y") THEN a.inc03 ELSE 0 END) as home_lab_price,
            
            DAY(LAST_DAY(a.dchdate)) as days_in_month
        FROM ipt i
        LEFT JOIN an_stat a ON a.an = i.an
        LEFT JOIN iptadm ia ON ia.an = a.an
        LEFT JOIN roomno r ON r.roomno = ia.roomno
        LEFT JOIN hrims.lookup_ward lw ON lw.ward = a.ward
        WHERE a.dchdate BETWEEN ? AND ?
        AND a.pdx NOT IN ("Z290","Z208")
        GROUP BY YEAR(a.dchdate), MONTH(a.dchdate)
        ORDER BY YEAR(a.dchdate), MONTH(a.dchdate)', [$start_date, $end_date]);

        $ip_all = [];
        $ip_normal = [];
        $ip_homeward = [];

        foreach ($monthly_stats as $stat) {
            $days = $stat->days_in_month;

            $ip_all[] = (object) [
                'month' => $stat->month,
                'an' => $stat->an,
                'admdate' => $stat->admdate,
                'avg_admdate' => $stat->an > 0 ? round($stat->admdate / $stat->an, 2) : 0,
                'bed_occupancy' => $stat->an > 0 ? round(($stat->admdate * 100) / ($bed_qty * $days), 2) : 0,
                'active_bed' => $stat->an > 0 ? round((($stat->admdate * 100) / ($bed_qty * $days) * $bed_qty) / 100, 2) : 0,
                'cmi' => $stat->an > 0 ? round($stat->adjrw / $stat->an, 2) : 0,
                'adjrw' => round($stat->adjrw, 2),
                'income_rw' => $stat->adjrw > 0 ? round($stat->income_after_rcpt / $stat->adjrw, 2) : 0,
                'drug_price' => round($stat->drug_price ?? 0, 2),
                'lab_price' => round($stat->lab_price ?? 0, 2),
                'days_in_month' => $days
            ];

            $ip_normal[] = (object) [
                'month' => $stat->month,
                'an' => $stat->norm_an,
                'admdate' => $stat->norm_admdate,
                'avg_admdate' => $stat->norm_an > 0 ? round($stat->norm_admdate / $stat->norm_an, 2) : 0,
                'bed_occupancy' => $stat->norm_an > 0 ? round(($stat->norm_admdate * 100) / ($bed_qty * $days), 2) : 0,
                'active_bed' => $stat->norm_an > 0 ? round((($stat->norm_admdate * 100) / ($bed_qty * $days) * $bed_qty) / 100, 2) : 0,
                'cmi' => $stat->norm_an > 0 ? round($stat->norm_adjrw / $stat->norm_an, 2) : 0,
                'adjrw' => round($stat->norm_adjrw, 2),
                'income_rw' => $stat->norm_adjrw > 0 ? round($stat->norm_income / $stat->norm_adjrw, 2) : 0,
                'drug_price' => round($stat->norm_drug_price ?? 0, 2),
                'lab_price' => round($stat->norm_lab_price ?? 0, 2),
                'days_in_month' => $days
            ];

            $ip_homeward[] = (object) [
                'month' => $stat->month,
                'an' => $stat->home_an,
                'admdate' => $stat->home_admdate,
                'avg_admdate' => $stat->home_an > 0 ? round($stat->home_admdate / $stat->home_an, 2) : 0,
                'bed_occupancy' => $stat->home_an > 0 ? round(($stat->home_admdate * 100) / ($bed_qty * $days), 2) : 0,
                'active_bed' => $stat->home_an > 0 ? round((($stat->admdate * 100) / ($bed_qty * $days) * $bed_qty) / 100, 2) : 0,
                'cmi' => $stat->home_an > 0 ? round($stat->home_adjrw / $stat->home_an, 2) : 0,
                'adjrw' => round($stat->home_adjrw, 2),
                'income_rw' => $stat->home_adjrw > 0 ? round($stat->home_income / $stat->home_adjrw, 2) : 0,
                'drug_price' => round($stat->home_drug_price ?? 0, 2),
                'lab_price' => round($stat->home_lab_price ?? 0, 2),
                'days_in_month' => $days
            ];
        }

        $month = array_column($ip_all, 'month');
        $bed_occupancy = array_column($ip_all, 'bed_occupancy');

        return view('ipd.ipd_visit', compact(
            'budget_year_select',
            'budget_year',
            'ip_all',
            'ip_normal',
            'ip_homeward',
            'month',
            'bed_occupancy'
        ));
    }
}

