<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotifyController extends Controller
{
    public function notify_summary(Request $request )
    {
        $budget_year_now = DB::table('budget_year')->where('DATE_END','>=',date('Y-m-d'))->where('DATE_BEGIN','<=',date('Y-m-d'))->value('LEAVE_YEAR_ID');
        $budget_year = $request->budget_year;
        if($budget_year == '' || $budget_year == null)
        {$budget_year = $budget_year_now;}else{$budget_year =$request->budget_year;} 
        $start_date =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_BEGIN');
        $end_date = DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_END');

        $notify = DB::connection('hosxp')->select('
            SELECT COUNT(vn) AS visit,IFNULL(SUM(CASE WHEN endpoint_code LIKE "EP%" THEN 1 ELSE 0 END),0) AS "endpoint",
            IFNULL(SUM(CASE WHEN hipdata_code = "UCS" THEN 1 ELSE 0 END),0) AS "ucs_all",
            IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND endpoint_code LIKE "EP%" THEN 1 ELSE 0 END),0) AS "ucs_endpoint",
            IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")
                THEN 1 ELSE 0 END),0) AS "uc_anywhere",
            IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
                AND uc_cr_name <> "" THEN 1 ELSE 0 END),0) AS "uc_cr",
            IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
                AND ppfs_name <> "" THEN 1 ELSE 0 END),0) AS "ppfs",
            IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND (healthmed <> "" OR herb32_name <>"") THEN 1 ELSE 0 END),0) AS "uc_healthmed",
            IFNULL(SUM(CASE WHEN hipdata_code = "OFC" THEN 1 ELSE 0 END),0) AS "ofc_all",
            IFNULL(SUM(CASE WHEN hipdata_code = "OFC" AND edc_approve_list_text <>"" THEN 1 ELSE 0 END),0) AS "ofc_edc",
            IFNULL(SUM(CASE WHEN (auth_code IS NULL OR auth_code ="") AND paidst IN ("02") THEN 1 ELSE 0 END),0) AS "non_authen",
            IFNULL(SUM(CASE WHEN (hipdata_code = "UCS" OR hipdata_code ="SSS") AND (hospmain="" OR hospmain IS NULL) THEN 1 ELSE 0 END),0) AS "non_hmain"
            FROM (SELECT o.vn,o.an,vp.auth_code,os.edc_approve_list_text,IF(vp.auth_code NOT LIKE "EP%",ep.claimCode,vp.auth_code) AS endpoint_code,vp.pttype,
            vp.hospmain,p.hipdata_code,ep.sourceChannel,p.paidst,oe.moph_finance_upload_datetime AS fdh,ep.claimType,p.pttype_price_group_id,v.pdx,
            GROUP_CONCAT(n1.`name`) AS uc_cr_name,SUM(o1.sum_price) AS uc_cr_price,GROUP_CONCAT(n2.`name`) AS ppfs_name,SUM(o2.sum_price) AS ppfs_price,
            GROUP_CONCAT(n3.`name`) AS herb32_name,SUM(o3.sum_price) AS herb32_price,hm.vn AS healthmed
            FROM ovst o
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN ovst_seq os ON os.vn=o.vn
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE uc_cr = "Y")
            LEFT JOIN nondrugitems n1 ON n1.icode=o1.icode
            LEFT JOIN opitemrece o2 ON o2.vn=o.vn AND o2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
            LEFT JOIN nondrugitems n2 ON n2.icode=o2.icode
            LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE herb32 = "Y")
            LEFT JOIN drugitems n3 ON n3.icode=o3.icode
            LEFT JOIN health_med_service hm ON hm.vn=o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate
            WHERE o.vstdate = DATE(DATE_ADD(now(), INTERVAL -1 DAY )) AND (o.an ="" OR o.an IS NULL) GROUP BY o.vn ) AS a');         

        foreach ($notify as $row){
                $visit=$row->visit;
                $endpoint=$row->endpoint;
                $ucs_all=$row->ucs_all;
                $ucs_endpoint=$row->ucs_endpoint;
                $uc_anywhere=$row->uc_anywhere;
                $uc_cr=$row->uc_cr;
                $uc_healthmed=$row->uc_healthmed;
                $ppfs=$row->ppfs;
                $ofc_all=$row->ofc_all;
                $ofc_edc=$row->ofc_edc;
                $non_authen=$row->non_authen;
                $non_hmain=$row->non_hmain;
        }    

        $ipd_dchsummary = DB::connection('hosxp')->select('
            SELECT SUM(CASE WHEN (id1.diag_text ="" OR id1.diag_text IS NULL) THEN 1 ELSE 0 END) AS non_diagtext,
            SUM(CASE WHEN (id.icd10 ="" OR id.icd10 IS NULL) AND id1.diag_text <>"" AND id1.diag_text IS NOT NULL THEN 1 ELSE 0 END) AS non_icd10
            FROM ipt i
            LEFT JOIN iptdiag id ON id.an = i.an AND id.diagtype = 1
            LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
            WHERE i.dchdate >= "'.$start_date.'" AND  i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y") 
            AND (id.icd10 ="" OR id.icd10 IS NULL OR id1.diag_text ="" OR id1.diag_text IS NULL)');        
        foreach ($ipd_dchsummary as $row){ 
            $non_diagtext=$row->non_diagtext;
            $non_icd10=$row->non_icd10;
            $url_ipd_dchsummary=url('ipd_non_dchsummary'); 
        }  

    //แจ้งเตือน Telegram

        $message = "ข้อมูลบริการ" ."วันที่ ". DateThai(date("Y-m-d", strtotime("-1 day"))) ."\n"  
        ."---------------------------------"  ."\n"        
        ."OP Visit: " .$visit ." visit" ."\n" 
        ."ปิดสิทธิ: " .$endpoint ." visit" ."\n"
        ."สิทธิ UCS|ปิดสิทธิ: " .$ucs_all ."|" .$ucs_endpoint ." visit" ."\n"
        ."  -OP Anywhere: " .$uc_anywhere ." visit" ."\n"
        ."  -บริการเฉพาะ CR: " .$uc_cr ." visit" ."\n"
        ."  -บริการแพทย์แผนไทย: " .$uc_healthmed ." visit" ."\n"
        ."PPFS: " .$ppfs ." visit" ."\n"
        ."สิทธิ OFC|รูดบัตร: " .$ofc_all ."|" .$ofc_edc ." visit" ."\n"
        ."ไม่ขอ Authen: " .$non_authen ." visit" ."\n" 
        ."ไม่บันทึก Hmain: " .$non_authen ." Visit" ."\n" 
        ."---------------------------------"  ."\n" 
        ."Chart รอลงรหัสโรค: " .$non_icd10 ." AN" ."\n"
        ."Chart รอแพทย์สรุป: " .$non_diagtext ." AN" ."\n" 
        .$url_ipd_dchsummary ."\n";  

        $token =  DB::table('main_setting')->where('name','telegram_token')->value('value'); //Notify_Bot
        $telegram_chat_id =  DB::table('main_setting')->where('name','telegram_chat_id')->value('value'); 
        $chat_ids = explode(',', $telegram_chat_id); //Notify_Group
  
        foreach ($chat_ids as $chat_id) {
                $url = "https://api.telegram.org/bot$token/sendMessage";
    
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $message
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
