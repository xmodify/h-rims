<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClaimIpController extends Controller
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
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NULL 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NOT NULL 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.ucs_incup',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outcup(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("UCS","WEL") AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NOT NULL 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.ucs_outcup',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function stp(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND i.data_exp_date IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,i.data_exp_date AS fdh,
            rep.rep_eclaim_detail_error_code AS rep_error,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "STP" 
            AND i.data_exp_date IS NOT NULL 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.stp',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ofc(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "OFC" AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,receive_treatment,
            stm.receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.stm_ofc stm ON stm.an=i.an 
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "OFC" AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.ofc',compact('start_date','end_date','search','claim'));
    }
    //----------------------------------------------------------------------------------------------------------------------------------------
    public function lgo(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO" AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            stm.case_iplg AS receive_treatment,stm.compensate_treatment AS receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.stm_lgo stm ON stm.an=i.an 
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "LGO" AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.lgo',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function bkk(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BKK" AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,stm.receive_treatment,
            stm.receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.stm_ofc stm ON stm.an=i.an 
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BKK" AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.bkk',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function bmt(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BMT" AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,stm.receive_treatment,
            stm.receive_total,stm.repno
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN hrims.stm_ofc stm ON stm.an=i.an 
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "BMT" AND ((ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")) OR stm.an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.bmt',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function sss(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("SSS","SSI") AND ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.sss',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function gof(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name,
            IF(ip.auth_code <> "","Y",NULL) AS auth_code,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("DIS","GOF","WVO") AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,ict.ipt_coll_status_type_name
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_accident ia ON ia.an=i.an
            LEFT JOIN referout r ON r.vn=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id            
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code IN ("DIS","GOF","WVO") AND ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.gof',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function rcpt(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,i.adjrw,a.income,a.paid_money,a.rcpt_money,
            a.paid_money-a.rcpt_money AS claim_price,r.rcpno,p2.arrear_date,p2.amount AS arrear_amount, r1.bill_amount AS paid_arrear,
            r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r ON r.vn = i.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date BETWEEN i.regdate AND i.dchdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK" AND r1.department="IPD"
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <>"0" AND a.rcpt_money <> a.paid_money 
            GROUP BY i.an ORDER BY i.ward,i.dchdate,p.pttype',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,i.adjrw,a.income,a.paid_money,a.rcpt_money,
            r.rcpno,p2.arrear_date,p2.amount AS arrear_amount, r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,
            fd.deposit_amount,fd1.debit_amount,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r ON r.vn = i.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date BETWEEN i.regdate AND i.dchdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK" AND r1.department="IPD"
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND a.paid_money <>"0" AND a.rcpt_money = a.paid_money 
            GROUP BY i.an ORDER BY i.ward,i.dchdate,p.pttype',[$start_date,$end_date]);

        return view('claim_ip.rcpt',compact('start_date','end_date','search','claim'));
    }

//----------------------------------------------------------------------------------------------------------------------------------------
    public function act(Request $request )
    {
        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');

        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');
        
        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            i.adjrw,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.pttype IN (' . $pttype_act . ') AND (ic.an IS NULL OR (ic.an IS NOT NULL AND ict.ipt_coll_status_type_id NOT IN ("4","5"))) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            i.adjrw,ict.ipt_coll_status_type_name,IF(id.an <> "","Y",NULL) AS dch_sum
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN iptdiag id ON id.an=a.an AND id.diagtype = 1
            LEFT JOIN iptoprt idx ON idx.an=i.an
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.pttype IN (' . $pttype_act . ') AND ic.an IS NOT NULL AND ict.ipt_coll_status_type_id IN ("4","5")
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);
            
        return view('claim_ip.act',compact('start_date','end_date','search','claim'));
    }

}
