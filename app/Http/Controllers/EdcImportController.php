<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class EdcImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Handle ZIP upload and extraction.
     * Returns a list of text files inside the ZIP.
     */
    public function importZip(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|extensions:zip',
        ]);

        $file = $request->file('zip_file');
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเปิดไฟล์ ZIP ได้'
            ], 400);
        }

        $uniqueId = uniqid('edc_');
        $extractPath = storage_path('app/tmp_edc_import/' . $uniqueId);

        if (!File::exists($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Get list of all txt files inside
        $files = File::files($extractPath);
        $txtFiles = [];

        foreach ($files as $f) {
            if (strtolower($f->getExtension()) === 'txt') {
                $txtFiles[] = [
                    'name' => $f->getFilename(),
                    'path' => $uniqueId . '/' . $f->getFilename(),
                ];
            }
        }

        if (empty($txtFiles)) {
            // Cleanup empty dir
            File::deleteDirectory($extractPath);
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์ .txt ในไฟล์ ZIP'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'unique_id' => $uniqueId,
            'files' => $txtFiles
        ]);
    }

    /**
     * Process a single extracted TXT file.
     */
    public function importFile(Request $request)
    {
        $request->validate([
            'unique_id' => 'required|string',
            'file_name' => 'required|string',
        ]);

        $uniqueId = $request->input('unique_id');
        $fileName = $request->input('file_name');
        
        // Prevent path traversal
        $fileName = basename($fileName);
        $filePath = storage_path('app/tmp_edc_import/' . $uniqueId . '/' . $fileName);

        if (!File::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์ข้อมูล ' . $fileName
            ], 400);
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถอ่านไฟล์ได้'
            ], 500);
        }

        $importedCount = 0;
        $matchedCount = 0;

        DB::beginTransaction();
        try {
            while (($line = fgets($handle)) !== false) {
                // Remove UTF-8 BOM if present
                $line = str_replace("\xEF\xBB\xBF", "", $line);
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                $parts = explode('|', $line);
                if (count($parts) < 28) {
                    continue;
                }

                // Extract fields
                $merchant_id = trim($parts[3] ?? '');
                $terminal_id = trim($parts[6] ?? '');
                
                // Parse date (dd/mm/yyyy) using Transaction Date (index 9) instead of Post Date (index 7)
                $dateStr = trim($parts[9] ?? '');
                $vstdate = null;
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
                    $vstdate = "{$m[3]}-{$m[2]}-{$m[1]}";
                }

                // Parse time using Transaction Time (index 10) instead of Post Time (index 8)
                $vsttime = trim($parts[10] ?? null);
                if ($vsttime && strlen($vsttime) > 8) {
                    $vsttime = substr($vsttime, 0, 8);
                }

                $cid = trim($parts[11] ?? '');
                $fname = trim($parts[12] ?? '');
                $lname = trim($parts[13] ?? '');
                $ptname = trim($fname . ' ' . $lname);
                $amount = floatval(trim($parts[22] ?? 0));
                $app_code = trim($parts[26] ?? ''); // APPR. CODE (index 26)
                $ref_no = trim($parts[27] ?? '');  // Trans Ref ID (index 27)
                $trans_type = trim($parts[25] ?? ''); // TXNS CODE (index 25)
                $inv_no = trim($parts[23] ?? '');  // BATCH (index 23)
                $approve_code = trim($parts[26] ?? ''); // APPR. CODE (index 26)
                $edc_type = trim($parts[28] ?? ''); // USER ID (index 28)
                $card_type = trim($parts[29] ?? ''); // Card Type (index 29)
                $note = trim($parts[30] ?? '');

                if (empty($approve_code) && !empty($inv_no)) {
                    // Fallback to inv_no if approve_code is empty (sometimes they might be same)
                    // But usually, approve_code is the 9-digit EDC approval code.
                    // If both empty, skip or insert what we have
                }

                // Insert/Update edc_approve_list
                DB::table('edc_approve_list')->updateOrInsert(
                    [
                        'cid' => $cid,
                        'vstdate' => $vstdate,
                        'approve_code' => $approve_code ?: ($inv_no ?: $ref_no)
                    ],
                    [
                        'ptname' => $ptname,
                        'vsttime' => $vsttime,
                        'amount' => $amount,
                        'app_code' => $app_code,
                        'ref_no' => $ref_no,
                        'trans_type' => $trans_type,
                        'inv_no' => $inv_no,
                        'terminal_id' => $terminal_id,
                        'merchant_id' => $merchant_id,
                        'edc_type' => $edc_type,
                        'card_type' => $card_type,
                        'note' => $note,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );

                $importedCount++;

                // Sync/Update HOSxP connection (ovst_seq.edc_approve_list_text)
                if (!empty($cid) && !empty($vstdate) && !empty($approve_code)) {
                    $vn = DB::connection('hosxp')->table('ovst')
                        ->join('patient', 'patient.hn', '=', 'ovst.hn')
                        ->where('patient.cid', $cid)
                        ->where('ovst.vstdate', $vstdate)
                        ->value('ovst.vn');

                    if ($vn) {
                        try {
                            DB::connection('hosxp')->table('ovst_seq')
                                ->where('vn', $vn)
                                ->update([
                                    'edc_approve_list_text' => $approve_code
                                ]);
                            $matchedCount++;
                        } catch (\Throwable $ex) {
                            Log::warning("Could not update HOSxP ovst_seq: " . $ex->getMessage());
                        }
                    }
                }
            }

            fclose($handle);
            DB::commit();

            // Delete the processed file
            File::delete($filePath);

            // If no more files in directory, delete directory
            $remainingFiles = File::files(storage_path('app/tmp_edc_import/' . $uniqueId));
            if (empty($remainingFiles)) {
                File::deleteDirectory(storage_path('app/tmp_edc_import/' . $uniqueId));
            }

            return response()->json([
                'success' => true,
                'message' => "นำเข้าไฟล์ {$fileName} สำเร็จ (นำเข้า {$importedCount} รายการ, เชื่อมโยง HOSxP {$matchedCount} รายการ)"
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            if ($handle) {
                fclose($handle);
            }
            Log::error("EDC Import File Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการประมวลผลไฟล์: ' . $e->getMessage()
            ], 500);
        }
    }
}
