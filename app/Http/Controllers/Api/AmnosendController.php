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
        set_time_limit(0);

        // 1) โหลดค่าพื้นฐานจาก main_setting-------------------------------------------------------------------------
        $token    = DB::table('main_setting')->where('name', 'opoh_token')->value('value');
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $bed_qty = DB::table('main_setting')->where('name', 'bed_qty')->value('value');

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
            SELECT 
                ? AS hospcode, a.vstdate,
                COUNT(DISTINCT a.hn) AS hn_total,
                COUNT(a.vn) AS visit_total,
                SUM(CASE WHEN a.diagtype ="OP" THEN 1 ELSE 0 END) AS visit_total_op,
                SUM(CASE WHEN a.diagtype ="PP" THEN 1 ELSE 0 END) AS visit_total_pp,
                SUM(CASE WHEN a.endpoint ="Y" THEN 1 ELSE 0 END) AS visit_endpoint,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.incup = "Y" THEN 1 ELSE 0 END) AS visit_ucs_incup,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.inprov = "Y" THEN 1 ELSE 0 END) AS visit_ucs_inprov,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.outprov = "Y" THEN 1 ELSE 0 END) AS visit_ucs_outprov,
                SUM(CASE WHEN a.hipdata_code IN ("OFC") AND a.paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_ofc,
                SUM(CASE WHEN a.hipdata_code IN ("BKK") AND a.paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_bkk,
                SUM(CASE WHEN a.hipdata_code IN ("BMT") AND a.paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_bmt,
                SUM(CASE WHEN a.hipdata_code IN ("SSS","SSI") AND a.paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_sss,
                SUM(CASE WHEN a.hipdata_code IN ("LGO") AND a.paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_lgo,
                SUM(CASE WHEN a.hipdata_code IN ("NRD","NRH") AND a.paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_fss,
                SUM(CASE WHEN a.hipdata_code IN ("STP") AND a.paidst NOT IN ("01","03") THEN 1 ELSE 0 END) AS visit_stp,
                SUM(CASE WHEN (a.paidst IN ("01","03") OR a.hipdata_code IN ("A1","A9")) THEN 1 ELSE 0 END) AS visit_pay,
                COUNT(DISTINCT CASE WHEN inc.ppfs = "Y" THEN a.vn END) AS visit_ppfs,
                COUNT(DISTINCT CASE WHEN inc.ppfs_claim = "Y" THEN a.vn END) AS visit_ppfs_claim,
                COUNT(DISTINCT CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND inc.uccr = "Y" THEN a.vn END) AS visit_ucs_cr,
                COUNT(DISTINCT CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND inc.uccr_claim = "Y" THEN a.vn END) AS visit_ucs_cr_claim,
                COUNT(DISTINCT CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND inc.herb = "Y" THEN a.vn END) AS visit_ucs_herb,
                COUNT(DISTINCT CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND inc.herb_claim = "Y" THEN a.vn END) AS visit_ucs_herb_claim,
                COUNT(DISTINCT CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.healthmed = "Y" THEN a.vn END) AS visit_ucs_healthmed,
                COUNT(DISTINCT CASE WHEN a.healthmed = "Y" THEN a.vn END) AS visit_healthmed,
                COUNT(DISTINCT CASE WHEN a.dent = "Y" THEN a.vn END) AS visit_dent,
                COUNT(DISTINCT CASE WHEN a.physic = "Y" THEN a.vn END) AS visit_physic,
                COUNT(DISTINCT CASE WHEN a.anc = "Y" THEN a.vn END) AS visit_anc,
                COUNT(DISTINCT CASE WHEN a.telehealth = "Y" THEN a.vn END) AS visit_telehealth,
                COALESCE(ma_booking.cnt, 0) AS visit_moph_oapp_booking,
                COUNT(DISTINCT CASE WHEN a.moph_oapp = "Y" THEN a.cid END) AS visit_moph_oapp,
                COUNT(DISTINCT CASE WHEN a.referout_inprov = "Y" THEN a.vn END) AS visit_referout_inprov,
                COUNT(DISTINCT CASE WHEN a.referout_outprov = "Y" THEN a.vn END) AS visit_referout_outprov,
                COUNT(DISTINCT CASE WHEN a.referout_inprov_ipd = "Y" THEN a.vn END) AS visit_referout_inprov_ipd,
                COUNT(DISTINCT CASE WHEN a.referout_outprov_ipd = "Y" THEN a.vn END) AS visit_referout_outprov_ipd,
                COUNT(DISTINCT CASE WHEN a.referin_inprov = "Y" THEN a.vn END) AS visit_referin_inprov,
                COUNT(DISTINCT CASE WHEN a.referin_outprov = "Y" THEN a.vn END) AS visit_referin_outprov,
                COUNT(DISTINCT CASE WHEN a.referin_inprov_ipd = "Y" THEN a.vn END) AS visit_referin_inprov_ipd,
                COUNT(DISTINCT CASE WHEN a.referin_outprov_ipd = "Y" THEN a.vn END) AS visit_referin_outprov_ipd,
                COALESCE(rb.visit_referback_inprov, 0) AS visit_referback_inprov,
                COALESCE(rb.visit_referback_outprov, 0) AS visit_referback_outprov,
                COALESCE(op.visit_operation, 0) AS visit_operation,
                SUM(a.income) AS inc_total, 
                SUM(a.inc03) AS inc_lab_total, 
                SUM(a.inc12) AS inc_drug_total,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.incup = "Y" THEN a.income ELSE 0 END) AS inc_ucs_incup,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.incup = "Y" THEN a.inc03 ELSE 0 END) AS inc_lab_ucs_incup,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.incup = "Y" THEN a.inc12 ELSE 0 END) AS inc_drug_ucs_incup,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.inprov = "Y" THEN a.income ELSE 0 END) AS inc_ucs_inprov,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.inprov = "Y" THEN a.inc03 ELSE 0 END) AS inc_lab_ucs_inprov,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.inprov = "Y" THEN a.inc12 ELSE 0 END) AS inc_drug_ucs_inprov,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.outprov = "Y" THEN a.income ELSE 0 END) AS inc_ucs_outprov,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.outprov = "Y" THEN a.inc03 ELSE 0 END) AS inc_lab_ucs_outprov,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") AND a.outprov = "Y" THEN a.inc12 ELSE 0 END) AS inc_drug_ucs_outprov,
                SUM(CASE WHEN a.hipdata_code IN ("OFC") AND a.paidst NOT IN ("01","03") THEN a.income ELSE 0 END) AS inc_ofc,
                SUM(CASE WHEN a.hipdata_code IN ("OFC") AND a.paidst NOT IN ("01","03") THEN a.inc03 ELSE 0 END) AS inc_lab_ofc,
                SUM(CASE WHEN a.hipdata_code IN ("OFC") AND a.paidst NOT IN ("01","03") THEN a.inc12 ELSE 0 END) AS inc_drug_ofc,
                SUM(CASE WHEN a.hipdata_code IN ("BKK") AND a.paidst NOT IN ("01","03") THEN a.income ELSE 0 END) AS inc_bkk,
                SUM(CASE WHEN a.hipdata_code IN ("BKK") AND a.paidst NOT IN ("01","03") THEN a.inc03 ELSE 0 END) AS inc_lab_bkk,
                SUM(CASE WHEN a.hipdata_code IN ("BKK") AND a.paidst NOT IN ("01","03") THEN a.inc12 ELSE 0 END) AS inc_drug_bkk,
                SUM(CASE WHEN a.hipdata_code IN ("BMT") AND a.paidst NOT IN ("01","03") THEN a.income ELSE 0 END) AS inc_bmt,
                SUM(CASE WHEN a.hipdata_code IN ("BMT") AND a.paidst NOT IN ("01","03") THEN a.inc03 ELSE 0 END) AS inc_lab_bmt,
                SUM(CASE WHEN a.hipdata_code IN ("BMT") AND a.paidst NOT IN ("01","03") THEN a.inc12 ELSE 0 END) AS inc_drug_bmt,
                SUM(CASE WHEN a.hipdata_code IN ("SSS","SSI") AND a.paidst NOT IN ("01","03") THEN a.income ELSE 0 END) AS inc_sss,
                SUM(CASE WHEN a.hipdata_code IN ("SSS","SSI") AND a.paidst NOT IN ("01","03") THEN a.inc03 ELSE 0 END) AS inc_lab_sss,
                SUM(CASE WHEN a.hipdata_code IN ("SSS","SSI") AND a.paidst NOT IN ("01","03") THEN a.inc12 ELSE 0 END) AS inc_drug_sss,
                SUM(CASE WHEN a.hipdata_code IN ("LGO") AND a.paidst NOT IN ("01","03") THEN a.income ELSE 0 END) AS inc_lgo,
                SUM(CASE WHEN a.hipdata_code IN ("LGO") AND a.paidst NOT IN ("01","03") THEN a.inc03 ELSE 0 END) AS inc_lab_lgo,
                SUM(CASE WHEN a.hipdata_code IN ("LGO") AND a.paidst NOT IN ("01","03") THEN a.inc12 ELSE 0 END) AS inc_drug_lgo,
                SUM(CASE WHEN a.hipdata_code IN ("NRD","NRH") AND a.paidst NOT IN ("01","03") THEN a.income ELSE 0 END) AS inc_fss,
                SUM(CASE WHEN a.hipdata_code IN ("NRD","NRH") AND a.paidst NOT IN ("01","03") THEN a.inc03 ELSE 0 END) AS inc_lab_fss,
                SUM(CASE WHEN a.hipdata_code IN ("NRD","NRH") AND a.paidst NOT IN ("01","03") THEN a.inc12 ELSE 0 END) AS inc_drug_fss,
                SUM(CASE WHEN a.hipdata_code IN ("STP") AND a.paidst NOT IN ("01","03") THEN a.income ELSE 0 END) AS inc_stp,
                SUM(CASE WHEN a.hipdata_code IN ("STP") AND a.paidst NOT IN ("01","03") THEN a.inc03 ELSE 0 END) AS inc_lab_stp,
                SUM(CASE WHEN a.hipdata_code IN ("STP") AND a.paidst NOT IN ("01","03") THEN a.inc12 ELSE 0 END) AS inc_drug_stp,
                SUM(CASE WHEN (a.hipdata_code IN ("A1","A9") OR a.paidst IN ("01","03")) THEN a.income ELSE 0 END) AS inc_pay,
                SUM(CASE WHEN (a.hipdata_code IN ("A1","A9") OR a.paidst IN ("01","03")) THEN a.inc03 ELSE 0 END) AS inc_lab_pay,
                SUM(CASE WHEN (a.hipdata_code IN ("A1","A9") OR a.paidst IN ("01","03")) THEN a.inc12 ELSE 0 END) AS inc_drug_pay,
                SUM(COALESCE(inc.inc_ppfs, 0)) AS inc_ppfs, 
                SUM(COALESCE(inc.inc_ppfs_claim, 0)) AS inc_ppfs_claim, 
                SUM(COALESCE(inc.inc_ppfs_receive, 0)) AS inc_ppfs_receive,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") THEN COALESCE(inc.inc_uccr, 0) ELSE 0 END) AS inc_uccr,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") THEN COALESCE(inc.inc_uccr_claim, 0) ELSE 0 END) AS inc_uccr_claim,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") THEN COALESCE(inc.inc_uccr_receive, 0) ELSE 0 END) AS inc_uccr_receive,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") THEN COALESCE(inc.inc_herb, 0) ELSE 0 END) AS inc_herb,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") THEN COALESCE(inc.inc_herb_claim, 0) ELSE 0 END) AS inc_herb_claim,
                SUM(CASE WHEN a.hipdata_code IN ("UCS","WEL","DIS") AND a.paidst NOT IN ("01","03") THEN COALESCE(inc.inc_herb_receive, 0) ELSE 0 END) AS inc_herb_receive
            FROM (
                SELECT ov.vstdate, ov.vn, ov.hn, v.cid, v.income, v.inc03, v.inc12, p.hipdata_code, p.paidst,
                    IF(i.icd10 IS NULL, "OP", "PP") AS diagtype,
                    IF(vp.hospmain IS NOT NULL, "Y", "") AS incup,
                    IF(vp1.hospmain IS NOT NULL, "Y", "") AS inprov,
                    IF(vp2.hospmain IS NOT NULL, "Y", "") AS outprov,
                    IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"), "Y", NULL) AS endpoint,
                    IF(dt.vn IS NOT NULL, "Y", "") AS dent,
                    IF(pl.vn IS NOT NULL, "Y", "") AS physic,
                    IF(hm.vn IS NOT NULL, "Y", "") AS healthmed,
                    IF(anc.vn IS NOT NULL, "Y", "") AS anc,
                    IF(oi.export_code = 5, "Y", "") AS telehealth,
                    IF(ma.cid IS NOT NULL, "Y", "") AS moph_oapp,
                    IF(r.vn IS NOT NULL, "Y", "") AS referout_inprov, IF(r1.vn IS NOT NULL, "Y", "") AS referout_outprov,
                    IF(re.vn IS NOT NULL, "Y", "") AS referout_inprov_ipd, IF(re1.vn IS NOT NULL, "Y", "") AS referout_outprov_ipd,
                    IF(ri.vn IS NOT NULL AND ip.vn IS NULL, "Y", "") AS referin_inprov, IF(ri1.vn IS NOT NULL AND ip.vn IS NULL, "Y", "") AS referin_outprov,
                    IF(rii.vn IS NOT NULL AND ip.vn IS NOT NULL, "Y", "") AS referin_inprov_ipd, IF(rii1.vn IS NOT NULL AND ip.vn IS NOT NULL, "Y", "") AS referin_outprov_ipd
                FROM ovst ov
                LEFT JOIN vn_stat v ON v.vn = ov.vn
                LEFT JOIN ipt ip ON ip.vn = ov.vn
                LEFT JOIN pttype p ON p.pttype = ov.pttype
                LEFT JOIN ovstist oi ON oi.ovstist = ov.ovstist
                LEFT JOIN hrims.lookup_icd10 i ON i.icd10 = v.pdx AND i.pp = "Y"
                LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = v.cid AND ep.vstdate = v.vstdate AND ep.claimCode LIKE "EP%"
                LEFT JOIN visit_pttype vp ON vp.vn = ov.vn AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs = "Y")
                LEFT JOIN visit_pttype vp1 ON vp1.vn = ov.vn AND vp1.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y" AND (hmain_ucs = "" OR hmain_ucs IS NULL))
                LEFT JOIN visit_pttype vp2 ON vp2.vn = ov.vn AND vp2.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN (SELECT DISTINCT vn FROM dtmain) dt ON dt.vn = ov.vn
                LEFT JOIN (SELECT DISTINCT vn FROM physic_list) pl ON pl.vn = ov.vn
                LEFT JOIN (SELECT DISTINCT vn FROM health_med_service) hm ON hm.vn = ov.vn
                LEFT JOIN (SELECT DISTINCT vn FROM person_anc_service) anc ON anc.vn = ov.vn
                LEFT JOIN moph_appointment_list ma ON ma.cid = v.cid AND ma.appointment_date = ov.vstdate
                LEFT JOIN referout r ON r.vn = ov.vn AND r.refer_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN referout r1 ON r1.vn = ov.vn AND r1.refer_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN referout re ON re.vn = ip.an AND re.refer_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN referout re1 ON re1.vn = ip.an AND re1.refer_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN referin ri ON ri.vn = ov.vn AND ri.refer_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN referin ri1 ON ri1.vn = ov.vn AND ri1.refer_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN referin rii ON rii.vn = ip.vn AND rii.refer_hospcode IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                LEFT JOIN referin rii1 ON rii1.vn = ip.vn AND rii1.refer_hospcode NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                WHERE ov.vstdate BETWEEN ? AND ?
            ) a
            LEFT JOIN (
                SELECT o.vn,
                    MAX(CASE WHEN li.ppfs = "Y" THEN "Y" ELSE "" END) as ppfs,
                    MAX(CASE WHEN li.ppfs = "Y" AND (oe.vn IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL) THEN "Y" ELSE "" END) as ppfs_claim,
                    SUM(CASE WHEN li.ppfs = "Y" THEN o.sum_price ELSE 0 END) as inc_ppfs,
                    SUM(CASE WHEN li.ppfs = "Y" AND (oe.vn IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL) THEN o.sum_price ELSE 0 END) as inc_ppfs_claim,
                    MAX(CASE WHEN li.ppfs = "Y" THEN COALESCE(stm.receive_total, 0) ELSE 0 END) as inc_ppfs_receive,
                    MAX(CASE WHEN li.uc_cr = "Y" THEN "Y" ELSE "" END) as uccr,
                    MAX(CASE WHEN li.uc_cr = "Y" AND (oe.vn IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL) THEN "Y" ELSE "" END) as uccr_claim,
                    SUM(CASE WHEN li.uc_cr = "Y" THEN o.sum_price ELSE 0 END) as inc_uccr,
                    SUM(CASE WHEN li.uc_cr = "Y" AND (oe.vn IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL) THEN o.sum_price ELSE 0 END) as inc_uccr_claim,
                    MAX(CASE WHEN li.uc_cr = "Y" THEN COALESCE(stm.receive_total, 0) ELSE 0 END) as inc_uccr_receive,
                    MAX(CASE WHEN li.herb32 = "Y" THEN "Y" ELSE "" END) as herb,
                    MAX(CASE WHEN li.herb32 = "Y" AND (oe.vn IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL) THEN "Y" ELSE "" END) as herb_claim,
                    SUM(CASE WHEN li.herb32 = "Y" THEN o.sum_price ELSE 0 END) as inc_herb,
                    SUM(CASE WHEN li.herb32 = "Y" AND (oe.vn IS NOT NULL OR rep.vn IS NOT NULL OR fdh.seq IS NOT NULL OR ec.hn IS NOT NULL) THEN o.sum_price ELSE 0 END) as inc_herb_claim,
                    MAX(CASE WHEN li.herb32 = "Y" THEN COALESCE(stm.receive_total, 0) ELSE 0 END) as inc_herb_receive
                FROM opitemrece o
                INNER JOIN hrims.lookup_icode li ON o.icode = li.icode
                LEFT JOIN patient pt ON pt.hn = o.hn
                LEFT JOIN ovst_eclaim oe ON oe.vn = o.vn
                LEFT JOIN rep_eclaim_detail rep ON rep.vn = o.vn
                LEFT JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn
                LEFT JOIN hrims.eclaim_status ec ON ec.hn = o.hn 
                    AND ec.vstdate = o.vstdate AND LEFT(ec.vsttime, 5) = LEFT(o.vsttime, 5)
                LEFT JOIN ( 
                    SELECT cid, vstdate, LEFT(TIME(datetimeadm), 5) AS vsttime5, SUM(receive_total) AS receive_total,
                        GROUP_CONCAT(DISTINCT repno) AS repno 
                    FROM hrims.stm_ucs
                    WHERE vstdate BETWEEN ? AND ?
                    GROUP BY cid, vstdate, LEFT(TIME(datetimeadm), 5)
                ) stm ON stm.cid = pt.cid AND stm.vstdate = o.vstdate AND stm.vsttime5 = LEFT(o.vsttime, 5)
                WHERE o.vstdate BETWEEN ? AND ?
                GROUP BY o.vn
            ) inc ON a.vn = inc.vn
            LEFT JOIN (
                SELECT DATE(reply_date_time) as d,
                    COUNT(DISTINCT CASE WHEN lh.in_province = "Y" THEN vn END) as visit_referback_inprov,
                    COUNT(DISTINCT CASE WHEN lh.in_province != "Y" OR lh.in_province IS NULL THEN vn END) as visit_referback_outprov
                FROM refer_reply rr
                LEFT JOIN hrims.lookup_hospcode lh ON rr.dest_hospcode = lh.hospcode
                WHERE reply_date_time BETWEEN CONCAT(?, " 00:00:00") AND CONCAT(?, " 23:59:59")
                GROUP BY 1
            ) rb ON a.vstdate = rb.d
            LEFT JOIN (
                SELECT request_date as d, COUNT(DISTINCT operation_id) as visit_operation FROM operation_list 
                WHERE request_date BETWEEN ? AND ? GROUP BY 1
            ) op ON a.vstdate = op.d
            LEFT JOIN (
                SELECT appointment_date as d, COUNT(DISTINCT cid) as cnt FROM moph_appointment_list 
                WHERE appointment_date BETWEEN ? AND ? GROUP BY 1
            ) ma_booking ON a.vstdate = ma_booking.d
            GROUP BY a.vstdate
            ORDER BY a.vstdate';

        $rowsOpd = DB::connection('hosxp')->select(
            $sqlOpd,
            [
                $hospcode,
                $start,
                $end,
                $start, // stm subquery start
                $end,   // stm subquery end
                $start,
                $end,
                $start,
                $end,
                $start,
                $end,
                $start,
                $end
            ]
        );

        $opdRecords = array_map(function ($r) {
            return [
                'vstdate'                       => $r->vstdate,
                'hn_total'                      => (int)$r->hn_total,
                'visit_total'                   => (int)$r->visit_total,
                'visit_total_op'                => (int)$r->visit_total_op,
                'visit_total_pp'                => (int)$r->visit_total_pp,
                'visit_endpoint'                => (int)$r->visit_endpoint,
                'visit_ucs_incup'               => (int)$r->visit_ucs_incup,
                'visit_ucs_inprov'              => (int)$r->visit_ucs_inprov,
                'visit_ucs_outprov'             => (int)$r->visit_ucs_outprov,
                'visit_ofc'                     => (int)$r->visit_ofc,
                'visit_bkk'                     => (int)$r->visit_bkk,
                'visit_bmt'                     => (int)$r->visit_bmt,
                'visit_sss'                     => (int)$r->visit_sss,
                'visit_lgo'                     => (int)$r->visit_lgo,
                'visit_fss'                     => (int)$r->visit_fss,
                'visit_stp'                     => (int)$r->visit_stp,
                'visit_pay'                     => (int)$r->visit_pay,
                'visit_ppfs'                    => (int)$r->visit_ppfs,
                'visit_ppfs_claim'              => (int)$r->visit_ppfs_claim,
                'visit_ucs_cr'                  => (int)$r->visit_ucs_cr,
                'visit_ucs_cr_claim'            => (int)$r->visit_ucs_cr_claim,
                'visit_ucs_herb'                => (int)$r->visit_ucs_herb,
                'visit_ucs_herb_claim'          => (int)$r->visit_ucs_herb_claim,
                'visit_ucs_healthmed'           => (int)$r->visit_ucs_healthmed,
                'visit_healthmed'               => (int)$r->visit_healthmed,
                'visit_dent'                    => (int)$r->visit_dent,
                'visit_physic'                  => (int)$r->visit_physic,
                'visit_anc'                     => (int)$r->visit_anc,
                'visit_telehealth'              => (int)$r->visit_telehealth,
                'visit_moph_oapp_booking'       => (int)$r->visit_moph_oapp_booking,
                'visit_moph_oapp'               => (int)$r->visit_moph_oapp,
                'visit_operation'               => (int)$r->visit_operation,
                'visit_referout_inprov'         => (int)$r->visit_referout_inprov,
                'visit_referout_outprov'        => (int)$r->visit_referout_outprov,
                'visit_referout_inprov_ipd'     => (int)$r->visit_referout_inprov_ipd,
                'visit_referout_outprov_ipd'    => (int)$r->visit_referout_outprov_ipd,
                'visit_referin_inprov'          => (int)$r->visit_referin_inprov,
                'visit_referin_outprov'         => (int)$r->visit_referin_outprov,
                'visit_referin_inprov_ipd'      => (int)$r->visit_referin_inprov_ipd,
                'visit_referin_outprov_ipd'     => (int)$r->visit_referin_outprov_ipd,
                'visit_referback_inprov'        => (int)$r->visit_referback_inprov,
                'visit_referback_outprov'       => (int)$r->visit_referback_outprov,
                'inc_total'                     => (float)$r->inc_total,
                'inc_lab_total'                 => (float)$r->inc_lab_total,
                'inc_drug_total'                => (float)$r->inc_drug_total,
                'inc_ucs_incup'                 => (float)$r->inc_ucs_incup,
                'inc_lab_ucs_incup'             => (float)$r->inc_lab_ucs_incup,
                'inc_drug_ucs_incup'            => (float)$r->inc_drug_ucs_incup,
                'inc_ucs_inprov'                => (float)$r->inc_ucs_inprov,
                'inc_lab_ucs_inprov'            => (float)$r->inc_lab_ucs_inprov,
                'inc_drug_ucs_inprov'           => (float)$r->inc_drug_ucs_inprov,
                'inc_ucs_outprov'               => (float)$r->inc_ucs_outprov,
                'inc_lab_ucs_outprov'           => (float)$r->inc_lab_ucs_outprov,
                'inc_drug_ucs_outprov'          => (float)$r->inc_drug_ucs_outprov,
                'inc_ofc'                       => (float)$r->inc_ofc,
                'inc_lab_ofc'                   => (float)$r->inc_lab_ofc,
                'inc_drug_ofc'                  => (float)$r->inc_drug_ofc,
                'inc_bkk'                       => (float)$r->inc_bkk,
                'inc_lab_bkk'                   => (float)$r->inc_lab_bkk,
                'inc_drug_bkk'                  => (float)$r->inc_drug_bkk,
                'inc_bmt'                       => (float)$r->inc_bmt,
                'inc_lab_bmt'                   => (float)$r->inc_lab_bmt,
                'inc_drug_bmt'                  => (float)$r->inc_drug_bmt,
                'inc_sss'                       => (float)$r->inc_sss,
                'inc_lab_sss'                   => (float)$r->inc_lab_sss,
                'inc_drug_sss'                  => (float)$r->inc_drug_sss,
                'inc_lgo'                       => (float)$r->inc_lgo,
                'inc_lab_lgo'                   => (float)$r->inc_lab_lgo,
                'inc_drug_lgo'                  => (float)$r->inc_drug_lgo,
                'inc_fss'                       => (float)$r->inc_fss,
                'inc_lab_fss'                   => (float)$r->inc_lab_fss,
                'inc_drug_fss'                  => (float)$r->inc_drug_fss,
                'inc_stp'                       => (float)$r->inc_stp,
                'inc_lab_stp'                   => (float)$r->inc_lab_stp,
                'inc_drug_stp'                  => (float)$r->inc_drug_stp,
                'inc_pay'                       => (float)$r->inc_pay,
                'inc_lab_pay'                   => (float)$r->inc_lab_pay,
                'inc_drug_pay'                  => (float)$r->inc_drug_pay,
                'inc_ppfs'                      => (float)$r->inc_ppfs,
                'inc_ppfs_claim'                => (float)$r->inc_ppfs_claim,
                'inc_ppfs_receive'              => (float)$r->inc_ppfs_receive,
                'inc_uccr'                      => (float)$r->inc_uccr,
                'inc_uccr_claim'                => (float)$r->inc_uccr_claim,
                'inc_uccr_receive'              => (float)$r->inc_uccr_receive,
                'inc_herb'                      => (float)$r->inc_herb,
                'inc_herb_claim'                => (float)$r->inc_herb_claim,
                'inc_herb_receive'              => (float)$r->inc_herb_receive,
            ];
        }, $rowsOpd);

        // 3.2 ข้อมูล IPD-----------------------------------------------------------------------------------------------------------
        $sqlIpd = '
            SELECT ? AS hospcode,dchdate,COUNT(DISTINCT an) AS an_total ,sum(admdate) AS admdate,        
            ROUND((SUM(a.admdate) * 100) / (? * CASE WHEN YEAR(a.dchdate) = YEAR(CURDATE()) AND MONTH(a.dchdate) = MONTH(CURDATE()) 
                THEN DAY(CURDATE()) ELSE DAY(LAST_DAY(a.dchdate))END), 2) AS bed_occupancy,
            ROUND((SUM(a.admdate) / CASE WHEN YEAR(a.dchdate) = YEAR(CURDATE()) AND MONTH(a.dchdate) = MONTH(CURDATE()) 
                THEN DAY(CURDATE()) ELSE DAY(LAST_DAY(a.dchdate)) END), 2) AS active_bed, 
			ROUND(SUM(adjrw)/COUNT(DISTINCT an),2) AS cmi,ROUND(SUM(adjrw),5) AS adjrw, 
            SUM(income) AS inc_total,
			SUM(inc03) AS inc_lab_total,
            SUM(inc12) AS inc_drug_total
			FROM (SELECT a.dchdate,a.an,a.admdate,i.adjrw,a.income,a.inc03,inc12
			FROM ipt i
			LEFT JOIN an_stat a ON a.an=i.an
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
            $dates = match ($prefix) {
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
