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
            \Illuminate\Support\Facades\Log::warning('FDH config missing when getting token', [
                'fdh_user' => $user,
                'fdh_pass' => $password ? 'OK' : null,
                'fdh_secretKey' => $secretKey ? 'OK' : null,
                'hospital_code' => $hcode,
            ]);
            return null;
        }

        // 🔐 Hash ตามคู่มือ HMAC SHA-256
        $hash = hash_hmac('sha256', $password, $secretKey);
        $passwordHash = strtoupper($hash);

        $apiUrl = 'https://fdh.moph.go.th/token?Action=get_moph_access_token';

        try {
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
                return trim($response->body());  // ใช้ body ตรง ๆ
            }

            // 🔴 ถ้าล้มเหลว
            \Illuminate\Support\Facades\Log::error("FDH Token retrieval failed", [
                "status" => $response->status(),
                "body"   => $response->body()
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("FDH Token retrieval exception: " . $e->getMessage());
        }

        return null;
    }

    // ✔ ทดสอบ token ##################################################################################################

    public function testToken()
    {
        $token = $this->getToken();
        return response()->json([
            "token" => $token,
            "status" => $token ? 'success' : 'failed'
        ]);
    }

    // ✔ เช็ค Track Claim ###############################################################################################

    /**
     * Shared helper to query FDH Status for a chunk of items and upsert to local DB
     */
    private function queryFdhAndSaveToDb($items, $hcode, $token)
    {
        $apiUrl = 'https://fdh.moph.go.th/api/v1/ucs/track_trans';
        $totalUpdated = 0;
        $totalErrors = 0;
        $totalNotFound = 0;
        $upsertData = [];
        $detailedResults = [];

        // ยิง HTTP พร้อมๆ กันแบบ Asynchronous สำหรับทั้ง Chunk
        $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($items, $hcode, $apiUrl, $token) {
            $reqs = [];
            foreach ($items as $index => $item) {
                $itemObj = (object)$item;
                $payload = [
                    'hcode' => $hcode,
                    'hn'    => $itemObj->hn,
                ];
                if (!empty($itemObj->an)) {
                    $payload['an'] = $itemObj->an;
                } else {
                    $payload['seq'] = $itemObj->seq;
                }

                $reqs[] = $pool->as((string)$index)
                    ->withOptions([
                        'verify' => false,
                        'http_errors' => false
                    ])
                    ->withToken($token)
                    ->timeout(120)
                    ->post($apiUrl, $payload);
            }
            return $reqs;
        });

        foreach ($responses as $index => $response) {
            $itemObj = (object)$items[$index];
            $hn = $itemObj->hn;
            $seq = $itemObj->seq ?? null;
            $an = $itemObj->an ?? null;

            $status = 500;
            $body = null;
            $errorMessage = null;

            if ($response instanceof \Exception) {
                $errorMessage = $response->getMessage();
                $totalErrors++;
            } else {
                $status = $response->status();
                $body   = $response->json();

                if ($status == 200 && isset($body['data'][0])) {
                    $d = $body['data'][0];
                    $now = now();
                    $upsertData[] = [
                        'hn'                => $d['hn']  ?? $hn,
                        'seq'               => $d['seq'] ?? $seq,
                        'an'                => $d['an']  ?? $an,
                        'hcode'             => $d['hcode'] ?? $hcode,
                        'status'            => $d['status'] ?? null,
                        'process_status'    => $d['process_status'] ?? null,
                        'status_message_th' => $d['status_message_th'] ?? null,
                        'stm_period'        => $d['stm_period'] ?? null,
                        'updated_at'        => $now,
                        'created_at'        => $now,
                    ];
                    $totalUpdated++;
                } else {
                    if ($status == 404 || ($status == 200 && !isset($body['data'][0]))) {
                        $totalNotFound++;
                    } else {
                        $totalErrors++;
                        $errorMessage = isset($body['message']) ? $body['message'] : "HTTP $status";
                        \Illuminate\Support\Facades\Log::warning("FDH queryFdhAndSaveToDb error response", [
                            'hn' => $hn,
                            'status' => $status,
                            'body' => $body
                        ]);
                    }
                }
            }

            // บันทึกผลลัพธ์รายบุคคล
            $detailedResults[] = [
                'hn' => $hn,
                'seq' => $seq,
                'an' => $an,
                'status' => $status,
                'error' => $errorMessage,
                'status_message_th' => ($status == 200 && isset($body['data'][0]['status_message_th'])) 
                    ? $body['data'][0]['status_message_th'] 
                    : (($status == 404 || ($status == 200 && !isset($body['data'][0]))) ? 'ไม่พบข้อมูลการส่งเคลมในระบบ FDH' : ($errorMessage ?? 'เกิดข้อผิดพลาดในการเชื่อมต่อ'))
            ];
        }

        if (!empty($upsertData)) {
            DB::table('fdh_claim_status')->upsert(
                $upsertData,
                ['hn', 'seq', 'an'],
                ['hcode', 'status', 'process_status', 'status_message_th', 'stm_period', 'updated_at']
            );
        }

        return [
            'success' => true,
            'total' => count($items),
            'updated_count' => $totalUpdated,
            'not_found_count' => $totalNotFound,
            'errors_count' => $totalErrors,
            'details' => $detailedResults
        ];
    }

    public function check(Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $dateStart = $request->date_start ?? date('Y-m-d');
        $dateEnd   = $request->date_end   ?? date('Y-m-d');
        $request->validate([
            'date_start' => 'nullable|date',
            'date_end'   ?? 'nullable|date',
        ]);

        $settings = DB::table('main_setting')->pluck('value', 'name')->toArray();
        $hcode = $settings['hospital_code'] ?? null;

        if (!$hcode) {
            return response()->json([
                'success' => false,
                'error' => 'hospital_code_not_found',
                'total' => 0,
                'updated_count' => 0,
                'errors_count' => 0
            ], 400);
        }

        // ดึงข้อมูล UCS จาก HOSxP
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
                'success' => true,
                'message' => 'no_data_found',
                'date_range' => [$dateStart, $dateEnd],
                'total' => 0,
                'updated_count' => 0,
                'errors_count' => 0
            ]);
        }

        $token = $this->getToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'token_unavailable',
                'total' => 0,
                'updated_count' => 0,
                'errors_count' => count($items)
            ], 500);
        }

        $totalUpdated = 0;
        $totalErrors = 0;
        $totalChecked = 0;

        $chunks = array_chunk($items, 20);
        foreach ($chunks as $chunk) {
            $res = $this->queryFdhAndSaveToDb($chunk, $hcode, $token);
            $totalUpdated += $res['updated_count'];
            $totalErrors += $res['errors_count'];
            $totalChecked += count($chunk);
            usleep(100000);
        }

        return response()->json([
            'success'    => true,
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
            'total'      => $totalChecked,
            'updated_count' => $totalUpdated,
            'errors_count' => $totalErrors,
            'message'    => 'FDH Check Completed'
        ]);
    }

    /**
     * ดึงข้อมูลตรวจสอบ FDH ย้อนหลัง 15 วัน (Auto)
     */
    public function checkLastDays()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $settings = DB::table('main_setting')->pluck('value', 'name')->toArray();
        $hcode = $settings['hospital_code'] ?? null;

        if (!$hcode) {
            return response()->json([
                'success' => false,
                'error' => 'hospital_code_not_found',
            ], 400);
        }

        $token = $this->getToken();
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'token_unavailable',
            ], 500);
        }

        $totalPulled = 0;
        $totalUpdated = 0;
        $totalNotFound = 0;
        $totalErrors = 0;
        $checkedDays = 0;

        for ($i = 15; $i >= 1; $i--) {
            $targetDate = date('Y-m-d', strtotime("-{$i} days"));

            // ดึงข้อมูล UCS จาก HOSxP
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
                GROUP BY i.an ", [$targetDate, $targetDate, $targetDate, $targetDate]);

            if (!empty($items)) {
                $checkedDays++;
                $chunks = array_chunk($items, 20);
                foreach ($chunks as $chunk) {
                    $res = $this->queryFdhAndSaveToDb($chunk, $hcode, $token);
                    $totalPulled += count($chunk);
                    $totalUpdated += $res['updated_count'];
                    $totalNotFound += $res['not_found_count'] ?? 0;
                    $totalErrors += $res['errors_count'];
                    usleep(100000);
                }
            }
        }

        $responseData = [
            'ok' => true,
            'success' => true,
            'message' => 'FDH Check 15 Days Completed Day-by-Day',
            'checked_days' => $checkedDays,
            'updated_claims' => $totalUpdated,
            'not_found_claims' => $totalNotFound,
            'errors' => $totalErrors,
            'total' => $totalPulled
        ];

        if (!app()->runningInConsole() && function_exists('appendAndLimitLog')) {
            $logMessage = "[" . now()->toDateTimeString() . "] FDH Claim Status output: " . json_encode($responseData, JSON_UNESCAPED_UNICODE) . "\n";
            appendAndLimitLog('fdh_claim_status_schedule.log', $logMessage, 30);
        }

        return response()->json($responseData);
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
            $response = Http::withOptions([
                'verify' => false,
                'http_errors' => false
            ])
                ->withToken($token)
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

    /**
     * ดึงรายชื่อ HN, SEQ, AN ทั้งหมดของสิทธิ์ UCS เพื่อส่งเช็คสถานะ FDH (สำหรับ chunk ประมวลผลบน client)
     */
    public function getCheckList(Request $request)
    {
        $dateStart = $request->input('date_start') ?? date('Y-m-d');
        $dateEnd = $request->input('date_end') ?? date('Y-m-d');

        // ดึงข้อมูล UCS จาก HOSxP
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

        return response()->json([
            'items' => $items,
            'date_start' => $dateStart,
            'date_end' => $dateEnd
        ]);
    }

    /**
     * ตรวจสอบสถานะ FDH ทีละ Chunk (ประมวลผลผ่าน AJAX sequential)
     */
    public function checkChunk(Request $request)
    {
        $items = $request->input('items') ?? [];

        if (empty($items)) {
            return response()->json([
                'success' => true,
                'total' => 0,
                'updated_count' => 0,
                'errors_count' => 0,
                'details' => []
            ]);
        }

        $settings = DB::table('main_setting')->pluck('value', 'name')->toArray();
        $hcode = $settings['hospital_code'] ?? null;

        if (!$hcode) {
            return response()->json(['error' => 'hospital_code_not_found'], 400);
        }

        $token = $this->getToken();
        if (!$token) {
            return response()->json(['error' => 'token_unavailable'], 500);
        }

        $totalUpdated = 0;
        $totalErrors = 0;
        $detailedResults = [];

        $chunks = array_chunk($items, 20);
        foreach ($chunks as $chunk) {
            $res = $this->queryFdhAndSaveToDb($chunk, $hcode, $token);
            $totalUpdated += $res['updated_count'];
            $totalErrors += $res['errors_count'];
            if (!empty($res['details'])) {
                $detailedResults = array_merge($detailedResults, $res['details']);
            }
            usleep(100000);
        }

        return response()->json([
            'success' => true,
            'total' => count($items),
            'updated_count' => $totalUpdated,
            'errors_count' => $totalErrors,
            'details' => $detailedResults
        ]);
    }

    /**
     * บันทึก Log สำหรับการตรวจเช็คสถานะ FDH แบบกำหนดเอง (Manual Check)
     */
    public function logManualCheck(Request $request)
    {
        $checkedDays = $request->input('checked_days') ?? 0;
        $updatedClaims = $request->input('updated_claims') ?? 0;
        $notFoundClaims = $request->input('not_found_claims') ?? 0;
        $errors = $request->input('errors') ?? 0;
        $ok = $request->input('ok') ?? true;
        $message = $request->input('message') ?? 'ตรวจสอบสถานะสำเร็จ';

        $data = [
            'ok' => $ok,
            'message' => $message,
            'checked_days' => $checkedDays,
            'updated_claims' => $updatedClaims,
            'not_found_claims' => $notFoundClaims,
            'errors' => $errors
        ];

        $logMessage = "[" . now()->toDateTimeString() . "] FDH Claim Status output: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        appendAndLimitLog('fdh_claim_status_schedule.log', $logMessage, 30);

        return response()->json(['status' => 'success']);
    }
}
