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
        if (empty($vns) || !is_array($vns)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งออก'
            ], 422);
        }

        try {
            $result = F16EclaimExportService::generate16Files($vns);

            // Create preview snippets (first 25 lines of each file) for faster rendering in modal
            $snippets = [];
            foreach ($result['files'] as $key => $content) {
                if (empty($content)) {
                    $snippets[$key] = '';
                    continue;
                }
                $lines = explode("\r\n", $content);
                $slice = array_slice($lines, 0, 30);
                $snippet = implode("\n", $slice);
                if (count($lines) > 30) {
                    $snippet .= "\n... (แสดงตัวอย่าง 30 แถวแรก จากทั้งหมด " . count($lines) . " แถว) ...";
                }
                $snippets[$key] = $snippet;
            }

            return response()->json([
                'status' => 'success',
                'counts' => $result['counts'],
                'snippets' => $snippets,
                'total_visits' => $result['total_visits'],
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
        if (empty($vns) || !is_array($vns)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกรายการอย่างน้อย 1 รายการก่อนส่งออก'
            ], 422);
        }

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
