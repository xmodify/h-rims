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
     * ดึงข้อมูลจาก สปสช (แบบกลุ่ม)
     */
    public function pull(Request $request)
    {
        set_time_limit(600);

        $vstdate = $request->input('vstdate') ?? now()->format('Y-m-d');
        $hosxp = DB::connection('hosxp')->select(
            '
        SELECT DISTINCT pt.cid
        FROM ovst o
        INNER JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
        INNER JOIN pttype p ON p.pttype = vp.pttype
        LEFT JOIN patient pt ON pt.hn = o.hn
        WHERE o.vstdate = ?
        AND p.hipdata_code IN ("UCS","WEL")
        AND (o.an = "" OR o.an IS NULL)
        AND pt.cid IS NOT NULL',
            [$vstdate]
        );

        $cids = array_column($hosxp, 'cid');

        $token = DB::connection('hosxp')
            ->table('sys_var')
            ->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')
            ->value('sys_value');


        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบ Token NHSO ในระบบ'], 500);
        }

        foreach (array_chunk($cids, 50) as $chunk) {
            $existing_claims = Nhso_Endpoint::whereIn('cid', $chunk)
                ->where('vstdate', $vstdate)
                ->pluck('claimType', 'claimCode')
                ->toArray();

            $upsertData = [];

            foreach ($chunk as $cid) {
                try {
                    $response = Http::timeout(5)
                        ->withToken($token)
                        ->acceptJson()
                        ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                            'personalId' => $cid,
                            'serviceDate' => $vstdate,
                        ]);

                    if ($response->failed()) {
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

                        if (isset($existing_claims[$claimCode])) {
                            if ($existing_claims[$claimCode] !== $claimType) {
                                Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
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
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error("NHSO Pull logic error for CID: {$cid}", ['msg' => $e->getMessage()]);
                }
            }

            if (!empty($upsertData)) {
                Nhso_Endpoint::insert($upsertData);
            }

            usleep(200000); // 0.2s delay between chunks
        }

        return response()->json(['status' => 'success', 'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ']);
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

        $token = DB::connection('hosxp')
            ->table('sys_var')
            ->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')
            ->value('sys_value');


        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Token not found'], 500);
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status", [
                'personalId' => $cid,
                'serviceDate' => $vstdate
            ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'NHSO API request failed', 'raw' => $response->body()], 500);
        }

        $result = $response->json();

        if (!isset($result['firstName']) || !isset($result['serviceHistories'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid data from NHSO API'], 500);
        }

        $firstName = $result['firstName'];
        $lastName = $result['lastName'];
        $mainInscl = $result['mainInscl']['id'] ?? '';
        $mainInsclName = $result['mainInscl']['name'] ?? '';
        $subInscl = $result['subInscl']['id'] ?? '';
        $subInsclName = $result['subInscl']['name'] ?? '';

        $services = $result['serviceHistories'];

        $foundPiSit = false;

        foreach ($services as $row) {
            $serviceDateTime = $row['serviceDateTime'] ?? null;
            $sourceChannel = $row['sourceChannel'] ?? '';
            $claimCode = $row['claimCode'] ?? null;
            $claimType = $row['service']['code'] ?? null;

            if (!$claimCode || !$claimType) {
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
            $indiv->nhso_response = json_encode($row);
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
    public function pullYesterday()
    {
        set_time_limit(600);
        $vstdate = Carbon::yesterday('Asia/Bangkok')->format('Y-m-d');

        $hosxp = DB::connection('hosxp')->select(
            '
        SELECT DISTINCT pt.cid
        FROM ovst o
        INNER JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
        INNER JOIN pttype p ON p.pttype = vp.pttype
        LEFT JOIN patient pt ON pt.hn = o.hn
        WHERE o.vstdate = ?
        AND p.hipdata_code IN ("UCS","WEL")
        AND (o.an = "" OR o.an IS NULL)
        AND pt.cid IS NOT NULL',
            [$vstdate]
        );

        $cids = array_map(static fn($row) => $row->cid, $hosxp);
        $token = DB::connection('hosxp')->table('sys_var')->where('sys_name', 'NHSO-13FILE-FEE-SCHEDULE-API-TOKEN')->value('sys_value');

        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'Token not found'], 500);
        }

        foreach (array_chunk($cids, 50) as $chunk) {
            $existing_claims = Nhso_Endpoint::whereIn('cid', $chunk)
                ->where('vstdate', $vstdate)
                ->pluck('claimType', 'claimCode')
                ->toArray();

            $upsertData = [];

            foreach ($chunk as $cid) {
                try {
                    $response = Http::timeout(5)
                        ->withToken($token)
                        ->acceptJson()
                        ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                            'personalId' => $cid,
                            'serviceDate' => $vstdate,
                        ]);

                    if ($response->failed())
                        continue;

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

                        if (!$claimCode)
                            continue;

                        if (isset($existing_claims[$claimCode])) {
                            if ($existing_claims[$claimCode] !== $claimType) {
                                Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
                            }
                        } else {
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
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error("NHSO Pull Yesterday logic error for CID: {$cid}", ['msg' => $e->getMessage()]);
                }
            }

            if (!empty($upsertData)) {
                Nhso_Endpoint::insert($upsertData);
            }

            usleep(200000);
        }

        return response()->json(['status' => 'success', 'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ']);
    }
    public function pushIndiv(Request $request)
    {
        $cid = $request->cid;
        $vstdate = $request->vstdate;

        if (!$cid || !$vstdate) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูล CID หรือวันที่ไม่ครบถ้วน'], 400);
        }

        // 1. ตรวจสอบสิทธิ์ (ถ้ามีระบบ Auth)
        if (auth()->check() && auth()->user()->allow_nhso_endpoint !== 'Y' && auth()->user()->status !== 'admin') {
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
}
