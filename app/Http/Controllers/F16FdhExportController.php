<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\F16FdhExportService;
use App\Services\LicenseVerificationService;

class F16FdhExportController extends Controller
{
    /**
     * ดึงข้อมูลสรุป Record Count และตัวอย่างข้อมูลสำหรับแสดงใน Modal Preview (16 แฟ้ม FDH)
     */
    public function preview(Request $request)
    {
        if (!LicenseVerificationService::isModuleLicensed('export_f16_fdh') || (auth()->check() && auth()->user()->status !== 'admin' && auth()->user()->allow_export_f16_fdh !== 'Y')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณไม่มีสิทธิ์ในการส่งออกข้อมูล 16 แฟ้ม FDH'
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
            $claimType = $isIp ? 'IP' : 'OP';
            $claimCode = strtoupper(trim($request->input('claim_code', 'UCS')));
            $cleanClaimCode = str_replace('_IP_', '_', $claimCode);
            if (str_starts_with($cleanClaimCode, 'IP_')) {
                $cleanClaimCode = substr($cleanClaimCode, 3);
            }
            $thYear = date('Y') + 543;
            $subfolderName = "F16_FDH_{$claimType}_{$cleanClaimCode}_{$thYear}" . date('md_Hi');

            $options = ['claim_code' => $claimCode];
            if ($isIp) {
                $result = F16FdhExportService::generate16FilesIp($keys, $options);
            } else {
                $result = F16FdhExportService::generate16Files($keys, $options);
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
                'message' => 'เกิดข้อผิดพลาดในการประมวลผล 16 แฟ้ม FDH: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงเนื้อหาเต็มของทั้ง 16/17 แฟ้ม FDH สำหรับให้ JavaScript บันทึกลงโฟลเดอร์โดยตรง
     */
    public function exportData(Request $request)
    {
        if (!LicenseVerificationService::isModuleLicensed('export_f16_fdh') || (auth()->check() && auth()->user()->status !== 'admin' && auth()->user()->allow_export_f16_fdh !== 'Y')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณไม่มีสิทธิ์ในการส่งออกข้อมูล 16 แฟ้ม FDH'
            ], 403);
        }

        $type = $request->input('type');
        $isIp = $type === 'ip' || $request->boolean('is_ip');
        if (!$type && !$request->has('is_ip')) {
            $isIp = $request->has('ans') && !$request->has('vns');
        }
        $rawKeys = $isIp ? ($request->input('ans') ?: $request->input('vns', [])) : ($request->input('vns') ?: $request->input('ans', []));
        $claimType = $isIp ? 'IP' : 'OP';
        $claimCode = strtoupper(trim($request->input('claim_code', 'UCS')));
        $claimCode = str_replace('_IP_', '_', $claimCode);
        if (str_starts_with($claimCode, 'IP_')) {
            $claimCode = substr($claimCode, 3);
        }
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
            $options = ['claim_code' => $claimCode];
            if ($isIp) {
                $result = F16FdhExportService::generate16FilesIp($keys, $options);
            } else {
                $result = F16FdhExportService::generate16Files($keys, $options);
            }
            
            $thYear = date('Y') + 543;
            $subfolderName = "F16_FDH_{$claimType}_{$claimCode}_{$thYear}" . date('md_Hi');

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
                'message' => 'เกิดข้อผิดพลาดในการส่งออกข้อมูล 16 แฟ้ม FDH: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ตรวจสอบความพร้อมของ FDH Token ของผู้ใช้งานปัจจุบัน
     */
    public function checkToken(Request $request)
    {
        $tokenDetail = F16FdhExportService::getFdhTokenDetail();
        $user = Auth::user();

        if ($tokenDetail['success']) {
            return response()->json([
                'status' => 'success',
                'has_token' => true,
                'has_credentials' => true,
                'token' => $tokenDetail['token'],
                'fdh_user' => $tokenDetail['fdh_user'],
                'token_type' => 'FDH Token (API)',
                'user_name' => $user->name ?? $tokenDetail['fdh_user'],
                'csrf_token' => csrf_token(),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'has_token' => false,
            'has_credentials' => $tokenDetail['has_credentials'] ?? false,
            'fdh_user' => $tokenDetail['fdh_user'] ?? ($user->fdh_user ?? null),
            'message' => $tokenDetail['message'] ?? 'ไม่สามารถขอ Token จากระบบ FDH ได้',
            'user_name' => $user->name ?? 'ผู้ให้บริการ',
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * ส่งชุดข้อมูล 16 แฟ้มมาตรฐาน FDH เข้าสู่ MOPH FDH API Gateway โดยตรง
     */
    public function sendApi(Request $request)
    {
        if (!LicenseVerificationService::isModuleLicensed('export_f16_fdh') || (auth()->check() && auth()->user()->status !== 'admin' && auth()->user()->allow_export_f16_fdh !== 'Y')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณไม่มีสิทธิ์ในการส่งออกข้อมูล 16 แฟ้ม FDH'
            ], 403);
        }

        $type = $request->input('type');
        $isIp = $type === 'ip' || $request->boolean('is_ip');
        if (!$type && !$request->has('is_ip')) {
            $isIp = $request->has('ans') && !$request->has('vns');
        }
        $rawKeys = $isIp ? ($request->input('ans') ?: $request->input('vns', [])) : ($request->input('vns') ?: $request->input('ans', []));
        $claimCode = strtoupper(trim($request->input('claim_code', 'UCS')));

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
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งข้อมูล'
            ], 422);
        }

        $customToken = $request->input('token');

        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');

        try {
            $options = ['claim_code' => $claimCode];
            $result = F16FdhExportService::sendToFdhApi($keys, $isIp, $claimCode, $customToken, $options);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'transaction_id' => $result['transaction_id'],
                    'total' => $result['total'],
                    'token_type' => $result['token_type'],
                    'sender_name' => $result['sender_name']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'need_login' => $result['need_login'] ?? false,
                    'provider_login_url' => route('auth.health-id.redirect'),
                    'message' => $result['message']
                ], ($result['need_login'] ?? false) ? 401 : 400);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการส่งข้อมูลเข้า FDH API: ' . $e->getMessage()
            ], 500);
        }
    }
}
