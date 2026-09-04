<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

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
     * ตัดทอนหรือฟอร์แมตรหัสสถานพยาบาล (5 หลัก โดยตัดเอา 5 หลักสุดท้าย เช่น EA0010703 -> 10703)
     */
    public static function formatHospcode($hospcode): string
    {
        $code = trim((string)$hospcode);
        if (empty($code)) return '';
        if (preg_match('/([0-9]{5})$/', $code, $m)) {
            return $m[1];
        }
        return strlen($code) > 5 ? substr($code, -5) : $code;
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
     * แปลงรหัสสิทธิการรักษาสำหรับ INS.txt (INSCL: UCS, OFC, SSS, LGO, PVT, ฯลฯ ความยาว 3 ตัวอักษร)
     */
    public static function mapInscl($hipdataCode, $pttype = null): string
    {
        $hip = strtoupper(trim((string)$hipdataCode));
        return !empty($hip) ? $hip : 'UCS';
    }

    /**
     * แปลงรหัสสิทธิการรักษาสำหรับ CHT.txt (PTTYPE: รหัสสิทธิ 2 หลัก เช่น 10, 89, 01, 30 ความยาวสูงสุดไม่เกิน 2 ตัวอักษร)
     */
    public static function mapChtPttype($hipdataCode, $pttype, $subtype = null): string
    {
        $sub = trim((string)$subtype);
        if (!empty($sub) && strlen($sub) <= 2) {
            return str_pad($sub, 2, '0', STR_PAD_LEFT);
        }

        $ptt = trim((string)$pttype);
        if (!empty($ptt) && strlen($ptt) <= 2 && is_numeric($ptt)) {
            return str_pad($ptt, 2, '0', STR_PAD_LEFT);
        }

        $hip = strtoupper(trim((string)$hipdataCode));
        if ($hip === 'UCS' || $hip === 'UC') return '10';
        if ($hip === 'OFC' || $hip === 'A2') return '01';
        if ($hip === 'SSS' || $hip === 'SS') return '30';
        if ($hip === 'LGO') return '20';
        if ($hip === 'STP') return '71';
        if ($hip === 'PVT') return '10';

        return '10';
    }

    /**
     * แปลงหมวดรายได้ของ HOSxP (income) ให้เป็นรหัสหมวดค่าบริการ 20 หมวด สปสช./FDH (CHRGITEM ใน CHA.txt: 2 หลัก เช่น D1, C1, I1, J2)
     */
    public static function mapIncomeToChaItem($income, $paidst = '02'): string
    {
        $inc = str_pad(trim((string)$income), 2, '0', STR_PAD_LEFT);
        $isPaidSelf = in_array(trim((string)$paidst), ['01', '03']); // จ่ายเอง / ส่วนเกิน
        $suffix = $isPaidSelf ? '2' : '1';

        $baseMap = [
            '01' => '1',  // ค่าห้อง/ค่าอาหาร -> 11 (เบิกได้) / 12 (ส่วนเกิน)
            '02' => '2',  // ค่าอวัยวะเทียม/อุปกรณ์ -> 21 / 22
            '03' => '3',  // ค่ายาและสารอาหารทางเส้นเลือดใน รพ. -> 31 / 32
            '04' => '4',  // ค่ายากลับบ้าน -> 41 / 42
            '05' => '5',  // ค่าเวชภัณฑ์ที่มิใช่ยา -> 51 / 52
            '06' => '6',  // ค่าบริการโลหิตและส่วนประกอบ -> 61 / 62
            '07' => '7',  // ค่าตรวจวินิจฉัยทางเทคนิคการแพทย์/พยาธิ -> 71 / 72
            '08' => '8',  // ค่าตรวจวินิจฉัยและรักษาทางรังสีวิทยา -> 81 / 82
            '09' => '9',  // ค่าตรวจวินิจฉัยโดยวิธีพิเศษอื่นๆ -> 91 / 92
            '10' => 'A',  // ค่าอุปกรณ์ของใช้และเครื่องมือทางการแพทย์ -> A1 / A2
            '11' => 'B',  // ค่าทำหัตถการและวิสัญญี -> B1 / B2
            '12' => 'C',  // ค่าบริการทางการพยาบาล -> C1 / C2
            '13' => 'D',  // ค่าบริการทางทันตกรรม -> D1 / D2
            '14' => 'E',  // ค่าบริการทางกายภาพบำบัด/เวชกรรมฟื้นฟู -> E1 / E2
            '15' => 'F',  // ค่าบริการการแพทย์แผนไทย/ฝังเข็ม -> F1 / F2
            '16' => 'J',  // ค่าบริการอื่นๆที่ไม่เกี่ยวกับการรักษาพยาบาลโดยตรง -> J1 / J2
            '17' => '4',  // ยานอกบัญชียาหลักแห่งชาติ -> 41 / 42
            '18' => 'H',  // ค่าธรรมเนียมทางการแพทย์ -> H1 / H2
            '19' => 'I',  // บริการอื่น ๆ และส่งเสริมป้องกันโรค -> I1 / I2
            '20' => '8',  // X-Ray -> 81 / 82
        ];

        $prefix = $baseMap[$inc] ?? 'J';
        return $prefix . $suffix;
    }

    /**
     * แปลงหมวดรายได้ของ HOSxP (income) ให้เป็นรหัสหมวดค่ารักษาพยาบาลสำหรับ ADP.txt (TYPE: 1-20 ตาม 16แฟ้มFDH.xlsx)
     */
    public static function mapIncomeToAdpType($income, $nhsoAdpType = null): string
    {
        $type = trim((string)$nhsoAdpType);
        if (!empty($type) && is_numeric($type) && intval($type) > 0 && intval($type) <= 20) {
            return (string)intval($type);
        }

        $inc = str_pad(trim((string)$income), 2, '0', STR_PAD_LEFT);
        $map = [
            '01' => '10', // ค่าห้อง/ค่าอาหาร
            '02' => '2',  // อวัยวะเทียม/อุปกรณ์ (Instrument)
            '05' => '11', // เวชภัณฑ์ที่ไม่ใช่ยา
            '06' => '14', // บริการโลหิตและส่วนประกอบ
            '07' => '15', // ตรวจวินิจฉัยทางเทคนิคการแพทย์และพยาธิวิทยา (Lab)
            '08' => '16', // ตรวจวินิจฉัยและรักษาทางรังสีวิทยา (X-Ray)
            '09' => '9',  // ตรวจวินิจฉัยโดยวิธีพิเศษอื่นๆ
            '10' => '18', // อุปกรณ์ของใช้และเครื่องมือทางการแพทย์
            '11' => '19', // ทำหัตถการและวิสัญญี
            '12' => '17', // ค่าบริการทางการพยาบาล
            '13' => '12', // ค่าบริการทางทันตกรรม
            '14' => '20', // ค่าบริการทางกายภาพบำบัดและเวชกรรมฟื้นฟู
            '15' => '13', // ค่าบริการฝังเข็ม/แพทย์แผนไทย
            '16' => '3',  // ค่าบริการอื่นๆ ที่ยังไม่ได้จัดหมวด
            '18' => '3',  // ค่าธรรมเนียมทางการแพทย์
            '19' => '4',  // ค่าส่งเสริมป้องกัน/บริการเฉพาะ (F6)
        ];

        return $map[$inc] ?? '3';
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
     * Normalize Non-ED reason code to standard 2-character code (EA, EB, EC, ED, EE, PA)
     * Note: EF (non-reimbursable) is converted to EC so it can be claimed/reimbursed properly.
     */
    public static function normalizeNedReason(?string $raw): string
    {
        if (empty($raw)) return 'EC';
        $trimmed = trim((string)$raw);
        $upper = strtoupper($trimmed);

        if ($upper === 'EF') {
            return 'EC';
        }
        if (in_array($upper, ['EA', 'EB', 'EC', 'ED', 'EE', 'PA'])) {
            return $upper;
        }
        if (preg_match('/^(EA|EB|EC|ED|EE|PA)\b/i', $trimmed, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/^EF\b/i', $trimmed)) {
            return 'EC';
        }
        if (in_array($upper, ['A', 'B', 'C', 'D', 'E'])) {
            return 'E' . $upper;
        }
        if ($upper === 'F') {
            return 'EC';
        }
        if (str_contains($trimmed, 'แพ้ยา') || str_contains($trimmed, 'ข้างเคียง') || str_contains($trimmed, 'ไม่พึงประสงค์')) {
            return 'EA';
        }
        if (str_contains($trimmed, 'ไม่บรรลุ') || str_contains($trimmed, 'ไม่ได้ผล') || str_contains($trimmed, 'ไม่บรรลุผล')) {
            return 'EB';
        }
        if (str_contains($trimmed, 'ไม่มียา') || str_contains($trimmed, 'ไม่มีกลุ่มยา') || str_contains($trimmed, 'อย.') || str_contains($trimmed, 'อาหารและยา')) {
            return 'EC';
        }
        if (str_contains($trimmed, 'ข้อห้าม') || str_contains($trimmed, 'ห้ามใช้')) {
            return 'ED';
        }
        if (str_contains($trimmed, 'ราคาแพง') || str_contains($trimmed, 'คุ้มค่า')) {
            return 'EE';
        }
        if (str_contains($trimmed, 'จำนง') || str_contains($trimmed, 'เบิกไม่ได้') || str_contains($trimmed, 'ชำระเอง')) {
            return 'EC';
        }
        if (str_contains($trimmed, 'PA') || str_contains($trimmed, 'อนุมัติก่อน')) {
            return 'PA';
        }

        return 'EC';
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
                       COALESCE(vp.pttype, o.pttype) as pttype, o.pt_subtype, o.ovstist, o.ovstost,
                       COALESCE(ost.export_code, o.ovstist, '1') as typein_code,
                       COALESCE(oos.export_code, o.ovstost, '1') as typeout_code,
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
                LEFT JOIN ovstist ost ON ost.ovstist = o.ovstist
                LEFT JOIN ovstost oos ON oos.ovstost = o.ovstost
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
                           COALESCE(NULLIF(n.income, ''), NULLIF(d.income, ''), op.income) as income, op.paidst, op.pttype, op.hos_guid,
                           d.name as drug_name, d.strength as drug_strength, d.units as drug_unit, d.packqty as drug_pack, d.did as drug_did,
                           d.tmt_tp_code, d.tmt_gp_code, d.ttmt_code, d.sks_drug_code, d.therapeutic,
                           n.name as nondrug_name,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) as nhso_adp_code,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) as nhso_adp_type,
                           drg.chrgitem_code1, drg.chrgitem_code2,
                           o.spclty, pt.cid, COALESCE(doc.licenseno, '') as doctor_license,
                           op.drugusage, du.code as sigcode, du.name1 as sigtext1, du.name2 as sigtext2, du.name3 as sigtext3
                    FROM opitemrece op
                    LEFT JOIN ovst o ON o.vn = op.vn
                    LEFT JOIN patient pt ON pt.hn = op.hn
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN income inc ON inc.income = COALESCE(NULLIF(n.income, ''), NULLIF(d.income, ''), op.income)
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
                               COALESCE(NULLIF(n.income, ''), NULLIF(d.income, ''), op.income) as income, op.paidst, op.pttype, op.hos_guid,
                               d.name as drug_name, d.strength as drug_strength, d.units as drug_unit, d.packqty as drug_pack, d.did as drug_did,
                               d.tmt_tp_code, d.tmt_gp_code, d.ttmt_code, d.sks_drug_code, d.therapeutic,
                               n.name as nondrug_name,
                               COALESCE(n.nhso_adp_code, d.nhso_adp_code) as nhso_adp_code,
                               COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) as nhso_adp_type,
                               drg.chrgitem_code1, drg.chrgitem_code2,
                               o.spclty, pt.cid, COALESCE(doc.licenseno, '') as doctor_license,
                               op.drugusage, '' as sigcode, '' as sigtext1, '' as sigtext2, '' as sigtext3
                        FROM opitemrece op
                        LEFT JOIN ovst o ON o.vn = op.vn
                        LEFT JOIN patient pt ON pt.hn = op.hn
                        LEFT JOIN drugitems d ON d.icode = op.icode
                        LEFT JOIN nondrugitems n ON n.icode = op.icode
                        LEFT JOIN income inc ON inc.income = COALESCE(NULLIF(n.income, ''), NULLIF(d.income, ''), op.income)
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
                    SELECT er.vn, er.er_pt_type, er.er_emergency_type, er.er_emergency_level_id,
                           TIME_FORMAT(COALESCE(er.er_time_1, er.enter_er_time), '%H%i') as aetime
                    FROM er_regist er
                    WHERE er.vn IN ($vnPlaceholders)
                ", $vnsList))->keyBy('vn');

                $referOutByVn = $referOuts->keyBy('vn');
                $referInByVn = $referIns->keyBy('vn');

                foreach ($visits as $v) {
                    $er = $erRows->get($v->vn);
                    $ro = $referOutByVn->get($v->vn);
                    $ri = $referInByVn->get($v->vn);
                    $hasRefer = !empty($ro) || !empty($ri);

                    $hipCode = strtoupper(trim((string)($v->hipdata_code ?? '')));
                    $isUcs = in_array($hipCode, ['UCS', 'WEL']);

                    // ลำดับ UCAE: 1. visit_pttype -> 2. er_pt_type (เฉพาะ UCS) -> 3. 'N' (ถ้ามี Refer)
                    $rawUcae = trim((string)($v->nhso_ucae_type_code ?? ($v->ucae ?? '')));
                    if (empty($rawUcae) && $isUcs && $er) {
                        if ($er->er_pt_type == 2) $rawUcae = 'A';
                        elseif ($er->er_pt_type == 1) $rawUcae = 'E';
                    }

                    $isUcaeClaim = $isUcs && in_array($rawUcae, ['A', 'E', 'I', 'O', 'C', 'Z']);
                    // ถ้าไม่ใช่ UCS: UCAE ต้องว่างเด็ดขาด (ไม่ใส่ A/E)
                    $finalUcae = $isUcs ? ($isUcaeClaim ? $rawUcae : ($hasRefer ? 'N' : '')) : '';

                    // แฟ้ม AER จะส่งเฉพาะ:
                    // 1. เคสที่มี Refer (In หรือ Out) สำหรับทุกสิทธิ
                    // 2. เคสสิทธิ UCS ที่ประสงค์เบิก A/E
                    if ($hasRefer || $isUcaeClaim) {
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
                            'ucae' => $finalUcae,
                            'is_ucae_claim' => $isUcaeClaim,
                            'has_refer' => $hasRefer
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

        // Query Non-ED Reason / DRUGREMARK from ovst_presc_ned and drugitems_ned_reason
        $opdNedReasons = collect();
        if (!empty($vnsList)) {
            try {
                $nedRows = DB::connection('hosxp')->select("
                    SELECT vn, icode,
                           COALESCE(NULLIF(presc_reason, ''), NULLIF(reason_all, '')) as ned_reason,
                           nhso_authorize_code as pa_no
                    FROM ovst_presc_ned
                    WHERE vn IN ($vnPlaceholders)
                ", $vnsList);
                $opdNedReasons = collect($nedRows)->keyBy(function($r) {
                    return $r->vn . '_' . $r->icode;
                });
            } catch (\Throwable $e) {}
        }

        $masterNedReasons = collect();
        try {
            $masterRows = DB::connection('hosxp')->select("
                SELECT icode, doctor_reason
                FROM drugitems_ned_reason
                WHERE doctor_reason IS NOT NULL AND doctor_reason != ''
            ");
            $masterNedReasons = collect($masterRows)->keyBy('icode');
        } catch (\Throwable $e) {}

        $nedIcodeMap = [];
        $edIcodeMap = [];
        try {
            if (Schema::hasTable('drugcat_nhso')) {
                $local_db = config('database.connections.mysql.database');
                $nhsoRows = DB::select("
                    SELECT dc.hospdrugcode, dc.ised, dc.ised_approved, dc.date_approved, dc.updateflag
                    FROM {$local_db}.drugcat_nhso dc
                    INNER JOIN (
                        SELECT hospdrugcode, MAX(date_approved) as max_date
                        FROM {$local_db}.drugcat_nhso
                        WHERE updateflag IN ('A','U','E')
                        GROUP BY hospdrugcode
                    ) max_dc ON max_dc.hospdrugcode = dc.hospdrugcode AND max_dc.max_date = dc.date_approved
                    WHERE dc.updateflag IN ('A','U','E')
                ");
                foreach ($nhsoRows as $nr) {
                    $approved = strtoupper(trim((string)$nr->ised_approved));
                    if ($approved === 'N') {
                        $nedIcodeMap[$nr->hospdrugcode] = true;
                    } elseif ($approved === 'E' || str_starts_with($approved, 'E')) {
                        $edIcodeMap[$nr->hospdrugcode] = true;
                    }
                }
            }
        } catch (\Throwable $e) {}

        try {
            $sksRows = DB::connection('hosxp')->select("
                SELECT HospDrugCode, ISED
                FROM sks_drugcatalog
                WHERE HospDrugCode IS NOT NULL
            ");
            foreach ($sksRows as $sr) {
                if (!isset($nedIcodeMap[$sr->HospDrugCode]) && !isset($edIcodeMap[$sr->HospDrugCode])) {
                    $sksIsed = strtoupper(trim((string)$sr->ISED));
                    if ($sksIsed === 'N') {
                        $nedIcodeMap[$sr->HospDrugCode] = true;
                    } elseif ($sksIsed === 'E') {
                        $edIcodeMap[$sr->HospDrugCode] = true;
                    }
                }
            }
        } catch (\Throwable $e) {}

        // =============================================================
        // BUILD 16/17 แฟ้ม FDH สำหรับ OPD
        // =============================================================

        // 1. INS.txt (19 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HN|INSCL|SUBTYPE|CID|HCODE|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE
        $insLines = ["HN|INSCL|SUBTYPE|CID|HCODE|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE"];
        foreach ($visits as $v) {
            $cid = trim((string)$v->cid);
            $permitno = trim((string)$v->permitno);
            $inscl = self::mapInscl($v->hipdata_code, $v->pttype);
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
            $optype = '';
            $typein = (string)intval($v->typein_code ?: ($v->ovstist ?: '1'));
            if (!in_array($typein, ['1', '2', '3', '4'])) {
                $typein = '1';
            }
            $typeout = (string)intval($v->typeout_code ?: ($v->ovstost ?: '1'));
            if ($typeout === '9') {
                $typeout = '7'; // ปฏิเสธการรักษา
            }
            if (!in_array($typeout, ['1', '2', '3', '4', '5', '6', '7', '8'])) {
                $typeout = '1';
            }

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
            $refer = self::formatHospcode($ro->refer_hospcode);
            $refertype = $ro->refer_type ?: '2';
            $seq = $ro->vn;
            $referdate = self::formatDate($ro->refer_date);

            $orfLines[] = "{$ro->hn}|{$dateopd}|{$clinic}|{$refer}|{$refertype}|{$seq}|{$referdate}";
        }
        foreach ($referIns as $ri) {
            $v = $visitsByVn->get($ri->vn);
            $dateopd = self::formatDate($v->vstdate ?? $ri->vstdate ?? $ri->refer_date);
            $clinic = self::formatClinic($ri->spclty ?? ($v->spclty ?? ''));
            $refer = self::formatHospcode($ri->refer_hospcode);
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
            $pttype = self::mapChtPttype($v->hipdata_code, $v->pttype, $v->pttype_nhso_code);
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
                $isPaidSelf = in_array(trim((string)($it->paidst ?? '')), ['01', '03']);
                $chrg = $isPaidSelf 
                    ? (trim((string)($it->chrgitem_code2 ?? '')) ?: self::mapIncomeToChaItem($it->income, '03'))
                    : (trim((string)($it->chrgitem_code1 ?? '')) ?: self::mapIncomeToChaItem($it->income, '02'));
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
            $isAe = !empty($er->is_ucae_claim);
            $aedate = $isAe ? $dateopd : '';
            $aetime = $isAe ? ($er->aetime ?: '') : '';
            $aetype = '';
            $referno = trim((string)($er->refer_no ?: ''));
            $refmaini = !empty($er->refmaini) ? self::formatHospcode($er->refmaini) : '';
            $ireftype = !empty($refmaini) ? '1' : '';
            $refmaino = !empty($er->refmaino) ? self::formatHospcode($er->refmaino) : '';
            $oreftype = !empty($refmaino) ? '1100' : '';
            $ucae = in_array(trim((string)($er->ucae ?? '')), ['A', 'E', 'I', 'O', 'C', 'Z', 'N']) ? trim((string)$er->ucae) : '';
            $emtype = !empty($er->is_ucae_claim) ? '3' : '';
            $seq = $er->vn;
            $an = '';

            $aerLines[] = "{$er->hn}|{$an}|{$dateopd}|{$authae}|{$aedate}|{$aetime}|{$aetype}|{$referno}|{$refmaini}|{$ireftype}|{$refmaino}|{$oreftype}|{$ucae}|{$emtype}|{$seq}|||";
        }

        // 14. ADP.txt (27 คอลัมน์ตามมาตรฐาน 16แฟ้ม FDH)
        // HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM
        $adpLines = ["HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM"];
        
        $claimCode = strtoupper(trim((string)($options['claim_code'] ?? '')));
        $isUcsIncupOrInprov = in_array($claimCode, ['UCS_INCUP', 'UCS_INPROV', 'UCS_INPROVINCE', 'INCUP', 'INPROV', 'INPROVINCE'])
            || str_contains($claimCode, 'INCUP')
            || str_contains($claimCode, 'INPROV');

        $adpItems = $items->filter(function($it) use ($isUcsIncupOrInprov) {
            $rawCode = strtoupper(trim((string)$it->nhso_adp_code));

            // ข้ามรหัส S1801, S1802 (ค่ารถ Refer) เฉพาะ 2 หน้า: ucs_incup และ ucs_inprovince
            if ($isUcsIncupOrInprov && in_array($rawCode, ['S1801', 'S1802'])) {
                return false;
            }

            $type = !empty($it->nhso_adp_type) ? (string)$it->nhso_adp_type : self::mapIncomeToAdpType($it->income);
            $isAllowedZero = in_array(trim($type), ['3', '03', '4', '04', '5', '05']);

            // แค่ Type 3, 4, 5 เท่านั้นที่เป็น 0 แล้วส่งออกได้ นอกนั้นเป็น 0 ไม่ต้องส่งออก
            if (!$isAllowedZero && (float)$it->sum_price <= 0 && (float)$it->unitprice <= 0) {
                return false;
            }

            $isDrug = str_starts_with((string)$it->icode, '1');
            if (!$isDrug) {
                return true;
            } else {
                return !empty(trim((string)$it->nhso_adp_code));
            }
        });

        foreach ($adpItems as $it) {
            $dateopd = self::formatDate($it->vstdate);
            $type = !empty($it->nhso_adp_type) ? (string)$it->nhso_adp_type : self::mapIncomeToAdpType($it->income);
            $rawCode = trim((string)$it->nhso_adp_code);
            $code = ($rawCode === 'XXXXXX' || empty($rawCode)) ? '' : $rawCode;
            $qty = intval($it->qty) ?: 1;
            $rate = (float)$it->unitprice > 0 ? (float)$it->unitprice : ((float)$it->sum_price / $qty);
            $rateStr = $rate == floor($rate) ? (string)intval($rate) : number_format($rate, 2, '.', '');
            $seq = $it->vn;
            $an = '';
            $cagcode = '';
            $dose = '';
            $catype = '';
            $serialno = '';
            $isNonReimbursable = (!empty($it->paidst) && $it->paidst !== '02');
            $totcopay = $isNonReimbursable ? number_format((float)$it->sum_price, 2, '.', '') : '0';
            $usestatus = ($type === '11') ? '2' : ''; // 1=ใช้ในโรงพยาบาล, 2=ใช้ที่บ้าน (OFC/LGO Type=11 ต้องระบุ)
            $total = $isNonReimbursable ? '0' : number_format((float)$it->sum_price, 2, '.', '');

            // แค่ Type 3, 4, 5 เท่านั้นที่เป็น 0 แล้วส่งออกได้ นอกนั้นถ้าทั้ง TOTAL และ TOTCOPAY เป็น 0 ไม่ต้องส่งออก
            if (!in_array(trim($type), ['3', '03', '4', '04', '5', '05']) && (float)$total <= 0 && (float)$totcopay <= 0) {
                continue;
            }
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
                $total = '0';
                $clinic = self::formatClinic($v->spclty);
                $an = '';
                $cagcode = '';
                $dose = '';
                $catype = '';
                $serialno = '';
                $totcopay = '0';
                $usestatus = '2';
                $qtyday = '';
                $tmltcode = '';
                $status1 = '';
                $bi = '';
                $itemsrc = '';
                $provider = '';
                $gravida = '';
                $gaweek = '';
                $dcip = '';
                $lmp = '';
                $spitem = '';

                $adpLines[] = "{$v->hn}|{$an}|{$dateopd}|{$type}|{$code}|{$qty}|{$rateStr}|{$seq}|{$cagcode}|{$dose}|{$catype}|{$serialno}|{$totcopay}|{$usestatus}|{$total}|{$qtyday}|{$tmltcode}|{$status1}|{$bi}|{$clinic}|{$itemsrc}|{$provider}|{$gravida}|{$gaweek}|{$dcip}|{$lmp}|{$spitem}";
            }
        }

        // 15. LVD.txt (7 คอลัมน์ - ว่างสำหรับ OPD)
        $lvdLines = ["SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY"];

        // 16. DRU.txt (24 คอลัมน์ตาม 16แฟ้มFDH.xlsx)
        // HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER|SP_ITEM
        $druLines = ["HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER|SP_ITEM"];
        $drugItems = $items->filter(function($it) {
            return str_starts_with((string)$it->icode, '1') && (float)$it->qty > 0 && (float)$it->sum_price > 0;
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
            $isNed = false;
            if (isset($nedIcodeMap[$it->icode])) {
                $isNed = true;
            } elseif (isset($edIcodeMap[$it->icode])) {
                $isNed = false;
            } else {
                $drugAcc = strtoupper(trim((string)($it->drugaccount ?? '')));
                $isNed = empty($drugAcc) || in_array($drugAcc, ['-', 'NED', 'NON-ED', 'นอก']);
            }

            $drugremark = '';
            $pano = '';
            $nedInfo = $opdNedReasons->get($it->vn . '_' . $it->icode);
            if ($isNed) {
                $rawReason = $nedInfo ? $nedInfo->ned_reason : ($masterNedReasons->get($it->icode)?->doctor_reason ?? '');
                $drugremark = !empty($rawReason) ? self::normalizeNedReason($rawReason) : 'EC';
                $pano = $nedInfo ? mb_substr(trim((string)$nedInfo->pa_no), 0, 30, 'UTF-8') : '';
            } else {
                if ($nedInfo && !empty($nedInfo->pa_no)) {
                    $pano = mb_substr(trim((string)$nedInfo->pa_no), 0, 30, 'UTF-8');
                    $drugremark = 'PA';
                }
            }
            $isNonReimbursable = (!empty($it->paidst) && $it->paidst !== '02');
            $totcopay = $isNonReimbursable ? number_format((float)$it->sum_price, 2, '.', '') : '0';
            $usestatus = '2'; // 1=In-hospital, 2=Home
            $total = $isNonReimbursable ? '0.00' : number_format((float)$it->sum_price, 2, '.', '');
            $sigcode = mb_substr(trim((string)$it->sigcode), 0, 50, 'UTF-8');
            $sigtext = mb_substr(str_replace('|', ' ', trim(implode(' ', array_filter([$it->sigtext1, $it->sigtext2, $it->sigtext3])))), 0, 255, 'UTF-8');
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
                       COALESCE(dst.nhso_dchstts, ipt.dchstts, '1') as dischs,
                       COALESCE(dt.nhso_dchtype, ipt.dchtype, '1') as discht,
                       ipt.ward as warddsc,
                       ipt.spclty as dept, ipt.bw as adm_w, '' as svctype,
                       ipt.pttype,
                       a.pdx, a.dx_doctor, a.income, a.paid_money, a.rcpt_money, a.uc_money,
                       a.dx0, a.dx1, a.dx2, a.dx3, a.dx4, a.dx5,
                       a.op0, a.op1, a.op2, a.op3, a.op4, a.op5,
                       pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex, pt.marrystatus, pt.occupation, pt.nationality,
                       pt.chwpart, pt.amppart, pt.tmbpart,
                       p.hipdata_code,
                       COALESCE(p.nhso_code, ipt.pttype, '10') as pttype_nhso_code,
                       doc.licenseno as doctor_license, doc.name as doctor_name,
                       COALESCE(ip.hospmain, '') as hospmain,
                       COALESCE(ip.hospsub, '') as hospsub,
                       COALESCE(ip.claim_code, ip.auth_code, '') as permitno,
                       ia.ac_ae as ipt_ac_ae, ia.ac_emtype as ipt_ac_emtype
                FROM ipt
                LEFT JOIN an_stat a ON a.an = ipt.an
                LEFT JOIN patient pt ON pt.hn = ipt.hn
                LEFT JOIN ipt_pttype ip ON ip.an = ipt.an
                LEFT JOIN pttype p ON p.pttype = COALESCE(ip.pttype, ipt.pttype)
                LEFT JOIN doctor doc ON doc.code = ipt.admdoctor
                LEFT JOIN dchstts dst ON dst.dchstts = ipt.dchstts
                LEFT JOIN dchtype dt ON dt.dchtype = ipt.dchtype
                LEFT JOIN ipt_accident ia ON ia.an = ipt.an
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
            } catch (\Throwable $e) {
                Log::warning("FDH IPD Export iptdiag query error: " . $e->getMessage());
            }

            // Fallback from an_stat pdx, dx0..dx5 if iptdiag is empty for an AN
            $existingAnsWithDiag = $ipdDiags->pluck('an')->unique()->toArray();
            foreach ($admissions as $adm) {
                if (!in_array($adm->an, $existingAnsWithDiag)) {
                    if (!empty($adm->pdx)) {
                        $ipdDiags->push((object)[
                            'an' => $adm->an,
                            'icd10' => $adm->pdx,
                            'diagtype' => '1',
                            'drdx' => $adm->doctor_license ?: 'ว00000',
                        ]);
                    }
                    for ($i = 0; $i <= 5; $i++) {
                        $dxField = "dx{$i}";
                        if (!empty($adm->$dxField)) {
                            $ipdDiags->push((object)[
                                'an' => $adm->an,
                                'icd10' => $adm->$dxField,
                                'diagtype' => '2',
                                'drdx' => $adm->doctor_license ?: 'ว00000',
                            ]);
                        }
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // 3. Query IPD Procedures (iptoprt)
        // -------------------------------------------------------------
        $ipdOpers = collect();
        if (!empty($ansList)) {
            try {
                $operRows = DB::connection('hosxp')->select("
                    SELECT io.an, io.icd9 as oper, COALESCE(io.oper_type, io.ovst_oper_type, '1') as opertype, doc.licenseno as dropid,
                           io.opdate as datein, io.optime as timein, io.enddate as dateout, io.endtime as timeout
                    FROM iptoprt io
                    LEFT JOIN doctor doc ON doc.code = io.doctor
                    WHERE io.an IN ($anPlaceholders)
                    ORDER BY io.an
                ", $ansList);
                $ipdOpers = collect($operRows);
            } catch (\Throwable $e) {
                Log::warning("FDH IPD Export iptoprt query error: " . $e->getMessage());
            }

            // Fallback from an_stat op0..op5 if iptoprt is empty for an AN
            $existingAnsWithOper = $ipdOpers->pluck('an')->unique()->toArray();
            foreach ($admissions as $adm) {
                if (!in_array($adm->an, $existingAnsWithOper)) {
                    for ($i = 0; $i <= 5; $i++) {
                        $opField = "op{$i}";
                        if (!empty($adm->$opField)) {
                            $ipdOpers->push((object)[
                                'an' => $adm->an,
                                'oper' => $adm->$opField,
                                'opertype' => ($i == 0 ? '1' : '2'),
                                'dropid' => $adm->doctor_license ?: 'ว00000',
                                'datein' => $adm->regdate,
                                'timein' => $adm->regtime,
                                'dateout' => $adm->dchdate ?: $adm->regdate,
                                'timeout' => $adm->dchtime ?: $adm->regtime,
                            ]);
                        }
                    }
                }
            }
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
                           COALESCE(NULLIF(n.income, ''), NULLIF(d.income, ''), op.income) as income, op.paidst, op.pttype, op.hos_guid,
                           d.name as drug_name, d.strength as drug_strength, d.units as drug_unit, d.packqty as drug_pack, d.did as drug_did,
                           d.tmt_tp_code, d.tmt_gp_code, d.ttmt_code, d.sks_drug_code, d.therapeutic,
                           n.name as nondrug_name,
                           COALESCE(n.nhso_adp_code, d.nhso_adp_code) as nhso_adp_code,
                           COALESCE(n.nhso_adp_type_id, d.nhso_adp_type_id) as nhso_adp_type,
                           drg.chrgitem_code1, drg.chrgitem_code2,
                           pt.cid, COALESCE(doc.licenseno, '') as doctor_license,
                           op.drugusage, du.code as sigcode, du.name1 as sigtext1, du.name2 as sigtext2, du.name3 as sigtext3
                    FROM opitemrece op
                    LEFT JOIN patient pt ON pt.hn = op.hn
                    LEFT JOIN drugitems d ON d.icode = op.icode
                    LEFT JOIN nondrugitems n ON n.icode = op.icode
                    LEFT JOIN income inc ON inc.income = COALESCE(NULLIF(n.income, ''), NULLIF(d.income, ''), op.income)
                    LEFT JOIN drg_chrgitem drg ON drg.drg_chrgitem_id = inc.drg_chrgitem_id
                    LEFT JOIN doctor doc ON doc.code = op.doctor
                    LEFT JOIN drugusage du ON du.drugusage = op.drugusage
                    WHERE op.an IN ($anPlaceholders)
                    ORDER BY op.an, op.item_no
                ", $ansList);
                $items = collect($itemRows);
            } catch (\Throwable $e) {}
        }

        // Query Non-ED Reason / DRUGREMARK from ipt_presc_ned
        $ipdNedReasons = collect();
        if (!empty($ansList)) {
            try {
                $nedRows = DB::connection('hosxp')->select("
                    SELECT an, icode,
                           COALESCE(NULLIF(presc_reason_1, ''), NULLIF(presc_reason_2, '')) as ned_reason,
                           nhso_authorize_code as pa_no
                    FROM ipt_presc_ned
                    WHERE an IN ($anPlaceholders)
                ", $ansList);
                $ipdNedReasons = collect($nedRows)->keyBy(function($r) {
                    return $r->an . '_' . $r->icode;
                });
            } catch (\Throwable $e) {}
        }

        $masterNedReasonsIp = collect();
        try {
            $masterRows = DB::connection('hosxp')->select("
                SELECT icode, doctor_reason
                FROM drugitems_ned_reason
                WHERE doctor_reason IS NOT NULL AND doctor_reason != ''
            ");
            $masterNedReasonsIp = collect($masterRows)->keyBy('icode');
        } catch (\Throwable $e) {}

        $nedIcodeMapIp = [];
        $edIcodeMapIp = [];
        try {
            if (Schema::hasTable('drugcat_nhso')) {
                $local_db = config('database.connections.mysql.database');
                $nhsoRows = DB::select("
                    SELECT dc.hospdrugcode, dc.ised, dc.ised_approved, dc.date_approved, dc.updateflag
                    FROM {$local_db}.drugcat_nhso dc
                    INNER JOIN (
                        SELECT hospdrugcode, MAX(date_approved) as max_date
                        FROM {$local_db}.drugcat_nhso
                        WHERE updateflag IN ('A','U','E')
                        GROUP BY hospdrugcode
                    ) max_dc ON max_dc.hospdrugcode = dc.hospdrugcode AND max_dc.max_date = dc.date_approved
                    WHERE dc.updateflag IN ('A','U','E')
                ");
                foreach ($nhsoRows as $nr) {
                    $approved = strtoupper(trim((string)$nr->ised_approved));
                    if ($approved === 'N') {
                        $nedIcodeMapIp[$nr->hospdrugcode] = true;
                    } elseif ($approved === 'E' || str_starts_with($approved, 'E')) {
                        $edIcodeMapIp[$nr->hospdrugcode] = true;
                    }
                }
            }
        } catch (\Throwable $e) {}

        try {
            $sksRows = DB::connection('hosxp')->select("
                SELECT HospDrugCode, ISED
                FROM sks_drugcatalog
                WHERE HospDrugCode IS NOT NULL
            ");
            foreach ($sksRows as $sr) {
                if (!isset($nedIcodeMapIp[$sr->HospDrugCode]) && !isset($edIcodeMapIp[$sr->HospDrugCode])) {
                    $sksIsed = strtoupper(trim((string)$sr->ISED));
                    if ($sksIsed === 'N') {
                        $nedIcodeMapIp[$sr->HospDrugCode] = true;
                    } elseif ($sksIsed === 'E') {
                        $edIcodeMapIp[$sr->HospDrugCode] = true;
                    }
                }
            }
        } catch (\Throwable $e) {}

        // =============================================================
        // BUILD 16/17 แฟ้ม FDH สำหรับ IPD
        // =============================================================

        // 1. INS.txt (19 คอลัมน์)
        $insLines = ["HN|INSCL|SUBTYPE|CID|HCODE|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE"];
        foreach ($admissions as $v) {
            $cid = trim((string)$v->cid);
            $permitno = trim((string)$v->permitno);
            $inscl = self::mapInscl($v->hipdata_code, $v->pttype);
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
            $dischs = substr((string)intval($v->dischs ?: '1'), 0, 1);
            $discht = substr((string)intval($v->discht ?: '1'), 0, 1);
            $warddsc = substr(str_pad(trim((string)$v->warddsc), 2, '0', STR_PAD_LEFT), 0, 4) ?: '01';
            $dept = substr(str_pad(trim((string)$v->dept), 2, '0', STR_PAD_LEFT), 0, 2) ?: '01';
            $admwKg = floatval($v->adm_w) > 500 ? floatval($v->adm_w) / 1000 : floatval($v->adm_w ?: 50);
            $admw = number_format($admwKg, 2, '.', '');
            $uuc = '1';
            $svctype = '1';

            $ipdLines[] = "{$v->hn}|{$v->an}|{$dateadm}|{$timeadm}|{$datedsc}|{$timedsc}|{$dischs}|{$discht}|{$warddsc}|{$dept}|{$admw}|{$uuc}|{$svctype}";
        }

        // 8. IRF.txt (3 คอลัมน์)
        // AN|REFER|REFERTYPE
        $irfLines = ["AN|REFER|REFERTYPE"];
        $admissionsByAn = $admissions->keyBy('an');
        $admissionsByVn = $admissions->keyBy('vn');

        foreach ($ipdRefers as $ro) {
            $refer = self::formatHospcode($ro->refer_hospcode);
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
            $refer = self::formatHospcode($ri->refer_hospcode);
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
            if (str_starts_with($dropid, '-') && strlen($dropid) > 6) {
                $dropid = substr($dropid, 0, 6);
            }
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
            $pttype = self::mapChtPttype($v->hipdata_code, $v->pttype, $v->pttype_nhso_code);
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
                $isPaidSelf = in_array(trim((string)($it->paidst ?? '')), ['01', '03']);
                $chrg = $isPaidSelf 
                    ? (trim((string)($it->chrgitem_code2 ?? '')) ?: self::mapIncomeToChaItem($it->income, '03'))
                    : (trim((string)($it->chrgitem_code1 ?? '')) ?: self::mapIncomeToChaItem($it->income, '02'));
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
        // HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT
        $aerLines = ["HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT"];
        $referOutByAn = $ipdRefers->keyBy(function($item) use ($admissionsByAn, $admissionsByVn) {
            if ($admissionsByAn->has($item->vn)) return $item->vn;
            if ($admissionsByVn->has($item->vn)) return $admissionsByVn->get($item->vn)->an;
            return $item->vn;
        });
        $referInByAn = $ipdReferIns->keyBy(function($item) use ($admissionsByAn, $admissionsByVn) {
            if ($admissionsByAn->has($item->vn)) return $item->vn;
            if ($admissionsByVn->has($item->vn)) return $admissionsByVn->get($item->vn)->an;
            return $item->vn;
        });

        foreach ($admissions as $v) {
            $ro = $referOutByAn->get($v->an);
            $ri = $referInByAn->get($v->an);
            $hasRefer = !empty($ro) || !empty($ri);

            $hipCode = strtoupper(trim((string)($v->hipdata_code ?? '')));
            $isUcs = in_array($hipCode, ['UCS', 'WEL']);

            $rawUcae = !empty($v->ipt_ac_ae) ? strtoupper(trim((string)$v->ipt_ac_ae)) : trim((string)($v->nhso_ucae_type_code ?? ''));
            $isUcaeClaim = $isUcs && in_array($rawUcae, ['A', 'E', 'I', 'O', 'C', 'Z']);
            $finalUcae = $isUcs ? ($isUcaeClaim ? $rawUcae : ($hasRefer ? 'N' : '')) : '';

            if ($hasRefer || $isUcaeClaim) {
                $dateopd = self::formatDate($v->regdate);
                $authae = '';
                $aedate = $isUcaeClaim ? $dateopd : '';
                $aetime = $isUcaeClaim ? (!empty($ro->refer_time) ? self::formatTime($ro->refer_time) : (!empty($ri->refer_time) ? self::formatTime($ri->refer_time) : self::formatTime($v->regtime))) : '';
                $aetype = '';
                $referno = trim((string)($ro->refer_number ?? ($ri->refer_number ?? '')));
                $refmaini = !empty($ri->refer_hospcode) ? self::formatHospcode($ri->refer_hospcode) : '';
                $ireftype = !empty($refmaini) ? '1' : '';
                $refmaino = !empty($ro->refer_hospcode) ? self::formatHospcode($ro->refer_hospcode) : '';
                $oreftype = !empty($refmaino) ? '1100' : '';
                $ucaeVal = $finalUcae;
                $emtype = $isUcaeClaim ? (!empty($v->ipt_ac_emtype) ? trim((string)$v->ipt_ac_emtype) : '3') : '';
                $seq = $v->an;
                $an = $v->an;

                $aerLines[] = "{$v->hn}|{$an}|{$dateopd}|{$authae}|{$aedate}|{$aetime}|{$aetype}|{$referno}|{$refmaini}|{$ireftype}|{$refmaino}|{$oreftype}|{$ucaeVal}|{$emtype}|{$seq}|||";
            }
        }

        // 14. ADP.txt (27 คอลัมน์)
        // 14. ADP.txt (27 คอลัมน์ตามมาตรฐาน 16แฟ้ม FDH)
        // HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM
        $adpLines = ["HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM"];
        $adpItems = $items->filter(function($it) {
            $type = !empty($it->nhso_adp_type) ? (string)$it->nhso_adp_type : self::mapIncomeToAdpType($it->income);
            $isAllowedZero = in_array(trim($type), ['3', '03', '4', '04', '5', '05']);

            // แค่ Type 3, 4, 5 เท่านั้นที่เป็น 0 แล้วส่งออกได้ นอกนั้นเป็น 0 ไม่ต้องส่งออก
            if (!$isAllowedZero && (float)$it->sum_price <= 0 && (float)$it->unitprice <= 0) {
                return false;
            }

            $isDrug = str_starts_with((string)$it->icode, '1');
            if (!$isDrug) {
                return true;
            } else {
                return !empty(trim((string)$it->nhso_adp_code));
            }
        });

        // Group by an, type, code, unitprice (matching HOSxP IPD ADP aggregation for multi-day stays)
        $groupedAdp = [];
        foreach ($adpItems as $it) {
            $adm = $admissionsByAn->get($it->an);
            $type = !empty($it->nhso_adp_type) ? (string)$it->nhso_adp_type : self::mapIncomeToAdpType($it->income);
            $rawCode = trim((string)$it->nhso_adp_code);
            $code = ($rawCode === 'XXXXXX' || empty($rawCode)) ? trim((string)$it->icode) : $rawCode;
            $rate = floatval($it->unitprice ?: 0.0);
            $rateStr = $rate == floor($rate) ? (string)intval($rate) : number_format($rate, 2, '.', '');
            $key = $it->an . '_' . $type . '_' . $code . '_' . $rateStr;

            if (!isset($groupedAdp[$key])) {
                $groupedAdp[$key] = clone $it;
                $groupedAdp[$key]->adp_type = $type;
                $groupedAdp[$key]->adp_code = $code;
                $groupedAdp[$key]->qty = 0;
                $groupedAdp[$key]->sum_price = 0;
                $groupedAdp[$key]->admit_date = $adm ? $adm->regdate : $it->vstdate;
            }
            $groupedAdp[$key]->qty += (float)($it->qty ?: 1);
            $groupedAdp[$key]->sum_price += (float)($it->sum_price ?: 0.0);
        }

        foreach ($groupedAdp as $it) {
            $adm = $admissionsByAn->get($it->an);
            $dateopd = self::formatDate($it->admit_date ?: ($adm ? $adm->regdate : $it->vstdate));
            $type = $it->adp_type;
            $code = $it->adp_code;
            $qty = number_format((float)$it->qty, 0, '.', '');
            $rate = floatval($it->unitprice ?: 0.0);
            if ($rate <= 0 && (float)$it->sum_price > 0) {
                $qtyVal = (float)$it->qty ?: 1.0;
                $rate = (float)$it->sum_price / $qtyVal;
            }
            $rateStr = $rate == floor($rate) ? (string)intval($rate) : number_format($rate, 2, '.', '');
            $seq = $it->an;
            $an = $it->an;
            $cagcode = '';
            $dose = '';
            $catype = '';
            $serialno = '';
            $isNonReimbursable = (!empty($it->paidst) && $it->paidst !== '02');
            $totcopay = $isNonReimbursable ? number_format((float)$it->sum_price, 2, '.', '') : '0';
            $usestatus = ($type === '11') ? '1' : ''; // 1=ใช้ในโรงพยาบาล, 2=ใช้ที่บ้าน (OFC/LGO Type=11 ต้องระบุ)
            $total = $isNonReimbursable ? '0' : number_format((float)$it->sum_price, 2, '.', '');

            // แค่ Type 3, 4, 5 เท่านั้นที่เป็น 0 แล้วส่งออกได้ นอกนั้นถ้าทั้ง TOTAL และ TOTCOPAY เป็น 0 ไม่ต้องส่งออก
            if (!in_array(trim($type), ['3', '03', '4', '04', '5', '05']) && (float)$total <= 0 && (float)$totcopay <= 0) {
                continue;
            }
            $qtyday = '';
            $tmltcode = '';
            $status1 = '';
            $bi = '';
            $clinic = self::formatClinic($adm ? $adm->dept : '01');
            $itemsrc = '1';
            $provider = $it->doctor_license ?: ($adm ? ($adm->doctor_license ?: '') : '');
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
            return str_starts_with((string)$it->icode, '1') && (float)$it->qty > 0 && (float)$it->sum_price > 0;
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
            $isNed = false;
            if (isset($nedIcodeMapIp[$it->icode])) {
                $isNed = true;
            } elseif (isset($edIcodeMapIp[$it->icode])) {
                $isNed = false;
            } else {
                $drugAcc = strtoupper(trim((string)($it->drugaccount ?? '')));
                $isNed = empty($drugAcc) || in_array($drugAcc, ['-', 'NED', 'NON-ED', 'นอก']);
            }

            $drugremark = '';
            $pano = '';
            $nedInfo = $ipdNedReasons->get($it->an . '_' . $it->icode);
            if ($isNed) {
                $rawReason = $nedInfo ? $nedInfo->ned_reason : ($masterNedReasonsIp->get($it->icode)?->doctor_reason ?? '');
                $drugremark = !empty($rawReason) ? self::normalizeNedReason($rawReason) : 'EC';
                $pano = $nedInfo ? mb_substr(trim((string)$nedInfo->pa_no), 0, 30, 'UTF-8') : '';
            } else {
                if ($nedInfo && !empty($nedInfo->pa_no)) {
                    $pano = mb_substr(trim((string)$nedInfo->pa_no), 0, 30, 'UTF-8');
                    $drugremark = 'PA';
                }
            }
            $totcopay = '0';
            $usestatus = '1'; // 1=In-hospital
            $total = number_format((float)$it->sum_price, 2, '.', '');
            $sigcode = mb_substr(trim((string)$it->sigcode), 0, 50, 'UTF-8');
            $sigtext = mb_substr(str_replace('|', ' ', trim(implode(' ', array_filter([$it->sigtext1, $it->sigtext2, $it->sigtext3])))), 0, 255, 'UTF-8');
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

    /**
     * ดึง FDH Access Token จาก MOPH Gateway (get_moph_access_token)
     * ลำดับการใช้งาน:
     * 1. ใช้ FDH Credentials ประจำตัวของผู้ใช้ที่ล็อกอิน (หากมีการตั้งค่าไว้ใน Edit Profile)
     * 2. ใช้ FDH Credentials กลางของโรงพยาบาล (จาก main_setting)
     */
    /**
     * ดึงรายละเอียดและขอ Access Token จาก FDH ด้วยข้อมูลบัญชีผู้ใช้งาน
     */
    public static function getFdhTokenDetail(?object $customUser = null): array
    {
        $settings = DB::table('main_setting')
            ->pluck('value', 'name')
            ->toArray();

        $userObj = $customUser ?: (Auth::check() ? Auth::user() : null);

        // ดึง FDH User, Pass, Secret Key ของ User ปัจจุบัน
        $user      = !empty($userObj->fdh_user) ? trim($userObj->fdh_user) : null;
        $password  = !empty($userObj->fdh_pass) ? trim($userObj->fdh_pass) : null;
        $secretKey = !empty($userObj->fdh_secretKey) ? trim($userObj->fdh_secretKey) : '$jwt@moph#';

        // ถ้า User ยังไม่ได้กรอก ให้ลองดึงค่ากลางจาก main_setting (ถ้ามี)
        if (!$user && !empty($settings['fdh_user'])) {
            $user = trim($settings['fdh_user']);
        }
        if (!$password && !empty($settings['fdh_pass'])) {
            $password = trim($settings['fdh_pass']);
        }
        if (empty($userObj->fdh_secretKey) && !empty($settings['fdh_secretKey'])) {
            $secretKey = trim($settings['fdh_secretKey']);
        }

        if (!$user || !$password) {
            return [
                'success' => false,
                'token' => null,
                'fdh_user' => $user,
                'has_credentials' => false,
                'message' => 'ยังไม่ได้ตั้งค่า FDH User หรือ FDH Pass ในข้อมูลผู้ใช้งาน'
            ];
        }

        $userParts = explode('.', $user);
        $hcode = (count($userParts) > 1 && is_numeric(end($userParts)))
            ? end($userParts)
            : ($settings['hospital_code'] ?? ($settings['hcode'] ?? null));

        if (!$hcode) {
            return [
                'success' => false,
                'token' => null,
                'fdh_user' => $user,
                'has_credentials' => true,
                'message' => 'ไม่พบรหัสสถานพยาบาล (Hospital Code)'
            ];
        }

        $hash = hash_hmac('sha256', $password, $secretKey);
        $passwordHash = strtoupper($hash);
        $apiUrl = 'https://fdh.moph.go.th/token?Action=get_moph_access_token';

        try {
            $response = Http::withOptions([
                'verify' => false,
                'http_errors' => false
            ])->withHeaders([
                "Accept" => "application/json",
                "Content-Type" => "application/json"
            ])->post($apiUrl, [
                'user'          => $user,
                'password_hash' => $passwordHash,
                'hospital_code' => $hcode
            ]);

            if ($response->successful()) {
                $token = trim($response->body());
                if (!empty($token) && !str_starts_with($token, '<!DOCTYPE') && !str_starts_with($token, '{') && !str_contains($token, 'Invalid')) {
                    return [
                        'success' => true,
                        'token' => $token,
                        'fdh_user' => $user,
                        'has_credentials' => true,
                        'user_name' => $userObj->name ?? $user,
                        'message' => 'ดึง Access Token สำเร็จ'
                    ];
                }
            }

            $json = $response->json();
            $msg = $json['Message'] ?? ($json['message'] ?? ($json['error'] ?? 'บัญชี FDH User หรือ รหัสผ่าน (FDH Pass) ไม่ถูกต้อง'));
            return [
                'success' => false,
                'token' => null,
                'fdh_user' => $user,
                'has_credentials' => true,
                'message' => $msg
            ];
        } catch (\Throwable $e) {
            Log::error("FDH Token error: " . $e->getMessage());
            return [
                'success' => false,
                'token' => null,
                'fdh_user' => $user,
                'has_credentials' => true,
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ FDH: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ดึง Token กลางของโรงพยาบาลหรือของผู้ใช้
     */
    public static function getHospitalCentralToken(?object $customUser = null): ?string
    {
        $detail = self::getFdhTokenDetail($customUser);
        return $detail['token'] ?? null;
    }

    /**
     * สกัดชื่อผู้ให้บริการ (Provider Name) จากก้อน JWT Token Payload ของกระทรวงฯ โดยตรง
     */
    public static function extractSenderNameFromToken(?string $token): ?string
    {
        if (empty($token)) return null;

        $parts = explode('.', $token);
        if (count($parts) >= 2) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (!empty($payload['client']['name'])) {
                return trim($payload['client']['name']);
            }
            if (!empty($payload['name'])) {
                return trim($payload['name']);
            }
            if (!empty($payload['client']['login'])) {
                return trim($payload['client']['login']);
            }
            // ถ้า sub เป็นชื่อหรืออีเมล (ไม่ใช่ตัวเลขล้วน) ให้ใช้ sub ได้
            if (!empty($payload['sub']) && !is_numeric($payload['sub'])) {
                return trim($payload['sub']);
            }
        }
        return null;
    }

    /**
     * ตรวจสอบว่า JWT Token หมดอายุแล้วหรือไม่ โดยอ่านค่า exp จาก Payload โดยตรง
     */
    public static function isJwtExpired(?string $token): bool
    {
        if (empty($token)) return true;

        $parts = explode('.', $token);
        if (count($parts) >= 2) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (isset($payload['exp'])) {
                // เผื่อเวลาล่วงหน้า 60 วินาที เพื่อป้องกัน Token ขาดช่วงระหว่างส่ง
                return time() >= ((int)$payload['exp'] - 60);
            }
        }
        return false;
    }

    /**
     * ค้นหา Token ที่เหมาะสมสำหรับส่งข้อมูล (FDH User Token -> Explicit Custom Token)
     */
    public static function resolveFdhToken(?string $customToken = null, bool $allowHospitalFallback = true): array
    {
        // 1. Explicit Custom Token
        if (!empty($customToken) && !self::isJwtExpired($customToken)) {
            $tokenName = self::extractSenderNameFromToken($customToken) ?: (Auth::user()->name ?? 'FDH Account');
            return [
                'token' => trim($customToken),
                'type' => 'Custom Token',
                'user_name' => $tokenName,
                'message' => 'Custom Token พร้อมใช้งาน'
            ];
        }

        // 2. Token จากการต่อ API ตรงด้วยบัญชี FDH ของผู้ใช้ปัจจุบัน
        $fdhDetail = self::getFdhTokenDetail();
        if ($fdhDetail['success'] && !empty($fdhDetail['token'])) {
            $tokenName = $fdhDetail['user_name'] ?? (Auth::user()->name ?? $fdhDetail['fdh_user']);
            return [
                'token' => $fdhDetail['token'],
                'type' => 'FDH Token (' . ($fdhDetail['fdh_user'] ?? 'User') . ')',
                'user_name' => $tokenName,
                'fdh_user' => $fdhDetail['fdh_user'],
                'message' => 'ขอ Token สำเร็จ'
            ];
        }

        // ถ้ามีข้อมูล credentials แต่ขอ Token ไม่ผ่าน (เช่น user/password ผิด) ให้หยุดและแจ้ง error ทันที
        if (!empty($fdhDetail['has_credentials'])) {
            return [
                'token' => null,
                'type' => 'None',
                'user_name' => Auth::user()->name ?? 'Unknown',
                'fdh_user' => $fdhDetail['fdh_user'] ?? null,
                'message' => $fdhDetail['message'] ?? 'ไม่สามารถขอ Access Token จากระบบ FDH ได้ บัญชี FDH User หรือ รหัสผ่าน ไม่ถูกต้อง'
            ];
        }

        return [
            'token' => null,
            'type' => 'None',
            'user_name' => Auth::user()->name ?? 'Unknown',
            'fdh_user' => $fdhDetail['fdh_user'] ?? null,
            'message' => $fdhDetail['message'] ?? 'ไม่สามารถขอ Access Token จากระบบ FDH ได้ กรุณาตรวจสอบ FDH User และ Password'
        ];
    }

    /**
     * =========================================================================
     * SEND 16 FILES DIRECTLY TO MOPH FDH API GATEWAY
     * =========================================================================
     */
    public static function sendToFdhApi(array $keys, bool $isIp, string $claimCode, ?string $customToken = null, array $options = []): array
    {
        if (empty($keys)) {
            return [
                'success' => false,
                'message' => 'ไม่พบรายการที่เลือกส่ง'
            ];
        }

        // 1. Resolve Token
        $tokenInfo = self::resolveFdhToken($customToken, true);
        $token = $tokenInfo['token'];
        $tokenType = $tokenInfo['type'];
        $senderName = Auth::user()->name ?? ($tokenInfo['user_name'] ?? 'System');

        if (empty($token)) {
            return [
                'success' => false,
                'need_login' => false,
                'message' => $tokenInfo['message'] ?? 'ไม่สามารถขอ Access Token จากระบบ FDH ได้ กรุณาตรวจสอบ FDH User และ Password ในข้อมูลผู้ใช้งาน'
            ];
        }

        if (!isset($options['claim_code'])) {
            $options['claim_code'] = $claimCode;
        }

        // 2. Generate 16/17 Files
        $exportData = $isIp 
            ? self::generate16FilesIp($keys, $options) 
            : self::generate16Files($keys, $options);

        if (empty($exportData['files'])) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถสร้างชุดข้อมูล 16 แฟ้มได้'
            ];
        }

        $files = $exportData['files'];
        $hcode = $exportData['hcode'] ?: self::getHcode();

        // 3. กรองไฟล์ให้เหมาะสมกับประเภทผู้ป่วย (OPD / IPD) และไม่เกิน 16 ไฟล์ตามมาตรฐาน FDH
        $filesToSend = [];
        foreach ($files as $name => $content) {
            // สำหรับผู้ป่วยนอก (OPD) ตัดแฟ้มเฉพาะทางของ IPD ที่ว่างเปล่าออก
            if (!$isIp && in_array($name, ['IPD', 'IRF', 'IDX', 'IOP', 'LVD'])) {
                $lines = explode("\n", trim($content));
                if (count($lines) <= 1) continue;
            }
            // สำหรับผู้ป่วยใน (IPD) ตัดแฟ้มเฉพาะทางของ OPD ที่ว่างเปล่าออก
            if ($isIp && in_array($name, ['OPD', 'ORF', 'ODX', 'OOP'])) {
                $lines = explode("\n", trim($content));
                if (count($lines) <= 1) continue;
            }
            $filesToSend[$name] = $content;
        }

        // 4. ส่งไฟล์ 16 แฟ้มแบบ multipart/form-data เข้าสู่ FDH API Gateway ตามคู่มือ Ver.3
        $apiUrl = 'https://fdh.moph.go.th/api/v2/data_hub/16_files';
        $transactionId = null;
        $isSuccess = false;
        $responseMessage = '';
        $rawResponse = null;

        $makeRequest = function($authToken) use ($apiUrl, $filesToSend) {
            $http = Http::withOptions([
                'verify' => false,
                'http_errors' => false
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $authToken,
                'Accept' => 'application/json'
            ])->asMultipart();

            $http->attach('type', 'txt');
            foreach ($filesToSend as $filename => $content) {
                $http->attach('file', $content, "{$filename}.txt");
            }

            return $http->timeout(180)->post($apiUrl);
        };

        try {
            $response = $makeRequest($token);
            $status = $response->status();
            $rawResponse = $response->body();
            $json = $response->json();

            // หาก Token ไม่ผ่าน (401/403) ให้ลองส่งด้วย Hospital Gateway Token จาก Account Center
            if ($status === 401 || $status === 403) {
                $hospitalToken = self::getHospitalCentralToken();
                if (!empty($hospitalToken) && $hospitalToken !== $token) {
                    $response = $makeRequest($hospitalToken);
                    $status = $response->status();
                    $rawResponse = $response->body();
                    $json = $response->json();
                }
            }

            if ($status === 200 || $status === 201 || (isset($json['status']) && $json['status'] == 200)) {
                $isSuccess = true;
                $transactionId = $json['data']['transaction_id'] 
                    ?? ($json['data']['claim_id'] 
                    ?? ($json['data']['batch_number'] 
                    ?? ($json['transaction_id'] ?? ('FDH-' . date('YmdHis') . '-' . substr(uniqid(), -4)))));
                $responseMessage = $json['message_th'] ?? ($json['message'] ?? 'ส่งข้อมูลเข้า FDH สำเร็จ');
            } elseif ($status === 401 || $status === 403) {
                return [
                    'success' => false,
                    'need_login' => true,
                    'message' => 'Token ของ Provider ID หมดอายุหรือไม่ถูกต้อง กรุณาเข้าสู่ระบบ Provider ID อีกครั้ง'
                ];
            } else {
                $responseMessage = $json['message_th'] ?? ($json['message'] ?? ($json['error'] ?? "FDH ตอบกลับ HTTP Status $status"));
            }
        } catch (\Throwable $e) {
            Log::error("FDH Send API Exception: " . $e->getMessage());
            $responseMessage = 'เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย: ' . $e->getMessage();
        }

        // 5. If Success -> Update or Insert into fdh_claim_status table
        if ($isSuccess) {
            $now = now();
            $senderCid = Auth::user()->cid ?? null;

            try {
                if ($isIp) {
                    $admList = DB::connection('hosxp')
                        ->table('ipt')
                        ->whereIn('an', $keys)
                        ->select('an', 'hn', 'vn')
                        ->get();

                    foreach ($admList as $adm) {
                        DB::table('fdh_claim_status')->updateOrInsert(
                            ['an' => $adm->an],
                            [
                                'hn' => $adm->hn,
                                'seq' => null,
                                'an' => $adm->an,
                                'hcode' => $hcode,
                                'status' => 'WAIT',
                                'process_status' => '0',
                                'status_message_th' => 'ส่งข้อมูลผ่าน FDH API สำเร็จ (รอประมวลผล)',
                                'stm_period' => null,
                                'transaction_id' => $transactionId,
                                'send_channel' => 'API',
                                'send_date' => $now,
                                'send_user' => $senderName,
                                'send_user_cid' => $senderCid,
                                'api_response' => is_string($rawResponse) ? substr($rawResponse, 0, 2000) : json_encode($rawResponse),
                                'updated_at' => $now,
                            ]
                        );
                    }
                } else {
                    $ovstList = DB::connection('hosxp')
                        ->table('ovst')
                        ->whereIn('vn', $keys)
                        ->select('vn', 'hn')
                        ->get();

                    foreach ($ovstList as $o) {
                        DB::table('fdh_claim_status')->updateOrInsert(
                            ['seq' => $o->vn],
                            [
                                'hn' => $o->hn,
                                'seq' => $o->vn,
                                'an' => null,
                                'hcode' => $hcode,
                                'status' => 'WAIT',
                                'process_status' => '0',
                                'status_message_th' => 'ส่งข้อมูลผ่าน FDH API สำเร็จ (รอประมวลผล)',
                                'stm_period' => null,
                                'transaction_id' => $transactionId,
                                'send_channel' => 'API',
                                'send_date' => $now,
                                'send_user' => $senderName,
                                'send_user_cid' => $senderCid,
                                'api_response' => is_string($rawResponse) ? substr($rawResponse, 0, 2000) : json_encode($rawResponse),
                                'updated_at' => $now,
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Could not updateOrInsert fdh_claim_status: " . $e->getMessage());
            }

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'total' => count($keys),
                'token_type' => $tokenType,
                'sender_name' => $senderName,
                'message' => $responseMessage ?: 'ส่งข้อมูล 16 แฟ้มเข้าสู่ระบบ FDH ผ่าน API สำเร็จเรียบร้อยแล้ว'
            ];
        }

        return [
            'success' => false,
            'message' => $responseMessage ?: 'ไม่สามารถส่งข้อมูลเข้า FDH ได้ กรุณาตรวจสอบการเชื่อมต่อ',
            'raw_response' => $rawResponse
        ];
    }
}
