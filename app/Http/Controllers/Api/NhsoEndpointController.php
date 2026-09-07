<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Nhso_Endpoint;
use Carbon\Carbon;

class NhsoEndpointController extends Controller
{
    /**
     * Helper to verify if schedule task is authorized
     */
    protected function isAuthorizedSchedule(Request $request): bool
    {
        if (auth()->check()) {
            return true;
        }
        $clientIp = $request->ip();
        if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true)) {
            return true;
        }
        $secretKey = config('app.schedule_secret_key');
        if (!$secretKey) {
            $secretKey = DB::table('main_setting')->where('name', 'schedule_secret_key')->value('value');
        }
        if (!$secretKey) {
            $hcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: 'hrims';
            $secretKey = substr(hash('sha256', $hcode . config('app.key', 'hrims_salt')), 0, 32);
        }
        $providedKey = $request->header('X-SCHEDULE-KEY') ?: $request->query('key') ?: $request->input('key');
        if ($providedKey && hash_equals($secretKey, (string)$providedKey)) {
            return true;
        }
        return false;
    }

    /**
     * ดึงข้อมูลจาก สปสช (แบบกลุ่ม)
     */
    public function pull(Request $request)
    {
        // ตรวจสอบสิทธิ์
        if (!auth()->check() || (auth()->user()->status !== 'admin' && auth()->user()->allow_nhso_endpoint !== 'Y')) {
            return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดึงข้อมูลปิดสิทธิ'], 403);
        }

        set_time_limit(600);

        $vstdate = $request->input('vstdate') ?? now()->format('Y-m-d');
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $vstdate, $m) && (int)$m[1] > 2400) {
            $vstdate = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
            $request->merge(['vstdate' => $vstdate]);
        }
        $hosxp = DB::connection('hosxp')->select('
            SELECT DISTINCT pt.cid 
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN vn_stat vs ON vs.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate 
                 AND (ep.claim_status = "success" OR ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001")
            WHERE o.vstdate = ?
            AND (o.an = "" OR o.an IS NULL)
            AND vs.uc_money > 0
            AND ep.cid IS NULL
            AND pt.cid IS NOT NULL', [$vstdate]);

        $cids = array_column($hosxp, 'cid');

        $token = DB::connection('hosxp')
            ->table('sys_var')
            ->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')
            ->value('sys_value');


        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบ Token NHSO ในระบบ'], 500);
        }

        $localHcode = DB::connection('hosxp')->table('opdconfig')->value('hospitalcode');

        $pulled = 0;
        $inserted = 0;
        $updated = 0;

        foreach (array_chunk($cids, 50) as $chunk) {
            $existing_claims = Nhso_Endpoint::whereIn('cid', $chunk)
                ->where('vstdate', $vstdate)
                ->pluck('claimType', 'claimCode')
                ->toArray();

            $upsertData = [];

            foreach ($chunk as $cid) {
                $response = null;
                $attempts = 0;
                while ($attempts < 3) {
                    $attempts++;
                    try {
                        $response = Http::withoutVerifying()
                            ->timeout(12)
                            ->withToken($token)
                            ->acceptJson()
                            ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                                'personalId' => $cid,
                                'serviceDate' => $vstdate,
                            ]);

                        if ($response->status() === 429) {
                            usleep(1500000);
                            continue;
                        }

                        break;
                    } catch (\Throwable $e) {
                        if ($attempts >= 3) {
                            Log::error("NHSO Pull logic error for CID: {$cid}", ['msg' => $e->getMessage()]);
                        }
                        usleep(500000);
                    }
                }

                usleep(120000); // 0.12s pacing

                if (!$response || $response->failed()) {
                    continue;
                }

                $result = $response->json();
                if (!is_array($result) || !isset($result['firstName']) || empty($result['serviceHistories'])) {
                    continue;
                }

                    foreach ($result['serviceHistories'] as $row) {
                        if (!is_array($row)) continue;

                        $claimCode = $row['claimCode'] ?? null;
                        $claimType = $row['service']['code'] ?? null;
                        $sourceChannel = $row['sourceChannel'] ?? '';
                        $serviceDateTime = $row['serviceDateTime'] ?? null;
                        $apiHcode = $row['hospital']['hcode'] ?? null;

                        if (!$claimCode) continue;

                        // ตรวจสอบ hcode ว่าตรงกับโรงพยาบาลเราหรือไม่
                        if ($localHcode && $apiHcode && $apiHcode !== $localHcode) {
                            continue;
                        }
                        
                        // กรองตามเงื่อนไข: ทั่วไป/ฟอกไต เอาเฉพาะ EP, Homeward เอาเฉพาะ PP
                        $shouldPull = false;
                        if (in_array($claimType, ['PG0060001', 'PG0130001'])) {
                            if (strpos($claimCode, 'EP') === 0) $shouldPull = true;
                        } elseif ($claimType === 'PG0140001') {
                            if (strpos($claimCode, 'PP') === 0) $shouldPull = true;
                        } elseif ($sourceChannel === 'ENDPOINT') {
                            $shouldPull = true;
                        }

                        if (!$shouldPull) {
                            continue;
                        }

                        $pulled++;

                        if (isset($existing_claims[$claimCode])) {
                            if ($existing_claims[$claimCode] !== $claimType) {
                                Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
                                $updated++;
                            }
                        } else {
                            $claimStatus = (strpos($claimCode, 'EP') === 0) ? 'success' : 'pulled';
                            $upsertData[] = [
                                'cid'             => $cid,
                                'firstName'       => $result['firstName'] ?? null,
                                'lastName'        => $result['lastName'] ?? null,
                                'mainInscl'       => $result['mainInscl']['id'] ?? null,
                                'mainInsclName'   => $result['mainInscl']['name'] ?? null,
                                'subInscl'        => $result['subInscl']['id'] ?? null,
                                'subInsclName'    => $result['subInscl']['name'] ?? null,
                                'serviceDateTime' => $serviceDateTime,
                                'vstdate'         => $serviceDateTime ? date('Y-m-d', strtotime($serviceDateTime)) : $vstdate,
                                'sourceChannel'   => $sourceChannel,
                                'claimCode'       => $claimCode,
                                'claimType'       => $claimType,
                                'claim_status'    => $claimStatus,
                                'saved_at'        => now(),
                                'nhso_response'   => json_encode($row, JSON_UNESCAPED_UNICODE),
                                'statusAuthen'    => $result['statusAuthen'] ?? null,
                                'statusMessage'   => $result['statusMessage'] ?? null,
                                'sex'             => $result['sex'] ?? null,
                                'birthDate_year'  => $result['birthDate']['year'] ?? null,
                                'birthDate_month' => $result['birthDate']['month'] ?? null,
                                'nation_code'     => $result['nation']['code'] ?? null,
                                'nation_descriptionTh'=> $result['nation']['descriptionTh'] ?? null,
                                'province_id'     => $result['province']['id'] ?? null,
                                'province_name'   => $result['province']['name'] ?? null,
                                'hcode'           => $row['hospital']['hcode'] ?? null,
                                'hname'           => $row['hospital']['hname'] ?? null,
                                'serviceName'     => $row['service']['name'] ?? null,
                            ];
                            $inserted++;
                        }
                    }
            }

            if (!empty($upsertData)) {
                Nhso_Endpoint::insert($upsertData);
            }

            usleep(200000); // 0.2s delay between chunks
        }

        return response()->json([
            'ok' => true,
            'status' => 'success',
            'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ',
            'pulled_records' => $pulled,
            'inserted' => $inserted,
            'updated' => $updated
        ]);
    }

    /**
     * ดึงข้อมูลจาก สปสช (รายคน)
     */
    public function pullIndiv(Request $request)
    {
        $vstdate = $request->input('vstdate');
        $cid = $request->input('cid');

        if (!$vstdate || !$cid) {
            return response()->json(['status' => 'error', 'message' => 'Vstdate and CID are required'], 400);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $vstdate, $m) && (int)$m[1] > 2400) {
            $vstdate = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
            $request->merge(['vstdate' => $vstdate]);
        }

        $token = DB::connection('hosxp')
            ->table('sys_var')
            ->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')
            ->value('sys_value');


        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Token not found'], 500);
        }

        $localHcode = DB::connection('hosxp')->table('opdconfig')->value('hospitalcode');

        try {
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withToken($token)
                ->acceptJson()
                ->get("https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status", [
                    'personalId' => $cid,
                    'serviceDate' => $vstdate
                ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อกับ สปสช. ได้: ' . $e->getMessage()], 500);
        }

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'NHSO API request failed', 'raw' => $response->body()], 500);
        }

        $result = $response->json();

        if (!is_array($result)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid JSON response from NHSO API'], 500);
        }

        if (isset($result['status']) && $result['status'] === 'error') {
            return response()->json(['status' => 'error', 'message' => $result['message'] ?? 'NHSO API Error'], 500);
        }

        if (!isset($result['firstName'])) {
            $msg = $result['statusMessage'] ?? $result['message'] ?? '';
            // If the message indicates a query result (like "no authen found" / "ไม่พบข้อมูลการ authen"), return success with found = false
            if ($msg && (strpos($msg, 'ไม่พบ') !== false || strpos($msg, 'authen') !== false || strpos($msg, 'Authen') !== false)) {
                return response()->json([
                    'status' => 'success',
                    'found' => false,
                    'message' => $msg
                ]);
            }
            return response()->json(['status' => 'error', 'message' => $msg ?: 'ไม่พบข้อมูลบุคคลจาก สปสช. (กรุณาตรวจสอบ CID)'], 500);
        }

        $firstName = $result['firstName'];
        $lastName = $result['lastName'] ?? '';
        $mainInscl = $result['mainInscl']['id'] ?? '';
        $mainInsclName = $result['mainInscl']['name'] ?? '';
        $subInscl = $result['subInscl']['id'] ?? '';
        $subInsclName = $result['subInscl']['name'] ?? '';

        $services = $result['serviceHistories'] ?? [];

        $foundPiSit = false;

        foreach ($services as $row) {
            $serviceDateTime = $row['serviceDateTime'] ?? null;
            $sourceChannel = $row['sourceChannel'] ?? '';
            $claimCode = $row['claimCode'] ?? null;
            $claimType = $row['service']['code'] ?? null;
            $apiHcode = $row['hospital']['hcode'] ?? null;

            if (!$claimCode || !$claimType) {
                continue;
            }

            // ตรวจสอบ hcode ว่าตรงกับโรงพยาบาลเราหรือไม่
            if ($localHcode && $apiHcode && $apiHcode !== $localHcode) {
                continue;
            }
            // กรองตามเงื่อนไข: ทั่วไป/ฟอกไต เอาเฉพาะ EP, Homeward เอาเฉพาะ PP
            $shouldPull = false;
            if (in_array($claimType, ['PG0060001', 'PG0130001'])) {
                if (strpos($claimCode, 'EP') === 0) $shouldPull = true;
            } elseif ($claimType === 'PG0140001') {
                if (strpos($claimCode, 'PP') === 0) $shouldPull = true;
            } elseif ($sourceChannel === 'ENDPOINT') {
                $shouldPull = true;
            }

            if (!$shouldPull) {
                continue;
            }

            $indiv = Nhso_Endpoint::firstOrNew([
                'cid' => $cid,
                'claimCode' => $claimCode,
            ]);

            // กำหนด claim_status จาก claimCode (EP = เขียว, อื่นๆ = ส้ม)
            $claimStatus = (strpos($claimCode, 'EP') === 0) ? 'success' : 'pulled';

            $indiv->firstName = $firstName;
            $indiv->lastName = $lastName;
            $indiv->mainInscl = $mainInscl;
            $indiv->mainInsclName = $mainInsclName;
            $indiv->subInscl = $subInscl;
            $indiv->subInsclName = $subInsclName;
            $indiv->serviceDateTime = $serviceDateTime;
            $indiv->vstdate = date('Y-m-d', strtotime($serviceDateTime));
            $indiv->sourceChannel = $sourceChannel;
            $indiv->claimType = $claimType;
            $indiv->claim_status = $claimStatus;
            $indiv->saved_at = now();
            $indiv->nhso_response = json_encode($row, JSON_UNESCAPED_UNICODE);
            
            $indiv->statusAuthen = $result['statusAuthen'] ?? null;
            $indiv->statusMessage = $result['statusMessage'] ?? null;
            $indiv->sex = $result['sex'] ?? null;
            $indiv->birthDate_year = $result['birthDate']['year'] ?? null;
            $indiv->birthDate_month = $result['birthDate']['month'] ?? null;
            $indiv->nation_code = $result['nation']['code'] ?? null;
            $indiv->nation_descriptionTh = $result['nation']['descriptionTh'] ?? null;
            $indiv->province_id = $result['province']['id'] ?? null;
            $indiv->province_name = $result['province']['name'] ?? null;
            $indiv->hcode = $row['hospital']['hcode'] ?? null;
            $indiv->hname = $row['hospital']['hname'] ?? null;
            $indiv->serviceName = $row['service']['name'] ?? null;

            $indiv->save();

            $foundPiSit = true;
        }

        return response()->json([
            'status' => 'success',
            'found' => $foundPiSit,
            'message' => $foundPiSit ? 'พบข้อมูลปิดสิทธิจาก สปสช. แล้วครับ' : 'ไม่พบข้อมูลปิดสิทธิที่ สปสช. ยังไม่เคยปิดสิทธิสำหรับรายการนี้'
        ]);
    }

    /**
     * ดึงข้อมูลจาก สปสช ของเมื่อวาน (Auto)
     */
    public function pullYesterday(Request $request)
    {
        if (!$this->isAuthorizedSchedule($request)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access'], 401);
        }

        set_time_limit(600);
        $vstdate = Carbon::yesterday('Asia/Bangkok')->format('Y-m-d');

        $hosxp = DB::connection('hosxp')->select('
            SELECT DISTINCT pt.cid 
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN vn_stat vs ON vs.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate 
                 AND (ep.claim_status = "success" OR ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001")
            WHERE o.vstdate = ?
            AND (o.an = "" OR o.an IS NULL)
            AND vs.uc_money > 0
            AND ep.cid IS NULL
            AND pt.cid IS NOT NULL', [$vstdate]);

        $cids = array_map(static fn($row) => $row->cid, $hosxp);
        $token = DB::connection('hosxp')->table('sys_var')->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')->value('sys_value');

        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Token not found'], 500);
        }

        $localHcode = DB::connection('hosxp')->table('opdconfig')->value('hospitalcode');

        $pulled = 0;
        $inserted = 0;
        $updated = 0;

        foreach (array_chunk($cids, 50) as $chunk) {
            $existing_claims = Nhso_Endpoint::whereIn('cid', $chunk)
                ->where('vstdate', $vstdate)
                ->pluck('claimType', 'claimCode')
                ->toArray();

            $upsertData = [];

            foreach ($chunk as $cid) {
                $response = null;
                $attempts = 0;
                while ($attempts < 3) {
                    $attempts++;
                    try {
                        $response = Http::withoutVerifying()
                            ->timeout(12)
                            ->withToken($token)
                            ->acceptJson()
                            ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                                'personalId' => $cid,
                                'serviceDate' => $vstdate,
                            ]);

                        if ($response->status() === 429) {
                            usleep(1500000);
                            continue;
                        }

                        break;
                    } catch (\Throwable $e) {
                        if ($attempts >= 3) {
                            Log::error("NHSO Pull Yesterday logic error for CID: {$cid}", ['msg' => $e->getMessage()]);
                        }
                        usleep(500000);
                    }
                }

                usleep(120000); // 0.12s pacing

                if (!$response || $response->failed()) {
                    continue;
                }

                $result = $response->json();
                if (!is_array($result) || !isset($result['firstName']) || empty($result['serviceHistories'])) {
                    continue;
                }

                    foreach ($result['serviceHistories'] as $row) {
                        if (!is_array($row))
                            continue;

                        $claimCode = $row['claimCode'] ?? null;
                        $claimType = $row['service']['code'] ?? null;
                        $sourceChannel = $row['sourceChannel'] ?? '';
                        $serviceDateTime = $row['serviceDateTime'] ?? null;
                        $apiHcode = $row['hospital']['hcode'] ?? null;

                        if (!$claimCode)
                            continue;

                        // ตรวจสอบ hcode ว่าตรงกับโรงพยาบาลเราหรือไม่
                        if ($localHcode && $apiHcode && $apiHcode !== $localHcode) {
                            continue;
                        }

                        // กรองตามเงื่อนไข: ทั่วไป/ฟอกไต เอาเฉพาะ EP, Homeward เอาเฉพาะ PP
                        $shouldPull = false;
                        if (in_array($claimType, ['PG0060001', 'PG0130001'])) {
                            if (strpos($claimCode, 'EP') === 0) $shouldPull = true;
                        } elseif ($claimType === 'PG0140001') {
                            if (strpos($claimCode, 'PP') === 0) $shouldPull = true;
                        } elseif ($sourceChannel === 'ENDPOINT') {
                            $shouldPull = true;
                        }

                        if (!$shouldPull) {
                            continue;
                        }

                        $pulled++;

                        if (isset($existing_claims[$claimCode])) {
                            if ($existing_claims[$claimCode] !== $claimType) {
                                Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
                                $updated++;
                            }
                        } else {
                            $claimStatus = (strpos($claimCode, 'EP') === 0) ? 'success' : 'pulled';
                            $upsertData[] = [
                                'cid'             => $cid,
                                'firstName'       => $result['firstName'] ?? null,
                                'lastName'        => $result['lastName'] ?? null,
                                'mainInscl'       => $result['mainInscl']['id'] ?? null,
                                'mainInsclName'   => $result['mainInscl']['name'] ?? null,
                                'subInscl'        => $result['subInscl']['id'] ?? null,
                                'subInsclName'    => $result['subInscl']['name'] ?? null,
                                'serviceDateTime' => $serviceDateTime,
                                'vstdate'         => $serviceDateTime ? date('Y-m-d', strtotime($serviceDateTime)) : $vstdate,
                                'sourceChannel'   => $sourceChannel,
                                'claimCode'       => $claimCode,
                                'claimType'       => $claimType,
                                'claim_status'    => $claimStatus,
                                'saved_at'        => now(),
                                'nhso_response'   => json_encode($row, JSON_UNESCAPED_UNICODE),
                                'statusAuthen'    => $result['statusAuthen'] ?? null,
                                'statusMessage'   => $result['statusMessage'] ?? null,
                                'sex'             => $result['sex'] ?? null,
                                'birthDate_year'  => $result['birthDate']['year'] ?? null,
                                'birthDate_month' => $result['birthDate']['month'] ?? null,
                                'nation_code'     => $result['nation']['code'] ?? null,
                                'nation_descriptionTh'=> $result['nation']['descriptionTh'] ?? null,
                                'province_id'     => $result['province']['id'] ?? null,
                                'province_name'   => $result['province']['name'] ?? null,
                                'hcode'           => $row['hospital']['hcode'] ?? null,
                                'hname'           => $row['hospital']['hname'] ?? null,
                                'serviceName'     => $row['service']['name'] ?? null,
                            ];
                            $inserted++;
                        }
                    }
            }

            if (!empty($upsertData)) {
                Nhso_Endpoint::insert($upsertData);
            }

            usleep(200000);
        }

        $responseData = [
            'ok' => true,
            'status' => 'success',
            'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ',
            'pulled_records' => $pulled,
            'inserted' => $inserted,
            'updated' => $updated
        ];

        if (!app()->runningInConsole() && function_exists('appendAndLimitLog')) {
            $logMessage = "[" . now()->toDateTimeString() . "] NHSO Endpoint output: " . json_encode($responseData, JSON_UNESCAPED_UNICODE) . "\n";
            appendAndLimitLog('nhso_endpoint_schedule.log', $logMessage, 30);
        }

        return response()->json($responseData);
    }
    public function pushIndiv(Request $request)
    {
        $cid = $request->cid;
        $vstdate = $request->vstdate;

        if (!$cid || !$vstdate) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูล CID หรือวันที่ไม่ครบถ้วน'], 400);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $vstdate, $m) && (int)$m[1] > 2400) {
            $vstdate = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
            $request->merge(['vstdate' => $vstdate]);
        }

        // 1. ตรวจสอบสิทธิ์
        if (!auth()->check() || (auth()->user()->status !== 'admin' && auth()->user()->allow_nhso_endpoint !== 'Y')) {
            return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ส่งข้อมูลปิดสิทธิ'], 403);
        }

        try {
            // 2. Pre-check / Pull ล่าสุดจาก สปสช.
            $this->pullIndiv($request);
            // ตรวจสอบสถานะหลังดึงข้อมูล (ถ้าสำเร็จจากที่อื่นแล้ว status จะเป็น 'success')
            $checkLocal = DB::table('nhso_endpoint')->where('cid', $cid)->where('vstdate', $vstdate)->first();
            if ($checkLocal && @$checkLocal->claim_status === 'success') {
                return response()->json([
                    'status' => 'success', 
                    'message' => 'ตรวจสอบพบข้อมูลปิดสิทธิเรียบร้อยแล้วในระบบ สปสช. (ดึงสถานะล่าสุดให้แล้ว)',
                    'data' => $checkLocal
                ]);
            }

            // 3. ดึงข้อมูลจาก HOSxP (Financial & Service Detail)
            $hosxpData = DB::connection('hosxp')->select("
                SELECT 
                    (SELECT hospitalcode FROM opdconfig LIMIT 1) AS hcode,
                    p.cid AS pid,
                    o.vn AS visitNumber,
                    o.vstdate, o.vsttime,
                    pt.hipdata_code AS mainInsclCode,
                    vs.income AS totalAmount,
                    vs.uc_money AS privilegeAmount,
                    vs.rcpt_money AS paidAmount
                FROM ovst o
                INNER JOIN patient p ON p.hn = o.hn
                INNER JOIN vn_stat vs ON vs.vn = o.vn
                LEFT JOIN pttype pt ON pt.pttype = o.pttype
                WHERE p.cid = :cid AND o.vstdate = :vstdate
                LIMIT 1
            ", ['cid' => $cid, 'vstdate' => $vstdate]);

            if (empty($hosxpData)) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล visit ใน HOSxP'], 404);
            }

            $data = $hosxpData[0];
            $token = DB::connection('hosxp')
                ->table('sys_var')
                ->where('sys_name', 'NHSO-CONFIRM-PRIVIVLEGE-API-TOKEN')
                ->value('sys_value');

            if (!$token) {
                return response()->json(['status' => 'error', 'message' => 'กรุณาตั้งค่า NHSO Token ก่อนใช้งาน'], 400);
            }

            $recorderPid = auth()->check() ? auth()->user()->cid : "";
            // ลบช่องว่างหรือขีดออก (ถ้ามี)
            $recorderPid = preg_replace('/[^0-9]/', '', $recorderPid);

            if (strlen($recorderPid) !== 13) {
                return response()->json(['status' => 'error', 'message' => 'ผู้ใช้งานปัจจุบันไม่มีเลขบัตรประชาชน 13 หลัก (recorderPid) กรุณาตรวจสอบข้อมูลผู้ใช้งาน'], 400);
            }

            // 4. ประกอบร่าง JSON สำหรับส่ง ปิดสิทธิ (DataSet20231207)
            $serviceDateTime = strtotime($data->vstdate . ' ' . $data->vsttime) * 1000;

            $now = round(microtime(true) * 1000);

            $payload = [
                "hcode"            => $data->hcode,
                "visitNumber"      => $data->visitNumber,
                "pid"              => $data->pid,
                "transactionId"    => $data->hcode . $data->visitNumber,
                "serviceDateTime"  => $serviceDateTime,
                "invoiceDateTime"  => $serviceDateTime,
                "mainInsclCode"    => $data->mainInsclCode,
                "totalAmount"      => (float)$data->totalAmount,
                "paidAmount"       => (float)$data->paidAmount,
                "privilegeAmount"  => (float)$data->privilegeAmount,
                "claimServiceCode" => $request->claim_service_code ?: "PG0060001",
                "sourceId"         => "RiMS",
                "recorderPid"      => $recorderPid,
            ];


            Log::info('NHSO Push Payload:', ['payload' => $payload]);

            // 5. ส่งข้อมูลไปยัง สปสช. (DataSet20231207 v8)
            $apiUrl = 'https://nhsoapi.nhso.go.th/nhsoendpoint/api/nhso-claim-detail';
            /** @var \Illuminate\Http\Client\Response $apiResponse */
            $apiResponse = Http::withToken($token)
                ->withoutVerifying()
                ->acceptJson()
                ->post($apiUrl, $payload);

            $contents = $apiResponse->body();
            $resultArr = $apiResponse->json() ?? [];
            $result = (object) $resultArr;

            Log::info('NHSO Push Response:', [
                'status' => $apiResponse->status(),
                'body' => $contents
            ]);

            // 6. อัปเดตสถานะกลับลงฐานข้อมูล
            $hasDataError = isset($resultArr['dataError']) || isset($resultArr['error']);
            $success = ($apiResponse->successful() && !$hasDataError && (($resultArr['status'] ?? '') == '200' || ($resultArr['success'] ?? false) || isset($resultArr['authenCode'])));
            $status = $success ? 'success' : 'failed';

            // Extract claimCode (authenCode)
            $claimCode = $resultArr['data']['authenCode'] ?? null;
            if (!$claimCode && isset($resultArr['authenCode'])) {
                $claimCode = $resultArr['authenCode'];
            }
            
            DB::table('nhso_endpoint')
                ->updateOrInsert(
                ['cid' => $cid, 'vstdate' => $vstdate],
                [
                    'claim_status' => $status,
                    'claimCode'    => $claimCode, // สำคัญ: ต้องบันทึกเพื่อให้ JOIN ใน HomeController เจอ
                    'saved_at'     => now(),
                    'nhso_response' => $contents
                ]
            );

            if ($success) {
                // ดึงข้อมูลกลับมาทันทีเพื่อให้สถานะในระบบตรงกับ สปสช. 100%
                $this->pullIndiv($request);
                
                return response()->json(['status' => 'success', 'message' => 'ส่งข้อมูลปิดสิทธิสำเร็จและอัปเดตสถานะแล้ว', 'data' => $resultArr]);
            }

 else {
                $errorMsg = is_object($result) ? ($result->message ?? 'ไม่ทราบสาเหตุ') : 'สปสช. ส่งคืนข้อมูลที่ไม่ใช่ JSON: ' . substr($contents, 0, 100);
                return response()->json(['status' => 'error', 'message' => 'สปสช. ตอบกลับ: ' . $errorMsg], 500);
            }

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ทดสอบการเชื่อมต่อและสิทธิ์การเข้าถึง API ของ สปสช. (authenucws.nhso.go.th)
     */
    public function testConnection()
    {
        if (!auth()->check() || (auth()->user()->status !== 'admin' && auth()->user()->allow_nhso_endpoint !== 'Y')) {
            return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'], 403);
        }

        $token = DB::connection('hosxp')
            ->table('sys_var')
            ->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')
            ->value('sys_value');

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่พบตัวแปร NHSO-13FILE-FEE-SCHEDULE-API-TOKEN ในตาราง sys_var ของ HOSxP'
            ], 400);
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                    'personalId' => '1100000000000', // CID ตัวอย่าง
                    'serviceDate' => date('Y-m-d')
                ]);

            $status = $response->status();
            $body = $response->body();

            if ($response->successful() || $status === 400 || $status === 401) {
                if ($status === 401) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'เชื่อมต่อ API สำเร็จ แต่ Token ไม่ถูกต้อง (Unauthorized - 401)'
                    ]);
                }
                return response()->json([
                    'status' => 'success',
                    'message' => 'เชื่อมต่อกับระบบ สปสช. สำเร็จ และใช้งาน Token ได้ถูกต้อง (HTTP 200)'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'สปสช. ตอบกลับด้วยรหัส: ' . $status . ' - ' . substr($body, 0, 200)
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถเชื่อมต่อไปยัง สปสช. ได้: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงรายชื่อเคส/CID ทั้งหมดที่ต้องส่งไปเช็คที่ สปสช.
     */
    public function getPullList(Request $request)
    {
        // ตรวจสอบสิทธิ์
        if (!auth()->check() || (auth()->user()->status !== 'admin' && auth()->user()->allow_nhso_endpoint !== 'Y')) {
            return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดึงข้อมูลปิดสิทธิ'], 403);
        }

        $start_date = $request->input('start_date') ?: ($request->input('vstdate') ?: date('Y-m-d'));
        $end_date = $request->input('end_date') ?: $start_date;

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $start_date, $m) && (int)$m[1] > 2400) {
            $start_date = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $end_date, $m) && (int)$m[1] > 2400) {
            $end_date = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
        }

        $hosxp = DB::connection('hosxp')->select('
            SELECT o.vn, pt.cid, o.vstdate
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN vn_stat vs ON vs.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate 
                 AND (ep.claim_status = "success" OR ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001")
            LEFT JOIN (
                SELECT ori.vn FROM opitemrece ori 
                INNER JOIN hrims.lookup_icode li ON li.icode = ori.icode 
                WHERE li.kidney = "Y" AND ori.vstdate BETWEEN ? AND ?
                GROUP BY ori.vn
            ) kidney ON kidney.vn = o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND (o.an = "" OR o.an IS NULL)
            AND vs.uc_money > 0
            AND p.hipdata_code IN ("UCS","OFC","SSS","LGO","NHS","STP","BKK","BMT","SRT","KKT","PTY")
            AND ep.cid IS NULL
            AND kidney.vn IS NULL
            AND pt.cid IS NOT NULL
            ORDER BY o.vstdate ASC', 
            [$start_date, $end_date, $start_date, $end_date]);

        $total_vns = count($hosxp);
        $items = [];
        $cids = [];
        $seen = [];
        foreach ($hosxp as $row) {
            $key = $row->cid . '_' . $row->vstdate;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $items[] = [
                    'cid' => $row->cid,
                    'vstdate' => $row->vstdate
                ];
                $cids[] = $row->cid;
            }
        }

        return response()->json([
            'status' => 'success',
            'items' => $items,
            'cids' => $cids,
            'total' => count($items),
            'total_cids' => count($items),
            'total_vns' => $total_vns,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
    }

    /**
     * ดึงข้อมูล สปสช. ทีละ Chunk (ประมวลผลผ่าน sequential AJAX)
     */
    public function pullChunk(Request $request)
    {
        // ตรวจสอบสิทธิ์
        if (!auth()->check() || (auth()->user()->status !== 'admin' && auth()->user()->allow_nhso_endpoint !== 'Y')) {
            return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดึงข้อมูลปิดสิทธิ'], 403);
        }

        set_time_limit(180);

        $items = $request->input('items') ?? [];
        if (empty($items) && !empty($request->input('cids'))) {
            $defaultVstdate = $request->input('vstdate') ?? now()->format('Y-m-d');
            foreach ($request->input('cids') as $cid) {
                $items[] = ['cid' => $cid, 'vstdate' => $defaultVstdate];
            }
        }

        if (empty($items)) {
            return response()->json([
                'success' => true,
                'pulled' => 0,
                'inserted' => 0,
                'updated' => 0
            ]);
        }

        $token = DB::connection('hosxp')
            ->table('sys_var')
            ->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')
            ->value('sys_value');

        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบ Token NHSO ในระบบ'], 500);
        }

        $upsertData = [];
        $pulled = 0;
        $inserted = 0;
        $updated = 0;
        $errors = 0;

        foreach ($items as $item) {
            $cid = is_array($item) ? ($item['cid'] ?? null) : ($item->cid ?? null);
            $vstdate = is_array($item) ? ($item['vstdate'] ?? null) : ($item->vstdate ?? null);
            if (!$cid || !$vstdate) continue;

            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $vstdate, $m) && (int)$m[1] > 2400) {
                $vstdate = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
            }

            $response = null;
            $attempts = 0;
            while ($attempts < 3) {
                $attempts++;
                try {
                    $response = Http::withoutVerifying()
                        ->timeout(12)
                        ->withToken($token)
                        ->acceptJson()
                        ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                            'personalId' => $cid,
                            'serviceDate' => $vstdate,
                        ]);

                    if ($response->status() === 429) {
                        // Rate limit hit: sleep 1.5s and retry
                        usleep(1500000);
                        continue;
                    }

                    break; // Request finished (success or other code)
                } catch (\Throwable $e) {
                    if ($attempts >= 3) {
                        Log::error("NHSO Pull logic error for CID: {$cid}", ['msg' => $e->getMessage()]);
                    }
                    usleep(500000);
                }
            }

            // Small delay between requests to prevent triggering NHSO rate limit
            usleep(120000); // 0.12s

            if (!$response || $response->failed()) {
                $errors++;
                continue;
            }

            $result = $response->json();
            if (!is_array($result) || !isset($result['firstName']) || empty($result['serviceHistories'])) {
                continue;
            }

            $existing_claims = Nhso_Endpoint::where('cid', $cid)
                ->where('vstdate', $vstdate)
                ->pluck('claimType', 'claimCode')
                ->toArray();

            foreach ($result['serviceHistories'] as $row) {
                if (!is_array($row)) continue;

                $claimCode = $row['claimCode'] ?? null;
                $claimType = $row['service']['code'] ?? null;
                $sourceChannel = $row['sourceChannel'] ?? '';
                $serviceDateTime = $row['serviceDateTime'] ?? null;

                if (!$claimCode) continue;

                // กรองตามเงื่อนไข: ทั่วไป/ฟอกไต เอาเฉพาะ EP, Homeward เอาเฉพาะ PP
                $shouldPull = false;
                if (in_array($claimType, ['PG0060001', 'PG0130001'])) {
                    if (strpos($claimCode, 'EP') === 0) $shouldPull = true;
                } elseif ($claimType === 'PG0140001') {
                    if (strpos($claimCode, 'PP') === 0) $shouldPull = true;
                } elseif ($sourceChannel === 'ENDPOINT') {
                    $shouldPull = true;
                }

                if (!$shouldPull) {
                    continue;
                }

                $pulled++;

                if (isset($existing_claims[$claimCode])) {
                    if ($existing_claims[$claimCode] !== $claimType) {
                        Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
                        $updated++;
                    }
                } else {
                    // Prevent duplicate entries in same chunk
                    $alreadyInUpsert = false;
                    foreach ($upsertData as $u) {
                        if ($u['claimCode'] === $claimCode) {
                            $alreadyInUpsert = true;
                            break;
                        }
                    }
                    if ($alreadyInUpsert) {
                        continue;
                    }

                    $claimStatus = (strpos($claimCode, 'EP') === 0) ? 'success' : 'pulled';
                    $upsertData[] = [
                        'cid'             => $cid,
                        'firstName'       => $result['firstName'] ?? null,
                        'lastName'        => $result['lastName'] ?? null,
                        'mainInscl'       => $result['mainInscl']['id'] ?? null,
                        'mainInsclName'   => $result['mainInscl']['name'] ?? null,
                        'subInscl'        => $result['subInscl']['id'] ?? null,
                        'subInsclName'    => $result['subInscl']['name'] ?? null,
                        'serviceDateTime' => $serviceDateTime,
                        'vstdate'         => $serviceDateTime ? date('Y-m-d', strtotime($serviceDateTime)) : $vstdate,
                        'sourceChannel'   => $sourceChannel,
                        'claimCode'       => $claimCode,
                        'claimType'       => $claimType,
                        'claim_status'    => $claimStatus,
                        'saved_at'        => now(),
                    ];
                    $inserted++;
                }
            }
        }

        if (!empty($upsertData)) {
            Nhso_Endpoint::insert($upsertData);
        }

        return response()->json([
            'success' => true,
            'pulled' => $pulled,
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => $errors
        ]);
    }

    /**
     * บันทึก Log สำหรับการดึงข้อมูล NHSO แบบกำหนดเอง (Manual Pull)
     */
    public function logManualPull(Request $request)
    {
        $pulled = $request->input('pulled_records') ?? 0;
        $inserted = $request->input('inserted') ?? 0;
        $updated = $request->input('updated') ?? 0;
        $ok = $request->input('ok') ?? true;
        $message = $request->input('message') ?? 'ดึงข้อมูลสำเร็จ';

        $data = [
            'ok' => $ok,
            'message' => $message,
            'pulled_records' => $pulled,
            'inserted' => $inserted,
            'updated' => $updated
        ];

        $logMessage = "[" . now()->toDateTimeString() . "] NHSO Endpoint output: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        appendAndLimitLog('nhso_endpoint_schedule.log', $logMessage, 30);

        return response()->json(['status' => 'success']);
    }

    /**
     * ดึงข้อมูลรายชื่อปิดสิทธิ สปสช. แล้ว และรอดำเนินการปิดสิทธิ สำหรับแสดงผลในตาราง Modal
     */
    public function getEndpointData(Request $request)
    {
        // ตรวจสอบสิทธิ์
        if (!auth()->check() || (auth()->user()->status !== 'admin' && auth()->user()->allow_nhso_endpoint !== 'Y')) {
            return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลปิดสิทธิ สปสช.'], 403);
        }

        $start_date = $request->input('start_date') ?: date('Y-m-d');
        $end_date = $request->input('end_date') ?: date('Y-m-d');

        // Normalize Thai Buddhist Era (> 2400) to Christian Era (e.g. 2569 -> 2026)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $start_date, $m) && (int)$m[1] > 2400) {
            $start_date = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $end_date, $m) && (int)$m[1] > 2400) {
            $end_date = ((int)$m[1] - 543) . '-' . $m[2] . '-' . $m[3];
        }

        // 1. Closed Records (Visits with EP prefix in RiMS)
        $closed = DB::connection('hosxp')->select('
            SELECT pt.fname AS firstName, pt.lname AS lastName, pt.cid, 
                   COALESCE(ep.subInsclName, p.name) as subInsclName, ep.subInscl,
                   CONCAT(o.vstdate, " ", o.vsttime) as serviceDateTime,
                   COALESCE(ep.claimType, "") as claimType,
                   ep.claimCode as claimCode
            FROM ovst o
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
                 AND (ep.claim_status = "success" OR ep.claimCode LIKE "EP%")
            WHERE o.vstdate BETWEEN ? AND ?
            AND ep.claimCode LIKE "EP%"        
            AND (o.an = "" OR o.an IS NULL)
            ORDER BY o.vstdate DESC, o.vsttime DESC', [$start_date, $end_date]);

        // 2. Pending Records (Visits pending pull/push)
        $pending = DB::connection('hosxp')->select('
            SELECT o.vn, pt.cid, pt.hn, CONCAT(pt.pname, pt.fname, pt.lname) AS ptname, pt.mobile_phone_number,
                   p.name AS subInsclName, o.vstdate, o.vsttime, o.oqueue, vp.hospmain, vs.pdx, vs.income, 
                   vs.paid_money, vs.rcpt_money, vs.uc_money as debtor,
                   CONCAT(o.vstdate, " ", o.vsttime) as serviceDateTime, vp.auth_code AS claimCode
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN vn_stat vs ON vs.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate 
                 AND (ep.claim_status = "success" OR ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001")
            LEFT JOIN (
                SELECT ori.vn FROM opitemrece ori 
                INNER JOIN hrims.lookup_icode li ON li.icode = ori.icode 
                WHERE li.kidney = "Y" AND ori.vstdate BETWEEN ? AND ?
                GROUP BY ori.vn
            ) kidney ON kidney.vn = o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND (o.an = "" OR o.an IS NULL)
            AND vs.uc_money > 0
            AND p.hipdata_code IN ("UCS","OFC","SSS","LGO","NHS","STP","BKK","BMT","SRT","KKT","PTY")
            AND ep.cid IS NULL
            AND kidney.vn IS NULL
            ORDER BY o.vstdate DESC, o.vsttime DESC', 
            [$start_date, $end_date, $start_date, $end_date]);

        $formattedClosed = [];
        foreach ($closed as $idx => $row) {
            $formattedClosed[] = [
                'index' => $idx + 1,
                'name' => trim(($row->firstName ?? '') . ' ' . ($row->lastName ?? '')),
                'cid' => $row->cid ?? '',
                'subInsclName' => $row->subInsclName ?: ($row->subInscl ?? '-'),
                'serviceDateTime' => $row->serviceDateTime ? (function_exists('DatetimeThai') ? DatetimeThai($row->serviceDateTime) : $row->serviceDateTime) : '-',
                'claimType' => $row->claimType ?? '',
                'claimCode' => $row->claimCode ?? '',
            ];
        }

        $formattedPending = [];
        foreach ($pending as $idx => $row) {
            $formattedPending[] = [
                'index' => $idx + 1,
                'vn' => $row->vn,
                'cid' => $row->cid ?? '',
                'hn' => $row->hn ?? '',
                'ptname' => $row->ptname ?? '',
                'mobile_phone_number' => $row->mobile_phone_number ?: '-',
                'subInsclName' => $row->subInsclName ?? '-',
                'hospmain' => $row->hospmain ?? '-',
                'vstdate' => $row->vstdate,
                'vstdate_thai' => function_exists('DateThai') ? DateThai($row->vstdate) : $row->vstdate,
                'vsttime' => $row->vsttime,
                'oqueue' => $row->oqueue,
                'claimCode' => $row->claimCode ?? '',
                'pdx' => $row->pdx ?: '-',
                'income' => number_format($row->income ?? 0, 2),
                'paid_money' => number_format($row->paid_money ?? 0, 2),
                'rcpt_money' => number_format($row->rcpt_money ?? 0, 2),
                'debtor' => number_format($row->debtor ?? 0, 2),
            ];
        }

        return response()->json([
            'status' => 'success',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_date_thai' => function_exists('DateThai') ? DateThai($start_date) : $start_date,
            'end_date_thai' => function_exists('DateThai') ? DateThai($end_date) : $end_date,
            'closed' => $formattedClosed,
            'pending' => $formattedPending,
            'closed_count' => count($formattedClosed),
            'pending_count' => count($formattedPending),
        ]);
    }
}
