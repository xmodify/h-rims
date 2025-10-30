<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class AmnosendController extends Controller
{
    public function send(Request $request)
    {
    // 1) โหลดค่าพื้นฐานจาก main_setting-------------------------------------------------------------------------
        $token    = DB::table('main_setting')->where('name', 'opoh_token')->value('value');
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $bed_qty = DB::table('main_setting')->where('name','bed_qty')->value('value'); 

        if (!$token || !$hospcode) {
            return response()->json([
                'ok' => false,
                'message' => 'Missing opoh_token or hospital_code in main_setting.'
            ], 422);
        }

    // 2) ช่วงวันที่ (default = 10 วันย้อนหลัง)---------------------------------------------------------------------
        $start = $request->query('start_date');
        $end   = $request->query('end_date');

        if (!$start || !$end) {
            $today = Carbon::today();
            $start = $today->copy()->subDays(10)->toDateString();
            $end   = $today->toDateString();
        }

    // 3) Query จากฐาน HOSxP (connection 'hosxp')
        // 3.1 ข้อมูล OPD--------------------------------------------------------------------------------------------
        $sqlOpd = '
            SELECT ? AS hospcode,vstdate,COUNT(DISTINCT hn) AS hn_total,COUNT(vn) AS visit_total,
            SUM(CASE WHEN diagtype ="OP" THEN 1 ELSE 0 END) AS visit_total_op,
            SUM(CASE WHEN diagtype ="PP" THEN 1 ELSE 0 END) AS visit_total_pp,
			SUM(CASE WHEN endpoint ="Y" THEN 1 ELSE 0 END) AS visit_endpoint,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND incup = "Y" THEN 1 ELSE 0 END) AS visit_ucs_incup,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND inprov = "Y" THEN 1 ELSE 0 END) AS visit_ucs_inprov,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND outprov = "Y" THEN 1 ELSE 0 END) AS visit_ucs_outprov,
            SUM(CASE WHEN hipdata_code IN ("OFC") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_ofc,
            SUM(CASE WHEN hipdata_code IN ("BKK") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_bkk,
            SUM(CASE WHEN hipdata_code IN ("BMT") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_bmt,
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_sss,
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_lgo,
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_fss,
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_stp,
            SUM(CASE WHEN (paidst IN ("01","03") OR hipdata_code IN ("A1","A9")) THEN 1 ELSE 0 END) AS visit_pay,
            SUM(CASE WHEN ppfs = "Y" THEN 1 ELSE 0 END) AS visit_ppfs,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND uccr = "Y" THEN 1 ELSE 0 END) AS visit_ucs_cr,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND herb = "Y" THEN 1 ELSE 0 END) AS visit_ucs_herb,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND healthmed = "Y" THEN 1 ELSE 0 END) AS visit_ucs_healthmed,
			SUM(CASE WHEN healthmed = "Y" THEN 1 ELSE 0 END) AS visit_healthmed,
			SUM(CASE WHEN dent = "Y" THEN 1 ELSE 0 END) AS visit_dent,
			SUM(CASE WHEN physic = "Y" THEN 1 ELSE 0 END) AS visit_physic,
			SUM(CASE WHEN referout_inprov = "Y" THEN 1 ELSE 0 END) AS visit_referout_inprov,
			SUM(CASE WHEN referout_outprov = "Y" THEN 1 ELSE 0 END) AS visit_referout_outprov,
            SUM(CASE WHEN referout_inprov_ipd = "Y" THEN 1 ELSE 0 END) AS visit_referout_inprov_ipd,
			SUM(CASE WHEN referout_outprov_ipd = "Y" THEN 1 ELSE 0 END) AS visit_referout_outprov_ipd,
            SUM(CASE WHEN referin_inprov = "Y" THEN 1 ELSE 0 END) AS visit_referin_inprov,
			SUM(CASE WHEN referin_outprov = "Y" THEN 1 ELSE 0 END) AS visit_referin_outprov,
            SUM(CASE WHEN referback_inprov = "Y" THEN 1 ELSE 0 END) AS visit_referback_inprov,
		    SUM(CASE WHEN referback_outprov = "Y" THEN 1 ELSE 0 END) AS visit_referback_outprov,
            SUM(income) AS inc_total,
            SUM(inc03) AS inc_lab_total,
            SUM(inc12) AS inc_drug_total,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND incup = "Y" THEN income ELSE 0 END) AS inc_ucs_incup,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND incup = "Y" THEN inc03 ELSE 0 END) AS inc_lab_ucs_incup,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND incup = "Y" THEN inc12 ELSE 0 END) AS inc_drug_ucs_incup,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND inprov = "Y" THEN income ELSE 0 END) AS inc_ucs_inprov,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND inprov = "Y" THEN inc03 ELSE 0 END) AS inc_lab_ucs_inprov,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND inprov = "Y" THEN inc12 ELSE 0 END) AS inc_drug_ucs_inprov,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND outprov = "Y" THEN income ELSE 0 END) AS inc_ucs_outprov,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND outprov = "Y" THEN inc03 ELSE 0 END) AS inc_lab_ucs_outprov,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") AND outprov = "Y" THEN inc12 ELSE 0 END) AS inc_drug_ucs_outprov,
            SUM(CASE WHEN hipdata_code IN ("OFC") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS inc_ofc,            
            SUM(CASE WHEN hipdata_code IN ("OFC") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS inc_lab_ofc, 
            SUM(CASE WHEN hipdata_code IN ("OFC") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS inc_drug_ofc, 
            SUM(CASE WHEN hipdata_code IN ("BKK") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS inc_bkk,            
            SUM(CASE WHEN hipdata_code IN ("BKK") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS inc_lab_bkk, 
            SUM(CASE WHEN hipdata_code IN ("BKK") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS inc_drug_bkk,
            SUM(CASE WHEN hipdata_code IN ("BMT") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS inc_bmt,            
            SUM(CASE WHEN hipdata_code IN ("BMT") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS inc_lab_bmt, 
            SUM(CASE WHEN hipdata_code IN ("BMT") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS inc_drug_bmt,
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS inc_sss,            
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS inc_lab_sss, 
            SUM(CASE WHEN hipdata_code IN ("SSS","SSI") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS inc_drug_sss,         
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS inc_lgo,            
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS inc_lab_lgo, 
            SUM(CASE WHEN hipdata_code IN ("LGO") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS inc_drug_lgo,            
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS inc_fss,            
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS inc_lab_fss, 
            SUM(CASE WHEN hipdata_code IN ("NRD","NRH") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS inc_drug_fss,  
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN income ELSE 0 END) AS inc_stp,            
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN inc03 ELSE 0 END) AS inc_lab_stp, 
            SUM(CASE WHEN hipdata_code IN ("STP") AND paidst NOT IN ("01","03") THEN inc12 ELSE 0 END) AS inc_drug_stp,  
            SUM(CASE WHEN (hipdata_code IN ("A1","A9") OR paidst IN ("01","03")) THEN income ELSE 0 END) AS inc_pay,            
            SUM(CASE WHEN (hipdata_code IN ("A1","A9") OR paidst IN ("01","03")) THEN inc03 ELSE 0 END) AS inc_lab_pay, 
            SUM(CASE WHEN (hipdata_code IN ("A1","A9") OR paidst IN ("01","03")) THEN inc12 ELSE 0 END) AS inc_drug_pay,
            SUM(inc_ppfs) AS inc_ppfs,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") THEN inc_uccr ELSE 0 END) AS inc_uccr,
            SUM(CASE WHEN hipdata_code IN ("UCS","WEL","DIS") AND paidst NOT IN ("01","03") THEN inc_herb ELSE 0 END) AS inc_herb
            FROM (SELECT v.vstdate,v.vn,v.hn,v.pttype,p.hipdata_code,p.paidst,v.income,v.inc03,v.inc12 ,v.pdx,
			IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
			IF(i.icd10 IS NULL,"OP","PP") AS diagtype,IF(vp.hospmain IS NOT NULL,"Y","") AS incup,
            IF(vp1.hospmain IS NOT NULL,"Y","") AS inprov,IF(vp2.hospmain IS NOT NULL,"Y","") AS outprov,
            IF(op.vn IS NOT NULL,"Y","") AS ppfs,IF(op1.vn IS NOT NULL,"Y","") AS uccr,IF(op2.vn IS NOT NULL,"Y","") AS herb,
            COALESCE(inc_ppfs.inc, 0) AS inc_ppfs,COALESCE(inc_uccr.inc, 0) AS inc_uccr,COALESCE(inc_herb.inc, 0) AS inc_herb,
			IF(dt.vn IS NOT NULL,"Y","") AS dent,IF(pl.vn IS NOT NULL,"Y","") AS physic,IF(hm.vn IS NOT NULL,"Y","") AS healthmed,
			IF(r.vn IS NOT NULL,"Y","") AS referout_inprov,IF(r1.vn IS NOT NULL,"Y","") AS referout_outprov,
            IF(re.vn IS NOT NULL,"Y","") AS referout_inprov_ipd,IF(re1.vn IS NOT NULL,"Y","") AS referout_outprov_ipd,
            IF(ri.vn IS NOT NULL,"Y","") AS referin_inprov,IF(ri1.vn IS NOT NULL,"Y","") AS referin_outprov,
			IF(rb.vn IS NOT NULL,"Y","") AS referback_inprov,IF(rb1.vn IS NOT NULL,"Y","") AS referback_outprov
            FROM vn_stat v
            LEFT JOIN ipt i ON i.vn=v.vn
            LEFT JOIN pttype p ON p.pttype=v.pttype
            LEFT JOIN visit_pttype vp ON vp.vn =v.vn 
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
            LEFT JOIN visit_pttype vp1 ON vp1.vn =v.vn 
                AND vp1.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs =""))
            LEFT JOIN visit_pttype vp2 ON vp2.vn =v.vn 
                AND vp2.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            LEFT JOIN opitemrece op ON op.vn=v.vn AND op.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN opitemrece op1 ON op1.vn=v.vn AND op1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE uc_cr = "Y")
            LEFT JOIN opitemrece op2 ON op2.vn=v.vn AND op2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE herb32 = "Y")
            LEFT JOIN health_med_service hm ON hm.vn=v.vn
			LEFT JOIN physic_list pl ON pl.vn=v.vn
			LEFT JOIN dtmain dt ON dt.vn=v.vn
			LEFT JOIN referout r ON r.vn=v.vn AND r.refer_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
			LEFT JOIN referout r1 ON r1.vn=v.vn AND r1.refer_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            LEFT JOIN referout re ON re.vn=i.an AND re.refer_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
			LEFT JOIN referout re1 ON re1.vn=i.an AND re1.refer_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            LEFT JOIN referin ri ON ri.vn=v.vn AND ri.refer_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
			LEFT JOIN referin ri1 ON ri1.vn=v.vn AND ri1.refer_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
           	LEFT JOIN refer_reply rb ON rb.vn=v.vn AND rb.dest_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
			LEFT JOIN refer_reply rb1 ON rb1.vn=v.vn AND rb1.dest_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
            LEFT JOIN hrims.lookup_icd10 i ON i.icd10=v.pdx AND i.pp="Y"
			LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND ep.vstdate=v.vstdate AND ep.claimCode LIKE "EP%"
            LEFT JOIN (SELECT o.vn,SUM(o.sum_price) AS inc FROM opitemrece o
                INNER JOIN hrims.lookup_icode li ON o.icode = li.icode
                WHERE o.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" 
                GROUP BY o.vn) inc_ppfs ON inc_ppfs.vn=v.vn
            LEFT JOIN (SELECT o.vn,SUM(o.sum_price) AS inc FROM opitemrece o
                INNER JOIN hrims.lookup_icode li ON o.icode = li.icode
                WHERE o.vstdate BETWEEN ? AND ? AND li.uc_cr = "Y" 
                GROUP BY o.vn) inc_uccr ON inc_uccr.vn=v.vn
            LEFT JOIN (SELECT o.vn,SUM(o.sum_price) AS inc FROM opitemrece o
                INNER JOIN hrims.lookup_icode li ON o.icode = li.icode
                WHERE o.vstdate BETWEEN ? AND ? AND li.herb32 = "Y" 
                GROUP BY o.vn) inc_herb ON inc_herb.vn=v.vn
            WHERE v.vstdate BETWEEN ? AND ? GROUP BY v.vn) AS a GROUP BY vstdate ';

        $rowsOpd = DB::connection('hosxp')->select($sqlOpd, [$hospcode, $start, $end, $start, $end, $start, $end, $start, $end]);

        $opdRecords = array_map(function ($r) {
            return [
                'vstdate'              => $r->vstdate,
                'hn_total'             => (int)$r->hn_total,
                'visit_total'          => (int)$r->visit_total,
                'visit_total_op'       => (int)$r->visit_total_op,
                'visit_total_pp'       => (int)$r->visit_total_pp,
                'visit_endpoint'       => (int)$r->visit_endpoint,
                'visit_ucs_incup'      => (int)$r->visit_ucs_incup,
                'visit_ucs_inprov'     => (int)$r->visit_ucs_inprov,
                'visit_ucs_outprov'    => (int)$r->visit_ucs_outprov,
                'visit_ofc'            => (int)$r->visit_ofc,
                'visit_bkk'            => (int)$r->visit_bkk,
                'visit_bmt'            => (int)$r->visit_bmt,
                'visit_sss'            => (int)$r->visit_sss,
                'visit_lgo'            => (int)$r->visit_lgo,
                'visit_fss'            => (int)$r->visit_fss,
                'visit_stp'            => (int)$r->visit_stp,
                'visit_pay'            => (int)$r->visit_pay,
                'visit_ppfs'           => (int)$r->visit_ppfs,
                'visit_ucs_cr'         => (int)$r->visit_ucs_cr,
                'visit_ucs_herb'       => (int)$r->visit_ucs_herb,
                'visit_ucs_healthmed'  => (int)$r->visit_ucs_healthmed,
                'visit_healthmed'      => (int)$r->visit_healthmed,
                'visit_dent'           => (int)$r->visit_dent,
                'visit_physic'         => (int)$r->visit_physic,
                'visit_referout_inprov'         => (int)$r->visit_referout_inprov,                
                'visit_referout_outprov'        => (int)$r->visit_referout_outprov,
                'visit_referout_inprov_ipd'     => (int)$r->visit_referout_inprov_ipd,
                'visit_referout_outprov_ipd'    => (int)$r->visit_referout_outprov_ipd,
                'visit_referin_inprov'          => (int)$r->visit_referin_inprov,
                'visit_referin_outprov'         => (int)$r->visit_referin_outprov,
                'visit_referback_inprov'        => (int)$r->visit_referback_inprov,
                'visit_referback_outprov'       => (int)$r->visit_referback_outprov,
                'inc_total'            => (float)$r->inc_total,
                'inc_lab_total'        => (float)$r->inc_lab_total,
                'inc_drug_total'       => (float)$r->inc_drug_total,
                'inc_ucs_incup'        => (float)$r->inc_ucs_incup,
                'inc_lab_ucs_incup'    => (float)$r->inc_lab_ucs_incup,
                'inc_drug_ucs_incup'   => (float)$r->inc_drug_ucs_incup,
                'inc_ucs_inprov'       => (float)$r->inc_ucs_inprov,
                'inc_lab_ucs_inprov'   => (float)$r->inc_lab_ucs_inprov,
                'inc_drug_ucs_inprov'  => (float)$r->inc_drug_ucs_inprov,
                'inc_ucs_outprov'      => (float)$r->inc_ucs_outprov,
                'inc_lab_ucs_outprov'  => (float)$r->inc_lab_ucs_outprov,
                'inc_drug_ucs_outprov' => (float)$r->inc_drug_ucs_outprov,
                'inc_ofc'              => (float)$r->inc_ofc,
                'inc_lab_ofc'          => (float)$r->inc_lab_ofc,
                'inc_drug_ofc'         => (float)$r->inc_drug_ofc,
                'inc_bkk'              => (float)$r->inc_bkk,
                'inc_lab_bkk'          => (float)$r->inc_lab_bkk,
                'inc_drug_bkk'         => (float)$r->inc_drug_bkk,
                'inc_bmt'              => (float)$r->inc_bmt,
                'inc_lab_bmt'          => (float)$r->inc_lab_bmt,
                'inc_drug_bmt'         => (float)$r->inc_drug_bmt,
                'inc_sss'              => (float)$r->inc_sss,
                'inc_lab_sss'          => (float)$r->inc_lab_sss,
                'inc_drug_sss'         => (float)$r->inc_drug_sss,
                'inc_lgo'              => (float)$r->inc_lgo,
                'inc_lab_lgo'          => (float)$r->inc_lab_lgo,
                'inc_drug_lgo'         => (float)$r->inc_drug_lgo,
                'inc_fss'              => (float)$r->inc_fss,
                'inc_lab_fss'          => (float)$r->inc_lab_fss,
                'inc_drug_fss'         => (float)$r->inc_drug_fss,
                'inc_stp'              => (float)$r->inc_stp,
                'inc_lab_stp'          => (float)$r->inc_lab_stp,
                'inc_drug_stp'         => (float)$r->inc_drug_stp,
                'inc_pay'              => (float)$r->inc_pay,
                'inc_lab_pay'          => (float)$r->inc_lab_pay,
                'inc_drug_pay'         => (float)$r->inc_drug_pay,
                'inc_ppfs'             => (float)$r->inc_ppfs,
                'inc_uccr'             => (float)$r->inc_uccr,
                'inc_herb'             => (float)$r->inc_herb,
            ];
        }, $rowsOpd);
        
        // 3.2 ข้อมูล IPD-----------------------------------------------------------------------------------------------------------
        $sqlIpd = '
            SELECT ? AS hospcode,dchdate,COUNT(DISTINCT an) AS an_total ,sum(admdate) AS admdate,        
            ROUND((SUM(a.admdate) * 100) / (? * CASE WHEN YEAR(a.dchdate) = YEAR(CURDATE()) AND MONTH(a.dchdate) = MONTH(CURDATE()) 
                THEN DAY(CURDATE()) ELSE DAY(LAST_DAY(a.dchdate))END), 2) AS bed_occupancy,
            ROUND((SUM(a.admdate) / CASE WHEN YEAR(a.dchdate) = YEAR(CURDATE()) AND MONTH(a.dchdate) = MONTH(CURDATE()) 
                THEN DAY(CURDATE()) ELSE DAY(LAST_DAY(a.dchdate)) END), 2) AS active_bed, 
			ROUND(SUM(rw)/COUNT(DISTINCT an),2) AS cmi,ROUND(SUM(rw),5) AS adjrw, 
            SUM(income) AS inc_total,
			SUM(inc03) AS inc_lab_total,
            SUM(inc12) AS inc_drug_total
			FROM (SELECT a.dchdate,a.an,a.admdate,a.rw,a.income,a.inc03,inc12
			FROM an_stat a 
			LEFT JOIN pttype p ON p.pttype=a.pttype
            WHERE a.dchdate BETWEEN ? AND ?
            AND a.pdx NOT IN ("Z290","Z208")
            GROUP BY a.an ) AS a
			GROUP BY dchdate';

        $rowsIpd = DB::connection('hosxp')->select($sqlIpd, [$hospcode, $bed_qty, $start, $end]);

        $ipdRecords = array_map(function ($r) {
            return [
                'dchdate'           => $r->dchdate,
                'an_total'          => (int)$r->an_total,
                'admdate'           => (int)$r->admdate,
                'bed_occupancy'     => (float)$r->bed_occupancy,
                'active_bed'        => (float)$r->active_bed,
                'cmi'               => (float)$r->cmi,
                'adjrw'             => (float)$r->adjrw,
                'inc_total'         => (float)$r->inc_total,   
                'inc_lab_total'     => (float)$r->inc_lab_total,   
                'inc_drug_total'    => (float)$r->inc_drug_total,   
            ];
        }, $rowsIpd);

    // 3.3 ข้อมูล UPdate Hospital ปัจจุบัน-------------------------------------------------------------------------------------------------------
        $sqlhospital = '
            SELECT ? AS hospcode,IFNULL((SELECT SUM(bed_qty) FROM hrims.lookup_ward 
            WHERE (ward_normal = "Y" OR ward_m ="Y" OR ward_f ="Y" OR ward_vip="Y")),0) AS bed_qty,
            IFNULL(COUNT(DISTINCT bedno),0) AS bed_use
            FROM (SELECT i.an,i.regdate,i.regtime,i.ward,b.bedno,b.export_code
            FROM ipt i 
			INNER JOIN iptadm ia ON ia.an = i.an
            LEFT JOIN bedno b ON b.bedno=ia.bedno
			WHERE i.confirm_discharge = "N" 
			AND b.export_code IS NOT NULL AND b.export_code <>"") AS a ';

        $rowshospital = DB::connection('hosxp')->select($sqlhospital, [$hospcode]);

        $hospitalRecords = array_map(function ($r) use ($hospcode) {
            return [
                'hospcode' => $hospcode,
                'bed_qty'  => (int)($r->bed_qty ?? $bed_qty ?? 0),
                'bed_use'  => (int)($r->bed_use ?? 0),
            ];
        }, $rowshospital);

    // 3.4 ข้อมูล IPD_bed-----------------------------------------------------------------------------------------------------------
        $sqlIpd_bed = '
            SELECT ? AS hospcode,
            IFNULL(b.export_code,0) AS bed_code,
            IFNULL(COUNT(DISTINCT b.bedno),0) AS bed_qty,
            IFNULL(b1.bed_use,0) AS bed_use
            FROM bedno b
            LEFT JOIN (SELECT b.export_code,COUNT(DISTINCT b.bedno) AS bed_use
            FROM ipt i
            INNER JOIN iptadm ia ON ia.an=i.an
            LEFT JOIN bedno b ON b.bedno=ia.bedno
            WHERE b.export_code IS NOT NULL AND b.export_code <>""
            AND i.confirm_discharge = "N"
            GROUP BY b.export_code) b1 ON b1.export_code=b.export_code
            WHERE b.export_code IS NOT NULL AND b.export_code <>""
            GROUP BY b.export_code 
            ORDER BY b.export_code';

        $rowsIpd_bed = DB::connection('hosxp')->select($sqlIpd_bed, [$hospcode]);

        $ipdbedRecords = array_map(function ($r) {
            return [
                'bed_code' => (string)$r->bed_code,
                'bed_qty'  => (int)$r->bed_qty,
                'bed_use'  => (int)$r->bed_use,              
            ];
        }, $rowsIpd_bed);

    // 4) ส่งข้อมูลไปยัง API ปลายทาง-----------------------------------------------------------------------------------------------

        $chunkSize = (int)($request->query('chunk', 200));

        // ---- OPD ----
        //$urlOpd = config('services.opoh.opd_url', 'http://127.0.0.1:8837/api/opd');
        $urlOpd = config('services.opoh.opd_url', 'http://1.179.128.29:3394/api/opd');
        $summaryOpd = $this->sendChunks($opdRecords, $urlOpd, $token, $hospcode, 'OPD', $chunkSize);

        // ---- IPD ----
        //$urlIpd = config('services.opoh.ipd_url', 'http://127.0.0.1:8837/api/ipd');
        $urlIpd = config('services.opoh.ipd_url', 'http://1.179.128.29:3394/api/ipd');
        $summaryIpd = $this->sendChunks($ipdRecords, $urlIpd, $token, $hospcode, 'IPD', $chunkSize);

        // ---- IPD BED ----
        //$urlIpd_bed = config('services.opoh.ipd_bed_url', 'http://127.0.0.1:8837/api/ipd_bed_dep');
        $urlIpd_bed = config('services.opoh.ipd_bed_url', 'http://1.179.128.29:3394/api/ipd_bed_dep');
        $summaryIpd_bed = $this->sendChunks($ipdbedRecords, $urlIpd_bed, $token, $hospcode, 'IPD_BED', $chunkSize);

        // ---- HOSPITAL ----
        //$urlhospital = config('services.opoh.hospital_url', 'http://127.0.0.1:8837/api/hospital_config');
        $urlhospital = config('services.opoh.hospital_url', 'http://1.179.128.29:3394/api/hospital_config');
        $summaryHospital = $this->sendChunks($hospitalRecords, $urlhospital, $token, $hospcode, 'HOSPITAL', $chunkSize);

        // กัน error ถ้าไม่ส่ง IPD
            // $summaryIpd = $summaryIpd ?? [
            //     'batches' => 0,
            //     'sent'    => 0,
            //     'failed'  => 0,
            //     'details' => [],
            // ];

    // 5) สรุปผลรวม
        // =====================================================
        return response()->json([
            'ok'         => $summaryOpd['failed'] === 0 
                        && $summaryIpd['failed'] === 0 
                        && $summaryIpd_bed['failed'] === 0 
                        && $summaryHospital['failed'] === 0,
            'hospcode'   => $hospcode,
            'start_date' => $start,
            'end_date'   => $end,
            'received'   => [
                'opd' => count($opdRecords),
                'ipd' => count($ipdRecords),
                'ipd_bed' => count($ipdbedRecords),
                'hospital' => count($hospitalRecords),
            ],
            'summary'    => [
                'opd' => $summaryOpd,
                'ipd' => $summaryIpd,
                'ipd_bed' => $summaryIpd_bed,
                'hospital' => $summaryHospital,
            ],
            'sample'     => [
                'opd' => $opdRecords[0] ?? null,
                'ipd' => $ipdRecords[0] ?? null,
                'ipd_bed' => $ipdbedRecords[0] ?? null,
                'hospital' => $hospitalRecords[0] ?? null,
            ],
        ], 200);
    }

    /**
     * Helper function ส่งข้อมูลเป็นก้อน ๆ
     */
    private function sendChunks(array $records, string $url, string $token, string $hospcode, string $prefix, int $chunkSize)
    {
        $chunks = array_chunk($records, max(1, $chunkSize));
        $summary = [
            'batches' => count($chunks),
            'sent'    => 0,
            'failed'  => 0,
            'details' => [],
        ];

        foreach ($chunks as $i => $chunk) {
            // $dates = array_column($chunk, $prefix === 'OPD' ? 'vstdate' : 'admdate');
            $dates = match($prefix) {
                'OPD' => array_column($chunk, 'vstdate'),
                'IPD' => array_column($chunk, 'dchdate'),
                default => []  // HOSPITAL
            };
            sort($dates);
            $idempotencyKey = hash('sha256', $hospcode . "|$prefix|" . implode(',', $dates));

            try {
                $res = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(20)
                    ->retry(3, 300)
                    ->withHeaders([
                        'Idempotency-Key' => $idempotencyKey,
                    ])
                    ->post($url, ['records' => $chunk]);

                $status = $res->status();
                $ok = $res->successful() || $status === 207;

                $summary[$ok ? 'sent' : 'failed'] += count($chunk);
                $summary['details'][] = [
                    'batch'  => $i + 1,
                    'size'   => count($chunk),
                    'status' => $status,
                    'body'   => $res->json() ?? $res->body(),
                ];
            } catch (\Throwable $e) {
                $summary['failed'] += count($chunk);
                $summary['details'][] = [
                    'batch'  => $i + 1,
                    'size'   => count($chunk),
                    'status' => 'exception',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $summary;
    }    

}
