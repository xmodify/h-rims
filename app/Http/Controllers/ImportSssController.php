<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use SimpleXMLElement;

class ImportSssController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_import !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'นำเข้าข้อมูล'], 403);
                }
                return $next($request);
            }
        ]);
    }

    /**
     * Parse Buddhist Era date "DD/MM/YYYY HH:II" or "DD/MM/YYYY" to "YYYY-MM-DD"
     */
    private function parseBuddhistDate($dateStr)
    {
        $dateStr = trim($dateStr);
        if (empty($dateStr)) {
            return null;
        }
        $parts = explode(' ', $dateStr);
        $dateParts = explode('/', $parts[0]);
        if (count($dateParts) === 3) {
            $year = (int)$dateParts[2] - 543;
            $month = str_pad($dateParts[1], 2, '0', STR_PAD_LEFT);
            $day = str_pad($dateParts[0], 2, '0', STR_PAD_LEFT);
            return "$year-$month-$day";
        }
        return null;
    }

    /**
     * Parse STM Datetime (ISO format or Buddhist format) to get date, time, and datetime strings
     */
    private function parseStmDateTime($dttran_raw)
    {
        $dttran_raw = trim($dttran_raw);
        if (empty($dttran_raw)) {
            return ['date' => null, 'time' => null, 'datetime' => null];
        }

        // Standard ISO format: 2026-04-11T07:06:36
        if (str_contains($dttran_raw, 'T')) {
            $parts = explode('T', $dttran_raw);
            $date = $parts[0];
            $time = $parts[1] ?? null;
            return [
                'date' => $date,
                'time' => $time,
                'datetime' => $date . ' ' . $time
            ];
        }

        // Buddhist format: "11/04/69 07.06" or "11/04/2569 07:06:36"
        $parts = explode(' ', $dttran_raw);
        $datePart = $parts[0];
        $timePart = $parts[1] ?? null;

        if ($timePart) {
            $timePart = str_replace('.', ':', $timePart);
        }

        $dateParts = explode('/', $datePart);
        if (count($dateParts) === 3) {
            $day = str_pad($dateParts[0], 2, '0', STR_PAD_LEFT);
            $month = str_pad($dateParts[1], 2, '0', STR_PAD_LEFT);
            $yearRaw = (int)$dateParts[2];
            
            if ($yearRaw < 100) {
                $year = ($yearRaw + 2500) - 543;
            } else {
                $year = $yearRaw - 543;
            }
            
            $formattedDate = "$year-$month-$day";
            return [
                'date' => $formattedDate,
                'time' => $timePart,
                'datetime' => $formattedDate . ' ' . $timePart
            ];
        }

        if (str_contains($dttran_raw, ' ')) {
            $parts = explode(' ', $dttran_raw);
            return [
                'date' => $parts[0],
                'time' => $parts[1] ?? null,
                'datetime' => $dttran_raw
            ];
        }

        return ['date' => $dttran_raw, 'time' => null, 'datetime' => $dttran_raw];
    }

    /**
     * Find actual HOSxP VN based on invoice number, CID, date, and time
     */
    private function findHosxpVn($invno, $pid, $vstdate, $vsttimeRaw)
    {
        // 1. Match by invno (InvoiceNo) in ovst_sss_billtran
        if (!empty($invno) && $invno !== '0') {
            $vn = DB::connection('hosxp')
                ->table('ovst_sss_billtran')
                ->where('invno', $invno)
                ->value('vn');
            if ($vn) {
                return $vn;
            }

            // 2. Match by invno in vn_stat.debt_id_list
            $vn = DB::connection('hosxp')
                ->table('vn_stat')
                ->where('debt_id_list', $invno)
                ->value('vn');
            if ($vn) {
                return $vn;
            }
        }

        // 3. Match by PID (CID) and Date (and optional Time prefix)
        if (!empty($pid) && !empty($vstdate)) {
            $timePrefix = null;
            if (!empty($vsttimeRaw)) {
                $cleanTime = str_replace('.', ':', trim($vsttimeRaw));
                if (str_contains($cleanTime, 'T')) {
                    $parts = explode('T', $cleanTime);
                    $cleanTime = $parts[1] ?? '';
                }
                $timeParts = explode(':', trim($cleanTime));
                if (count($timeParts) >= 2) {
                    $timePrefix = str_pad(trim($timeParts[0]), 2, '0', STR_PAD_LEFT) . ':' . str_pad(trim($timeParts[1]), 2, '0', STR_PAD_LEFT);
                }
            }

            $query = DB::connection('hosxp')
                ->table('ovst as o')
                ->join('patient as pt', 'pt.hn', '=', 'o.hn')
                ->where('pt.cid', $pid)
                ->where('o.vstdate', $vstdate);

            if ($timePrefix) {
                $query->where('o.vsttime', 'like', $timePrefix . '%');
            }

            $vn = $query->value('o.vn');
            if ($vn) {
                return $vn;
            }

            // Fallback: match by PID and date only
            $vn = DB::connection('hosxp')
                ->table('ovst as o')
                ->join('patient as pt', 'pt.hn', '=', 'o.hn')
                ->where('pt.cid', $pid)
                ->where('o.vstdate', $vstdate)
                ->value('o.vn');
            if ($vn) {
                return $vn;
            }
        }

        return null;
    }

    /**
     * Import REP ZIP File (.BIL inside)
     */
    public function import_rep(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('zip_file');
        $uniqueId = uniqid('sss_rep_');
        $extractPath = storage_path('app/tmp_sss_rep/' . $uniqueId);

        try {
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                return response()->json(['success' => false, 'message' => 'ไฟล์ ZIP เสียหาย (ไม่สามารถเปิดไฟล์ได้)'], 400);
            }
            if (!File::exists($extractPath)) {
                File::makeDirectory($extractPath, 0755, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();
        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแตกไฟล์: ' . $e->getMessage()], 400);
        }

        try {
            $files = File::files($extractPath);
            $processedCount = 0;
            $fileFound = false;

            foreach ($files as $f) {
                $fileName = $f->getFilename();
                if (str_contains(strtoupper($fileName), 'SOCDBIL') && strtolower($f->getExtension()) === 'bil') {
                    $fileFound = true;
                    // Delete existing records of the same file to prevent duplicates
                    DB::table('sss_ssop_rep')->where('rep_file', $fileName)->delete();

                    $contentBytes = File::get($f->getRealPath());
                    $content = @iconv('Windows-874', 'UTF-8//IGNORE', $contentBytes);
                    if ($content === false) {
                        $content = mb_convert_encoding($contentBytes, 'UTF-8', 'TIS-620');
                    }

                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        // Check for line header starting with *|
                        if (str_starts_with($line, '*|')) {
                            $raw_data = trim(substr($line, 2));
                            $parts = explode('|', $raw_data);
                            if (count($parts) >= 1) {
                                $detail_cols = explode(',', $parts[0]);
                                $error_codes = isset($parts[1]) ? trim($parts[1]) : '';

                                if (count($detail_cols) >= 11) {
                                    $claim_type = trim($detail_cols[0]);
                                    $repline = trim($detail_cols[1]);
                                    $hcode = trim($detail_cols[2]);
                                    $hmain = trim($detail_cols[3]);
                                    $dttran_raw = trim($detail_cols[5]);
                                    $invno = trim($detail_cols[6]);
                                    $pid = trim($detail_cols[7]);
                                    $bp_type = trim($detail_cols[8]);
                                    $amount = trim($detail_cols[9]);
                                    $claim_price = trim($detail_cols[10]);

                                    $dttran = $this->parseBuddhistDate($dttran_raw);

                                    // Extract time prefix
                                    $time_raw = '';
                                    $dt_parts = explode(' ', trim($dttran_raw));
                                    if (isset($dt_parts[1])) {
                                        $time_raw = $dt_parts[1];
                                    }

                                    // Match HOSxP VN
                                    $vn = $this->findHosxpVn($invno, $pid, $dttran, $time_raw);

                                    DB::table('sss_ssop_rep')->insert([
                                        'rep_file' => $fileName,
                                        'repline' => is_numeric($repline) ? (int)$repline : null,
                                        'hcode' => $hcode,
                                        'hmain' => $hmain,
                                        'vn' => $vn, // HOSxP VN (or null if not found)
                                        'invno' => $invno,
                                        'hn' => null,
                                        'pid' => $pid,
                                        'dttran' => $dttran ? ($dttran . ' ' . ($time_raw ?: '00:00:00')) : null,
                                        'dttran_date' => $dttran,
                                        'dttran_time' => !empty($time_raw) ? $time_raw : null,
                                        'claim_type' => $claim_type,
                                        'amount' => is_numeric($amount) ? (float)$amount : null,
                                        'claim_price' => is_numeric($claim_price) ? (float)$claim_price : null,
                                        'error_codes' => $error_codes,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);
                                    $processedCount++;
                                }
                            }
                        }
                    }
                }
            }

            File::deleteDirectory($extractPath);
            if (!$fileFound) {
                $foundFiles = [];
                foreach ($files as $f) {
                    $foundFiles[] = $f->getFilename();
                }
                $filesStr = !empty($foundFiles) ? ' (พบไฟล์ด้านใน: ' . implode(', ', $foundFiles) . ')' : ' (ไม่พบไฟล์ใดๆ ด้านใน ZIP)';
                return response()->json([
                    'success' => false,
                    'message' => 'เลือกประเภทไฟล์ไม่ถูกต้อง'
                ], 400);
            }
            return response()->json([
                'success' => true,
                'message' => "นำเข้าไฟล์ REP สำเร็จเรียบร้อยแล้ว (ประมวลผลทั้งหมด $processedCount รายการ)"
            ]);

        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล REP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import STM ZIP File (.xml inside)
     */
    public function import_stm(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('zip_file');
        $uniqueId = uniqid('sss_stm_');
        $extractPath = storage_path('app/tmp_sss_stm/' . $uniqueId);

        try {
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                return response()->json(['success' => false, 'message' => 'ไฟล์ ZIP เสียหาย (ไม่สามารถเปิดไฟล์ได้)'], 400);
            }
            if (!File::exists($extractPath)) {
                File::makeDirectory($extractPath, 0755, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();
        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแตกไฟล์: ' . $e->getMessage()], 400);
        }

        try {
            $files = File::files($extractPath);
            $processedCount = 0;
            $fileFound = false;

            foreach ($files as $f) {
                $fileName = $f->getFilename();
                if (str_contains(strtoupper($fileName), 'SOGNSTM') && strtolower($f->getExtension()) === 'xml') {
                    $fileFound = true;
                    // Delete existing records of the same file to prevent duplicates
                    DB::table('sss_ssop_stm')->where('stm_file', $fileName)->delete();

                    $xmlContent = File::get($f->getRealPath());
                    $xml = simplexml_load_string($xmlContent);
                    if ($xml === false) {
                        continue;
                    }

                    // Traverse down to TBill
                    if (isset($xml->TBills->ST->HG)) {
                        foreach ($xml->TBills->ST->HG as $hg) {
                            if (isset($hg->TBill)) {
                                foreach ($hg->TBill as $tb) {
                                    $hn = (string)$tb->hn;
                                    $pid = (string)$tb->pid;
                                    $invno = (string)$tb->invno;
                                    $dttran_raw = (string)$tb->dttran; // e.g. "2026-04-11T07:06:36"
                                    $total = (string)$tb->total;
                                    $rid = (string)$tb->rid;

                                    $parsed = $this->parseStmDateTime($dttran_raw);
                                    $dttran = $parsed['datetime'];
                                    $date_only = $parsed['date'];
                                    $time_raw = $parsed['time'];

                                    // Match HOSxP VN
                                    $vn = $this->findHosxpVn($invno, $pid, $date_only, $time_raw);

                                    DB::table('sss_ssop_stm')->insert([
                                        'stm_file' => $fileName,
                                        'vn' => $vn, // HOSxP VN (or null if not found)
                                        'hn' => $hn,
                                        'pid' => $pid,
                                        'invno' => $invno,
                                        'dttran' => $dttran,
                                        'dttran_date' => $date_only,
                                        'dttran_time' => $time_raw,
                                        'total' => is_numeric($total) ? (float)$total : null,
                                        'rid' => $rid,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);
                                    $processedCount++;
                                }
                            }
                        }
                    }
                }
            }

            File::deleteDirectory($extractPath);
            if (!$fileFound) {
                $foundFiles = [];
                foreach ($files as $f) {
                    $foundFiles[] = $f->getFilename();
                }
                $filesStr = !empty($foundFiles) ? ' (พบไฟล์ด้านใน: ' . implode(', ', $foundFiles) . ')' : ' (ไม่พบไฟล์ใดๆ ด้านใน ZIP)';
                return response()->json([
                    'success' => false,
                    'message' => 'เลือกประเภทไฟล์ไม่ถูกต้อง'
                ], 400);
            }
            return response()->json([
                'success' => true,
                'message' => "นำเข้าไฟล์ STM สำเร็จเรียบร้อยแล้ว (ประมวลผลทั้งหมด $processedCount รายการ)"
            ]);

        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล STM: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import Chronic Disease Feedback ZIP
     */
    public function import_chronic(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('zip_file');
        $uniqueId = uniqid('sss_chronic_');
        $extractPath = storage_path('app/tmp_sss_chronic_import/' . $uniqueId);

        try {
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                return response()->json(['success' => false, 'message' => 'ไฟล์ ZIP เสียหาย (ไม่สามารถเปิดไฟล์ได้)'], 400);
            }
            if (!File::exists($extractPath)) {
                File::makeDirectory($extractPath, 0755, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();
        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแตกไฟล์ ZIP: ' . $e->getMessage()], 400);
        }

        try {
            $files = File::files($extractPath);
            $processedCount = 0;
            $fileFound = false;
            $newTpuCount = 0;
            $newDxCount = 0;
            $new_tpu_entries = [];
            $new_dx_entries = [];
            foreach ($files as $f) {
                $fileName = $f->getFilename();
                if (str_contains(strtoupper($fileName), 'SOCDACD') && strtolower($f->getExtension()) === 'txt') {
                    $fileFound = true;
                    DB::table('sss_chronic')->where('rep_file', $fileName)->delete();
                    $contentBytes = File::get($f->getRealPath());
                    
                    $content = @iconv('Windows-874', 'UTF-8//IGNORE', $contentBytes);
                    if ($content === false || ($content === '' && $contentBytes !== '')) {
                        try {
                            $supported = mb_list_encodings();
                            $from_enc = 'auto';
                            if (in_array('Windows-874', $supported)) {
                                $from_enc = 'Windows-874';
                            } elseif (in_array('TIS-620', $supported)) {
                                $from_enc = 'TIS-620';
                            }
                            $content = mb_convert_encoding($contentBytes, 'UTF-8', $from_enc);
                        } catch (\Throwable $e) {
                            $content = $contentBytes;
                        }
                    }

                    $lines = explode("\n", $content);
                    $current_section = null;

                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) {
                            continue;
                        }

                        if (str_contains($line, 'ตอนที่ 1')) {
                            $current_section = '1';
                            continue;
                        } elseif (str_contains($line, 'ตอนที่ 2.1')) {
                            $current_section = '2.1';
                            continue;
                        } elseif (str_contains($line, 'ตอนที่ 2.2')) {
                            $current_section = '2.2';
                            continue;
                        }

                        $parts = str_getcsv($line);
                        if (count($parts) >= 10 && is_numeric(trim($parts[0]))) {
                            $repline = trim($parts[1]);
                            $hcode = trim($parts[2]);
                            $hmain = trim($parts[3]);
                            $invno = trim($parts[4]);
                            $hn = trim($parts[5]);
                            $pid = trim($parts[6]);
                            $dttran = trim($parts[7]);
                            $dx = trim($parts[8]);
                            $drug = trim($parts[9]);

                            if ($current_section !== null) {
                                DB::table('sss_chronic')->insert([
                                    'rep_file' => $fileName,
                                    'repline' => is_numeric($repline) ? (int)$repline : null,
                                    'hcode' => $hcode,
                                    'hmain' => $hmain,
                                    'invno' => $invno,
                                    'hn' => $hn,
                                    'pid' => $pid,
                                    'dttran' => $dttran,
                                    'section_type' => $current_section,
                                    'dx' => $dx,
                                    'drug' => $drug,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                                $processedCount++;

                                if ($current_section === '1') {
                                    if (preg_match('/^(\d+)\s*\(([^)]+)\)/', $drug, $drugMatches)) {
                                        $cat_str = str_pad(ltrim($drugMatches[1], '0'), 2, '0', STR_PAD_LEFT);
                                        $codes = explode(',', $drugMatches[2]);
                                        foreach ($codes as $c) {
                                            $c = trim($c);
                                            if (is_numeric($c)) {
                                                $new_tpu_entries[] = ['cat' => $cat_str, 'tpu' => $c];
                                            }
                                        }
                                    }
                                    if (preg_match('/^(\d+)\s*\(([^)]+)\)/', $dx, $dxMatches)) {
                                        $cat_str = str_pad(ltrim($dxMatches[1], '0'), 2, '0', STR_PAD_LEFT);
                                        $codes = explode(',', $dxMatches[2]);
                                        foreach ($codes as $c) {
                                            $c = trim($c);
                                            if (!empty($c)) {
                                                $new_dx_entries[] = ['cat' => $cat_str, 'dx' => $c];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Learning logic
            if (!empty($new_tpu_entries) || !empty($new_dx_entries)) {
                $tmt_json_path = base_path('docs/lookup/tmt_sss_chronic.json');
                $tmt_data = [];
                if (File::exists($tmt_json_path)) {
                    $tmt_data = json_decode(File::get($tmt_json_path), true);
                }
                $diseases = $tmt_data['diseases'] ?? [];

                foreach ($new_tpu_entries as $entry) {
                    $cat = $entry['cat'];
                    $tpu = $entry['tpu'];
                    foreach ($diseases as &$dis) {
                        if (substr($dis['id'], 0, 2) === $cat) {
                            if (!isset($dis['tpu_codes'])) {
                                $dis['tpu_codes'] = [];
                            }
                            if (!in_array($tpu, $dis['tpu_codes'])) {
                                $dis['tpu_codes'][] = $tpu;
                                $newTpuCount++;
                            }
                        }
                    }
                }
                $tmt_data['diseases'] = $diseases;
                File::put($tmt_json_path, json_encode($tmt_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $ncd_json_path = base_path('docs/lookup/icd10_sss_chronic.json');
                $ncd_data = [];
                if (File::exists($ncd_json_path)) {
                    $ncd_data = json_decode(File::get($ncd_json_path), true);
                }
                $ncd_diseases = $ncd_data['diseases'] ?? [];

                foreach ($new_dx_entries as $entry) {
                    $cat = $entry['cat'];
                    $dx = $entry['dx'];
                    foreach ($ncd_diseases as &$dis) {
                        if (substr($dis['id'], 0, 2) === $cat) {
                            if (!isset($dis['prefixes'])) {
                                $dis['prefixes'] = [];
                            }
                            if (!in_array($dx, $dis['prefixes'])) {
                                $dis['prefixes'][] = $dx;
                                $newDxCount++;
                            }
                        }
                    }
                }
                $ncd_data['diseases'] = $ncd_diseases;
                File::put($ncd_json_path, json_encode($ncd_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            File::deleteDirectory($extractPath);
            if (!$fileFound) {
                $foundFiles = [];
                foreach ($files as $f) {
                    $foundFiles[] = $f->getFilename();
                }
                $filesStr = !empty($foundFiles) ? ' (พบไฟล์ด้านใน: ' . implode(', ', $foundFiles) . ')' : ' (ไม่พบไฟล์ใดๆ ด้านใน ZIP)';
                return response()->json([
                    'success' => false,
                    'message' => 'เลือกประเภทไฟล์ไม่ถูกต้อง'
                ], 400);
            }
            return response()->json([
                'success' => true,
                'message' => "นำเข้าไฟล์โรคเรื้อรังสำเร็จเรียบร้อยแล้ว (นำเข้าทั้งหมด $processedCount รายการ, เรียนรู้รหัส TMT ใหม่ $newTpuCount รายการ, เรียนรู้โรคใหม่ $newDxCount รายการ)"
            ]);

        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import Chronic Disease Registered Patient Database ZIP (ACDCONF excel inside)
     */
    public function import_chronic_register(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('zip_file');
        $uniqueId = uniqid('sss_chronic_reg_');
        $extractPath = storage_path('app/tmp_sss_chronic_reg_import/' . $uniqueId);

        try {
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                return response()->json(['success' => false, 'message' => 'ไฟล์ ZIP เสียหาย (ไม่สามารถเปิดไฟล์ได้)'], 400);
            }
            if (!File::exists($extractPath)) {
                File::makeDirectory($extractPath, 0755, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();
        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแตกไฟล์ ZIP: ' . $e->getMessage()], 400);
        }

        try {
            $files = File::files($extractPath);
            $processedCount = 0;
            $acdconf_file = null;

            foreach ($files as $f) {
                $fileName = $f->getFilename();
                $ext = strtolower($f->getExtension());
                if (str_contains(strtoupper($fileName), 'ACDCONF') && ($ext === 'xlsx' || $ext === 'xls')) {
                    $acdconf_file = $f;
                    break;
                }
            }

            if (!$acdconf_file) {
                $foundFiles = [];
                foreach ($files as $f) {
                    $foundFiles[] = $f->getFilename();
                }
                $filesStr = !empty($foundFiles) ? ' (พบไฟล์ด้านใน: ' . implode(', ', $foundFiles) . ')' : ' (ไม่พบไฟล์ใดๆ ด้านใน ZIP)';
                File::deleteDirectory($extractPath);
                return response()->json([
                    'success' => false,
                    'message' => 'เลือกประเภทไฟล์ไม่ถูกต้อง'
                ], 400);
            }

            $fileName = $acdconf_file->getFilename();
            DB::table('sss_chronic_register')->where('import_file', $fileName)->delete();

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($acdconf_file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $inserts = [];
            $lineCount = 0;
            foreach ($rows as $row) {
                $lineCount++;
                if ($lineCount <= 2) {
                    continue; // Skip header rows
                }

                $cid = isset($row['E']) ? trim($row['E']) : null;
                if (empty($cid) || !is_numeric($cid)) {
                    continue;
                }

                $type = isset($row['B']) ? trim($row['B']) : null;
                $chronic_code = isset($row['C']) ? trim($row['C']) : null;
                $case_id = isset($row['D']) ? trim($row['D']) : null;

                $first_date = null;
                if (!empty($row['F'])) {
                    $rawDate = trim($row['F']);
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                        $first_date = $rawDate;
                    } else {
                        try {
                            $first_date = date('Y-m-d', strtotime($rawDate));
                        } catch (\Throwable $ex) {}
                    }
                }

                $confirm_round = isset($row['O']) ? trim($row['O']) : null;

                $inserts[] = [
                    'import_file' => $fileName,
                    'type' => $type,
                    'chronic_code' => $chronic_code,
                    'case_id' => $case_id,
                    'cid' => $cid,
                    'first_date' => $first_date,
                    'confirm_round' => $confirm_round,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $processedCount++;

                if (count($inserts) >= 100) {
                    DB::table('sss_chronic_register')->insert($inserts);
                    $inserts = [];
                }
            }

            if (!empty($inserts)) {
                DB::table('sss_chronic_register')->insert($inserts);
            }

            File::deleteDirectory($extractPath);
            return response()->json([
                'success' => true,
                'message' => "นำเข้าบัญชีการยืนยันโรคเรื้อรัง (ACDCONF) สำเร็จเรียบร้อยแล้ว (ประมวลผลทั้งหมด $processedCount รายการ)"
            ]);

        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unified list helper to fetch feedback tables data
     */
    public function get_feedback_list(Request $request)
    {
        $type = $request->type;

        if ($type === 'rep') {
            // Fetch rejected REP claims
            $data = DB::table('sss_ssop_rep')
                ->whereNotNull('error_codes')
                ->where('error_codes', '!=', '')
                ->orderByDesc('id')
                ->limit(500)
                ->get();
            return response()->json(['success' => true, 'data' => $data]);

        } elseif ($type === 'stm') {
            // Fetch STM statement payments
            $data = DB::table('sss_ssop_stm')
                ->orderByDesc('id')
                ->limit(500)
                ->get();
            return response()->json(['success' => true, 'data' => $data]);

        } elseif ($type === 'chronic_drugs') {
            // Load Approved TMT codes from JSON
            $tmt_json_path = base_path('docs/lookup/tmt_sss_chronic.json');
            $tmt_data = [];
            if (\File::exists($tmt_json_path)) {
                $tmt_data = json_decode(\File::get($tmt_json_path), true);
            }
            $diseases = $tmt_data['diseases'] ?? [];

            // Collect all unique TMT codes
            $all_tmt_codes = [];
            foreach ($diseases as $dis) {
                if (!empty($dis['tpu_codes'])) {
                    foreach ($dis['tpu_codes'] as $code) {
                        $all_tmt_codes[] = trim($code);
                    }
                }
            }
            $all_tmt_codes = array_values(array_unique(array_filter($all_tmt_codes)));

            // Query drug names from HOSxP for these TMT codes
            $drug_names = [];
            if (!empty($all_tmt_codes)) {
                try {
                    $drugs = DB::connection('hosxp')->table('drugitems as d')
                        ->leftJoin('drugitems_ref_code as d3', function($join) {
                            $join->on('d3.icode', '=', 'd.icode')
                                 ->where('d3.drugitems_ref_code_type_id', '=', 3);
                        })
                        ->select('d.name', DB::raw('COALESCE(d3.ref_code, d.sks_drug_code) as tmtid'))
                        ->where(function($query) use ($all_tmt_codes) {
                            $query->whereIn('d3.ref_code', $all_tmt_codes)
                                  ->orWhereIn('d.sks_drug_code', $all_tmt_codes);
                        })
                        ->get();

                    foreach ($drugs as $dg) {
                        if (!empty($dg->tmtid)) {
                            $tmt_clean = trim($dg->tmtid);
                            if (!isset($drug_names[$tmt_clean])) {
                                $drug_names[$tmt_clean] = [];
                            }
                            $drug_names[$tmt_clean][] = $dg->name;
                        }
                    }
                } catch (\Throwable $e) {
                    try {
                        $drugs = DB::table('s_drugitems')
                            ->select('name', 'sks_drug_code as tmtid')
                            ->whereIn('sks_drug_code', $all_tmt_codes)
                            ->get();
                        foreach ($drugs as $dg) {
                            if (!empty($dg->tmtid)) {
                                $tmt_clean = trim($dg->tmtid);
                                if (!isset($drug_names[$tmt_clean])) {
                                    $drug_names[$tmt_clean] = [];
                                }
                                $drug_names[$tmt_clean][] = $dg->name;
                            }
                        }
                    } catch (\Throwable $ex) {}
                }
            }

            // Build output rows
            $rows = [];
            foreach ($diseases as $dis) {
                $disease_id = $dis['id'];
                $disease_name = $dis['name'];
                if (!empty($dis['tpu_codes'])) {
                    foreach ($dis['tpu_codes'] as $code) {
                        $code = trim($code);
                        $names = isset($drug_names[$code]) ? implode(', ', array_unique($drug_names[$code])) : '<span class="text-muted italic">ไม่มีการเชื่อมโยงกับยาในโรงพยาบาล</span>';
                        $rows[] = [
                            'disease_id' => $disease_id,
                            'disease_name' => $disease_name,
                            'tmt_code' => $code,
                            'drug_names' => $names
                        ];
                    }
                } else {
                    $rows[] = [
                        'disease_id' => $disease_id,
                        'disease_name' => $disease_name,
                        'tmt_code' => '-',
                        'drug_names' => '<span class="text-muted italic">ยังไม่มีการลงทะเบียนรหัส TMT สำหรับกลุ่มโรคนี้</span>'
                    ];
                }
            }

            return response()->json(['success' => true, 'data' => $rows]);

        } else {
            // Default: Fetch chronic feedback list21 and list22 for the chronic feedback modal
            $list21 = DB::table('sss_chronic')
                ->where('section_type', '2.1')
                ->orderByDesc('dttran')
                ->limit(300)
                ->get();

            $list22 = DB::table('sss_chronic')
                ->where('section_type', '2.2')
                ->orderByDesc('dttran')
                ->limit(300)
                ->get();

            // Filter out from list22 any patient who is already in list21 (by PID) or already registered in sss_chronic_register
            $pidsIn21 = $list21->pluck('pid')->filter()->unique()->toArray();
            $filteredList22 = [];
            foreach ($list22 as $row) {
                if (!empty($row->pid) && in_array($row->pid, $pidsIn21)) {
                    continue;
                }
                
                if (!empty($row->pid)) {
                    // Check if already registered in the chronic registry (at all)
                    $is_registered = DB::table('sss_chronic_register')
                        ->where('cid', $row->pid)
                        ->exists();
                    if ($is_registered) {
                        continue; // Skip since registered
                    }
                }
                
                $filteredList22[] = $row;
            }
            $list22 = collect($filteredList22);

            $hns = $list21->pluck('hn')->merge($list22->pluck('hn'))->unique()->filter()->toArray();

            $patients = [];
            if (!empty($hns)) {
                try {
                    $patients = DB::connection('hosxp')->table('patient')
                        ->select('hn', DB::raw("CONCAT(pname, fname, ' ', lname) AS ptname"))
                        ->whereIn('hn', $hns)
                        ->get()
                        ->keyBy('hn')
                        ->toArray();
                } catch (\Throwable $e) {
                }
            }

            foreach ($list21 as $row) {
                $row->ptname = isset($patients[$row->hn]) ? $patients[$row->hn]->ptname : '-';
            }
            foreach ($list22 as $row) {
                $row->ptname = isset($patients[$row->hn]) ? $patients[$row->hn]->ptname : '-';
            }

            return response()->json([
                'success' => true,
                'list21' => $list21,
                'list22' => $list22,
                'feedback_21' => $list21,
                'feedback_22' => $list22
            ]);
        }
    }
}
