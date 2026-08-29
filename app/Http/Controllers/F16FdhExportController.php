<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\F16FdhExportService;
use App\Services\LicenseVerificationService;

class F16FdhExportController extends Controller
{
    /**
     * ดึงข้อมูลสรุป Record Count และตัวอย่างข้อมูลสำหรับแสดงใน Modal Preview (16 แฟ้ม FDH)
     */
    public function preview(Request $request)
    {
        if (!LicenseVerificationService::isModuleLicensed('export_f16_fdh')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณยังไม่มี License สำหรับโมดูล ส่งออก 16 แฟ้ม FDH (export_f16_fdh)'
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
                $result = F16FdhExportService::generate16FilesIp($keys);
            } else {
                $result = F16FdhExportService::generate16Files($keys);
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
            $claimCode = strtoupper(trim($request->input('claim_code', 'UCS')));
            $claimCode = str_replace('_IP_', '_', $claimCode);
            if (str_starts_with($claimCode, 'IP_')) {
                $claimCode = substr($claimCode, 3);
            }
            $thYear = date('Y') + 543;
            $subfolderName = "F16_FDH_{$claimType}_{$claimCode}_{$thYear}" . date('md_Hi');

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
        if (!LicenseVerificationService::isModuleLicensed('export_f16_fdh')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณยังไม่มี License สำหรับโมดูล ส่งออก 16 แฟ้ม FDH (export_f16_fdh)'
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
            if ($isIp) {
                $result = F16FdhExportService::generate16FilesIp($keys);
            } else {
                $result = F16FdhExportService::generate16Files($keys);
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
}
