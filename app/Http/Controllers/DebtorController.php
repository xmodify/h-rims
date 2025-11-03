<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Debtor_1102050101_103;
use App\Models\Debtor_1102050101_109;
use App\Models\Debtor_1102050101_201;
use App\Models\Debtor_1102050101_202;
use App\Models\Debtor_1102050101_203;
use App\Models\Debtor_1102050101_209;
use App\Models\Debtor_1102050101_216;
use App\Models\Debtor_1102050101_217;
use App\Models\Debtor_1102050101_301;
use App\Models\Debtor_1102050101_302;
use App\Models\Debtor_1102050101_303;
use App\Models\Debtor_1102050101_304;
use App\Models\Debtor_1102050101_307;
use App\Models\Debtor_1102050101_308;
use App\Models\Debtor_1102050101_309;
use App\Models\Debtor_1102050101_310;
use App\Models\Debtor_1102050101_401;
use App\Models\Debtor_1102050101_402;
use App\Models\Debtor_1102050101_501;
use App\Models\Debtor_1102050101_502;
use App\Models\Debtor_1102050101_503;
use App\Models\Debtor_1102050101_504;
use App\Models\Debtor_1102050101_701;
use App\Models\Debtor_1102050101_702;
use App\Models\Debtor_1102050101_703;
use App\Models\Debtor_1102050101_704;
use App\Models\Debtor_1102050102_106;
use App\Models\Debtor_1102050102_106_tracking;
use App\Models\Debtor_1102050102_107;
use App\Models\Debtor_1102050102_107_tracking;
use App\Models\Debtor_1102050102_108;
use App\Models\Debtor_1102050102_109;
use App\Models\Debtor_1102050102_602;
use App\Models\Debtor_1102050102_603;
use App\Models\Debtor_1102050102_801;
use App\Models\Debtor_1102050102_802;
use App\Models\Debtor_1102050102_803;
use App\Models\Debtor_1102050102_804;

class DebtorController extends Controller
{
//Check Login---------------------------------------------------------------------
    public function __construct()
    {
        $this->middleware('auth');
    }
//index---------------------------------------------------------------------------
    public function index()
    {    
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value'); 

        return view('debtor.index', compact('hospital_name','hospital_code'));
    }
//_check_income---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- 
    public function _check_income(Request $request )
    {
        $start_date = $request->start_date ?: date('Y-m-d', strtotime("first day of this month"));
        $end_date = $request->end_date ?: date('Y-m-d', strtotime("last day of this month"));   

        $check_income = DB::connection('hosxp')->select('
            SELECT (SELECT SUM(income) FROM vn_stat WHERE vstdate BETWEEN ? AND ?) AS vn_stat,
            (SELECT SUM(paid_money) FROM vn_stat WHERE vstdate BETWEEN ? AND ?) AS vn_stat_paid,
            (SELECT SUM(sum_price) FROM opitemrece WHERE vstdate BETWEEN ? AND ? AND (an IS NULL OR an ="")) AS opitemrece,
            (SELECT SUM(sum_price) FROM opitemrece WHERE vstdate BETWEEN ? AND ? AND (an IS NULL OR an ="") AND paidst IN ("01","03")) AS opitemrece_paid,
            IF((SELECT SUM(income) FROM vn_stat WHERE vstdate BETWEEN ? AND ?)<>(SELECT SUM(sum_price) FROM opitemrece WHERE vstdate BETWEEN ? AND ? AND (an IS NULL OR an =""))
            ,"Resync VN","Success") AS status_check',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);

        $check_income_ipd = DB::connection('hosxp')->select('
            SELECT (SELECT SUM(income) FROM an_stat WHERE dchdate BETWEEN ? AND ?) AS an_stat,
            (SELECT SUM(paid_money) FROM an_stat WHERE dchdate BETWEEN ? AND ?) AS an_stat_paid,
            (SELECT SUM(sum_price) FROM opitemrece o ,ipt i WHERE o.an = i.an AND i.dchdate BETWEEN ? AND ?) AS opitemrece,
            (SELECT SUM(sum_price) FROM opitemrece o ,ipt i WHERE o.an = i.an AND i.dchdate BETWEEN ? AND ? AND paidst IN ("01","03")) AS opitemrece_paid,
            IF((SELECT SUM(income) FROM an_stat WHERE dchdate BETWEEN ? AND ?)<>(SELECT  SUM(sum_price) FROM opitemrece o ,ipt i WHERE o.an = i.an AND i.dchdate BETWEEN ? AND ?)
            ,"Resync AN","Success") AS status_check',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]);  

        return view('debtor._check_income',compact('start_date','end_date','check_income','check_income_ipd'));
    }
//_summary-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    public function _summary(Request $request )
        {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');

        $_1102050101_103 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_103 
            WHERE vstdate BETWEEN ? AND ? ',[$start_date,$end_date]);
        $_1102050101_109 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_109 
            WHERE vstdate BETWEEN ? AND ? ',[$start_date,$end_date]);
        $_1102050101_201 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_201 
            WHERE vstdate BETWEEN ? AND ? ',[$start_date,$end_date]);
        $_1102050101_203 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_203
            WHERE vstdate BETWEEN ? AND ? ',[$start_date,$end_date]);
        $_1102050101_209 = DB::select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,IFNULL(SUM(s.receive_pp),0) AS receive
            FROM debtor_1102050101_209 d 
            LEFT JOIN stm_ucs s ON s.cid=d.cid AND s.vstdate = d.vstdate 
            AND LEFT(s.vsttime,5) =LEFT(d.vsttime,5) 
            WHERE d.vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_216 = DB::select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,SUM(IFNULL(s.receive_total,sk.receive_total)) AS receive
            FROM debtor_1102050101_216 d 
            LEFT JOIN stm_ucs s ON s.cid=d.cid AND s.vstdate = d.vstdate 
            AND LEFT(s.vsttime,5) =LEFT(d.vsttime,5) 
            LEFT JOIN (SELECT cid,datetimeadm AS vstdate,sum(receive_total) AS receive_total FROM stm_ucs_kidney GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.vstdate = d.vstdate 
            WHERE d.vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_301 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_301 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_303 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_303 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_307 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_307 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_309 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn, SUM(debtor) AS debtor,IFNULL(SUM(s.amount+s.epopay+s.epoadm),0) AS receive
            FROM debtor_1102050101_309 d 
            LEFT JOIN stm_sss_kidney s ON s.cid=d.cid AND s.vstdate = d.vstdate 
            WHERE d.vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_401 = DB::select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,
            SUM(IFNULL(d.receive,0))+SUM(IFNULL(s.receive_total,0)+IFNULL(sk.amount,0)) AS receive
            FROM debtor_1102050101_401 d 
            LEFT JOIN stm_ofc s ON s.hn=d.hn AND s.vstdate = d.vstdate	AND LEFT(s.vsttime,5) =LEFT(d.vsttime,5)
            LEFT JOIN stm_ofc_kidney sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate AND d.kidney IS NOT NULL 
            WHERE d.vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_501 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_501 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_503 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_503 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_701 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_701
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_702 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_702
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_703 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050101_703 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_106 = DB::connection('hosxp')->select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(debtor) AS debtor,SUM(IFNULL(d.receive,r.bill_amount)) AS receive
            FROM hrims.debtor_1102050102_106 d 
            LEFT JOIN rcpt_print r ON r.vn = d.vn AND r.`status` ="OK" AND r.department="OPD" 
            WHERE d.vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_108 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050102_108 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_602 = DB::select('
            SELECT COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive),0) AS receive
            FROM debtor_1102050102_602 
            WHERE vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_801 = DB::select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,SUM(IFNULL(s.compensate_treatment,0)+IFNULL(sk.compensate_kidney,0)) AS receive
            FROM debtor_1102050102_801 d   
                LEFT JOIN stm_lgo s ON s.hn=d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) =LEFT(d.vsttime,5)
            LEFT JOIN stm_lgo_kidney sk ON sk.hn=d.hn AND sk.datetimeadm = d.vstdate AND d.kidney IS NOT NULL
            WHERE d.vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_803 = DB::select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(d.debtor) AS debtor,SUM(IFNULL(s.receive_total,0)+IFNULL(sk.amount,0)) AS receive
            FROM debtor_1102050102_803 d   
                LEFT JOIN stm_ofc s ON s.hn=d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime,5) =LEFT(d.vsttime,5)
            LEFT JOIN stm_ofc_kidney sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate AND d.kidney IS NOT NULL
            WHERE d.vstdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_202 = DB::select('
            SELECT COUNT(an) AS anvn,SUM(debtor) AS debtor,IFNULL(SUM(receive_ip_compensate_pay),0) AS receive
            FROM (SELECT d.an,d.debtor,stm.receive_ip_compensate_pay FROM debtor_1102050101_202 d
            LEFT JOIN stm_ucs stm ON stm.an=d.an    
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a',[$start_date,$end_date]);
        $_1102050101_217 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM (SELECT d.dchdate,d.an,d.debtor,(s.receive_total-s.receive_ip_compensate_pay)+IFNULL(SUM(sk.receive_total),0) AS receive
            FROM debtor_1102050101_217 d
            LEFT JOIN stm_ucs s ON s.an=d.an
            LEFT JOIN stm_ucs_kidney sk ON sk.cid=d.cid AND sk.datetimeadm BETWEEN d.regdate AND d.dchdate
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a ',[$start_date,$end_date]);
        $_1102050101_302 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_302    
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_304 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_304    
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_308 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_308    
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_310 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_310    
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_402 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive_total) AS receive
            FROM (SELECT d.*,s.receive_total+IFNULL(SUM(sk.amount),0) AS receive_total 
            FROM debtor_1102050101_402 d    
            LEFT JOIN stm_ofc s ON s.an=d.an
            LEFT JOIN stm_ofc_kidney sk ON sk.hn=d.hn AND sk.vstdate BETWEEN d.regdate AND d.dchdate
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a ' ,[$start_date,$end_date]);
        $_1102050101_502 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_502    
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_504 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_504    
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050101_704 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_704    
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_107 = DB::connection('hosxp')->select('
            SELECT COUNT(DISTINCT d.vn) AS anvn,SUM(debtor) AS debtor,SUM(IFNULL(d.receive,r.bill_amount)) AS receive
            FROM hrims.debtor_1102050102_107 d
            LEFT JOIN rcpt_print r ON r.vn = d.vn AND r.`status` ="OK" AND r.department="OPD"
            WHERE d.dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_109 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_109   
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_603 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_603  
            WHERE dchdate BETWEEN ? AND ?',[$start_date,$end_date]);
        $_1102050102_802 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive_total) AS receive
            FROM (SELECT d.*,s.compensate_treatment+IFNULL(SUM(sk.compensate_kidney),0) AS receive_total 
            FROM debtor_1102050102_802 d    
            LEFT JOIN stm_lgo s ON s.an=d.an
            LEFT JOIN stm_lgo_kidney sk ON sk.cid=d.cid AND sk.datetimeadm BETWEEN d.regdate AND d.dchdate
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an ) AS a' ,[$start_date,$end_date]);
        $_1102050102_804 = DB::select('
            SELECT COUNT(DISTINCT an) AS anvn, SUM(debtor) AS debtor,SUM(receive_total) AS receive
                FROM (SELECT d.*,s.receive_total
            FROM debtor_1102050102_804 d    
            LEFT JOIN stm_ofc s ON s.an=d.an  
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a ' ,[$start_date,$end_date]);

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('_1102050101_103',$_1102050101_103);
        $request->session()->put('_1102050101_109',$_1102050101_109);
        $request->session()->put('_1102050101_201',$_1102050101_201);
        $request->session()->put('_1102050101_203',$_1102050101_203);
        $request->session()->put('_1102050101_209',$_1102050101_209);
        $request->session()->put('_1102050101_216',$_1102050101_216);
        $request->session()->put('_1102050101_301',$_1102050101_301);
        $request->session()->put('_1102050101_303',$_1102050101_303);
        $request->session()->put('_1102050101_307',$_1102050101_307);
        $request->session()->put('_1102050101_309',$_1102050101_309);
        $request->session()->put('_1102050101_401',$_1102050101_401);
        $request->session()->put('_1102050101_501',$_1102050101_501);
        $request->session()->put('_1102050101_503',$_1102050101_503);
        $request->session()->put('_1102050101_701',$_1102050101_701);
        $request->session()->put('_1102050101_702',$_1102050101_702);
        $request->session()->put('_1102050101_703',$_1102050101_703);
        $request->session()->put('_1102050102_106',$_1102050102_106);
        $request->session()->put('_1102050102_108',$_1102050102_108);
        $request->session()->put('_1102050102_602',$_1102050102_602);
        $request->session()->put('_1102050102_801',$_1102050102_801);
        $request->session()->put('_1102050102_803',$_1102050102_803);
        $request->session()->put('_1102050101_202',$_1102050101_202);
        $request->session()->put('_1102050101_217',$_1102050101_217);
        $request->session()->put('_1102050101_302',$_1102050101_302);
        $request->session()->put('_1102050101_304',$_1102050101_304);
        $request->session()->put('_1102050101_308',$_1102050101_308);
        $request->session()->put('_1102050101_310',$_1102050101_310);
        $request->session()->put('_1102050101_402',$_1102050101_402);
        $request->session()->put('_1102050101_502',$_1102050101_502);
        $request->session()->put('_1102050101_504',$_1102050101_504);
        $request->session()->put('_1102050101_704',$_1102050101_704);
        $request->session()->put('_1102050102_107',$_1102050102_107);
        $request->session()->put('_1102050102_109',$_1102050102_109);
        $request->session()->put('_1102050102_603',$_1102050102_603);
        $request->session()->put('_1102050102_802',$_1102050102_802);
        $request->session()->put('_1102050102_804',$_1102050102_804);
        $request->session()->save();

        return view('debtor._summary',compact('start_date','end_date','_1102050101_103','_1102050101_109','_1102050101_201','_1102050101_203',
            '_1102050101_209','_1102050101_216','_1102050101_301','_1102050101_303','_1102050101_307','_1102050101_309','_1102050101_401',
            '_1102050101_501','_1102050101_503','_1102050101_701','_1102050101_702','_1102050101_703','_1102050102_106','_1102050102_108','_1102050102_602',
            '_1102050102_801','_1102050102_803','_1102050101_202','_1102050101_217','_1102050101_302','_1102050101_304','_1102050101_308','_1102050101_310',
            '_1102050101_402','_1102050101_502','_1102050101_504','_1102050101_704','_1102050102_107','_1102050102_109','_1102050102_603','_1102050102_802','_1102050102_804'));
    }
//_summary_pdf--------------------------------------------------------------------------------------------------------------------------------------------------
    public function _summary_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');     
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $_1102050101_103 = Session::get('_1102050101_103');
        $_1102050101_109 = Session::get('_1102050101_109');
        $_1102050101_201 = Session::get('_1102050101_201');
        $_1102050101_203 = Session::get('_1102050101_203');
        $_1102050101_209 = Session::get('_1102050101_209');
        $_1102050101_216 = Session::get('_1102050101_216');
        $_1102050101_301 = Session::get('_1102050101_301');
        $_1102050101_303 = Session::get('_1102050101_303');
        $_1102050101_307 = Session::get('_1102050101_307');
        $_1102050101_309 = Session::get('_1102050101_309');
        $_1102050101_401 = Session::get('_1102050101_401');
        $_1102050101_501 = Session::get('_1102050101_501');
        $_1102050101_503 = Session::get('_1102050101_503');
        $_1102050101_701 = Session::get('_1102050101_701');
        $_1102050101_702 = Session::get('_1102050101_702');
        $_1102050101_703 = Session::get('_1102050101_703');
        $_1102050102_106 = Session::get('_1102050102_106');
        $_1102050102_108 = Session::get('_1102050102_108');
        $_1102050102_602 = Session::get('_1102050102_602');
        $_1102050102_801 = Session::get('_1102050102_801');
        $_1102050102_803 = Session::get('_1102050102_803');
        $_1102050101_202 = Session::get('_1102050101_202');
        $_1102050101_217 = Session::get('_1102050101_217');
        $_1102050101_302 = Session::get('_1102050101_302');
        $_1102050101_304 = Session::get('_1102050101_304');
        $_1102050101_308 = Session::get('_1102050101_308');
        $_1102050101_310 = Session::get('_1102050101_310');
        $_1102050101_402 = Session::get('_1102050101_402');
        $_1102050101_502 = Session::get('_1102050101_502');
        $_1102050101_504 = Session::get('_1102050101_504');
        $_1102050101_704 = Session::get('_1102050101_704');
        $_1102050102_107 = Session::get('_1102050102_107');
        $_1102050102_109 = Session::get('_1102050102_109');
        $_1102050102_603 = Session::get('_1102050102_603');
        $_1102050102_802 = Session::get('_1102050102_802');
        $_1102050102_804 = Session::get('_1102050102_804');
        $pdf = PDF::loadView('debtor._summary_pdf',compact('hospital_name','hospital_code','start_date','end_date','_1102050101_103','_1102050101_109','_1102050101_201',
        '_1102050101_203','_1102050101_209','_1102050101_216','_1102050101_301','_1102050101_303','_1102050101_307','_1102050101_309','_1102050101_401',
        '_1102050101_501','_1102050101_503','_1102050101_701','_1102050101_702','_1102050101_703','_1102050102_106','_1102050102_108','_1102050102_602',
        '_1102050102_801','_1102050102_803','_1102050101_202','_1102050101_217','_1102050101_302','_1102050101_304','_1102050101_308','_1102050101_310',
        '_1102050101_402','_1102050101_502','_1102050101_504','_1102050101_704','_1102050102_107','_1102050102_109','_1102050102_603','_1102050102_802','_1102050102_804'))
                    ->setPaper('A4', 'landscape');
        return @$pdf->stream();
    }
##############################################################################################################################################################
//_1102050101_103--------------------------------------------------------------------------------------------------------------
    public function _1102050101_103(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
       

        $debtor =  Debtor_1102050101_103::whereBetween('vstdate', [$start_date,$end_date])
                    ->where(function ($query) use ($search){
                        $query->where('ptname','like','%'.$search.'%');
                        $query->orwhere('hn','like','%'.$search.'%');
                        })
                    ->orderBy('vstdate')
                    ->get()
                    ->map(function ($item) {                        
                        if (($item->receive - $item->debtor) >= 0) {
                            $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                        } else {
                            $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                        }
                        return $item;
                    });

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money AS debtor ,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype    
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <>"0" 
                AND vp.pttype IN ('.$pttype_checkup.')
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_103 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_103',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_103_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money AS debtor ,"ยืนยันลูกหนี้" AS status   
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype    
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <>"0" 
                AND vp.pttype IN ('.$pttype_checkup.')
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_103::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_103_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_103::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_103_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_103::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_103_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_103_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value'); 
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_103  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_103_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_103_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_103_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_103_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_109--------------------------------------------------------------------------------------------------------------
    public function _1102050101_109(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');              

        $debtor =  Debtor_1102050101_109::whereBetween('vstdate', [$start_date,$end_date])
                    ->where(function ($query) use ($search){
                        $query->where('ptname','like','%'.$search.'%');
                        $query->orwhere('hn','like','%'.$search.'%');
                        })
                    ->orderBy('vstdate')->get()
                    ->map(function ($item) {                        
                        if (($item->receive - $item->debtor) >= 0) {
                            $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                        } else {
                            $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                        }
                        return $item;
                    });

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,p.cid,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,o.vstdate,
                o.vsttime,p1.`name` AS pttype,vp.hospmain,p1.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(ems.claim_price, 0) AS debtor ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient p ON p.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p1 ON p1.pttype=vp.pttype
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.ems = "Y"	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
				WHERE op.vstdate BETWEEN ? AND ? AND li.ems = "Y" GROUP BY op.vn) ems ON ems.vn=o.vn			
            WHERE o.vstdate BETWEEN ? AND ?            
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_109 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_109',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_109_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,p.cid,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,o.vstdate,
                o.vsttime,p1.`name` AS pttype,vp.hospmain,p1.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(ems.claim_price, 0) AS debtor ,GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient p ON p.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p1 ON p1.pttype=vp.pttype
			INNER JOIN opitemrece o1 ON o1.vn=o.vn
			INNER JOIN hrims.lookup_icode li ON o1.icode = li.icode AND li.ems = "Y"	
			LEFT JOIN s_drugitems sd ON sd.icode=o1.icode
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode
				WHERE op.vstdate BETWEEN ? AND ? AND li.ems = "Y" GROUP BY op.vn) ems ON ems.vn=o.vn			
            WHERE o.vstdate BETWEEN ? AND ?            
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_109::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_109_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_109::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_109_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_109::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_109_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_109_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value'); 
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_109  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_109_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_109_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_109_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_109_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_201--------------------------------------------------------------------------------------------------------------
    public function _1102050101_201(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');     

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock
                FROM debtor_1102050101_201 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock
                FROM debtor_1102050101_201 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
                v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
                GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.ppfs ="" OR li.ppfs IS NULL)
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (ppfs IS NULL OR ppfs =""))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"					
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS" 					
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_201 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_201',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_201_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_201_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');  
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.ppfs ="" OR li.ppfs IS NULL)
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (ppfs IS NULL OR ppfs =""))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"					
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS" 					
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_201::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_201_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_201_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_201::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_201_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_201_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_201  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_201_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_201_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_201_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_201_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_203--------------------------------------------------------------------------------------------------------------
    public function _1102050101_203(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');              

        $debtor =  Debtor_1102050101_203::whereBetween('vstdate', [$start_date,$end_date])
                    ->where(function ($query) use ($search){
                        $query->where('ptname','like','%'.$search.'%');
                        $query->orwhere('hn','like','%'.$search.'%');
                        })
                    ->orderBy('vstdate')->get();
        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.charge,d.charge_date,d.charge_no,d.receive,d.receive_date,
					d.receive_no,d.repno,s.receive_pp,s.repno AS repno_pp,d.status,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive,0) - IFNULL(d.debtor,0)) >= 0 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_203 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.charge,d.charge_date,d.charge_no,d.receive,d.receive_date,
					d.receive_no,d.repno,s.receive_pp,s.repno AS repno_pp,d.status,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive,0) - IFNULL(d.debtor,0)) >= 0 THEN 0 
                    ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_203 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.ppfs ="" OR li.ppfs IS NULL)
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (ppfs IS NULL OR ppfs =""))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"					
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS" 					
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs ="")) 
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_203 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_203',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_203_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.ppfs ="" OR li.ppfs IS NULL)
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (ppfs IS NULL OR ppfs =""))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"					
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS" 					
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y"	AND (hmain_ucs IS NULL OR hmain_ucs ="")) 
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_203::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_203_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_203::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_203_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_203::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_203_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_203_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_203  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_203_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_203_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_203_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_203_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_209--------------------------------------------------------------------------------------------------------------
    public function _1102050101_209(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        
        if ($search) {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.ptname, d.hipdata_code, d.pttype, d.hospmain,d.pdx, d.income,  
                    d.rcpt_money, d.ppfs, d.pp, d.other, d.debtor,s.receive_pp AS receive, s.repno, d.status, d.debtor_lock
                FROM debtor_1102050101_209 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.ptname, d.hipdata_code, d.pttype, d.hospmain, d.pdx,d.income,
                     d.rcpt_money, d.ppfs, d.pp, d.other,d.debtor,s.receive_pp AS receive, s.repno, d.status, d.debtor_lock
                FROM debtor_1102050101_209 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.ppfs ="" OR li.ppfs IS NULL)
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (ppfs IS NULL OR ppfs =""))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode			
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <> "0" 
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <> "0" 
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code NOT IN ("OFC","LGO")	
                AND vp.pttype NOT IN ('.$pttype_checkup.')               
                AND v.pdx IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_209 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_209',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_209_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_209_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.ppfs ="" OR li.ppfs IS NULL)
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (ppfs IS NULL OR ppfs =""))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode		
            WHERE (o.an IS NULL OR o.an ="") 
                AND v.income-v.rcpt_money <> "0"
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <> "0" 
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code NOT IN ("OFC","LGO")	
                AND vp.pttype NOT IN ('.$pttype_checkup.')
                AND v.pdx IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_209::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_209_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_209_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_209::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_209_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_209_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT d.vstdate,COUNT(DISTINCT d.vn) AS anvn,
            SUM(d.debtor) AS debtor,SUM(s.receive_pp) AS receive
            FROM debtor_1102050101_209 d   
            LEFT JOIN stm_ucs s ON s.cid=d.cid AND s.vstdate = d.vstdate
            AND LEFT(s.vsttime,5) =LEFT(d.vsttime,5)
            WHERE d.vstdate BETWEEN ? AND ?
            GROUP BY d.vstdate ORDER BY d.vstdate ',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_209_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_209_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_209_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_209_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_216--------------------------------------------------------------------------------------------------------------
    public function _1102050101_216(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        
        if ($search) {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.ptname, d.hipdata_code, d.pttype, d.hospmain,d.pdx, d.income,  
                    d.rcpt_money, d.kidney, d.cr,d.anywhere, d.debtor,IFNULL(s.receive_total,sk.receive_total) AS receive,
                    IFNULL(s.repno,sk.repno) AS repno, d.status, d.debtor_lock,
                    CASE WHEN (IFNULL(IFNULL(s.receive_total, sk.receive_total), 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_216 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,sum(receive_total) AS receive_total,repno
                    FROM stm_ucs_kidney GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.vstdate = d.vstdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search,$search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.ptname, d.hipdata_code, d.pttype, d.hospmain,d.pdx, d.income,  
                    d.rcpt_money, d.kidney, d.cr,d.anywhere, d.debtor,IFNULL(s.receive_total,sk.receive_total) AS receive,
                    IFNULL(s.repno,sk.repno) AS repno, d.status, d.debtor_lock,
                    CASE WHEN (IFNULL(IFNULL(s.receive_total, sk.receive_total), 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_216 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                LEFT JOIN (SELECT cid,datetimeadm AS vstdate,sum(receive_total) AS receive_total,repno
                    FROM stm_ucs_kidney GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date,$end_date]);
        }

        $debtor_search_kidney = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money-COALESCE(o1.claim_price, 0) AS other,COALESCE(o1.claim_price, 0) AS debtor,
                GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn			
			LEFT JOIN opitemrece o2 ON o2.vn=o.vn AND o2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o2.icode	
            WHERE (o.an IS NULL OR o.an ="")                
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS"
				AND o1.vn IS NOT NULL 
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 

        $debtor_search_cr = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money-COALESCE(o1.claim_price, 0) AS other,COALESCE(o1.claim_price, 0) AS debtor,
                GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,"ยืนยันลูกหนี้" AS status,
                IF(oe.moph_finance_upload_status IS NOT NULL,"Y","")  AS send_claim   
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.uc_cr ="Y" OR li.herb32 = "Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn			
			LEFT JOIN opitemrece o2 ON o2.vn=o.vn 
                AND o2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (uc_cr ="Y" OR herb32 = "Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o2.icode
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn 	
            WHERE (o.an IS NULL OR o.an ="")                                
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS"
				AND o1.vn IS NOT NULL 
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y") 
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 

        $debtor_search_anywhere = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,v.income-v.rcpt_money AS debtor,
                "ยืนยันลูกหนี้" AS status,IF(oe.moph_finance_upload_status IS NOT NULL,"Y","")  AS send_claim 
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
			    AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney="Y")
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn 
            WHERE (o.an IS NULL OR o.an ="") 
                AND v.income-v.rcpt_money <>"0"               
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS" 
                AND o1.vn IS NULL				
                AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y") 
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_216 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_216',compact('start_date','end_date','search','debtor','debtor_search_kidney',
            'debtor_search_cr','debtor_search_anywhere'));
    }
//_1102050101_216_confirm_kidney-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_confirm_kidney(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');    
        $request->validate([
        'checkbox_kidney' => 'required|array',
        ], [
            'checkbox_kidney.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_kidney'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money-COALESCE(o1.claim_price, 0) AS other,COALESCE(o1.claim_price, 0) AS debtor,
                GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn			
			LEFT JOIN opitemrece o2 ON o2.vn=o.vn AND o2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o2.icode	
            WHERE (o.an IS NULL OR o.an ="")                
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS"
				AND o1.vn IS NOT NULL 
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_216::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'kidney'          => $row->debtor,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_216_confirm_cr-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_confirm_cr(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');    
        $request->validate([
        'checkbox_cr' => 'required|array',
        ], [
            'checkbox_cr.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_cr'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money-COALESCE(o1.claim_price, 0) AS other,COALESCE(o1.claim_price, 0) AS debtor,
                GROUP_CONCAT(DISTINCT sd.`name`) AS claim_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS claim_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.uc_cr ="Y" OR li.herb32 = "Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn			
			LEFT JOIN opitemrece o2 ON o2.vn=o.vn 
                AND o2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (uc_cr ="Y" OR herb32 = "Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o2.icode	
            WHERE (o.an IS NULL OR o.an ="")                                                     
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS"
				AND o1.vn IS NOT NULL 
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")  
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_216::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'cr'              => $row->debtor,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_216_confirm_anywhere-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_confirm_anywhere(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');    
        $request->validate([
        'checkbox_anywhere' => 'required|array',
        ], [
            'checkbox_anywhere.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_anywhere'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
           SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN opitemrece o1 ON o1.vn=o.vn 
			    AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney="Y")
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"                
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "UCS" 
                AND o1.vn IS NULL 				
                AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE in_province = "Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")  
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_216::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'anywhere'        => $row->debtor,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_216_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_216::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_216_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_216_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM (SELECT d.vn, d.vstdate, d.vsttime, d.hn, d.ptname, d.hipdata_code, d.pttype, d.hospmain,d.pdx, d.income,  
            d.rcpt_money, d.kidney, d.cr,d.anywhere, d.debtor,IFNULL(s.receive_total,sk.receive_total) AS receive,
            IFNULL(s.repno,sk.repno) AS repno, d.status, d.debtor_lock
            FROM debtor_1102050101_216 d   
            LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
            LEFT JOIN (SELECT cid,datetimeadm AS vstdate,sum(receive_total) AS receive_total,repno
            FROM stm_ucs_kidney GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?) AS a GROUP BY vstdate ORDER BY vsttime ',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_216_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_216_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_216_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_216_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_301--------------------------------------------------------------------------------------------------------------
    public function _1102050101_301(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');          

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive, 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_301 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive, 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_301 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
                v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
                GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"							
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "SSS" 
                AND p.pttype NOT IN ('.$pttype_sss_fund.')					
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_301 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_301',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_301_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_301_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"			
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "SSS" 
                AND p.pttype NOT IN ('.$pttype_sss_fund.')					
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_301::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_301_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_301_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_301::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_301_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_301_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_301  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_301_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_301_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_301_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_301_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_303--------------------------------------------------------------------------------------------------------------
    public function _1102050101_303(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');          

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no, 
                    d.receive ,d.repno,s.receive_pp,IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive, 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_303 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no, 
                    d.receive ,d.repno,s.receive_pp,IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive, 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_303 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
                v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
                GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"							
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "SSS" 
                AND p.pttype NOT IN ('.$pttype_sss_fund.')					
                AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_303 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_303',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_303_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"			
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "SSS" 
                AND p.pttype NOT IN ('.$pttype_sss_fund.')					
                AND vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_303::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_303_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_303::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_303_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_303::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_303_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_303_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_303  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_303_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_303_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_303_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_303_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_307--------------------------------------------------------------------------------------------------------------
    public function _1102050101_307(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');          

        $debtor = Debtor_1102050101_307::selectRaw('*, IFNULL(vstdate, dchdate) as visit_date,IFNULL(vsttime, dchtime) as visit_time')
            ->whereBetween(DB::raw('IFNULL(vstdate, dchdate)'), [$start_date, $end_date])
            ->where(function ($query) use ($search) {
                $query->where('ptname','like','%'.$search.'%')
                    ->orWhere('hn','like','%'.$search.'%')
                    ->orWhere('an','like','%'.$search.'%');
            })
            ->orderBy(DB::raw('IFNULL(vstdate, dchdate)'))
            ->get()
            ->map(function ($item) {
                $baseDate = $item->visit_date; // ใช้ field ใหม่ที่เราสร้าง
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0;
                } else {
                    $item->days = Carbon::parse($baseDate)->diffInDays(Carbon::today());
                }
                return $item;
            });

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
                v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
                GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"							
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype IN ('.$pttype_sss_fund.')
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_307 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        $debtor_search_ip = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.pttype IN ('.$pttype_sss_fund.')
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_307 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_307',compact('start_date','end_date','search','debtor','debtor_search','debtor_search_ip'));
    }
//_1102050101_307_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"			
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype IN ('.$pttype_sss_fund.')	
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_307::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_307_confirm_ip-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_confirm_ip(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value'); 
        $request->validate([
        'checkbox_ip' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox_ip = $request->input('checkbox_ip'); // รับ array
        $checkbox_string = implode(",", $checkbox_ip); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.pttype IN ('.$pttype_sss_fund.')
            AND i.dchdate BETWEEN ? AND ?
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]);  
        
        foreach ($debtor as $row) {
            Debtor_1102050101_307::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox_ip) || !is_array($checkbox_ip)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_307_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_307::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_307_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_307::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_307_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_307_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_307  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_307_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_307_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_307_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_307_indiv_excel',compact('start_date','end_date','debtor'));
    }   
##############################################################################################################################################################
//_1102050101_309--------------------------------------------------------------------------------------------------------------
    public function _1102050101_309(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');                  

        if ($search) {
            $debtor = DB::select('
                SELECT d.vstdate,d.vsttime,d.vn,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,
                    d.pdx,d.hospmain,d.income,d.rcpt_money,d.kidney,d.debtor,
                    s.amount+s.epopay+s.epoadm AS receive,s.rid AS repno,d.debtor_lock,
                    CASE WHEN (IFNULL(s.amount+s.epopay+s.epoadm, 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                    FROM debtor_1102050101_309 d   
                LEFT JOIN stm_sss_kidney s ON s.cid=d.cid AND s.vstdate = d.vstdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vstdate,d.vsttime,d.vn,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,
                    d.pdx,d.hospmain,d.income,d.rcpt_money,d.kidney,d.debtor,
                    s.amount+s.epopay+s.epoadm AS receive,s.rid AS repno,d.debtor_lock,
                    CASE WHEN (IFNULL(s.amount+s.epopay+s.epoadm, 0) - IFNULL(d.debtor, 0)) >= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                    FROM debtor_1102050101_309 d   
                LEFT JOIN stm_sss_kidney s ON s.cid=d.cid AND s.vstdate = d.vstdate
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o1.kidney_price, 0) AS debtor,
			    GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn	
			LEFT JOIN opitemrece o2 ON o2.vn=o.vn AND o2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o2.icode	
            WHERE p.hipdata_code IN ("SSS","SSI")	
			AND o1.vn IS NOT NULL	
            AND o.vstdate BETWEEN ? AND ?
            AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_309 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_309',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_309_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o1.kidney_price, 0) AS debtor,
			    GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn	
			LEFT JOIN opitemrece o2 ON o2.vn=o.vn AND o2.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o2.icode	
            WHERE p.hipdata_code IN ("SSS","SSI")	
			AND o1.vn IS NOT NULL	
            AND o.vstdate BETWEEN ? AND ?
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_309::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'kidney'          => $row->kidney,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_309_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_309::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_309_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_309::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_309_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_309_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM (SELECT d.vstdate,d.vsttime,d.vn,d.hn,d.cid,d.ptname,d.hipdata_code,d.pttype,
            d.pdx,d.hospmain,d.income,d.rcpt_money,d.kidney,d.debtor,
            s.amount+s.epopay+s.epoadm AS receive,s.rid AS repno,d.debtor_lock
            FROM debtor_1102050101_309 d   
            LEFT JOIN stm_sss_kidney s ON s.cid=d.cid AND s.vstdate = d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?) AS a GROUP BY vstdate ORDER BY vsttime',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_309_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_309_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_309_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_309_indiv_excel',compact('start_date','end_date','debtor'));
    }   
##############################################################################################################################################################
//_1102050101_401--------------------------------------------------------------------------------------------------------------
    public function _1102050101_401(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');           

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,d.rcpt_money,  
                    d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,
                    IFNULL(s.receive_total,0) AS receive_ofc,IFNULL(sk.receive_total,0) AS receive_kidney,IFNULL(su.receive_pp,0) AS receive_ppfs,
                    IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0) AS receive,d.status,s.repno,sk.repno AS repno1,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_401 d   
                LEFT JOIN stm_ofc s ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
				LEFT JOIN (SELECT hn,vstdate,sum(amount) AS receive_total,rid AS repno FROM stm_ofc_kidney
				WHERE vstdate BETWEEN ? AND ? GROUP BY hn,vstdate) sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate
				LEFT JOIN stm_ucs su ON su.hn = d.hn AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$start_date,$end_date,$search, $search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,d.rcpt_money,  
                    d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,
                    IFNULL(s.receive_total,0) AS receive_ofc,IFNULL(sk.receive_total,0) AS receive_kidney,IFNULL(su.receive_pp,0) AS receive_ppfs,
                    IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0)AS receive,d.status,s.repno,sk.repno AS repno1,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050101_401 d   
                LEFT JOIN stm_ofc s ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
				LEFT JOIN (SELECT hn,vstdate,sum(amount) AS receive_total,rid AS repno FROM stm_ofc_kidney
				WHERE vstdate BETWEEN ? AND ? GROUP BY hn,vstdate) sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate
				LEFT JOIN stm_ucs su ON su.hn = d.hn AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date,$end_date,$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,oe.upload_datetime AS claim,								        
                v.income-v.rcpt_money-COALESCE(o1.kidney_price, 0)-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS ofc,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o2.ppfs_price, 0) AS ppfs,COALESCE(o3.other_price, 0) AS other,
                v.income-v.rcpt_money-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,
                GROUP_CONCAT(DISTINCT sd1.`name`) AS ppfs_list,GROUP_CONCAT(DISTINCT sd2.`name`) AS other_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn				
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o3 ON o3.vn=o.vn				
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o4.icode	
			LEFT JOIN opitemrece o5 ON o5.vn=o.vn AND o5.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd1 ON sd1.icode=o5.icode
			LEFT JOIN opitemrece o6 ON o6.vn=o.vn AND o6.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o6.icode
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            WHERE (o.an IS NULL OR o.an ="")
			    AND p.hipdata_code = "OFC"
                AND v.income-v.rcpt_money <>"0"		
			    AND v.income-v.rcpt_money-COALESCE(o3.other_price, 0) <>"0"				
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype NOT IN ('.$pttype_checkup.')
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_401 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_401',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_401_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,								        
                v.income-v.rcpt_money-COALESCE(o1.kidney_price, 0)-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS ofc,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o2.ppfs_price, 0) AS ppfs,COALESCE(o3.other_price, 0) AS other,
                v.income-v.rcpt_money-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,
                GROUP_CONCAT(DISTINCT sd1.`name`) AS ppfs_list,GROUP_CONCAT(DISTINCT sd2.`name`) AS other_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn				
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o3 ON o3.vn=o.vn				
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o4.icode	
			LEFT JOIN opitemrece o5 ON o5.vn=o.vn AND o5.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd1 ON sd1.icode=o5.icode
			LEFT JOIN opitemrece o6 ON o6.vn=o.vn AND o6.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o6.icode
            WHERE (o.an IS NULL OR o.an ="")
			    AND p.hipdata_code = "OFC"
                AND v.income-v.rcpt_money <>"0"		
			    AND v.income-v.rcpt_money-COALESCE(o3.other_price, 0) <>"0"				
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype NOT IN ('.$pttype_checkup.')
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_401::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'ofc'             => $row->ofc,
                'kidney'          => $row->kidney,
                'ppfs'            => $row->ppfs,
                'other'           => $row->other,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_401_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_401::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_401_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_401::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_401_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_401_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,
                SUM(IFNULL(receive,0)+IFNULL(receive_s,0)+IFNULL(receive_sk,0)) AS receive
            FROM (SELECT d.vstdate,d.vn,d.hn,d.debtor,d.receive,s.receive_total AS receive_s,sk.receive_sk ,su.receive_pp 
            FROM debtor_1102050101_401 d   
            LEFT JOIN stm_ofc s ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
			LEFT JOIN (SELECT hn,vstdate,sum(amount) AS receive_sk,rid AS repno FROM stm_ofc_kidney
				WHERE vstdate BETWEEN ? AND ? GROUP BY hn,vstdate) sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate
			LEFT JOIN stm_ucs su ON su.hn = d.hn AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
            WHERE d.vstdate BETWEEN ? AND ? GROUP BY d.vn ) AS a GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date,$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_401_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_401_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_401_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_401_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_501--------------------------------------------------------------------------------------------------------------
    public function _1102050101_501(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        

        $debtor =  Debtor_1102050101_501::whereBetween('vstdate', [$start_date,$end_date])
                    ->where(function ($query) use ($search){
                        $query->where('ptname','like','%'.$search.'%');
                        $query->orwhere('hn','like','%'.$search.'%');
                        })
                    ->orderBy('vstdate')->get()
                    ->map(function ($item) {                        
                        if (($item->receive - $item->debtor) >= 0) {
                            $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                        } else {
                            $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                        }
                        return $item;
                    }); 

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money AS debtor ,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype    
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <>"0" 
                AND p.hipdata_code = "NRH"
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_501 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_501',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_501_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money AS debtor ,"ยืนยันลูกหนี้" AS status   
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype    
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <>"0" 
                AND p.hipdata_code = "NRH"
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_501::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_501_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_501::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_501_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_501::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_501_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_501_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_501  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_501_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_501_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_501_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_501_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_503--------------------------------------------------------------------------------------------------------------
    public function _1102050101_503(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');       

        $debtor =  Debtor_1102050101_503::whereBetween('vstdate', [$start_date,$end_date])
                    ->where(function ($query) use ($search){
                        $query->where('ptname','like','%'.$search.'%');
                        $query->orwhere('hn','like','%'.$search.'%');
                        })
                    ->orderBy('vstdate')->get();

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money AS debtor ,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype    
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <>"0" 
                AND p.hipdata_code = "NRH"
                AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    OR vp.hospmain IS NULL OR vp.hospmain ="")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_503 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_503',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_503_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                v.income-v.rcpt_money AS debtor ,"ยืนยันลูกหนี้" AS status   
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype    
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <>"0" 
                AND p.hipdata_code = "NRH"
                AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    OR vp.hospmain IS NULL OR vp.hospmain ="")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_503::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_503_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_503::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_503_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_update(Request $request, $vn)
    {
        $item = Debtor_1102050101_503::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_503_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_503_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_503  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_503_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_503_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_503_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_503_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_701--------------------------------------------------------------------------------------------------------------
    public function _1102050101_701(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock
                FROM debtor_1102050101_701 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock
                FROM debtor_1102050101_701 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
                v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
                GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"							
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "STP"                 				
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")                
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_701 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_701',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_701_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_701_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"			
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "STP" 			
                AND vp.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_701::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_701_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_701_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_701::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_701_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_701_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_701  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_701_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_701_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_701_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_701_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050101_702--------------------------------------------------------------------------------------------------------------
    public function _1102050101_702(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock
                FROM debtor_1102050101_702 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime, d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,  
                    d.rcpt_money,d.other,d.ppfs,d.debtor,d.receive,d.repno,s.receive_pp,
                    IF(s.receive_pp <>"",s.repno,"") AS repno_pp,d.status,d.debtor_lock
                FROM debtor_1102050101_702 d   
                LEFT JOIN stm_ucs s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
                v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
                GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"							
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "STP"                 				
                AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    OR vp.hospmain IS NULL OR vp.hospmain ="")               
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050101_702 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_702',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_702_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_702_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
            o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
            COALESCE(o1.other_price, 0) AS other,COALESCE(o2.ppfs_price, 0) AS ppfs,
			v.income-v.rcpt_money-COALESCE(o1.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS other_list,
			GROUP_CONCAT(DISTINCT sd2.`name`) AS ppfs_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND (li.kidney ="Y" OR li.ems ="Y")
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN opitemrece o3 ON o3.vn=o.vn AND o3.icode IN (SELECT icode FROM hrims.lookup_icode WHERE (kidney ="Y" OR ems ="Y"))	
			LEFT JOIN s_drugitems sd ON sd.icode=o3.icode			
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o4.icode	
            WHERE (o.an IS NULL OR o.an ="")
                AND v.income-v.rcpt_money <>"0"		
                AND v.income-v.rcpt_money-COALESCE(o1.other_price, 0) <>"0"			
                AND o.vstdate BETWEEN ? AND ?
                AND p.hipdata_code = "STP" 			
                AND (vp.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    OR vp.hospmain IS NULL OR vp.hospmain ="")  
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_702::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'ppfs'            => $row->ppfs,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_702_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_702_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_702::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_702_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_702_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_702  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_702_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050101_702_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_702_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_702_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050102_106--------------------------------------------------------------------------------------------------------------
    public function _1102050102_106(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value');       

        if ($search) {
            $debtor = DB::connection('hosxp')->select('
                SELECT d.vstdate,d.vsttime,d.hn,d.vn,d.ptname,d.mobile_phone_number,d.pttype,d.hospmain,d.pdx,d.income,
                    d.paid_money,d.rcpt_money,d.debtor,d.debtor_lock,IF(r.bill_amount <>"","กระทบยอดแล้ว",d.status) AS status,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,IFNULL(d.receive,r.bill_amount) AS receive,
                    IFNULL(d.repno,r.rcpno) AS repno,r.bill_amount,IF(t.visit IS NULL,0,t.visit) AS visit,
                    CASE WHEN IFNULL(d.receive,r.bill_amount) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM hrims.debtor_1102050102_106 d
                LEFT JOIN rcpt_print r ON r.vn = d.vn AND r.`status` ="OK" AND r.department="OPD" AND r.bill_date <> d.vstdate
                LEFT JOIN (SELECT vn,COUNT(vn) AS visit FROM hrims.debtor_1102050102_106_tracking GROUP BY vn) t ON t.vn=d.vn
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$search, $search, $start_date, $end_date]);
        } else {
            $debtor = DB::connection('hosxp')->select('
                SELECT d.vstdate,d.vsttime,d.hn,d.vn,d.ptname,d.mobile_phone_number,d.pttype,d.hospmain,d.pdx,d.income,
                    d.paid_money,d.rcpt_money,d.debtor,d.debtor_lock,IF(r.bill_amount <>"","กระทบยอดแล้ว",d.status) AS status,
                    d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,IFNULL(d.receive,r.bill_amount) AS receive,
                    IFNULL(d.repno,r.rcpno) AS repno,r.bill_amount,IF(t.visit IS NULL,0,t.visit) AS visit,
                    CASE WHEN IFNULL(d.receive,r.bill_amount) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM hrims.debtor_1102050102_106 d
                LEFT JOIN rcpt_print r ON r.vn = d.vn AND r.`status` ="OK" AND r.department="OPD" AND r.bill_date <> d.vstdate
                LEFT JOIN (SELECT vn,COUNT(vn) AS visit FROM hrims.debtor_1102050102_106_tracking GROUP BY vn) t ON t.vn=d.vn
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,o.vn,o.an,o.hn,v.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.paid_money,
                v.rcpt_money,v.paid_money-v.rcpt_money AS debtor,r.rcpno,p2.arrear_date,p2.amount AS arrear_amount,
                r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount
            FROM ovst o 
                LEFT JOIN patient pt ON pt.hn=o.hn		
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN patient_arrear p2 ON p2.vn=o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN rcpt_print r ON r.vn = o.vn AND r.`status` ="OK" AND r.department="OPD" AND r.bill_date=o.vstdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.vn AND r1.`status` ="OK" AND r1.department="OPD" 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN hospcode h ON h.hospcode=vp.hospmain
            WHERE (o.an IS NULL OR o.an ="")
                AND v.paid_money <>"0" 
                AND v.rcpt_money <> v.paid_money 
                AND o.vstdate BETWEEN ? AND ?
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_106 WHERE vn IS NOT NULL)    
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue ',[$start_date,$end_date]); 

        $debtor_search_iclaim = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.oqueue,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,o.vsttime,
                p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,GROUP_CONCAT(s.`name`) AS other_list,
                IFNULL(SUM(o1.sum_price),0) AS other,v.income-v.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
			LEFT JOIN s_drugitems s ON s.icode = o1.icode	
            WHERE (o.an IS NULL OR o.an ="") 
                AND vp.pttype = ?
				AND o.vstdate BETWEEN ? AND ?
				AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_106 WHERE vn IS NOT NULL)
            GROUP BY o.vn ORDER BY sum_price DESC ,o.vstdate,o.oqueue',[$pttype_iclaim,$start_date,$end_date]);

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_106',compact('start_date','end_date','search','debtor','debtor_search','debtor_search_iclaim'));
    }
//_1102050102_106_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vstdate,o.vsttime,o.oqueue,o.vn,o.an,o.hn,v.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.paid_money,
                v.rcpt_money,v.paid_money-v.rcpt_money AS debtor,r.rcpno,p2.arrear_date,p2.amount AS arrear_amount,
                r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount,"ยืนยันลูกหนี้" AS status  
            FROM ovst o 
                LEFT JOIN patient pt ON pt.hn=o.hn		
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN patient_arrear p2 ON p2.vn=o.vn
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = o.vn
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = o.vn
            LEFT JOIN rcpt_print r ON r.vn = o.vn AND r.`status` ="OK" AND r.department="OPD" AND r.bill_date=o.vstdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.vn AND r1.`status` ="OK" AND r1.department="OPD" 
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN hospcode h ON h.hospcode=vp.hospmain
            WHERE (o.an IS NULL OR o.an ="")
                AND v.paid_money <>"0" 
                AND v.rcpt_money <> v.paid_money 
                AND o.vstdate BETWEEN ? AND ?  
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_106::insert([
                'vn'                  => $row->vn,
                'hn'                  => $row->hn,
                'an'                  => $row->an,
                'cid'                 => $row->cid,
                'ptname'              => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'vstdate'             => $row->vstdate,
                'vsttime'             => $row->vsttime,
                'pttype'              => $row->pttype,    
                'hospmain'            => $row->hospmain,    
                'hipdata_code'        => $row->hipdata_code,   
                'pdx'                 => $row->pdx,  
                'income'              => $row->income,  
                'paid_money'          => $row->paid_money,
                'rcpt_money'          => $row->rcpt_money,
                'debtor'              => $row->debtor,      
                'status'              => $row->status,         
                ]);            
            }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_106_confirm_iclaim-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_confirm_iclaim(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value'); 
        $request->validate([
        'checkbox_iclaim' => 'required|array',
        ], [
            'checkbox_iclaim.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox_iclaim'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.oqueue,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,o.vsttime,
                pt.mobile_phone_number,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.paid_money,v.rcpt_money,
                GROUP_CONCAT(s.`name`) AS other_list,IFNULL(SUM(o1.sum_price),0) AS other,
                v.income-v.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor,"ยืนยันลูกหนี้" AS status
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
			LEFT JOIN s_drugitems s ON s.icode = o1.icode	
            WHERE (o.an IS NULL OR o.an ="") 
                AND vp.pttype = ?
				AND o.vstdate BETWEEN ? AND ?  
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$pttype_iclaim,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_106::insert([
                'vn'                  => $row->vn,
                'hn'                  => $row->hn,
                'an'                  => $row->an,
                'cid'                 => $row->cid,
                'ptname'              => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'vstdate'             => $row->vstdate,
                'vsttime'             => $row->vsttime,
                'pttype'              => $row->pttype,    
                'hospmain'            => $row->hospmain,    
                'hipdata_code'        => $row->hipdata_code,   
                'pdx'                 => $row->pdx,  
                'income'              => $row->income,  
                'paid_money'          => $row->paid_money,
                'rcpt_money'          => $row->rcpt_money,
                'other'               => $row->other,
                'debtor'              => $row->debtor,      
                'status'              => $row->status,         
                ]);            
            }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_106_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_106::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_106_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_106::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_106_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_106_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::connection('hosxp')->select('
            SELECT d.vstdate,COUNT(DISTINCT d.vn) AS anvn,
            SUM(d.debtor) AS debtor,SUM(IFNULL(d.receive, r.bill_amount)) AS receive
            FROM hrims.debtor_1102050102_106 d 
            LEFT JOIN rcpt_print r ON r.vn = d.vn AND r.`status` ="OK" AND r.department="OPD" AND r.bill_date <> d.vstdate
            WHERE d.vstdate BETWEEN ? AND ?
            GROUP BY d.vstdate ORDER BY d.vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_106_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050102_106_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_106_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_106_indiv_excel',compact('start_date','end_date','debtor'));
    }
//_1102050102_106_tracking-------------------------------------------------------------------------------------------------------   
    public function _1102050102_106_tracking(Request $request, $vn )
    {
        $debtor = DB::select('
            SELECT * FROM debtor_1102050102_106 WHERE vn = ?', [$vn]);

        $tracking = DB::select('
            SELECT * FROM debtor_1102050102_106_tracking WHERE vn = ?', [$vn]);

        return view('debtor.1102050102_106_tracking',compact('debtor','tracking'));
    }
//_1102050102_106_tracking_insert--------------------------------------------------------------------------------------------------
    public function _1102050102_106_tracking_insert(Request $request)
    {
        $item = new Debtor_1102050102_106_tracking;
        $item->vn = $request->input('vn');
        $item->tracking_date = $request->input('tracking_date');
        $item->tracking_type = $request->input('tracking_type');
        $item->tracking_no = $request->input('tracking_no');
        $item->tracking_officer = $request->input('tracking_officer');
        $item->tracking_note = $request->input('tracking_note');  
        $item->save();  

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//_1102050102_106_tracking_update-------------------------------------------------------------------------------------------------
    public function _1102050102_106_tracking_update(Request $request, $tracking_id)
    {
    Debtor_1102050102_106_tracking::where('tracking_id', $tracking_id)
        ->update([
        'tracking_date' => $request->input('tracking_date'),
        'tracking_type' => $request->input('tracking_type'),
        'tracking_no' => $request->input('tracking_no'),
        'tracking_officer' => $request->input('tracking_officer'),
        'tracking_note' => $request->input('tracking_note')
        ]);

    return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');  
    }
##############################################################################################################################################################
//_1102050102_108--------------------------------------------------------------------------------------------------------------
    public function _1102050102_108(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        

        $debtor =  Debtor_1102050102_108::whereBetween('vstdate', [$start_date,$end_date])
                    ->where(function ($query) use ($search){
                        $query->where('ptname','like','%'.$search.'%');
                        $query->orwhere('hn','like','%'.$search.'%');
                        })
                    ->orderBy('vstdate')->get()
                    ->map(function ($item) {                        
                        if (($item->receive - $item->debtor) >= 0) {
                            $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                        } else {
                            $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                        }
                        return $item;
                    }); 

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                IFNULL(SUM(o1.sum_price),0) AS other,GROUP_CONCAT(s.`name`) AS other_list,
                v.income-v.rcpt_money-IFNULL(o1.sum_price,0) AS debtor ,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y") 
            LEFT JOIN s_drugitems s ON s.icode = o1.icode   
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income <> "0" 
                AND v.paid_money = "0" 
                AND p.hipdata_code IN ("BFC","GOF","PVT","WVO")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_108 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_108',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_108_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050108_108_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                IFNULL(SUM(o1.sum_price),0) AS other,GROUP_CONCAT(s.`name`) AS other_list,
                v.income-v.rcpt_money-IFNULL(o1.sum_price,0) AS debtor ,"ยืนยันลูกหนี้" AS status    
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype 
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y") 
            LEFT JOIN s_drugitems s ON s.icode = o1.icode      
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income <> "0" 
                AND v.paid_money = "0" 
                AND p.hipdata_code IN ("BFC","GOF","PVT","WVO")
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_108::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_108_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_108_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_108::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_108_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_108_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_108::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_108_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_108_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_108  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_108_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050102_108_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_108_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_108_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050102_602--------------------------------------------------------------------------------------------------------------
    public function _1102050102_602(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');    
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');     

        $debtor =  Debtor_1102050102_602::whereBetween('vstdate', [$start_date,$end_date])
                    ->where(function ($query) use ($search){
                        $query->where('ptname','like','%'.$search.'%');
                        $query->orwhere('hn','like','%'.$search.'%');
                        })
                    ->orderBy('vstdate')->get()
                    ->map(function ($item) {                        
                        if (($item->receive - $item->debtor) >= 0) {
                            $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                        } else {
                            $item->days = Carbon::parse($item->vstdate)->diffInDays(Carbon::today());
                        }
                        return $item;
                    });

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                IFNULL(SUM(o1.sum_price),0) AS other,GROUP_CONCAT(s.`name`) AS other_list,
                v.income-v.rcpt_money-IFNULL(o1.sum_price,0) AS debtor ,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y") 
            LEFT JOIN s_drugitems s ON s.icode = o1.icode   
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <> "0" 
                AND vp.pttype IN ('.$pttype_act.')
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_602 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_602',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_602_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');     
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,
                IFNULL(SUM(o1.sum_price),0) AS other,GROUP_CONCAT(s.`name`) AS other_list,
                v.income-v.rcpt_money-IFNULL(o1.sum_price,0) AS debtor ,"ยืนยันลูกหนี้" AS status    
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype 
            LEFT JOIN opitemrece o1 ON o1.vn=o.vn AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y") 
            LEFT JOIN s_drugitems s ON s.icode = o1.icode      
            WHERE (o.an IS NULL OR o.an ="") 
                AND o.vstdate BETWEEN ? AND ?
                AND v.income-v.rcpt_money <> "0" 
                AND vp.pttype IN ('.$pttype_act.')
                AND v.pdx NOT IN (SELECT icd10 FROM hrims.lookup_icd10 WHERE pp = "Y")
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_602::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'other'           => $row->other,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_602_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_602::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_602_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_602::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_602_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_602_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,
            SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_602  
            WHERE vstdate BETWEEN ? AND ?
            GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_602_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
 //1102050102_602_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_602_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_602_indiv_excel',compact('start_date','end_date','debtor'));
    }
##############################################################################################################################################################
//_1102050102_801--------------------------------------------------------------------------------------------------------------
    public function _1102050102_801(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');           

        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,d.rcpt_money,  
                    d.lgo,d.kidney,d.ppfs,d.other,d.debtor,IFNULL(s.compensate_treatment,0) AS receive_lgo,IFNULL(sk.receive_total,0) AS receive_kidney,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,IFNULL(s.compensate_treatment,0)+IFNULL(sk.receive_total,0)+IFNULL(su.receive_pp,0) AS receive,
                    d.status,IFNULL(s.repno,sk.repno) AS repno,d.debtor_lock,
                    CASE WHEN (IFNULL(s.compensate_treatment,0)+IFNULL(sk.receive_total,0)+IFNULL(su.receive_pp,0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_801 d   
                LEFT JOIN stm_lgo s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
				LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM stm_lgo_kidney
				WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.datetimeadm = d.vstdate
				LEFT JOIN stm_ucs su ON su.cid = d.cid AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$start_date,$end_date,$search, $search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,d.rcpt_money,  
                    d.lgo,d.kidney,d.ppfs,d.other,d.debtor,IFNULL(s.compensate_treatment,0) AS receive_lgo,IFNULL(sk.receive_total,0) AS receive_kidney,
                    IFNULL(su.receive_pp,0) AS receive_ppfs,IFNULL(s.compensate_treatment,0)+IFNULL(sk.receive_total,0)+IFNULL(su.receive_pp,0) AS receive,
                    d.status,IFNULL(s.repno,sk.repno) AS repno,d.debtor_lock,
                    CASE WHEN (IFNULL(s.compensate_treatment,0)+IFNULL(sk.receive_total,0)+IFNULL(su.receive_pp,0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_801 d   
                LEFT JOIN stm_lgo s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
				LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM stm_lgo_kidney
				WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.datetimeadm = d.vstdate
				LEFT JOIN stm_ucs su ON su.cid = d.cid AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date,$end_date,$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,oe.upload_datetime AS claim,								        
                v.income-v.rcpt_money-COALESCE(o1.kidney_price, 0)-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS lgo,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o2.ppfs_price, 0) AS ppfs,COALESCE(o3.other_price, 0) AS other,
                v.income-v.rcpt_money-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,
                GROUP_CONCAT(DISTINCT sd1.`name`) AS ppfs_list,GROUP_CONCAT(DISTINCT sd2.`name`) AS other_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn				
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o3 ON o3.vn=o.vn				
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o4.icode	
			LEFT JOIN opitemrece o5 ON o5.vn=o.vn AND o5.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd1 ON sd1.icode=o5.icode
			LEFT JOIN opitemrece o6 ON o6.vn=o.vn AND o6.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o6.icode
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            WHERE (o.an IS NULL OR o.an ="")
			    AND p.hipdata_code = "LGO"
                AND v.income-v.rcpt_money <>"0"		
			    AND v.income-v.rcpt_money-COALESCE(o3.other_price, 0) <>"0"				
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype NOT IN ('.$pttype_checkup.')
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_801 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_801',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_801_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,								        
                v.income-v.rcpt_money-COALESCE(o1.kidney_price, 0)-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS lgo,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o2.ppfs_price, 0) AS ppfs,COALESCE(o3.other_price, 0) AS other,
                v.income-v.rcpt_money-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,
                GROUP_CONCAT(DISTINCT sd1.`name`) AS ppfs_list,GROUP_CONCAT(DISTINCT sd2.`name`) AS other_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn				
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o3 ON o3.vn=o.vn				
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o4.icode	
			LEFT JOIN opitemrece o5 ON o5.vn=o.vn AND o5.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd1 ON sd1.icode=o5.icode
			LEFT JOIN opitemrece o6 ON o6.vn=o.vn AND o6.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o6.icode
            WHERE (o.an IS NULL OR o.an ="")
			    AND p.hipdata_code = "LGO"
                AND v.income-v.rcpt_money <>"0"		
			    AND v.income-v.rcpt_money-COALESCE(o3.other_price, 0) <>"0"				
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype NOT IN ('.$pttype_checkup.')
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_801::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'lgo'             => $row->lgo,
                'kidney'          => $row->kidney,
                'ppfs'            => $row->ppfs,
                'other'           => $row->other,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_801_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_801::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_801_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_801::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_801_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_801_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,
                SUM(IFNULL(receive_lgo,0)+IFNULL(receive_kidney,0)) AS receive
            FROM (SELECT d.vstdate,d.vn,d.hn,d.debtor,IFNULL(s.compensate_treatment,0) AS receive_lgo,
			IFNULL(sk.receive_total,0) AS receive_kidney
            FROM debtor_1102050102_801 d   
            LEFT JOIN stm_lgo s ON s.cid = d.cid AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
			LEFT JOIN (SELECT cid,datetimeadm,sum(compensate_kidney) AS receive_total,repno FROM stm_lgo_kidney
				WHERE datetimeadm BETWEEN ? AND ? GROUP BY cid,datetimeadm) sk ON sk.cid=d.cid AND sk.datetimeadm = d.vstdate
			LEFT JOIN stm_ucs su ON su.hn = d.hn AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
            WHERE d.vstdate BETWEEN ? AND ? GROUP BY d.vn ) AS a GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date,$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_801_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050102_801_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_801_indiv_excel(Request $request)
        {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_801_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050102_803--------------------------------------------------------------------------------------------------------------
    public function _1102050102_803(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');           
 
        if ($search) {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,d.rcpt_money,  
                    d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,
                    IFNULL(s.receive_total,0) AS receive_ofc,IFNULL(sk.receive_total,0) AS receive_kidney,IFNULL(su.receive_pp,0) AS receive_ppfs,
                    IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0) AS receive,d.status,IFNULL(s.repno,sk.repno) AS repno,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_803 d   
                LEFT JOIN stm_ofc s ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
				LEFT JOIN (SELECT hn,vstdate,sum(amount) AS receive_total,rid AS repno FROM stm_ofc_kidney
				WHERE vstdate BETWEEN ? AND ? GROUP BY hn,vstdate) sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate
				LEFT JOIN stm_ucs su ON su.hn = d.hn AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%"))
                AND d.vstdate BETWEEN ? AND ?', [$start_date,$end_date,$search, $search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.vn,d.vstdate,d.vsttime,d.hn,d.ptname,d.hipdata_code,d.pttype,d.hospmain,d.pdx,d.income,d.rcpt_money,  
                    d.ofc,d.kidney,d.ppfs,d.other,d.debtor,d.charge_date,d.charge_no,d.charge,d.receive_date,d.receive_no,
                    IFNULL(s.receive_total,0) AS receive_ofc,IFNULL(sk.receive_total,0) AS receive_kidney,IFNULL(su.receive_pp,0) AS receive_ppfs,
                    IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0)AS receive,d.status,IFNULL(s.repno,sk.repno) AS repno,d.debtor_lock,
                    CASE WHEN (IFNULL(d.receive,0)+IFNULL(s.receive_total,0)+IFNULL(sk.receive_total,0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.vstdate) END AS days
                FROM debtor_1102050102_803 d   
                LEFT JOIN stm_ofc s ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
				LEFT JOIN (SELECT hn,vstdate,sum(amount) AS receive_total,rid AS repno FROM stm_ofc_kidney
				WHERE vstdate BETWEEN ? AND ? GROUP BY hn,vstdate) sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate
				LEFT JOIN stm_ucs su ON su.hn = d.hn AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
                WHERE d.vstdate BETWEEN ? AND ?', [$start_date,$end_date,$start_date, $end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,oe.upload_datetime AS claim,								        
                v.income-v.rcpt_money-COALESCE(o1.kidney_price, 0)-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS ofc,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o2.ppfs_price, 0) AS ppfs,COALESCE(o3.other_price, 0) AS other,
                v.income-v.rcpt_money-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,
                GROUP_CONCAT(DISTINCT sd1.`name`) AS ppfs_list,GROUP_CONCAT(DISTINCT sd2.`name`) AS other_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn				
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o3 ON o3.vn=o.vn				
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o4.icode	
			LEFT JOIN opitemrece o5 ON o5.vn=o.vn AND o5.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd1 ON sd1.icode=o5.icode
			LEFT JOIN opitemrece o6 ON o6.vn=o.vn AND o6.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o6.icode
            LEFT JOIN ovst_eclaim oe ON oe.vn=o.vn
            WHERE (o.an IS NULL OR o.an ="")
			    AND p.hipdata_code IN ("BKK","BMT")
                AND v.income-v.rcpt_money <>"0"		
			    AND v.income-v.rcpt_money-COALESCE(o3.other_price, 0) <>"0"				
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype NOT IN ('.$pttype_checkup.')
                AND o.vn NOT IN (SELECT vn FROM hrims.debtor_1102050102_803 WHERE vn IS NOT NULL) 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_803',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_803_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');        
        $pttype_checkup = DB::table('main_setting')->where('name', 'pttype_checkup')->value('value');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT o.vn,o.hn,o.an,pt.cid,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,o.vstdate,
                o.vsttime,p.`name` AS pttype,vp.hospmain,p.hipdata_code,v.pdx,v.income,v.rcpt_money,								        
                v.income-v.rcpt_money-COALESCE(o1.kidney_price, 0)-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS ofc,
                COALESCE(o1.kidney_price, 0) AS kidney,COALESCE(o2.ppfs_price, 0) AS ppfs,COALESCE(o3.other_price, 0) AS other,
                v.income-v.rcpt_money-COALESCE(o2.ppfs_price, 0)-COALESCE(o3.other_price, 0) AS debtor,GROUP_CONCAT(DISTINCT sd.`name`) AS kidney_list,
                GROUP_CONCAT(DISTINCT sd1.`name`) AS ppfs_list,GROUP_CONCAT(DISTINCT sd2.`name`) AS other_list,"ยืนยันลูกหนี้" AS status  
            FROM ovst o    
            LEFT JOIN patient pt ON pt.hn=o.hn
            LEFT JOIN vn_stat v ON v.vn=o.vn
            LEFT JOIN visit_pttype vp ON vp.vn=o.vn
            LEFT JOIN pttype p ON p.pttype=vp.pttype
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS kidney_price FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.kidney ="Y"
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o1 ON o1.vn=o.vn				
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS ppfs_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ppfs ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o2 ON o2.vn=o.vn
			LEFT JOIN (SELECT op.vn, SUM(op.sum_price) AS other_price	FROM opitemrece op
				INNER JOIN hrims.lookup_icode li ON op.icode = li.icode AND li.ems ="Y" 
				WHERE op.vstdate BETWEEN ? AND ? GROUP BY op.vn) o3 ON o3.vn=o.vn				
			LEFT JOIN opitemrece o4 ON o4.vn=o.vn AND o4.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney ="Y")	
			LEFT JOIN s_drugitems sd ON sd.icode=o4.icode	
			LEFT JOIN opitemrece o5 ON o5.vn=o.vn AND o5.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs ="Y")	
			LEFT JOIN s_drugitems sd1 ON sd1.icode=o5.icode
			LEFT JOIN opitemrece o6 ON o6.vn=o.vn AND o6.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")	
			LEFT JOIN s_drugitems sd2 ON sd2.icode=o6.icode
            WHERE (o.an IS NULL OR o.an ="")
			    AND p.hipdata_code IN ("BKK","BMT")
                AND v.income-v.rcpt_money <>"0"		
			    AND v.income-v.rcpt_money-COALESCE(o3.other_price, 0) <>"0"				
                AND o.vstdate BETWEEN ? AND ?
                AND p.pttype NOT IN ('.$pttype_checkup.')
                AND o.vn IN ('.$checkbox_string.') 
            GROUP BY o.vn ORDER BY o.vstdate,o.oqueue',[$start_date,$end_date,$start_date,$end_date,$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_803::insert([
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'vstdate'         => $row->vstdate,
                'vsttime'         => $row->vsttime,
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money,
                'ofc'             => $row->ofc,
                'kidney'          => $row->kidney,
                'ppfs'            => $row->ppfs,
                'other'           => $row->other,
                'debtor'          => $row->debtor,  
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_803_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_803::whereIn('vn', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_803_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_update(Request $request, $vn)
    {
        $item = Debtor_1102050102_803::findOrFail($vn);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_803_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_803_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT vstdate,COUNT(DISTINCT vn) AS anvn,SUM(debtor) AS debtor,
                SUM(IFNULL(receive,0)+IFNULL(receive_s,0)+IFNULL(receive_sk,0)) AS receive
            FROM (SELECT d.vstdate,d.vn,d.hn,d.debtor,d.receive,s.receive_total AS receive_s,sk.receive_sk ,su.receive_pp 
            FROM debtor_1102050102_803 d   
            LEFT JOIN stm_ofc s ON s.hn = d.hn AND s.vstdate = d.vstdate AND LEFT(s.vsttime, 5) = LEFT(d.vsttime, 5)
			LEFT JOIN (SELECT hn,vstdate,sum(amount) AS receive_sk,rid AS repno FROM stm_ofc_kidney
				WHERE vstdate BETWEEN ? AND ? GROUP BY hn,vstdate) sk ON sk.hn=d.hn AND sk.vstdate = d.vstdate
			LEFT JOIN stm_ucs su ON su.hn = d.hn AND su.vstdate = d.vstdate AND LEFT(su.vsttime, 5) = LEFT(d.vsttime, 5)
            WHERE d.vstdate BETWEEN ? AND ? GROUP BY d.vn ) AS a GROUP BY vstdate ORDER BY vstdate',[$start_date,$end_date,$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_803_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050103_803_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_803_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_803_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_202--------------------------------------------------------------------------------------------------------------
    public function _1102050101_202(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        
 
        if ($search) {
            $debtor = DB::select('
                SELECT d.*,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno,
                CASE WHEN (IFNULL(stm.receive_total,0) - IFNULL(d.debtor,0)) >= 0 THEN 0
                   ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_202 d
                LEFT JOIN stm_ucs stm ON stm.an=d.an
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$search, $search,$search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.*,stm.fund_ip_payrate,stm.receive_ip_compensate_pay,stm.receive_total,stm.repno,
                CASE WHEN (IFNULL(stm.receive_total,0) - IFNULL(d.debtor,0)) >= 0 THEN 0
                   ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_202 d
                LEFT JOIN stm_ucs stm ON stm.an=d.an
                WHERE d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$start_date,$end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?
                AND (li.kidney = "Y" OR li.ems="Y" OR li.uc_cr ="Y" OR n.nhso_adp_code IN ("S1801","S1802")) 
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND (o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y" OR uc_cr ="Y")
                OR o.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802")))
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "UCS" 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_202 WHERE an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_202',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_202_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?
                AND (li.kidney = "Y" OR li.ems="Y" OR li.uc_cr ="Y" OR n.nhso_adp_code IN ("S1801","S1802")) 
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND (o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y" OR uc_cr ="Y")
                OR o.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802")))
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "UCS" 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_202::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,           
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_202_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_202::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_202_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_202_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,
            SUM(debtor) AS debtor,SUM(receive_ip_compensate_pay) AS receive
            FROM (SELECT d.dchdate,d.an,d.debtor,stm.receive_ip_compensate_pay FROM debtor_1102050101_202 d
            LEFT JOIN stm_ucs stm ON stm.an=d.an    
            WHERE d.dchdate BETWEEN ? AND ?
            GROUP BY d.an) AS a
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_202_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_202_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_202_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_202_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_217--------------------------------------------------------------------------------------------------------------
    public function _1102050101_217(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        
 
        if ($search) {
            $debtor = DB::select('
                SELECT d.*,stm.fund_ip_payrate,(stm.receive_total-stm.receive_ip_compensate_pay)+sk.receive_total AS receive,
				    stm.repno,sk.repno AS repno_kidney,
                    CASE WHEN IFNULL((stm.receive_total-stm.receive_ip_compensate_pay)+sk.receive_total,0) - IFNULL(d.debtor, 0) >= 0
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_217 d
                LEFT JOIN stm_ucs stm ON stm.an=d.an
                LEFT JOIN stm_ucs_kidney sk ON sk.cid=d.cid AND sk.datetimeadm BETWEEN d.regdate AND d.dchdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$search,$search,$search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.*,stm.fund_ip_payrate,(stm.receive_total-stm.receive_ip_compensate_pay)+sk.receive_total AS receive,
					stm.repno,sk.repno AS repno_kidney,
                    CASE WHEN IFNULL((stm.receive_total-stm.receive_ip_compensate_pay)+sk.receive_total,0) - IFNULL(d.debtor, 0) >= 0
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_217 d
                LEFT JOIN stm_ucs stm ON stm.an=d.an
                LEFT JOIN stm_ucs_kidney sk ON sk.cid=d.cid AND sk.datetimeadm BETWEEN d.regdate AND d.dchdate
                WHERE d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$start_date,$end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(cr.cr_price,0) AS cr,COALESCE(cr.cr_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS cr_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS cr_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?
                AND (li.kidney = "Y" OR li.ems="Y" OR li.uc_cr ="Y" OR n.nhso_adp_code IN ("S1801","S1802")) 
                GROUP BY op.an) cr ON cr.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND (o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y" OR ems="Y" OR uc_cr ="Y")
                OR o.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802")))
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
                AND COALESCE(cr.cr_price,0) <> "0"
                AND p.hipdata_code = "UCS" 
                AND i.dchdate BETWEEN ? AND ?
                AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_217 WHERE an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_217',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_217_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_217_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(cr.cr_price,0) AS cr,COALESCE(cr.cr_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS cr_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS cr_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?
                AND (li.kidney = "Y" OR li.ems="Y" OR li.uc_cr ="Y" OR n.nhso_adp_code IN ("S1801","S1802")) 
                GROUP BY op.an) cr ON cr.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND (o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y" OR ems="Y" OR uc_cr ="Y")
                OR o.icode IN (SELECT icode FROM nondrugitems WHERE nhso_adp_code IN ("S1801","S1802")))
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND COALESCE(cr.cr_price,0) <> "0"
            AND p.hipdata_code = "UCS" 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_217::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'cr'              => $row->cr,  
                'debtor'          => $row->debtor,           
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_217_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_217_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_217::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_217_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_217_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM (SELECT d.dchdate,d.an,d.debtor,(stm.receive_total-stm.receive_ip_compensate_pay)+IFNULL(SUM(sk.receive_total),0) AS receive
            FROM debtor_1102050101_217 d
            LEFT JOIN stm_ucs stm ON stm.an=d.an 
			LEFT JOIN stm_ucs_kidney sk ON sk.cid=d.cid AND sk.datetimeadm BETWEEN d.regdate AND d.dchdate			
            WHERE d.dchdate BETWEEN ? AND ?
            GROUP BY d.an) AS a
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_217_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_217_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_217_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_217_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_302--------------------------------------------------------------------------------------------------------------
    public function _1102050101_302(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        
        $debtor =  Debtor_1102050101_302::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            });  

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status   
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "SSS"
            AND ip.pttype NOT IN ('.$pttype_sss_fund.')
			AND ip.pttype NOT IN ('.$pttype_sss_72.') 
            AND i.dchdate BETWEEN ? AND ?
			AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_302 WHERE an IS NOT NULL)
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_302',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_302_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "SSS" 
			AND ip.pttype NOT IN ('.$pttype_sss_fund.')
			AND ip.pttype NOT IN ('.$pttype_sss_72.')
            AND i.dchdate BETWEEN ? AND ?
			AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_302::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor, 
                'status'          => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_302_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_302::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_302_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_update(Request $request, $an)
    {
        $item = Debtor_1102050101_302::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_302_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_302_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_302    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_302_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_302_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_302_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_302_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_304--------------------------------------------------------------------------------------------------------------
    public function _1102050101_304(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        
        $debtor =  Debtor_1102050101_304::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            });  

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "SSS"
            AND ip.pttype NOT IN ('.$pttype_sss_fund.')
			AND ip.pttype NOT IN ('.$pttype_sss_72.') 
            AND i.dchdate BETWEEN ? AND ?
			AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_304 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_304',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_304_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "SSS" 
			AND ip.pttype NOT IN ('.$pttype_sss_fund.')
			AND ip.pttype NOT IN ('.$pttype_sss_72.')
            AND i.dchdate BETWEEN ? AND ?
			AND ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_sss ="Y")
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_304::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_304_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_304::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_304_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_update(Request $request, $an)
    {
        $item = Debtor_1102050101_304::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_304_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_304_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_304    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_304_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_304_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_304_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_304_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_308--------------------------------------------------------------------------------------------------------------
    public function _1102050101_308(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        
        $debtor =  Debtor_1102050101_308::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            });  

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND ip.pttype IN ('.$pttype_sss_72.') 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_308 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_308',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_308_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $pttype_sss_fund = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value');
        $pttype_sss_72 = DB::table('main_setting')->where('name', 'pttype_sss_72')->value('value');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND ip.pttype IN ('.$pttype_sss_72.')
            AND i.dchdate BETWEEN ? AND ?			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_308::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_308_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_308::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_308_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_update(Request $request, $an)
    {
        $item = Debtor_1102050101_308::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_308_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_308_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_308    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_308_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_308_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_308_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_308_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_310--------------------------------------------------------------------------------------------------------------
    public function _1102050101_310(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        
        $debtor =  Debtor_1102050101_310::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            });  

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,COALESCE(kidney.kidney_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code IN ("SSS","SSI") 
            AND COALESCE(kidney.kidney_price,0)<>"0"
            AND i.dchdate BETWEEN ? AND ?            
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_310 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_310',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_310_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,COALESCE(kidney.kidney_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
                LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code IN ("SSS","SSI") 
            AND COALESCE(kidney.kidney_price,0)<>"0"
            AND i.dchdate BETWEEN ? AND ?   			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_310::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'kidney'          => $row->kidney,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_310_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_310::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_310_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_update(Request $request, $an)
    {
        $item = Debtor_1102050101_310::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_310_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_310_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_310   
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_310_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_310_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_310_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_310_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_402--------------------------------------------------------------------------------------------------------------
    public function _1102050101_402(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        
 
        if ($search) {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,d.income,
                    d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,s.receive_total+IFNULL(SUM(s1.amount),0) AS receive,
                    s.receive_total AS receive_ofc,SUM(s1.amount) AS receive_kidney,s.repno,s1.rid AS repno_kidney,
                    CASE WHEN (s.receive_total+IFNULL(SUM(s1.amount),0)) - IFNULL(d.debtor,0) >= 0 THEN 0
                    ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_402 d    
                LEFT JOIN hrims.stm_ofc s ON s.an=d.an
                LEFT JOIN hrims.stm_ofc_kidney s1 ON s1.hn=d.hn AND s1.vstdate BETWEEN d.regdate AND d.dchdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$search,$search,$search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,d.income,
                    d.rcpt_money,d.kidney,d.debtor,debtor_lock,s.receive_total+IFNULL(SUM(s1.amount),0) AS receive,
                    s.receive_total AS receive_ofc,SUM(s1.amount) AS receive_kidney,s.repno,s1.rid AS repno_kidney,
                    CASE WHEN (s.receive_total+IFNULL(SUM(s1.amount),0)) - IFNULL(d.debtor,0) >= 0 THEN 0
                    ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050101_402 d    
                LEFT JOIN hrims.stm_ofc s ON s.an=d.an
                LEFT JOIN hrims.stm_ofc_kidney s1 ON s1.hn=d.hn AND s1.vstdate BETWEEN d.regdate AND d.dchdate
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an', [$start_date,$end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,a.income-a.rcpt_money-COALESCE(kidney.kidney_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
				LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y" 
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "OFC" 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_402 WHERE an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_402',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_402_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_402_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,a.income-a.rcpt_money-COALESCE(kidney.kidney_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
				LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y" 
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "OFC" 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_402::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'kidney'          => $row->kidney,  
                'debtor'          => $row->debtor,           
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_402_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_402_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_402::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050101_402_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_402_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,
            SUM(debtor) AS debtor,SUM(receive_total) AS receive
            FROM (SELECT d.*,s.receive_total+IFNULL(SUM(sk.amount),0) AS receive_total 
            FROM debtor_1102050101_402 d    
            LEFT JOIN hrims.stm_ofc s ON s.an=d.an
            LEFT JOIN hrims.stm_ofc_kidney sk ON sk.hn=d.hn AND sk.vstdate BETWEEN d.regdate AND d.dchdate
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a 
		GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_402_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_402_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_402_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_402_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_502--------------------------------------------------------------------------------------------------------------
    public function _1102050101_502(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        
        $debtor =  Debtor_1102050101_502::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            });   

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "NRH"
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_502 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_502',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_502_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "NRH"
            AND ip.hospmain IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
            AND i.dchdate BETWEEN ? AND ?			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_502::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_502_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_502::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_502_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_update(Request $request, $an)
    {
        $item = Debtor_1102050101_502::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_502_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_502_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_502    
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_502_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_502_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_502_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_502_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_504--------------------------------------------------------------------------------------------------------------
    public function _1102050101_504(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        
        $debtor =  Debtor_1102050101_504::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            });     

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "NRH"
            AND (ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    OR ip.hospmain IS NULL OR ip.hospmain ="")
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_504 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_504',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_504_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "NRH"
            AND (ip.hospmain NOT IN (SELECT hospcode FROM hrims.lookup_hospcode WHERE hmain_ucs ="Y")
                    OR ip.hospmain IS NULL OR ip.hospmain ="")
            AND i.dchdate BETWEEN ? AND ?			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_504::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_504_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_504::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_504_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_update(Request $request, $an)
    {
        $item = Debtor_1102050101_504::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_504_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_504_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_504   
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_504_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_504_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_504_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_504_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050101_704--------------------------------------------------------------------------------------------------------------
    public function _1102050101_704(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        
        $debtor =  Debtor_1102050101_704::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get();  

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "STP"            
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050101_704 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050101_704',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050101_704_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "STP"
            AND i.dchdate BETWEEN ? AND ?			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050101_704::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050101_704_delete-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050101_704::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050101_704_update-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_update(Request $request, $an)
    {
        $item = Debtor_1102050101_704::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050101_704_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050101_704_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050101_704   
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050101_704_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050101_704_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050101_704_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050101_704_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050102_107--------------------------------------------------------------------------------------------------------------
    public function _1102050102_107(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        $pttype_iclaim = DB::table('main_setting')->where('name', 'pttype_iclaim')->value('value');       
        
        if ($search) {
            $debtor = DB::connection('hosxp')->select('
                SELECT d.regdate,d.regtime,d.dchdate,d.dchtime,d.hn,d.vn,d.an,d.ptname,d.mobile_phone_number,d.pttype,d.pdx,d.income,d.paid_money,
                    d.rcpt_money,d.debtor,d.debtor_lock,IF(r.bill_amount <>"","กระทบยอดแล้ว",d.status) AS status,d.charge_date,d.charge_no,d.charge,
                    d.receive_date,d.receive_no,IFNULL(d.receive,r.bill_amount) AS receive,IFNULL(d.repno,r.rcpno) AS repno,r.bill_amount,
                    IF(t.visit IS NULL,0,t.visit) AS visit,CASE WHEN IFNULL(d.receive,r.bill_amount) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM hrims.debtor_1102050102_107 d
                LEFT JOIN rcpt_print r ON r.vn = d.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date NOT BETWEEN d.regdate AND d.dchdate
                LEFT JOIN (SELECT an,COUNT(an) AS visit FROM hrims.debtor_1102050102_107_tracking GROUP BY an) t ON t.an=d.an
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?', [$search,$search,$search,$start_date,$end_date]);
        } else {
            $debtor = DB::connection('hosxp')->select('
                SELECT d.regdate,d.regtime,d.dchdate,d.dchtime,d.hn,d.vn,d.an,d.ptname,d.mobile_phone_number,d.pttype,d.pdx,d.income,d.paid_money,
                    d.rcpt_money,d.debtor,d.debtor_lock,IF(r.bill_amount <>"","กระทบยอดแล้ว",d.status) AS status,d.charge_date,d.charge_no,d.charge,
                    d.receive_date,d.receive_no,IFNULL(d.receive,r.bill_amount) AS receive,IFNULL(d.repno,r.rcpno) AS repno,r.bill_amount,
                    IF(t.visit IS NULL,0,t.visit) AS visit,CASE WHEN IFNULL(d.receive,r.bill_amount) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM hrims.debtor_1102050102_107 d
                LEFT JOIN rcpt_print r ON r.vn = d.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date NOT BETWEEN d.regdate AND d.dchdate
                LEFT JOIN (SELECT an,COUNT(an) AS visit FROM hrims.debtor_1102050102_107_tracking GROUP BY an) t ON t.an=d.an
                WHERE d.dchdate BETWEEN ? AND ?', [$start_date, $end_date]);
            }  

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.name AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.vn,i.an,p.cid,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,
                p.mobile_phone_number,a.age_y,p1.`name` AS pttype,ip.hospmain,p1.hipdata_code,a.pdx,i.adjrw,a.income,
                a.paid_money,a.rcpt_money,(a.paid_money-a.rcpt_money) AS debtor,r.rcpno,p2.arrear_date,p2.amount AS arrear_amount,
                r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p1 ON p1.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r ON r.vn = i.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date BETWEEN i.regdate AND i.dchdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK" AND r1.department="IPD"
            LEFT JOIN patient p ON p.hn=i.hn
            LEFT JOIN hospcode h ON h.hospcode=ip.hospmain
            WHERE i.dchdate BETWEEN ? AND ?
            AND a.paid_money <>"0" AND a.rcpt_money <> a.paid_money 
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_107 WHERE an IS NOT NULL)
            GROUP BY i.an ORDER BY i.dchdate',[$start_date,$end_date]); 

        $debtor_search_iclaim = DB::connection('hosxp')->select('
            SELECT * FROM (SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.vn,i.hn,i.an,
                CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,a.pdx,p.cid,a.age_y,p1.name AS pttype,
                ip.hospmain,p1.hipdata_code,i.adjrw,a.income,a.rcpt_money,IFNULL(SUM(o1.sum_price),0) AS other,
                a.income-a.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor
            FROM ipt i 
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p1 ON p1.pttype=ip.pttype
            LEFT JOIN patient p ON p.hn=i.hn
            LEFT JOIN opitemrece o1 ON o1.an=i.an AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
            WHERE i.confirm_discharge = "Y" 
            AND p1.pttype = ?
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_107 WHERE an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate) AS a WHERE debtor <> "0"',[$pttype_iclaim,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_107',compact('start_date','end_date','search','debtor','debtor_search','debtor_search_iclaim'));
    }
//_1102050102_107_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้' 
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT i.regdate,i.regtime,i.dchdate,i.dchtime,i.hn,i.vn,i.an,p.cid,CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,
                p.mobile_phone_number,a.age_y,p1.`name` AS pttype,ip.hospmain,p1.hipdata_code,a.pdx,i.adjrw,a.income,
                a.paid_money,a.rcpt_money,(a.paid_money-a.rcpt_money) AS debtor,r.rcpno,p2.arrear_date,p2.amount AS arrear_amount,
                r1.bill_amount AS paid_arrear,r1.rcpno AS rcpno_arrear,fd.deposit_amount,fd1.debit_amount,"ยืนยันลูกหนี้" AS status  
            FROM ipt i LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p1 ON p1.pttype=ip.pttype
            LEFT JOIN patient_arrear p2 ON p2.an=i.an
            LEFT JOIN patient_finance_deposit fd ON fd.anvn = i.an
            LEFT JOIN patient_finance_debit fd1 ON fd1.anvn = i.an
            LEFT JOIN rcpt_print r ON r.vn = i.an AND r.`status` ="OK" AND r.department="IPD" AND r.bill_date BETWEEN i.regdate AND i.dchdate
            LEFT JOIN rcpt_print r1 ON r1.vn = p2.an AND r1.`status` ="OK" AND r1.department="IPD"
            LEFT JOIN patient p ON p.hn=i.hn
            LEFT JOIN hospcode h ON h.hospcode=ip.hospmain
            WHERE i.dchdate BETWEEN ? AND ?
            AND a.paid_money <>"0" AND a.rcpt_money <> a.paid_money 			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_107::insert([
                'vn'                  => $row->vn,
                'hn'                  => $row->hn,
                'an'                  => $row->an,
                'cid'                 => $row->cid,
                'ptname'              => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'regdate'             => $row->regdate,
                'regtime'             => $row->regtime,
                'dchdate'             => $row->dchdate,
                'dchtime'             => $row->dchtime,
                'pttype'              => $row->pttype,    
                'hospmain'            => $row->hospmain,    
                'hipdata_code'        => $row->hipdata_code,   
                'pdx'                 => $row->pdx,  
                'income'              => $row->income,  
                'paid_money'          => $row->paid_money,
                'rcpt_money'          => $row->rcpt_money,
                'debtor'              => $row->debtor,      
                'status'              => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_107_confirm_iclaim-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_confirm_iclaim(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT * FROM (SELECT w.`name` AS ward,i.regdate,i.regtime,i.dchdate,i.dchtime,i.vn,i.hn,i.an,
                CONCAT(p.pname,p.fname,SPACE(1),p.lname) AS ptname,a.pdx,p.cid,a.age_y,p1.name AS pttype,
                ip.hospmain,p1.hipdata_code,i.adjrw,a.income,a.rcpt_money,IFNULL(SUM(o1.sum_price),0) AS other,
                a.income-a.rcpt_money-IFNULL(SUM(o1.sum_price),0) AS debtor,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p1 ON p1.pttype=ip.pttype
            LEFT JOIN patient p ON p.hn=i.hn
            LEFT JOIN opitemrece o1 ON o1.an=i.an AND o1.icode IN (SELECT icode FROM hrims.lookup_icode WHERE ems ="Y")
            WHERE i.confirm_discharge = "Y" 
            AND p1.pttype = ?
            AND i.dchdate BETWEEN ? AND ?
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_107::insert([
                'vn'                  => $row->vn,
                'hn'                  => $row->hn,
                'an'                  => $row->an,
                'cid'                 => $row->cid,
                'ptname'              => $row->ptname,
                'mobile_phone_number' => $row->mobile_phone_number,
                'regdate'             => $row->regdate,
                'regtime'             => $row->regtime,
                'dchdate'             => $row->dchdate,
                'dchtime'             => $row->dchtime,
                'pttype'              => $row->pttype,    
                'hospmain'            => $row->hospmain,    
                'hipdata_code'        => $row->hipdata_code,   
                'pdx'                 => $row->pdx,  
                'income'              => $row->income,  
                'paid_money'          => $row->paid_money,
                'rcpt_money'          => $row->rcpt_money,
                'debtor'              => $row->debtor,      
                'status'              => $row->status,          
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_107_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_107::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_107_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_update(Request $request, $an)
    {
        $item = Debtor_1102050102_107::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_107_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT d.dchdate AS vstdate ,COUNT(DISTINCT d.vn) AS anvn,
            SUM(debtor) AS debtor,SUM(IFNULL(d.receive,r.bill_amount)) AS receive
            FROM debtor_1102050102_107 d
            LEFT JOIN hosxe.rcpt_print r ON r.vn = d.vn AND r.`status` ="OK" AND r.department="OPD"
            WHERE d.dchdate BETWEEN ? AND ?
            GROUP BY d.dchdate ORDER BY d.dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_107_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050102_107_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_107_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_107_indiv_excel',compact('start_date','end_date','debtor'));
    } 
//1102050102_107_tracking-------------------------------------------------------------------------------------------------------  
    public function _1102050102_107_tracking(Request $request, $an )
    {
        $debtor = DB::select('
            SELECT * FROM debtor_1102050102_107 WHERE an = ?',[$an]);

        $tracking = DB::select('
            SELECT * FROM debtor_1102050102_107_tracking WHERE an = ?',[$an]);

    return view('debtor.1102050102_107_tracking',compact('debtor','tracking'));
    }
//1102050102_107_tracking_insert-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_tracking_insert(Request $request)
    {
        $item = new Debtor_1102050102_107_tracking;
        $item->vn = $request->input('vn');
        $item->an = $request->input('an');
        $item->tracking_date = $request->input('tracking_date');
        $item->tracking_type = $request->input('tracking_type');
        $item->tracking_no = $request->input('tracking_no');
        $item->tracking_officer = $request->input('tracking_officer');
        $item->tracking_note = $request->input('tracking_note');  
        $item->save();  

        return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//_1102050102_107_tracking_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_107_tracking_update(Request $request, $tracking_id)
    {
    Debtor_1102050102_107_tracking::where('tracking_id', $tracking_id)
        ->update([
        'tracking_date' => $request->input('tracking_date'),
        'tracking_type' => $request->input('tracking_type'),
        'tracking_no' => $request->input('tracking_no'),
        'tracking_officer' => $request->input('tracking_officer'),
        'tracking_note' => $request->input('tracking_note')
        ]);

    return redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย');  
    }
##############################################################################################################################################################
//_1102050102_109--------------------------------------------------------------------------------------------------------------
    public function _1102050102_109(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        
        $debtor =  Debtor_1102050102_109::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            });     

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "GOF"            
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_109 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_109',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_109_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.hipdata_code = "GOF"
            AND i.dchdate BETWEEN ? AND ?			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_109::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_109_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_109::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_109_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_update(Request $request, $an)
    {
        $item = Debtor_1102050102_109::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_109_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_109_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM Debtor_1102050102_109 
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_109_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050102_109_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_109_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_109_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050102_603--------------------------------------------------------------------------------------------------------------
    public function _1102050102_603(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search'); 
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        $debtor =  Debtor_1102050102_603::whereBetween('dchdate', [$start_date,$end_date])
            ->where(function ($query) use ($search){
                $query->where('ptname','like','%'.$search.'%');
                $query->orwhere('hn','like','%'.$search.'%');
                $query->orwhere('an','like','%'.$search.'%');
            })
            ->orderBy('dchdate')->get()
            ->map(function ($item) {                        
                if (($item->receive - $item->debtor) >= 0) {
                    $item->days = 0; // เช็คก่อนว่ารับแล้วหรือยัง
                } else {
                    $item->days = Carbon::parse($item->dchdate)->diffInDays(Carbon::today());
                }
                return $item;
            }); 

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.pttype IN ('.$pttype_act.')           
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_603 WHERE an IS NOT NULL)
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_603',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_603_confirm------------------------------------------------------------------------------------------------------- 
    public function _1102050102_603_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $pttype_act = DB::table('main_setting')->where('name', 'pttype_act')->value('value');

        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(other.other_price,0) AS other,a.income-a.rcpt_money-COALESCE(other.other_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS other_list,ict.ipt_coll_status_type_name,i.data_ok ,"ยืนยันลูกหนี้" AS status  
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS other_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
            LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ?  AND li.kidney = "Y"
                GROUP BY op.an) other ON other.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
			AND p.pttype IN ('.$pttype_act.')
            AND i.dchdate BETWEEN ? AND ?			
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an,ip.pttype ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_603::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'other'           => $row->other,  
                'debtor'          => $row->debtor,  
                'status'          => $row->status,            
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_603_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_603_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_603::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//_1102050102_603_update-------------------------------------------------------------------------------------------------------
    public function _1102050102_603_update(Request $request, $an)
    {
        $item = Debtor_1102050102_603::findOrFail($an);
        $item->update([
            'charge_date' => $request->input('charge_date'),
            'charge_no' => $request->input('charge_no'),
            'charge' => $request->input('charge'),
            'receive_date' => $request->input('receive_date'),
            'receive_no' => $request->input('receive_no'),
            'receive' => $request->input('receive'),
            'repno' => $request->input('repno'),
            'status' => $request->input('status'),
        ]);

        return  redirect()->back()->with('success', 'บันทึกข้อมูลเรียบร้อย'); 
    }
//1102050102_603_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_603_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM debtor_1102050102_603 
            WHERE dchdate BETWEEN ? AND ?
            GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_603_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050102_603_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_603_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_603_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050102_802--------------------------------------------------------------------------------------------------------------
    public function _1102050102_802(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        
 
        if ($search) {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,d.income,
                    d.rcpt_money,d.kidney,d.debtor,debtor_lock,s.compensate_treatment+IFNULL(SUM(s1.compensate_kidney),0) AS receive,
                    s.compensate_treatment AS receive_lgo,SUM(s1.compensate_kidney) AS receive_kidney,s.repno,s1.repno AS repno_kidney,
                    CASE WHEN (s.compensate_treatment+IFNULL(SUM(s1.compensate_kidney),0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_802 d    
                LEFT JOIN stm_lgo s ON s.an=d.an
				LEFT JOIN stm_lgo_kidney s1 ON s1.cid=d.cid AND s1.datetimeadm BETWEEN d.regdate AND d.dchdate
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$search,$search,$search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,d.income,
                    d.rcpt_money,d.kidney,d.debtor,debtor_lock,s.compensate_treatment+IFNULL(SUM(s1.compensate_kidney),0) AS receive,
                    s.compensate_treatment AS receive_lgo,SUM(s1.compensate_kidney) AS receive_kidney,s.repno,s1.repno AS repno_kidney,
                    CASE WHEN (s.compensate_treatment+IFNULL(SUM(s1.compensate_kidney),0)) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_802 d    
                LEFT JOIN stm_lgo s ON s.an=d.an
				LEFT JOIN stm_lgo_kidney s1 ON s1.cid=d.cid AND s1.datetimeadm BETWEEN d.regdate AND d.dchdate
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an', [$start_date,$end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,a.income-a.rcpt_money-COALESCE(kidney.kidney_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
				LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y" 
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "LGO" 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_802 WHERE an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_802',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_802_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_802_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,a.income-a.rcpt_money-COALESCE(kidney.kidney_price,0) AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
				LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y" 
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code = "LGO" 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_802::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'kidney'          => $row->kidney,  
                'debtor'          => $row->debtor,           
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_802_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_802_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_802::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }
//1102050102_802_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_802_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,SUM(debtor) AS debtor,SUM(receive) AS receive
            FROM (SELECT d.hn,d.an,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,d.income,
            d.rcpt_money,d.kidney,d.debtor,debtor_lock,s.compensate_treatment+IFNULL(SUM(s1.compensate_kidney),0) AS receive,
            s.compensate_treatment AS receive_lgo,SUM(s1.compensate_kidney) AS receive_kidney,s.repno,s1.repno AS repno_kidney
            FROM debtor_1102050102_802 d    
            LEFT JOIN stm_lgo s ON s.an=d.an
			LEFT JOIN stm_lgo_kidney s1 ON s1.cid=d.cid AND s1.datetimeadm BETWEEN d.regdate AND d.dchdate
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a
		    GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_802_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050102_802_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_802_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_802_indiv_excel',compact('start_date','end_date','debtor'));
    } 
##############################################################################################################################################################
//_1102050102_804--------------------------------------------------------------------------------------------------------------
    public function _1102050102_804(Request $request )
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        $search  =  $request->search ?: Session::get('search');        
 
        if ($search) {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,d.income,
                    d.rcpt_money,d.kidney,d.debtor,d.debtor_lock,s.receive_total AS receive,s.repno,
                    CASE WHEN IFNULL(s.receive_total, 0) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_804 d    
                LEFT JOIN hrims.stm_ofc s ON s.an=d.an                
                WHERE (d.ptname LIKE CONCAT("%", ?, "%") OR d.hn LIKE CONCAT("%", ?, "%") OR d.an LIKE CONCAT("%", ?, "%"))
                AND d.dchdate BETWEEN ? AND ?
                GROUP BY d.an', [$search,$search,$search,$start_date,$end_date]);
        } else {
            $debtor = DB::select('
                SELECT d.hn,d.an,d.ptname,d.pttype,d.regdate,d.regtime,d.dchdate,d.dchtime,d.pdx,d.adjrw,d.income,
                    d.rcpt_money,d.kidney,d.debtor,debtor_lock,s.receive_total AS receive,s.repno,
                    CASE WHEN IFNULL(s.receive_total, 0) - IFNULL(d.debtor, 0)>= 0 
                    THEN 0 ELSE DATEDIFF(CURDATE(), d.dchdate) END AS days
                FROM debtor_1102050102_804 d    
                LEFT JOIN hrims.stm_ofc s ON s.an=d.an                
                WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an', [$start_date,$end_date]);
        }

        $debtor_search = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,a.income-a.rcpt_money AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
				LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y" 
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code IN ("BKK","BMT") 
            AND i.dchdate BETWEEN ? AND ?
            AND i.an NOT IN (SELECT an FROM hrims.debtor_1102050102_804 WHERE an IS NOT NULL) 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 

        $request->session()->put('start_date',$start_date);
        $request->session()->put('end_date',$end_date);
        $request->session()->put('search',$search);
        $request->session()->put('debtor',$debtor);
        $request->session()->save();

        return view('debtor.1102050102_804',compact('start_date','end_date','search','debtor','debtor_search'));
    }
//_1102050102_804_confirm-------------------------------------------------------------------------------------------------------
    public function _1102050102_804_confirm(Request $request )
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date'); 
        $request->validate([
        'checkbox' => 'required|array',
        ], [
            'checkbox.required' => 'กรุณาเลือกรายการที่ต้องการยืนยันลูกหนี้'
        ]);
        $checkbox = $request->input('checkbox'); // รับ array
        $checkbox_string = implode(",", $checkbox); // แปลงเป็น string สำหรับ SQL IN
       
        $debtor = DB::connection('hosxp')->select('
            SELECT w.`name` AS ward,i.hn,pt.cid,i.vn,i.an,CONCAT(pt.pname,pt.fname,SPACE(1),pt.lname) AS ptname,a.age_y,
                p.`name` AS pttype,p.hipdata_code,ip.hospmain,i.regdate,i.regtime,i.dchdate,i.dchtime,a.pdx,i.adjrw,
                a.income,a.rcpt_money,COALESCE(kidney.kidney_price,0) AS kidney,a.income-a.rcpt_money AS debtor,
                GROUP_CONCAT(DISTINCT s.`name`) AS kidney_list,ict.ipt_coll_status_type_name,i.data_ok 
            FROM ipt i 
            LEFT JOIN patient pt ON pt.hn=i.hn
            LEFT JOIN ipt_pttype ip ON ip.an=i.an
            LEFT JOIN pttype p ON p.pttype=ip.pttype
            LEFT JOIN ward w ON w.ward=i.ward
            LEFT JOIN an_stat a ON a.an=i.an
            LEFT JOIN (SELECT op.an, SUM(op.sum_price) AS kidney_price	
                FROM opitemrece op INNER JOIN ipt ON ipt.an=op.an
				LEFT JOIN hrims.lookup_icode li ON op.icode = li.icode
                LEFT JOIN nondrugitems n ON n.icode=op.icode 
                WHERE op.an IS NOT NULL AND ipt.dchdate BETWEEN ? AND ? AND li.kidney = "Y" 
                GROUP BY op.an) kidney ON kidney.an=i.an
            LEFT JOIN opitemrece o ON o.an=i.an AND o.icode IN (SELECT icode FROM hrims.lookup_icode WHERE kidney = "Y")
            LEFT JOIN s_drugitems s ON s.icode=o.icode
            LEFT JOIN ipt_coll_stat ic ON ic.an=i.an
            LEFT JOIN ipt_coll_status_type ict ON ict.ipt_coll_status_type_id=ic.ipt_coll_status_type_id
            WHERE i.confirm_discharge = "Y" 
            AND p.hipdata_code IN ("BKK","BMT")
            AND i.dchdate BETWEEN ? AND ?
            AND i.an IN ('.$checkbox_string.') 
            GROUP BY i.an ORDER BY i.ward,i.dchdate',[$start_date,$end_date,$start_date,$end_date]); 
        
        foreach ($debtor as $row) {
            Debtor_1102050102_804::insert([
                'an'              => $row->an,
                'vn'              => $row->vn,
                'hn'              => $row->hn,
                'cid'             => $row->cid,
                'ptname'          => $row->ptname,
                'regdate'         => $row->regdate,
                'regtime'         => $row->regtime,
                'dchdate'         => $row->dchdate,
                'dchtime'         => $row->dchtime,    
                'pttype'          => $row->pttype,    
                'hospmain'        => $row->hospmain,    
                'hipdata_code'    => $row->hipdata_code,   
                'pdx'             => $row->pdx,  
                'adjrw'           => $row->adjrw,  
                'income'          => $row->income,  
                'rcpt_money'      => $row->rcpt_money, 
                'kidney'          => $row->kidney,  
                'debtor'          => $row->debtor,           
            ]);            
        }

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะยืนยัน'
            ]);
        }

        return redirect()->back()->with('success', 'ยืนยันลูกหนี้สำเร็จ');
    }
//_1102050102_804_delete-------------------------------------------------------------------------------------------------------
    public function _1102050102_804_delete(Request $request )
    {
        $checkbox = $request->input('checkbox_d');        

        $deleted = Debtor_1102050102_804::whereIn('an', $checkbox)
            ->whereNull('debtor_lock')
            ->delete();

        if (empty($checkbox) || !is_array($checkbox)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเลือกรายการที่จะลบ'
            ]);
        }

        return redirect()->back()->with('success', 'ลบลูกหนี้เรียบร้อย ');
    }

//1102050102_804_daily_pdf-------------------------------------------------------------------------------------------------------
    public function _1102050102_804_daily_pdf(Request $request)
    {
        $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = DB::select('
            SELECT dchdate AS vstdate,COUNT(DISTINCT an) AS anvn,
            SUM(debtor) AS debtor,SUM(receive_total) AS receive
            FROM (SELECT d.*,s.receive_total
            FROM debtor_1102050102_804 d    
            LEFT JOIN hrims.stm_ofc s ON s.an=d.an            
            WHERE d.dchdate BETWEEN ? AND ? GROUP BY d.an) AS a 
		GROUP BY dchdate ORDER BY dchdate',[$start_date,$end_date]);

        $pdf = PDF::loadView('debtor.1102050102_804_daily_pdf', compact('hospital_name','hospital_code','start_date','end_date','debtor'))
                    ->setPaper('A4', 'portrait');
        return @$pdf->stream();
    }
//1102050102_804_indiv_excel-------------------------------------------------------------------------------------------------------   
    public function _1102050102_804_indiv_excel(Request $request)
    {
        $start_date = Session::get('start_date');
        $end_date = Session::get('end_date');
        $debtor = Session::get('debtor');
        
        return view('debtor.1102050102_804_indiv_excel',compact('start_date','end_date','debtor'));
    } 

}
