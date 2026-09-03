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

        // 2. ดึงข้อมูลการฝากครรภ์/ANC สำหรับเติมใน GRAVIDA / GA_WEEK / LMP
        $ancMap = $this->getAncMap($vns);

        // KTB Health Platform รองรับและใช้งานเฉพาะ 6 แฟ้มหลัก: INS, PAT, OPD, ODX, ADP, DRU
        $allFileKeys = ['INS', 'PAT', 'OPD', 'ODX', 'ADP', 'DRU'];
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
            if ($key === 'ADP') {
                $rows = $this->enrichAndGroupAdpRows($rows, $ancMap, $activityCode, $vns);
            }

            $result[$key] = $rows;
        }

        return $result;
    }

    /**
     * ดึงข้อมูล Preview พร้อม Raw Files, Headers, Tables สำหรับ Modal
     */
    public function getPreviewData(array $vns, string $activityCode = 'S01'): array
    {
        $filesData = $this->generate16Files($vns, $activityCode);
        $allFileKeys = ['INS', 'PAT', 'OPD', 'ODX', 'ADP', 'DRU'];

        $defaultHeaders = [
            'INS' => ['HN', 'INSCL', 'SUBTYPE', 'CID', 'HCODE', 'DATEEXP', 'HOSPMAIN', 'HOSPSUB', 'GOVCODE', 'GOVNAME', 'PERMITNO', 'DOCNO', 'OWNRPID', 'OWNNAME', 'AN', 'SEQ', 'SUBINSCL', 'RELINSCL', 'HTYPE'],
            'PAT' => ['HCODE', 'HN', 'CHANGWAT', 'AMPHUR', 'DOB', 'SEX', 'MARRIAGE', 'OCCUPA', 'NATION', 'PERSON_ID', 'NAMEPAT', 'TITLE', 'FNAME', 'LNAME', 'IDTYPE'],
            'OPD' => ['HN', 'CLINIC', 'DATEOPD', 'TIMEOPD', 'SEQ', 'UUC', 'DETAIL', 'BTEMP', 'SBP', 'DBP', 'PR', 'RR', 'OPTYPE', 'TYPEIN', 'TYPEOUT'],
            'ODX' => ['HN', 'DATEDX', 'CLINIC', 'DIAG', 'DXTYPE', 'DRDX', 'PERSON_ID', 'SEQ'],
            'ADP' => ['HN', 'AN', 'DATEOPD', 'TYPE', 'CODE', 'QTY', 'RATE', 'SEQ', 'CAGCODE', 'DOSE', 'CA_TYPE', 'SERIALNO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL', 'QTYDAY', 'TMLTCODE', 'STATUS1', 'BI', 'CLINIC', 'ITEMSRC', 'PROVIDER', 'GRAVIDA', 'GA_WEEK', 'DCIP/E_SCREEN', 'LMP', 'SP_ITEM'],
            'DRU' => ['HCODE', 'HN', 'AN', 'CLINIC', 'PERSON_ID', 'DATE_SERV', 'DID', 'DIDNAME', 'AMOUNT', 'DRUGPRICE', 'DRUGCOST', 'DIDSTD', 'UNIT', 'UNIT_PACK', 'SEQ', 'DRUGREMARK', 'PA_NO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL', 'SIGCODE', 'SIGTEXT', 'PROVIDER', 'SP_ITEM'],
        ];

        $rawFiles = [];
        $tables = [];
        $headers = [];
        $counts = [];

        foreach ($allFileKeys as $key) {
            $rows = $filesData[$key] ?? [];
            $counts[$key] = count($rows);

            if (!empty($rows)) {
                $fileHeaders = array_keys($rows[0]);
                $headers[$key] = $fileHeaders;
                $tableRows = [];
                $rawText = implode('|', $fileHeaders) . "\r\n";
                foreach ($rows as $row) {
                    $tableRows[] = array_values($row);
                    $rawText .= implode('|', array_values($row)) . "\r\n";
                }
                $tables[$key] = $tableRows;
                $rawFiles[$key] = $rawText;
            } else {
                $fileHeaders = $defaultHeaders[$key] ?? [];
                $headers[$key] = $fileHeaders;
                $tables[$key] = [];
                $rawFiles[$key] = implode('|', $fileHeaders) . "\r\n";
            }
        }

        $fileNames = $this->getKtbFileNames($vns);

        return [
            'files' => $filesData,
            'file_names' => $fileNames,
            'raw_files' => $rawFiles,
            'tables' => $tables,
            'headers' => $headers,
            'counts' => $counts,
            'subfolder_name' => 'F16_KTB_' . strtoupper($activityCode) . '_' . date('Ymd_His')
        ];
    }

    /**
     * สร้างชื่อไฟล์ตามมาตรฐาน 16 แฟ้ม KTB / สปสช. [รหัสแฟ้มข้อมูล + YY + MM + xxxx.txt]
     * เช่น INS69090001.txt, PAT69090001.txt, OPD69090001.txt, ODX69090001.txt, ADP69090001.txt, DRU69090001.txt
     */
    public function getKtbFileNames(array $vns): array
    {
        $vstdate = null;
        if (!empty($vns)) {
            try {
                $firstVn = $vns[0];
                $vRow = DB::connection('hosxp')->table('vn_stat')->where('vn', $firstVn)->value('vstdate');
                if ($vRow) {
                    $vstdate = $vRow;
                }
            } catch (\Throwable $e) {}
        }

        if ($vstdate) {
            $ts = strtotime($vstdate);
            $year = (int)date('Y', $ts) + 543;
            $yy = substr((string)$year, -2);
            $mm = date('m', $ts);
        } else {
            $year = (int)date('Y') + 543;
            $yy = substr((string)$year, -2);
            $mm = date('m');
        }

        $suffix = $yy . $mm . '0001.txt';

        $allFileKeys = ['INS', 'PAT', 'OPD', 'ODX', 'ADP', 'DRU'];
        $names = [];
        foreach ($allFileKeys as $k) {
            $names[$k] = $k . $suffix;
        }

        return $names;
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
     * รวมยอด กรอง และจับคู่รหัสกิจกรรม KTB สำหรับแฟ้ม ADP
     * กรองเฉพาะรหัสกิจกรรมที่สอดคล้องกับข้อกำหนด KTB Health Platform (Section 4)
     * และตัดรายการที่ไม่ใช่กิจกรรม PP (เช่น Type 15, Type 17, 12003, 12004) ออกเพื่อป้องกัน Error 300
     */
    protected function enrichAndGroupAdpRows(array $rows, array $ancMap, string $activityCode = 'S01', array $vns = []): array
    {
        $screenData = [];
        $visitData = [];
        if (!empty($vns)) {
            try {
                $screenData = DB::connection('hosxp')->table('opdscreen')
                    ->whereIn('vn', $vns)
                    ->get()
                    ->keyBy('vn');
                $visitData = DB::connection('hosxp')->table('ovst')
                    ->whereIn('vn', $vns)
                    ->get(['vn', 'hn', 'vstdate'])
                    ->keyBy('vn');
            } catch (\Throwable $e) {
                Log::warning("F16KtbExportService enrichAndGroupAdpRows query warning: " . $e->getMessage());
            }
        }

        // รายการรหัส ADP ของ KTB ที่ได้รับอนุญาตตาม Section 4
        $allowedKtbCodes = [
            '12001', '12002_0', '12002_1', '90005', '30014', '30011', '30012', '30013',
            '36003', '30008', '30009', 'AB001', 'AB002', 'AB003', '30016', '2206', '2207',
            '90004', '1B004P', '1B004N', '1B004_0P', '1B004_0N', '1B005', '0320277_0', '0320277_1',
            '1B0046_0', '1B0046_01', '1B0046_1', '1B0046_11', '1B0046_2', '1B0046_21'
        ];

        $grouped = [];
        $seqWithValidAdp = [];

        foreach ($rows as $r) {
            $code = trim((string)($r['CODE'] ?? ''));
            $seq  = trim((string)($r['SEQ'] ?? ''));
            $type = trim((string)($r['TYPE'] ?? ''));

            // ตัดรายการที่ไม่มีรหัส ADP หรือค่าว่างออก
            if ($code === '' || $code === 'XXXXXX') {
                continue;
            }

            // จัดการแปลงรหัส 12002 (S02) อัตโนมัติ: 12002 -> 12002_0 หรือ 12002_1
            if ($code === '12002' || strpos($code, '12002') === 0) {
                $fbs = (float)($screenData[$seq]->fbs ?? 0);
                $riskdm = strtoupper(trim((string)($screenData[$seq]->riskdm ?? '')));
                $isRisk = ($fbs >= 100) || in_array($riskdm, ['Y', '1', 'RISK']);
                $code = $isRisk ? '12002_1' : '12002_0';
                $r['CODE'] = $code;
                $r['TYPE'] = '4';
                $type = '4';
            }

            // ถ้าส่งออก S01 แต่ไม่ใช่รหัส 12001 (เช่น 12003, 12004, Type 15, Type 17) ให้ตัดออก
            if ($activityCode === 'S01' && $code !== '12001') {
                continue;
            }

            // ถ้าส่งออก S02 แต่ไม่ใช่รหัส 12002_0 หรือ 12002_1 ให้ตัดออก
            if ($activityCode === 'S02' && !in_array($code, ['12002_0', '12002_1'])) {
                continue;
            }

            // ถ้าไม่ใช่รหัสกิจกรรมของ KTB ที่อนุญาต ให้ตัดออก
            if (!in_array($code, $allowedKtbCodes)) {
                continue;
            }

            $rate = trim((string)($r['RATE'] ?? '0'));
            $gKey = "{$seq}_{$type}_{$code}_{$rate}";

            // เติม GRAVIDA, GA_WEEK, LMP ถ้ามีข้อมูลจาก ANC
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

            $seqWithValidAdp[$seq] = true;
        }

        // ตรวจสอบความครบถ้วน: หากผู้ป่วยรายใดไม่มีรหัสกิจกรรม KTB ในแฟ้ม ADP ให้สร้างแถวกิจกรรมหลักให้อัตโนมัติ
        foreach ($vns as $vn) {
            $vnStr = (string)$vn;
            if (empty($seqWithValidAdp[$vnStr]) && isset($visitData[$vnStr])) {
                $v = $visitData[$vnStr];
                $rawDate = str_replace('-', '', substr($v->vstdate ?? '', 0, 10));
                
                $actCode = '12001';
                $actType = '4';
                if ($activityCode === 'S02') {
                    $fbs = (float)($screenData[$vnStr]->fbs ?? 0);
                    $riskdm = strtoupper(trim((string)($screenData[$vnStr]->riskdm ?? '')));
                    $isRisk = ($fbs >= 100) || in_array($riskdm, ['Y', '1', 'RISK']);
                    $actCode = $isRisk ? '12002_1' : '12002_0';
                } elseif ($activityCode === 'B39') {
                    $actCode = '90005';
                }

                $gKey = "{$vnStr}_{$actType}_{$actCode}_0";
                $grouped[$gKey] = [
                    'HN' => (string)($v->hn ?? ''),
                    'AN' => '',
                    'DATEOPD' => $rawDate,
                    'TYPE' => $actType,
                    'CODE' => $actCode,
                    'QTY' => 1,
                    'RATE' => '0',
                    'SEQ' => $vnStr,
                    'CAGCODE' => '',
                    'DOSE' => '',
                    'CA_TYPE' => '',
                    'SERIALNO' => '',
                    'TOTCOPAY' => 0,
                    'USE_STATUS' => '',
                    'TOTAL' => 0,
                    'QTYDAY' => '',
                    'TMLTCODE' => '',
                    'STATUS1' => '',
                    'BI' => '',
                    'CLINIC' => '',
                    'ITEMSRC' => '',
                    'PROVIDER' => '',
                    'GRAVIDA' => (string)($ancMap[$vnStr]['gravida'] ?? ''),
                    'GA_WEEK' => (string)($ancMap[$vnStr]['ga_week'] ?? ''),
                    'DCIP/E_SCREEN' => '',
                    'LMP' => (string)($ancMap[$vnStr]['lmp'] ?? ''),
                    'SP_ITEM' => ''
                ];
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

        // KTB Health Platform รองรับเฉพาะ 6 แฟ้ม: INS, PAT, OPD, ODX, ADP, DRU
        $defaultHeaders = [
            'INS' => ['HN', 'INSCL', 'SUBTYPE', 'CID', 'HCODE', 'DATEEXP', 'HOSPMAIN', 'HOSPSUB', 'GOVCODE', 'GOVNAME', 'PERMITNO', 'DOCNO', 'OWNRPID', 'OWNNAME', 'AN', 'SEQ', 'SUBINSCL', 'RELINSCL', 'HTYPE'],
            'PAT' => ['HCODE', 'HN', 'CHANGWAT', 'AMPHUR', 'DOB', 'SEX', 'MARRIAGE', 'OCCUPA', 'NATION', 'PERSON_ID', 'NAMEPAT', 'TITLE', 'FNAME', 'LNAME', 'IDTYPE'],
            'OPD' => ['HN', 'CLINIC', 'DATEOPD', 'TIMEOPD', 'SEQ', 'UUC', 'DETAIL', 'BTEMP', 'SBP', 'DBP', 'PR', 'RR', 'OPTYPE', 'TYPEIN', 'TYPEOUT'],
            'ODX' => ['HN', 'DATEDX', 'CLINIC', 'DIAG', 'DXTYPE', 'DRDX', 'PERSON_ID', 'SEQ'],
            'ADP' => ['HN', 'AN', 'DATEOPD', 'TYPE', 'CODE', 'QTY', 'RATE', 'SEQ', 'CAGCODE', 'DOSE', 'CA_TYPE', 'SERIALNO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL', 'QTYDAY', 'TMLTCODE', 'STATUS1', 'BI', 'CLINIC', 'ITEMSRC', 'PROVIDER', 'GRAVIDA', 'GA_WEEK', 'DCIP/E_SCREEN', 'LMP', 'SP_ITEM'],
            'DRU' => ['HCODE', 'HN', 'AN', 'CLINIC', 'PERSON_ID', 'DATE_SERV', 'DID', 'DIDNAME', 'AMOUNT', 'DRUGPRICE', 'DRUGCOST', 'DIDSTD', 'UNIT', 'UNIT_PACK', 'SEQ', 'DRUGREMARK', 'PA_NO', 'TOTCOPAY', 'USE_STATUS', 'TOTAL', 'SIGCODE', 'SIGTEXT', 'PROVIDER', 'SP_ITEM'],
        ];

        $allFileKeys = ['INS', 'PAT', 'OPD', 'ODX', 'ADP', 'DRU'];
        $fileNames = $this->getKtbFileNames($vns);

        foreach ($allFileKeys as $key) {
            $realFileName = $fileNames[$key] ?? ($key . '.txt');
            $filePath = $tempDir . '/' . $realFileName;
            $rows = $filesData[$key] ?? [];
            $content = '';

            if (!empty($rows)) {
                $headers = array_keys($rows[0]);
                $content .= implode('|', $headers) . "\r\n";
                foreach ($rows as $row) {
                    $content .= implode('|', array_values($row)) . "\r\n";
                }
            } else {
                $headers = $defaultHeaders[$key] ?? [];
                $content .= implode('|', $headers) . "\r\n";
            }

            file_put_contents($filePath, $content);
        }

        $zipFileName = 'F16_KTB_' . $activityCode . '_' . date('Ymd_His') . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($allFileKeys as $key) {
                $realFileName = $fileNames[$key] ?? ($key . '.txt');
                $txtFile = $tempDir . '/' . $realFileName;
                if (file_exists($txtFile)) {
                    $zip->addFile($txtFile, $realFileName);
                }
            }
            $zip->close();
        }

        // Clean up temp text files
        foreach ($allFileKeys as $key) {
            $realFileName = $fileNames[$key] ?? ($key . '.txt');
            @unlink($tempDir . '/' . $realFileName);
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
