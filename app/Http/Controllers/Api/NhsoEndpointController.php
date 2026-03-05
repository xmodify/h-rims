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
        SELECT o.vn, o.hn, pt.cid, vp.auth_code
        FROM ovst o
        INNER JOIN visit_pttype vp ON vp.vn = o.vn 
        LEFT JOIN patient pt ON pt.hn = o.hn
        WHERE o.vstdate = ?
        AND pt.cid NOT IN (SELECT cid FROM hrims.nhso_endpoint WHERE vstdate = ? AND cid IS NOT NULL)',
            [$vstdate, $vstdate]
        );

        $cids = array_column($hosxp, 'cid');

        $token = DB::table('main_setting')
            ->where('name', 'token_authen_kiosk_nhso')
            ->value('value');

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ Token NHSO ในระบบ'], 500);
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

                        if (isset($existing_claims[$claimCode])) {
                            if ($existing_claims[$claimCode] !== $claimType) {
                                Nhso_Endpoint::where('claimCode', $claimCode)->update(['claimType' => $claimType]);
                            }
                        } elseif ($sourceChannel === 'ENDPOINT' || $claimType === 'PG0140001') {
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

        return response()->json(['success' => true, 'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ']);
    }

    /**
     * ดึงข้อมูลจาก สปสช (รายคน)
     */
    public function pullIndiv(Request $request)
    {
        $vstdate = $request->input('vstdate');
        $cid = $request->input('cid');

        if (!$vstdate || !$cid) {
            return response()->json(['error' => 'Vstdate and CID are required'], 400);
        }

        $token = DB::table('main_setting')
            ->where('name', 'token_authen_kiosk_nhso')
            ->value('value');

        if (!$token) {
            return response()->json(['error' => 'Token not found'], 500);
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get("https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status", [
                'personalId' => $cid,
                'serviceDate' => $vstdate
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'NHSO API request failed', 'message' => $response->body()], 500);
        }

        $result = $response->json();

        if (!isset($result['firstName']) || !isset($result['serviceHistories'])) {
            return response()->json(['error' => 'Invalid data from NHSO API'], 500);
        }

        $firstName = $result['firstName'];
        $lastName = $result['lastName'];
        $mainInscl = $result['mainInscl']['id'] ?? '';
        $mainInsclName = $result['mainInscl']['name'] ?? '';
        $subInscl = $result['subInscl']['id'] ?? '';
        $subInsclName = $result['subInscl']['name'] ?? '';

        $services = $result['serviceHistories'];

        foreach ($services as $row) {
            $serviceDateTime = $row['serviceDateTime'] ?? null;
            $sourceChannel = $row['sourceChannel'] ?? '';
            $claimCode = $row['claimCode'] ?? null;
            $claimType = $row['service']['code'] ?? null;

            if (!$claimCode || !$claimType) {
                continue;
            }
            if (!($sourceChannel === 'ENDPOINT' || $claimType === 'PG0140001')) {
                continue;
            }

            $indiv = Nhso_Endpoint::firstOrNew([
                'cid' => $cid,
                'claimCode' => $claimCode,
            ]);

            if (!$indiv->exists || $sourceChannel == 'ENDPOINT' || $claimType == 'PG0140001') {
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
                $indiv->save();
            }
        }

        return response()->json(['success' => true]);
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
        SELECT o.vn, o.hn, pt.cid, vp.auth_code
        FROM ovst o
        INNER JOIN visit_pttype vp ON vp.vn = o.vn 
        LEFT JOIN patient pt ON pt.hn = o.hn
        LEFT JOIN hrims.nhso_endpoint nep ON nep.vstdate = o.vstdate AND nep.cid = pt.cid
        WHERE o.vstdate = ? AND nep.cid IS NULL',
            [$vstdate]
        );

        $cids = array_map(static fn($row) => $row->cid, $hosxp);
        $token = DB::table('main_setting')->where('name', 'token_authen_kiosk_nhso')->value('value');

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token not found'], 500);
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
                        } elseif ($sourceChannel === 'ENDPOINT' || $claimType === 'PG0140001') {
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

        return response()->json(['success' => true, 'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ']);
    }
}
