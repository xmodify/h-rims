<?php

namespace App\Http\Controllers;

use App\Helpers\PlaywrightHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ImportEdcController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check Playwright & KTB Credentials Status
     */
    public function checkKtbStatus()
    {
        $companyId = DB::table('main_setting')->where('name', 'ktb_company_id')->value('value');
        $userId = DB::table('main_setting')->where('name', 'ktb_user_id')->value('value');
        $password = DB::table('main_setting')->where('name', 'ktb_password')->value('value');

        $pwStatus = PlaywrightHelper::checkStatus();

        return response()->json([
            'success' => true,
            'has_credentials' => (!empty($companyId) && !empty($userId) && !empty($password)),
            'company_id' => $companyId ?: '',
            'user_id' => $userId ?: '',
            'playwright' => $pwStatus
        ]);
    }

    /**
     * Inspect a TXT file: parse rows and compare against edc_approve_list table.
     */
    protected function inspectTextFile(string $filePath, string $uniqueId): array
    {
        $fileName = basename($filePath);
        $handle = @fopen($filePath, 'r');
        if (!$handle) {
            return [
                'file_name' => $fileName,
                'path' => $uniqueId . '/' . $fileName,
                'total_records' => 0,
                'existing_records' => 0,
                'new_records' => 0,
                'status' => 'error',
                'status_text' => 'ไม่สามารถเปิดอ่านไฟล์ได้',
                'status_color' => 'danger',
                'vstdate' => '-',
                'post_date' => '-',
                'records' => []
            ];
        }

        $records = [];
        $totalRecords = 0;
        $existingRecords = 0;
        $firstVstdate = null;
        $firstPostDate = null;

        while (($line = fgets($handle)) !== false) {
            $line = str_replace("\xEF\xBB\xBF", "", $line);
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode('|', $line);
            if (count($parts) < 28) continue;

            // Transaction Date (Index 7)
            $dateStr = trim($parts[7] ?? '');
            $vstdate = null;
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
                $vstdate = "{$m[3]}-{$m[2]}-{$m[1]}";
            }

            $vsttime = trim($parts[8] ?? '');
            if (strlen($vsttime) > 8) $vsttime = substr($vsttime, 0, 8);

            // Post Date (Index 9)
            $postDateStr = trim($parts[9] ?? '');
            $postDate = null;
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $postDateStr, $m)) {
                $postDate = "{$m[3]}-{$m[2]}-{$m[1]}";
            }

            $postTime = trim($parts[10] ?? '');
            if (strlen($postTime) > 8) $postTime = substr($postTime, 0, 8);

            $cid = trim($parts[11] ?? '');
            $fname = trim($parts[12] ?? '');
            $lname = trim($parts[13] ?? '');
            $ptname = trim($fname . ' ' . $lname);
            $amount = floatval(trim($parts[22] ?? 0));
            $approveCode = trim($parts[26] ?? '');
            $invNo = trim($parts[23] ?? '');
            $refNo = trim($parts[27] ?? '');
            $finalCode = $approveCode ?: ($invNo ?: $refNo);

            if (!$firstVstdate && $vstdate) $firstVstdate = $vstdate;
            if (!$firstPostDate && $postDate) $firstPostDate = $postDate;

            // Check if this record already exists in edc_approve_list
            $exists = false;
            if (!empty($cid) && !empty($finalCode)) {
                $query = DB::table('edc_approve_list')->where('cid', $cid)->where('approve_code', $finalCode);
                if ($vstdate) {
                    $query->where('vstdate', $vstdate);
                }
                $exists = $query->exists();
            }

            if ($exists) {
                $existingRecords++;
            }

            $totalRecords++;

            $records[] = [
                'cid' => $cid,
                'ptname' => $ptname,
                'vstdate' => $vstdate,
                'vsttime' => $vsttime,
                'post_date' => $postDate,
                'post_time' => $postTime,
                'amount' => $amount,
                'approve_code' => $finalCode,
                'is_existing' => $exists
            ];
        }

        fclose($handle);

        $newRecords = $totalRecords - $existingRecords;

        if ($totalRecords === 0) {
            $status = 'empty';
            $statusText = 'ไม่มีข้อมูลในไฟล์';
            $statusColor = 'secondary';
        } elseif ($existingRecords === $totalRecords) {
            $status = 'full_existing';
            $statusText = "นำเข้าครบแล้ว ($existingRecords/$totalRecords รายการ)";
            $statusColor = 'success';
        } elseif ($existingRecords > 0) {
            $status = 'partial_existing';
            $statusText = "มีแล้วบางส่วน ($existingRecords/$totalRecords) - ใหม่ $newRecords รายการ";
            $statusColor = 'warning';
        } else {
            $status = 'not_existing';
            $statusText = "ยังไม่เคยนำเข้า (0/$totalRecords รายการ)";
            $statusColor = 'secondary';
        }

        return [
            'file_name' => $fileName,
            'path' => $uniqueId . '/' . $fileName,
            'total_records' => $totalRecords,
            'existing_records' => $existingRecords,
            'new_records' => $newRecords,
            'status' => $status,
            'status_text' => $statusText,
            'status_color' => $statusColor,
            'vstdate' => $firstVstdate,
            'post_date' => $firstPostDate,
            'records' => $records
        ];
    }

    /**
     * Trigger Playwright to download EDC reports from KTB Corporate and return preview list.
     */
    public function syncKtb(Request $request)
    {
        $request->validate([
            'from_date' => 'required|string',
            'to_date' => 'required|string',
        ]);

        $fromDate = $request->input('from_date'); // e.g. 2026-08-21 or 21-08-2026
        $toDate = $request->input('to_date');

        // Fetch credentials from main_setting
        $companyId = DB::table('main_setting')->where('name', 'ktb_company_id')->value('value');
        $userId = DB::table('main_setting')->where('name', 'ktb_user_id')->value('value');
        $password = DB::table('main_setting')->where('name', 'ktb_password')->value('value');

        if (empty($companyId) || empty($userId) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาตั้งค่า KTB Company ID, User ID และ Password ในเมนู Main Setting ก่อนดำเนินการ'
            ], 422);
        }

        $uniqueId = uniqid('ktb_sync_');
        $outputDir = storage_path('app/tmp_edc_import/' . $uniqueId);
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        // Run crawler
        $crawlResult = PlaywrightHelper::runKtbCrawler([
            'company_id' => $companyId,
            'user_id' => $userId,
            'password' => $password,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'output_dir' => $outputDir,
            'headless' => true,
        ]);

        if (!$crawlResult || empty($crawlResult['success'])) {
            File::deleteDirectory($outputDir);
            return response()->json([
                'success' => false,
                'message' => $crawlResult['message'] ?? 'เกิดข้อผิดพลาดในการเชื่อมต่อหรือดาวน์โหลดข้อมูลจากธนาคารกรุงไทย'
            ], 422);
        }

        // Auto-extract any downloaded ZIP files from KTB
        $allFiles = File::allFiles($outputDir);
        foreach ($allFiles as $zf) {
            if (strtolower($zf->getExtension()) === 'zip') {
                $zipPath = $zf->getPathname();
                $extracted = false;
                if (class_exists('\ZipArchive')) {
                    $zip = new \ZipArchive;
                    if ($zip->open($zipPath) === TRUE) {
                        $zip->extractTo($outputDir);
                        $zip->close();
                        $extracted = true;
                    }
                }
                if (!$extracted) {
                    @exec("unzip -o " . escapeshellarg($zipPath) . " -d " . escapeshellarg($outputDir));
                }
            }
        }

        // Scan all .txt files inside outputDir (including extracted from zip)
        $files = File::allFiles($outputDir);
        $filesSummary = [];
        $totalAllRecords = 0;
        $totalAllNew = 0;

        foreach ($files as $f) {
            if (strtolower($f->getExtension()) === 'txt') {
                $info = $this->inspectTextFile($f->getPathname(), $uniqueId);
                $filesSummary[] = $info;
                $totalAllRecords += $info['total_records'];
                $totalAllNew += $info['new_records'];
            }
        }

        if (empty($filesSummary)) {
            $hasFiles = count($allFiles) > 0;
            File::deleteDirectory($outputDir);
            return response()->json([
                'success' => true,
                'message' => $hasFiles ? 'ดาวน์โหลดไฟล์รายงานจาก KTB แล้ว แต่ไม่พบข้อมูล Text รายการรูดบัตรในช่วงวันที่เลือก' : ($crawlResult['message'] ?? 'ไม่พบไฟล์รายงาน EDC ในช่วงวันที่ระบุ'),
                'unique_id' => $uniqueId,
                'files' => [],
                'total_files' => 0,
                'total_records' => 0,
                'total_new' => 0
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "ซิงค์ข้อมูลจาก KTB สำเร็จ พบทั้งหมด " . count($filesSummary) . " ไฟล์ (รวม {$totalAllRecords} รายการ, ใหม่ {$totalAllNew} รายการ)",
            'unique_id' => $uniqueId,
            'files' => $filesSummary,
            'total_files' => count($filesSummary),
            'total_records' => $totalAllRecords,
            'total_new' => $totalAllNew
        ]);
    }

    /**
     * Handle manual ZIP upload and extract preview list.
     */
    public function importZip(Request $request)
    {
        $request->validate([
            'zip_file' => 'required',
        ]);

        $filesUploaded = $request->file('zip_file');
        if (!is_array($filesUploaded)) {
            $filesUploaded = [$filesUploaded];
        }

        $uniqueId = uniqid('edc_manual_');
        $extractPath = storage_path('app/tmp_edc_import/' . $uniqueId);

        if (!File::exists($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }

        foreach ($filesUploaded as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($file->getRealPath()) === true) {
                    $zip->extractTo($extractPath);
                    $zip->close();
                }
            } elseif ($ext === 'txt') {
                $file->move($extractPath, $file->getClientOriginalName());
            }
        }

        // Get list of all txt files inside
        $files = File::files($extractPath);
        $filesSummary = [];
        $totalAllRecords = 0;
        $totalAllNew = 0;

        foreach ($files as $f) {
            if (strtolower($f->getExtension()) === 'txt') {
                $info = $this->inspectTextFile($f->getPathname(), $uniqueId);
                $filesSummary[] = $info;
                $totalAllRecords += $info['total_records'];
                $totalAllNew += $info['new_records'];
            }
        }

        if (empty($filesSummary)) {
            File::deleteDirectory($extractPath);
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์ .txt ในไฟล์ที่อัปโหลด'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "อัปโหลดและตรวจสอบไฟล์สำเร็จ พบทั้งหมด " . count($filesSummary) . " ไฟล์",
            'unique_id' => $uniqueId,
            'files' => $filesSummary,
            'total_files' => count($filesSummary),
            'total_records' => $totalAllRecords,
            'total_new' => $totalAllNew
        ]);
    }

    /**
     * Process a single extracted TXT file into edc_approve_list & sync HOSxP.
     */
    public function importFile(Request $request)
    {
        $request->validate([
            'unique_id' => 'required|string',
            'file_name' => 'required|string',
            'import_mode' => 'nullable|string|in:skip_existing,overwrite',
        ]);

        $uniqueId = $request->input('unique_id');
        $fileName = basename($request->input('file_name'));
        $importMode = $request->input('import_mode', 'skip_existing');
        
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
        $updatedCount = 0;
        $skippedCount = 0;
        $matchedCount = 0;

        DB::beginTransaction();
        try {
            while (($line = fgets($handle)) !== false) {
                // Remove UTF-8 BOM if present
                $line = str_replace("\xEF\xBB\xBF", "", $line);
                $line = trim($line);
                if (empty($line)) continue;

                $parts = explode('|', $line);
                if (count($parts) < 28) continue;

                // Extract fields
                $merchant_id = trim($parts[3] ?? '');
                $terminal_id = trim($parts[6] ?? '');
                
                // Parse date (dd/mm/yyyy) using Transaction Date (index 7)
                $dateStr = trim($parts[7] ?? '');
                $vstdate = null;
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateStr, $m)) {
                    $vstdate = "{$m[3]}-{$m[2]}-{$m[1]}";
                }

                // Parse time using Transaction Time (index 8)
                $vsttime = trim($parts[8] ?? null);
                if ($vsttime && strlen($vsttime) > 8) {
                    $vsttime = substr($vsttime, 0, 8);
                }

                // Parse Post Date (dd/mm/yyyy) using index 9
                $postDateStr = trim($parts[9] ?? '');
                $post_date = null;
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $postDateStr, $m)) {
                    $post_date = "{$m[3]}-{$m[2]}-{$m[1]}";
                }

                // Parse Post Time using index 10
                $post_time = trim($parts[10] ?? null);
                if ($post_time && strlen($post_time) > 8) {
                    $post_time = substr($post_time, 0, 8);
                }

                $cid = trim($parts[11] ?? '');
                $fname = trim($parts[12] ?? '');
                $lname = trim($parts[13] ?? '');
                $ptname = trim($fname . ' ' . $lname);
                $amount = floatval(trim($parts[22] ?? 0));
                $app_code = trim($parts[26] ?? '');
                $ref_no = trim($parts[27] ?? '');
                $trans_type = trim($parts[25] ?? '');
                $inv_no = trim($parts[23] ?? '');
                $approve_code = trim($parts[26] ?? '');
                $edc_type = trim($parts[28] ?? '');
                $card_type = trim($parts[29] ?? '');
                $note = trim($parts[30] ?? '');
                $finalCode = $approve_code ?: ($inv_no ?: $ref_no);

                if (empty($finalCode) && empty($cid)) {
                    continue;
                }

                // Check existing
                $existingQuery = DB::table('edc_approve_list')
                    ->where('cid', $cid)
                    ->where('approve_code', $finalCode);
                if ($vstdate) {
                    $existingQuery->where('vstdate', $vstdate);
                }
                $existingRecord = $existingQuery->first();

                if ($existingRecord) {
                    if ($importMode === 'skip_existing') {
                        $skippedCount++;
                        continue;
                    }
                    // Overwrite mode
                    DB::table('edc_approve_list')->where('id', $existingRecord->id)->update([
                        'ptname' => $ptname,
                        'vsttime' => $vsttime,
                        'post_date' => $post_date,
                        'post_time' => $post_time,
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
                        'updated_at' => now()
                    ]);
                    $updatedCount++;
                } else {
                    // Insert new record
                    DB::table('edc_approve_list')->insert([
                        'cid' => $cid,
                        'vstdate' => $vstdate,
                        'vsttime' => $vsttime,
                        'post_date' => $post_date,
                        'post_time' => $post_time,
                        'ptname' => $ptname,
                        'amount' => $amount,
                        'approve_code' => $finalCode,
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
                    ]);
                    $importedCount++;
                }

                // Sync/Update HOSxP connection (ovst_seq.edc_approve_list_text)
                if (!empty($cid) && !empty($vstdate) && !empty($finalCode)) {
                    try {
                        $vn = DB::connection('hosxp')->table('ovst')
                            ->join('patient', 'patient.hn', '=', 'ovst.hn')
                            ->where('patient.cid', $cid)
                            ->where('ovst.vstdate', $vstdate)
                            ->value('ovst.vn');

                        if ($vn) {
                            DB::connection('hosxp')->table('ovst_seq')
                                ->where('vn', $vn)
                                ->update([
                                    'edc_approve_list_text' => $finalCode
                                ]);
                            $matchedCount++;
                        }
                    } catch (\Throwable $ex) {
                        Log::warning("Could not update HOSxP ovst_seq: " . $ex->getMessage());
                    }
                }
            }

            fclose($handle);
            DB::commit();

            // Re-inspect the file to get updated DB status
            $updatedInfo = $this->inspectTextFile($filePath, $uniqueId);

            $msg = "ไฟล์ {$fileName}: นำเข้าใหม่ {$importedCount} รายการ";
            if ($updatedCount > 0) $msg .= ", ปรับปรุง {$updatedCount} รายการ";
            if ($skippedCount > 0) $msg .= ", ข้ามที่ซ้ำ {$skippedCount} รายการ";
            if ($matchedCount > 0) $msg .= " (เชื่อมโยง HOSxP {$matchedCount} รายการ)";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'imported_count' => $importedCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'matched_count' => $matchedCount,
                'file_info' => $updatedInfo
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
