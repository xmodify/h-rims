<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MishosController extends Controller
{
//Check Login
    public function __construct()
    {
        $this->middleware('auth');
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ae(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-v.rcpt_money AS claim_price,stm.receive_total
                FROM ovst o           
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn  
                LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
                LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                    AND proj.vn IS NULL
                    AND kidney.vn IS NULL 
                    AND p.hipdata_code = "UCS" 							
                    AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
                    AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.rcpt_money AS claim_price,
            stm.receive_total,stm.repno,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))   
		    LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
			AND proj.vn IS NULL
			AND kidney.vn IS NULL 
			AND p.hipdata_code = "UCS" 							
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
			AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('mishos.ucs_ae',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_walkin(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,v.income-v.rcpt_money AS claim_price,stm.receive_total
                FROM ovst o           
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn           
                LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
                LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN"))
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                    AND proj.vn IS NOT NULL
                    AND kidney.vn IS NULL 
                    AND p.hipdata_code = "UCS" 							
                    AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
                    AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.rcpt_money AS claim_price,
            stm.receive_total,stm.repno,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN"))
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)
			AND proj.vn IS NOT NULL
			AND kidney.vn IS NULL 
			AND p.hipdata_code = "UCS" 							
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")            
			AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('mishos.ucs_walkin',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_herb(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vstdate,o.vsttime,o.vn,COALESCE(herb.claim_price, 0) AS claim_price,
				IF(stm.receive_hc_drug=0,stm.receive_hc_hc,stm.receive_hc_drug) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.herb32 = "Y"						
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
					INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
					WHERE op.vstdate BETWEEN ? AND ? AND li.herb32 = "Y" GROUP BY op.vn) herb ON herb.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			        AND p.hipdata_code = "UCS" 	
			        AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			        AND o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn ORDER BY o.vstdate,o.vsttime ) AS a
                GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,COALESCE(herb.claim_price, 0) AS claim_price,
                IF(stm.receive_hc_drug=0,stm.receive_hc_hc,stm.receive_hc_drug) AS receive_total,
                GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.herb32 = "Y"
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
				WHERE op.vstdate BETWEEN ? AND ? AND li.herb32 = "Y" GROUP BY op.vn) herb ON herb.vn=o.vn						
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS" 	
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
                AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_herb',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_telemed(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(tele.claim_price, 0) AS claim_price,
				LEAST(stm.receive_op, tele.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("TELMED")		
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ?
					AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("TELMED")) GROUP BY op.vn) tele ON tele.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			        AND p.hipdata_code = "UCS" 	
			        AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			        AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,COALESCE(tele.claim_price, 0) AS claim_price,
                LEAST(stm.receive_op, tele.claim_price) AS receive_total,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
				IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("TELMED")	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("TELMED")) GROUP BY op.vn) tele ON tele.vn=o.vn						
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS" 	
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
                AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_telemed',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_rider(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(rider.claim_price, 0) AS claim_price,
				LEAST(stm.receive_op, rider.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("DRUGP")		
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ?
					AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("DRUGP")) GROUP BY op.vn) rider ON rider.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			        AND p.hipdata_code = "UCS" 	
			        AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			        AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,COALESCE(rider.claim_price, 0) AS claim_price,
                LEAST(stm.receive_op, rider.claim_price) AS receive_total,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
				IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("DRUGP")	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("DRUGP")) GROUP BY op.vn) rider ON rider.vn=o.vn						
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS" 	
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
                AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_rider',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_gdm(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                stm.receive_dmis_compensate_pay AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("80008")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? 
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("80008")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS" 	       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price,stm.receive_dmis_compensate_pay AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("O244","O249")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("80008"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("80008")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) 
            AND p.hipdata_code = "UCS" 	 
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_gdm',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_drug_clopidogrel(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $drug_clopidogrel = DB::table('main_setting')->where('name', 'drug_clopidogrel')->value('value');  

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(drug.claim_price, 0) AS claim_price,LEAST(stm.receive_hc_drug, drug.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode = ?						
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ? AND op.icode = ? GROUP BY op.vn) drug ON drug.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			        AND p.hipdata_code = "UCS" 	
			        AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			        AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$drug_clopidogrel,$start_date_b,$end_date_b,$drug_clopidogrel,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,COALESCE(drug.claim_price, 0) AS claim_price,
                LEAST(stm.receive_hc_drug, drug.claim_price) AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			INNER JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode = ?			
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? AND op.icode=? GROUP BY op.vn) drug ON drug.vn=o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS" 	
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
                AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$drug_clopidogrel,$start_date,$end_date,$drug_clopidogrel,$start_date,$end_date]);

        return view('mishos.ucs_drug_clopidogrel',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_drug_sk(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(sk.claim_price, 0) AS claim_price,stm.receive_dmis_drug AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN drugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("STEMI1")		
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ?
					AND op.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("STEMI1")) GROUP BY op.vn) sk ON sk.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			        AND p.hipdata_code = "UCS" 	
			        AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
			        AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,COALESCE(sk.claim_price, 0) AS claim_price,
                stm.receive_dmis_drug AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN drugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("STEMI1")	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("STEMI1")) GROUP BY op.vn) sk ON sk.vn=o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS" 	
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")            
                AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_drug_sk',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ins(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ins.claim_price, 0) AS claim_price,stm.receive_inst AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_type_id = "2"	AND nt.nhso_adp_code NOT IN ("8901","8902","8904")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
					WHERE op.vstdate BETWEEN ? AND ?
					AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_type_id = "2"
                    AND nhso_adp_code NOT IN ("8901","8902","8904")) GROUP BY op.vn) ins ON ins.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			        AND p.hipdata_code = "UCS" 	       
			        AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,COALESCE(ins.claim_price, 0) AS claim_price,
                stm.receive_inst AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_type_id = "2"	AND nt.nhso_adp_code NOT IN ("8901","8902","8904")
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_type_id = "2"
                AND nhso_adp_code NOT IN ("8901","8902","8904")) GROUP BY op.vn) ins ON ins.vn=o.vn	
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn					
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS"       
                AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ins',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_palliative(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(palli.claim_price, 0) AS claim_price,stm.receive_palliative AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("30001","Cons01","Eva001")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? 
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30001","Cons01","Eva001")) 
                    GROUP BY op.vn) palli ON palli.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
			        AND p.hipdata_code = "UCS" 	       
			        AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,COALESCE(palli.claim_price, 0) AS claim_price,
                stm.receive_palliative AS receive_total ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
                IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("30001","Cons01","Eva001")
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30001","Cons01","Eva001")) 
                GROUP BY op.vn) palli ON palli.vn=o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)
                AND p.hipdata_code = "UCS"       
                AND o.vstdate BETWEEN ? AND ? 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_palliative',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_fp(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
                    AND (o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
			        OR o1.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			        WHERE op.vstdate BETWEEN ? AND ?
				    AND (op.icode IN (SELECT icode FROM nondrugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
				    OR op.icode IN (SELECT icode FROM drugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
				    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                AND o1.vn IS NOT NULL
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("Z301","Z304","Z308","G431","697","9923")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
                AND (o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
			    OR o1.icode IN (SELECT icode FROM drugitems WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
				AND (op.icode IN (SELECT icode FROM nondrugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4"))
				OR op.icode IN (SELECT icode FROM drugitems	WHERE nhso_adp_code IN ("FP001","FP002","FP002_1","FP002_2","FP003_1","FP003_2","FP003_3","FP003_4")))
				GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_fp',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_prt(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $lab_prt = DB::table('main_setting')->where('name', 'lab_prt')->value('value'); 

        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("30014")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30014")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,ov.icd10,lo.lab_items_name_ref,v.income,v.rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("Z320","Z321")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30014"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN lab_head lh ON lh.vn=o.vn
			LEFT JOIN lab_order lo ON lo.lab_order_number=lh.lab_order_number AND lo.lab_items_code IN ('.$lab_prt.') 
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30014")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR lo.lab_items_name_ref IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_prt',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_ida(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("13001")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("13001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,v.age_y,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,lab.lab,
			COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN (SELECT v1.vn,op.icode,nd.name AS lab FROM vn_stat v1
                INNER JOIN opitemrece op ON op.vn=v1.vn AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code ="30101")
                LEFT JOIN nondrugitems nd ON nd.icode=op.icode
                WHERE v1.sex = 2 AND v1.age_y BETWEEN 13 AND 24 ) lab ON lab.vn=o.vn			
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("Z138")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("13001"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("13001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL OR lab.vn IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_ida',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_ferrofolic(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("14001")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("14001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("Z130")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("14001"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("14001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_ferrofolic',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_fluoride(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("15001")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				WHERE op.vstdate BETWEEN ? AND ? 
				AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("15001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
			COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("K020","K1170","Z298","9654","2387021")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("15001"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("15001")) GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_fluoride',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_anc(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("30008","30009","30010","30011","30012","30013")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? 
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30008","30009","30010","30011","30012","30013")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,a.anc_service_number,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN person_anc_service a ON a.vn=o.vn
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn
                AND ov.icd10 IN ("Z340","Z348","Z350","Z359","8878","2387010","2277310","2277320","2287310","2287320")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30008","30009","30010","30011","30012","30013"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30008","30009","30010","30011","30012","30013")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_anc',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_postnatal(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("30015","30016")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? 
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30015","30016")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("Z390","Z392")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30015","30016"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("30015","30016")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_postnatal',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_fittest(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("90005")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? 
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("90005")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("Z121")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("90005"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("90005")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_fittest',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_ppfs_scr(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $year_data = DB::table('budget_year')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->first(['LEAVE_YEAR_ID', 'DATE_BEGIN', 'DATE_END']);
        $budget_year = $year_data->LEAVE_YEAR_ID ?? null;
        $start_date_b = $year_data->DATE_BEGIN ?? null;
        $end_date_b   = $year_data->DATE_END ?? null;
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $sum_month=DB::connection('hosxp')->select('
            SELECT CASE WHEN MONTH(vstdate)=10 THEN CONCAT("ต.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=11 THEN CONCAT("พ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=12 THEN CONCAT("ธ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=1 THEN CONCAT("ม.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=2 THEN CONCAT("ก.พ. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=3 THEN CONCAT("มี.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=4 THEN CONCAT("เม.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=5 THEN CONCAT("พ.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=6 THEN CONCAT("มิ.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=7 THEN CONCAT("ก.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=8 THEN CONCAT("ส.ค. ", RIGHT(YEAR(vstdate)+543, 2))
                WHEN MONTH(vstdate)=9 THEN CONCAT("ก.ย. ", RIGHT(YEAR(vstdate)+543, 2))
                END AS month,COUNT(vn) AS visit,SUM(IFNULL(claim_price,0)) AS claim_price,SUM(IFNULL(receive_total,0)) AS receive_total
            FROM (SELECT o.vn,o.vstdate,o.vsttime,COALESCE(ppfs.claim_price, 0) AS claim_price,
                LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
                LEFT JOIN pttype p ON p.pttype=vp.pttype          
                LEFT JOIN vn_stat v ON v.vn = o.vn
				INNER JOIN opitemrece o1 ON o1.vn=o.vn
				INNER JOIN nondrugitems nt ON o1.icode = nt.icode AND nt.nhso_adp_code IN ("12003","12004")
				LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
				    WHERE op.vstdate BETWEEN ? AND ? 
				    AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("12003","12004")) 
                    GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
                LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
                WHERE (o.an ="" OR o.an IS NULL)       
			    AND o.vstdate BETWEEN ? AND ? 
                GROUP BY o.vn ) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');

        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
            p.`name` AS pttype,vp.hospmain,v.pdx,GROUP_CONCAT(DISTINCT ov.icd10) AS icd10,v.income,v.rcpt_money,
            COALESCE(ppfs.claim_price, 0) AS claim_price,LEAST(stm.receive_pp, ppfs.claim_price) AS receive_total,
            GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","") AS claim
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn           
            LEFT JOIN pttype p ON p.pttype=vp.pttype          
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN ovstdiag ov ON ov.vn=o.vn AND ov.icd10 IN ("Z131","Z136")
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
                AND o1.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("12003","12004"))
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode			
			LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op					
			WHERE op.vstdate BETWEEN ? AND ?
			AND op.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("12003","12004")) 
            GROUP BY op.vn) ppfs ON ppfs.vn=o.vn						
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL)  
			AND (o1.vn IS NOT NULL OR ov.icd10 IS NOT NULL)
            AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('mishos.ucs_ppfs_scr',compact('start_date','end_date','month','claim_price','receive_total','search'));
    }

}
