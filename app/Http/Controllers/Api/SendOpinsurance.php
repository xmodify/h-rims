<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class SendOpinsurance extends Controller
{
    public function send(Request $request)
    {
        // 1) ตั้งค่าพื้นฐาน
        $token    = DB::table('main_setting')->where('name', 'opoh_token')->value('value');
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value');

        if (!$token || !$hospcode) {
            return response()->json([
                'ok' => false,
                'message' => 'Missing opoh_token or hospital_code in main_setting.'
            ], 422);
        }

        // 2) รับช่วงวันที่ (ไม่ส่งมา = 10 วันย้อนหลังถึงปัจจุบัน) 
        $start = $request->query('start_date');
        $end   = $request->query('end_date');

        if (!$start || !$end) {
            $today = Carbon::today();
            $start = $today->copy()->subDays(10)->toDateString();
            $end   = $today->toDateString();
        }
        // 3) Query จากฐาน HOSxP (connection 'hosxp')
        $sql = '
            SELECT ? AS hospcode,vstdate,COUNT(DISTINCT hn) AS hn_total,COUNT(vn) AS visit_total,
            SUM(CASE WHEN diagtype ="OP" THEN 1 ELSE 0 END) AS visit_total_op,
            SUM(CASE WHEN diagtype ="PP" THEN 1 ELSE 0 END) AS visit_total_pp,
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
            IF(i.icd10 IS NULL,"OP","PP") AS diagtype,IF(vp.hospmain IS NOT NULL,"Y","") AS incup,
            IF(vp1.hospmain IS NOT NULL,"Y","") AS inprov,IF(vp2.hospmain IS NOT NULL,"Y","") AS outprov,
            IF(op.vn IS NOT NULL,"Y","") AS ppfs,IF(op1.vn IS NOT NULL,"Y","") AS uccr,IF(op2.vn IS NOT NULL,"Y","") AS herb,
            IF(hm.vn IS NOT NULL,"Y","") AS healthmed,COALESCE(inc_ppfs.inc, 0) AS inc_ppfs,COALESCE(inc_uccr.inc, 0) AS inc_uccr,
            COALESCE(inc_herb.inc, 0) AS inc_herb
            FROM vn_stat v
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
            LEFT JOIN hrims.lookup_icd10 i ON i.icd10=v.pdx AND i.pp="Y"
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

        $rows = DB::connection('hosxp')->select($sql, [$hospcode, $start, $end, $start, $end, $start, $end, $start, $end]);

        // 4) แปลงผลให้เป็น records ตามสเปกของปลายทาง
        $records = array_map(function ($r) {
            return [
                'vstdate'              => $r->vstdate,
                'hn_total'             => (int)$r->hn_total,
                'visit_total'          => (int)$r->visit_total,
                'visit_total_op'       => (int)$r->visit_total_op,
                'visit_total_pp'       => (int)$r->visit_total_pp,
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
        }, $rows);

        if (empty($records)) {
            return response()->json([
                'ok' => true,
                'hospcode' => $hospcode,
                'start_date' => $start,
                'end_date' => $end,
                'received' => 0,
                'summary' => [
                    'batches' => 0,
                    'sent' => 0,
                    'failed' => 0,
                    'details' => [],
                ],
                'message' => 'No data in selected date range.',
            ], 200);
        }

        // 5) ส่งเข้า API ปลายทาง (Sanctum Bearer)
        $url = config('services.opoh.ingest_url', 'http://1.179.128.29:3394/api/op_insurance');
        // $url = config('services.opoh.ingest_url', 'http://127.0.0.1:8000/api/op_insurance');

        $chunkSize = (int)($request->query('chunk', 200)); // เปลี่ยนได้ผ่าน ?chunk=
        $chunks = array_chunk($records, max(1, $chunkSize));

        $summary = [
            'batches' => count($chunks),
            'sent'    => 0,
            'failed'  => 0,
            'details' => [],
        ];

        foreach ($chunks as $i => $chunk) {
            // สร้าง Idempotency-Key ต่อก้อนจาก hospcode + รายการวันที่ (กันรีเพลย์ซ้ำ)
            $dates = array_column($chunk, 'vstdate');
            sort($dates);
            $idempotencyKey = hash('sha256', $hospcode . '|' . implode(',', $dates));

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

        return response()->json([
            'ok'         => $summary['failed'] === 0,
            'hospcode'   => $hospcode,
            'start_date' => $start,
            'end_date'   => $end,
            'received'   => count($records),
            'summary'    => $summary,
            'sample'     => $records[0] ?? null,
        ], $summary['failed'] === 0 ? 200 : 207);
    }

}
