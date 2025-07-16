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
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND DATE(stm.datetimeadm) = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "UCS" AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
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
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND DATE(stm.datetimeadm) = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
            AND p.hipdata_code = "UCS" AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND o1.vn IS NOT NULL AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.ucs_incup',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_inprovince(Request $request )
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND DATE(stm.datetimeadm) = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)            
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "UCS" AND o.vstdate BETWEEN ? AND ?             
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))            
            AND o1.vn IS NOT NULL AND oe.moph_finance_upload_status IS NULL AND rep.vn IS NULL AND stm.cid IS NULL AND stm_k.cid IS NULL
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
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND DATE(stm.datetimeadm) = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "UCS" AND o.vstdate BETWEEN ? AND ?            
            AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs ="")) 
            AND o1.vn IS NOT NULL AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        return view('claim_op.ucs_inprovince',compact('start_date','end_date','search','claim'));
    }

//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outprovince(Request $request )
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

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
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND DATE(stm.datetimeadm) = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "UCS" AND o.vstdate BETWEEN ? AND ?
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
            LEFT JOIN hrims.stm_ucs stm ON stm.cid=pt.cid AND DATE(stm.datetimeadm) = o.vstdate	
                AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
            WHERE (o.an ="" OR o.an IS NULL) AND p.hipdata_code = "UCS" AND o.vstdate BETWEEN ? AND ?
            AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            AND kidney.vn IS NULL AND (oe.moph_finance_upload_status IS NOT NULL OR rep.vn IS NOT NULL OR stm.cid IS NOT NULL )
            GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

        return view('claim_op.ucs_outprovince',compact('start_date','end_date','search','claim'));
    }

}
