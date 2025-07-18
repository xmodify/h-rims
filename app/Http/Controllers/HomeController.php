<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Nhso_Endpoint;
use Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
public function __construct()
{
    $this->middleware('auth')->except(['ipd_non_dchsummary','ipd_finance_chk_opd_wait_transfer','ipd_finance_chk_wait_rcpt_money']);
}

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
public function index(Request $request )
{
    $budget_year_now = DB::table('budget_year')->where('DATE_END','>=',date('Y-m-d'))->where('DATE_BEGIN','<=',date('Y-m-d'))->value('LEAVE_YEAR_ID');
    $budget_year = $request->budget_year;
        if($budget_year == '' || $budget_year == null)
        {$budget_year = $budget_year_now;}else{$budget_year =$request->budget_year;} 
    $start_date =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_BEGIN');
    $end_date = DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_END');

    $opd_monitor = DB::connection('hosxp')->select('
        SELECT COUNT(vn) AS total,IFNULL(SUM(CASE WHEN endpoint<>"" THEN 1 ELSE 0 END),0) AS "endpoint",
        IFNULL(SUM(CASE WHEN hipdata_code="OFC" THEN 1 ELSE 0 END),0) AS "ofc",
        IFNULL(SUM(CASE WHEN hipdata_code="OFC" AND edc_approve_list_text <> "" THEN 1 ELSE 0 END),0) AS "ofc_edc",
        IFNULL(SUM(CASE WHEN auth_code="" AND cid NOT LIKE "0%" THEN 1 ELSE 0 END),0) AS "non_authen",
        IFNULL(SUM(CASE WHEN hipdata_code IN ("UCS","SSS","STP") AND hospmain="" THEN 1 ELSE 0 END),0) AS "non_hmain",
        IFNULL(SUM(CASE WHEN hipdata_code="UCS" AND hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")
            THEN 1 ELSE 0 END),0) AS "uc_anywhere",
        IFNULL(SUM(CASE WHEN hipdata_code="UCS" AND hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")
            AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_anywhere_endpoint",
        IFNULL(SUM(CASE WHEN hipdata_code="UCS" AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
            AND uc_cr<>"" THEN 1 ELSE 0 END),0) AS "uc_cr",
        IFNULL(SUM(CASE WHEN hipdata_code="UCS" AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
            AND uc_cr<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_cr_endpoint",
        IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
            AND herb<>"" THEN 1 ELSE 0 END),0) AS "uc_herb",
        IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
            AND herb<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_herb_endpoint",
        IFNULL(SUM(CASE WHEN ppfs<>"" THEN 1 ELSE 0 END),0) AS "ppfs",
        IFNULL(SUM(CASE WHEN ppfs<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "ppfs_endpoint",
        IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND healthmed<>"" THEN 1 ELSE 0 END),0) AS "uc_healthmed",
        IFNULL(SUM(CASE WHEN hipdata_code = "UCS" AND healthmed<>"" AND endpoint ="Y" THEN 1 ELSE 0 END),0) AS "uc_healthmed_endpoint"
        FROM(SELECT o.vn,pt.cid,pt.nationality,vp.auth_code,p.pttype,p.paidst,p.hipdata_code,vp.hospmain,os.edc_approve_list_text,
        uc_cr.vn AS uc_cr,herb.vn AS herb,ppfs.vn AS ppfs,healthmed.vn AS healthmed,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,v.income,v.paid_money
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN ovst_seq os ON os.vn=o.vn
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
        LEFT JOIN opitemrece uc_cr ON uc_cr.vn=o.vn AND uc_cr.icode IN (SELECT icode FROM hrims.lookup_icode WHERE uc_cr = "Y")
        LEFT JOIN opitemrece herb ON herb.vn=o.vn AND herb.icode IN (SELECT icode FROM hrims.lookup_icode WHERE herb32 = "Y")
        LEFT JOIN health_med_service healthmed ON healthmed.vn=o.vn
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
        WHERE o.vstdate = DATE(NOW()) AND (o.an ="" OR o.an IS NULL) GROUP BY o.vn) AS a');

    foreach ($opd_monitor as $row){
        $opd_total = $row->total;
        $endpoint =$row->endpoint; 
        $ofc = $row->ofc;
        $ofc_edc = $row->ofc_edc;
        $non_authen = $row->non_authen;  
        $non_hmain = $row->non_hmain;  
        $uc_anywhere = $row->uc_anywhere;
        $uc_anywhere_endpoint = $row->uc_anywhere_endpoint;
        $uc_cr = $row->uc_cr;
        $uc_cr_endpoint = $row->uc_cr_endpoint; 
        $uc_herb = $row->uc_herb;
        $uc_herb_endpoint = $row->uc_herb_endpoint; 
        $uc_healthmed = $row->uc_healthmed;  
        $uc_healthmed_endpoint = $row->uc_healthmed_endpoint; 
        $ppfs = $row->ppfs;
        $ppfs_endpoint = $row->ppfs_endpoint; 
    }

    $ipd_admit_homeward = DB::connection('hosxp')->select('
        SELECT COUNT(DISTINCT o.an) AS homeward,COUNT(ep.claimCode) AS endpoint
        FROM ovst o INNER JOIN ipt i ON i.an = o.an 
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimType = "PG0140001"
        WHERE o.vstdate = DATE(NOW())
        AND i.ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")');
        foreach($ipd_admit_homeward as $row){
            $admit_homeward = $row->homeward;
            $admit_homeward_endpoint = $row->endpoint;
        }

    $sql=DB::connection('hosxp')->select('
        SELECT COUNT(DISTINCT an) AS admit_now,
        IFNULL(SUM(CASE WHEN ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_m = "Y")  
			THEN 1 ELSE 0 END),0) AS "ward_m",
        IFNULL(SUM(CASE WHEN ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_f = "Y")  
			THEN 1 ELSE 0 END),0) AS "ward_f",
        IFNULL(SUM(CASE WHEN ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_vip = "Y")  
			THEN 1 ELSE 0 END),0) AS "ward_vip",
        IFNULL(SUM(CASE WHEN ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_lr = "Y") 
			THEN 1 ELSE 0 END),0) AS "ward_lr",
        IFNULL(SUM(CASE WHEN ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y") 
			THEN 1 ELSE 0 END),0) AS "ward_homeward"
        FROM (SELECT i.an,i.regdate,i.regtime,i.dchdate,i.dchtime,i.ward 
        FROM ipt i WHERE confirm_discharge = "N") AS a');
        foreach ($sql as $row){
            $admit_now = $row->admit_now;
            $ward_m =$row->ward_m;
            $ward_f =$row->ward_f;
            $ward_vip = $row->ward_vip;
            $ward_lr =$row->ward_lr;
            $word_homeward =$row->ward_homeward;      
        } 

    $ipd_dchsummary = DB::connection('hosxp')->select('
        SELECT SUM(CASE WHEN (a.diag_text_list ="" OR a.diag_text_list IS NULL ) THEN 1 ELSE 0 END) AS non_diagtext,
        SUM(CASE WHEN (id.icd10 ="" OR id.icd10 IS NULL OR a.pdx = "" OR a.pdx IS NULL) THEN 1 ELSE 0 END) AS non_icd10
        FROM ipt i
        LEFT JOIN iptdiag id ON id.an = i.an AND id.diagtype = 1
		LEFT JOIN an_stat a ON a.an=i.an
        WHERE i.dchdate >= ? AND  i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y") 
        AND (a.diag_text_list ="" OR a.diag_text_list IS NULL 				
		OR id.icd10 ="" OR id.icd10 IS NULL
		OR a.pdx = "" OR a.pdx IS NULL)',[$start_date]);        
        foreach ($ipd_dchsummary as $row){ 
            $non_diagtext=$row->non_diagtext;
            $non_icd10=$row->non_icd10;
        }
    $ipd_paid_money = DB::connection('hosxp')->select('
        SELECT SUM(CASE WHEN (opd_wait_money <> "0") THEN 1 ELSE 0 END) AS not_transfer,
        SUM(CASE WHEN wait_paid_money <> "0" THEN 1 ELSE 0 END) AS wait_paid_money,
        SUM(wait_paid_money) AS sum_wait_paid_money
        FROM (SELECT i.hn,i.an,i.finance_transfer,a.opd_wait_money,a.item_money,a.uc_money-a.debt_money AS wait_debt_money,
        a.paid_money,a.rcpt_money,a.paid_money-a.rcpt_money AS wait_paid_money
        FROM ipt i 
        LEFT JOIN an_stat a ON a.an=i.an   
        WHERE i.confirm_discharge = "N"  AND (a.opd_wait_money <>"0" 
        OR a.paid_money-a.rcpt_money <>"0" ) GROUP BY i.an 
        ORDER BY a.opd_wait_money DESC,i.ward,wait_paid_money DESC) AS a');         

    foreach ($ipd_paid_money as $row){ 
        $not_transfer=$row->not_transfer;
        $wait_paid_money=$row->wait_paid_money;
        $sum_wait_paid_money=$row->sum_wait_paid_money;
    }
    $bed_qty = DB::table('main_setting')->where('name','bed_qty')->value('value'); 
    $ip_all = DB::connection('hosxp')->select('
        SELECT CASE WHEN MONTH(i.dchdate)="10" THEN CONCAT("ต.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="11" THEN CONCAT("พ.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="12" THEN CONCAT("ธ.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="1" THEN CONCAT("ม.ค ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="2" THEN CONCAT("ก.พ. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="3" THEN CONCAT("มี.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="4" THEN CONCAT("เม.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="5" THEN CONCAT("พ.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="6" THEN CONCAT("มิ.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="7" THEN CONCAT("ก.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="8" THEN CONCAT("ส.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="9" THEN CONCAT("ก.ย. ",YEAR(i.dchdate)+543)
        END AS "month",COUNT(DISTINCT i.an) AS an ,sum(a.admdate) AS admdate,        
        ROUND((SUM(a.admdate)*100)/(?*DAY(LAST_DAY(i.dchdate))),2) AS "bed_occupancy",
        ROUND(((SUM(a.admdate)*100)/(?*DAY(LAST_DAY(a.dchdate)))*?)/100,2) AS "active_bed",
		ROUND(SUM(i.adjrw)/COUNT(DISTINCT i.an),2) AS cmi,
        ROUND(SUM(i.adjrw),2) AS adjrw ,SUM(a.income-a.rcpt_money)/SUM(i.adjrw) AS "income_rw"  
        FROM an_stat a INNER JOIN ipt i ON a.an=i.an
        WHERE i.dchdate BETWEEN ? AND DATE(NOW())
        AND a.pdx NOT IN ("Z290","Z208")
        GROUP BY MONTH(i.dchdate)
        ORDER BY YEAR(i.dchdate) , MONTH(i.dchdate)',[$bed_qty,$bed_qty,$bed_qty,$start_date]);

    $ip_normal = DB::connection('hosxp')->select('
        SELECT CASE WHEN MONTH(i.dchdate)="10" THEN CONCAT("ต.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="11" THEN CONCAT("พ.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="12" THEN CONCAT("ธ.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="1" THEN CONCAT("ม.ค ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="2" THEN CONCAT("ก.พ. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="3" THEN CONCAT("มี.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="4" THEN CONCAT("เม.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="5" THEN CONCAT("พ.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="6" THEN CONCAT("มิ.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="7" THEN CONCAT("ก.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="8" THEN CONCAT("ส.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="9" THEN CONCAT("ก.ย. ",YEAR(i.dchdate)+543)
        END AS "month",COUNT(DISTINCT i.an) AS an ,sum(a.admdate) AS admdate,        
        ROUND((SUM(a.admdate)*100)/(?*DAY(LAST_DAY(i.dchdate))),2) AS "bed_occupancy",
        ROUND(((SUM(a.admdate)*100)/(?*DAY(LAST_DAY(a.dchdate)))*?)/100,2) AS "active_bed",
		ROUND(SUM(i.adjrw)/COUNT(DISTINCT i.an),2) AS cmi,
        ROUND(SUM(i.adjrw),2) AS adjrw ,SUM(a.income-a.rcpt_money)/SUM(i.adjrw) AS "income_rw"  
        FROM an_stat a INNER JOIN ipt i ON a.an=i.an
        WHERE i.dchdate BETWEEN ? AND DATE(NOW())
        AND a.pdx NOT IN ("Z290","Z208") AND i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
        GROUP BY MONTH(i.dchdate)
        ORDER BY YEAR(i.dchdate) , MONTH(i.dchdate)',[$bed_qty,$bed_qty,$bed_qty,$start_date]);

    $ip_homeward = DB::connection('hosxp')->select('
        SELECT CASE WHEN MONTH(i.dchdate)="10" THEN CONCAT("ต.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="11" THEN CONCAT("พ.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="12" THEN CONCAT("ธ.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="1" THEN CONCAT("ม.ค ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="2" THEN CONCAT("ก.พ. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="3" THEN CONCAT("มี.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="4" THEN CONCAT("เม.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="5" THEN CONCAT("พ.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="6" THEN CONCAT("มิ.ย. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="7" THEN CONCAT("ก.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="8" THEN CONCAT("ส.ค. ",YEAR(i.dchdate)+543)
        WHEN MONTH(i.dchdate)="9" THEN CONCAT("ก.ย. ",YEAR(i.dchdate)+543)
        END AS "month",COUNT(DISTINCT i.an) AS an ,sum(a.admdate) AS admdate,        
        ROUND((SUM(a.admdate)*100)/(?*DAY(LAST_DAY(i.dchdate))),2) AS "bed_occupancy",
        ROUND(((SUM(a.admdate)*100)/(?*DAY(LAST_DAY(a.dchdate)))*?)/100,2) AS "active_bed",
		ROUND(SUM(i.adjrw)/COUNT(DISTINCT i.an),2) AS cmi,
        ROUND(SUM(i.adjrw),2) AS adjrw ,SUM(a.income-a.rcpt_money)/SUM(i.adjrw) AS "income_rw"  
        FROM an_stat a INNER JOIN ipt i ON a.an=i.an
        WHERE i.dchdate BETWEEN ? AND DATE(NOW())
        AND a.pdx NOT IN ("Z290","Z208") AND i.ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
        GROUP BY MONTH(i.dchdate)
        ORDER BY YEAR(i.dchdate) , MONTH(i.dchdate)',[$bed_qty,$bed_qty,$bed_qty,$start_date]);
    $month = array_column($ip_all,'month');  
    $bed_occupancy = array_column($ip_all,'bed_occupancy');

    return view('home',compact('budget_year','opd_total','endpoint','ofc','ofc_edc','non_authen','non_hmain',
        'uc_anywhere','uc_anywhere_endpoint','uc_cr','uc_cr_endpoint','uc_herb','uc_herb_endpoint',
        'uc_healthmed','uc_healthmed_endpoint','ppfs','ppfs_endpoint','admit_homeward','admit_homeward_endpoint','non_diagtext','non_icd10','not_transfer',
        'wait_paid_money','sum_wait_paid_money','ip_all','ip_normal','ip_homeward','month','bed_occupancy','admit_now'));
}
###################################################################################################
//Create nhso_endpoint_pull
public function nhso_endpoint_pull(Request $request)
{   
    $vstdate = $request->input('vstdate') ?? now()->format('Y-m-d'); 
    $hosxp = DB::connection('hosxp')->select('
        SELECT o.vn, o.hn, pt.cid, vp.auth_code
        FROM ovst o
        INNER JOIN visit_pttype vp ON vp.vn = o.vn 
        LEFT JOIN patient pt ON pt.hn = o.hn
        WHERE o.vstdate = ?
        AND vp.auth_code NOT LIKE "EP%"
        AND vp.auth_code <> "" ', [$vstdate]);  

    $cids = array_column($hosxp, 'cid');      
    $token = DB::table('main_setting')
        ->where('name', 'token_authen_kiosk_nhso')
        ->value('value');

    foreach ($cids as $cid) {
        $response = Http::timeout(5)  // สูงสุดรอ 5 วิ ต่อ 1 request
            ->withToken($token)
            ->acceptJson()
            ->get('https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status', [
                'personalId' => $cid,
                'serviceDate' => $vstdate
            ]);

        if ($response->failed()) {
            \Log::warning("ดึงข้อมูลไม่สำเร็จสำหรับ CID: $cid", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            continue;
        }

        $result = $response->json();

        if (!isset($result['firstName']) || empty($result['serviceHistories'])) {
            continue;
        }

        $firstName = $result['firstName'];
        $lastName  = $result['lastName'];
        $mainInscl = $result['mainInscl']['id'] ?? null;
        $mainInsclName = $result['mainInscl']['name'] ?? null;
        $subInscl = $result['subInscl']['id'] ?? null;
        $subInsclName = $result['subInscl']['name'] ?? null;

        foreach ($result['serviceHistories'] as $row) {
            $serviceDateTime = $row['serviceDateTime'] ?? null;
            $sourceChannel = $row['sourceChannel'] ?? '';
            $claimCode = $row['claimCode'] ?? null;
            $claimType = $row['service']['code'] ?? null;

            if (!$claimCode) continue;

            $exists = Nhso_Endpoint::where('cid', $cid)
                ->where('claimCode', $claimCode)
                ->exists();

            if ($exists) {
                Nhso_Endpoint::where('cid', $cid)
                    ->where('claimCode', $claimCode)
                    ->update([
                        'claimType' => $claimType
                    ]);
            } elseif ($sourceChannel == 'ENDPOINT' || $claimType == 'PG0140001') {
                Nhso_Endpoint::create([
                    'cid' => $cid,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'mainInscl' => $mainInscl,
                    'mainInsclName' => $mainInsclName,
                    'subInscl' => $subInscl,
                    'subInsclName' => $subInsclName,
                    'serviceDateTime' => $serviceDateTime,
                    'sourceChannel' => $sourceChannel,
                    'claimCode' => $claimCode,
                    'claimType' => $claimType,
                ]);
            }
        }
    }
 
    return response()->json(['success' => true, 'message' => 'ดึงข้อมูลจาก สปสช สำเร็จ' ]);
}
#################################################################################################################
public function nhso_endpoint_pull_indiv(Request $request, $vstdate, $cid)
{
    $token = DB::table('main_setting')
        ->where('name', 'token_authen_kiosk_nhso')
        ->value('value');

    // ตรวจสอบ token
    if (!$token) {
        return response()->json(['error' => 'Token not found'], 500);
    }

    // ส่ง request ไปยัง NHSO API
    $response = Http::withToken($token)
        ->acceptJson()
        ->get("https://authenucws.nhso.go.th/authencodestatus/api/check-authen-status", [
            'personalId' => $cid,
            'serviceDate' => $vstdate
        ]);

    if ($response->failed()) {
        return response()->json(['error' => 'NHSO API request failed', 'message' => $response->body()], 500);
    }

    $result = $response->json();

    if (!isset($result['firstName']) || !isset($result['serviceHistories'])) {
        return response()->json(['error' => 'Invalid data from NHSO API'], 500);
    }

    $firstName = $result['firstName'];
    $lastName = $result['lastName'];
    $mainInscl = $result['mainInscl']['id'] ?? '';
    $mainInsclName = $result['mainInscl']['name'] ?? '';
    $subInscl = $result['subInscl']['id'] ?? '';
    $subInsclName = $result['subInscl']['name'] ?? '';

    $services = $result['serviceHistories'];

    foreach ($services as $row) {
        $serviceDateTime = $row['serviceDateTime'] ?? null;
        $sourceChannel = $row['sourceChannel'] ?? '';
        $claimCode = $row['claimCode'] ?? null;
        $claimType = $row['service']['code'] ?? null;

        if (!$claimCode || !$claimType) {
            continue; // ข้ามรายการที่ข้อมูลไม่ครบ
        }

        $indiv = Nhso_Endpoint::firstOrNew([
            'cid' => $cid,
            'claimCode' => $claimCode,
        ]);

        // ถ้าเป็นรายการใหม่ หรือแก้ไขได้ตามเงื่อนไข
        if (!$indiv->exists || $sourceChannel == 'ENDPOINT' || $claimType == 'PG0140001') {
            $indiv->firstName = $firstName;
            $indiv->lastName = $lastName;
            $indiv->mainInscl = $mainInscl;
            $indiv->mainInsclName = $mainInsclName;
            $indiv->subInscl = $subInscl;
            $indiv->subInsclName = $subInsclName;
            $indiv->serviceDateTime = $serviceDateTime;
            $indiv->sourceChannel = $sourceChannel;
            $indiv->claimType = $claimType;
            $indiv->save();
        }
    }

   return response()->json(['success' => true]);
   
}

##############################################################################################
public function opd_ofc(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $sql=DB::connection('hosxp')->select('
        SELECT o.vstdate,o.vsttime,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
        pt.cid,pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,v.income-v.paid_money AS debtor,
        v.pdx,IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,
        IFNULL(vp.Claim_Code,os.edc_approve_list_text) AS edc,IF(ppfs.vn <>"","Y",NULL) AS ppfs
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
		LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN ovst_seq os ON os.vn = o.vn
        LEFT JOIN vn_stat v ON v.vn = o.vn
		LEFT JOIN opitemrece ppfs ON ppfs.vn=o.vn AND ppfs.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y")
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
        WHERE o.vstdate  BETWEEN ? AND ? AND p.hipdata_code = "OFC" AND (o.an ="" OR o.an IS NULL)
        GROUP BY o.vn ORDER BY ep.claimCode DESC ,o.vstdate,o.vsttime',[$start_date,$end_date]);

    return view('home_detail.opd_ofc',compact('start_date','end_date','sql'));
}
#################################################################################################
public function opd_non_authen(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $sql=DB::connection('hosxp')->select('
        SELECT o.vstdate,o.vsttime,o.oqueue,o.hn,p.cid,p.mobile_phone_number,p.hometel,p1.`name` AS pttype,
        vp.hospmain,k.department,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,v.age_y
        FROM ovst o
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN patient p ON p.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p1 ON p1.pttype=vp.pttype
        LEFT JOIN kskdepartment k ON k.depcode=o.main_dep
        WHERE o.vstdate BETWEEN ? AND ?        
        AND p.cid NOT LIKE "0%" AND vp.auth_code ="" 
        GROUP BY o.vn ORDER BY o.vsttime',[$start_date,$end_date]);

    return view('home_detail.opd_non_authen',compact('start_date','end_date','sql'));
}
##############################################################################################
public function opd_non_hospmain(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $sql=DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        o.vstdate,o.vsttime,o.oqueue,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
        pt.cid,pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain        
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        WHERE o.vstdate BETWEEN ? AND ?
        AND p.hipdata_code IN ("UCS","SSS","STP") AND (vp.hospmain="" OR vp.hospmain IS NULL)
        GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);

    return view('home_detail.opd_non_hospmain',compact('start_date','end_date','sql'));
}
##############################################################################################
public function opd_ucs_anywhere(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $search=DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        et.ucae AS er,GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,vp.nhso_ucae_type_code AS ae,
        rep.rep_eclaim_detail_nhso AS rep_nhso,rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_total,stm.repno        
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN er_regist e ON e.vn=o.vn 
        LEFT JOIN er_pt_type et ON et.er_pt_type=e.er_pt_type AND et.ucae IN ("A","E")        
        LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
            IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
        LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode       
        LEFT JOIN vn_stat v ON v.vn = o.vn
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND DATE(stm.datetimeadm) = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"     
        WHERE (o.an ="" OR o.an IS NULL) AND o.vstdate BETWEEN ? AND ?
        AND p.hipdata_code = "UCS" AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")
        GROUP BY o.vn ORDER BY o.vstdate,o.vsttime',[$start_date,$end_date]);    

    return view('home_detail.opd_ucs_anywhere',compact('start_date','end_date','search'));
}
##############################################################################################
public function opd_ucs_cr(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $search=DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        GROUP_CONCAT(DISTINCT s.`name`) AS claim_list,COALESCE(uc_cr.claim_price, 0) AS claim_price,
        GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,rep.rep_eclaim_detail_nhso AS rep_nhso,
        rep.rep_eclaim_detail_error_code AS rep_error, stm.receive_inst+stm.receive_op+stm.receive_palliative
            +stm.receive_dmis_drug+stm.receive_hc_drug+stm.receive_hc_hc AS receive_total,stm.repno 
        FROM ovst o    
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
            ON o1.icode = li.icode AND li.uc_cr = "Y" 
        LEFT JOIN s_drugitems s ON s.icode = o1.icode
        LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
        INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
            WHERE op.vstdate BETWEEN ? AND ? AND li.uc_cr = "Y" GROUP BY op.vn) uc_cr ON uc_cr.vn=o.vn
        LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
            IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
        LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND DATE(stm.datetimeadm) = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"       
        WHERE p.hipdata_code = "UCS" AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
        AND (o.an IS NULL OR o.an ="") AND o1.vn IS NOT NULL AND o.vstdate BETWEEN ? AND ?
        GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]);

    return view('home_detail.opd_ucs_cr',compact('start_date','end_date','search'));
}
##############################################################################################
public function opd_ucs_herb(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $search=DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        GROUP_CONCAT(DISTINCT s.`name`) AS claim_list,COALESCE(herb.claim_price, 0) AS claim_price,
        GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,rep.rep_eclaim_detail_nhso AS rep_nhso,
        rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_hc_hc AS receive_total,stm.repno 
        FROM ovst o    
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
            ON o1.icode = li.icode AND li.herb32 = "Y" 
        LEFT JOIN s_drugitems s ON s.icode = o1.icode
        LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
        INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
            WHERE op.vstdate BETWEEN ? AND ? AND li.herb32 = "Y" GROUP BY op.vn) herb ON herb.vn=o.vn
        LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
            IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
        LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND DATE(stm.datetimeadm) = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"       
        WHERE p.hipdata_code = "UCS" AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y") 
        AND (o.an IS NULL OR o.an ="") AND o1.vn IS NOT NULL AND o.vstdate BETWEEN ? AND ?
        GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]);    

    return view('home_detail.opd_ucs_herb',compact('start_date','end_date','search'));
}
##############################################################################################
public function opd_ucs_healthmed(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $search=DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.vstdate,o.vsttime,
        o.oqueue,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.income-v.paid_money AS debtor,k.department ,
			GROUP_CONCAT(DISTINCT healthmed.health_med_operation) AS operation
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN kskdepartment k ON k.depcode = o.cur_dep				
        LEFT JOIN vn_stat v ON v.vn = o.vn
        LEFT JOIN (SELECT h.vn,CONCAT(h2.health_med_operation_item_name," [",h2.icd10tm,"]") AS health_med_operation 
            FROM health_med_service h
            LEFT JOIN health_med_service_operation h1 ON h1.health_med_service_id=h.health_med_service_id
            LEFT JOIN health_med_operation_item h2 ON h2.health_med_operation_item_id=h1.health_med_operation_item_id
            WHERE h.service_date BETWEEN ? AND ?
            GROUP BY h1.health_med_service_id,h1.health_med_operation_item_id) healthmed ON healthmed.vn=o.vn
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=pt.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"
        WHERE (o.an ="" OR o.an IS NULL) AND healthmed.vn <>"" AND o.vstdate BETWEEN ? AND ?
        AND p.hipdata_code = "UCS" AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province ="Y")          
        GROUP BY o.vn ORDER BY ep.claimCode DESC,o.vstdate,o.vsttime',[$start_date,$end_date,$start_date,$end_date]);

    return view('home_detail.opd_ucs_healthmed',compact('start_date','end_date','search'));
}

##############################################################################################
public function opd_ppfs(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $search=DB::connection('hosxp')->select('
        SELECT IF((vp.auth_code IS NOT NULL OR vp.auth_code <> ""),"Y",NULL) AS auth_code,
        IF((vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%"),"Y",NULL) AS endpoint,o.oqueue,
        o.vstdate,o.vsttime,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,pt.cid,pt.mobile_phone_number,
        p.`name` AS pttype,vp.hospmain,v.pdx,v.income,v.rcpt_money,v.income-v.paid_money AS debtor,
        GROUP_CONCAT(DISTINCT s.`name`) AS claim_list,COALESCE(ppfs.claim_price, 0) AS claim_price,
        GROUP_CONCAT(DISTINCT n_proj.nhso_adp_code) AS project,rep.rep_eclaim_detail_nhso AS rep_nhso,
        rep.rep_eclaim_detail_error_code AS rep_error,stm.receive_pp AS receive_total,stm.repno
        FROM ovst o    
        LEFT JOIN patient pt ON pt.hn=o.hn
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p ON p.pttype=vp.pttype
        LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode JOIN hrims.lookup_icode li 
            ON o1.icode = li.icode AND li.ppfs = "Y" 
        LEFT JOIN s_drugitems s ON s.icode = o1.icode
        LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
        INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
            WHERE op.vstdate BETWEEN ? AND ? AND li.ppfs = "Y" GROUP BY op.vn) ppfs ON ppfs.vn=o.vn
        LEFT JOIN opitemrece proj ON proj.vn=o.vn AND proj.icode 
            IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("WALKIN","UCEP24"))
        LEFT JOIN nondrugitems n_proj ON n_proj.icode=proj.icode
        LEFT JOIN rep_eclaim_detail rep ON rep.vn=o.vn
        LEFT JOIN hrims.stm_ucs stm ON stm.hn=o.hn AND DATE(stm.datetimeadm) = o.vstdate AND LEFT(TIME(stm.datetimeadm),5) =LEFT(o.vsttime,5)
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimCode LIKE "EP%"       
        WHERE (o.an IS NULL OR o.an ="") AND o1.vn IS NOT NULL AND o.vstdate BETWEEN ? AND ?
        GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]);

    return view('home_detail.opd_ppfs',compact('start_date','end_date','search'));
}

##############################################################################################
public function ipd_homeward(Request $request )
{
    $start_date = $request->start_date;
    $end_date = $request->end_date;
    if($start_date == '' || $end_date == null)
    {$start_date = date('Y-m-d');}else{$start_date =$request->start_date;}
    if($end_date == '' || $end_date == null)
    {$end_date = date('Y-m-d');}else{$end_date =$request->end_date;}

    $sql=DB::connection('hosxp')->select('
        SELECT ep.claimCode,o.vstdate,o.vsttime,o.oqueue,o.hn,p.cid,p.mobile_phone_number,
        p.hometel,p1.`name` AS pttype,vp.hospmain,k.department,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,v.age_y
        FROM ovst o
        LEFT JOIN vn_stat v ON v.vn=o.vn
        LEFT JOIN patient p ON p.hn=o.hn
        LEFT JOIN visit_pttype vp ON vp.vn=o.vn
        LEFT JOIN pttype p1 ON p1.pttype=vp.pttype				
        LEFT JOIN kskdepartment k ON k.depcode=o.main_dep
		LEFT JOIN ipt i ON i.an=o.an AND i.ward IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y")
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid=v.cid AND DATE(ep.serviceDateTime)=o.vstdate AND ep.claimType = "PG0140001"
        WHERE (i.an IS NOT NULL OR i.an <>"") AND o.vstdate BETWEEN ? AND ?
		GROUP BY o.vn ORDER BY o.vsttime',[$start_date,$end_date]);

    return view('home_detail.ipd_homeward',compact('start_date','end_date','sql'));
}
##############################################################################################
public function ipd_non_dchsummary(Request $request )
{
    $budget_year_now = DB::table('budget_year')->where('DATE_END','>=',date('Y-m-d'))->where('DATE_BEGIN','<=',date('Y-m-d'))->value('LEAVE_YEAR_ID');
    $budget_year = $request->budget_year;
        if($budget_year == '' || $budget_year == null)
        {$budget_year = $budget_year_now;}else{$budget_year =$request->budget_year;} 
    $start_date =DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_BEGIN');
    $end_date = DB::table('budget_year')->where('LEAVE_YEAR_ID',$budget_year)->value('DATE_END');

    $non_dchsummary=DB::connection('hosxp')->select('
        SELECT w.`name` AS ward,i.hn,i.an,id.icd10,a.diag_text_list,d.`name` AS owner_doctor_name,
        i.dchdate,TIMESTAMPDIFF(day,i.dchdate,DATE(NOW())) AS dch_day,
        CASE WHEN (a.diag_text_list ="" OR a.diag_text_list IS NULL) THEN "รอแพทย์สรุป Chart"
        WHEN (id.icd10 ="" OR id.icd10 IS NULL) THEN "รอลงรหัสวินิจฉัยโรค" END AS diag_status
        FROM ipt i
        LEFT JOIN ward w ON w.ward=i.ward 
        LEFT JOIN iptdiag id ON id.an = i.an AND id.diagtype = 1
		LEFT JOIN ipt_doctor_diag id1 ON id1.an = i.an	AND id1.diagtype = 1 
        LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
        LEFT JOIN doctor d ON d.`code` = il.doctor
        LEFT JOIN an_stat a ON a.an=i.an
        WHERE i.dchdate >= ? AND  i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y") 
        AND (a.diag_text_list ="" OR a.diag_text_list IS NULL)
        GROUP BY i.an
        ORDER BY d.`name`,dch_day DESC',[$start_date]);  

    $non_dchsummary_sum=DB::connection('hosxp')->select('
        SELECT d.`name` AS owner_doctor_name,COUNT(i.an) AS total
        FROM ipt i     
        LEFT JOIN iptdiag ON iptdiag.an = i.an AND iptdiag.diagtype = "1"
        LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = 1 AND il.active_doctor = "Y"
        LEFT JOIN doctor d ON d.`code` = il.doctor
        LEFT JOIN an_stat a ON a.an=i.an
        WHERE i.dchdate >= ? AND  i.ward NOT IN (SELECT ward FROM hrims.lookup_ward WHERE ward_homeward = "Y") 
        AND (a.diag_text_list ="" OR a.diag_text_list IS NULL)
        GROUP BY d.`name` 
        ORDER BY total DESC',[$start_date]); 
    $owner_doctor_name = array_column($non_dchsummary_sum,'owner_doctor_name');
    $owner_doctor_total = array_column($non_dchsummary_sum,'total');
    
    return view('home_detail.ipd_non_dchsummary',compact('non_dchsummary','owner_doctor_name','owner_doctor_total'));        
}
####################################################################################################################
public function ipd_finance_chk_opd_wait_transfer(Request $request)
{      
    $finance_chk=DB::connection('hosxp')->select('
        SELECT w.`name` AS ward,i1.bedno,i.hn,i.an,i.regdate,p.`name` AS pttype,i2.hospmain,
        i.finance_transfer, a.opd_wait_money,a.item_money,a.uc_money-a.debt_money AS wait_debt_money,
        a.paid_money,a.rcpt_money,a.paid_money-a.rcpt_money AS wait_paid_money
        FROM ipt i
        LEFT JOIN ward w ON w.ward=i.ward
        LEFT JOIN iptadm i1 ON i1.an = i.an
        LEFT JOIN ipt_pttype i2 ON i2.an = i.an AND i2.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=i2.pttype
        LEFT JOIN an_stat a ON a.an=i.an
        WHERE i.confirm_discharge = "N" 
        AND a.opd_wait_money <>"0" GROUP BY i.an 
        ORDER BY a.opd_wait_money DESC,i.ward,wait_paid_money DESC');  

      return view('home_detail.ipd_finance_chk',compact('finance_chk'));        
}
public function ipd_finance_chk_wait_rcpt_money(Request $request)
{      
    $finance_chk=DB::connection('hosxp')->select('
        SELECT w.`name` AS ward,i1.bedno,i.hn,i.an,i.regdate,p.`name` AS pttype,i2.hospmain,
        i.finance_transfer,a.opd_wait_money,a.item_money,a.uc_money-a.debt_money AS wait_debt_money,
        a.paid_money,a.rcpt_money,a.paid_money-a.rcpt_money AS wait_paid_money
        FROM ipt i
        LEFT JOIN ward w ON w.ward=i.ward
        LEFT JOIN iptadm i1 ON i1.an = i.an
        LEFT JOIN ipt_pttype i2 ON i2.an = i.an AND i2.pttype_number = 1
        LEFT JOIN pttype p ON p.pttype=i2.pttype
        LEFT JOIN an_stat a ON a.an=i.an
        WHERE i.confirm_discharge = "N" 
        AND (a.paid_money-a.rcpt_money <>"0") GROUP BY i.an 
        ORDER BY a.opd_wait_money DESC,i.ward,wait_paid_money DESC');  

      return view('home_detail.ipd_finance_chk',compact('finance_chk'));        
}
    
}
