<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\FdhClaimStatus;

class FdhClaimStatusController extends Controller
{
    private function getToken()
    {
        // 🔍 ดึงค่าทั้งหมดจาก main_setting แล้วเก็บเป็น key => value
        $settings = DB::table('main_setting')
            ->pluck('value', 'name')
            ->toArray();

        // 🧩 ดึงค่าที่ต้องใช้
        $user      = $settings['fdh_user'] ?? null;
        $password  = $settings['fdh_pass'] ?? null;
        $secretKey = $settings['fdh_secretKey'] ?? null;
        $hcode     = $settings['hospital_code'] ?? null;

        // ❗ กันข้อมูลหาย
        if (!$user || !$password || !$secretKey || !$hcode) {
            return response()->json([
                'error' => 'FDH config missing',
                'detail' => [
                    'fdh_user' => $user,
                    'fdh_pass' => $password ? 'OK' : null,
                    'fdh_secretKey' => $secretKey ? 'OK' : null,
                    'hospital_code' => $hcode,
                ]
            ], 400);
        }

        // 🔐 Hash ตามคู่มือ HMAC SHA-256
        $hash = hash_hmac('sha256', $password, $secretKey);
        $passwordHash = strtoupper($hash);

        $apiUrl = 'https://fdh.moph.go.th/token?Action=get_moph_access_token';

        // 🔗 เรียก API
        $response = Http::withOptions([
            'verify' => false   // ใช้สำหรับ local เท่านั้น
        ])->withHeaders([
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])->post($apiUrl, [
            'user'          => $user,
            'password_hash' => $passwordHash,
            'hospital_code' => $hcode
        ]);

        // 🟢 สำเร็จ → FDH ส่ง token มาเป็น string
        if ($response->successful()) {
            return $response->body();  // ใช้ body ตรง ๆ
        }

        // 🔴 ถ้าล้มเหลว
        return response()->json([
            "status" => $response->status(),
            "body"   => $response->body()
        ], 400);
    }

    // ✔ ทดสอบ token ##################################################################################################

    public function testToken()
    {
        return response()->json([
            "token" => $this->getToken()
        ]);
    }

    // ✔ เช็ค Track Claim ###############################################################################################

    public function check(Request $request)
    {
        // อนุญาตให้รันยาว
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        // 1) วันที่ default = วันนี้
        $dateStart = $request->date_start ?? date('Y-m-d');
        $dateEnd   = $request->date_end   ?? date('Y-m-d');
        $request->validate([
            'date_start' => 'nullable|date',
            'date_end'   => 'nullable|date',
        ]);

        // 2) ดึง main_setting
        $settings = DB::table('main_setting')
            ->pluck('value', 'name')
            ->toArray();
        $hcode = $settings['hospital_code'] ?? null;

        if (!$hcode) {
            return response()->json(['error' => 'hospital_code_not_found'], 400);
        }

        // 3) ดึงข้อมูล UCS จาก HOSxP
        $items = DB::connection('hosxp')->select("
            SELECT o.hn, o.vn AS seq, '' AS an
            FROM ovst o
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn			
            LEFT JOIN pttype p ON p.pttype = vp.pttype	
            WHERE o.vstdate BETWEEN ? AND ?
			AND o.an IS NULL
            AND p.hipdata_code = 'UCS' 
			GROUP BY o.vn
			UNION
			SELECT i.hn, '' AS seq, i.an
            FROM ipt i
            LEFT JOIN ipt_pttype ip ON ip.an = i.an			
            LEFT JOIN pttype p ON p.pttype = ip.pttype	
            WHERE i.dchdate BETWEEN ? AND ?
            AND p.hipdata_code = 'UCS' 
			GROUP BY i.an ", [$dateStart, $dateEnd, $dateStart, $dateEnd]);

        if (empty($items)) {
            return response()->json([
                'error' => 'no_data_found',
                'date_range' => [$dateStart, $dateEnd]
            ], 404);
        }

        // 4) ขอ Token FDH
        $token = $this->getToken();
        if (!$token) {
            return response()->json(['error' => 'token_unavailable'], 500);
        }
        $apiUrl = 'https://fdh.moph.go.th/api/v1/ucs/track_trans';
        $results = [];

        // 5) Chunk = 50 วิเคราะห์แบบหลายๆ คิวพร้อมกัน (Concurrent Requests)
        $chunks = array_chunk($items, 50);
        foreach ($chunks as $chunk) {

            // ยิง HTTP พร้อมๆ กันแบบ Asynchronous
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($chunk, $hcode, $apiUrl, $token) {
                $reqs = [];
                foreach ($chunk as $index => $item) {
                    $payload = [
                        'hcode' => $hcode,
                        'hn'    => $item->hn,
                    ];
                    if (!empty($item->an)) {
                        $payload['an'] = $item->an;
                    } else {
                        $payload['seq'] = $item->seq;
                    }

                    $reqs[] = $pool->as((string)$index)
                        ->withOptions(['verify' => false])
                        ->withToken($token)
                        ->timeout(120)
                        ->post($apiUrl, $payload);
                }
                return $reqs;
            });

            $upsertData = [];

            foreach ($responses as $index => $response) {
                $item = $chunk[$index];

                $payload = [
                    'hcode' => $hcode,
                    'hn'    => $item->hn,
                ];
                if (!empty($item->an)) {
                    $payload['an'] = $item->an;
                } else {
                    $payload['seq'] = $item->seq;
                }

                if ($response instanceof \Exception) {
                    $status = 500;
                    $body = [
                        'error' => 'request_failed',
                        'message' => $response->getMessage()
                    ];
                } else {
                    $status = $response->status();
                    $body   = $response->json();

                    if ($status == 200 && isset($body['data'][0])) {
                        $d = $body['data'][0];
                        $now = now();
                        $upsertData[] = [
                            'hn'                => $d['hn']  ?? $item->hn,
                            'seq'               => $d['seq'] ?? $item->seq,
                            'an'                => $d['an']  ?? $item->an,
                            'hcode'             => $d['hcode'] ?? $hcode,
                            'status'            => $d['status'] ?? null,
                            'process_status'    => $d['process_status'] ?? null,
                            'status_message_th' => $d['status_message_th'] ?? null,
                            'stm_period'        => $d['stm_period'] ?? null,
                            'updated_at'        => $now,
                            'created_at'        => $now,
                        ];
                    }
                }

                $results[] = [
                    'hn'     => $item->hn,
                    'seq'    => $item->seq,
                    'an'     => $item->an,
                    'payload_used' => $payload,
                    'status' => $status,
                    'body'   => $body
                ];
            }

            // บันทึกฐานข้อมูลรวดเดียวจบ (Bulk Upsert)
            if (!empty($upsertData)) {
                DB::table('fdh_claim_status')->upsert(
                    $upsertData,
                    ['hn', 'seq', 'an'], // เช็คซ้ำจาก Unique Columns นี้
                    ['hcode', 'status', 'process_status', 'status_message_th', 'stm_period', 'updated_at'] // สิ่งที่ต้องอัปเดตเมื่อซ้ำ
                );
            }

            // หน่วงป้องกัน spam ลดลงเหลือ 0.1s
            usleep(100000);
        }
        return response()->json([
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
            'total'      => count($results),
            'data'       => $results
        ]);
    }

    // ✔ เช็ค Track Claim Indiv #############################################################################################

    public function check_indiv(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        // Validation
        $request->validate([
            'hn'  => 'required|string',
            'seq' => 'nullable|string',
            'an'  => 'nullable|string',
        ]);

        // ❗ ถ้าไม่ส่ง seq หรือ an → ตอบ HTTP 200 + status 400
        if (!$request->an && !$request->seq) {
            return response()->json([
                'status' => 400,
                'error'  => 'seq_or_an_required',
                'saved'  => false,
            ], 200);
        }

        // โหลด setting
        $settings = DB::table('main_setting')->pluck('value', 'name')->toArray();
        $hcode = $settings['hospital_code'] ?? null;

        if (!$hcode) {
            return response()->json([
                'status' => 400,
                'error'  => 'hospital_code_not_found',
                'saved'  => false,
            ], 200);
        }

        // Token
        $token = $this->getToken();
        if (!$token) {
            return response()->json([
                'status' => 500,
                'error'  => 'token_unavailable',
                'saved'  => false,
            ], 200);
        }

        // Payload
        $payload = [
            'hcode' => $hcode,
            'hn'    => $request->hn,
        ];
        if ($request->an) {
            $payload['an'] = $request->an;
        } else {
            $payload['seq'] = $request->seq;
        }

        $apiUrl = 'https://fdh.moph.go.th/api/v1/ucs/track_trans';

        // API call
        try {
            $response = Http::withOptions(['verify' => false])
                ->withToken($token)
                ->retry(3, 2000)
                ->timeout(60)
                ->post($apiUrl, $payload);

            $status = $response->status();
            $body   = $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => 'request_failed',
                'message' => $e->getMessage(),
                'saved'   => false,
            ], 200);
        }

        // บันทึกเฉพาะ 200 + มี data
        $saved = false;

        if ($status == 200 && isset($body['data'][0])) {

            $d   = $body['data'][0];

            $hn  = $d['hn']  ?? $request->hn;
            $seq = $d['seq'] ?? $request->seq;
            $an  = $d['an']  ?? $request->an;

            $now = now();
            DB::table('fdh_claim_status')->upsert(
                [[
                    'hn'                => $hn,
                    'seq'               => $seq,
                    'an'                => $an,
                    'hcode'             => $d['hcode'] ?? $hcode,
                    'status'            => $d['status'] ?? null,
                    'process_status'    => $d['process_status'] ?? null,
                    'status_message_th' => $d['status_message_th'] ?? null,
                    'stm_period'        => $d['stm_period'] ?? null,
                    'updated_at'        => $now,
                    'created_at'        => $now,
                ]],
                ['hn', 'seq', 'an'],
                ['hcode', 'status', 'process_status', 'status_message_th', 'stm_period', 'updated_at']
            );

            $saved = true;
        }

        // ส่งผลกลับไป — HTTP 200 เท่านั้น!
        return response()->json([
            'status' => $status,  // = 200, 404, 400, 500 (ของ FDH)
            'body'   => $body,
            'saved'  => $saved,
        ], 200);
    }
}
