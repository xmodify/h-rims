<?php

namespace App\Http\Controllers; 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimOpController extends Controller
{
    //Check Login
    public function __construct()
    {
        $this->middleware('auth');
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_incup(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
            v.income,v.rcpt_money,COALESCE(o2.claim_price, 0) AS claim_price,GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
                ON o1.icode = li.icode AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND (li.kidney = "" OR li.kidney IS NULL) GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND o1.vn IS NOT NULL AND oe.moph_finance_upload_status IS NULL AND rep.vn IS NULL AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
            COALESCE(uc_cr.claim_price, 0) AS uc_cr,COALESCE(ppfs.claim_price, 0) AS ppfs,COALESCE(herb.claim_price, 0) AS herb,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,
            stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
                ON o1.icode = li.icode AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND li.uc_cr = "Y" GROUP BY op.vn) uc_cr ON uc_cr.vn=o.vn
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) ppfs ON ppfs.vn=o.vn
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND li.herb32 = "Y" GROUP BY op.vn) herb ON herb.vn=o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND o1.vn IS NOT NULL AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.ucs_incup',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
            v.income,v.rcpt_money,COALESCE(o2.claim_price, 0) AS claim_price,GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
                ON o1.icode = li.icode AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND (li.kidney = "" OR li.kidney IS NULL) GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)            
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?             
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))            
            AND o1.vn IS NOT NULL AND oe.moph_finance_upload_status IS NULL AND rep.vn IS NULL AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
            COALESCE(uc_cr.claim_price, 0) AS uc_cr,COALESCE(ppfs.claim_price, 0) AS ppfs,COALESCE(herb.claim_price, 0) AS herb,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,
            stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
                ON o1.icode = li.icode AND (li.uc_cr = "Y" OR li.ppfs="Y" OR li.herb32 = "Y")
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND li.uc_cr = "Y" GROUP BY op.vn) uc_cr ON uc_cr.vn=o.vn
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) ppfs ON ppfs.vn=o.vn
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
	            WHERE op.vstdate BETWEEN ? AND ? AND li.herb32 = "Y" GROUP BY op.vn) herb ON herb.vn=o.vn
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?            
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs ="")) 
            AND o1.vn IS NOT NULL AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.ucs_inprovince',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince_va(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $sum=DB::connection('hosxp')->select('
            SELECT hospmain,COUNT(vn) AS visit,SUM(income) AS income,SUM(rcpt_money) AS rcpt_money,
            SUM(other_price) AS other_price,SUM(claim_price) AS claim_price,
            SUM(CASE WHEN pt_status ="อุบัติเหตุฉุกเฉิน" THEN 1 ELSE 0 END) AS er_visit,
            SUM(CASE WHEN pt_status ="อุบัติเหตุฉุกเฉิน" THEN claim_price ELSE 0 END) AS er_price,
            SUM(CASE WHEN pt_status ="ผู้ป่วยทั่วไป" THEN 1 ELSE 0 END) AS normal_visit,
            SUM(CASE WHEN pt_status ="ผู้ป่วยทั่วไป" THEN claim_price ELSE 0 END) AS normal_price
			FROM (SELECT v.vn,CONCAT(vp.hospmain," ",hc.`name`) AS hospmain,
			    CASE WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN "อุบัติเหตุฉุกเฉิน"
				WHEN er.vn IS NULL OR v1.vn IS NOT NULL THEN "ผู้ป่วยทั่วไป" END AS pt_status,						
				o.vstdate,o.vsttime,p.`name` AS pttype,v.pdx,v.income,v.rcpt_money,COALESCE(o2.other_price, 0) AS other_price,
				v.income-v.rcpt_money-COALESCE(o2.other_price,0) AS claim_price            
                FROM ovst o
				LEFT JOIN er_regist er ON er.vn=o.vn
                LEFT JOIN patient pt ON pt.hn=o.hn
                LEFT JOIN visit_pttype vp ON vp.vn=o.vn
				LEFT JOIN hospcode hc ON hc.hospcode=vp.hospmain
                LEFT JOIN pttype p ON p.pttype=vp.pttype
                LEFT JOIN vn_stat v ON v.vn = o.vn
				LEFT JOIN vn_stat v1 ON v1.vn = o.vn AND v1.pdx IN ("Z242","Z235","Z439","Z488","Z489","Z480","Z098","Z549","Z479")
                LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
                    INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
					WHERE op.vstdate BETWEEN ? AND ?  GROUP BY op.vn) o2 ON o2.vn=o.vn            
                WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ? 
				AND v.income-v.rcpt_money-COALESCE(o2.other_price,0) <> 0
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10)
                GROUP BY o.vn ORDER BY vp.hospmain,pt_status DESC,o.vstdate,o.vsttime) AS a	GROUP BY hospmain ORDER BY hospmain',[$start_date,$end_date,$start_date,$end_date]);

        $search=DB::connection('hosxp')->select('
            SELECT CONCAT(vp.hospmain," ",hc.`name`) AS hospmain,
            CASE WHEN er.vn IS NOT NULL AND v1.vn IS NULL THEN "อุบัติเหตุฉุกเฉิน"			
			WHEN er.vn IS NULL OR v1.vn IS NOT NULL THEN "ผู้ป่วยทั่วไป" 
            WHEN v.pdx IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y" ) THEN "ส่งเสริมป้องกันโรคPP" 
			END AS pt_status,o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
			p.`name` AS pttype,os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,
            COALESCE(o2.other_price, 0) AS other_price,v.income-v.rcpt_money-COALESCE(o2.other_price,0) AS claim_price,
            GROUP_CONCAT(DISTINCT sd.`name`) AS other_list
            FROM ovst o
			LEFT JOIN er_regist er ON er.vn=o.vn
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
			LEFT JOIN hospcode hc ON hc.hospcode=vp.hospmain
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
			LEFT JOIN vn_stat v1 ON v1.vn = o.vn AND v1.pdx IN ("Z242","Z235","Z439","Z488","Z480","Z098","Z549","Z479")
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode )           
            LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
                INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
				WHERE op.vstdate BETWEEN ? AND ?  GROUP BY op.vn) o2 ON o2.vn=o.vn            
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ? 
			AND v.income-v.rcpt_money-COALESCE(o2.other_price,0) <> 0
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
            AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10)
            GROUP BY o.vn ORDER BY vp.hospmain,pt_status DESC,o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);
        
        return view('claim_op.ucs_inprovince_va',compact('start_date','end_date','sum','search'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outprovince(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,SUM(DISTINCT refer.sum_price) AS refer,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,et.ucae AS er,vp.nhso_ucae_type_code AS ae
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece refer ON refer.vn=o.vn AND refer.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802"))
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND kidney.vn IS NULL AND oe.moph_finance_upload_status IS NULL AND rep.vn IS NULL AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,SUM(DISTINCT refer.sum_price) AS refer,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece refer ON refer.vn=o.vn AND refer.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802"))
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND kidney.vn IS NULL AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('claim_op.ucs_outprovince',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_kidney(Request $request )
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
            FROM (SELECT o.vstdate,o.vn,COALESCE(kidney.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total 
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype      
            INNER JOIN opitemrece o1 ON o1.vn=o.vn
            INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.kidney = "Y"            
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.kidney = "Y" GROUP BY op.vn) kidney ON kidney.vn=o.vn            
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_ucs_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid 
				AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "UCS" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime) AS a
				GROUP BY YEAR(vstdate), MONTH(vstdate)
                ORDER BY YEAR(vstdate), MONTH(vstdate)',[$start_date_b,$end_date_b,$start_date_b,$end_date_b,$start_date_b,$end_date_b]);
        $month = array_column($sum_month,'month');  
        $claim_price = array_column($sum_month,'claim_price');
        $receive_total = array_column($sum_month,'receive_total');
       
        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
            COALESCE(kidney.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
                ON o1.icode = li.icode AND li.kidney = "Y"
            LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.kidney = "Y" GROUP BY op.vn) kidney ON kidney.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(receive_total) AS receive_total,repno FROM hrims.stm_ucs_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE o1.vn IS NOT NULL AND p.hipdata_code IN ("UCS","WEL") AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.ucs_kidney',compact('start_date','end_date','month','claim_price','receive_total','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function stp_incup(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT IFNULL(d.`name`,n.`name`)) AS claim_list,
            v.income,v.rcpt_money,v.income-v.rcpt_money-COALESCE(o2.claim_price, 0) AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn            
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND oe.moph_finance_upload_status IS NULL AND rep.vn IS NULL AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
            COALESCE(o2.claim_price, 0) AS ppfs,v.income-v.rcpt_money-COALESCE(o2.claim_price, 0) AS claim_price,rep.rep_eclaim_detail_nhso AS rep_nhso,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn            
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.stp_incup',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function stp_outcup(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,SUM(DISTINCT refer.sum_price) AS refer,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,et.ucae AS er,vp.nhso_ucae_type_code AS ae
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece refer ON refer.vn=o.vn AND refer.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802"))
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "STP" AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND kidney.vn IS NULL AND oe.moph_finance_upload_status IS NULL AND rep.vn IS NULL AND stm.cid IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,SUM(DISTINCT refer.sum_price) AS refer,
            GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,et.ucae AS er,vp.nhso_ucae_type_code AS ae,
            rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN er_regist e ON e.vn=o.vn 
            LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN opitemrece refer ON refer.vn=o.vn AND refer.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802"))
            LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
                IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
            LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "STP" AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            AND kidney.vn IS NULL AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('claim_op.stp_outcup',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,v.income,
            v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "OFC" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.stm_ofc stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            LEFT JOIN hrims.stm_ucs stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND LEFT(stm_uc.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "OFC" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.ofc',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc_kidney(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
       
        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
            COALESCE(kidney.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN opitemrece o1 ON o1.vn=o.vn
            INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.kidney = "Y"
            LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.kidney = "Y" GROUP BY op.vn) kidney ON kidney.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT hn,vstdate,sum(amount) AS receive_total,rid AS repno FROM hrims.stm_ofc_kidney
                WHERE vstdate BETWEEN ? AND ? GROUP BY hn,vstdate) stm ON stm.hn=pt.hn AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "OFC" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.ofc_kidney',compact('start_date','end_date','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function lgo(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,v.income,
            v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,stm.compensate_treatment AS receive_total,stm_uc.receive_pp,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.stm_lgo stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            LEFT JOIN hrims.stm_ucs stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND LEFT(stm_uc.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.lgo',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function lgo_kidney(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
       
        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
            COALESCE(kidney.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN opitemrece o1 ON o1.vn=o.vn
            INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.kidney = "Y"
            LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.kidney = "Y" GROUP BY op.vn) kidney ON kidney.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM hrims.stm_lgo_kidney
                WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) stm ON stm.cid=pt.cid AND stm.datetimeadm = o.vstdate
            WHERE p.hipdata_code = "LGO" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.lgo_kidney',compact('start_date','end_date','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function bkk(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,v.income,
            v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "BKK" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.stm_ofc stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            LEFT JOIN hrims.stm_ucs stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND LEFT(stm_uc.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "BKK" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.bkk',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function bmt(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,v.income,
            v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,v.income-v.rcpt_money AS debtor
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "BMT" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NULL 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            IFNULL(vp.Claim_Code,oq.edc_approve_list_text) AS edc,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,os.cc,v.pdx,
            GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT s.`name`) AS ppfs_list,
            oe.upload_datetime AS ecliam,v.income,v.rcpt_money,COALESCE(o2.claim_price, 0) AS ppfs,
            v.income-v.rcpt_money AS debtor,stm.receive_total,stm_uc.receive_pp,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN ovst_seq oq ON oq.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN s_drugitems s ON s.icode=ppfs.icode
            LEFT JOIN opitemrece kidney ON kidney.vn=o.vn AND kidney.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN (SELECT op.vn,SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN hrims.stm_ofc stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            LEFT JOIN hrims.stm_ucs stm_uc ON stm_uc.cid=pt.cid AND stm_uc.vstdate = o.vstdate AND LEFT(stm_uc.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "BMT" AND o.vstdate BETWEEN ? AND ?
            AND v.income <>"0" AND kidney.vn IS NULL AND oe.upload_datetime IS NOT NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.bmt',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_ppfs(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
            vp.confirm_and_locked,vp.request_funds,o.vstdate,o.vsttime,o.oqueue,pt.cid,pt.hn,
            CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,GROUP_CONCAT(DISTINCT IFNULL(d.`name`,n.`name`)) AS claim_list,
            v.income,v.rcpt_money,COALESCE(o2.claim_price, 0) AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            INNER JOIN opitemrece o1 ON o1.vn=o.vn
            INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.ppfs = "Y" 
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) o2 ON o2.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND oe.upload_datetime IS NULL AND stm.cid IS NULL
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,os.cc,
            v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT IFNULL(n.`name`,d.`name`)) AS claim_list,
            COALESCE(ppfs.claim_price, 0) AS ppfs,oe.upload_datetime AS eclaim,rep.rep_eclaim_detail_nhso AS rep_nhso,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN opitemrece o1 ON o1.vn=o.vn
            INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.ppfs = "Y"
            LEFT JOIN nondrugitems n ON n.icode=o1.icode
            LEFT JOIN drugitems d ON d.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) ppfs ON ppfs.vn=o.vn            
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate AND LEFT(stm.vsttime,5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") 
            AND (oe.upload_datetime IS NOT NULL OR stm.cid IS NOT NULL)
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.sss_ppfs',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_fund(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');       
       
        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,v.income-v.rcpt_money AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE p.pttype IN ('.$pttype_sss_fund.') AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('claim_op.sss_fund',compact('start_date','end_date','claim'));
    }

//----------------------------------------------------------------------------------------------------------------------------------------
    public function sss_kidney(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
       
        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,
            COALESCE(kidney.claim_price, 0) AS claim_price,COALESCE(stm.receive_total, 0) AS receive_total ,stm.repno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn        
            INNER JOIN opitemrece o1 ON o1.vn=o.vn
            INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.kidney = "Y"
            LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
            LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
            INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
                WHERE op.vstdate BETWEEN ? AND ? AND li.kidney = "Y" GROUP BY op.vn) kidney ON kidney.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT cid,vstdate,sum(amount+epopay+epoadm) AS receive_total,rid AS repno FROM hrims.stm_sss_kidney
                WHERE vstdate BETWEEN ? AND ? GROUP BY cid,vstdate) stm ON stm.cid=pt.cid AND stm.vstdate = o.vstdate
            WHERE p.hipdata_code = "SSS" AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.sss_kidney',compact('start_date','end_date','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function rcpt(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        
        $search=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,v.paid_money,v.paid_money-v.rcpt_money AS claim_price,
            r.rcpno,p2.arrear_date,p2.amount AS arrear_amount,r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN patient_arrear p2 ON p2.vn=o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN rcpt_print r ON r.vn = o.vn AND r.`status` ="OK" AND r.department="OPD" AND r.bill_date=o.vstdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.vn AND r1.`status` ="OK" AND r1.department="OPD" 
            WHERE (o.an IS NULL OR o.an ="") AND o.vstdate BETWEEN ? AND ? 
            AND v.paid_money <>"0" AND v.rcpt_money <> v.paid_money 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);
       
        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,v.paid_money,v.paid_money-v.rcpt_money AS claim_price,
            r.rcpno,p2.arrear_date,p2.amount AS arrear_amount,r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN patient_arrear p2 ON p2.vn=o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN rcpt_print r ON r.vn = o.vn AND r.`status` ="OK" AND r.department="OPD" AND r.bill_date=o.vstdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.vn AND r1.`status` ="OK" AND r1.department="OPD" 
            WHERE (o.an IS NULL OR o.an ="") AND o.vstdate BETWEEN ? AND ? 
            AND v.paid_money <>"0" AND v.rcpt_money = v.paid_money 
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('claim_op.rcpt',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function act(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');       
       
        $claim=DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,pt.hn,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,p.`name` AS pttype,vp.hospmain,
            os.cc,v.pdx,GROUP_CONCAT(DISTINCT od.icd10) AS icd9,v.income,v.rcpt_money,v.income-v.rcpt_money AS claim_price
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opdscreen os ON os.vn=o.vn
            LEFT JOIN ovstdiag od ON od.vn = o.vn AND od.hn=o.hn AND od.diagtype = "2"
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE p.pttype IN ('.$pttype_act.') AND o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('claim_op.act',compact('start_date','end_date','claim'));
    }

}
