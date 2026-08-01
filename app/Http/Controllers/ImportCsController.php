<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ImportCsController extends Controller
{
    /**
     * Import CSOP REP ZIP File
     */
    public function import_rep_csop(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'zip_file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('zip_file');
        $uniqueId = uniqid('csop_rep_');
        $extractPath = storage_path('app/tmp_csop_rep/' . $uniqueId);

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
            $files = File::allFiles($extractPath);
            $processedCount = 0;
            $fileFound = false;

            foreach ($files as $f) {
                $fileName = $f->getFilename();
                $ext = strtolower($f->getExtension());

                if (str_contains(strtoupper($fileName), 'COCDBIL') && in_array($ext, ['xml', 'bil', 'rep', 'txt'])) {
                    $fileFound = true;
                    // Delete existing records of the same file to prevent duplicates
                    DB::table('rep_csop')->where('rep_file', $fileName)->delete();

                    $rawBytes = File::get($f->getRealPath());

                    $rawContent = @iconv('Windows-874', 'UTF-8//IGNORE', $rawBytes);
                    if ($rawContent === false) {
                        $rawContent = mb_convert_encoding($rawBytes, 'UTF-8', 'TIS-620');
                    }

                    $lines = explode("\n", $rawContent);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        // Check for line header starting with *|
                        if (str_starts_with($line, '*|')) {
                            $raw_data = trim(substr($line, 2));
                            $parts = explode('|', $raw_data);
                            if (count($parts) >= 1) {
                                $detail_cols = explode(',', $parts[0]);
                                $error_codes = isset($parts[1]) ? trim($parts[1]) : '';

                                if (count($detail_cols) >= 9) {
                                    $claim_type = trim($detail_cols[0]);
                                    $repline = trim($detail_cols[1]);
                                    $dttran_raw = trim($detail_cols[3]); // E.g. "11/10/2568 10:08:43"
                                    $invno = trim(str_replace('_', '', $detail_cols[4]));
                                    $hn = trim($detail_cols[6]);
                                    $amount = trim(str_replace('|', '', $detail_cols[8]));

                                    // Parse date and time
                                    $dttran = null;
                                    $time_raw = '';
                                    if (!empty($dttran_raw)) {
                                        $dt_parts = explode(' ', trim($dttran_raw));
                                        if (isset($dt_parts[0])) {
                                            $dateStr = $dt_parts[0]; // "11/10/2568"
                                            $date_parts = explode('/', $dateStr);
                                            if (count($date_parts) === 3) {
                                                $d = (int)$date_parts[0];
                                                $m = (int)$date_parts[1];
                                                $y = (int)$date_parts[2] - 543; // Buddhist to Gregorian year
                                                $dttran = sprintf('%04d-%02d-%02d', $y, $m, $d);
                                            }
                                        }
                                        if (isset($dt_parts[1])) {
                                            $time_raw = trim($dt_parts[1]);
                                        }
                                    }

                                    // Try to match visit/vn in HOSxP using HN and Date/Time
                                    $vn = null;
                                    if ($hn && $dttran) {
                                        $vn = DB::connection('hosxp')
                                            ->table('ovst')
                                            ->where('hn', $hn)
                                            ->where('vstdate', $dttran)
                                            ->value('vn');
                                    }

                                    DB::table('rep_csop')->insert([
                                        'rep_file' => $fileName,
                                        'repline' => is_numeric($repline) ? (int)$repline : null,
                                        'vn' => $vn,
                                        'hn' => $hn,
                                        'invno' => $invno,
                                        'dttran' => $dttran ? ($dttran . ' ' . ($time_raw ?: '00:00:00')) : null,
                                        'dttran_date' => $dttran,
                                        'dttran_time' => !empty($time_raw) ? $time_raw : null,
                                        'claim_type' => $claim_type,
                                        'amount' => is_numeric($amount) ? (float)$amount : null,
                                        'error_codes' => $error_codes,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                    $processedCount++;
                                }
                            }
                        }
                    }
                }
            }

            // Cleanup
            File::deleteDirectory($extractPath);

            if (!$fileFound) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบไฟล์ COCDBIL ด้านใน ZIP'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "นำเข้าข้อมูลสำเร็จ ประมวลผลสำเร็จ {$processedCount} รายการ"
            ]);

        } catch (\Throwable $e) {
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }
}
