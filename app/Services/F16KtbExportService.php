<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use App\Services\F16EclaimExportService;

class F16KtbExportService
{
    /**
     * ดึงและสร้างข้อมูล 16 แฟ้มสำหรับ KTB Health Platform
     * อ้างอิงโครงสร้างมาตรฐานสากล 16 แฟ้มเดียวกับ F16EclaimExportService
     * พร้อมตรวจสอบและจัดกลุ่มข้อมูลให้ครบถ้วนถูกต้องตามสเปก KTB / สปสช.
     */
    public function generate16Files(array $vns, string $activityCode = 'S01'): array
    {
        if (empty($vns)) {
            return [];
        }

        // 1. ดึงข้อมูล 16 แฟ้มหลักจาก Engine F16EclaimExportService
        $eclaimData = F16EclaimExportService::generate16Files($vns);
        $rawFiles = $eclaimData['files'] ?? [];

        // 2. ดึงข้อมูลสัญญาณชีพ (Vitals) และข้อมูลคัดกรอง/ANC สำหรับเติมใน STATUS1 / GRAVIDA
        $vitalsMap = $this->getVitalsMap($vns);
        $ancMap = $this->getAncMap($vns);

        $allFileKeys = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'IPD', 'IRF', 'IDX', 'IOP', 'CHT', 'CHA', 'AER', 'ADP', 'LVD', 'DRU', 'LABFU'];
        $result = [];

        foreach ($allFileKeys as $key) {
            $content = $rawFiles[$key] ?? '';
            $rows = [];
            if (!empty($content)) {
                $lines = explode("\n", str_replace("\r", "", trim($content)));
                if (count($lines) > 0) {
                    $headers = explode('|', array_shift($lines));
                    foreach ($lines as $line) {
                        if (trim($line) === '') continue;
                        $cols = explode('|', $line);
                        $rowObj = [];
                        foreach ($headers as $idx => $h) {
                            $rowObj[$h] = $cols[$idx] ?? '';
                        }
                        $rows[] = $rowObj;
                    }
                }
            }

            // จัดกลุ่มและเติมข้อมูลเฉพาะของแฟ้ม ADP
            if ($key === 'ADP' && !empty($rows)) {
                $rows = $this->enrichAndGroupAdpRows($rows, $vitalsMap, $ancMap);
            }

            $result[$key] = $rows;
        }

        return $result;
    }

    /**
     * ดึงข้อมูลสัญญาณชีพจาก opdscreen
     */
    protected function getVitalsMap(array $vns): array
    {
        try {
            $placeholders = implode(',', array_fill(0, count($vns), '?'));
            $rows = DB::connection('hosxp')->select("
                SELECT os.vn, os.bps, os.bpd, os.bw, os.height, os.bmi, os.waist, os.pulse, os.rr
                FROM opdscreen os
                WHERE os.vn IN ($placeholders)
            ", $vns);

            $map = [];
            foreach ($rows as $r) {
                $parts = [];
                if (!empty($r->bps)) $parts[] = 'SBP:' . intval($r->bps);
                if (!empty($r->bpd)) $parts[] = 'DBP:' . intval($r->bpd);
                if (!empty($r->bw) && floatval($r->bw) > 0) $parts[] = 'BW:' . number_format((float)$r->bw, 1, '.', '');
                if (!empty($r->height) && floatval($r->height) > 0) $parts[] = 'HT:' . intval($r->height);
                if (!empty($r->bmi) && floatval($r->bmi) > 0) $parts[] = 'BMI:' . number_format((float)$r->bmi, 1, '.', '');
                if (!empty($r->waist) && floatval($r->waist) > 0) $parts[] = 'WAIST:' . intval($r->waist);
                $map[$r->vn] = implode('|', $parts);
            }
            return $map;
        } catch (\Throwable $e) {
            Log::warning("F16KtbExportService vitals query: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงข้อมูลการฝากครรภ์ (Gravida / GA / LMP)
     */
    protected function getAncMap(array $vns): array
    {
        try {
            $placeholders = implode(',', array_fill(0, count($vns), '?'));
            $rows = DB::connection('hosxp')->select("
                SELECT a.vn, a.pa_week as ga_week, a.anc_no as gravida, DATE_FORMAT(a.lmp, '%Y%m%d') as lmp
                FROM anc_service a
                WHERE a.vn IN ($placeholders)
            ", $vns);

            $map = [];
            foreach ($rows as $r) {
                $map[$r->vn] = [
                    'gravida' => $r->gravida ?? '',
                    'ga_week' => $r->ga_week ?? '',
                    'lmp'     => $r->lmp ?? '',
                ];
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * รวมยอดและกรองข้อมูลแฟ้ม ADP พร้อมเติมค่า STATUS1 และ GRAVIDA
     */
    protected function enrichAndGroupAdpRows(array $rows, array $vitalsMap, array $ancMap): array
    {
        $grouped = [];
        foreach ($rows as $r) {
            $code = trim((string)($r['CODE'] ?? ''));
            // ตัดรายการที่ไม่มีรหัส ADP ออก
            if ($code === '' || $code === 'XXXXXX') {
                continue;
            }

            $seq  = trim((string)($r['SEQ'] ?? ''));
            $type = trim((string)($r['TYPE'] ?? ''));
            $rate = trim((string)($r['RATE'] ?? '0'));
            $gKey = "{$seq}_{$type}_{$code}_{$rate}";

            // เติม STATUS1 ถ้ายังว่างอยู่
            if (empty($r['STATUS1']) && isset($vitalsMap[$seq])) {
                $r['STATUS1'] = $vitalsMap[$seq];
            }

            // เติม GRAVIDA, GA_WEEK, LMP
            if (isset($ancMap[$seq])) {
                if (empty($r['GRAVIDA'])) $r['GRAVIDA'] = (string)($ancMap[$seq]['gravida'] ?? '');
                if (empty($r['GA_WEEK'])) $r['GA_WEEK'] = (string)($ancMap[$seq]['ga_week'] ?? '');
                if (empty($r['LMP'])) $r['LMP'] = (string)($ancMap[$seq]['lmp'] ?? '');
            }

            if (!isset($grouped[$gKey])) {
                $grouped[$gKey] = $r;
                $grouped[$gKey]['QTY']      = (float)($r['QTY'] ?? 1);
                $grouped[$gKey]['TOTAL']    = (float)($r['TOTAL'] ?? 0);
                $grouped[$gKey]['TOTCOPAY'] = (float)($r['TOTCOPAY'] ?? 0);
            } else {
                $grouped[$gKey]['QTY']      += (float)($r['QTY'] ?? 1);
                $grouped[$gKey]['TOTAL']    += (float)($r['TOTAL'] ?? 0);
                $grouped[$gKey]['TOTCOPAY'] += (float)($r['TOTCOPAY'] ?? 0);
            }
        }

        $final = [];
        foreach ($grouped as $item) {
            $item['QTY']      = (string)intval($item['QTY']);
            $item['TOTAL']    = number_format((float)$item['TOTAL'], 2, '.', '');
            $item['TOTCOPAY'] = number_format((float)$item['TOTCOPAY'], 2, '.', '');
            $final[] = $item;
        }

        return $final;
    }

    /**
     * ส่งออกไฟล์เป็น Zip Archive ตามรูปแบบมาตรฐาน 16 แฟ้ม
     */
    public function exportZip(array $vns, string $activityCode = 'S01'): array
    {
        $filesData = $this->generate16Files($vns, $activityCode);

        $tempDir = storage_path('app/ktb_temp_' . uniqid());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $allFileKeys = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'IPD', 'IRF', 'IDX', 'IOP', 'CHT', 'CHA', 'AER', 'ADP', 'LVD', 'DRU', 'LABFU'];

        foreach ($allFileKeys as $key) {
            $filePath = $tempDir . '/' . $key . '.txt';
            $rows = $filesData[$key] ?? [];
            $content = '';

            if (!empty($rows)) {
                $headers = array_keys($rows[0]);
                $content .= implode('|', $headers) . "\r\n";
                foreach ($rows as $row) {
                    $content .= implode('|', array_values($row)) . "\r\n";
                }
            }

            file_put_contents($filePath, iconv('UTF-8', 'TIS-620//IGNORE', $content));
        }

        $zipFileName = 'F16_KTB_' . $activityCode . '_' . date('Ymd_His') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($allFileKeys as $key) {
                $txtFile = $tempDir . '/' . $key . '.txt';
                if (file_exists($txtFile)) {
                    $zip->addFile($txtFile, $key . '.txt');
                }
            }
            $zip->close();
        }

        // Clean up temp text files
        foreach ($allFileKeys as $key) {
            @unlink($tempDir . '/' . $key . '.txt');
        }
        @rmdir($tempDir);

        return [
            'success' => true,
            'zip_file_name' => $zipFileName,
            'zip_path' => $zipPath,
            'download_url' => url('ktb/download_zip/' . $zipFileName),
            'files_count' => array_map(fn($f) => count($f), $filesData),
        ];
    }
}
