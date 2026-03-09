<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotifyController extends Controller
{
    public function notify_summary(Request $request)
    {
        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');
        $budget_year = $request->budget_year ?: $budget_year_now;
        $year = DB::table('budget_year')
            ->where('LEAVE_YEAR_ID', $budget_year)
            ->first(['DATE_BEGIN', 'DATE_END']);
        $start_date = $year->DATE_BEGIN ?? null;
        $end_date = $year->DATE_END ?? null;

        $notify = DB::connection('hosxp')->select('
            SELECT
                COUNT(vn) AS visit,
                SUM(CASE WHEN endpoint = "Y" THEN 1 ELSE 0 END) AS endpoint,
                SUM(CASE WHEN hipdata_code IN ("UCS","WEL") THEN 1 ELSE 0 END) AS ucs_all,
                SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND endpoint = "Y" THEN 1 ELSE 0 END) AS ucs_endpoint,
                SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND (hospmain <> "" AND hospmain IS NOT NULL) AND IFNULL(in_province,"N") <> "Y" THEN 1 ELSE 0 END) AS uc_anywhere,
                SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND IFNULL(in_province,"N") = "Y" AND uc_cr_flag = "Y" THEN 1 ELSE 0 END) AS uc_cr,
                SUM(CASE WHEN ppfs_flag = "Y" THEN 1 ELSE 0 END) AS ppfs,
                SUM(CASE WHEN hipdata_code IN ("UCS","WEL") AND healthmed_flag = "Y" THEN 1 ELSE 0 END) AS uc_healthmed,
                SUM(CASE WHEN hipdata_code = "OFC" THEN 1 ELSE 0 END) AS ofc_all,
                SUM(CASE WHEN hipdata_code = "OFC" AND (edc_approve_list_text <> "" OR claim_code_flag = "Y") THEN 1 ELSE 0 END) AS ofc_edc,
                SUM(CASE WHEN (auth_code = "" OR auth_code IS NULL) AND cid NOT LIKE "0%" THEN 1 ELSE 0 END) AS non_authen,
                SUM(CASE WHEN hipdata_code IN ("UCS","WEL","SSS","STP") AND (hospmain = "" OR hospmain IS NULL) THEN 1 ELSE 0 END) AS non_hmain
            FROM (
                SELECT o.vn, pt.cid, vp.auth_code, p.hipdata_code, vp.hospmain, os.edc_approve_list_text,
                    lh.in_province,
                    MAX(CASE WHEN li.ppfs = "Y" THEN "Y" ELSE "N" END) as ppfs_flag,
                    MAX(CASE WHEN li.uc_cr = "Y" THEN "Y" ELSE "N" END) as uc_cr_flag,
                    MAX(CASE WHEN li.herb32 = "Y" THEN "Y" ELSE "N" END) as herb_flag,
                    IF(hms.vn IS NOT NULL, "Y", "N") as healthmed_flag,
                    MAX(CASE WHEN vp.Claim_Code IS NOT NULL AND vp.Claim_Code <> "" THEN "Y" ELSE "N" END) as claim_code_flag,
                    IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001"),"Y",NULL) AS endpoint
                FROM ovst o
                LEFT JOIN patient pt ON pt.hn = o.hn
                LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
                LEFT JOIN pttype p ON p.pttype = vp.pttype
                LEFT JOIN ovst_seq os ON os.vn = o.vn
                LEFT JOIN opitemrece ori ON ori.vn = o.vn
                LEFT JOIN hrims.lookup_icode li ON li.icode = ori.icode
                LEFT JOIN hrims.lookup_hospcode lh ON lh.hospcode = vp.hospmain
                LEFT JOIN (
                    SELECT h.vn FROM health_med_service h
                    INNER JOIN health_med_service_operation hso ON hso.health_med_service_id = h.health_med_service_id
                    GROUP BY h.vn
                ) hms ON hms.vn = o.vn
                LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate AND ep.claimCode LIKE "EP%"
                WHERE o.vstdate = DATE(DATE_ADD(NOW(), INTERVAL -1 DAY)) AND (o.an = "" OR o.an IS NULL)
                GROUP BY o.vn
            ) AS a');

        foreach ($notify as $row) {
            $visit = $row->visit;
            $endpoint = $row->endpoint;
            $ucs_all = $row->ucs_all;
            $ucs_endpoint = $row->ucs_endpoint;
            $uc_anywhere = $row->uc_anywhere;
            $uc_cr = $row->uc_cr;
            $uc_healthmed = $row->uc_healthmed;
            $ppfs = $row->ppfs;
            $ofc_all = $row->ofc_all;
            $ofc_edc = $row->ofc_edc;
            $non_authen = $row->non_authen;
            $non_hmain = $row->non_hmain;
        }

        $ipd_dchsummary = DB::connection('hosxp')->select('
            SELECT
                SUM(CASE WHEN (a.diag_text_list IS NULL OR a.diag_text_list = "") THEN 1 ELSE 0 END) AS non_diagtext,
                SUM(CASE WHEN (a.diag_text_list IS NOT NULL AND a.diag_text_list <> "") AND (id.icd10 IS NULL OR id.icd10 = "") THEN 1 ELSE 0 END) AS non_icd10
            FROM ipt i
            LEFT JOIN iptdiag id ON id.an = i.an AND id.diagtype = 1
            LEFT JOIN an_stat a ON a.an = i.an
            WHERE i.dchdate BETWEEN ? AND ?
            AND i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
            AND (
                (a.diag_text_list IS NULL OR a.diag_text_list = "")
                OR
                ((a.diag_text_list IS NOT NULL AND a.diag_text_list <> "") AND (id.icd10 IS NULL OR id.icd10 = ""))
            )', [$start_date, $end_date]);
        foreach ($ipd_dchsummary as $row) {
            $non_diagtext = $row->non_diagtext;
            $non_icd10 = $row->non_icd10;
            $url_ipd_dchsummary = url('ipd_non_dchsummary');
        }

        //แจ้งเตือน Telegram

        $message = "ข้อมูลบริการ" . "วันที่ " . DateThai(date("Y-m-d", strtotime("-1 day"))) . "\n"
            . "---------------------------------" . "\n"
            . "OP Visit: " . $visit . " visit" . "\n"
            . "ปิดสิทธิ: " . $endpoint . " visit" . "\n"
            . "สิทธิ UCS|ปิดสิทธิ: " . $ucs_all . "|" . $ucs_endpoint . " visit" . "\n"
            . "  -OP Anywhere: " . $uc_anywhere . " visit" . "\n"
            . "  -บริการเฉพาะ CR: " . $uc_cr . " visit" . "\n"
            . "  -บริการแพทย์แผนไทย: " . $uc_healthmed . " visit" . "\n"
            . "PPFS: " . $ppfs . " visit" . "\n"
            . "สิทธิ OFC|รูดบัตร: " . $ofc_all . "|" . $ofc_edc . " visit" . "\n"
            . "ไม่ขอ Authen: " . $non_authen . " visit" . "\n"
            . "ไม่บันทึก Hmain: " . $non_hmain . " Visit" . "\n"
            . "---------------------------------" . "\n"
            . "Chart รอแพทย์สรุป: " . $non_diagtext . " AN" . "\n"
            . "Chart รอลงรหัสโรค: " . $non_icd10 . " AN" . "\n"
            . $url_ipd_dchsummary . "\n";

        $token = DB::table('main_setting')->where('name', 'telegram_token')->value('value'); //Notify_Bot
        $telegram_chat_id = DB::table('main_setting')->where('name', 'telegram_chat_id_ipdsummary')->value('value');
        $chat_ids = explode(',', $telegram_chat_id); //Notify_Group

        foreach ($chat_ids as $chat_id) {
            $url = "https://api.telegram.org/bot$token/sendMessage";

            $data = [
                'chat_id' => $chat_id,
                'text' => $message
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
            sleep(1);
        }

        return response()->json(['success' => 'success'], 200);
    }
}
