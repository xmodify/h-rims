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
     * Map รหัสหมวดรายได้ HOSxP (income) ให้เป็นรหัส 16 หมวด สปสช. (CHA CHRGITEM)
     */
    private static function mapIncomeToChaItem($income)
    {
        $inc = str_pad(trim((string)$income), 2, '0', STR_PAD_LEFT);
        switch ($inc) {
            case '01': return '11'; // ค่าห้อง/ค่าอาหาร
            case '02': return '21'; // ค่าอาหาร
            case '03': return '41'; // ค่ายาในบัญชี
            case '04': return '42'; // ค่ายานอกบัญชี
            case '17': return '41'; // ยาอื่นๆ
            case '05': return '51'; // ค่าเวชภัณฑ์มิใช่ยา
            case '06': return '61'; // ค่าบริการโลหิต
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
     * ประมวลผลและสร้างเนื้อหา 16 แฟ้ม จากรายการ VNs ที่เลือก
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
        // 1. Query Visit Details (ovst, vn_stat, patient, pttype, doctor, clinic)
        // -------------------------------------------------------------
        $visits = DB::connection('hosxp')->select("
            SELECT o.vn, o.vn as seq, o.hn, o.an, o.vstdate, o.vsttime, o.spclty, o.main_dep, o.cur_dep,
                   v.pttype, v.pdx, v.dx_doctor, v.income, v.paid_money, v.rcpt_money, v.uc_money,
                   pt.cid, pt.pname, pt.fname, pt.lname, pt.birthday, pt.sex, pt.marrystatus, pt.occupation, pt.nationality,
                   pt.chwpart, pt.amppart, pt.tmbpart,
                   p.hipdata_code, p.pttype_sks_code, p.nhso_code,
                   doc.licenseno as doctor_license, doc.name as doctor_name,
                   o.pt_subtype,
                   COALESCE(vp.hospmain, v.hospmain) as hospmain,
                   COALESCE(vp.hospsub, v.hospsub) as hospsub,
                   vp.claim_code as permitno,
                   v.auth_code,
                   v.gov_code,
                   v.gov_name
            FROM ovst o
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN pttype p ON p.pttype = o.pttype
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype = o.pttype
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.vn IN ($placeholders)
            ORDER BY o.vstdate, o.vsttime
        ", $vns);

        $visits = collect($visits);
        $vnsList = $visits->pluck('vn')->toArray();
        $hnsList = $visits->pluck('hn')->unique()->toArray();
        $ansList = $visits->pluck('an')->filter()->unique()->toArray();

        // -------------------------------------------------------------
        // 2. Query OPD Diag (ovstdiag)
        // -------------------------------------------------------------
        $opdDiags = [];
        if (!empty($vnsList)) {
            $diagRows = DB::connection('hosxp')->select("
                SELECT od.vn, od.hn, od.vstdate, od.icd10, od.diagtype, od.doctor,
                       doc.licenseno as doctor_license,
                       pt.cid, o.cur_dep as clinic
                FROM ovstdiag od
                LEFT JOIN ovst o ON o.vn = od.vn
                LEFT JOIN patient pt ON pt.hn = od.hn
                LEFT JOIN doctor doc ON doc.code = od.doctor
                WHERE od.vn IN ($placeholders)
                ORDER BY od.vn, od.diagtype
            ", $vnsList);
            $opdDiags = collect($diagRows);
        }

        // -------------------------------------------------------------
        // 3. Query OPD Procedure (ovst_operation / doctor_operation)
        // -------------------------------------------------------------
        $opdOpers = [];
        if (!empty($vnsList)) {
            $operRows = DB::connection('hosxp')->select("
                SELECT op.vn, op.hn, o.vstdate, o.cur_dep as clinic,
                       op.icd9, op.doctor, doc.licenseno as doctor_license,
                       pt.cid, op.price as servprice
                FROM ovst_operation op
                LEFT JOIN ovst o ON o.vn = op.vn
                LEFT JOIN patient pt ON pt.hn = op.hn
                LEFT JOIN doctor doc ON doc.code = op.doctor
                WHERE op.vn IN ($placeholders)
                ORDER BY op.vn
            ", $vnsList);
            $opdOpers = collect($operRows);
        }

        // -------------------------------------------------------------
        // 4. Query Items (opitemrece, drugitems, nondrugitems)
        // -------------------------------------------------------------
        $items = [];
        if (!empty($vnsList)) {
            $itemRows = DB::connection('hosxp')->select("
                SELECT op.vn, op.hn, op.an, op.vstdate, op.vsttime, op.icode, op.qty, op.unitprice, op.sum_price, op.cost,
                       op.income, op.nhso_adp_code, op.nhso_adp_type, op.nhso_sub_code, op.claim_code, op.hos_guid,
                       d.name as drug_name, d.units as drug_unit, d.packing as drug_pack, d.did as drug_did,
                       d.tmt_tp_code, d.tmt_gp_code, d.nhso_tmt_id, d.therapeutic,
                       n.name as nondrug_name, n.nhso_adp_code as nondrug_adp_code, n.nhso_adp_type as nondrug_adp_type,
                       o.cur_dep as clinic, pt.cid, doc.licenseno as doctor_license,
                       op.drugusage, du.code as sigcode, du.name1 as sigtext1, du.name2 as sigtext2, du.name3 as sigtext3
                FROM opitemrece op
                LEFT JOIN ovst o ON o.vn = op.vn
                LEFT JOIN patient pt ON pt.hn = op.hn
                LEFT JOIN drugitems d ON d.icode = op.icode
                LEFT JOIN nondrugitems n ON n.icode = op.icode
                LEFT JOIN doctor doc ON doc.code = op.doctor
                LEFT JOIN drugusage du ON du.drugusage = op.drugusage
                WHERE op.vn IN ($placeholders)
                ORDER BY op.vn, op.item_no
            ", $vnsList);
            $items = collect($itemRows);
        }

        // -------------------------------------------------------------
        // 5. Query Refer (referout / referin)
        // -------------------------------------------------------------
        $referOuts = [];
        if (!empty($vnsList)) {
            $referRows = DB::connection('hosxp')->select("
                SELECT ro.vn, ro.hn, ro.refer_date, ro.refer_hospcode, ro.refer_type,
                       ro.refer_number, o.cur_dep as clinic, ro.station_id
                FROM referout ro
                LEFT JOIN ovst o ON o.vn = ro.vn
                WHERE ro.vn IN ($placeholders)
            ", $vnsList);
            $referOuts = collect($referRows);
        }

        // -------------------------------------------------------------
        // 6. Query ER (er_regist) for AER.txt
        // -------------------------------------------------------------
        $erVisits = [];
        if (!empty($vnsList)) {
            $erRows = DB::connection('hosxp')->select("
                SELECT er.vn, o.hn, o.vstdate, er.enter_time, er.er_emergency_type,
                       er.er_accident_type_id, er.accident_place_type_id,
                       er.er_pt_type, er.accident_transport_type_id,
                       er.er_accident_airway_type_id, er.er_accident_alcohol_type_id,
                       ro.refer_number as refer_no, ro.refer_hospcode as refer_hosp
                FROM er_regist er
                LEFT JOIN ovst o ON o.vn = er.vn
                LEFT JOIN referout ro ON ro.vn = er.vn
                WHERE er.vn IN ($placeholders)
            ", $vnsList);
            $erVisits = collect($erRows)->keyBy('vn');
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
            
            $ipdVisits = collect(DB::connection('hosxp')->select("
                SELECT ipt.an, ipt.hn, ipt.regdate, ipt.regtime, ipt.dchdate, ipt.dchtime,
                       ipt.dchstts as dischs, ipt.dchtype as discht, ipt.ward as warddsc,
                       ipt.spclty as dept, ipt.bw as adm_w, ipt.svctype
                FROM ipt
                WHERE ipt.an IN ($anPlaceholders)
            ", $ansList))->keyBy('an');

            $ipdDiags = collect(DB::connection('hosxp')->select("
                SELECT id.an, id.icd10, id.diagtype, doc.licenseno as drdx
                FROM iptdiag id
                LEFT JOIN doctor doc ON doc.code = id.doctor
                WHERE id.an IN ($anPlaceholders)
                ORDER BY id.an, id.diagtype
            ", $ansList));

            $ipdOpers = collect(DB::connection('hosxp')->select("
                SELECT io.an, io.icd9 as oper, io.opertype, doc.licenseno as dropid,
                       io.opdate as datein, io.optime as timein, io.enddate as dateout, io.endtime as timeout
                FROM ipt_operation io
                LEFT JOIN doctor doc ON doc.code = io.doctor
                WHERE io.an IN ($anPlaceholders)
            ", $ansList));
        }

        // =============================================================
        // GENERATE EACH OF THE 16 FILES
        // =============================================================

        // 1. INS.txt (สิทธิการรักษาพยาบาล)
        // HN|INSCL|SUBTYPE|CID|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNRNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE
        $insLines = [];
        foreach ($visits as $v) {
            $inscl = $v->hipdata_code ?: ($v->pttype_sks_code ?: 'A2');
            $subtype = $v->pt_subtype ?: 'O4';
            $cid = trim((string)$v->cid);
            $hospmain = $v->hospmain ?: '';
            $hospsub = $v->hospsub ?: '';
            $govcode = $v->gov_code ?: '';
            $govname = $v->gov_name ?: '';
            $permitno = $v->permitno ?: ($v->auth_code ?: '');
            $docno = '';
            $ownrpid = '';
            $ownrname = '';
            $an = $v->an ?: '';
            $seq = $v->seq ?: $v->vn;
            $subinscl = '';
            $relinscl = '';
            $htype = '';

            $insLines[] = "{$v->hn}|{$inscl}|{$subtype}|{$cid}|{$hospmain}|{$hospsub}|{$govcode}|{$govname}|{$permitno}|{$docno}|{$ownrpid}|{$ownrname}|{$an}|{$seq}|{$subinscl}|{$relinscl}|{$htype}";
        }

        // 2. PAT.txt (ข้อมูลผู้ป่วย)
        // HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE
        $patLines = [];
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
            $nation = $v->nationality ?: '099';
            $cid = trim((string)$v->cid);
            $title = trim((string)$v->pname);
            $fname = trim((string)$v->fname);
            $lname = trim((string)$v->lname);
            $namepat = "{$fname}  {$lname} , {$title}";
            $idtype = '1';

            $patLines[] = "{$hcode}|{$v->hn}|{$chw}|{$amp}|{$dob}|{$sex}|{$marry}|{$occupa}|{$nation}|{$cid}|{$namepat}|{$title}|{$fname}|{$lname}|{$idtype}";
        }

        // 3. OPD.txt (ข้อมูลผู้ป่วยนอก)
        // HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC
        $opdLines = [];
        foreach ($visits as $v) {
            $clinic = str_pad(trim((string)$v->cur_dep), 5, '0', STR_PAD_LEFT) ?: '00100';
            $dateopd = self::formatDate($v->vstdate);
            $timeopd = self::formatTime($v->vsttime);
            $seq = $v->seq ?: $v->vn;
            $uuc = '1';

            $opdLines[] = "{$v->hn}|{$clinic}|{$dateopd}|{$timeopd}|{$seq}|{$uuc}";
        }

        // 4. IPD.txt (ข้อมูลผู้ป่วยใน)
        // HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE
        $ipdLines = [];
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
            $svctype = $ip->svctype ?: '';

            $ipdLines[] = "{$v->hn}|{$v->an}|{$dateadm}|{$timeadm}|{$datedsc}|{$timedsc}|{$dischs}|{$discht}|{$ward}|{$dept}|{$admw}|{$uuc}|{$svctype}";
        }

        // 5. ODX.txt (วินิจฉัยโรค OPD)
        // HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ
        $odxLines = [];
        foreach ($opdDiags as $d) {
            $clinic = str_pad(trim((string)$d->clinic), 5, '0', STR_PAD_LEFT) ?: '00100';
            $datedx = self::formatDate($d->vstdate);
            $diag = strtoupper(str_replace('.', '', trim((string)$d->icd10)));
            if (empty($diag)) continue;
            $dxtype = $d->diagtype ?: '1';
            $drdx = $d->doctor_license ?: ($d->doctor ?: 'ว00000');
            $cid = trim((string)$d->cid);
            $seq = $d->vn;

            $odxLines[] = "{$d->hn}|{$datedx}|{$clinic}|{$diag}|{$dxtype}|{$drdx}|{$cid}|{$seq}";
        }

        // 6. OOP.txt (หัตถการ OPD)
        // HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ|SERVPRICE
        $oopLines = [];
        foreach ($opdOpers as $op) {
            $clinic = str_pad(trim((string)$op->clinic), 5, '0', STR_PAD_LEFT) ?: '01200';
            $dateopd = self::formatDate($op->vstdate);
            $oper = str_replace('.', '', trim((string)$op->icd9));
            if (empty($oper)) continue;
            $dropid = $op->doctor_license ?: ($op->doctor ?: 'พ00000');
            $cid = trim((string)$op->cid);
            $seq = $op->vn;
            $servprice = $op->servprice ? number_format((float)$op->servprice, 2, '.', '') : '';

            $oopLines[] = "{$op->hn}|{$dateopd}|{$clinic}|{$oper}|{$dropid}|{$cid}|{$seq}|{$servprice}";
        }

        // 7. IDX.txt (วินิจฉัยโรค IPD)
        // AN|DIAG|DXTYPE|DRDX
        $idxLines = [];
        foreach ($ipdDiags as $id) {
            $diag = strtoupper(str_replace('.', '', trim((string)$id->icd10)));
            if (empty($diag)) continue;
            $dxtype = $id->diagtype ?: '1';
            $drdx = $id->drdx ?: 'ว00000';

            $idxLines[] = "{$id->an}|{$diag}|{$dxtype}|{$drdx}";
        }

        // 8. IOP.txt (หัตถการผ่าตัด IPD)
        // AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT
        $iopLines = [];
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

        // 9. ORF.txt (ส่งต่อผู้ป่วยนอก)
        // HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE
        $orfLines = [];
        foreach ($referOuts as $ro) {
            $dateopd = self::formatDate($ro->refer_date);
            $clinic = str_pad(trim((string)$ro->clinic), 5, '0', STR_PAD_LEFT) ?: '01200';
            $refer = trim((string)$ro->refer_hospcode);
            $refertype = $ro->refer_type ?: '2';
            $seq = $ro->vn;
            $referdate = self::formatDate($ro->refer_date);

            $orfLines[] = "{$ro->hn}|{$dateopd}|{$clinic}|{$refer}|{$refertype}|{$seq}|{$referdate}";
        }

        // 10. IRF.txt (ส่งต่อผู้ป่วยใน)
        // AN|REFER|REFERTYPE
        $irfLines = [];
        foreach ($referOuts as $ro) {
            $v = $visits->firstWhere('vn', $ro->vn);
            if ($v && !empty($v->an)) {
                $refer = trim((string)$ro->refer_hospcode);
                $refertype = $ro->refer_type ?: '2';
                $irfLines[] = "{$v->an}|{$refer}|{$refertype}";
            }
        }

        // 11. LVD.txt (การลากลับบ้าน)
        // HN|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY
        $lvdLines = [];

        // 12. DRU.txt (รายการสั่งใช้ยา)
        // HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRIC|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER
        $druLines = [];
        $drugItems = $items->filter(function($it) {
            return str_starts_with($it->icode, '1');
        });

        foreach ($drugItems as $it) {
            $clinic = str_pad(trim((string)$it->clinic), 5, '0', STR_PAD_LEFT) ?: '00100';
            $dateserv = self::formatDate($it->vstdate);
            $did = $it->icode;
            $didname = str_replace('|', ' ', trim((string)$it->drug_name));
            $amount = (float)$it->qty;
            $amountStr = $amount == floor($amount) ? (string)intval($amount) : number_format($amount, 2, '.', '');
            $drugpric = number_format((float)$it->unitprice, 2, '.', '');
            $drugcost = number_format((float)$it->cost, 2, '.', '');
            $didstd = $it->nhso_tmt_id ?: ($it->tmt_tp_code ?: ($it->tmt_gp_code ?: ($it->drug_did ?: '')));
            $unit = trim((string)$it->drug_unit) ?: 'เม็ด';
            $unitpack = trim((string)$it->drug_pack) ?: "1x{$unit}";
            $seq = $it->vn;
            $an = $it->an ?: '';
            $cid = trim((string)$it->cid);
            $remark = '';
            $pano = '';
            $totcopay = '0.00';
            $usestatus = '';
            $total = number_format((float)$it->sum_price, 2, '.', '');
            $sigcode = trim((string)$it->sigcode);
            $sigtext = trim((string)$it->sigtext1 . ' ' . (string)$it->sigtext2);
            $provider = $it->doctor_license ?: '';

            $druLines[] = "{$hcode}|{$it->hn}|{$an}|{$clinic}|{$cid}|{$dateserv}|{$did}|{$didname}|{$amountStr}|{$drugpric}|{$drugcost}|{$didstd}|{$unit}|{$unitpack}|{$seq}|{$remark}|{$pano}|{$totcopay}|{$usestatus}|{$total}|{$sigcode}|{$sigtext}|{$provider}";
        }

        // 13. CHA.txt (ค่าบริการ 16 หมวด สปสช.)
        // HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ
        $chaLines = [];
        $itemsByVn = $items->groupBy('vn');
        foreach ($itemsByVn as $vn => $vnItems) {
            $v = $visits->firstWhere('vn', $vn);
            if (!$v) continue;

            $date = self::formatDate($v->vstdate);
            $cid = trim((string)$v->cid);
            $an = $v->an ?: '';
            $seq = $v->seq ?: $v->vn;

            // Group by CHA CHRGITEM
            $chaGroups = [];
            foreach ($vnItems as $it) {
                $chrg = self::mapIncomeToChaItem($it->income);
                if (!isset($chaGroups[$chrg])) {
                    $chaGroups[$chrg] = 0.0;
                }
                $chaGroups[$chrg] += (float)$it->sum_price;
            }

            foreach ($chaGroups as $chrg => $sumAmt) {
                $amtStr = number_format($sumAmt, 2, '.', '');
                $chaLines[] = "{$v->hn}|{$an}|{$date}|{$chrg}|{$amtStr}|{$cid}|{$seq}";
            }
        }

        // 14. CHT.txt (สรุปยอดรวมค่าใช้จ่ายและใบเสร็จ)
        // HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ|OPD_MEMO|INVOICE_NO|INVOICE_LT
        $chtLines = [];
        foreach ($visits as $v) {
            $date = self::formatDate($v->vstdate);
            $total = number_format((float)$v->income, 2, '.', '');
            $paid = number_format((float)($v->rcpt_money ?: 0.0), 2, '.', '');
            $pttype = $v->hipdata_code ?: ($v->pttype_sks_code ?: 'A2');
            $cid = trim((string)$v->cid);
            $an = $v->an ?: '';
            $seq = $v->seq ?: $v->vn;

            $chtLines[] = "{$v->hn}|{$an}|{$date}|{$total}|{$paid}|{$pttype}|{$cid}|{$seq}";
        }

        // 15. AER.txt (อุบัติเหตุและฉุกเฉิน)
        // HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT
        $aerLines = [];
        foreach ($visits as $v) {
            $er = $erVisits->get($v->vn);
            if ($er) {
                $dateopd = self::formatDate($v->vstdate);
                $authae = '';
                $aedate = $dateopd;
                $aetime = self::formatTime($er->enter_time);
                $aetype = '';
                $referno = $er->refer_no ?: '';
                $refmaini = '';
                $ireftype = '';
                $refmaino = $er->refer_hosp ?: '';
                $oreftype = '1100';
                $ucae = '';
                $emtype = '3';
                $seq = $v->vn;
                $an = $v->an ?: '';

                $aerLines[] = "{$v->hn}|{$an}|{$dateopd}|{$authae}|{$aedate}|{$aetime}|{$aetype}|{$referno}|{$refmaini}|{$ireftype}|{$refmaino}|{$oreftype}|{$ucae}|{$emtype}|{$seq}|||";
            }
        }

        // 16. ADP.txt (บริการเสริม/อุปกรณ์/PPFS/แลปพิเศษ)
        // HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP|LMP|SP_ITEM
        $adpLines = [];
        $nonDrugItems = $items->filter(function($it) {
            return !str_starts_with($it->icode, '1');
        });

        foreach ($nonDrugItems as $it) {
            $dateopd = self::formatDate($it->vstdate);
            $type = $it->nhso_adp_type ?: ($it->nondrug_adp_type ?: '14'); // Default 14 (ค่าบริการ)
            $code = $it->nhso_adp_code ?: ($it->nondrug_adp_code ?: $it->icode);
            $qty = intval($it->qty) ?: 1;
            $rate = number_format((float)$it->unitprice, 2, '.', '');
            $seq = $it->vn;
            $an = $it->an ?: '';
            $cagcode = "{$seq}:{$type}:{$code}:{$rate}:False";
            $dose = $it->hos_guid ?: ('{' . strtoupper(md5($it->vn . $it->icode . microtime())) . '}');
            $total = number_format((float)$it->sum_price, 2, '.', '');
            $clinic = str_pad(trim((string)$it->clinic), 5, '0', STR_PAD_LEFT) ?: '00100';
            $provider = $it->doctor_license ?: '';

            $adpLines[] = "{$it->hn}|{$an}|{$dateopd}|{$type}|{$code}|{$qty}|{$rate}|{$seq}|{$cagcode}|{$dose}|||0.00||{$total}|||||{$clinic}||{$provider}|||||";
        }

        // =============================================================
        // COMPILE FINAL RESULT WITH ALL 16 FILES
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
        ];

        $counts = [
            'INS' => count($insLines),
            'PAT' => count($patLines),
            'OPD' => count($opdLines),
            'IPD' => count($ipdLines),
            'ODX' => count($odxLines),
            'OOP' => count($oopLines),
            'IDX' => count($idxLines),
            'IOP' => count($iopLines),
            'ORF' => count($orfLines),
            'IRF' => count($irfLines),
            'LVD' => count($lvdLines),
            'DRU' => count($druLines),
            'CHA' => count($chaLines),
            'CHT' => count($chtLines),
            'AER' => count($aerLines),
            'ADP' => count($adpLines),
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
