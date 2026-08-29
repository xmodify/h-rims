<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class F16FdhExportService
 * 
 * ระบบประมวลผลและสร้างข้อมูล 16/17 แฟ้มมาตรฐานส่งออก FDH (Financial Data Hub)
 * รองรับทั้งข้อมูลผู้ป่วยนอก (OPD) และผู้ป่วยใน (IPD)
 */
class F16FdhExportService
{
    /**
     * ดึงรหัสสถานพยาบาล HCODE ของโรงพยาบาล
     */
    public static function getHcode(): string
    {
        return LicenseVerificationService::getHcode();
    }

    /**
     * แปลงวันที่เป็นรูปแบบ YYYYMMDD (ค.ศ.)
     */
    public static function formatDate($date): string
    {
        if (empty($date)) return '';
        $ts = strtotime($date);
        return $ts ? date('Ymd', $ts) : '';
    }

    /**
     * แปลงเวลาเป็นรูปแบบ HHMM (24 ชั่วโมง ไม่มี colon)
     */
    public static function formatTime($time): string
    {
        if (empty($time)) return '';
        $t = str_replace(':', '', trim($time));
        if (strlen($t) >= 4) {
            return substr($t, 0, 4);
        }
        return str_pad($t, 4, '0', STR_PAD_LEFT);
    }

    /**
     * แมป Clinic Code (2 หลัก หรือ 5 หลัก)
     */
    public static function formatClinic($spclty): string
    {
        $code = trim((string)$spclty);
        if (empty($code) || $code === '0' || $code === '00') {
            return '0100';
        }
        if (strlen($code) == 1) {
            return '0' . $code . '00';
        }
        if (strlen($code) == 2) {
            return $code . '00';
        }
        return $code;
    }

    /**
     * แปลงรหัสสิทธิการรักษาสำหรับ CHT.txt และ INS.txt
     */
    public static function mapChtPttype($hipdataCode, $pttype): string
    {
        $hip = strtoupper(trim((string)$hipdataCode));
        $ptt = strtoupper(trim((string)$pttype));

        if ($hip === 'UCS' || $hip === 'UC' || str_starts_with($ptt, '1') || str_starts_with($ptt, '2') || str_starts_with($ptt, '7') || str_starts_with($ptt, '8') || str_starts_with($ptt, '9')) {
            return 'UCS';
        }
        if ($hip === 'OFC' || $hip === 'A2' || str_starts_with($ptt, 'O')) {
            return 'OFC';
        }
        if ($hip === 'SSS' || $hip === 'SS' || str_starts_with($ptt, '3')) {
            return 'SSS';
        }
        if ($hip === 'LGO' || str_starts_with($ptt, 'L')) {
            return 'LGO';
        }
        if ($hip === 'SSI') {
            return 'SSI';
        }
        if ($hip === 'PVT' || str_starts_with($ptt, 'P')) {
            return 'PVT';
        }
        if ($hip === 'BKK') {
            return 'BKK';
        }
        if ($hip === 'BMT') {
            return 'BMT';
        }
        if ($hip === 'SRT') {
            return 'SRT';
        }

        return 'UCS';
    }

    /**
     * แปลงหมวดรายได้ของ HOSxP (income) ให้เป็นรหัสหมวดค่าบริการ 16 หมวด สปสช./FDH (CHRGITEM ใน CHA.txt)
     */
    public static function mapIncomeToChaItem($income): string
    {
        $inc = str_pad(trim((string)$income), 2, '0', STR_PAD_LEFT);
        $map = [
            '01' => '1',  // ค่าห้อง/ค่าอาหาร
            '02' => '2',  // ค่าอวัยวะเทียม/อุปกรณ์
            '03' => '3',  // ค่ายาในบัญชียาหลัก
            '04' => '4',  // ค่ายานอกบัญชียาหลัก
            '05' => '5',  // ค่าเวชภัณฑ์ที่มิใช่ยา
            '06' => '6',  // ค่าบริการโลหิตและส่วนประกอบ
            '07' => '7',  // ค่าตรวจวินิจฉัยทางเทคนิคการแพทย์/พยาธิ
            '08' => '8',  // ค่าตรวจวินิจฉัยและรักษาทางรังสีวิทยา
            '09' => '9',  // ค่าตรวจวินิจฉัยโดยวิธีพิเศษอื่นๆ
            '10' => 'A',  // ค่าอุปกรณ์ของใช้และเครื่องมือทางการแพทย์
            '11' => 'B',  // ค่าทำหัตถการและวิสัญญี
            '12' => 'C',  // ค่าบริการทางการพยาบาล
            '13' => 'D',  // ค่าบริการทางทันตกรรม
            '14' => 'E',  // ค่าบริการทางกายภาพบำบัด/เวชกรรมฟื้นฟู
            '15' => 'F',  // ค่าบริการฝังเข็ม/การบำบัดรักษาทางเลือก
            '16' => 'G',  // ค่าบริการอื่นๆ
            '17' => '3',  // ยา
            '18' => '5',  // วัสดุ
            '19' => '7',  // Lab
            '20' => '8',  // X-Ray
        ];
        return $map[$inc] ?? 'G';
    }

    /**
     * แมปรหัสการตรวจ LAB chronic / Standard LAB code 2 หลักสำหรับ LABFU.txt
     */
    private static function mapLabTestCode($labtest, $tmltCode, $provisCode, $labName): string
    {
        $lt = trim((string)$labtest);
        if (!empty($lt) && is_numeric($lt) && intval($lt) > 0 && intval($lt) <= 28) {
            return str_pad($lt, 2, '0', STR_PAD_LEFT);
        }

        $tmlt = trim((string)$tmltCode);
        $name = strtolower(trim((string)$labName));

        if (str_contains($tmlt, '30101') || str_contains($name, 'fbs') || str_contains($name, 'fasting blood sugar') || str_contains($name, 'glucose')) {
            return '01'; // Blood Sugar / FBS
        }
        if (str_contains($tmlt, '30109') || str_contains($name, 'hba1c') || str_contains($name, 'hemoglobin a1c') || str_contains($name, 'a1c')) {
            return '02'; // HbA1c
        }
        if (str_contains($tmlt, '30201') || str_contains($name, 'cholesterol') || str_contains($name, 'tc') || str_contains($name, 'total cholesterol')) {
            return '03'; // Total Cholesterol
        }
        if (str_contains($tmlt, '30202') || str_contains($name, 'triglyceride') || str_contains($name, 'tg')) {
            return '04'; // Triglyceride
        }
        if (str_contains($tmlt, '30203') || str_contains($name, 'hdl') || str_contains($name, 'hdl-c')) {
            return '05'; // HDL-Cholesterol
        }
        if (str_contains($tmlt, '30204') || str_contains($name, 'ldl') || str_contains($name, 'ldl-c')) {
            return '06'; // LDL-Cholesterol
        }
        if (str_contains($tmlt, '30301') || str_contains($name, 'bun') || str_contains($name, 'blood urea nitrogen')) {
            return '07'; // BUN
        }
        if (str_contains($tmlt, '30302') || str_contains($name, 'creatinine') || str_contains($name, 'cr') || str_ends_with($name, ' cr')) {
            return '08'; // Creatinine
        }
        if (str_contains($name, 'egfr') || str_contains($name, 'gfr')) {
            return '09'; // eGFR
        }
        if (str_contains($tmlt, '30601') || str_contains($name, 'ast') || str_contains($name, 'sgot')) {
            return '10'; // AST / SGOT
        }
        if (str_contains($tmlt, '30602') || str_contains($name, 'alt') || str_contains($name, 'sgpt')) {
            return '11'; // ALT / SGPT
        }
        if (str_contains($tmlt, '30603') || str_contains($name, 'alp') || str_contains($name, 'alkaline phosphatase')) {
            return '12'; // ALP
        }
        if (str_contains($name, 'microalbumin') || str_contains($name, 'urine microalbumin')) {
            return '13'; // Urine Microalbumin
        }
        if (str_contains($name, 'urine protein') || str_contains($name, 'protein, urine')) {
            return '14'; // Urine Protein
        }
        if (str_contains($name, 'urinalysis') || str_contains($name, 'ua') || str_contains($name, 'u/a')) {
            return '15'; // Urinalysis
        }
        if (str_contains($name, 'cbc') || str_contains($name, 'complete blood count')) {
            return '16'; // Complete Blood Count (CBC)
        }
        if (str_contains($name, 'potassium') || str_contains($name, ' k ') || str_ends_with($name, ' k') || str_contains($name, 'electrolyte')) {
            return '17'; // Potassium
        }
        if (str_contains($name, 'sodium') || str_contains($name, ' na ') || str_ends_with($name, ' na')) {
            return '18'; // Sodium
        }
        if (str_contains($name, 'chloride') || str_contains($name, ' cl ') || str_ends_with($name, ' cl')) {
            return '19'; // Chloride
        }
        if (str_contains($name, 'bicarbonate') || str_contains($name, 'co2') || str_contains($name, 'tco2')) {
            return '20'; // Total CO2 / Bicarbonate
        }
        if (str_contains($name, 'hiv') || str_contains($name, 'anti-hiv') || str_contains($name, 'anti hiv')) {
            return '21'; // Anti-HIV
        }
        if (str_contains($name, 'cd4')) {
            return '22'; // CD4 count
        }
        if (str_contains($name, 'viral load') || str_contains($name, 'hiv viral load') || str_contains($name, 'vl')) {
            return '23'; // Viral Load
        }
        if (str_contains($name, 'pap smear') || str_contains($name, 'cervical cancer') || str_contains($name, 'hpv')) {
            return '24'; // Pap smear / HPV
        }
        if (str_contains($name, 'fob') || str_contains($name, 'fit test') || str_contains($name, 'occult blood')) {
            return '25'; // Stool Occult Blood / FIT test
        }

        $prov = trim((string)$provisCode);
        if (!empty($prov) && is_numeric($prov) && intval($prov) > 0 && intval($prov) <= 28) {
            return str_pad($prov, 2, '0', STR_PAD_LEFT);
        }

        return '';
    }

    /**
     * =========================================================================
     * GENERATE 16/17 แฟ้มมาตรฐาน FDH สำหรับผู้ป่วยนอก (OPD)
     * =========================================================================
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
        // 1. Query Visit Details (ovst, vn_stat, patient, visit_pttype, pttype, opdscreen, doctor)
        // -------------------------------------------------------------
        $visits = collect();
        try {
            $visitRows = DB::connection('hosxp')->select("
                SELECT o.vn, o.vn as seq, o.hn, o.an, o.vstdate, o.vsttime, o.spclty, o.main_dep, o.cur_dep,
                       o.pttype, o.pt_subtype, o.ovstist, o.ovstost,
                       v.pdx, v.dx_doctor, v.income, v.paid_money, v.rcpt_money, v.uc_money,
                       pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex, pt.marrystatus, pt.occupation, pt.nationality,
                       pt.chwpart, pt.amppart, pt.tmbpart,
                       p.hipdata_code,
                       COALESCE(p.nhso_code, o.pt_subtype, '10') as pttype_nhso_code,
                       doc.licenseno as doctor_license, doc.name as doctor_name,
                       COALESCE(vp.hospmain, '') as hospmain,
                       COALESCE(vp.hospsub, '') as hospsub,
                       COALESCE(vp.auth_code, vp.claim_code, '') as permitno,
                       vp.auth_code,
                       vp.claim_code,
                       vp.nhso_ucae_type_code,
                       vp.begin_date as datein,
                       vp.expire_date as dateexp,
                       vp.nhso_govcode as gov_code,
                       vp.nhso_govname as gov_name,
                       vp.nhso_docno as docno,
                       vp.nhso_ownright_pid as ownrpid,
                       vp.nhso_ownright_name as ownname,
                       os.cc as cc_detail,
                       os.temperature as btemp,
                       os.bps as sbp,
                       os.bpd as dbp,
                       os.pulse as pr,
                       os.rr as rr
                FROM ovst o
                LEFT JOIN vn_stat v ON v.vn = o.vn
                LEFT JOIN patient pt ON pt.hn = o.hn
                LEFT JOIN visit_pttype vp ON vp.vn = o.vn
                LEFT JOIN pttype p ON p.pttype = COALESCE(vp.pttype, o.pttype)
                LEFT JOIN opdscreen os ON os.vn = o.vn
                LEFT JOIN doctor doc ON doc.code = o.doctor
                WHERE o.vn IN ($placeholders)
                ORDER BY o.vstdate, o.vsttime
            ", $vns);
            $visits = collect($visitRows);
        } catch (\Throwable $e) {
            Log::error("FDH Export main visit query error: " . $e->getMessage());
            throw $e;
        }

        $vnsList = array_values($visits->pluck('vn')->toArray());
        $vnPlaceholders = !empty($vnsList) ? implode(',', array_fill(0, count($vnsList), '?')) : '';
        $hnsList = array_values($visits->pluck('hn')->unique()->toArray());
        $ansList = array_values($visits->pluck('an')->filter()->unique()->toArray());
        $cids = $visits->pluck('cid')->filter()->unique()->toArray();

        // -------------------------------------------------------------
        // PERMITNO Logic สำหรับ UCS:
        // 1. visit_pttype.auth_code
        // 2. nhso_endpoint.claimCode หรือ authenCode
        // 3. visit_pttype.claim_code
        // -------------------------------------------------------------
        $nhsoEndpoints = collect();
        if (!empty($cids)) {
            try {
                $nhsoEndpoints = DB::table('nhso_endpoint')
                    ->whereIn('cid', $cids)
                    ->select('cid', 'vstdate', 'claimCode', 'authenCode')
                    ->get()
                    ->groupBy(function ($r) {
                        return $r->cid . '_' . $r->vstdate;
                    });
            } catch (\Throwable $ex) {
                Log::warning("Could not query nhso_endpoint table: " . $ex->getMessage());
            }
        }

        $visits->transform(function ($v) use ($nhsoEndpoints) {
            $cid = trim((string)$v->cid);
            $vstdate = $v->vstdate;
            $permit = trim((string)($v->auth_code ?? ''));
            if (empty($permit) && !empty($cid)) {
                $key = $cid . '_' . $vstdate;
                if (isset($nhsoEndpoints[$key]) && count($nhsoEndpoints[$key]) > 0) {
                    $ep = $nhsoEndpoints[$key]->first();
                    $permit = trim((string)($ep->claimCode ?: $ep->authenCode));
                }
            }
            if (empty($permit)) {
                $permit = trim((string)($v->claim_code ?? ''));
            }
            $v->permitno = $permit;
            return $v;
        });

        // -------------------------------------------------------------
        // 2. Query OPD Diags (ovstdiag)
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
                    WHERE od.vn IN ($vnPlaceholders)
                    ORDER BY od.vn, od.diagtype, od.ovst_diag_id
                ", $vnsList);
                $opdDiags = collect($diagRows);
            } catch (\Throwable $e) {
                Log::warning("FDH Export ovstdiag query error: " . $e->getMessage());
            }
        }

        // -------------------------------------------------------------
        // 3. Query OPD Procedures (doctor_operation + ovstdiag ICD-9 fallback)
        // -------------------------------------------------------------
        $opdOpers = collect();
        if (!empty($vnsList)) {
            try {
                $operRows = DB::connection('hosxp')->select("
                    SELECT d.vn, o.hn, o.vstdate, o.spclty,
                           d.icd9, d.doctor, COALESCE(doc.licenseno, d.doctor) as doctor_license,
                           pt.cid, 0 as servprice
                    FROM doctor_operation d
                    LEFT JOIN ovst o ON o.vn = d.vn
                    LEFT JOIN patient pt ON pt.hn = o.hn
                    LEFT JOIN doctor doc ON doc.code = d.doctor
                    WHERE d.vn IN ($vnPlaceholders)
                    ORDER BY d.vn
                ", $vnsList);
                $opdOpers = collect($operRows);
            } catch (\Throwable $e) {
                Log::warning("FDH Export doctor_operation query error: " . $e->getMessage());
            }

            $operSet = [];
            foreach ($opdOpers as $item) {
                $k = $item->vn . '_' . str_replace('.', '', trim((string)$item->icd9));
                $operSet[$k] = true;
            }

            // ตรวจสอบรหัส ICD-9 ที่อาจบันทึกอยู่ใน ovstdiag
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
        // 4. Query Drug & Non-Drug Items (opitemrece, drugitems, nondrugitems, income, drg_chrgitem)
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
                    WHERE op.vn IN ($vnPlaceholders)
                    ORDER BY op.vn, op.item_no
                ", $vnsList);
                $items = collect($itemRows);
            } catch (\Throwable $e) {
                // Fallback query
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
                        WHERE op.vn IN ($vnPlaceholders)
                        ORDER BY op.vn, op.item_no
                    ", $vnsList);
                    $items = collect($itemRows);
                } catch (\Throwable $e2) {
                    Log::warning("FDH Export opitemrece query error: " . $e2->getMessage());
                }
            }
        }

        // -------------------------------------------------------------
        // 5. Query Refer (referout / referin)
        // -------------------------------------------------------------
        $referOuts = collect();
        $referIns = collect();
        if (!empty($vnsList)) {
            try {
                $referRows = DB::connection('hosxp')->select("
                    SELECT ro.vn, ro.hn, ro.refer_date,
                           TIME_FORMAT(ro.refer_time, '%H%i') as refer_time,
                           COALESCE(NULLIF(ro.refer_hospcode, ''), ro.hospcode) as refer_hospcode,
                           COALESCE(ro.refer_type, '2') as refer_type,
                           ro.refer_number, o.spclty, o.vstdate
                    FROM referout ro
                    LEFT JOIN ovst o ON o.vn = ro.vn
                    WHERE ro.vn IN ($vnPlaceholders)
                ", $vnsList);
                $referOuts = collect($referRows);
            } catch (\Throwable $e) {}

            try {
                $referInRows = DB::connection('hosxp')->select("
                    SELECT ri.vn, ri.hn, ri.refer_date,
                           TIME_FORMAT(ri.refer_time, '%H%i') as refer_time,
                           COALESCE(NULLIF(ri.refer_hospcode, ''), ri.hospcode) as refer_hospcode,
                           COALESCE(ri.refer_type, '1') as refer_type,
                           ri.docno as refer_number, o.spclty, o.vstdate
                    FROM referin ri
                    LEFT JOIN ovst o ON o.vn = ri.vn
                    WHERE ri.vn IN ($vnPlaceholders)
                ", $vnsList);
                $referIns = collect($referInRows);
            } catch (\Throwable $e) {}
        }

        // -------------------------------------------------------------
        // 6. Query ER Records (er_regist) for AER.txt
        // -------------------------------------------------------------
        $aerVisits = collect();
        if (!empty($vnsList)) {
            try {
                $erRows = collect(DB::connection('hosxp')->select("
                    SELECT er.vn, er.er_emergency_type, er.er_emergency_level_id,
                           TIME_FORMAT(COALESCE(er.er_time_1, er.enter_er_time), '%H%i') as aetime
                    FROM er_regist er
                    WHERE er.vn IN ($vnPlaceholders)
                ", $vnsList))->keyBy('vn');

                $referOutByVn = $referOuts->keyBy('vn');
                $referInByVn = $referIns->keyBy('vn');

                foreach ($visits as $v) {
                    $hasEr = $erRows->has($v->vn);
                    $ro = $referOutByVn->get($v->vn);
                    $ri = $referInByVn->get($v->vn);
                    $ucae = trim((string)($v->nhso_ucae_type_code ?? ''));
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
                            'ucae' => $ucae ?: 'N'
                        ]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Query rcpt_print for Invoice Numbers
        $invoices = collect();
        if (!empty($vnsList)) {
            try {
                $invRows = collect(DB::connection('hosxp')->select("
                    SELECT vn, finance_number, book_number, receipt_number, total_amount
                    FROM rcpt_print
                    WHERE vn IN ($vnPlaceholders)
                ", $vnsList))->groupBy('vn');
                $invoices = $invRows;
            } catch (\Throwable $e) {}
        }

        // -------------------------------------------------------------
        // 7. Query Lab Chronic / LABFU
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
                    WHERE lh.vn IN ($vnPlaceholders)
                      AND lo.lab_order_result IS NOT NULL 
                      AND lo.lab_order_result != ''
                      AND lo.confirm = 'Y'
                    ORDER BY lh.vn, lo.lab_items_code
                ", $vnsList);
                $labOrders = collect($labRows);
            } catch (\Throwable $e) {
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
                        WHERE lh.vn IN ($vnPlaceholders)
                          AND lo.lab_order_result IS NOT NULL 
                          AND lo.lab_order_result != ''
                        ORDER BY lh.vn, lo.lab_items_code
                    ", $vnsList);
                    $labOrders = collect($labRows);
                } catch (\Throwable $e2) {}
            }
        }

        // =============================================================
        // BUILD 16/17 แฟ้ม FDH สำหรับ OPD
        // =============================================================

        // 1. INS.txt (19 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HN|INSCL|SUBTYPE|CID|HCODE|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE
        $insLines = ["HN|INSCL|SUBTYPE|CID|HCODE|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE"];
        foreach ($visits as $v) {
            $cid = trim((string)$v->cid);
            $permitno = trim((string)$v->permitno);
            $inscl = self::mapChtPttype($v->hipdata_code, $v->pttype);
            $subtype = $v->pttype_nhso_code ?: '10';
            $dateexp = self::formatDate($v->dateexp ?? '');
            $hospmain = trim((string)$v->hospmain) ?: $hcode;
            $hospsub = trim((string)$v->hospsub);
            $govcode = trim((string)($v->gov_code ?? ''));
            $govname = trim((string)($v->gov_name ?? ''));
            $docno = trim((string)($v->docno ?? ''));
            $ownrpid = trim((string)($v->ownrpid ?? ''));
            $ownname = trim((string)($v->ownname ?? ''));
            $an = $v->an ?: '';
            $seq = !empty($v->an) ? '' : $v->vn;
            $subinscl = '';
            $relinscl = '';
            $htype = !empty($v->an) ? '2' : '1';

            $insLines[] = "{$v->hn}|{$inscl}|{$subtype}|{$cid}|{$hcode}|{$dateexp}|{$hospmain}|{$hospsub}|{$govcode}|{$govname}|{$permitno}|{$docno}|{$ownrpid}|{$ownname}|{$an}|{$seq}|{$subinscl}|{$relinscl}|{$htype}";
        }

        // 2. PAT.txt (15 คอลัมน์)
        // HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE
        $patLines = ["HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE"];
        $seenPat = [];
        foreach ($visits as $v) {
            if (isset($seenPat[$v->hn])) continue;
            $seenPat[$v->hn] = true;

            $chw = str_pad(trim((string)$v->chwpart), 2, '0', STR_PAD_LEFT);
            $amp = str_pad(trim((string)$v->amppart), 2, '0', STR_PAD_LEFT);
            $dob = self::formatDate($v->birthday);
            $sex = $v->sex === '2' ? '2' : '1';
            $marriage = $v->marrystatus ?: '1';
            $occupa = str_pad(trim((string)$v->occupation), 3, '0', STR_PAD_LEFT);
            if ($occupa === '000') $occupa = '000';
            $nation = str_pad(trim((string)$v->nationality), 3, '0', STR_PAD_LEFT);
            if ($nation === '000' || empty($nation)) $nation = '099';
            $cid = trim((string)$v->cid);
            $title = trim((string)$v->pname);
            $fname = trim((string)$v->fname);
            $lname = trim((string)$v->lname);
            $namepat = "{$fname}  {$lname} , {$title}";
            $idtype = '1';

            $patLines[] = "{$hcode}|{$v->hn}|{$chw}|{$amp}|{$dob}|{$sex}|{$marriage}|{$occupa}|{$nation}|{$cid}|{$namepat}|{$title}|{$fname}|{$lname}|{$idtype}";
        }

        // 3. OPD.txt (15 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC|DETAIL|BTEMP|SBP|DBP|PR|RR|OPTYPE|TYPEIN|TYPEOUT
        $opdLines = ["HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC|DETAIL|BTEMP|SBP|DBP|PR|RR|OPTYPE|TYPEIN|TYPEOUT"];
        foreach ($visits as $v) {
            $clinic = self::formatClinic($v->spclty);
            $dateopd = self::formatDate($v->vstdate);
            $timeopd = self::formatTime($v->vsttime);
            $seq = $v->vn;
            $uuc = '1';
            $detail = str_replace(['|', "\r", "\n"], ' ', trim((string)$v->cc_detail));
            $btemp = !empty($v->btemp) ? number_format((float)$v->btemp, 1, '.', '') : '';
            $sbp = !empty($v->sbp) ? (string)intval($v->sbp) : '';
            $dbp = !empty($v->dbp) ? (string)intval($v->dbp) : '';
            $pr = !empty($v->pr) ? (string)intval($v->pr) : '';
            $rr = !empty($v->rr) ? (string)intval($v->rr) : '';
            $optype = trim((string)($v->pt_subtype ?: '1'));
            if (!in_array($optype, ['1', '2', '3', '4', '5'])) $optype = '1';
            $typein = trim((string)($v->ovstist ?: '1'));
            if (!in_array($typein, ['1', '2', '3', '4'])) $typein = '1';
            $typeout = trim((string)($v->ovstost ?: '1'));
            if (!in_array($typeout, ['1', '2', '3', '4', '5'])) $typeout = '1';

            $opdLines[] = "{$v->hn}|{$clinic}|{$dateopd}|{$timeopd}|{$seq}|{$uuc}|{$detail}|{$btemp}|{$sbp}|{$dbp}|{$pr}|{$rr}|{$optype}|{$typein}|{$typeout}";
        }

        // 4. ORF.txt (7 คอลัมน์)
        // HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE
        $orfLines = ["HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE"];
        $visitsByVn = $visits->keyBy('vn');
        foreach ($referOuts as $ro) {
            $v = $visitsByVn->get($ro->vn);
            $dateopd = self::formatDate($v->vstdate ?? $ro->vstdate ?? $ro->refer_date);
            $clinic = self::formatClinic($ro->spclty ?? ($v->spclty ?? ''));
            $refer = trim((string)$ro->refer_hospcode);
            $refertype = $ro->refer_type ?: '2';
            $seq = $ro->vn;
            $referdate = self::formatDate($ro->refer_date);

            $orfLines[] = "{$ro->hn}|{$dateopd}|{$clinic}|{$refer}|{$refertype}|{$seq}|{$referdate}";
        }
        foreach ($referIns as $ri) {
            $v = $visitsByVn->get($ri->vn);
            $dateopd = self::formatDate($v->vstdate ?? $ri->vstdate ?? $ri->refer_date);
            $clinic = self::formatClinic($ri->spclty ?? ($v->spclty ?? ''));
            $refer = trim((string)$ri->refer_hospcode);
            $refertype = $ri->refer_type ?: '1';
            $seq = $ri->vn;
            $referdate = self::formatDate($ri->refer_date);

            $orfLines[] = "{$ri->hn}|{$dateopd}|{$clinic}|{$refer}|{$refertype}|{$seq}|{$referdate}";
        }

        // 5. ODX.txt (8 คอลัมน์)
        // HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ
        $odxLines = ["HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ"];
        $seenOdx = [];
        foreach ($opdDiags as $d) {
            $datedx = self::formatDate($d->vstdate);
            $v = $visitsByVn->get($d->vn);
            $clinic = self::formatClinic($v ? $v->spclty : '01');
            $diag = strtoupper(str_replace('.', '', trim((string)$d->icd10)));
            if (empty($diag)) continue;
            if (preg_match('/^[0-9]/', $diag)) continue; // รหัสตัวเลขเป็น ICD-9 หัตถการ ข้ามไปแฟ้ม OOP
            $dxtype = $d->diagtype ?: '1';
            $drdx = $d->doctor_license ?: ($d->doctor ?: 'ว00000');
            if (str_starts_with($drdx, '-') && strlen($drdx) > 6) {
                $drdx = substr($drdx, 0, 6);
            }
            $cid = trim((string)$d->cid);
            $seq = $d->vn;

            $key = "{$seq}_{$diag}_{$dxtype}";
            if (isset($seenOdx[$key])) continue;
            $seenOdx[$key] = true;

            $odxLines[] = "{$d->hn}|{$datedx}|{$clinic}|{$diag}|{$dxtype}|{$drdx}|{$cid}|{$seq}";
        }

        // 6. OOP.txt (8 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ|SERVPRICE
        $oopLines = ["HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ|SERVPRICE"];
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
            $servprice = number_format((float)($op->servprice ?: 0.0), 2, '.', '');

            $key = "{$seq}_{$oper}_{$dropid}";
            if (isset($seenOop[$key])) continue;
            $seenOop[$key] = true;

            $oopLines[] = "{$op->hn}|{$dateopd}|{$clinic}|{$oper}|{$dropid}|{$cid}|{$seq}|{$servprice}";
        }

        // 7. IPD.txt (13 คอลัมน์ - ว่างสำหรับ OPD)
        $ipdLines = ["HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE"];

        // 8. IRF.txt (3 คอลัมน์ - ว่างสำหรับ OPD)
        $irfLines = ["AN|REFER|REFERTYPE"];

        // 9. IDX.txt (4 คอลัมน์ - ว่างสำหรับ OPD)
        $idxLines = ["AN|DIAG|DXTYPE|DRDX"];

        // 10. IOP.txt (8 คอลัมน์ - ว่างสำหรับ OPD)
        $iopLines = ["AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT"];

        // 11. CHT.txt (11 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ|OPD_MEMO|INVOICE_NO|INVOICE_LT
        $chtLines = ["HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ|OPD_MEMO|INVOICE_NO|INVOICE_LT"];
        foreach ($visits as $v) {
            $date = self::formatDate($v->vstdate);
            $total = number_format((float)$v->income, 2, '.', '');
            $paid = number_format((float)($v->rcpt_money ?: 0.0), 2, '.', '');
            $pttype = self::mapChtPttype($v->hipdata_code, $v->pttype);
            $cid = trim((string)$v->cid);
            $an = '';
            $seq = $v->vn;
            $opdMemo = '';
            
            $invGroup = $invoices->get($v->vn);
            $invNo = '';
            $invLt = '';
            if ($invGroup && $invGroup->isNotEmpty()) {
                $firstInv = $invGroup->first();
                $invNo = trim((string)($firstInv->finance_number ?: $firstInv->receipt_number));
                $invLt = trim((string)$firstInv->book_number);
            }

            $chtLines[] = "{$v->hn}|{$an}|{$date}|{$total}|{$paid}|{$pttype}|{$cid}|{$seq}|{$opdMemo}|{$invNo}|{$invLt}";
        }

        // 12. CHA.txt (7 คอลัมน์)
        // HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ
        $chaLines = ["HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ"];
        $itemsByVn = $items->groupBy('vn');
        foreach ($visits as $v) {
            $vnItems = $itemsByVn->get($v->vn, collect());
            $date = self::formatDate($v->vstdate);
            $cid = trim((string)$v->cid);
            $seq = $v->vn;

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
                $chaLines[] = "{$v->hn}||{$date}|{$chrg}|{$amtStr}|{$cid}|{$seq}";
            }
        }

        // 13. AER.txt (18 คอลัมน์)
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
            $ucae = in_array(trim((string)($er->ucae ?? '')), ['A', 'E', 'I', 'O', 'C', 'Z']) ? trim((string)$er->ucae) : 'N';
            $emtype = '3';
            $seq = $er->vn;
            $an = '';

            $aerLines[] = "{$er->hn}|{$an}|{$dateopd}|{$authae}|{$aedate}|{$aetime}|{$aetype}|{$referno}|{$refmaini}|{$ireftype}|{$refmaino}|{$oreftype}|{$ucae}|{$emtype}|{$seq}|||";
        }

        // 14. ADP.txt (27 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM
        $adpLines = ["HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM"];
        
        $adpItems = $items->filter(function($it) {
            $isDrug = str_starts_with((string)$it->icode, '1');
            if (!$isDrug) {
                return true;
            } else {
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
            $seq = $it->vn;
            $an = '';
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
            $itemsrc = '1';
            $provider = $it->doctor_license ?: 'ว00000';
            $gravida = '';
            $gaweek = '';
            $dcip = '';
            $lmp = '';
            $spitem = '';

            $adpLines[] = "{$it->hn}|{$an}|{$dateopd}|{$type}|{$code}|{$qty}|{$rateStr}|{$seq}|{$cagcode}|{$dose}|{$catype}|{$serialno}|{$totcopay}|{$usestatus}|{$total}|{$qtyday}|{$tmltcode}|{$status1}|{$bi}|{$clinic}|{$itemsrc}|{$provider}|{$gravida}|{$gaweek}|{$dcip}|{$lmp}|{$spitem}";
        }

        // ตรวจสอบและเพิ่ม ADP TYPE = 5 (โครงการบริการ เช่น WALKIN / 30 บาทรักษาทุกที่)
        foreach ($visits as $v) {
            if ($v->pt_subtype === 'O1' || str_contains(strtolower((string)$v->pttype), 'walkin') || str_contains(strtolower((string)$v->pttype_nhso_code), 'walkin')) {
                $dateopd = self::formatDate($v->vstdate);
                $type = '5';
                $code = 'WALKIN';
                $qty = '1';
                $rateStr = '0';
                $seq = $v->vn;
                $total = '0.00';
                $clinic = self::formatClinic($v->spclty);
                $provider = $v->doctor_license ?: 'ว00000';

                $adpLines[] = "{$v->hn}||{$dateopd}|{$type}|{$code}|{$qty}|{$rateStr}|{$seq}|||||0||{$total}|||||{$clinic}|1|{$provider}|||||";
            }
        }

        // 15. LVD.txt (7 คอลัมน์ - ว่างสำหรับ OPD)
        $lvdLines = ["SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY"];

        // 16. DRU.txt (24 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER|SP_ITEM
        $druLines = ["HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER|SP_ITEM"];
        $drugItems = $items->filter(function($it) {
            return str_starts_with((string)$it->icode, '1');
        });

        foreach ($drugItems as $it) {
            $v = $visitsByVn->get($it->vn);
            $cid = $v ? trim((string)$v->cid) : '';
            $clinic = self::formatClinic($it->spclty ?: ($v ? $v->spclty : '01'));
            $dateserv = self::formatDate($it->vstdate ?: ($v ? $v->vstdate : ''));
            $did = trim((string)$it->icode);
            
            $nameParts = array_filter([trim((string)$it->drug_name), trim((string)$it->drug_strength), trim((string)$it->drug_unit)]);
            $didname = str_replace('|', ' ', implode(' ', $nameParts));

            $amount = (float)$it->qty;
            $amountStr = $amount == floor($amount) ? (string)intval($amount) : number_format($amount, 2, '.', '');
            $drugprice = number_format((float)$it->unitprice, 2, '.', '');
            $drugcost = number_format((float)$it->cost, 2, '.', '');
            $didstd = $it->sks_drug_code ?: ($it->tmt_tp_code ?: ($it->tmt_gp_code ?: ($it->ttmt_code ?: ($it->drug_did ?: $did))));
            $unit = trim((string)$it->drug_unit) ?: 'เม็ด';
            $unitpack = trim((string)$it->drug_pack) ? "1x{$it->drug_pack}" : "1x{$unit}";
            $seq = $it->vn;
            $drugremark = '';
            $pano = '';
            $totcopay = '0';
            $usestatus = '2'; // 1=In-hospital, 2=Home
            $total = number_format((float)$it->sum_price, 2, '.', '');
            $sigcode = trim((string)$it->sigcode);
            $sigtext = trim(implode(' ', array_filter([$it->sigtext1, $it->sigtext2, $it->sigtext3])));
            $provider = $it->doctor_license ?: ($v ? ($v->doctor_license ?: 'ว00000') : 'ว00000');
            $spitem = '';

            $druLines[] = "{$hcode}|{$it->hn}||{$clinic}|{$cid}|{$dateserv}|{$did}|{$didname}|{$amountStr}|{$drugprice}|{$drugcost}|{$didstd}|{$unit}|{$unitpack}|{$seq}|{$drugremark}|{$pano}|{$totcopay}|{$usestatus}|{$total}|{$sigcode}|{$sigtext}|{$provider}|{$spitem}";
        }

        // 17. LABFU.txt (7 คอลัมน์)
        // HCODE|HN|PERSON_ID|DATESERV|SEQ|LABTEST|LABRESULT
        $labLines = ["HCODE|HN|PERSON_ID|DATESERV|SEQ|LABTEST|LABRESULT"];
        $seenLab = [];
        foreach ($labOrders as $lab) {
            $labName = $lab->lab_items_name_ref ?? $lab->lab_items_name ?? '';
            $labTest = $lab->labtest ?? '';
            $tmlt = $lab->tmlt_code ?? '';
            $provis = $lab->provis_labcode ?? '';
            $labTestCode = self::mapLabTestCode($labTest, $tmlt, $provis, $labName);
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

        // COMPILE RESULT FOR OPD
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
            'LABFU' => implode("\r\n", $labLines),
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
            'LABFU' => count($labLines) - 1,
        ];

        return [
            'status' => 'success',
            'files' => $files,
            'counts' => $counts,
            'total_visits' => count($visits),
            'hcode' => $hcode
        ];
    }

    /**
     * =========================================================================
     * GENERATE 16/17 แฟ้มมาตรฐาน FDH สำหรับผู้ป่วยใน (IPD - DRG)
     * =========================================================================
     */
    public static function generate16FilesIp(array $ans, array $options = []): array
    {
        if (empty($ans)) {
            return [
                'files' => [],
                'counts' => [],
                'total_visits' => 0,
                'hcode' => self::getHcode()
            ];
        }

        $hcode = self::getHcode();
        $placeholders = implode(',', array_fill(0, count($ans), '?'));

        // -------------------------------------------------------------
        // 1. Query IPD Admission Details (ipt, an_stat, patient, visit_pttype, pttype, doctor)
        // -------------------------------------------------------------
        $admissions = collect();
        try {
            $admRows = DB::connection('hosxp')->select("
                SELECT ipt.an, ipt.vn, ipt.hn, ipt.regdate, ipt.regtime, ipt.dchdate, ipt.dchtime,
                       ipt.dchstts as dischs, ipt.dchtype as discht, ipt.ward as warddsc,
                       ipt.spclty as dept, ipt.bw as adm_w, '' as svctype,
                       ipt.pttype,
                       a.pdx, a.dx_doctor, a.income, a.paid_money, a.rcpt_money, a.uc_money,
                       pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex, pt.marrystatus, pt.occupation, pt.nationality,
                       pt.chwpart, pt.amppart, pt.tmbpart,
                       p.hipdata_code,
                       COALESCE(p.nhso_code, ipt.pttype, '10') as pttype_nhso_code,
                       doc.licenseno as doctor_license, doc.name as doctor_name,
                       COALESCE(ip.hospmain, '') as hospmain,
                       COALESCE(ip.hospsub, '') as hospsub,
                       COALESCE(ip.claim_code, ip.auth_code, '') as permitno
                FROM ipt
                LEFT JOIN an_stat a ON a.an = ipt.an
                LEFT JOIN patient pt ON pt.hn = ipt.hn
                LEFT JOIN ipt_pttype ip ON ip.an = ipt.an
                LEFT JOIN pttype p ON p.pttype = COALESCE(ip.pttype, ipt.pttype)
                LEFT JOIN doctor doc ON doc.code = ipt.admdoctor
                WHERE ipt.an IN ($placeholders)
                ORDER BY ipt.regdate, ipt.regtime
            ", $ans);
            $admissions = collect($admRows);
        } catch (\Throwable $e) {
            Log::error("FDH Export IPD admission query error: " . $e->getMessage());
            throw $e;
        }

        $ansList = array_values($admissions->pluck('an')->toArray());
        $anPlaceholders = !empty($ansList) ? implode(',', array_fill(0, count($ansList), '?')) : '';
        $vnsList = array_values($admissions->pluck('vn')->filter()->toArray());
        $cids = $admissions->pluck('cid')->filter()->unique()->toArray();

        // -------------------------------------------------------------
        // 2. Query IPD Diags (iptdiag)
        // -------------------------------------------------------------
        $ipdDiags = collect();
        if (!empty($ansList)) {
            try {
                $diagRows = DB::connection('hosxp')->select("
                    SELECT id.an, id.icd10, id.diagtype, doc.licenseno as drdx
                    FROM iptdiag id
                    LEFT JOIN doctor doc ON doc.code = id.doctor
                    WHERE id.an IN ($anPlaceholders)
                    ORDER BY id.an, id.diagtype
                ", $ansList);
                $ipdDiags = collect($diagRows);
            } catch (\Throwable $e) {}
        }

        // -------------------------------------------------------------
        // 3. Query IPD Procedures (iptoprt)
        // -------------------------------------------------------------
        $ipdOpers = collect();
        if (!empty($ansList)) {
            try {
                $operRows = DB::connection('hosxp')->select("
                    SELECT io.an, io.icd9 as oper, io.opertype, doc.licenseno as dropid,
                           io.opdate as datein, io.optime as timein, io.enddate as dateout, io.endtime as timeout
                    FROM iptoprt io
                    LEFT JOIN doctor doc ON doc.code = io.doctor
                    WHERE io.an IN ($anPlaceholders)
                    ORDER BY io.an
                ", $ansList);
                $ipdOpers = collect($operRows);
            } catch (\Throwable $e) {}
        }

        // -------------------------------------------------------------
        // 4. Query IPD Refer (referout / referin)
        // -------------------------------------------------------------
        $ipdRefers = collect();
        $ipdReferIns = collect();
        $lookupKeys = array_unique(array_merge($ansList, $vnsList));
        $lookupPlaceholders = implode(',', array_fill(0, count($lookupKeys), '?'));
        
        if (!empty($lookupKeys)) {
            try {
                $referRows = DB::connection('hosxp')->select("
                    SELECT ro.vn, ro.hn, ro.refer_date,
                           TIME_FORMAT(ro.refer_time, '%H%i') as refer_time,
                           COALESCE(NULLIF(ro.refer_hospcode, ''), ro.hospcode) as refer_hospcode,
                           COALESCE(ro.refer_type, '2') as refer_type,
                           ro.refer_number, o.spclty, o.vstdate
                    FROM referout ro
                    LEFT JOIN ovst o ON o.vn = ro.vn
                    WHERE ro.vn IN ($lookupPlaceholders)
                ", $lookupKeys);
                $ipdRefers = collect($referRows);
            } catch (\Throwable $e) {}

            try {
                $referInRows = DB::connection('hosxp')->select("
                    SELECT ri.vn, ri.hn, ri.refer_date,
                           TIME_FORMAT(ri.refer_time, '%H%i') as refer_time,
                           COALESCE(NULLIF(ri.refer_hospcode, ''), ri.hospcode) as refer_hospcode,
                           COALESCE(ri.refer_type, '1') as refer_type,
                           ri.docno as refer_number, o.spclty, o.vstdate
                    FROM referin ri
                    LEFT JOIN ovst o ON o.vn = ri.vn
                    WHERE ri.vn IN ($lookupPlaceholders)
                ", $lookupKeys);
                $ipdReferIns = collect($referInRows);
            } catch (\Throwable $e) {}
        }

        // -------------------------------------------------------------
        // 5. Query Leaves (ipt_leave)
        // -------------------------------------------------------------
        $ipdLeaves = collect();
        if (!empty($ansList)) {
            try {
                $leaveRows = DB::connection('hosxp')->select("
                    SELECT l.an, l.leave_date as dateout, l.leave_time as timeout,
                           l.back_date as datein, l.back_time as timein,
                           COALESCE(l.leave_day_count, DATEDIFF(l.back_date, l.leave_date), 1) as qtyday
                    FROM ipt_leave l
                    WHERE l.an IN ($anPlaceholders)
                    ORDER BY l.an, l.leave_date
                ", $ansList);
                $ipdLeaves = collect($leaveRows);
            } catch (\Throwable $e) {}
        }

        // -------------------------------------------------------------
        // 6. Query IPD Items (opitemrece)
        // -------------------------------------------------------------
        $items = collect();
        if (!empty($ansList)) {
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
                           pt.cid, COALESCE(doc.licenseno, '') as doctor_license,
                           op.drugusage, du.code as sigcode, du.name1 as sigtext1, du.name2 as sigtext2, du.name3 as sigtext3
                    FROM opitemrece op
                    LEFT JOIN patient pt ON pt.hn = op.hn
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN income inc ON inc.income = op.income
                    LEFT JOIN drg_chrgitem drg ON drg.drg_chrgitem_id = inc.drg_chrgitem_id
                    LEFT JOIN doctor doc ON doc.code = op.doctor
                    LEFT JOIN drugusage du ON du.drugusage = op.drugusage
                    WHERE op.an IN ($anPlaceholders)
                    ORDER BY op.an, op.item_no
                ", $ansList);
                $items = collect($itemRows);
            } catch (\Throwable $e) {}
        }

        // =============================================================
        // BUILD 16/17 แฟ้ม FDH สำหรับ IPD
        // =============================================================

        // 1. INS.txt (19 คอลัมน์)
        $insLines = ["HN|INSCL|SUBTYPE|CID|HCODE|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE"];
        foreach ($admissions as $v) {
            $cid = trim((string)$v->cid);
            $permitno = trim((string)$v->permitno);
            $inscl = self::mapChtPttype($v->hipdata_code, $v->pttype);
            $subtype = $v->pttype_nhso_code ?: '10';
            $dateexp = '';
            $hospmain = trim((string)$v->hospmain) ?: $hcode;
            $hospsub = trim((string)$v->hospsub);
            $govcode = '';
            $govname = '';
            $docno = '';
            $ownrpid = '';
            $ownname = '';
            $an = $v->an;
            $seq = $v->an;
            $subinscl = '';
            $relinscl = '';
            $htype = '';

            $insLines[] = "{$v->hn}|{$inscl}|{$subtype}|{$cid}|{$hcode}|{$dateexp}|{$hospmain}|{$hospsub}|{$govcode}|{$govname}|{$permitno}|{$docno}|{$ownrpid}|{$ownname}|{$an}|{$seq}|{$subinscl}|{$relinscl}|{$htype}";
        }

        // 2. PAT.txt (15 คอลัมน์)
        $patLines = ["HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE"];
        $seenPat = [];
        foreach ($admissions as $v) {
            if (isset($seenPat[$v->hn])) continue;
            $seenPat[$v->hn] = true;

            $chw = str_pad(trim((string)$v->chwpart), 2, '0', STR_PAD_LEFT);
            $amp = str_pad(trim((string)$v->amppart), 2, '0', STR_PAD_LEFT);
            $dob = self::formatDate($v->birthday);
            $sex = $v->sex === '2' ? '2' : '1';
            $marriage = $v->marrystatus ?: '1';
            $occupa = str_pad(trim((string)$v->occupation), 3, '0', STR_PAD_LEFT);
            if ($occupa === '000') $occupa = '000';
            $nation = str_pad(trim((string)$v->nationality), 3, '0', STR_PAD_LEFT);
            if ($nation === '000' || empty($nation)) $nation = '099';
            $cid = trim((string)$v->cid);
            $title = trim((string)$v->pname);
            $fname = trim((string)$v->fname);
            $lname = trim((string)$v->lname);
            $namepat = "{$fname}  {$lname} , {$title}";
            $idtype = '1';

            $patLines[] = "{$hcode}|{$v->hn}|{$chw}|{$amp}|{$dob}|{$sex}|{$marriage}|{$occupa}|{$nation}|{$cid}|{$namepat}|{$title}|{$fname}|{$lname}|{$idtype}";
        }

        // 3. OPD.txt (15 คอลัมน์ - ว่างสำหรับ IPD)
        $opdLines = ["HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC|DETAIL|BTEMP|SBP|DBP|PR|RR|OPTYPE|TYPEIN|TYPEOUT"];

        // 4. ORF.txt (7 คอลัมน์ - ว่างสำหรับ IPD)
        $orfLines = ["HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE"];

        // 5. ODX.txt (8 คอลัมน์ - ว่างสำหรับ IPD)
        $odxLines = ["HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ"];

        // 6. OOP.txt (8 คอลัมน์ - ว่างสำหรับ IPD)
        $oopLines = ["HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ|SERVPRICE"];

        // 7. IPD.txt (13 คอลัมน์)
        // HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE
        $ipdLines = ["HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE"];
        foreach ($admissions as $v) {
            $dateadm = self::formatDate($v->regdate);
            $timeadm = self::formatTime($v->regtime);
            $datedsc = self::formatDate($v->dchdate);
            $timedsc = self::formatTime($v->dchtime);
            $dischs = $v->dischs ?: '1';
            $discht = $v->discht ?: '1';
            $warddsc = $v->warddsc ?: '01';
            $dept = self::formatClinic($v->dept);
            $admw = !empty($v->adm_w) ? number_format((float)$v->adm_w, 2, '.', '') : '0.00';
            $uuc = '1';
            $svctype = '';

            $ipdLines[] = "{$v->hn}|{$v->an}|{$dateadm}|{$timeadm}|{$datedsc}|{$timedsc}|{$dischs}|{$discht}|{$warddsc}|{$dept}|{$admw}|{$uuc}|{$svctype}";
        }

        // 8. IRF.txt (3 คอลัมน์)
        // AN|REFER|REFERTYPE
        $irfLines = ["AN|REFER|REFERTYPE"];
        $admissionsByAn = $admissions->keyBy('an');
        $admissionsByVn = $admissions->keyBy('vn');

        foreach ($ipdRefers as $ro) {
            $refer = trim((string)$ro->refer_hospcode);
            $refertype = $ro->refer_type ?: '2';
            $an = '';
            if ($admissionsByAn->has($ro->vn)) {
                $an = $ro->vn;
            } elseif ($admissionsByVn->has($ro->vn)) {
                $an = $admissionsByVn->get($ro->vn)->an;
            }
            if (!empty($an)) {
                $irfLines[] = "{$an}|{$refer}|{$refertype}";
            }
        }
        foreach ($ipdReferIns as $ri) {
            $refer = trim((string)$ri->refer_hospcode);
            $refertype = $ri->refer_type ?: '1';
            $an = '';
            if ($admissionsByAn->has($ri->vn)) {
                $an = $ri->vn;
            } elseif ($admissionsByVn->has($ri->vn)) {
                $an = $admissionsByVn->get($ri->vn)->an;
            }
            if (!empty($an)) {
                $irfLines[] = "{$an}|{$refer}|{$refertype}";
            }
        }

        // 9. IDX.txt (4 คอลัมน์)
        // AN|DIAG|DXTYPE|DRDX
        $idxLines = ["AN|DIAG|DXTYPE|DRDX"];
        foreach ($ipdDiags as $id) {
            $diag = strtoupper(str_replace('.', '', trim((string)$id->icd10)));
            if (empty($diag)) continue;
            $dxtype = $id->diagtype ?: '1';
            $drdx = $id->drdx ?: 'ว00000';

            $idxLines[] = "{$id->an}|{$diag}|{$dxtype}|{$drdx}";
        }

        // 10. IOP.txt (8 คอลัมน์)
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

        // 11. CHT.txt (11 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ|OPD_MEMO|INVOICE_NO|INVOICE_LT
        $chtLines = ["HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ|OPD_MEMO|INVOICE_NO|INVOICE_LT"];
        foreach ($admissions as $v) {
            $date = self::formatDate($v->dchdate ?: $v->regdate);
            $total = number_format((float)$v->income, 2, '.', '');
            $paid = number_format((float)($v->rcpt_money ?: 0.0), 2, '.', '');
            $pttype = self::mapChtPttype($v->hipdata_code, $v->pttype);
            $cid = trim((string)$v->cid);
            $an = $v->an;
            $seq = $v->an;
            $opdMemo = '';
            $invNo = '';
            $invLt = '';

            $chtLines[] = "{$v->hn}|{$an}|{$date}|{$total}|{$paid}|{$pttype}|{$cid}|{$seq}|{$opdMemo}|{$invNo}|{$invLt}";
        }

        // 12. CHA.txt (7 คอลัมน์)
        // HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ
        $chaLines = ["HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ"];
        $itemsByAn = $items->groupBy('an');
        foreach ($admissions as $v) {
            $anItems = $itemsByAn->get($v->an, collect());
            $date = self::formatDate($v->dchdate ?: $v->regdate);
            $cid = trim((string)$v->cid);
            $seq = $v->an;

            $chaGroups = [];
            foreach ($anItems as $it) {
                $chrg = self::mapIncomeToChaItem($it->income);
                if (!isset($chaGroups[$chrg])) {
                    $chaGroups[$chrg] = 0.0;
                }
                $chaGroups[$chrg] += (float)$it->sum_price;
            }

            ksort($chaGroups);
            foreach ($chaGroups as $chrg => $sumAmt) {
                $amtStr = number_format($sumAmt, 2, '.', '');
                $chaLines[] = "{$v->hn}|{$v->an}|{$date}|{$chrg}|{$amtStr}|{$cid}|{$seq}";
            }
        }

        // 13. AER.txt (18 คอลัมน์)
        $aerLines = ["HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT"];

        // 14. ADP.txt (27 คอลัมน์)
        $adpLines = ["HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM"];
        $adpItems = $items->filter(function($it) {
            $isDrug = str_starts_with((string)$it->icode, '1');
            if (!$isDrug) {
                return true;
            } else {
                return !empty(trim((string)$it->nhso_adp_code));
            }
        });

        foreach ($adpItems as $it) {
            $adm = $admissionsByAn->get($it->an);
            $dateopd = self::formatDate($it->vstdate ?: ($adm ? $adm->dchdate : ''));
            $type = trim((string)($it->nhso_adp_type ?: '17'));
            $code = trim((string)($it->nhso_adp_code ?: $it->icode));
            $qty = intval($it->qty) ?: 1;
            $rate = (float)$it->unitprice;
            $rateStr = $rate == floor($rate) ? (string)intval($rate) : number_format($rate, 2, '.', '');
            $seq = $it->an;
            $an = $it->an;
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
            $clinic = self::formatClinic($adm ? $adm->dept : '01');
            $itemsrc = '1';
            $provider = $it->doctor_license ?: ($adm ? ($adm->doctor_license ?: 'ว00000') : 'ว00000');
            $gravida = '';
            $gaweek = '';
            $dcip = '';
            $lmp = '';
            $spitem = '';

            $adpLines[] = "{$it->hn}|{$an}|{$dateopd}|{$type}|{$code}|{$qty}|{$rateStr}|{$seq}|{$cagcode}|{$dose}|{$catype}|{$serialno}|{$totcopay}|{$usestatus}|{$total}|{$qtyday}|{$tmltcode}|{$status1}|{$bi}|{$clinic}|{$itemsrc}|{$provider}|{$gravida}|{$gaweek}|{$dcip}|{$lmp}|{$spitem}";
        }

        // 15. LVD.txt (7 คอลัมน์)
        $lvdLines = ["SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY"];
        $seqLvd = 1;
        foreach ($ipdLeaves as $l) {
            $dateout = self::formatDate($l->dateout);
            $timeout = self::formatTime($l->timeout);
            $datein = self::formatDate($l->datein);
            $timein = self::formatTime($l->timein);
            $qtyday = intval($l->qtyday) ?: 1;

            $lvdLines[] = "{$seqLvd}|{$l->an}|{$dateout}|{$timeout}|{$datein}|{$timein}|{$qtyday}";
            $seqLvd++;
        }

        // 16. DRU.txt (24 คอลัมน์)
        $druLines = ["HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER|SP_ITEM"];
        $drugItems = $items->filter(function($it) {
            return str_starts_with((string)$it->icode, '1');
        });

        foreach ($drugItems as $it) {
            $adm = $admissionsByAn->get($it->an);
            $cid = $adm ? trim((string)$adm->cid) : '';
            $clinic = self::formatClinic($adm ? $adm->dept : '01');
            $dateserv = self::formatDate($it->vstdate ?: ($adm ? $adm->dchdate : ''));
            $did = trim((string)$it->icode);
            
            $nameParts = array_filter([trim((string)$it->drug_name), trim((string)$it->drug_strength), trim((string)$it->drug_unit)]);
            $didname = str_replace('|', ' ', implode(' ', $nameParts));

            $amount = (float)$it->qty;
            $amountStr = $amount == floor($amount) ? (string)intval($amount) : number_format($amount, 2, '.', '');
            $drugprice = number_format((float)$it->unitprice, 2, '.', '');
            $drugcost = number_format((float)$it->cost, 2, '.', '');
            $didstd = $it->sks_drug_code ?: ($it->tmt_tp_code ?: ($it->tmt_gp_code ?: ($it->ttmt_code ?: ($it->drug_did ?: $did))));
            $unit = trim((string)$it->drug_unit) ?: 'เม็ด';
            $unitpack = trim((string)$it->drug_pack) ? "1x{$it->drug_pack}" : "1x{$unit}";
            $seq = $it->an;
            $drugremark = '';
            $pano = '';
            $totcopay = '0';
            $usestatus = '1'; // 1=In-hospital
            $total = number_format((float)$it->sum_price, 2, '.', '');
            $sigcode = trim((string)$it->sigcode);
            $sigtext = trim(implode(' ', array_filter([$it->sigtext1, $it->sigtext2, $it->sigtext3])));
            $provider = $it->doctor_license ?: ($adm ? ($adm->doctor_license ?: 'ว00000') : 'ว00000');
            $spitem = '';

            $druLines[] = "{$hcode}|{$it->hn}|{$it->an}|{$clinic}|{$cid}|{$dateserv}|{$did}|{$didname}|{$amountStr}|{$drugprice}|{$drugcost}|{$didstd}|{$unit}|{$unitpack}|{$seq}|{$drugremark}|{$pano}|{$totcopay}|{$usestatus}|{$total}|{$sigcode}|{$sigtext}|{$provider}|{$spitem}";
        }

        // 17. LABFU.txt (7 คอลัมน์)
        $labLines = ["HCODE|HN|PERSON_ID|DATESERV|SEQ|LABTEST|LABRESULT"];

        // COMPILE RESULT FOR IPD
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
            'LABFU' => implode("\r\n", $labLines),
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
            'LABFU' => count($labLines) - 1,
        ];

        return [
            'status' => 'success',
            'files' => $files,
            'counts' => $counts,
            'total_visits' => count($admissions),
            'hcode' => $hcode
        ];
    }
}
