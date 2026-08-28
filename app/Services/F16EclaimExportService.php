<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class F16EclaimExportService
{
    /**
     * ดึงรหัสสถานพยาบาล 5 หลัก (HCODE)
     */
    public static function getHcode()
    {
        return LicenseVerificationService::getHcode();
    }

    /**
     * จัดรูปแบบวันที่เป็น YYYYMMDD
     */
    private static function formatDate($dateStr)
    {
        if (empty($dateStr)) return '';
        $time = strtotime($dateStr);
        if (!$time) return '';
        return date('Ymd', $time);
    }

    /**
     * จัดรูปแบบเวลาเป็น HHMM (4 หลัก)
     */
    private static function formatTime($timeStr)
    {
        if (empty($timeStr)) return '';
        $cleaned = str_replace(':', '', trim($timeStr));
        if (strlen($cleaned) >= 4) {
            return substr($cleaned, 0, 4);
        }
        return str_pad($cleaned, 4, '0', STR_PAD_RIGHT);
    }

    /**
     * Format clinic code to 5 digits (e.g. 01200, 01400, 00100)
     */
    private static function formatClinic($spclty)
    {
        $sp = trim((string)$spclty);
        if (empty($sp)) return '00100';
        $spPad = str_pad($sp, 2, '0', STR_PAD_LEFT);
        return '0' . $spPad . '00';
    }

    /**
     * Map รหัสหมวดรายได้ HOSxP (income) ให้เป็นรหัส 16 หมวด สปสช. (CHA CHRGITEM)
     */
    private static function mapIncomeToChaItem($income)
    {
        $inc = str_pad(trim((string)$income), 2, '0', STR_PAD_LEFT);
        switch ($inc) {
            case '01': return '11'; // ค่าห้อง/ค่าอาหาร
            case '02': return '11'; // ค่าอาหาร
            case '03': return '41'; // ค่ายาในบัญชี
            case '04': return '42'; // ค่ายานอกบัญชี
            case '17': return '41'; // ยาอื่นๆ
            case '05': return '51'; // ค่าเวชภัณฑ์มิใช่ยา
            case '06': return '31'; // ค่าบริการโลหิต
            case '07': return '71'; // ค่าตรวจวินิจฉัยทางเทคนิคการแพทย์และพยาธิวิทยา
            case '08': return '81'; // ค่าตรวจวินิจฉัยและรักษาทางรังสีวิทยา
            case '09': return '91'; // ค่าตรวจวินิจฉัยโดยวิธีพิเศษอื่นๆ
            case '10': return 'A1'; // ค่าอุปกรณ์ของใช้และเครื่องมือทางการแพทย์
            case '11': return 'B1'; // ค่าทำหัตถการและวิสัญญี
            case '12': return 'C1'; // ค่าบริการทางการพยาบาล
            case '18': return 'C1'; // ค่าบริการทางการพยาบาล
            case '13': return 'D1'; // ค่าบริการทางทันตกรรม
            case '14': return 'E1'; // ค่าบริการทางกายภาพบำบัด
            case '15': return 'F1'; // ค่าบริการการแพทย์แผนไทย
            case '16': return 'G1'; // ค่าบริการอื่นๆ
            default:   return 'H1'; // เบ็ดเตล็ด
        }
    }

    /**
     * Map fund code to CHT PTTYPE (A2, A7, UC, SS, etc.)
     */
    private static function mapChtPttype($hipdataCode, $pttype)
    {
        $hip = strtoupper(trim((string)$hipdataCode));
        $ptt = strtoupper(trim((string)$pttype));

        if ($hip === 'OFC' || str_starts_with($ptt, 'O') || $hip === 'A2') {
            return 'A2';
        }
        if ($hip === 'LGO' || str_starts_with($ptt, 'L') || $hip === 'A7') {
            return 'A7';
        }
        if ($hip === 'UCS' || $hip === 'UC' || str_starts_with($ptt, 'U')) {
            return 'UC';
        }
        if ($hip === 'SSS' || $hip === 'SS' || str_starts_with($ptt, 'S')) {
            return 'SS';
        }
        if ($hip === 'PRO' || $hip === 'B1') {
            return 'B1';
        }
        return $hip ?: 'A2';
    }

    /**
     * Map รหัสการตรวจทางห้องปฏิบัติการ (LABTEST 2 หลัก) ตามมาตรฐาน สปสช. / e-Claim (แฟ้ม LAB / LABFU)
     */
    private static function mapLabTestCode($labtest, $tmltCode, $provisCode, $labName)
    {
        $lt = trim((string)$labtest);
        if (!empty($lt) && is_numeric($lt) && intval($lt) > 0 && intval($lt) <= 28) {
            return str_pad($lt, 2, '0', STR_PAD_LEFT);
        }

        $tmlt = trim((string)$tmltCode);
        $name = strtolower(trim((string)$labName));
        $provis = trim((string)$provisCode);

        // 05 = HbA1C
        if (str_contains($name, 'hba1c') || str_contains($name, 'a1c') || $tmlt === '320008' || $provis === '0531202') {
            return '05';
        }
        // 11 = Creatinine in blood
        if (str_contains($name, 'creatinine') && !str_contains($name, 'urine') || $tmlt === '320055' || $provis === '0581902') {
            return '11';
        }
        // 15 = eGFR
        if (str_contains($name, 'egfr') || str_contains($name, 'gfr')) {
            return '15';
        }
        // 10 = BUN
        if (str_contains($name, 'bun') || $tmlt === '320052' || $provis === '0584902') {
            return '10';
        }
        // 09 = LDL Cholesterol
        if (str_contains($name, 'ldl') || $tmlt === '320073' || $provis === '0541402') {
            return '09';
        }
        // 08 = HDL Cholesterol
        if (str_contains($name, 'hdl') || $tmlt === '320072' || $provis === '0541302') {
            return '08';
        }
        // 07 = Total Cholesterol
        if (str_contains($name, 'cholesterol') || str_contains($name, 'chol') || $tmlt === '320070' || $provis === '0541102') {
            return '07';
        }
        // 06 = Triglyceride
        if (str_contains($name, 'triglyceride') || str_contains($name, 'tg') || $tmlt === '320071' || $provis === '0541202') {
            return '06';
        }
        // 01 / 04 = Blood Sugar / Glucose / DTX
        if (str_contains($name, 'fbs') || str_contains($name, 'fasting') || str_contains($name, 'glucose')) {
            return '01';
        }
        if (str_contains($name, 'dtx') || str_contains($name, 'bedside')) {
            return '04';
        }
        // 12 = Microalbumin in urine
        if (str_contains($name, 'microalbumin') || str_contains($name, 'malb')) {
            return '12';
        }
        // 13 = Creatinine in urine
        if (str_contains($name, 'urine creat') || str_contains($name, 'u-creat')) {
            return '13';
        }
        // 14 = Macroalbumin in urine
        if (str_contains($name, 'urine protein') || str_contains($name, 'macroalbumin')) {
            return '14';
        }
        // 16 = Hb (Hemoglobin)
        if (str_contains($name, 'hemoglobin') || $name === 'hb' || $tmlt === '320005') {
            return '16';
        }
        // 17 = UPCR (Urine protein creatinine ratio)
        if (str_contains($name, 'upcr')) {
            return '17';
        }
        // 18 = Potassium (K)
        if (str_contains($name, 'potassium') || $name === 'k' || $tmlt === '320033' || $provis === '0511702') {
            return '18';
        }
        // 19 = Bicarbonate (CO2 / Bicarb)
        if (str_contains($name, 'bicarbonate') || str_contains($name, 'bicarb') || str_contains($name, 'co2')) {
            return '19';
        }
        // 20 = Phosphorus / Phosphate
        if (str_contains($name, 'phosphorus') || str_contains($name, 'phosphate') || $tmlt === '320022' || $provis === '0511202') {
            return '20';
        }
        // 21 = PTH (Parathyroid hormone)
        if (str_contains($name, 'pth') || str_contains($name, 'parathyroid')) {
            return '21';
        }

        return !empty($lt) ? str_pad($lt, 2, '0', STR_PAD_LEFT) : '';
    }

    /**
     * ประมวลผลและสร้างเนื้อหา 16/17 แฟ้ม จากรายการ VNs ที่เลือก
     *
     * @param array $vns รายการ VN
     * @param array $options ตัวเลือกเพิ่มเติม
     * @return array [ 'files' => [ 'INS' => '...', 'PAT' => '...', ... ], 'counts' => [ 'INS' => 45, ... ], 'hcode' => '10989' ]
     */
    public static function generate16Files(array $vns, array $options = []): array
    {
        if (empty($vns)) {
            return [
                'files' => [],
                'counts' => [],
                'total_visits' => 0,
                'hcode' => self::getHcode()
            ];
        }

        $hcode = self::getHcode();
        $placeholders = implode(',', array_fill(0, count($vns), '?'));

        // -------------------------------------------------------------
        // 1. Query Visit Details (ovst, vn_stat, patient, pttype, doctor, spclty, ovst_seq, visit_pttype)
        // -------------------------------------------------------------
        $visits = collect();
        try {
            $visitRows = DB::connection('hosxp')->select("
                SELECT o.vn, o.vn as seq, o.hn, o.an, o.vstdate, o.vsttime, o.spclty, o.main_dep, o.cur_dep,
                       o.pttype, v.pdx, v.dx_doctor, v.income, v.paid_money, v.rcpt_money, v.uc_money,
                       pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex, pt.marrystatus, pt.occupation, pt.nationality,
                       pt.chwpart, pt.amppart, pt.tmbpart,
                       p.hipdata_code,
                       COALESCE(p.nhso_code, o.pt_subtype, 'O1') as pttype_nhso_code,
                       doc.licenseno as doctor_license, doc.name as doctor_name,
                       o.pt_subtype,
                       COALESCE(vp.hospmain, '') as hospmain,
                       COALESCE(vp.hospsub, '') as hospsub,
                       COALESCE(oq.edc_approve_list_text, vp.claim_code, vp.auth_code, '') as permitno,
                       vp.auth_code,
                       vp.claim_code,
                       oq.edc_approve_list_text,
                       '' as gov_code,
                       '' as gov_name
                FROM ovst o
                LEFT JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN patient pt ON pt.hn = o.hn
                LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                LEFT JOIN pttype p ON p.pttype = COALESCE(vp.pttype, o.pttype)
                LEFT JOIN ovst_seq oq ON oq.vn = o.vn
                LEFT JOIN doctor doc ON doc.code = o.doctor
                WHERE o.vn IN ($placeholders)
                ORDER BY o.vstdate, o.vsttime
            ", $vns);
            $visits = collect($visitRows);
        } catch (\Throwable $e) {
            // Fallback without ovst_seq
            try {
                $visitRows = DB::connection('hosxp')->select("
                    SELECT o.vn, o.vn as seq, o.hn, o.an, o.vstdate, o.vsttime, o.spclty, o.main_dep, o.cur_dep,
                           o.pttype, v.pdx, v.dx_doctor, v.income, v.paid_money, v.rcpt_money, v.uc_money,
                           pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex, pt.marrystatus, pt.occupation, pt.nationality,
                           pt.chwpart, pt.amppart, pt.tmbpart,
                           p.hipdata_code,
                           COALESCE(p.nhso_code, o.pt_subtype, 'O1') as pttype_nhso_code,
                           doc.licenseno as doctor_license, doc.name as doctor_name,
                           o.pt_subtype,
                           COALESCE(vp.hospmain, '') as hospmain,
                           COALESCE(vp.hospsub, '') as hospsub,
                           COALESCE(vp.claim_code, vp.auth_code, '') as permitno,
                           vp.auth_code,
                           vp.claim_code,
                           '' as edc_approve_list_text,
                           '' as gov_code,
                           '' as gov_name
                    FROM ovst o
                    LEFT JOIN vn_stat v ON v.vn = o.vn
                    LEFT JOIN patient pt ON pt.hn = o.hn
                    LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                    LEFT JOIN pttype p ON p.pttype = COALESCE(vp.pttype, o.pttype)
                    LEFT JOIN doctor doc ON doc.code = o.doctor
                    WHERE o.vn IN ($placeholders)
                    ORDER BY o.vstdate, o.vsttime
                ", $vns);
                $visits = collect($visitRows);
            } catch (\Throwable $e2) {
                Log::error("F16 Export main visit query error: " . $e2->getMessage());
                throw $e2;
            }
        }
        $vnsList = array_values($visits->pluck('vn')->toArray());
        $hnsList = array_values($visits->pluck('hn')->unique()->toArray());
        $ansList = array_values($visits->pluck('an')->filter()->unique()->toArray());

        // -------------------------------------------------------------
        // PERMITNO Logic:
        // - สิทธิ OFC: ใช้เลข EDC (ktb_edc_transaction / edc_approve_list / rcpt_debt / ovst_seq / claim_code)
        // - สิทธิอื่น (UCS, SSS, LGO ฯลฯ): ใช้เลข Authen (auth_code) หากไม่พบให้ใช้เลขปิดสิทธิ (claim_code)
        // -------------------------------------------------------------
        $cids = $visits->pluck('cid')->filter()->unique()->toArray();
        $edcRows = collect();
        if (!empty($cids)) {
            try {
                $edcRows = DB::table('edc_approve_list')
                    ->whereIn('cid', $cids)
                    ->select('cid', 'vstdate', 'approve_code')
                    ->get()
                    ->groupBy(function ($r) {
                        return $r->cid . '_' . $r->vstdate;
                    });
            } catch (\Throwable $ex) {
                Log::warning("Could not load EDC approve list from local DB: " . $ex->getMessage());
            }
        }

        // Check KTB EDC transaction from HOSxP if exists
        $ktbEdcRows = collect();
        if (!empty($vnsList)) {
            try {
                $ktbEdcRows = collect(DB::connection('hosxp')->select("
                    SELECT vn, approve_code FROM ktb_edc_transaction WHERE vn IN ($placeholders)
                ", $vnsList))->keyBy('vn');
            } catch (\Throwable $ex) {}
        }

        // Check rcpt_debt approve_code from HOSxP if exists
        $rcptDebtRows = collect();
        if (!empty($vnsList)) {
            try {
                $rcptDebtRows = collect(DB::connection('hosxp')->select("
                    SELECT vn, approve_code FROM rcpt_debt WHERE vn IN ($placeholders) AND approve_code IS NOT NULL AND approve_code != ''
                ", $vnsList))->keyBy('vn');
            } catch (\Throwable $ex) {}
        }

        $visits->transform(function ($v) use ($edcRows, $ktbEdcRows, $rcptDebtRows) {
            $hip = strtoupper(trim((string)$v->hipdata_code));
            $ptt = strtoupper(trim((string)$v->pttype));
            $isOfc = ($hip === 'OFC' || $hip === 'A2' || str_starts_with($ptt, 'O'));

            if ($isOfc) {
                // OFC: ใช้เลข EDC
                $edc = '';
                if ($ktbEdcRows->has($v->vn)) {
                    $edc = trim((string)$ktbEdcRows->get($v->vn)->approve_code);
                }
                if (empty($edc) && $rcptDebtRows->has($v->vn)) {
                    $edc = trim((string)$rcptDebtRows->get($v->vn)->approve_code);
                }
                if (empty($edc)) {
                    $edc = trim((string)($v->edc_approve_list_text ?? ''));
                }
                if (empty($edc)) {
                    $key = $v->cid . '_' . $v->vstdate;
                    if (isset($edcRows[$key]) && count($edcRows[$key]) > 0) {
                        $edc = $edcRows[$key]->first()->approve_code;
                    }
                }
                $v->permitno = $edc ?: (trim((string)($v->claim_code ?? '')));
            } else {
                // สิทธิอื่นๆ (UCS, SSS, LGO ฯลฯ): ใช้เลข Authen ถ้าไม่พบใช้เลขปิดสิทธิ (claim_code)
                $v->permitno = trim((string)($v->auth_code ?: ($v->claim_code ?: '')));
            }
            return $v;
        });

        // -------------------------------------------------------------
        // 2. Query OPD Diag (ovstdiag)
        // -------------------------------------------------------------
        $opdDiags = collect();
        if (!empty($vnsList)) {
            try {
                $diagRows = DB::connection('hosxp')->select("
                    SELECT od.vn, od.hn, od.vstdate, od.icd10, od.diagtype, od.doctor,
                           COALESCE(doc.licenseno, od.doctor) as doctor_license,
                           pt.cid, o.spclty
                    FROM ovstdiag od
                    LEFT JOIN ovst o ON o.vn = od.vn
                    LEFT JOIN patient pt ON pt.hn = od.hn
                    LEFT JOIN doctor doc ON doc.code = od.doctor
                    WHERE od.vn IN ($placeholders)
                    ORDER BY od.vn, od.diagtype, od.ovst_diag_id
                ", $vnsList);
                $opdDiags = collect($diagRows);
            } catch (\Throwable $e) {
                Log::warning("F16 Export ovstdiag query error: " . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 3. Query OPD Procedure (ovstdiag where diagtype='2' + doctor_operation)
        // -------------------------------------------------------------
        $opdOpers = collect();
        if (!empty($vnsList)) {
            try {
                // Query doctor_operation
                $operRows = DB::connection('hosxp')->select("
                    SELECT d.vn, o.hn, o.vstdate, o.spclty,
                           d.icd9, d.doctor, COALESCE(doc.licenseno, d.doctor) as doctor_license,
                           pt.cid, 0 as servprice
                    FROM doctor_operation d
                    LEFT JOIN ovst o ON o.vn = d.vn
                    LEFT JOIN patient pt ON pt.hn = o.hn
                    LEFT JOIN doctor doc ON doc.code = d.doctor
                    WHERE d.vn IN ($placeholders)
                    ORDER BY d.vn
                ", $vnsList);
                $opdOpers = collect($operRows);
            } catch (\Throwable $e) {
                Log::warning("F16 Export doctor_operation query error: " . $e->getMessage());
            }

            $operSet = [];
            foreach ($opdOpers as $item) {
                $k = $item->vn . '_' . str_replace('.', '', trim((string)$item->icd9));
                $operSet[$k] = true;
            }

            // Also check ovstdiag for ICD-9 format (starting with digits)
            foreach ($opdDiags as $od) {
                $isIcd9 = preg_match('/^[0-9]/', trim((string)$od->icd10));
                if ($isIcd9) {
                    $icd9Code = str_replace('.', '', trim((string)$od->icd10));
                    $k = $od->vn . '_' . $icd9Code;
                    if (!isset($operSet[$k])) {
                        $operSet[$k] = true;
                        $opdOpers->push((object)[
                            'vn' => $od->vn,
                            'hn' => $od->hn,
                            'vstdate' => $od->vstdate,
                            'spclty' => $od->spclty,
                            'icd9' => $icd9Code,
                            'doctor' => $od->doctor,
                            'doctor_license' => $od->doctor_license,
                            'cid' => $od->cid,
                            'servprice' => 0
                        ]);
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // 4. Query Items (opitemrece, drugitems, nondrugitems, income, drg_chrgitem)
        // -------------------------------------------------------------
        $items = collect();
        if (!empty($vnsList)) {
            try {
                $itemRows = DB::connection('hosxp')->select("
                    SELECT op.vn, op.hn, op.an, op.vstdate, op.vsttime, op.icode, op.qty, op.unitprice, op.sum_price, op.cost,
                           op.income, op.paidst, op.pttype, op.hos_guid,
                           d.name as drug_name, d.strength as drug_strength, d.units as drug_unit, d.packqty as drug_pack, d.did as drug_did,
                           d.tmt_tp_code, d.tmt_gp_code, d.ttmt_code, d.sks_drug_code, d.therapeutic,
                           n.name as nondrug_name,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) as nhso_adp_code,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) as nhso_adp_type,
                           COALESCE(drg.chrgitem_code1, 'H1') as chrgitem_code,
                           o.spclty, pt.cid, COALESCE(doc.licenseno, '') as doctor_license,
                           op.drugusage, du.code as sigcode, du.name1 as sigtext1, du.name2 as sigtext2, du.name3 as sigtext3
                    FROM opitemrece op
                    LEFT JOIN ovst o ON o.vn = op.vn
                    LEFT JOIN patient pt ON pt.hn = op.hn
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN income inc ON inc.income = op.income
                    LEFT JOIN drg_chrgitem drg ON drg.drg_chrgitem_id = inc.drg_chrgitem_id
                    LEFT JOIN doctor doc ON doc.code = op.doctor
                    LEFT JOIN drugusage du ON du.drugusage = op.drugusage
                    WHERE op.vn IN ($placeholders)
                    ORDER BY op.vn, op.item_no
                ", $vnsList);
                $items = collect($itemRows);
            } catch (\Throwable $e) {
                // Fallback query without drugusage details
                try {
                    $itemRows = DB::connection('hosxp')->select("
                        SELECT op.vn, op.hn, op.an, op.vstdate, op.vsttime, op.icode, op.qty, op.unitprice, op.sum_price, op.cost,
                               op.income, op.paidst, op.pttype, op.hos_guid,
                               d.name as drug_name, d.strength as drug_strength, d.units as drug_unit, d.packqty as drug_pack, d.did as drug_did,
                               d.tmt_tp_code, d.tmt_gp_code, d.ttmt_code, d.sks_drug_code, d.therapeutic,
                               n.name as nondrug_name,
                               COALESCE(n.nhso_adp_code, d.nhso_adp_code) as nhso_adp_code,
                               COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) as nhso_adp_type,
                               COALESCE(drg.chrgitem_code1, 'H1') as chrgitem_code,
                               o.spclty, pt.cid, COALESCE(doc.licenseno, '') as doctor_license,
                               op.drugusage, '' as sigcode, '' as sigtext1, '' as sigtext2, '' as sigtext3
                        FROM opitemrece op
                        LEFT JOIN ovst o ON o.vn = op.vn
                        LEFT JOIN patient pt ON pt.hn = op.hn
                        LEFT JOIN drugitems d ON d.icode = op.icode
                        LEFT JOIN nondrugitems n ON n.icode = op.icode
                        LEFT JOIN income inc ON inc.income = op.income
                        LEFT JOIN drg_chrgitem drg ON drg.drg_chrgitem_id = inc.drg_chrgitem_id
                        LEFT JOIN doctor doc ON doc.code = op.doctor
                        WHERE op.vn IN ($placeholders)
                        ORDER BY op.vn, op.item_no
                    ", $vnsList);
                    $items = collect($itemRows);
                } catch (\Throwable $e2) {
                    Log::warning("F16 Export opitemrece query error: " . $e2->getMessage());
                }
            }
        }

        // -------------------------------------------------------------
        // 5. Query Refer (referout / referin) for both OP (VN) and IP (AN)
        // -------------------------------------------------------------
        $allSearchKeys = array_unique(array_filter(array_merge($vnsList, $ansList)));
        $referPlaceholders = !empty($allSearchKeys) ? implode(',', array_fill(0, count($allSearchKeys), '?')) : '';
        
        $referOuts = collect();
        $referIns = collect();

        if (!empty($allSearchKeys)) {
            try {
                $referRows = DB::connection('hosxp')->select("
                    SELECT ro.vn, ro.hn, ro.refer_date,
                           TIME_FORMAT(ro.refer_time, '%H%i') as refer_time,
                           COALESCE(NULLIF(ro.refer_hospcode, ''), ro.hospcode) as refer_hospcode,
                           COALESCE(ro.refer_type, '2') as refer_type,
                           ro.refer_number, o.spclty, o.vstdate
                    FROM referout ro
                    LEFT JOIN ovst o ON o.vn = ro.vn
                    WHERE ro.vn IN ($referPlaceholders)
                ", $allSearchKeys);
                $referOuts = collect($referRows);
            } catch (\Throwable $e) {
                Log::warning("F16 Export referout query error: " . $e->getMessage());
            }

            try {
                $referInRows = DB::connection('hosxp')->select("
                    SELECT ri.vn, ri.hn, ri.refer_date,
                           TIME_FORMAT(ri.refer_time, '%H%i') as refer_time,
                           COALESCE(NULLIF(ri.refer_hospcode, ''), ri.hospcode) as refer_hospcode,
                           COALESCE(ri.refer_type, '1') as refer_type,
                           ri.docno as refer_number, o.spclty, o.vstdate
                    FROM referin ri
                    LEFT JOIN ovst o ON o.vn = ri.vn
                    WHERE ri.vn IN ($referPlaceholders)
                ", $allSearchKeys);
                $referIns = collect($referInRows);
            } catch (\Throwable $e) {
                Log::warning("F16 Export referin query error: " . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 6. Query ER & Refer for AER.txt (Accident, Emergency, Refer)
        // -------------------------------------------------------------
        $aerVisits = collect();
        if (!empty($vnsList)) {
            try {
                $erRows = collect(DB::connection('hosxp')->select("
                    SELECT er.vn, er.er_emergency_type, er.er_emergency_level_id,
                           TIME_FORMAT(COALESCE(er.er_time_1, er.enter_er_time), '%H%i') as aetime
                    FROM er_regist er
                    WHERE er.vn IN ($placeholders)
                ", $vnsList))->keyBy('vn');

                $referOutByVn = $referOuts->keyBy('vn');
                $referInByVn = $referIns->keyBy('vn');

                foreach ($visits as $v) {
                    $hasEr = $erRows->has($v->vn);
                    $ro = $referOutByVn->get($v->vn) ?: ($v->an ? $referOutByVn->get($v->an) : null);
                    $ri = $referInByVn->get($v->vn) ?: ($v->an ? $referInByVn->get($v->an) : null);
                    $ucae = trim((string)($v->ucae ?? ''));
                    $isUcae = in_array($ucae, ['A', 'E', 'I', 'O', 'C', 'Z']);

                    if ($hasEr || $ro || $ri || $isUcae) {
                        $er = $erRows->get($v->vn);
                        $aerVisits->push((object)[
                            'vn' => $v->vn,
                            'hn' => $v->hn,
                            'an' => $v->an,
                            'vstdate' => $v->vstdate,
                            'er_emergency_type' => $er->er_emergency_type ?? '',
                            'er_emergency_level_id' => $er->er_emergency_level_id ?? '',
                            'aetime' => $er->aetime ?? ($ro->refer_time ?? ($ri->refer_time ?? self::formatTime($v->vsttime))),
                            'refer_no' => $ro->refer_number ?? ($ri->refer_number ?? ''),
                            'refmaino' => $ro->refer_hospcode ?? '',
                            'refmaini' => $ri->refer_hospcode ?? '',
                            'ucae' => $ucae
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("F16 Export AER query error: " . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 7. Query IPD Details if any AN present
        // -------------------------------------------------------------
        $ipdVisits = collect();
        $ipdDiags = collect();
        $ipdOpers = collect();
        $ipdLeaves = collect();

        if (!empty($ansList)) {
            $anPlaceholders = implode(',', array_fill(0, count($ansList), '?'));
            
            try {
                $ipdVisits = collect(DB::connection('hosxp')->select("
                    SELECT ipt.an, ipt.hn, ipt.regdate, ipt.regtime, ipt.dchdate, ipt.dchtime,
                           ipt.dchstts as dischs, ipt.dchtype as discht, ipt.ward as warddsc,
                           ipt.spclty as dept, ipt.bw as adm_w, '' as svctype
                    FROM ipt
                    WHERE ipt.an IN ($anPlaceholders)
                ", $ansList))->keyBy('an');
            } catch (\Throwable $e) {
                Log::warning("F16 Export ipt query error: " . $e->getMessage());
            }

            try {
                $ipdDiags = collect(DB::connection('hosxp')->select("
                    SELECT id.an, id.icd10, id.diagtype, doc.licenseno as drdx
                    FROM iptdiag id
                    LEFT JOIN doctor doc ON doc.code = id.doctor
                    WHERE id.an IN ($anPlaceholders)
                    ORDER BY id.an, id.diagtype
                ", $ansList));
            } catch (\Throwable $e) {
                Log::warning("F16 Export iptdiag query error: " . $e->getMessage());
            }

            try {
                $ipdOpers = collect(DB::connection('hosxp')->select("
                    SELECT io.an, io.icd9 as oper, io.opertype, doc.licenseno as dropid,
                           io.opdate as datein, io.optime as timein, io.enddate as dateout, io.endtime as timeout
                    FROM iptoprt io
                    LEFT JOIN doctor doc ON doc.code = io.doctor
                    WHERE io.an IN ($anPlaceholders)
                ", $ansList));
            } catch (\Throwable $e) {
                Log::warning("F16 Export iptoprt query error: " . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 8. Query Lab (lab_head, lab_order, lab_items) for LAB.txt / LABFU.txt
        // -------------------------------------------------------------
        $labOrders = collect();
        if (!empty($vnsList)) {
            try {
                $labRows = DB::connection('hosxp')->select("
                    SELECT lh.vn, lh.hn, lh.order_date, lh.order_time,
                           lo.lab_items_code, lo.lab_order_result,
                           li.lab_items_name, li.labtest, li.tmlt_code, li.provis_labcode,
                           pt.cid
                    FROM lab_head lh
                    JOIN lab_order lo ON lo.lab_order_number = lh.lab_order_number
                    JOIN lab_items li ON li.lab_items_code = lo.lab_items_code
                    LEFT JOIN patient pt ON pt.hn = lh.hn
                    WHERE lh.vn IN ($placeholders)
                      AND lo.lab_order_result IS NOT NULL 
                      AND lo.lab_order_result != ''
                      AND lo.confirm = 'Y'
                    ORDER BY lh.vn, lo.lab_items_code
                ", $vnsList);
                $labOrders = collect($labRows);
            } catch (\Throwable $e) {
                // Fallback query without confirm = 'Y'
                try {
                    $labRows = DB::connection('hosxp')->select("
                        SELECT lh.vn, lh.hn, lh.order_date, lh.order_time,
                               lo.lab_items_code, lo.lab_order_result,
                               li.lab_items_name, li.labtest, li.tmlt_code, li.provis_labcode,
                               pt.cid
                        FROM lab_head lh
                        JOIN lab_order lo ON lo.lab_order_number = lh.lab_order_number
                        JOIN lab_items li ON li.lab_items_code = lo.lab_items_code
                        LEFT JOIN patient pt ON pt.hn = lh.hn
                        WHERE lh.vn IN ($placeholders)
                          AND lo.lab_order_result IS NOT NULL 
                          AND lo.lab_order_result != ''
                        ORDER BY lh.vn, lo.lab_items_code
                    ", $vnsList);
                    $labOrders = collect($labRows);
                } catch (\Throwable $e2) {
                    Log::warning("F16 Export opitemrece query error: " . $e2->getMessage());
                }
            }
        }

        // =============================================================
        // GENERATE EACH OF THE 17 FILES (WITH OFFICIAL HEADERS)
        // =============================================================

        // 1. INS.txt (20 columns)
        // HN|INSCL|SUBTYPE|CID|HCODE|DATEIN|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE
        $insLines = ["HN|INSCL|SUBTYPE|CID|HCODE|DATEIN|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE"];
        foreach ($visits as $v) {
            $hip = strtoupper(trim((string)$v->hipdata_code));
            $ptt = strtoupper(trim((string)$v->pttype));
            $inscl = $hip ?: (str_starts_with($ptt, 'O') ? 'OFC' : (str_starts_with($ptt, 'L') ? 'LGO' : (str_starts_with($ptt, 'U') ? 'UCS' : 'OFC')));
            $subtype = trim((string)$v->pttype_nhso_code) ?: 'O1';
            $cid = trim((string)$v->cid);
            $datein = '';
            $dateexp = '';
            $hospmain = $v->hospmain ?: '';
            $hospsub = $v->hospsub ?: '';
            $govcode = '';
            $govname = '';
            $permitno = $v->permitno ?: '';
            $docno = '';
            $ownrpid = '';
            $ownrname = '';
            $an = $v->an ?: '';
            $seq = !empty($v->an) ? '' : ($v->seq ?: $v->vn);
            $subinscl = '';
            $relinscl = '';
            $htype = '1';

            $insLines[] = "{$v->hn}|{$inscl}|{$subtype}|{$cid}|{$hcode}|{$datein}|{$dateexp}|{$hospmain}|{$hospsub}|{$govcode}|{$govname}|{$permitno}|{$docno}|{$ownrpid}|{$ownrname}|{$an}|{$seq}|{$subinscl}|{$relinscl}|{$htype}";
        }

        // 2. PAT.txt (15 columns)
        // HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE
        $patLines = ["HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE"];
        $seenHnPat = [];
        foreach ($visits as $v) {
            if (isset($seenHnPat[$v->hn])) continue;
            $seenHnPat[$v->hn] = true;

            $chw = str_pad(trim((string)$v->chwpart), 2, '0', STR_PAD_LEFT);
            $amp = str_pad(trim((string)$v->amppart), 2, '0', STR_PAD_LEFT);
            $dob = self::formatDate($v->birthday);
            $sex = $v->sex == '1' ? '1' : ($v->sex == '2' ? '2' : '1');
            $marry = $v->marrystatus ?: '1';
            $occupa = str_pad(trim((string)$v->occupation), 3, '0', STR_PAD_LEFT) ?: '000';
            $nation = str_pad(trim((string)$v->nationality ?: '99'), 3, '0', STR_PAD_LEFT);
            $cid = trim((string)$v->cid);
            $title = trim((string)$v->pname);
            $fname = trim((string)$v->fname);
            $lname = trim((string)$v->lname);
            $namepat = "{$fname}  {$lname} , {$title}";
            $idtype = '1';

            $patLines[] = "{$hcode}|{$v->hn}|{$chw}|{$amp}|{$dob}|{$sex}|{$marry}|{$occupa}|{$nation}|{$cid}|{$namepat}|{$title}|{$fname}|{$lname}|{$idtype}";
        }

        // 3. OPD.txt (6 columns)
        // HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC
        $opdLines = ["HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC"];
        foreach ($visits as $v) {
            $clinic = self::formatClinic($v->spclty);
            $dateopd = self::formatDate($v->vstdate);
            $timeopd = self::formatTime($v->vsttime);
            $seq = $v->seq ?: $v->vn;
            $uuc = '1';

            $opdLines[] = "{$v->hn}|{$clinic}|{$dateopd}|{$timeopd}|{$seq}|{$uuc}";
        }

        // 4. IPD.txt (13 columns)
        // HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE
        $ipdLines = ["HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE"];
        foreach ($visits as $v) {
            if (empty($v->an)) continue;
            $ip = $ipdVisits->get($v->an);
            if (!$ip) continue;

            $dateadm = self::formatDate($ip->regdate);
            $timeadm = self::formatTime($ip->regtime);
            $datedsc = self::formatDate($ip->dchdate);
            $timedsc = self::formatTime($ip->dchtime);
            $dischs = $ip->dischs ?: '1';
            $discht = $ip->discht ?: '1';
            $ward = str_pad(trim((string)$ip->warddsc), 2, '0', STR_PAD_LEFT) ?: '01';
            $dept = str_pad(trim((string)$ip->dept), 2, '0', STR_PAD_LEFT) ?: '01';
            $admw = number_format((float)($ip->adm_w ?: 50), 3, '.', '');
            $uuc = '1';
            $svctype = $ip->svctype ?: 'I';

            $ipdLines[] = "{$v->hn}|{$v->an}|{$dateadm}|{$timeadm}|{$datedsc}|{$timedsc}|{$dischs}|{$discht}|{$ward}|{$dept}|{$admw}|{$uuc}|{$svctype}";
        }

        // 5. ODX.txt (8 columns)
        // HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ
        $odxLines = ["HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ"];
        $diagsByVn = $opdDiags->groupBy('vn');
        foreach ($opdDiags as $d) {
            // Must only be ICD-10 (starts with letter A-Z, not ICD-9 numeric procedure)
            if (preg_match('/^[0-9]/', trim((string)$d->icd10))) {
                continue;
            }
            $clinic = self::formatClinic($d->spclty);
            $datedx = self::formatDate($d->vstdate);
            $diag = strtoupper(str_replace('.', '', trim((string)$d->icd10)));
            if (empty($diag)) continue;
            $dxtype = $d->diagtype ?: '1';
            $drdx = $d->doctor_license ?: ($d->doctor ?: 'ว00000');
            $cid = trim((string)$d->cid);
            $seq = $d->vn;

            $odxLines[] = "{$d->hn}|{$datedx}|{$clinic}|{$diag}|{$dxtype}|{$drdx}|{$cid}|{$seq}";
        }
        // Fallback: หาก Visit ใดไม่มีข้อมูลใน ovstdiag แต่มี pdx ใน vn_stat ให้สร้างแถว ODX หลัก
        foreach ($visits as $v) {
            $vSeq = $v->seq ?: $v->vn;
            $hasIcd10 = false;
            if ($diagsByVn->has($vSeq)) {
                foreach ($diagsByVn->get($vSeq) as $cand) {
                    if (!preg_match('/^[0-9]/', trim((string)$cand->icd10))) {
                        $hasIcd10 = true;
                        break;
                    }
                }
            }
            if (!$hasIcd10 && !empty($v->pdx)) {
                $clinic = self::formatClinic($v->spclty);
                $datedx = self::formatDate($v->vstdate);
                $diag = strtoupper(str_replace('.', '', trim((string)$v->pdx)));
                $drdx = $v->doctor_license ?: 'ว00000';
                $cid = trim((string)$v->cid);
                $odxLines[] = "{$v->hn}|{$datedx}|{$clinic}|{$diag}|1|{$drdx}|{$cid}|{$vSeq}";
            }
        }

        // 6. OOP.txt (7 columns)
        // HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ
        $oopLines = ["HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ"];
        $seenOop = [];
        foreach ($opdOpers as $op) {
            $clinic = self::formatClinic($op->spclty);
            $dateopd = self::formatDate($op->vstdate);
            $oper = str_replace('.', '', trim((string)$op->icd9));
            if (empty($oper)) continue;
            $dropid = $op->doctor_license ?: ($op->doctor ?: 'ว00000');
            if (str_starts_with($dropid, '-') && strlen($dropid) > 6) {
                $dropid = substr($dropid, 0, 6);
            }
            $cid = trim((string)$op->cid);
            $seq = $op->vn;

            $key = "{$seq}_{$oper}_{$dropid}";
            if (isset($seenOop[$key])) continue;
            $seenOop[$key] = true;

            $oopLines[] = "{$op->hn}|{$dateopd}|{$clinic}|{$oper}|{$dropid}|{$cid}|{$seq}";
        }

        // 7. IDX.txt (4 columns)
        // AN|DIAG|DXTYPE|DRDX
        $idxLines = ["AN|DIAG|DXTYPE|DRDX"];
        foreach ($ipdDiags as $id) {
            $diag = strtoupper(str_replace('.', '', trim((string)$id->icd10)));
            if (empty($diag)) continue;
            $dxtype = $id->diagtype ?: '1';
            $drdx = $id->drdx ?: 'ว00000';

            $idxLines[] = "{$id->an}|{$diag}|{$dxtype}|{$drdx}";
        }

        // 8. IOP.txt (8 columns)
        // AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT
        $iopLines = ["AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT"];
        foreach ($ipdOpers as $io) {
            $oper = str_replace('.', '', trim((string)$io->oper));
            if (empty($oper)) continue;
            $optype = $io->opertype ?: '1';
            $dropid = $io->dropid ?: 'ว00000';
            $datein = self::formatDate($io->datein);
            $timein = self::formatTime($io->timein);
            $dateout = self::formatDate($io->dateout);
            $timeout = self::formatTime($io->timeout);

            $iopLines[] = "{$io->an}|{$oper}|{$optype}|{$dropid}|{$datein}|{$timein}|{$dateout}|{$timeout}";
        }

        // 9. ORF.txt (7 columns)
        // HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE
        $orfLines = ["HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE"];
        $visitsByVn = $visits->keyBy('vn');
        
        // Add Refer Out for OP
        foreach ($referOuts as $ro) {
            // Skip pure IPD records (handled in IRF)
            if (in_array($ro->vn, $ansList)) continue;
            
            $v = $visitsByVn->get($ro->vn);
            $dateopd = self::formatDate($v->vstdate ?? $ro->vstdate ?? $ro->refer_date);
            $clinic = self::formatClinic($ro->spclty ?? ($v->spclty ?? ''));
            $refer = trim((string)$ro->refer_hospcode);
            $refertype = $ro->refer_type ?: '2';
            $seq = $ro->vn;
            $referdate = self::formatDate($ro->refer_date);

            $orfLines[] = "{$ro->hn}|{$dateopd}|{$clinic}|{$refer}|{$refertype}|{$seq}|{$referdate}";
        }

        // Add Refer In for OP
        foreach ($referIns as $ri) {
            if (in_array($ri->vn, $ansList)) continue;

            $v = $visitsByVn->get($ri->vn);
            $dateopd = self::formatDate($v->vstdate ?? $ri->vstdate ?? $ri->refer_date);
            $clinic = self::formatClinic($ri->spclty ?? ($v->spclty ?? ''));
            $refer = trim((string)$ri->refer_hospcode);
            $refertype = $ri->refer_type ?: '1';
            $seq = $ri->vn;
            $referdate = self::formatDate($ri->refer_date);

            $orfLines[] = "{$ri->hn}|{$dateopd}|{$clinic}|{$refer}|{$refertype}|{$seq}|{$referdate}";
        }

        // 10. IRF.txt (3 columns)
        // AN|REFER|REFERTYPE
        $irfLines = ["AN|REFER|REFERTYPE"];
        
        // Refer Out for IP (Case 1: ro.vn = ipt.an, Case 2: ro.vn = ovst.vn associated with an)
        foreach ($referOuts as $ro) {
            $refer = trim((string)$ro->refer_hospcode);
            $refertype = $ro->refer_type ?: '2';

            if (in_array($ro->vn, $ansList)) {
                $irfLines[] = "{$ro->vn}|{$refer}|{$refertype}";
            } else {
                $v = $visitsByVn->get($ro->vn);
                if ($v && !empty($v->an)) {
                    $irfLines[] = "{$v->an}|{$refer}|{$refertype}";
                }
            }
        }

        // Refer In for IP
        foreach ($referIns as $ri) {
            $refer = trim((string)$ri->refer_hospcode);
            $refertype = $ri->refer_type ?: '1';

            if (in_array($ri->vn, $ansList)) {
                $irfLines[] = "{$ri->vn}|{$refer}|{$refertype}";
            } else {
                $v = $visitsByVn->get($ri->vn);
                if ($v && !empty($v->an)) {
                    $irfLines[] = "{$v->an}|{$refer}|{$refertype}";
                }
            }
        }

        // 11. LVD.txt (7 columns)
        // SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY
        $lvdLines = ["SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY"];

        // 12. DRU.txt (21 columns)
        // HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGTYPE|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL
        $druLines = ["HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGTYPE|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL"];
        $drugItems = $items->filter(function($it) {
            return str_starts_with((string)$it->icode, '1');
        });

        foreach ($drugItems as $it) {
            $clinic = self::formatClinic($it->spclty);
            $dateserv = self::formatDate($it->vstdate);
            $did = $it->icode;
            
            // Build full drug name: name strength units
            $nameParts = array_filter([trim((string)$it->drug_name), trim((string)$it->drug_strength), trim((string)$it->drug_unit)]);
            $didname = str_replace('|', ' ', implode(' ', $nameParts));
            
            $amount = (float)$it->qty;
            $amountStr = $amount == floor($amount) ? (string)intval($amount) : number_format($amount, 2, '.', '');
            $drugpric = number_format((float)$it->unitprice, 2, '.', '');
            $drugcost = number_format((float)$it->cost, 2, '.', '');
            $didstd = $it->sks_drug_code ?: ($it->tmt_tp_code ?: ($it->tmt_gp_code ?: ($it->ttmt_code ?: ($it->drug_did ?: ''))));
            $unit = trim((string)$it->drug_unit) ?: 'เม็ด';
            $unitpack = "1x{$unit}";
            $seq = $it->vn;
            $an = $it->an ?: '';
            $cid = trim((string)$it->cid);
            $drugtype = '';
            $drugremark = '';
            $pano = '';
            $totcopay = '0';
            $usestatus = !empty($it->an) ? '1' : '2'; // 1=In-hospital, 2=Home
            $total = number_format((float)$it->sum_price, 2, '.', '');

            $druLines[] = "{$hcode}|{$it->hn}|{$an}|{$clinic}|{$cid}|{$dateserv}|{$did}|{$didname}|{$amountStr}|{$drugpric}|{$drugcost}|{$didstd}|{$unit}|{$unitpack}|{$seq}|{$drugtype}|{$drugremark}|{$pano}|{$totcopay}|{$usestatus}|{$total}";
        }

        // 13. CHA.txt (7 columns)
        // HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ
        $chaLines = ["HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ"];
        $itemsByVn = $items->groupBy('vn');
        $visitsByVn = $visits->keyBy('vn');
        foreach ($itemsByVn as $vn => $vnItems) {
            $v = $visitsByVn->get($vn);
            if (!$v) continue;

            $date = self::formatDate($v->vstdate);
            $cid = trim((string)$v->cid);
            $an = $v->an ?: '';
            $seq = !empty($v->an) ? $v->an : ($v->seq ?: $v->vn);

            // Group by CHA CHRGITEM
            $chaGroups = [];
            foreach ($vnItems as $it) {
                $chrg = self::mapIncomeToChaItem($it->income);
                if (!isset($chaGroups[$chrg])) {
                    $chaGroups[$chrg] = 0.0;
                }
                $chaGroups[$chrg] += (float)$it->sum_price;
            }

            ksort($chaGroups);

            foreach ($chaGroups as $chrg => $sumAmt) {
                $amtStr = number_format($sumAmt, 2, '.', '');
                $chaLines[] = "{$v->hn}|{$an}|{$date}|{$chrg}|{$amtStr}|{$cid}|{$seq}";
            }
        }

        // 14. CHT.txt (8 columns)
        // HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ
        $chtLines = ["HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ"];
        foreach ($visits as $v) {
            $date = self::formatDate($v->vstdate);
            $total = number_format((float)$v->income, 2, '.', '');
            $paid = number_format((float)($v->rcpt_money ?: 0.0), 2, '.', '');
            $pttype = self::mapChtPttype($v->hipdata_code, $v->pttype);
            $cid = trim((string)$v->cid);
            $an = $v->an ?: '';
            $seq = !empty($v->an) ? $v->an : ($v->seq ?: $v->vn);

            $chtLines[] = "{$v->hn}|{$an}|{$date}|{$total}|{$paid}|{$pttype}|{$cid}|{$seq}";
        }

        // 15. AER.txt (18 columns)
        // HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT
        $aerLines = ["HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT"];
        foreach ($aerVisits as $er) {
            $dateopd = self::formatDate($er->vstdate);
            $authae = '';
            $aedate = $dateopd;
            $aetime = $er->aetime ?: '';
            $aetype = '';
            $referno = trim((string)($er->refer_no ?: ''));
            $refmaini = trim((string)($er->refmaini ?: ''));
            $ireftype = !empty($refmaini) ? '1' : '';
            $refmaino = trim((string)($er->refmaino ?: ''));
            $oreftype = !empty($refmaino) ? '1100' : '';
            $ucae = in_array(trim((string)($er->ucae ?? '')), ['A', 'E', 'I', 'O', 'C', 'Z']) ? trim((string)$er->ucae) : '';
            $emtype = '3';
            $seq = $er->vn;
            $an = $er->an ?: '';

            $aerLines[] = "{$er->hn}|{$an}|{$dateopd}|{$authae}|{$aedate}|{$aetime}|{$aetype}|{$referno}|{$refmaini}|{$ireftype}|{$refmaino}|{$oreftype}|{$ucae}|{$emtype}|{$seq}|||";
        }

        // 16. ADP.txt (29 columns)
        // HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM|CHECK_KEY|GUID
        $adpLines = ["HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM|CHECK_KEY|GUID"];
        $adpItems = $items->filter(function($it) {
            $price = (float)$it->sum_price;
            $isDrug = str_starts_with((string)$it->icode, '1');
            if (!$isDrug) {
                // Non-drug items
                return true;
            } else {
                // Drug items only if mapped to nhso_adp_code
                return !empty(trim((string)$it->nhso_adp_code));
            }
        });

        foreach ($adpItems as $it) {
            $dateopd = self::formatDate($it->vstdate);
            $type = trim((string)($it->nhso_adp_type ?: '17'));
            $code = trim((string)($it->nhso_adp_code ?: $it->icode));
            $qty = intval($it->qty) ?: 1;
            $rate = (float)$it->unitprice;
            $rateStr = $rate == floor($rate) ? (string)intval($rate) : number_format($rate, 2, '.', '');
            $seq = !empty($it->an) ? $it->an : $it->vn;
            $an = $it->an ?: '';
            $cagcode = '';
            $dose = '';
            $catype = '';
            $serialno = '';
            $totcopay = '0';
            $usestatus = '';
            $total = number_format((float)$it->sum_price, 2, '.', '');
            $qtyday = '';
            $tmltcode = '';
            $status1 = '';
            $bi = '';
            $clinic = self::formatClinic($it->spclty);
            $itemsrc = '';
            $provider = '';
            $gravida = '';
            $gaweek = '';
            $dcip = '';
            $lmp = '';
            $spitem = '';
            $checkkey = "{$seq}:{$type}:{$code}:{$rateStr}";
            $guid = $it->hos_guid ? '{' . trim($it->hos_guid, '{}') . '}' : ('{' . strtoupper(md5($it->vn . $it->icode . microtime())) . '}');

            $adpLines[] = "{$it->hn}|{$an}|{$dateopd}|{$type}|{$code}|{$qty}|{$rateStr}|{$seq}|{$cagcode}|{$dose}|{$catype}|{$serialno}|{$totcopay}|{$usestatus}|{$total}|{$qtyday}|{$tmltcode}|{$status1}|{$bi}|{$clinic}|{$itemsrc}|{$provider}|{$gravida}|{$gaweek}|{$dcip}|{$lmp}|{$spitem}|{$checkkey}|{$guid}";
        }

        // 17. LAB.txt / LABFU.txt (7 columns)
        // HCODE|HN|PERSON_ID|DATESERV|SEQ|LABTEST|LABRESULT
        $labLines = ["HCODE|HN|PERSON_ID|DATESERV|SEQ|LABTEST|LABRESULT"];
        $seenLab = [];
        foreach ($labOrders as $lab) {
            $labTestCode = self::mapLabTestCode($lab->labtest, $lab->tmlt_code, $lab->provis_labcode, $lab->lab_items_name);
            if (empty($labTestCode)) continue;

            $dateserv = self::formatDate($lab->order_date);
            $seq = $lab->vn;
            $cid = trim((string)$lab->cid);
            $rawResult = trim((string)$lab->lab_order_result);
            $labResult = str_replace([',', '|'], '', $rawResult);

            $key = "{$seq}_{$labTestCode}";
            if (isset($seenLab[$key])) continue;
            $seenLab[$key] = true;

            $labLines[] = "{$hcode}|{$lab->hn}|{$cid}|{$dateserv}|{$seq}|{$labTestCode}|{$labResult}";
        }

        // =============================================================
        // COMPILE FINAL RESULT WITH ALL 17 FILES
        // =============================================================
        $files = [
            'INS' => implode("\r\n", $insLines),
            'PAT' => implode("\r\n", $patLines),
            'OPD' => implode("\r\n", $opdLines),
            'IPD' => implode("\r\n", $ipdLines),
            'ODX' => implode("\r\n", $odxLines),
            'OOP' => implode("\r\n", $oopLines),
            'IDX' => implode("\r\n", $idxLines),
            'IOP' => implode("\r\n", $iopLines),
            'ORF' => implode("\r\n", $orfLines),
            'IRF' => implode("\r\n", $irfLines),
            'LVD' => implode("\r\n", $lvdLines),
            'DRU' => implode("\r\n", $druLines),
            'CHA' => implode("\r\n", $chaLines),
            'CHT' => implode("\r\n", $chtLines),
            'AER' => implode("\r\n", $aerLines),
            'ADP' => implode("\r\n", $adpLines),
            'LAB' => implode("\r\n", $labLines),
        ];

        $counts = [
            'INS' => count($insLines) - 1,
            'PAT' => count($patLines) - 1,
            'OPD' => count($opdLines) - 1,
            'IPD' => count($ipdLines) - 1,
            'ODX' => count($odxLines) - 1,
            'OOP' => count($oopLines) - 1,
            'IDX' => count($idxLines) - 1,
            'IOP' => count($iopLines) - 1,
            'ORF' => count($orfLines) - 1,
            'IRF' => count($irfLines) - 1,
            'LVD' => count($lvdLines) - 1,
            'DRU' => count($druLines) - 1,
            'CHA' => count($chaLines) - 1,
            'CHT' => count($chtLines) - 1,
            'AER' => count($aerLines) - 1,
            'ADP' => count($adpLines) - 1,
            'LAB' => count($labLines) - 1,
        ];

        return [
            'status' => 'success',
            'files' => $files,
            'counts' => $counts,
            'total_visits' => count($visits),
            'hcode' => $hcode
        ];
    }
}
