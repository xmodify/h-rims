<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ClaimPreAuditService
{
    /**
     * Cache for lookup_nhso_c_deny dictionary
     */
    protected static ?array $cDenyCache = null;

    /**
     * Get or load C-Deny definitions from database or fallback JSON
     */
    public static function getLookupCache(): array
    {
        if (self::$cDenyCache !== null) {
            return self::$cDenyCache;
        }

        self::$cDenyCache = [];

        try {
            if (Schema::hasTable('lookup_nhso_c_deny')) {
                $rows = DB::table('lookup_nhso_c_deny')
                    ->select('code', 'type', 'description', 'guide')
                    ->get();
                foreach ($rows as $r) {
                    self::$cDenyCache[trim((string)$r->code)] = [
                        'code' => trim((string)$r->code),
                        'type' => $r->type ?? 'Corrective',
                        'description' => $r->description ?? '',
                        'guide' => $r->guide ?? ''
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("ClaimPreAuditService: lookup db error " . $e->getMessage());
        }

        // Fallback to json file if cache is empty
        if (empty(self::$cDenyCache)) {
            $jsonPath = base_path('docs/lookup/lookup_nhso_c_deny.json');
            if (file_exists($jsonPath)) {
                $jsonContent = @file_get_contents($jsonPath);
                $jsonData = json_decode($jsonContent, true);
                if (is_array($jsonData)) {
                    foreach ($jsonData as $item) {
                        if (!empty($item['code'])) {
                            self::$cDenyCache[trim((string)$item['code'])] = [
                                'code' => trim((string)$item['code']),
                                'type' => $item['type'] ?? 'Corrective',
                                'description' => $item['description'] ?? '',
                                'guide' => $item['guide'] ?? ''
                            ];
                        }
                    }
                }
            }
        }

        return self::$cDenyCache;
    }

    /**
     * Helper to lookup explanation for a specific C-Code
     */
    public static function lookupCode(string $code, string $defaultDesc = '', string $defaultGuide = ''): array
    {
        $cache = self::getLookupCache();
        $code = trim($code);
        if (isset($cache[$code])) {
            return [
                'code' => $code,
                'type' => $cache[$code]['type'] ?: 'Corrective',
                'description' => $cache[$code]['description'] ?: $defaultDesc,
                'guide' => $cache[$code]['guide'] ?: $defaultGuide
            ];
        }

        return [
            'code' => $code,
            'type' => 'Corrective',
            'description' => $defaultDesc,
            'guide' => $defaultGuide
        ];
    }

    /**
     * Validate Thai National ID Card (CID) Checksum Mod 11
     */
    public static function validateThaiCid(string $cid): bool
    {
        $cid = trim($cid);
        if (strlen($cid) !== 13 || !ctype_digit($cid)) {
            return false;
        }

        if (preg_match('/^(\d)\1{12}$/', $cid)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$cid[$i] * (13 - $i);
        }

        $checkDigit = (11 - ($sum % 11)) % 10;
        return $checkDigit === (int)$cid[12];
    }

    /**
     * Audit single OPD Visit ($vn)
     */
    public static function auditVisit(string $vn, array $context = []): array
    {
        if (empty($vn)) {
            return self::emptyResult();
        }

        try {
            // 1. Fetch Visit details
            $visit = DB::connection('hosxp')->selectOne("
                SELECT o.vn, o.hn, o.vstdate, o.vsttime, o.spclty, o.doctor,
                       pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex,
                       v.age_y, v.income, v.uc_money, v.item_money,
                       p.pttype, p.name AS pttype_name, p.hipdata_code,
                       vp.hospmain, vp.hospsub, vp.auth_code, vp.claim_code,
                       er.er_pt_type, er.er_emergency_type, er.er_emergency_level_id,
                       er.vstdate AS er_vstdate,
                       COALESCE(er.er_time_1, er.enter_er_time) AS er_time,
                       vp.nhso_ucae_type_code AS ucae
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn = o.hn
                LEFT JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                LEFT JOIN pttype p ON p.pttype = vp.pttype
                LEFT JOIN er_regist er ON er.vn = o.vn
                WHERE o.vn = ?
                LIMIT 1
            ", [$vn]);

            if (!$visit) {
                return [
                    'status' => 'FAIL',
                    'badge' => 'danger',
                    'is_valid' => false,
                    'summary' => ['errors' => 1, 'warnings' => 0, 'total' => 1],
                    'issues' => [
                        [
                            'code' => 'NOT_FOUND',
                            'type' => 'Danger',
                            'severity' => 'danger',
                            'file' => 'SYS',
                            'title' => 'ไม่พบข้อมูล Visit ในระบบ HOSxP',
                            'description' => "ไม่พบรหัส VN: {$vn} ในฐานข้อมูล",
                            'guide' => 'ตรวจสอบการส่งตรวจและการบันทึกข้อมูลใน HOSxP',
                            'location' => 'ระบบส่งตรวจ OPD'
                        ]
                    ]
                ];
            }

            // 2. Fetch Diags
            $diags = DB::connection('hosxp')->select("
                SELECT od.vn, od.icd10, od.diagtype, od.doctor, i.name AS icd_name
                FROM ovstdiag od
                LEFT JOIN icd101 i ON i.code = od.icd10
                WHERE od.vn = ?
                ORDER BY od.diagtype ASC
            ", [$vn]);

            // 3. Fetch Procedures (ovstdiag / doctor_operation)
            $procedures = DB::connection('hosxp')->select("
                SELECT o.vn, o.icd9, o.doctor, doc.name AS doctor_name, doc.licenseno
                FROM doctor_operation o
                LEFT JOIN doctor doc ON doc.code = o.doctor
                WHERE o.vn = ?
            ", [$vn]);

            // 4. Fetch Items (opitemrece)
            $items = DB::connection('hosxp')->select("
                SELECT op.icode, op.qty, op.unitprice, op.sum_price, op.income, op.paidst,
                       COALESCE(nd.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type,
                       COALESCE(nd.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                       nd.name AS item_name,
                       d.did AS drug_did, d.name AS drug_name, d.units
                FROM opitemrece op
                LEFT JOIN nondrugitems nd ON nd.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.vn = ?
            ", [$vn]);

            // 5. Fetch Refer Out / Refer In
            $referOut = DB::connection('hosxp')->selectOne("
                SELECT ro.refer_number, COALESCE(NULLIF(ro.refer_hospcode, ''), ro.hospcode) as refer_hospcode, ro.refer_date, ro.refer_time
                FROM referout ro WHERE ro.vn = ? LIMIT 1
            ", [$vn]);

            $referIn = DB::connection('hosxp')->selectOne("
                SELECT ri.docno as refer_number, COALESCE(NULLIF(ri.refer_hospcode, ''), ri.hospcode) as refer_hospcode, ri.refer_date, ri.refer_time
                FROM referin ri WHERE ri.vn = ? LIMIT 1
            ", [$vn]);

            // Check NHSO Endpoint / Authen if exists in H-RIMS
            $endpointStatus = null;
            try {
                if (Schema::hasTable('nhso_endpoint')) {
                    $endpointStatus = DB::table('nhso_endpoint')
                        ->where('cid', $visit->cid)
                        ->where('vstdate', $visit->vstdate)
                        ->value('claimCode');
                }
            } catch (\Throwable $e) {}

            return self::evaluateRules(
                $visit,
                $diags,
                $procedures,
                $items,
                $referOut,
                $referIn,
                $endpointStatus,
                'OPD',
                $context
            );

        } catch (\Throwable $e) {
            Log::error("ClaimPreAuditService::auditVisit error: " . $e->getMessage());
            return self::errorResult($e->getMessage());
        }
    }

    /**
     * Audit single IPD Admission ($an)
     */
    public static function auditAdmission(string $an, array $context = []): array
    {
        if (empty($an)) {
            return self::emptyResult();
        }

        try {
            // 1. Fetch IPD details
            $admission = DB::connection('hosxp')->selectOne("
                SELECT ipt.an, ipt.vn, ipt.hn, ipt.regdate AS vstdate, ipt.regtime AS vsttime,
                       ipt.dchdate, ipt.dchtime, ipt.dchstts, ipt.dchtype, ipt.spclty, ipt.admdoctor AS doctor,
                       pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex,
                       an_stat.age_y, an_stat.income, an_stat.uc_money, an_stat.item_money,
                       p.pttype, p.name AS pttype_name, p.hipdata_code,
                       vp.hospmain, vp.hospsub, vp.auth_code, vp.claim_code,
                       ia.ac_ae AS ipt_ac_ae, ia.ac_emtype AS ipt_ac_emtype
                FROM ipt
                LEFT JOIN patient pt ON pt.hn = ipt.hn
                LEFT JOIN an_stat ON an_stat.an = ipt.an
                LEFT JOIN visit_pttype vp ON vp.vn = ipt.vn
                LEFT JOIN pttype p ON p.pttype = ipt.pttype
                LEFT JOIN ipt_accident ia ON ia.an = ipt.an
                WHERE ipt.an = ?
                LIMIT 1
            ", [$an]);

            if (!$admission) {
                return [
                    'status' => 'FAIL',
                    'badge' => 'danger',
                    'is_valid' => false,
                    'summary' => ['errors' => 1, 'warnings' => 0, 'total' => 1],
                    'issues' => [
                        [
                            'code' => 'NOT_FOUND',
                            'type' => 'Danger',
                            'severity' => 'danger',
                            'file' => 'SYS',
                            'title' => 'ไม่พบข้อมูล Admission ในระบบ HOSxP',
                            'description' => "ไม่พบรหัส AN: {$an} ในฐานข้อมูล",
                            'guide' => 'ตรวจสอบการรับผู้ป่วยใน HOSxP',
                            'location' => 'ระบบงานผู้ป่วยใน IPD'
                        ]
                    ]
                ];
            }

            // 2. Fetch IPD Diags
            $diags = DB::connection('hosxp')->select("
                SELECT id.an, id.icd10, id.diagtype, id.doctor, i.name AS icd_name
                FROM iptdiag id
                LEFT JOIN icd101 i ON i.code = id.icd10
                WHERE id.an = ?
                ORDER BY id.diagtype ASC
            ", [$an]);

            // 3. Fetch IPD Procedures
            $procedures = DB::connection('hosxp')->select("
                SELECT io.an, io.icd9, io.doctor, doc.name AS doctor_name, doc.licenseno
                FROM ipt_oper io
                LEFT JOIN doctor doc ON doc.code = io.doctor
                WHERE io.an = ?
            ", [$an]);

            // 4. Fetch IPD Items (opitemrece by AN)
            $items = DB::connection('hosxp')->select("
                SELECT op.icode, op.qty, op.unitprice, op.sum_price, op.income, op.paidst,
                       COALESCE(nd.nhso_adp_type_id, d.nhso_adp_type_id) AS nhso_adp_type,
                       COALESCE(nd.nhso_adp_code, d.nhso_adp_code) AS nhso_adp_code,
                       nd.name AS item_name,
                       d.did AS drug_did, d.name AS drug_name, d.units
                FROM opitemrece op
                LEFT JOIN nondrugitems nd ON nd.icode = op.icode
                LEFT JOIN drugitems d ON d.icode = op.icode
                WHERE op.an = ?
            ", [$an]);

            // 5. Fetch IPD Refer
            $referOut = DB::connection('hosxp')->selectOne("
                SELECT ro.refer_number, COALESCE(NULLIF(ro.refer_hospcode, ''), ro.hospcode) as refer_hospcode, ro.refer_date, ro.refer_time
                FROM referout ro WHERE ro.an = ? OR ro.vn = ? LIMIT 1
            ", [$an, $admission->vn]);

            $referIn = DB::connection('hosxp')->selectOne("
                SELECT ri.docno as refer_number, COALESCE(NULLIF(ri.refer_hospcode, ''), ri.hospcode) as refer_hospcode, ri.refer_date, ri.refer_time
                FROM referin ri WHERE ri.an = ? OR ri.vn = ? LIMIT 1
            ", [$an, $admission->vn]);

            return self::evaluateRules(
                $admission,
                $diags,
                $procedures,
                $items,
                $referOut,
                $referIn,
                null,
                'IPD',
                $context
            );

        } catch (\Throwable $e) {
            Log::error("ClaimPreAuditService::auditAdmission error: " . $e->getMessage());
            return self::errorResult($e->getMessage());
        }
    }

    /**
     * Core Rule Engine evaluation
     */
    protected static function evaluateRules(
        $visit,
        array $diags,
        array $procedures,
        array $items,
        $referOut,
        $referIn,
        ?string $endpointStatus,
        string $visitType,
        array $context = []
    ): array {
        $issues = [];
        $vstdate = $visit->vstdate ?? null;

        // =========================================================================
        // 1. หมวด AER (Accident, Emergency, Refer)
        // =========================================================================
        $isErEmergency = (!empty($visit->er_pt_type) && $visit->er_pt_type == 2) || in_array((string)($visit->er_emergency_type ?? ''), ['1', '2']);
        $hasReferOut = !empty($referOut);
        $hasReferIn = !empty($referIn);
        $ucae = strtoupper(trim((string)($visit->ucae ?? ($visit->ipt_ac_ae ?? ''))));
        $isUcae = in_array($ucae, ['A', 'E', 'I', 'O', 'C', 'Z']);

        // Rule C851 และ C852: ตรวจเฉพาะเคสที่เป็นอุบัติเหตุฉุกเฉินจริง (ER Emergency หรือเบิก A/E)
        if ($isErEmergency || $isUcae) {
            $aedate = $visit->er_vstdate ?: ($referOut->refer_date ?? ($referIn->refer_date ?? $vstdate));
            $aetime = $visit->er_time ?: ($referOut->refer_time ?? ($referIn->refer_time ?? $visit->vsttime));

            // Rule C851: วันที่เกิดอุบัติเหตุก่อน admit 20 วันขึ้นไป หรือหลังวัน Admit/ตรวจ
            if (!empty($aedate) && !empty($vstdate)) {
                $vstTimestamp = strtotime($vstdate);
                $aeTimestamp = strtotime($aedate);
                $diffDays = ($vstTimestamp - $aeTimestamp) / 86400;

                if ($aeTimestamp > $vstTimestamp) {
                    $info = self::lookupCode('851', 'วันที่เกิดอุบัติเหตุก่อน admit 20 วันขึ้นไป หรือหลังวัน Admit', 'ตรวจสอบและบันทึกวันและเวลาที่เกิดอุบัติเหตุในห้องฉุกเฉิน (ER) ให้ถูกต้อง แล้วส่งเข้ามาใหม่');
                    $issues[] = [
                        'code' => '851',
                        'type' => 'C-Code',
                        'severity' => 'danger',
                        'file' => 'AER',
                        'title' => "ข้อผิดพลาด 851: วันที่เกิดเหตุ ({$aedate}) อยู่หลังวันที่รับบริการ ({$vstdate})",
                        'description' => $info['description'],
                        'guide' => $info['guide'],
                        'location' => 'ห้องฉุกเฉิน (ER Register) -> วันที่เกิดเหตุ'
                    ];
                } elseif ($diffDays > 20) {
                    $info = self::lookupCode('851', 'วันที่เกิดอุบัติเหตุก่อน admit 20 วันขึ้นไป หรือหลังวัน Admit', 'ตรวจสอบและบันทึกวันและเวลาที่เกิดอุบัติเหตุในห้องฉุกเฉิน (ER) ให้ถูกต้อง แล้วส่งเข้ามาใหม่');
                    $issues[] = [
                        'code' => '851',
                        'type' => 'C-Code',
                        'severity' => 'danger',
                        'file' => 'AER',
                        'title' => "ข้อผิดพลาด 851: วันที่เกิดอุบัติเหตุ ({$aedate}) ย้อนหลังเกิน 20 วัน (" . intval($diffDays) . " วัน) นับจากวันรับบริการ",
                        'description' => $info['description'],
                        'guide' => $info['guide'],
                        'location' => 'ห้องฉุกเฉิน (ER Register) -> บันทึกข้อมูลอุบัติเหตุ/วันเกิดเหตุ'
                    ];
                }
            }

            // Rule C852: ไม่ระบุเวลาเกิดเหตุหรือเวลารับบริการฉุกเฉิน
            if (empty($aetime)) {
                $info = self::lookupCode('852', 'ไม่ระบุเวลาที่เกิดอุบัติเหตุ/ฉุกเฉิน', 'บันทึกเวลาที่เกิดอุบัติเหตุหรือเวลาที่เข้ารับบริการฉุกเฉินให้ถูกต้อง');
                $issues[] = [
                    'code' => '852',
                    'type' => 'C-Code',
                    'severity' => 'warning',
                    'file' => 'AER',
                    'title' => 'ข้อผิดพลาด 852: ไม่ระบุเวลาที่เกิดเหตุ (AETIME ว่าง)',
                    'description' => $info['description'],
                    'guide' => $info['guide'],
                    'location' => 'ห้องฉุกเฉิน (ER Register) -> เวลาเกิดเหตุ / เวลาเข้าตรวจ ER'
                ];
            }
        }

        // Rule C853: รหัสสถานพยาบาลส่งต่อ (Refer Hospcode) ไม่ถูกต้องหรือไม่ครบ 5 หลัก (ตรวจทุกเคสที่มี Refer)
        if ($hasReferOut && !empty($referOut->refer_hospcode) && strlen(trim($referOut->refer_hospcode)) < 5) {
            $info = self::lookupCode('853', 'รหัสสถานพยาบาลที่ส่งต่อไม่ถูกต้อง', 'ตรวจสอบรหัสสถานพยาบาลส่งต่อให้ถูกต้อง 5 หลัก');
            $issues[] = [
                'code' => '853',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'AER',
                'title' => "ข้อผิดพลาด 853: รหัสสถานพยาบาลส่งต่อออก (Refer Out) ไม่ถูกต้อง ('{$referOut->refer_hospcode}')",
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'ระบบส่งต่อ (Refer Out) -> รหัสสถานพยาบาลปลายทาง'
            ];
        }

        // =========================================================================
        // 2. หมวด PAT / INS (ข้อมูลบุคคลและสิทธิการรักษา)
        // =========================================================================
        $cid = trim((string)($visit->cid ?? ''));
        if (empty($cid)) {
            $info = self::lookupCode('010', 'ไม่มีเลขประจำตัวประชาชน 13 หลัก', 'ตรวจสอบและบันทึกเลขประจำตัวประชาชน 13 หลักของผู้ป่วย');
            $issues[] = [
                'code' => '010',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'PAT',
                'title' => 'ข้อผิดพลาด 010: ไม่มีเลขประจำตัวประชาชน (CID ว่าง)',
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'เวชระเบียน / ทะเบียนประวัติผู้ป่วย (Patient)'
            ];
        } elseif (!self::validateThaiCid($cid)) {
            $isAlien = in_array(substr($cid, 0, 1), ['0', '6', '7', '8']);
            if (!$isAlien && strlen($cid) === 13) {
                $info = self::lookupCode('010', 'เลขประจำตัวประชาชนไม่ถูกต้องตามหลักการคำนวณ', 'ตรวจสอบความถูกต้องของเลขประจำตัวประชาชน 13 หลัก');
                $issues[] = [
                    'code' => '010',
                    'type' => 'C-Code',
                    'severity' => 'danger',
                    'file' => 'PAT',
                    'title' => "ข้อผิดพลาด 010: เลขประจำตัวประชาชน ({$cid}) ไม่ถูกต้อง (Check Digit ผิด)",
                    'description' => $info['description'],
                    'guide' => $info['guide'],
                    'location' => 'เวชระเบียน (Patient) -> เลขบัตรประชาชน'
                ];
            }
        }

        // วันเดือนปีเกิด
        if (empty($visit->birthday)) {
            $issues[] = [
                'code' => '011',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'PAT',
                'title' => 'ข้อผิดพลาด 011: ไม่ระบุวันเดือนปีเกิด (DOB ว่าง)',
                'description' => 'ข้อมูลวันเดือนปีเกิดของผู้ป่วยเป็นค่าว่าง',
                'guide' => 'บันทึกวันเดือนปีเกิดของผู้ป่วยในเวชระเบียน',
                'location' => 'เวชระเบียน (Patient) -> วันเดือนปีเกิด'
            ];
        }

        // เพศ
        if (!in_array((string)$visit->sex, ['1', '2', 'M', 'F'])) {
            $issues[] = [
                'code' => '012',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'PAT',
                'title' => 'ข้อผิดพลาด 012: ไม่ระบุเพศของผู้ป่วย',
                'description' => 'เพศของผู้ป่วยไม่ถูกต้อง ต้องระบุเป็น 1 (ชาย) หรือ 2 (หญิง)',
                'guide' => 'ระบุเพศของผู้ป่วยในเวชระเบียนให้ถูกต้อง',
                'location' => 'เวชระเบียน (Patient) -> เพศ'
            ];
        }

        // สิทธิการรักษา
        if (empty($visit->pttype)) {
            $issues[] = [
                'code' => '013',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'INS',
                'title' => 'ข้อผิดพลาด 013: ไม่ระบุสิทธิการรักษา (Pttype ว่าง)',
                'description' => 'ไม่มีการระบุสิทธิการรักษาในการเข้ารับบริการครั้งนี้',
                'guide' => 'ระบุสิทธิการรักษาในหน้าลงทะเบียนส่งตรวจ',
                'location' => 'ส่งตรวจ OPD / ลงทะเบียนผู้ป่วยใน'
            ];
        }

        // =========================================================================
        // 3. หมวด ODX / IPDX (การวินิจฉัยโรค)
        // =========================================================================
        $diagTypes = [];
        $icdCodes = [];
        $hasPdx = false;
        $hasExtCause = false;
        $hasInjury = false;

        foreach ($diags as $d) {
            $icd = strtoupper(trim((string)$d->icd10));
            $dtype = (string)$d->diagtype;
            if ($icd === '') continue;

            $icdCodes[] = $icd;
            $diagTypes[$dtype][] = $icd;

            if ($dtype === '1') {
                $hasPdx = true;
            }

            $firstChar = substr($icd, 0, 1);
            if (in_array($firstChar, ['V', 'W', 'X', 'Y'])) {
                $hasExtCause = true;
            }
            if (in_array($firstChar, ['S', 'T'])) {
                $hasInjury = true;
            }
        }

        // Rule C201: ไม่มี Principle Diagnosis (Diag Type 1)
        if (!$hasPdx) {
            $info = self::lookupCode('201', 'ไม่มีรหัสการวินิจฉัยโรคหลัก (Principle Diagnosis)', 'บันทึกการวินิจฉัยโรคหลัก (Diag Type 1) ให้ครบถ้วน');
            $issues[] = [
                'code' => '201',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'ODX',
                'title' => 'ข้อผิดพลาด 201: ไม่มีรหัสการวินิจฉัยหลัก (ขาด Diag Type 1)',
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'ห้องตรวจแพทย์ / บันทึกผลวินิจฉัยโรค -> เพิ่มรหัสโรคประเภท 1 (Principle Diag)'
            ];
        }

        // Rule C202: รหัสโรคซ้ำซ้อนใน Visit เดียวกัน
        $duplicateIcds = array_diff_assoc($icdCodes, array_unique($icdCodes));
        if (!empty($duplicateIcds)) {
            $dupList = implode(', ', array_unique($duplicateIcds));
            $info = self::lookupCode('202', 'รหัสการวินิจฉัยโรคซ้ำซ้อน', 'ตรวจสอบและลบรหัสโรคที่ลงซ้ำกันออก');
            $issues[] = [
                'code' => '202',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'ODX',
                'title' => "ข้อผิดพลาด 202: พบรหัสโรคซ้ำซ้อนใน Visit เดียวกัน ({$dupList})",
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'บันทึกผลการวินิจฉัยโรค -> ลบรหัสโรคที่ซ้ำออก'
            ];
        }

        // Rule C204: มีรหัส External Cause (V-Y) แต่ไม่มีรหัส Injury (S-T)
        if ($hasExtCause && !$hasInjury) {
            $info = self::lookupCode('204', 'มีรหัสสาเหตุภายนอกแต่ไม่มีรหัสการบาดเจ็บ', 'กรณีลงรหัสสาเหตุภายนอก (V, W, X, Y) ต้องระบุรหัสการบาดเจ็บบาดแผล (S00-T98) ด้วย');
            $issues[] = [
                'code' => '204',
                'type' => 'C-Code',
                'severity' => 'warning',
                'file' => 'ODX',
                'title' => 'ข้อควรระวัง 204: มีรหัสสาเหตุอุบัติเหตุ (External Cause) แต่ไม่มีรหัสการบาดเจ็บ (S-T)',
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'บันทึกผลการวินิจฉัยโรค -> เพิ่มรหัสโรคกลุ่มการบาดเจ็บ (S00-T98)'
            ];
        }

        // =========================================================================
        // 4. หมวด OOP / IPOT (หัตถการ)
        // =========================================================================
        foreach ($procedures as $p) {
            if (empty($p->doctor) && empty($p->licenseno)) {
                $issues[] = [
                    'code' => '210',
                    'type' => 'C-Code',
                    'severity' => 'warning',
                    'file' => 'OOP',
                    'title' => "ข้อผิดพลาด 210: หัตถการ ICD-9 ({$p->icd9}) ไม่ได้ระบุแพทย์ผู้ทำหัตถการ",
                    'description' => 'การเบิกค่าหัตถการต้องระบุรหัสแพทย์ผู้ทำหัตถการและเลขที่ใบประกอบวิชาชีพ',
                    'guide' => 'ระบุแพทย์ผู้ทำหัตถการในหน้าบันทึกหัตถการ',
                    'location' => 'บันทึกหัตถการ (Operation / Procedure)'
                ];
                break;
            }
        }

        // =========================================================================
        // 5. หมวด ADP (ค่าบริการ / หัตถการ / อุปกรณ์)
        // =========================================================================
        $missingAdpCodes = [];
        $zeroQtyItems = [];

        foreach ($items as $it) {
            $isDrug = str_starts_with((string)$it->icode, '1') || !empty($it->drug_name);
            $qty = (float)$it->qty;

            if ($qty <= 0) {
                $zeroQtyItems[] = $it->item_name ?: ($it->drug_name ?: $it->icode);
            }

            if (!$isDrug) {
                $rawCode = trim((string)($it->nhso_adp_code ?? ''));
                if (empty($rawCode) || $rawCode === 'XXXXXX') {
                    $missingAdpCodes[] = $it->item_name ?: $it->icode;
                }
            }
        }

        if (!empty($missingAdpCodes)) {
            $sampleList = implode(', ', array_slice(array_unique($missingAdpCodes), 0, 3));
            $more = count($missingAdpCodes) > 3 ? " และอีก " . (count($missingAdpCodes) - 3) . " รายการ" : "";
            $info = self::lookupCode('300', 'รายการที่ขอเบิกไม่อยู่ในเงื่อนไข หรือยังไม่จับคู่รหัสมาตรฐาน', 'ตรวจสอบและจับคู่รหัส NHSO ADP Code / กรมบัญชีกลาง ให้ถูกต้อง');
            $issues[] = [
                'code' => '300',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'ADP',
                'title' => "ข้อผิดพลาด 300: รายการค่าบริการยังไม่ได้จับคู่รหัสมาตรฐาน NHSO/กรมบัญชีกลาง ({$sampleList}{$more})",
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'ตั้งค่ารายการค่ารักษา (Non-drug items) -> NHSO ADP Code'
            ];
        }

        if (!empty($zeroQtyItems)) {
            $sampleList = implode(', ', array_slice(array_unique($zeroQtyItems), 0, 3));
            $info = self::lookupCode('303', 'จำนวนที่ขอเบิกเป็น 0 หรือว่าง', 'ตรวจสอบและระบุจำนวนที่ขอเบิกให้ถูกต้อง');
            $issues[] = [
                'code' => '303',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'ADP',
                'title' => "ข้อผิดพลาด 303: รายการค่าบริการมีจำนวน (Qty) เป็น 0 หรือติดลบ ({$sampleList})",
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'สั่งการรักษา / คิดค่าบริการ'
            ];
        }

        // =========================================================================
        // 6. หมวด DRU (ยาและเวชภัณฑ์)
        // =========================================================================
        $missingTmtDrugs = [];
        foreach ($items as $it) {
            $isDrug = str_starts_with((string)$it->icode, '1') || !empty($it->drug_name);
            if ($isDrug) {
                $did = trim((string)($it->drug_did ?? ''));
                if (empty($did) || strlen($did) < 24) {
                    $missingTmtDrugs[] = $it->drug_name ?: $it->icode;
                }
            }
        }

        if (!empty($missingTmtDrugs)) {
            $sampleList = implode(', ', array_slice(array_unique($missingTmtDrugs), 0, 3));
            $more = count($missingTmtDrugs) > 3 ? " และอีก " . (count($missingTmtDrugs) - 3) . " รายการ" : "";
            $info = self::lookupCode('401', 'รหัสยา 24 หลัก (TMT) ไม่ครบถ้วนหรือไม่ถูกต้อง', 'ตรวจสอบรหัสยามาตรฐาน 24 หลักใน Drug Catalog ให้ครบถ้วน');
            $issues[] = [
                'code' => '401',
                'type' => 'C-Code',
                'severity' => 'danger',
                'file' => 'DRU',
                'title' => "ข้อผิดพลาด 401: รายการยายังไม่มีรหัสมาตรฐาน 24 หลัก TMT ({$sampleList}{$more})",
                'description' => $info['description'],
                'guide' => $info['guide'],
                'location' => 'คลังยา / ข้อมูลรายการยา (Drug Catalog) -> รหัส 24 หลัก (TMT)'
            ];
        }

        // =========================================================================
        // 7. หมวดเฉพาะสิทธิ (Scheme Specific Rules: UCS Authen / OFC etc.)
        // =========================================================================
        $hipCode = strtoupper(trim((string)($visit->hipdata_code ?? '')));
        if ($hipCode === 'UCS' || str_contains(strtoupper((string)($visit->pttype_name ?? '')), 'บัตรทอง') || str_contains(strtoupper((string)($visit->pttype_name ?? '')), 'UC')) {
            $hasAuthen = !empty($visit->auth_code) || !empty($visit->claim_code) || !empty($endpointStatus);
            if (!$hasAuthen) {
                $issues[] = [
                    'code' => 'AUTH_WARN',
                    'type' => 'Warning',
                    'severity' => 'warning',
                    'file' => 'INS',
                    'title' => 'คำเตือน: ยังไม่พบการปิดสิทธิ / ขอ Authen Code สปสช. (Endpoint)',
                    'description' => 'ผู้ป่วยสิทธิบัตรทอง (UCS) ควรทำการขอ Authen Code หรือปิดสิทธิ สปสช. ก่อนส่งเบิก',
                    'guide' => 'ดำเนินการกดดึงข้อมูล Authen Code หรือปิดสิทธิที่หน้าตรวจสอบสิทธิ สปสช.',
                    'location' => 'ระบบตรวจสอบสิทธิ สปสช. (NHSO Endpoint)'
                ];
            }
        }

        // Calculate Final Status
        $dangerCount = 0;
        $warningCount = 0;

        foreach ($issues as $iss) {
            if ($iss['severity'] === 'danger') {
                $dangerCount++;
            } else {
                $warningCount++;
            }
        }

        $isValid = ($dangerCount === 0);
        $status = $dangerCount > 0 ? 'FAIL' : ($warningCount > 0 ? 'WARN' : 'PASS');
        $badge = $dangerCount > 0 ? 'danger' : ($warningCount > 0 ? 'warning' : 'success');

        return [
            'status' => $status,
            'badge' => $badge,
            'is_valid' => $isValid,
            'summary' => [
                'errors' => $dangerCount,
                'warnings' => $warningCount,
                'total' => count($issues)
            ],
            'visit_info' => [
                'hn' => $visit->hn ?? '',
                'vn' => $visit->vn ?? ($visit->an ?? ''),
                'ptname' => trim(($visit->pname ?? '') . ($visit->fname ?? '') . ' ' . ($visit->lname ?? '')),
                'cid' => $visit->cid ?? '',
                'vstdate' => $visit->vstdate ?? '',
                'vsttime' => $visit->vsttime ?? '',
                'pttype' => $visit->pttype ?? '',
                'pttype_name' => $visit->pttype_name ?? '',
                'income' => (float)($visit->income ?? 0)
            ],
            'issues' => $issues
        ];
    }

    /**
     * Batch Audit for Table Lists (Fast Pre-check for Multiple Visits)
     */
    public static function auditBatchVisits(array $vns): array
    {
        if (empty($vns)) return [];

        $results = [];
        foreach ($vns as $vn) {
            $results[$vn] = self::auditVisit((string)$vn);
        }
        return $results;
    }

    /**
     * Empty result helper
     */
    protected static function emptyResult(): array
    {
        return [
            'status' => 'PASS',
            'badge' => 'success',
            'is_valid' => true,
            'summary' => ['errors' => 0, 'warnings' => 0, 'total' => 0],
            'issues' => []
        ];
    }

    /**
     * Error result helper
     */
    protected static function errorResult(string $message): array
    {
        return [
            'status' => 'FAIL',
            'badge' => 'danger',
            'is_valid' => false,
            'summary' => ['errors' => 1, 'warnings' => 0, 'total' => 1],
            'issues' => [
                [
                    'code' => 'SYS_ERROR',
                    'type' => 'Error',
                    'severity' => 'danger',
                    'file' => 'SYS',
                    'title' => 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูล',
                    'description' => $message,
                    'guide' => 'กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ',
                    'location' => 'ระบบ Pre-Audit'
                ]
            ]
        ];
    }
}
