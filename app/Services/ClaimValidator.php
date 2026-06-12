<?php

namespace App\Services;

class ClaimValidator
{
    protected $ppfsRules;

    public function __construct()
    {
        $this->ppfsRules = file_exists(config_path('claims/ppfs_rules.php')) 
            ? require config_path('claims/ppfs_rules.php') 
            : [];
    }

    /**
     * Validate a visit record along with its billed items.
     *
     * @param object $visit
     * @param array $billedItems
     * @return array
     */
    public function validate($visit, $billedItems)
    {
        $errors = [];

        // 1. Basic Checks
        if (($visit->auth_code ?? '') !== 'Y') {
            $errors[] = "ยังไม่มีรหัส Authen Code";
        }

        // endpoint check — แยกออกจาก is_valid เพื่อให้ UI แสดงสีเหลืองแทนสีแดง
        $endpointOk = ($visit->endpoint ?? '') === 'Y'
            || (!empty($visit->fdh_status) && (
                strpos($visit->fdh_status, 'อนุมัติ') !== false ||
                strpos($visit->fdh_status, 'สำเร็จ') !== false
            ));

        // Normalize Patient Demographics
        $rawSex = strtoupper(trim($visit->sex ?? ''));
        $sex = '';
        if ($rawSex === '1' || $rawSex === 'M' || $rawSex === 'ชาย') {
            $sex = 'M';
        } elseif ($rawSex === '2' || $rawSex === 'F' || $rawSex === 'หญิง') {
            $sex = 'F';
        }

        $age = isset($visit->age_y) ? (int)$visit->age_y : null;

        // Gather diagnoses (ICD-10): pdx and sdx only
        $diagnoses = [];
        if (!empty($visit->pdx)) {
            $diagnoses[] = $this->normalizeCode($visit->pdx);
        }
        if (!empty($visit->sdx)) {
            $secCodes = explode(',', $visit->sdx);
            foreach ($secCodes as $code) {
                $trimmed = $this->normalizeCode($code);
                if ($trimmed !== '') {
                    $diagnoses[] = $trimmed;
                }
            }
        }
        $diagnoses = array_unique($diagnoses);

        // Gather procedures (ICD-9 / Dental ICD-10 TM): icd9 only
        $procedures = [];
        if (!empty($visit->icd9)) {
            $procCodes = explode(',', $visit->icd9);
            foreach ($procCodes as $code) {
                $trimmed = $this->normalizeCode($code);
                if ($trimmed !== '') {
                    $procedures[] = $trimmed;
                }
            }
        }
        $procedures = array_unique($procedures);

        // 2. Item Specific Checks
        if (empty($billedItems)) {
            $errors[] = "ไม่พบรายการยาหรือเวชภัณฑ์ที่เข้าข่ายการเคลม (PPFS)";
        } else {
            foreach ($billedItems as $item) {
                if ($item->ppfs === 'Y') {
                    $itemErrors = $this->validatePpfs($item, $sex, $age, $diagnoses, $procedures);
                    $errors = array_merge($errors, $itemErrors);
                }
            }
        }

        return [
            'is_valid'       => empty($errors),
            'endpoint_valid' => $endpointOk,
            'errors'         => $errors
        ];
    }

    /**
     * Validate PPFS rules.
     */
    protected function validatePpfs($item, $sex, $age, $diagnoses, $procedures)
    {
        $errors = [];
        $adpCode = trim($item->nhso_adp_code ?? '');
        $itemName = $item->name ?? $item->icode;
        $itemPrice = floatval($item->sum_price ?? 0);

        if (!isset($this->ppfsRules[$adpCode])) {
            return [];
        }

        $rule = $this->ppfsRules[$adpCode];

        // Sex Rule
        if (!empty($rule['sex'])) {
            $expectedSex = strtoupper($rule['sex']);
            if ($sex && $sex !== $expectedSex) {
                $genderName = $expectedSex === 'F' ? 'หญิง' : 'ชาย';
                $errors[] = "รหัส {$adpCode} ({$itemName}): จำกัดเฉพาะเพศ {$genderName} เท่านั้น";
            }
        }

        // Age Rule
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

        // ICD10 Diagnosis Rule
        if (!empty($rule['icd10'])) {
            $expectedDiags = array_map([$this, 'normalizeCode'], $rule['icd10']);
            $matched = false;
            foreach ($diagnoses as $diag) {
                if (in_array($diag, $expectedDiags)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $expectedList = implode(', ', $rule['icd10']);
                $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสโรคหลัก/โรคร่วมที่กำหนด (ต้องการรหัสใดรหัสหนึ่งในกลุ่ม: {$expectedList})";
            }
        }

        // ICD9 Procedure Rule
        if (!empty($rule['icd9'])) {
            $expectedProcs = array_map([$this, 'normalizeCode'], $rule['icd9']);
            $matched = false;
            foreach ($procedures as $proc) {
                if (in_array($proc, $expectedProcs)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $expectedList = implode(', ', $rule['icd9']);
                $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการที่กำหนด (ต้องการรหัสใดรหัสหนึ่งในกลุ่ม: {$expectedList})";
            }
        }

        // Dental ICD10 TM Rule
        if (!empty($rule['dental_icd10_tm'])) {
            $isGrouped = false;
            foreach ($rule['dental_icd10_tm'] as $key => $val) {
                if (is_array($val)) {
                    $isGrouped = true;
                    break;
                }
            }

            if ($isGrouped) {
                $missingGroups = [];
                foreach ($rule['dental_icd10_tm'] as $groupName => $groupCodes) {
                    $expectedGroupCodes = array_map([$this, 'normalizeCode'], $groupCodes);
                    $groupMatched = false;
                    foreach ($procedures as $proc) {
                        if (in_array($proc, $expectedGroupCodes)) {
                            $groupMatched = true;
                            break;
                        }
                    }
                    if (!$groupMatched) {
                        $missingGroups[] = $groupName;
                    }
                }

                if (!empty($rule['rules']['both_dental_groups_required'])) {
                    if (!empty($missingGroups)) {
                        $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการทันตกรรมที่กำหนดในกลุ่ม " . implode(' และ ', $missingGroups);
                    }
                } else {
                    if (count($missingGroups) === count($rule['dental_icd10_tm'])) {
                        $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการทันตกรรมที่กำหนด";
                    }
                }
            } else {
                $expectedProcs = array_map([$this, 'normalizeCode'], $rule['dental_icd10_tm']);
                $matched = false;
                foreach ($procedures as $proc) {
                    if (in_array($proc, $expectedProcs)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    $expectedList = implode(', ', $rule['dental_icd10_tm']);
                    $errors[] = "รหัส {$adpCode} ({$itemName}): ขาดรหัสหัตถการทันตกรรมที่กำหนด (ต้องการรหัสใดรหัสหนึ่งในกลุ่ม: {$expectedList})";
                }
            }
        }

        // Price Rule
        if (isset($rule['amount']) && floatval($rule['amount']) > 0) {
            $expectedPrice = floatval($rule['amount']);
            if (abs($itemPrice - $expectedPrice) > 0.01) {
                $errors[] = "รหัส {$adpCode} ({$itemName}): ยอดเงินคีย์เรียกเก็บจริง (" . number_format($itemPrice, 2) . " บาท) ไม่ตรงกับราคาเกณฑ์ชดเชย (" . number_format($expectedPrice, 2) . " บาท)";
            }
        }

        return $errors;
    }



    /**
     * Normalize medical codes by stripping spaces, dots, and converting to uppercase.
     */
    private function normalizeCode($code)
    {
        return str_replace(['.', ' ', '-'], '', strtoupper(trim($code)));
    }
}
