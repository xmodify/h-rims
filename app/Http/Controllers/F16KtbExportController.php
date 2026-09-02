<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\F16KtbExportService;

class F16KtbExportController extends Controller
{
    protected F16KtbExportService $exportService;

    public function __construct(F16KtbExportService $exportService)
    {
        $this->middleware('auth');
        $this->exportService = $exportService;
    }

    /**
     * Preview ข้อมูลทั้ง 16/17 แฟ้มผ่าน AJAX Modal
     */
    public function previewData(Request $request)
    {
        $vns = $request->input('keys', []);
        $activityCode = $request->input('activity_code', 'S01');

        if (empty($vns)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่ต้องการส่งออกอย่างน้อย 1 รายการ'
            ], 400);
        }

        try {
            $preview = $this->exportService->getPreviewData($vns, $activityCode);
            return response()->json([
                'success' => true,
                'files' => $preview['files'],
                'file_names' => $preview['file_names'],
                'raw_files' => $preview['raw_files'],
                'tables' => $preview['tables'],
                'headers' => $preview['headers'],
                'counts' => $preview['counts'],
                'subfolder_name' => $preview['subfolder_name'],
                'total_keys' => count($vns),
                'activity_code' => $activityCode
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * สร้างและดาวน์โหลดไฟล์ 16 แฟ้ม Zip
     */
    public function exportZip(Request $request)
    {
        $vns = $request->input('keys', []);
        $activityCode = $request->input('activity_code', 'S01');

        if (empty($vns)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่ต้องการส่งออก'
            ], 400);
        }

        try {
            $result = $this->exportService->exportZip($vns, $activityCode);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถสร้างไฟล์ Zip ได้: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดาวน์โหลดไฟล์ Zip
     */
    public function downloadZip($fileName)
    {
        $safeFileName = basename($fileName);
        $filePath = storage_path('app/' . $safeFileName);

        if (!file_exists($filePath)) {
            abort(404, 'ไม่พบไฟล์ที่ต้องการดาวน์โหลด');
        }

        return response()->download($filePath, $safeFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
