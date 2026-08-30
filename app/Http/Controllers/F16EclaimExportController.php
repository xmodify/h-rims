<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\F16EclaimExportService;
use App\Services\LicenseVerificationService;

class F16EclaimExportController extends Controller
{
    /**
     * ดึงข้อมูลสรุป Record Count และตัวอย่างข้อมูลสำหรับแสดงใน Modal Preview
     */
    public function preview(Request $request)
    {
        if (!LicenseVerificationService::isModuleLicensed('export_f16_eclaim') || (auth()->check() && auth()->user()->status !== 'admin' && auth()->user()->allow_export_f16_eclaim !== 'Y')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณไม่มีสิทธิ์ในการส่งออกข้อมูล 16 แฟ้ม e-Claim'
            ], 403);
        }

        $type = $request->input('type');
        $isIp = $type === 'ip' || $request->boolean('is_ip');
        if (!$type && !$request->has('is_ip')) {
            $isIp = $request->has('ans') && !$request->has('vns');
        }
        $rawKeys = $isIp ? ($request->input('ans') ?: $request->input('vns', [])) : ($request->input('vns') ?: $request->input('ans', []));
        if (is_string($rawKeys)) {
            $decoded = json_decode($rawKeys, true);
            $keys = is_array($decoded) ? $decoded : explode(',', $rawKeys);
        } else {
            $keys = (array)$rawKeys;
        }
        $keys = array_values(array_filter(array_unique((array)$keys)));
        if (empty($keys)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งออก'
            ], 422);
        }

        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');

        try {
            if ($isIp) {
                $result = F16EclaimExportService::generate16FilesIp($keys);
            } else {
                $result = F16EclaimExportService::generate16Files($keys);
            }

            $headers = [];
            $tables = [];
            $rawFiles = [];
            foreach ($result['files'] as $key => $content) {
                if (empty($content)) {
                    $headers[$key] = [];
                    $tables[$key] = [];
                    $rawFiles[$key] = '';
                    continue;
                }
                $rawFiles[$key] = $content;
                $lines = preg_split('/\r\n|\r|\n/', trim($content));
                $rows = [];
                foreach ($lines as $line) {
                    if ($line === '') continue;
                    $rows[] = explode('|', $line);
                }
                $headers[$key] = count($rows) > 0 ? $rows[0] : [];
                $tables[$key] = count($rows) > 1 ? array_slice($rows, 1, 100) : [];
            }

            $claimType = $isIp ? 'IP' : 'OP';
            $claimCode = strtoupper(trim($request->input('claim_code', 'CLAIM')));
            $thYear = date('Y') + 543;
            $subfolderName = "F16_ECLAIM_{$claimType}_{$claimCode}_{$thYear}" . date('md_Hi');

            return response()->json([
                'status' => 'success',
                'counts' => $result['counts'],
                'headers' => $headers,
                'tables' => $tables,
                'raw_files' => $rawFiles,
                'total_visits' => $result['total_visits'],
                'subfolder_name' => $subfolderName,
                'hcode' => $result['hcode']
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการประมวลผล 16 แฟ้ม: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงเนื้อหาเต็มของทั้ง 16 แฟ้ม สำหรับให้ JavaScript บันทึกลงโฟลเดอร์โดยตรง
     */
    public function exportData(Request $request)
    {
        if (!LicenseVerificationService::isModuleLicensed('export_f16_eclaim') || (auth()->check() && auth()->user()->status !== 'admin' && auth()->user()->allow_export_f16_eclaim !== 'Y')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณไม่มีสิทธิ์ในการส่งออกข้อมูล 16 แฟ้ม e-Claim'
            ], 403);
        }

        $type = $request->input('type');
        $isIp = $type === 'ip' || $request->boolean('is_ip');
        if (!$type && !$request->has('is_ip')) {
            $isIp = $request->has('ans') && !$request->has('vns');
        }
        $rawKeys = $isIp ? ($request->input('ans') ?: $request->input('vns', [])) : ($request->input('vns') ?: $request->input('ans', []));
        $claimType = $isIp ? 'IP' : 'OP';
        $claimCode = strtoupper(trim($request->input('claim_code', 'CLAIM')));
        if (is_string($rawKeys)) {
            $decoded = json_decode($rawKeys, true);
            $keys = is_array($decoded) ? $decoded : explode(',', $rawKeys);
        } else {
            $keys = (array)$rawKeys;
        }
        $keys = array_values(array_filter(array_unique((array)$keys)));
        if (empty($keys)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งออก'
            ], 422);
        }

        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');

        try {
            if ($isIp) {
                $result = F16EclaimExportService::generate16FilesIp($keys);
            } else {
                $result = F16EclaimExportService::generate16Files($keys);
            }
            
            // Format Thai year (e.g. 2569) + MMDD_HHMM
            $thYear = date('Y') + 543;
            $subfolderName = "F16_ECLAIM_{$claimType}_{$claimCode}_{$thYear}" . date('md_Hi');

            return response()->json([
                'status' => 'success',
                'files' => $result['files'],
                'counts' => $result['counts'],
                'total_visits' => $result['total_visits'],
                'hcode' => $result['hcode'],
                'subfolder_name' => $subfolderName
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการส่งออกข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ตรวจสอบสถานะ e-Claim Token
     */
    public function checkToken(Request $request)
    {
        try {
            $tokenDetail = F16EclaimExportService::getEclaimTokenDetail();

            if ($tokenDetail['success'] && !empty($tokenDetail['token'])) {
                return response()->json([
                    'status' => 'success',
                    'has_token' => true,
                    'token' => $tokenDetail['token'],
                    'token_type' => 'e-Claim Token (' . ($tokenDetail['eclaim_user'] ?? 'User') . ')',
                    'user_name' => $tokenDetail['user_name'] ?? (auth()->user()->name ?? 'e-Claim User'),
                    'eclaim_user' => $tokenDetail['eclaim_user'] ?? '',
                    'message' => 'e-Claim Token พร้อมใช้งาน'
                ]);
            }

            return response()->json([
                'status' => 'error',
                'has_token' => false,
                'token' => null,
                'token_type' => 'None',
                'user_name' => auth()->user()->name ?? 'Unknown',
                'eclaim_user' => $tokenDetail['eclaim_user'] ?? '',
                'has_credentials' => $tokenDetail['has_credentials'] ?? false,
                'message' => $tokenDetail['message'] ?? 'ไม่สามารถดึง e-Claim Access Token ได้ กรุณาตรวจสอบ e-Claim User และ Password'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'has_token' => false,
                'message' => 'เกิดข้อผิดพลาดในการตรวจสอบ Token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ส่งข้อมูล 16 แฟ้ม e-Claim เข้าสู่ สปสช. API โดยตรง
     */
    public function sendApi(Request $request)
    {
        if (!LicenseVerificationService::isModuleLicensed('export_f16_eclaim') || (auth()->check() && auth()->user()->status !== 'admin' && auth()->user()->allow_export_f16_eclaim !== 'Y')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณไม่มีสิทธิ์ในการส่งออกข้อมูล 16 แฟ้ม e-Claim'
            ], 403);
        }

        $type = $request->input('type');
        $isIp = $type === 'ip' || $request->boolean('is_ip');
        if (!$type && !$request->has('is_ip')) {
            $isIp = $request->has('ans') && !$request->has('vns');
        }

        $rawKeys = $isIp ? ($request->input('ans') ?: $request->input('vns', [])) : ($request->input('vns') ?: $request->input('ans', []));
        $claimCode = strtoupper(trim($request->input('claim_code', 'CLAIM')));
        $customToken = $request->input('custom_token');

        if (is_string($rawKeys)) {
            $decoded = json_decode($rawKeys, true);
            $keys = is_array($decoded) ? $decoded : explode(',', $rawKeys);
        } else {
            $keys = (array)$rawKeys;
        }
        $keys = array_values(array_filter(array_unique((array)$keys)));

        if (empty($keys)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งออก'
            ], 422);
        }

        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');

        try {
            $apiResult = F16EclaimExportService::sendToEclaimApi($keys, $isIp, $claimCode, $customToken);

            if ($apiResult['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $apiResult['message'],
                    'transaction_id' => $apiResult['transaction_id'] ?? null,
                    'total' => $apiResult['total'] ?? count($keys),
                    'token_type' => $apiResult['token_type'] ?? 'e-Claim Token',
                    'sender_name' => $apiResult['sender_name'] ?? (auth()->user()->name ?? 'System')
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $apiResult['message'] ?? 'เกิดข้อผิดพลาดในการส่งข้อมูลเข้า e-Claim API',
                'raw_response' => $apiResult['raw_response'] ?? null
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในระบบส่ง API: ' . $e->getMessage()
            ], 500);
        }
    }
}
