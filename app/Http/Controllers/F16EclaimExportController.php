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
        if (!LicenseVerificationService::isModuleLicensed('export_f16_eclaim')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณยังไม่มี License สำหรับโมดูล ส่งออก 16 แฟ้ม (export_f16_eclaim)'
            ], 403);
        }

        $vns = $request->input('vns', []);
        if (is_string($vns)) {
            $decoded = json_decode($vns, true);
            $vns = is_array($decoded) ? $decoded : explode(',', $vns);
        }
        $vns = array_values(array_filter(array_unique((array)$vns)));
        if (empty($vns)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งออก'
            ], 422);
        }

        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');

        try {
            $result = F16EclaimExportService::generate16Files($vns);

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
                'subfolder_name' => $result['subfolder_name'] ?? ('F16_' . ($request->input('claim_code', 'OFC')) . '_' . date('Ymd_Hi')),
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
        if (!LicenseVerificationService::isModuleLicensed('export_f16_eclaim')) {
            return response()->json([
                'status' => 'error',
                'message' => 'คุณยังไม่มี License สำหรับโมดูล ส่งออก 16 แฟ้ม (export_f16_eclaim)'
            ], 403);
        }

        $vns = $request->input('vns', []);
        $claimCode = strtoupper(trim($request->input('claim_code', 'CLAIM')));
        if (is_string($vns)) {
            $decoded = json_decode($vns, true);
            $vns = is_array($decoded) ? $decoded : explode(',', $vns);
        }
        $vns = array_values(array_filter(array_unique((array)$vns)));
        if (empty($vns)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งออก'
            ], 422);
        }

        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '512M');

        try {
            $result = F16EclaimExportService::generate16Files($vns);
            
            // Format Thai year (e.g. 2569) + MMDD_HHMM
            $thYear = date('Y') + 543;
            $subfolderName = "F16_{$claimCode}_{$thYear}" . date('md_Hi');

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
}
