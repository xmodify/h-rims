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
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,IF(ip.auth_code <> "","Y",NULL) AS auth_code,
            IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok 
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
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "UCS" AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NULL 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,i.data_exp_date AS fdh,
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
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "UCS" AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NOT NULL 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.ucs_incup',compact('start_date','end_date','search','claim'));
    }
//----------------------------------------------------------------------------------------------------------------------------------------
    public function ucs_outcup(Request $request )
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        if($start_date == '' || $end_date == null)
        {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
        if($end_date == '' || $end_date == null)
        {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

        ini_set('max_execution_time', 300); // เพิ่มเป็น 5 นาที

        $search=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,IF(ip.auth_code <> "","Y",NULL) AS auth_code,
            IF(id.an <> "","Y",NULL) AS dch_sum,i.data_ok 
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
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "UCS" AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NULL
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        $claim=DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.regdate,i.dchdate,i.hn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
            p.`name` AS pttype,ip.hospmain,a.diag_text_list,id.icd10,idx.icd9,a.income,a.rcpt_money,a.income-a.rcpt_money AS claim_price,
            CONCAT(r.refer_hospcode,"[ucae=",ia.ac_ae,"]") AS refer,i.adjrw,i.data_exp_date AS fdh,
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
            LEFT JOIN rep_eclaim_detail rep ON rep.vn=i.vn
            LEFT JOIN hrims.stm_ucs stm ON stm.an=i.an
            WHERE i.confirm_discharge = "Y" AND i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = "UCS" AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.data_exp_date IS NOT NULL 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date]);

        return view('claim_ip.ucs_outcup',compact('start_date','end_date','search','claim'));
    }

}
