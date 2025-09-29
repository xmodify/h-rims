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
            SELECT ? AS hospcode,vstdate,COUNT(vn) AS total_visit,IFNULL(SUM(CASE WHEN endpoint<>"" THEN 1 ELSE 0 END),0) AS "endpoint",
            IFNULL(SUM(CASE WHEN hipdata_code="OFC" THEN 1 ELSE 0 END),0) AS "ofc_visit",
            IFNULL(SUM(CASE WHEN hipdata_code="OFC" AND edc_approve_list_text <> "" THEN 1 ELSE 0 END),0) AS "ofc_edc",
            IFNULL(SUM(CASE WHEN auth_code="" AND cid NOT LIKE "0%" THEN 1 ELSE 0 END),0) AS "non_authen",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","SSS","STP") AND hospmain="" THEN 1 ELSE 0 END),0) AS "non_hmain",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")
                THEN 1 ELSE 0 END),0) AS "uc_anywhere",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")
                AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_anywhere_endpoint",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
                AND uc_cr<>"" THEN 1 ELSE 0 END),0) AS "uc_cr",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
                AND uc_cr<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_cr_endpoint",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
                AND herb<>"" THEN 1 ELSE 0 END),0) AS "uc_herb",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
                AND herb<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_herb_endpoint",
            IFNULL(SUM(CASE WHEN ppfs<>"" THEN 1 ELSE 0 END),0) AS "ppfs",
            IFNULL(SUM(CASE WHEN ppfs<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "ppfs_endpoint",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND healthmed<>"" THEN 1 ELSE 0 END),0) AS "uc_healthmed",
            IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND healthmed<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_healthmed_endpoint"
            FROM(SELECT o.vstdate,o.vn,pt.cid,pt.nationality,vp.auth_code,p.pttype,p.paidst,p.hipdata_code,vp.hospmain,os.edc_approve_list_text,
            uc_cr.vn AS uc_cr,herb.vn AS herb,ppfs.vn AS ppfs,healthmed.vn AS healthmed,
            IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,v.income,v.paid_money
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN ovst_seq os ON os.vn=o.vn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN opitemrece uc_cr ON uc_cr.vn=o.vn AND uc_cr.icode IN (SELECT icode FROM hrims.lookup_icode WHERE uc_cr = "Y")
            LEFT JOIN opitemrece herb ON herb.vn=o.vn AND herb.icode IN (SELECT icode FROM hrims.lookup_icode WHERE herb32 = "Y")
            LEFT JOIN health_med_service healthmed ON healthmed.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND ep.vstdate=o.vstdate AND ep.claimCode LIKE "EP%"
            WHERE o.vstdate BETWEEN ? AND ?
            AND (o.an ="" OR o.an IS NULL) 
            GROUP BY o.vn) AS a GROUP BY vstdate ';

        $rows = DB::connection('hosxp')->select($sql, [$hospcode, $start, $end]);

        // 4) แปลงผลให้เป็น records ตามสเปกของปลายทาง
        $records = array_map(function ($r) {
            return [
                'vstdate'               => $r->vstdate,
                'total_visit'           => (int)$r->total_visit,
                'endpoint'              => (int)$r->endpoint,
                'ofc_visit'             => (int)$r->ofc_visit,
                'ofc_edc'               => (int)$r->ofc_edc,
                'non_authen'            => (int)$r->non_authen,
                'non_hmain'             => (int)$r->non_hmain,
                'uc_anywhere'           => (int)$r->uc_anywhere,
                'uc_anywhere_endpoint'  => (int)$r->uc_anywhere_endpoint,
                'uc_cr'                 => (int)$r->uc_cr,
                'uc_cr_endpoint'        => (int)$r->uc_cr_endpoint,
                'uc_herb'               => (int)$r->uc_herb,
                'uc_herb_endpoint'      => (int)$r->uc_herb_endpoint,
                'ppfs'                  => (int)$r->ppfs,
                'ppfs_endpoint'         => (int)$r->ppfs_endpoint,
                'uc_healthmed'          => (int)$r->uc_healthmed,
                'uc_healthmed_endpoint' => (int)$r->uc_healthmed_endpoint,
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
        // $url = config('services.opoh.ingest_url', 'http://1.179.128.29:3394/api/ingest');
        $url = config('services.opoh.ingest_url', 'http://127.0.0.1:8000/api/op_insurance');

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
