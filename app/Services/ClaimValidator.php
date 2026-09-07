<?php

namespace App\Services;

/**
 * ClaimValidator — ตรวจสอบเงื่อนไขการเคลม
 *
 * มี 2 function หลักที่เรียกแยกกันได้:
 *   validatePpfs($visit, $items)  → ตรวจ PPFS rules
 *   validateInsUcs($items)        → ตรวจ Instrument อยู่ในประกาศ UCS ไหม
 *
 * validate($visit, $items) → รันทั้งสองรวมกัน (ใช้เหมือนเดิม)
 */
class ClaimValidator
{
    protected $ppfsRules;

    public function __construct()
    {
        $this->ppfsRules = file_exists(config_path('claims/ppfs_rules.php'))
            ? require config_path('claims/ppfs_rules.php')
            : [];
    }

    // =========================================================================
    // validate() — รัน PPFS + InsUcs รวมกัน (backward compatible)
    // =========================================================================

    /**
     * @param  object $visit
     * @param  array  $billedItems  ต้องมี ins_ucs ติดมาแล้ว (inject โดย Controller)
     * @return array  ['is_valid', 'endpoint_valid', 'errors', 'warnings']
     */
    /**
     * Core configurable validator supporting aspect-based checks.
     *
     * @param  object $visit
     * @param  array  $billedItems
     * @param  array  $aspects
     * @return array  ['is_valid', 'endpoint_valid', 'errors', 'warnings']
     */
    public function validate($visit, $billedItems, array $aspects = []): array
    {
        // Default to standard aspects if none specified
        if (empty($aspects)) {
            $aspects = ['f16_required', 'ppfs', 'ins_ucs', 'endpoint'];
        }

        $errors   = [];
        $warnings = [];
        $endpointOk = true;

        // 0. Core e-Claim 16-File Required Fields (CID, PDX, etc.)
        if (in_array('f16_required', $aspects)) {
            $req = $this->validateF16Required($visit, (array) $billedItems);
            $errors   = array_merge($errors, $req['errors']);
            $warnings = array_merge($warnings, $req['warnings']);
        }

        // 1. PPFS validation
        if (in_array('ppfs', $aspects)) {
            $ppfs = $this->validatePpfs($visit, (array) $billedItems);
            $errors   = array_merge($errors, $ppfs['errors']);
            $warnings = array_merge($warnings, $ppfs['warnings']);
        }

        // 2. Instrument UCS validation
        if (in_array('ins_ucs', $aspects)) {
            $ins = $this->validateInsUcs((array) $billedItems);
            $errors   = array_merge($errors, $ins['errors']);
            $warnings = array_merge($warnings, $ins['warnings']);
        }

        // 3. EDC validation
        if (in_array('edc', $aspects)) {
            $edc = $this->validateEdc($visit);
            $errors   = array_merge($errors, $edc['errors']);
            $warnings = array_merge($warnings, $edc['warnings'] ?? []);
        }

        // 4. Endpoint closure check
        if (in_array('endpoint', $aspects)) {
            $endpointOk = $this->validateNhsoEndpoint($visit);
        }

        // 5. ADP OFC check (adp_type = 20 requires adp_code ending in 14)
        if (in_array('adp_ofc', $aspects)) {
            $adpOfc = $this->validateAdpOfc((array) $billedItems);
            $errors   = array_merge($errors, $adpOfc['errors']);
            $warnings = array_merge($warnings, $adpOfc['warnings']);
        }

        return [
            'is_valid'       => empty($errors),
            'endpoint_valid' => $endpointOk,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];
    }

    /**
     * ตรวจสอบฟิลด์บังคับตามมาตรฐานโครงสร้าง 16 แฟ้ม e-Claim (Required = Y)
     */
    public function validateF16Required($visit, array $billedItems = []): array
    {
        $errors = [];
        $warnings = [];

        // 1. CID (เลขประจำตัวประชาชน 13 หลัก - Required = Y ใน PAT/INS/ODX/CHA/CHT)
        $cid = trim((string)($visit->cid ?? ''));
        if (empty($cid)) {
            $errors[] = "ไม่พบเลขประจำตัวประชาชน (CID) ของผู้ป่วย (ฟิลด์บังคับ Required ในแฟ้ม PAT/INS)";
        } elseif (strlen($cid) !== 13) {
            $errors[] = "เลขประจำตัวประชาชน (CID) ไม่ครบ 13 หลัก [{$cid}] (ฟิลด์บังคับ Required)";
        }

        // 2. PDX (การวินิจฉัยหลัก - Required = Y ใน ODX/IDX)
        $pdx = trim((string)($visit->pdx ?? ''));
        if (empty($pdx)) {
            $errors[] = "ไม่พบรหัสการวินิจฉัยโรคหลัก (PDX) ใน HOSxP (ฟิลด์บังคับ Required ในแฟ้ม ODX/IDX)";
        }

        // 3. แพทย์ผู้ตรวจ / เลขที่ใบประกอบวิชาชีพ (DRDX - Required ใน ODX/IDX)
        $doctorLicense = trim((string)($visit->doctor_license ?? ($visit->dx_doctor ?? '')));
        if (empty($doctorLicense)) {
            $warnings[] = "ไม่พบเลขที่ใบอนุญาตประกอบวิชาชีพของแพทย์ผู้ตรวจ (ฟิลด์ DRDX ในแฟ้ม ODX/IDX)";
        }

        // 4. ตรวจสอบรายการค่าบริการที่ไม่มียอด 0 แต่ไม่พบรหัส ADP (Required ในแฟ้ม ADP)
        if (!empty($billedItems)) {
            $unmappedAdpNames = [];
            foreach ($billedItems as $it) {
                if (is_object($it)) {
                    $icode = (string)($it->icode ?? '');
                    $price = floatval($it->sum_price ?? ($it->unitprice ?? 0));
                    $adpCode = trim((string)($it->nhso_adp_code ?? ''));
                    // Non-drug items with price > 0 and no ADP code
                    if ($price > 0 && !str_starts_with($icode, '1') && empty($adpCode) && isset($it->nhso_adp_code)) {
                        $unmappedAdpNames[] = $it->name ?? $icode;
                    }
                }
            }
            if (!empty($unmappedAdpNames)) {
                $namesSample = implode(', ', array_slice($unmappedAdpNames, 0, 3));
                if (count($unmappedAdpNames) > 3) {
                    $namesSample .= ' และอีก ' . (count($unmappedAdpNames) - 3) . ' รายการ';
                }
                $errors[] = "พบรายการค่าบริการที่ยังไม่ได้ผูกรหัส ADP Code ({$namesSample})";
            }
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * ตรวจสอบเลขอนุมัติ EDC (สำหรับสิทธิ OFC)
     */
    public function validateEdc($visit): array
    {
        $errors = [];
        $warnings = [];
        $edc_hosxp_list = array_filter(array_map('trim', explode(',', $visit->edc ?? '')));
        $edc_ktb_list = array_filter(array_map('trim', explode(',', $visit->edc_ktb ?? '')));

        if (empty($edc_hosxp_list) && empty($edc_ktb_list)) {
            $errors[] = "ไม่พบเลขอนุมัติ EDC ทั้งใน HOSxP และไฟล์นำเข้า KTB (กรุณาตรวจสอบการรูดบัตรหรือนำเข้าไฟล์ EDC)";
        } elseif (empty($edc_hosxp_list) && !empty($edc_ktb_list)) {
            // มีในไฟล์นำเข้า KTB แต่ไม่มีใน HOSxP -> ให้ผ่านเกณฑ์แบบเตือน (ตาเหลือง)
            $warnings[] = "พบเลขอนุมัติในไฟล์นำเข้า KTB (" . implode(',', $edc_ktb_list) . ") แต่ไม่พบใน HOSxP";
        } elseif (!empty($edc_hosxp_list) && empty($edc_ktb_list)) {
            $warnings[] = "พบเลขอนุมัติใน HOSxP (" . implode(',', $edc_hosxp_list) . ") แต่ยังไม่พบในไฟล์นำเข้า KTB";
        } elseif (count(array_intersect($edc_hosxp_list, $edc_ktb_list)) === 0) {
            $warnings[] = "เลขอนุมัติ EDC ใน HOSxP (" . implode(',', $edc_hosxp_list) . ") ไม่ตรงกับไฟล์นำเข้า KTB (" . implode(',', $edc_ktb_list) . ")";
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * ตรวจสอบสถานะการปิดสิทธิ์ปลายทาง
     */
    public function validateNhsoEndpoint($visit): bool
    {
        return ($visit->endpoint ?? '') === 'Y'
            || (!empty($visit->fdh_status) && (
                strpos($visit->fdh_status, 'อนุมัติ') !== false ||
                strpos($visit->fdh_status, 'สำเร็จ') !== false
            ));
    }

    // =========================================================================
    // Backward compatibility wrappers
    // =========================================================================

    public function validateUcs($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'ins_ucs', 'endpoint']);
    }

    public function validatePpfsOnly($visit, $billedItems): array
    {
        if (is_object($visit)) {
            $visit->is_sss = true;
        } elseif (is_array($visit)) {
            $visit['is_sss'] = true;
        }
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'endpoint']);
    }

    public function validateInsUcsOnly($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ins_ucs', 'endpoint']);
    }

    public function validateOfc($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'edc', 'adp_ofc', 'endpoint']);
    }

    /**
     * ตรวจสอบความถูกต้องของรายการ ADP สำหรับสิทธิ OFC (กรมบัญชีกลาง)
     * - adp_type = 20 (ค่าบริการทางกายภาพบำบัดและเวชกรรมฟื้นฟู) ต้อง Map รหัส adp_code ที่ลงท้ายด้วย 14 (xxx14)
     */
    public function validateAdpOfc(array $billedItems): array
    {
        $errors = [];
        $warnings = [];

        foreach ($billedItems as $it) {
            if (!is_object($it) && !is_array($it)) continue;

            $adpType = is_object($it) ? ($it->nhso_adp_type_id ?? ($it->adp_type ?? null)) : ($it['nhso_adp_type_id'] ?? ($it['adp_type'] ?? null));
            $adpCode = trim((string)(is_object($it) ? ($it->nhso_adp_code ?? ($it->adp_code ?? '')) : ($it['nhso_adp_code'] ?? ($it['adp_code'] ?? ''))));
            $name    = is_object($it) ? ($it->name ?? ($it->icode ?? '')) : ($it['name'] ?? ($it['icode'] ?? ''));

            // ตรวจสอบ adp_type = 20 (กายภาพบำบัดและเวชกรรมฟื้นฟู)
            if ($adpType == 20 || $adpType === '20') {
                if (empty($adpCode)) {
                    $errors[] = "พบรายการกายภาพบำบัด (adp_type = 20): '{$name}' ยังไม่ได้ Map รหัส ADP Code (ต้องลงท้ายด้วย 14 เช่น xxx14)";
                } elseif (!str_ends_with($adpCode, '14')) {
                    $errors[] = "รายการกายภาพบำบัด (adp_type = 20): '{$name}' รหัส ADP Code [{$adpCode}] ไม่ถูกต้อง (ต้องเป็นรหัสที่ลงท้ายด้วย 14 เช่น xxx14)";
                }
            }
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    public function validateLgo($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'adp_ofc', 'endpoint']);
    }

    public function validateBkk($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'adp_ofc', 'endpoint']);
    }

    public function validateBmt($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'adp_ofc', 'endpoint']);
    }

    public function validateSrt($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'adp_ofc', 'endpoint']);
    }

    public function validatePvt($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['f16_required', 'ppfs', 'adp_ofc', 'endpoint']);
    }

    // =========================================================================
    // validatePpfs() — ตรวจเงื่อนไข PPFS (เพศ / อายุ / ICD-10 / ICD-9 / ราคา)
    // =========================================================================

    /**
     * ตรวจ item ทุกตัวที่มี ppfs = 'Y' ตาม ppfs_rules.php
     *
     * @param  object $visit
     * @param  array  $billedItems
     * @return array  ['errors' => [], 'warnings' => []]
     */
    public function validatePpfs($visit, array $billedItems): array
    {
        $errors = [];

        $hasPpfs = false;
        foreach ($billedItems as $item) {
            if (($item->ppfs ?? '') === 'Y') {
                $hasPpfs = true;
                break;
            }
        }

        if (!$hasPpfs) {
            return ['errors' => [], 'warnings' => []];
        }

        // If already compensated / paid, skip validation errors
        // ต้องได้รับเงินชดเชยจริง (> 0) และไม่มีรหัสข้อผิดพลาด (ไม่ติด C) เท่านั้น
        $errCode = !empty($visit->rep_error_code) ? $visit->rep_error_code : (!empty($visit->error_code) ? $visit->error_code : null);
        $has_err_code = !empty($errCode);
        $has_paid = ((!empty($visit->receive_total) && floatval($visit->receive_total) > 0) || (!empty($visit->rep_nhso) && floatval($visit->rep_nhso) > 0));
        $has_compensation = $has_paid && !$has_err_code;
        if ($has_compensation) {
            return ['errors' => [], 'warnings' => []];
        }

        if ($has_err_code) {
            $repErrors = $this->formatRepError((string)$errCode, $visit, $billedItems);
            $errors = array_merge($errors, $repErrors);
        }

        // check DRDX (Doctor License) & DROPID (Procedure Operator License) - Only for Social Security (SSS)
        $is_sss = is_object($visit) ? !empty($visit->is_sss) : (!empty($visit['is_sss']));
        if ($is_sss) {
            // 1. ตรวจสอบผู้วินิจฉัยโรค (DRDX) ต้องขึ้นต้นด้วย ว หรือ ท (หรือ พท)
            $doc_lic = !empty($visit->doctor_license) ? trim($visit->doctor_license) : '';
            $is_doc_valid = (!empty($doc_lic) && (str_starts_with($doc_lic, 'ว') || str_starts_with($doc_lic, 'ท') || str_starts_with($doc_lic, 'พท')));
            if (!$is_doc_valid) {
                if (empty($doc_lic)) {
                    $errors[] = "ไม่พบเลขใบอนุญาตผู้วินิจฉัยโรค (DRDX) ของแพทย์ผู้รักษา (ต้องขึ้นต้นด้วย ว หรือ ท)";
                } else {
                    $errors[] = "เลขใบอนุญาตผู้วินิจฉัยโรค '{$doc_lic}' รูปแบบไม่ถูกต้อง (DRDX) ต้องขึ้นต้นด้วย ว หรือ ท";
                }
            }

            // 2. ตรวจสอบผู้ทำหัตถการ (DROPID) ต้องขึ้นต้นด้วย ว หรือ ท (หรือ พท)
            $procDetails = is_object($visit) ? ($visit->procedure_details ?? []) : ($visit['procedure_details'] ?? []);
            if (!empty($procDetails)) {
                foreach ($procDetails as $proc) {
                    $procCode = is_object($proc) ? ($proc->icd9 ?? ($proc->icd10 ?? '')) : ($proc['icd9'] ?? ($proc['icd10'] ?? ''));
                    $procLic  = trim((string)(is_object($proc) ? ($proc->doctor_license ?? '') : ($proc['doctor_license'] ?? '')));
                    $is_proc_valid = (!empty($procLic) && (str_starts_with($procLic, 'ว') || str_starts_with($procLic, 'ท') || str_starts_with($procLic, 'พท')));
                    if (!$is_proc_valid) {
                        if (empty($procLic)) {
                            $errors[] = "หัตถการ [{$procCode}]: ไม่พบเลขใบอนุญาตผู้ทำหัตถการ (DROPID) ต้องขึ้นต้นด้วย ว หรือ ท";
                        } else {
                            $errors[] = "หัตถการ [{$procCode}]: เลขใบอนุญาตผู้ทำหัตถการ '{$procLic}' (DROPID) ไม่ถูกต้อง ต้องขึ้นต้นด้วย ว หรือ ท";
                        }
                    }
                }
            }
        }

        [$sex, $age, $diagnoses, $procedures, $pdx, $sdx] = $this->extractPatientContext($visit);

        foreach ($billedItems as $item) {
            if (($item->ppfs ?? '') === 'Y') {
                $itemErrors = $this->runPpfsRules($item, $sex, $age, $diagnoses, $procedures, $pdx, $sdx);
                $errors = array_merge($errors, $itemErrors);
            }
        }

        return ['errors' => $errors, 'warnings' => []];
    }

    // =========================================================================
    // validateInsUcs() — ตรวจ Instrument ที่ไม่อยู่ในประกาศ UCS
    // =========================================================================

    /**
     * ตรวจ item ทุกตัวที่มี uc_cr = 'Y' ว่าอยู่ในประกาศ UCS หรือเปล่า
     * items ต้องมีฟิลด์ ins_ucs ติดมา (inject โดย Controller ก่อนเรียก)
     *
     * @param  array $billedItems
     * @return array  ['errors' => [], 'warnings' => []]
     */
    public function validateInsUcs(array $billedItems): array
    {
        $warnings = [];

        foreach ($billedItems as $item) {
            if (
                ($item->uc_cr ?? '') === 'Y'
                && isset($item->ins_ucs)
                && ($item->ins_ucs ?? '') !== 'Y'
                && !empty($item->nhso_adp_code)
            ) {
                $adpCode  = $item->nhso_adp_code;
                $itemName = $item->name ?? $item->icode;
                $warnings[] = "รหัส ADP {$adpCode} ({$itemName}): Instrument ไม่อยู่ในประกาศราคา UCS — โปรดตรวจสอบก่อนเคลม";
            }
        }

        return ['errors' => [], 'warnings' => $warnings];
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * สกัด sex / age / diagnoses / procedures / pdx / sdx จาก $visit
     */
    private function extractPatientContext($visit): array
    {
        $rawSex = strtoupper(trim((string)(is_object($visit) ? ($visit->sex ?? '') : ($visit['sex'] ?? ''))));
        $sex = '';
        if ($rawSex === '1' || $rawSex === 'M' || $rawSex === 'ชาย') {
            $sex = 'M';
        } elseif ($rawSex === '2' || $rawSex === 'F' || $rawSex === 'หญิง') {
            $sex = 'F';
        }

        $age = is_object($visit)
            ? (isset($visit->age_y) ? (int) $visit->age_y : null)
            : (isset($visit['age_y']) ? (int) $visit['age_y'] : null);

        $pdx = trim((string)(is_object($visit) ? ($visit->pdx ?? '') : ($visit['pdx'] ?? '')));
        $sdx = trim((string)(is_object($visit) ? ($visit->sdx ?? '') : ($visit['sdx'] ?? '')));

        $diagnoses = [];
        if (!empty($pdx)) {
            $diagnoses[] = $this->normalizeCode($pdx);
        }
        if (!empty($sdx)) {
            foreach (explode(',', $sdx) as $code) {
                $t = $this->normalizeCode($code);
                if ($t !== '') $diagnoses[] = $t;
            }
        }
        $diagnoses = array_unique($diagnoses);

        $rawIcd9 = (string)(is_object($visit) ? ($visit->icd9 ?? '') : ($visit['icd9'] ?? ''));
        $procedures = [];
        if (!empty($rawIcd9)) {
            foreach (explode(',', $rawIcd9) as $code) {
                $t = $this->normalizeCode($code);
                if ($t !== '') $procedures[] = $t;
            }
        }
        $procedures = array_unique($procedures);

        return [$sex, $age, $diagnoses, $procedures, $pdx, $sdx];
    }

    /**
     * สรุป "สิ่งที่ขาดใน HOSxP" สำหรับรหัสข้อผิดพลาด REP (เช่น C-240, C-217 ฯลฯ)
     *
     * @param  string $rawErrorCode
     * @param  object|array $visit
     * @param  array  $billedItems
     * @return array  errors
     */
    public function formatRepError(string $rawErrorCode, $visit, array $billedItems = []): array
    {
        $errors = [];
        $cleanCodes = array_filter(array_map(function($c) {
            return preg_replace('/[^0-9A-Z]/', '', strtoupper(trim($c)));
        }, preg_split('/[,;\/\s]+/', $rawErrorCode)));

        if (empty($cleanCodes)) {
            $cleanCodes = [trim($rawErrorCode)];
        }

        $pdx = trim((string)(is_object($visit) ? ($visit->pdx ?? '') : ($visit['pdx'] ?? '')));
        $sdx = trim((string)(is_object($visit) ? ($visit->sdx ?? '') : ($visit['sdx'] ?? '')));
        $icd9 = trim((string)(is_object($visit) ? ($visit->icd9 ?? '') : ($visit['icd9'] ?? '')));
        $age = is_object($visit) ? ($visit->age_y ?? null) : ($visit['age_y'] ?? null);
        $rawSex = strtoupper(trim((string)(is_object($visit) ? ($visit->sex ?? '') : ($visit['sex'] ?? ''))));
        $gender = ($rawSex === '1' || $rawSex === 'M' || $rawSex === 'ชาย') ? 'ชาย' : (($rawSex === '2' || $rawSex === 'F' || $rawSex === 'หญิง') ? 'หญิง' : $rawSex);
        $cid = trim((string)(is_object($visit) ? ($visit->cid ?? '') : ($visit['cid'] ?? '')));

        // รวบรวมข้อมูลรายการ PPFS ที่เรียกเก็บ
        $ppfsItemNames = [];
        $ppfsAdpCodes = [];
        foreach ($billedItems as $it) {
            $adp = trim((string)(is_object($it) ? ($it->nhso_adp_code ?? '') : ($it['nhso_adp_code'] ?? '')));
            $name = trim((string)(is_object($it) ? ($it->name ?? $it->icode ?? '') : ($it['name'] ?? $it['icode'] ?? '')));
            $isPpfs = (is_object($it) ? ($it->ppfs ?? '') : ($it['ppfs'] ?? '')) === 'Y';
            if ($isPpfs || isset($this->ppfsRules[$adp])) {
                if ($adp) $ppfsAdpCodes[] = $adp;
                $ppfsItemNames[] = $adp ? "{$adp}-{$name}" : $name;
            }
        }
        $itemsDesc = !empty($ppfsItemNames) ? implode(', ', array_unique($ppfsItemNames)) : 'รายการ PPFS';

        $hosxpDiagSummary = "ปัจจุบัน HOSxP มี " . ($pdx ? "PDX: {$pdx}" : "ไม่พบ PDX") . ($sdx ? ", SDX: {$sdx}" : "");
        $hosxpProcSummary = "ปัจจุบัน HOSxP " . ($icd9 ? "มี ICD-9: {$icd9}" : "ไม่พบรหัสหัตถการ");

        foreach ($cleanCodes as $code) {
            $numCode = ltrim($code, 'C');

            if ($numCode === '240' || $code === 'C240') {
                // C-240: รหัสโรคไม่เข้าเกณฑ์กลุ่มเสี่ยง
                if (in_array('15001', $ppfsAdpCodes)) {
                    $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 240 (รายการ 15001-เคลือบฟลูออไรด์): สิ่งที่ขาดใน HOSxP — ขาดรหัสโรคกลุ่มเสี่ยงทางทันตกรรม (เช่น K020, K021 ฟันผุ, K060 เหงือกร่น, K1170 ปากแห้ง, C00-C14 มะเร็งศีรษะ/คอ) ({$hosxpDiagSummary})";
                } else {
                    $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 240: สิ่งที่ขาดใน HOSxP — ขาดรหัสโรคกลุ่มเสี่ยง PPFS ตามเกณฑ์ที่ สปสช. กำหนดสำหรับ {$itemsDesc} ({$hosxpDiagSummary})";
                }
            } elseif ($numCode === '217' || $code === 'C217') {
                // C-217: ขาดรหัสหัตถการ ICD-9
                if (in_array('FP002_2', $ppfsAdpCodes)) {
                    $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 217 (รายการ FP002_2-ถอดยาฝังคุมกำเนิด): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 8605 ใน doctor_operation ({$hosxpProcSummary})";
                } elseif (in_array('FP002_1', $ppfsAdpCodes)) {
                    $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 217 (รายการ FP002_1-ฝังยาคุมกำเนิด): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 9923 ใน doctor_operation ({$hosxpProcSummary})";
                } elseif (in_array('FP001', $ppfsAdpCodes)) {
                    $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 217 (รายการ FP001-ใส่/ถอดห่วงอนามัย): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 697 หรือ 9771 ใน doctor_operation ({$hosxpProcSummary})";
                } else {
                    $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 217: สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 ใน doctor_operation หรือรหัสไม่ตรงเงื่อนไขสำหรับ {$itemsDesc} ({$hosxpProcSummary})";
                }
            } elseif ($numCode === '201' || $code === 'C201') {
                $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 201: สิ่งที่ขาดใน HOSxP — เพศผู้ป่วยไม่ตรงเงื่อนไขของ {$itemsDesc} (ปัจจุบัน HOSxP ระบุเพศ: {$gender})";
            } elseif ($numCode === '202' || $code === 'C202') {
                $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 202: สิ่งที่ขาดใน HOSxP — อายุผู้ป่วยไม่อยู่ในเกณฑ์สิทธิประโยชน์ของ {$itemsDesc} (ปัจจุบัน HOSxP ระบุอายุ: {$age} ปี)";
            } elseif ($numCode === '204' || $code === 'C204') {
                $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 204: สิ่งที่ขาดใน HOSxP — ยอดเรียกเก็บของรายการใน HOSxP ไม่ตรงกับเกณฑ์ราคาชดเชยเหมาจ่ายของ สปสช.";
            } elseif ($numCode === '010' || $numCode === '10' || $code === 'C010') {
                $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 010: สิ่งที่ขาดใน HOSxP — เลขประจำตัวประชาชน (CID: [{$cid}]) ไม่ถูกต้องหรือไม่ครบ 13 หลักในทะเบียนประวัติ (patient)";
            } elseif ($numCode === '300' || $numCode === '301' || $code === 'C300') {
                $errors[] = "ติดข้อผิดพลาด สปสช. รหัส {$code}: สิ่งที่ขาด — ขาดรหัสยืนยันตัวตน (Authen Code) ของ สปสช. หรือรหัสไม่ตรงกับวันที่รับบริการ";
            } elseif ($numCode === '401' || $code === 'C401') {
                $errors[] = "ติดข้อผิดพลาด สปสช. รหัส 401: สิ่งที่ขาดใน HOSxP — ขาดเลขที่ใบอนุญาตประกอบวิชาชีพ (ว... หรือ ท...) ของแพทย์ผู้ตรวจ หรือผู้ทำหัตถการในตาราง doctor";
            } else {
                $errors[] = "ติดข้อผิดพลาด สปสช. รหัส {$code}: สิ่งที่ขาดใน HOSxP — ข้อมูลไม่ผ่านเกณฑ์การประมวลผลของ สปสช. ({$hosxpDiagSummary}, {$hosxpProcSummary})";
            }
        }

        return $errors;
    }

    /**
     * ตรวจ PPFS rules ของ item รายการเดียว — คืน errors[]
     */
    private function runPpfsRules($item, $sex, $age, $diagnoses, $procedures, string $pdx = '', string $sdx = ''): array
    {
        $errors    = [];
        $adpCode   = trim($item->nhso_adp_code ?? '');
        $itemName  = $item->name ?? $item->icode;
        $itemPrice = floatval($item->sum_price ?? 0);

        if (!isset($this->ppfsRules[$adpCode])) {
            return [];
        }

        $rule = $this->ppfsRules[$adpCode];
        $hosxpDiags = "ปัจจุบัน HOSxP มี " . ($pdx ? "PDX: {$pdx}" : "ไม่พบ PDX") . ($sdx ? ", SDX: {$sdx}" : "");
        $hosxpProc = "ปัจจุบัน HOSxP " . (!empty($procedures) ? "มี ICD-9: " . implode(',', $procedures) : "ไม่พบรหัสหัตถการ");

        // Sex
        if (!empty($rule['sex'])) {
            $expectedSex = strtoupper($rule['sex']);
            if ($sex && $sex !== $expectedSex) {
                $genderName = $expectedSex === 'F' ? 'หญิง' : 'ชาย';
                $currGender = $sex === 'F' ? 'หญิง' : ($sex === 'M' ? 'ชาย' : ($sex ?: 'ไม่ระบุ'));
                $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — จำกัดเฉพาะเพศ {$genderName} (ปัจจุบันเพศ {$currGender}) อาจทำให้ติด C-201";
            }
        }

        // Age
        if (isset($rule['age'])) {
            $minAge = $rule['age']['min'] ?? null;
            $maxAge = $rule['age']['max'] ?? null;
            if ($age !== null) {
                if ($minAge !== null && $age < $minAge) {
                    $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — จำกัดอายุตั้งแต่ {$minAge} ปีขึ้นไป (ปัจจุบันอายุ {$age} ปี) อาจทำให้ติด C-202";
                }
                if ($maxAge !== null && $age > $maxAge) {
                    $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — จำกัดอายุไม่เกิน {$maxAge} ปี (ปัจจุบันอายุ {$age} ปี) อาจทำให้ติด C-202";
                }
            }
        }

        // ICD-10 Diagnosis
        if (!empty($rule['icd10'])) {
            $expected = array_map([$this, 'normalizeCode'], $rule['icd10']);
            $matched  = false;
            foreach ($diagnoses as $d) {
                if (in_array($d, $expected)) { $matched = true; break; }
            }
            if (!$matched) {
                if ($adpCode === '15001') {
                    $errors[] = "รหัส 15001 ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสโรคกลุ่มเสี่ยงทางทันตกรรม (เช่น K020, K021 ฟันผุ, K060 เหงือกร่น, K1170 ปากแห้ง, C00-C14) อาจทำให้ติด C-240 ({$hosxpDiags})";
                } else {
                    $sampleCodes = implode(', ', array_slice($rule['icd10'], 0, 6));
                    $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสโรคที่กำหนด (ต้องการรหัสในกลุ่ม: {$sampleCodes}) อาจทำให้ติด C-240 ({$hosxpDiags})";
                }
            }
        }

        // ICD-9 Procedure
        if (!empty($rule['icd9'])) {
            $expected = array_map([$this, 'normalizeCode'], $rule['icd9']);
            $matched  = false;
            foreach ($procedures as $p) {
                if (in_array($p, $expected)) { $matched = true; break; }
            }
            if (!$matched) {
                if ($adpCode === 'FP002_2') {
                    $errors[] = "รหัส FP002_2 ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 8605 ใน doctor_operation อาจทำให้ติด C-217 ({$hosxpProc})";
                } elseif ($adpCode === 'FP002_1') {
                    $errors[] = "รหัส FP002_1 ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 9923 ใน doctor_operation อาจทำให้ติด C-217 ({$hosxpProc})";
                } elseif ($adpCode === 'FP001') {
                    $errors[] = "รหัส FP001 ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 697 หรือ 9771 ใน doctor_operation อาจทำให้ติด C-217 ({$hosxpProc})";
                } else {
                    $procList = implode(', ', $rule['icd9']);
                    $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการ ICD-9 ที่กำหนด ({$procList}) ใน doctor_operation อาจทำให้ติด C-217 ({$hosxpProc})";
                }
            }
        }

        // Dental ICD-10 TM
        if (!empty($rule['dental_icd10_tm'])) {
            $isGrouped = false;
            foreach ($rule['dental_icd10_tm'] as $val) {
                if (is_array($val)) { $isGrouped = true; break; }
            }

            if ($isGrouped) {
                $missingGroups = [];
                foreach ($rule['dental_icd10_tm'] as $groupName => $groupCodes) {
                    $expected     = array_map([$this, 'normalizeCode'], $groupCodes);
                    $groupMatched = false;
                    foreach ($procedures as $p) {
                        if (in_array($p, $expected)) { $groupMatched = true; break; }
                    }
                    if (!$groupMatched) $missingGroups[] = $groupName;
                }
                if (!empty($rule['rules']['both_dental_groups_required'])) {
                    if (!empty($missingGroups)) {
                        $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการทันตกรรมในกลุ่ม " . implode(' และ ', $missingGroups);
                    }
                } else {
                    if (count($missingGroups) === count($rule['dental_icd10_tm'])) {
                        $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการทันตกรรมที่กำหนด";
                    }
                }
            } else {
                $expected = array_map([$this, 'normalizeCode'], $rule['dental_icd10_tm']);
                $matched  = false;
                foreach ($procedures as $p) {
                    if (in_array($p, $expected)) { $matched = true; break; }
                }
                if (!$matched) {
                    $sampleDental = implode(', ', array_slice($rule['dental_icd10_tm'], 0, 6));
                    $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — ขาดรหัสหัตถการทันตกรรมที่กำหนด (ต้องการรหัสในกลุ่ม: {$sampleDental})";
                }
            }
        }

        // Price
        if (isset($rule['amount']) && floatval($rule['amount']) > 0) {
            $qty = isset($item->qty) ? floatval($item->qty) : 1;
            $expectedPrice = floatval($rule['amount']) * $qty;
            if (abs($itemPrice - $expectedPrice) > 0.01) {
                $errors[] = "รหัส {$adpCode} ({$itemName}): สิ่งที่ขาดใน HOSxP — ยอดเรียกเก็บ (" . number_format($itemPrice, 2) . " บาท) ไม่ตรงกับเกณฑ์ (" . number_format($expectedPrice, 2) . " บาท) อาจทำให้ติด C-204";
            }
        }

        return $errors;
    }

    /**
     * ตรวจสอบว่า ICD-10 เป็นรหัสโรคหลักที่ถูกต้องตามตาราง lookup_icd10_chi หรือไม่
     *
     * @param string $diagCode
     * @param string $diagType  '1' = PDX (โรคหลัก), อื่นๆ = SDX
     * @return array  ['is_valid' => bool, 'message' => string]
     */
    public function validateIcd10Chi(string $diagCode, string $diagType = '1'): array
    {
        $clean = $this->normalizeCode($diagCode);
        if (empty($clean)) {
            return ['is_valid' => true, 'message' => ''];
        }

        // If it is a Thai Traditional Medicine code (starts with U5, U6, U7), it is valid for TT CodeSet
        if (preg_match('/^U[567]/i', $clean)) {
            return ['is_valid' => true, 'message' => ''];
        }

        $row = \Illuminate\Support\Facades\DB::table('lookup_icd10_chi')
            ->where('code', $clean)
            ->first();

        if (!$row) {
            return [
                'is_valid' => false,
                'message' => "รหัสวินิจฉัย {$diagCode} ไม่พบในบัญชีรหัสโรค สกส."
            ];
        }

        // (Disabled as requested) 
        // if ($diagType === '1' && ($row->accpdx ?? '') === 'N') {
        //     return [
        //         'is_valid' => false,
        //         'message' => "รหัสโรคหลัก {$diagCode} ไม่อนุญาตให้เป็นโรคหลัก (ACCPDX=N)"
        //     ];
        // }

        return ['is_valid' => true, 'message' => ''];
    }

    /**
     * Normalize medical codes: ตัดช่องว่าง จุด ขีด แปลงเป็น uppercase
     */
    private function normalizeCode(string $code): string
    {
        return str_replace(['.', ' ', '-'], '', strtoupper(trim($code)));
    }
}
