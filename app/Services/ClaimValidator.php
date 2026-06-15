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
    public function validate($visit, $billedItems): array
    {
        $ppfs   = $this->validatePpfs($visit, (array) $billedItems);
        $insUcs = $this->validateInsUcs((array) $billedItems);

        $errors   = array_merge($ppfs['errors'],   $insUcs['errors']);
        $warnings = array_merge($ppfs['warnings'], $insUcs['warnings']);

        // Basic check: auth_code
        if (($visit->auth_code ?? '') !== 'Y') {
            array_unshift($errors, "ยังไม่มีรหัส Authen Code");
        }

        // Endpoint check (แยกออกจาก is_valid — UI แสดงสีเหลืองแทนสีแดง)
        $endpointOk = ($visit->endpoint ?? '') === 'Y'
            || (!empty($visit->fdh_status) && (
                strpos($visit->fdh_status, 'อนุมัติ') !== false ||
                strpos($visit->fdh_status, 'สำเร็จ') !== false
            ));

        return [
            'is_valid'       => empty($errors),
            'endpoint_valid' => $endpointOk,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];
    }

    /**
     * @param  object $visit
     * @param  array  $billedItems
     * @return array  ['is_valid', 'endpoint_valid', 'errors', 'warnings']
     */
    public function validatePpfsOnly($visit, $billedItems): array
    {
        $ppfs = $this->validatePpfs($visit, (array) $billedItems);

        $errors   = $ppfs['errors'];
        $warnings = $ppfs['warnings'];

        // Basic check: auth_code
        if (($visit->auth_code ?? '') !== 'Y') {
            array_unshift($errors, "ยังไม่มีรหัส Authen Code");
        }

        // Endpoint check
        $endpointOk = ($visit->endpoint ?? '') === 'Y'
            || (!empty($visit->fdh_status) && (
                strpos($visit->fdh_status, 'อนุมัติ') !== false ||
                strpos($visit->fdh_status, 'สำเร็จ') !== false
            ));

        return [
            'is_valid'       => empty($errors),
            'endpoint_valid' => $endpointOk,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];
    }

    /**
     * @param  object $visit
     * @param  array  $billedItems
     * @return array  ['is_valid', 'endpoint_valid', 'errors', 'warnings']
     */
    public function validateOfc($visit, $billedItems): array
    {
        // 1. PPFS validation (only validate if there is at least one PPFS item)
        $hasPpfs = false;
        foreach ((array) $billedItems as $item) {
            if (($item->ppfs ?? '') === 'Y') {
                $hasPpfs = true;
                break;
            }
        }

        $errors   = [];
        $warnings = [];
        if ($hasPpfs) {
            $ppfs = $this->validatePpfs($visit, (array) $billedItems);
            $errors   = $ppfs['errors'];
            $warnings = $ppfs['warnings'];
        }

        // 2. EDC Approve Code matching check
        $edc_hosxp_list = array_filter(array_map('trim', explode(',', $visit->edc ?? '')));
        $edc_ktb_list = array_filter(array_map('trim', explode(',', $visit->edc_ktb ?? '')));

        if (!empty($edc_hosxp_list) || !empty($edc_ktb_list)) {
            if (empty($edc_hosxp_list)) {
                $errors[] = "ไม่พบเลขอนุมัติ EDC ใน HOSxP";
            } elseif (empty($edc_ktb_list)) {
                $errors[] = "ไม่พบเลขอนุมัติ EDC ในไฟล์นำเข้า KTB (กรุณานำเข้าไฟล์ EDC)";
            } elseif (count(array_intersect($edc_hosxp_list, $edc_ktb_list)) === 0) {
                $errors[] = "เลขอนุมัติ EDC ใน HOSxP (" . implode(',', $edc_hosxp_list) . ") ไม่ตรงกับไฟล์นำเข้า KTB (" . implode(',', $edc_ktb_list) . ")";
            }
        }

        // Basic check: auth_code
        if (($visit->auth_code ?? '') !== 'Y') {
            $errors[] = "ยังไม่มีรหัส Authen Code";
        }

        // 3. Endpoint check (closure)
        $endpointOk = ($visit->endpoint ?? '') === 'Y';

        return [
            'is_valid'       => empty($errors),
            'endpoint_valid' => $endpointOk,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];
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

        if (empty($billedItems)) {
            return [
                'errors'   => ["ไม่พบรายการยาหรือเวชภัณฑ์ที่เข้าข่ายการเคลม (PPFS)"],
                'warnings' => [],
            ];
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
            $expectedPrice = floatval($rule['amount']);
            if (abs($itemPrice - $expectedPrice) > 0.01) {
                $errors[] = "รหัส {$adpCode} ({$itemName}): ยอดเรียกเก็บ (" . number_format($itemPrice, 2) . " บาท) ไม่ตรงกับเกณฑ์ชดเชย (" . number_format($expectedPrice, 2) . " บาท)";
            }
        }

        return $errors;
    }

    /**
     * Normalize medical codes: ตัดช่องว่าง จุด ขีด แปลงเป็น uppercase
     */
    private function normalizeCode(string $code): string
    {
        return str_replace(['.', ' ', '-'], '', strtoupper(trim($code)));
    }
}
