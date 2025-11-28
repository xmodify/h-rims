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
			GROUP BY i.an ", [ $dateStart, $dateEnd,$dateStart, $dateEnd ]);

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

        // 5) Chunk = 10 record
        $chunks = array_chunk($items, 10);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $item) {
                // payload
                $payload = [
                    'hcode' => $hcode,
                    'hn'    => $item->hn,
                ];

                if (!empty($item->an)) {
                    $payload['an'] = $item->an;
                } else {
                    $payload['seq'] = $item->seq;
                }

                try {
                    $response = Http::withOptions([
                            'verify' => false
                        ])
                        ->retry(3, 2000)
                        ->withToken($token)
                        ->timeout(120)
                        ->post($apiUrl, $payload);
                    $status = $response->status();
                    $body   = $response->json();

                } catch (\Exception $e) {

                    $status = 500;
                    $body = [
                        'error' => 'request_failed',
                        'message' => $e->getMessage()
                    ];
                }

                // บันทึกเฉพาะ status = 200 และมี data
                if ($status == 200 && isset($body['data'][0])) {
                    $d = $body['data'][0];

                    DB::table('fdh_claim_status')->updateOrInsert(
                        [
                            'hn'  => $d['hn']  ?? $item->hn,
                            'seq' => $d['seq'] ?? $item->seq,   
                            'an'  => $d['an']  ?? $item->an,  
                        ],
                        [
                            'hcode'             => $d['hcode']             ?? $hcode,
                            'status'            => $d['status']            ?? null,
                            'process_status'    => $d['process_status']    ?? null,
                            'status_message_th' => $d['status_message_th'] ?? null,
                            'stm_period'        => $d['stm_period'] ?? null,
                            'updated_at'        => now(),
                            'created_at'        => DB::raw('COALESCE(created_at, NOW())'),
                        ]
                    );
                }
                // เก็บผล
                $results[] = [
                    'hn'     => $item->hn,
                    'seq'    => $item->seq,
                    'an'     => $item->an,
                    'payload_used' => $payload,
                    'status' => $status,
                    'body'   => $body
                ];
            }
            // หน่วงป้องกัน spam server
            usleep(300000); // 0.3s
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
        // อนุญาตให้รันยาว
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        
        $request->validate([
            'hn'  => 'required|string',
            'seq' => 'nullable|string',
            'an'  => 'nullable|string',
        ]);

        if (!$request->an && !$request->seq) {
            return response()->json([
                'error' => 'seq_or_an_required'
            ], 400);
        }

        // โหลด hcode
        $settings = DB::table('main_setting')
            ->pluck('value', 'name')
            ->toArray();

        $hcode = $settings['hospital_code'] ?? null;
        if (!$hcode) {
            return response()->json(['error' => 'hospital_code_not_found'], 400);
        }

        // Token
        $token = $this->getToken();
        if (!$token) {
            return response()->json(['error' => 'token_unavailable'], 500);
        }

        // Payload
        $payload = [
            'hcode' => $hcode,
            'hn'    => $request->hn,
        ];

        if (!empty($request->an)) {
            $payload['an'] = $request->an;     // IPD
        } else {
            $payload['seq'] = $request->seq;   // OPD
        }

        $apiUrl = 'https://fdh.moph.go.th/api/v1/ucs/track_trans';

        // ยิง API
        try {
            $response = Http::withOptions([
                    'verify' => false
                ])
                ->withToken($token)
                ->retry(3, 2000)
                ->timeout(60)
                ->post($apiUrl, $payload);

            $status = $response->status();
            $body   = $response->json();

        } catch (\Exception $e) {
            $status = 500;
            $body = [
                'error' => 'request_failed',
                'message' => $e->getMessage()
            ];
        }

        // ✔️ บันทึกเฉพาะเมื่อ status = 200 ---------------------------------------------------------------
        // if ($status == 200 && isset($body['data'][0])) {

        //     $d = $body['data'][0];

        //     DB::table('fdh_claim_status')->updateOrInsert(
        //         [
        //             'hn'  => $d['hn'] ?? $request->hn,
        //             'seq' => $d['seq'] ?? $request->seq,   
        //             'an'  => $d['an']  ?? $request->an,
        //         ],
        //         [
        //             'hcode'             => $d['hcode']             ?? $hcode,
        //             'status'            => $d['status']            ?? null,
        //             'process_status'    => $d['process_status']    ?? null,
        //             'status_message_th' => $d['status_message_th'] ?? null,
        //             'updated_at'        => now(),
        //             'created_at'        => DB::raw('COALESCE(created_at, NOW())'),
        //         ]
        //     );
        // }
        //----------------------------------------------------------------------------------------------

    // บันทึกทุกสถานะ-------------------------------------------------------------------------------------
        $d = $body['data'][0] ?? [];

        // คีย์หลัก (ใช้ request เป็น fallback)
        $hn  = $request->hn;
        $seq = $request->seq;
        $an  = $request->an;

        if (!empty($d['an'])) {
            $an = $d['an'];
        }

        // 🟩 จัดการข้อความไทยตามสถานะ
        if ($status == 500) {
            // ถ้า API error → บันทึกเฉพาะคำนี้
            $status_message_th = "ไม่มีรายการนี้ส่ง";
        } else {
            // สถานะอื่น: 200 / 400 / 404 / 409 ฯลฯ
            $status_message_th = $d['status_message_th'] 
                                ?? ($body['message'] ?? null);
        }
        // บันทึกข้อมูล   
        DB::table('fdh_claim_status')->updateOrInsert(
            [
                'hn'  => $hn,
                'seq' => $seq,
                'an'  => $an,
            ],
            [
                'hcode'             => $d['hcode']             ?? $hcode,
                'status'            => $d['status']            ?? $status,  
                'process_status'    => $d['process_status']    ?? null,
                'status_message_th' => $status_message_th,
                'stm_period'        => $d['stm_period']    ?? null,
                'updated_at'        => now(),
                'created_at'        => DB::raw('COALESCE(created_at, NOW())'),
            ]
        ); 
        //----------------------------------------------------------------------------------------------
        return response()->json([
            'input'   => $payload,
            'status'  => $status,
            'body'    => $body,
            'saved'   => ($status == 200),
        ]);
    }
    
}
