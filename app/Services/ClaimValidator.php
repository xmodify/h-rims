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
        // Default to UCS aspects for backward compatibility if none specified
        if (empty($aspects)) {
            $aspects = ['ppfs', 'ins_ucs', 'endpoint'];
        }

        $errors   = [];
        $warnings = [];
        $endpointOk = true;

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
        }

        // 4. Endpoint closure check
        if (in_array('endpoint', $aspects)) {
            $endpointOk = $this->validateNhsoEndpoint($visit);
        }

        return [
            'is_valid'       => empty($errors),
            'endpoint_valid' => $endpointOk,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];
    }

    /**
     * ตรวจสอบเลขอนุมัติ EDC (สำหรับสิทธิ OFC)
     */
    public function validateEdc($visit): array
    {
        $errors = [];
        $edc_hosxp_list = array_filter(array_map('trim', explode(',', $visit->edc ?? '')));
        $edc_ktb_list = array_filter(array_map('trim', explode(',', $visit->edc_ktb ?? '')));

        if (empty($edc_hosxp_list)) {
            $errors[] = "ไม่พบเลขอนุมัติ EDC ใน HOSxP";
        } elseif (empty($edc_ktb_list)) {
            $errors[] = "ไม่พบเลขอนุมัติ EDC ในไฟล์นำเข้า KTB (กรุณานำเข้าไฟล์ EDC)";
        } elseif (count(array_intersect($edc_hosxp_list, $edc_ktb_list)) === 0) {
            $errors[] = "เลขอนุมัติ EDC ใน HOSxP (" . implode(',', $edc_hosxp_list) . ") ไม่ตรงกับไฟล์นำเข้า KTB (" . implode(',', $edc_ktb_list) . ")";
        }

        return ['errors' => $errors];
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
        return $this->validate($visit, $billedItems, ['ppfs', 'ins_ucs', 'endpoint']);
    }

    public function validatePpfsOnly($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['ppfs', 'endpoint']);
    }

    public function validateInsUcsOnly($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['ins_ucs', 'endpoint']);
    }

    public function validateOfc($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['ppfs', 'edc', 'endpoint']);
    }

    public function validateLgo($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['ppfs', 'endpoint']);
    }

    public function validateBkk($visit, $billedItems): array
    {
        return $this->validate($visit, $billedItems, ['ppfs', 'endpoint']);
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

        [$sex, $age, $diagnoses, $procedures] = $this->extractPatientContext($visit);

        foreach ($billedItems as $item) {
            if (($item->ppfs ?? '') === 'Y') {
                $itemErrors = $this->runPpfsRules($item, $sex, $age, $diagnoses, $procedures);
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
     * สกัด sex / age / diagnoses / procedures จาก $visit
     */
    private function extractPatientContext($visit): array
    {
        $rawSex = strtoupper(trim($visit->sex ?? ''));
        $sex = '';
        if ($rawSex === '1' || $rawSex === 'M' || $rawSex === 'ชาย') {
            $sex = 'M';
        } elseif ($rawSex === '2' || $rawSex === 'F' || $rawSex === 'หญิง') {
            $sex = 'F';
        }

        $age = isset($visit->age_y) ? (int) $visit->age_y : null;

        $diagnoses = [];
        if (!empty($visit->pdx)) {
            $diagnoses[] = $this->normalizeCode($visit->pdx);
        }
        if (!empty($visit->sdx)) {
            foreach (explode(',', $visit->sdx) as $code) {
                $t = $this->normalizeCode($code);
                if ($t !== '') $diagnoses[] = $t;
            }
        }
        $diagnoses = array_unique($diagnoses);

        $procedures = [];
        if (!empty($visit->icd9)) {
            foreach (explode(',', $visit->icd9) as $code) {
                $t = $this->normalizeCode($code);
                if ($t !== '') $procedures[] = $t;
            }
        }
        $procedures = array_unique($procedures);

        return [$sex, $age, $diagnoses, $procedures];
    }

    /**
     * ตรวจ PPFS rules ของ item รายการเดียว — คืน errors[]
     */
    private function runPpfsRules($item, $sex, $age, $diagnoses, $procedures): array
    {
        $errors    = [];
        $adpCode   = trim($item->nhso_adp_code ?? '');
        $itemName  = $item->name ?? $item->icode;
        $itemPrice = floatval($item->sum_price ?? 0);

        if (!isset($this->ppfsRules[$adpCode])) {
            return [];
        }

        $rule = $this->ppfsRules[$adpCode];

        // Sex
        if (!empty($rule['sex'])) {
            $expectedSex = strtoupper($rule['sex']);
            if ($sex && $sex !== $expectedSex) {
                $genderName = $expectedSex === 'F' ? 'หญิง' : 'ชาย';
                $errors[] = "รหัส {$adpCode} ({$itemName}): จำกัดเฉพาะเพศ {$genderName} เท่านั้น";
            }
        }

        // Age
        if (isset($rule['age'])) {
            $minAge = $rule['age']['min'] ?? null;
            $maxAge = $rule['age']['max'] ?? null;
            if ($age !== null) {
                if ($minAge !== null && $age < $minAge) {
                    $errors[] = "รหัส {$adpCode} ({$itemName}): จำกัดอายุผู้ป่วยตั้งแต่ {$minAge} ปีขึ้นไป (ปัจจุบันอายุ {$age} ปี)";
                }
                if ($maxAge !== null && $age > $maxAge) {
                    $errors[] = "รหัส {$adpCode} ({$itemName}): จำกัดอายุผู้ป่วยไม่เกิน {$maxAge} ปี (ปัจจุบันอายุ {$age} ปี)";
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
                $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสโรคหลัก/โรคร่วมที่กำหนด (ต้องการรหัสใดรหัสหนึ่งในกลุ่ม: " . implode(', ', $rule['icd10']) . ")";
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
                $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการที่กำหนด (ต้องการรหัสใดรหัสหนึ่งในกลุ่ม: " . implode(', ', $rule['icd9']) . ")";
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
                        $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการทันตกรรมในกลุ่ม " . implode(' และ ', $missingGroups);
                    }
                } else {
                    if (count($missingGroups) === count($rule['dental_icd10_tm'])) {
                        $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการทันตกรรมที่กำหนด";
                    }
                }
            } else {
                $expected = array_map([$this, 'normalizeCode'], $rule['dental_icd10_tm']);
                $matched  = false;
                foreach ($procedures as $p) {
                    if (in_array($p, $expected)) { $matched = true; break; }
                }
                if (!$matched) {
                    $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการทันตกรรมที่กำหนด (ต้องการรหัสใดรหัสหนึ่งในกลุ่ม: " . implode(', ', $rule['dental_icd10_tm']) . ")";
                }
            }
        }

        // Price
        if (isset($rule['amount']) && floatval($rule['amount']) > 0) {
            $qty = isset($item->qty) ? floatval($item->qty) : 1;
            $expectedPrice = floatval($rule['amount']) * $qty;
            if (abs($itemPrice - $expectedPrice) > 0.01) {
                $errors[] = "รหัส {$adpCode} ({$itemName}): ยอดเรียกเก็บ (" . number_format($itemPrice, 2) . " บาท) ไม่ตรงกับเกณฑ์ชดเชย (" . number_format($expectedPrice, 2) . " บาท)";
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
