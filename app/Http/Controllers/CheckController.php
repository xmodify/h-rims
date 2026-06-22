<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Drugcat_nhso;
use App\Models\Drugcat_chi;
use App\Models\Drugcat_fdh;
use App\Models\Labcat_nhso;
use App\Models\Labcat_chi;


class CheckController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_check !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'ตรวจสอบข้อมูล'], 403);
                }
                return $next($request);
            }
        ]);
    }
    ###################################################################################################################################################
    //ข้อมูลปิดสิทธิ สปสช---------------------------------------------------------------------------------------------------------------------------
    public function nhso_endpoint(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        // 1. Closed Records (Visits that have an EP prefix in HOSxP or RiMS)
        $closed = DB::connection('hosxp')->select('
            SELECT pt.fname AS firstName, pt.lname AS lastName, pt.cid, 
                   COALESCE(ep.subInsclName, p.name) as subInsclName, ep.subInscl,
                   CONCAT(o.vstdate, " ", o.vsttime) as serviceDateTime,
                   COALESCE(ep.claimType, "") as claimType,
                   COALESCE(ep.claimCode, vp.auth_code) as claimCode
            FROM ovst o
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
                 AND (ep.claim_status = "success" OR ep.claimCode LIKE "EP%")
            WHERE o.vstdate BETWEEN ? AND ?
            AND (vp.auth_code LIKE "EP%" OR ep.claimCode LIKE "EP%")        
            AND (o.an = "" OR o.an IS NULL)
            ORDER BY o.vstdate DESC, o.vsttime DESC', [$start_date, $end_date]);

        $pending = DB::connection('hosxp')->select('
            SELECT o.vn, pt.cid, pt.hn, CONCAT(pt.pname, pt.fname, pt.lname) AS ptname, pt.mobile_phone_number,
                   p.name AS subInsclName, o.vstdate, o.vsttime, o.oqueue, vp.hospmain, vs.pdx, vs.income, 
                   vs.paid_money,vs.rcpt_money,vs.uc_money as debtor,
                   CONCAT(o.vstdate, " ", o.vsttime) as serviceDateTime, vp.auth_code AS claimCode
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype_number = 1
            LEFT JOIN pttype p ON p.pttype = vp.pttype
            LEFT JOIN vn_stat vs ON vs.vn = o.vn
            LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate 
                 AND (ep.claim_status = "success" OR ep.claimCode LIKE "EP%" OR ep.claimType = "PG0140001")
            LEFT JOIN (
                SELECT ori.vn FROM opitemrece ori 
                INNER JOIN hrims.lookup_icode li ON li.icode = ori.icode 
                WHERE li.kidney = "Y" AND ori.vstdate BETWEEN ? AND ?
                GROUP BY ori.vn
            ) kidney ON kidney.vn = o.vn
            WHERE o.vstdate BETWEEN ? AND ?
            AND (o.an = "" OR o.an IS NULL)
            AND vs.uc_money > 0
            AND p.hipdata_code IN ("UCS","OFC","SSS","LGO","NHS","STP","BKK","BMT","SRT","KKT","PTY")
            AND (vp.auth_code NOT LIKE "EP%" OR vp.auth_code IS NULL)
            AND ep.cid IS NULL
            AND kidney.vn IS NULL
            ORDER BY o.vstdate DESC, o.vsttime DESC', 
            [$start_date, $end_date, $start_date, $end_date]);

        return view('check.nhso_endpoint', compact('start_date', 'end_date', 'closed', 'pending'));
    }
    ###################################################################################################################################################
    //ข้อมูล FDH Claim Status---------------------------------------------------------------------------------------------------------------------------
    public function fdh_claim_status(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        // อัปเดตค่าเก็บใน Session เผื่อครั้งถัดไป
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        $sql = DB::connection('hosxp')->select('
            SELECT fdh.*
            FROM ovst o
            INNER JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn						
            WHERE o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn
            UNION
            SELECT fdh.*
            FROM ipt i
            INNER JOIN hrims.fdh_claim_status fdh ON fdh.an = i.an						
            WHERE i.dchdate BETWEEN ? AND ?
            GROUP BY i.an', [$start_date, $end_date, $start_date, $end_date]);

        return view('check.fdh_claim_status', compact('start_date', 'end_date', 'sql'));
    }
    ####################################################################################################################################
    //นำเข้า Drug Catalog-----------------------------------------------------------------------------------------------------------------

    public function drugcat_nhso_save(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        Drugcat_nhso::truncate();

        $this->validate($request, [
            'file' => 'required|file'
        ]);
        $the_file = $request->file('file');
        if (!in_array(strtolower($the_file->getClientOriginalExtension()), ['xls', 'xlsx'])) {
            return back()->withErrors('กรุณาเลือกเฉพาะไฟล์นามสกุล .xls หรือ .xlsx เท่านั้น');
        }
        $file_name = $the_file->getClientOriginalName(); //ชื่อไฟล์

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            // $sheet        = $spreadsheet->getActiveSheet();
            $sheet        = $spreadsheet->setActiveSheetIndex(0);
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range('2', $row_limit);
            // $row_range    = range( "!", $row_limit );
            $column_range = range('Y', $column_limit);
            $startcount = '2';
            // $row_range_namefile  = range( 9, $sheet->getCell( 'A' . $row )->getValue() );
            $data = array();
            foreach ($row_range as $row) {

                $dc = $sheet->getCell('S' . $row)->getValue();
                $dcday = substr($dc, 0, 2);
                $dcmo = substr($dc, 3, 2);
                $dcyear = substr($dc, 8, 4);
                $datechange = $dcyear . '-' . $dcmo . '-' . $dcday;

                $du = $sheet->getCell('T' . $row)->getValue();
                $duday = substr($du, 0, 2);
                $dumo = substr($du, 3, 2);
                $duyear = substr($du, 8, 4);
                $dateupdate = $duyear . '-' . $dumo . '-' . $duday;

                $de = $sheet->getCell('U' . $row)->getValue();
                $deday = substr($de, 0, 2);
                $demo = substr($de, 3, 2);
                $deyear = substr($de, 8, 4);
                $dateeffective = $deyear . '-' . $demo . '-' . $deday;

                $da = $sheet->getCell('X' . $row)->getValue();
                $daday = substr($da, 0, 2);
                $damo = substr($da, 3, 2);
                $dayear = substr($da, 8, 4);
                $date_approved = $dayear . '-' . $damo . '-' . $daday;

                $data[] = [
                    'hospdrugcode'      => $sheet->getCell('A' . $row)->getValue(),
                    'productcat'        => $sheet->getCell('B' . $row)->getValue(),
                    'tmtid'             => $sheet->getCell('C' . $row)->getValue(),
                    'specprep'          => $sheet->getCell('D' . $row)->getValue(),
                    'genericname'       => $sheet->getCell('E' . $row)->getValue(),
                    'tradename'         => $sheet->getCell('F' . $row)->getValue(),
                    'dfscode'           => $sheet->getCell('G' . $row)->getValue(),
                    'dosageform'        => $sheet->getCell('H' . $row)->getValue(),
                    'strength'          => $sheet->getCell('I' . $row)->getValue(),
                    'content'           => $sheet->getCell('J' . $row)->getValue(),
                    'unitprice'         => $sheet->getCell('K' . $row)->getValue(),
                    'distributor'       => $sheet->getCell('L' . $row)->getValue(),
                    'manufacturer'      => $sheet->getCell('M' . $row)->getValue(),
                    'ised'              => $sheet->getCell('N' . $row)->getValue(),
                    'ndc24'             => $sheet->getCell('O' . $row)->getValue(),
                    'packsize'          => $sheet->getCell('P' . $row)->getValue(),
                    'packprice'         => $sheet->getCell('Q' . $row)->getValue(),
                    'updateflag'        => $sheet->getCell('R' . $row)->getValue(),
                    'datechange'        => $datechange,
                    'dateupdate'        => $dateupdate,
                    'dateeffective'     => $dateeffective,
                    'ised_approved'     => $sheet->getCell('V' . $row)->getValue(),
                    'ndc24_approved'    => $sheet->getCell('W' . $row)->getValue(),
                    'date_approved'     => $date_approved,
                    'ised_status'       => $sheet->getCell('Y' . $row)->getValue(),
                    'stm_filename'      => $file_name,
                ];
                $startcount++;
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $key => $data_) {
                Drugcat_nhso::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.drugcat_nhso')->with('success', $file_name);
    }
    //Drug ทั้งหมดใน HOSxP-----------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug ไม่พบที่ NHSO----------------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso_non_nhso()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.hospdrugcode IS NULL  
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug Catalog ราคาไม่ตรงกับ HOSxP-------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso_price_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm    
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.unitprice <> d.unitprice
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug Catalog รหัส TMT ไม่ตรงกับ HOSxP
    public function drugcat_nhso_tmt_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm   
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.tmtid <> d3.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug Catalog รหัส 24 หลักไม่ตรงกับ HOSxP---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso_code24_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm 
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.ndc24 <> d2.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug Catalog ยาสมุนไพร---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso_herb()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d2.ref_code LIKE '4%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug Catalog บัญชียาหลักไม่ตรงกัน (ED/NED Mismatch)-----------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso_ised_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND nd.hospdrugcode IS NOT NULL 
              AND CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END <> CASE WHEN (nd.ised LIKE 'E%') THEN 'E' ELSE 'N' END
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug Catalog ลืมผูกรหัส 24 หลักใน HOSxP-----------------------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso_code24_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND (d2.ref_code IS NULL OR d2.ref_code = '') 
              AND nd.ndc24 IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }
    //Drug Catalog ลืมผูกรหัส TMT ใน HOSxP-----------------------------------------------------------------------------------------------------------------------------
    public function drugcat_nhso_tmt_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND (d3.ref_code IS NULL OR d3.ref_code = '') 
              AND nd.tmtid IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_nhso', compact('drug'));
    }

    //นำเข้า Drug Catalog สกส.-----------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_save(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        Drugcat_chi::truncate();

        $this->validate($request, [
            'file' => 'required|file'
        ]);
        $the_file = $request->file('file');
        if (!in_array(strtolower($the_file->getClientOriginalExtension()), ['xls', 'xlsx'])) {
            return back()->withErrors('กรุณาเลือกเฉพาะไฟล์นามสกุล .xls หรือ .xlsx เท่านั้น');
        }
        $file_name = $the_file->getClientOriginalName(); //ชื่อไฟล์

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->setActiveSheetIndex(0);
            $row_limit    = $sheet->getHighestDataRow();
            $row_range    = range('5', $row_limit);

            $parseExcelDate = function ($value) {
                if (empty($value) || $value === '-' || trim($value) === '') {
                    return null;
                }
                $value = trim($value);
                if (is_numeric($value)) {
                    try {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
                foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y'] as $format) {
                    try {
                        return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // continue
                    }
                }
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            };

            $cleanExcelRate = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = array();
            foreach ($row_range as $row) {
                $hospdrugcode = $sheet->getCell('B' . $row)->getValue();
                if (empty($hospdrugcode)) {
                    continue;
                }

                $unitprice = $cleanExcelRate($sheet->getCell('L' . $row)->getValue());
                
                $datechange = $parseExcelDate($sheet->getCell('T' . $row)->getValue());
                $dateupdate = $parseExcelDate($sheet->getCell('U' . $row)->getValue());
                $dateeffective = $parseExcelDate($sheet->getCell('V' . $row)->getValue());
                $date_approved = $parseExcelDate($sheet->getCell('W' . $row)->getValue());

                $data[] = [
                    'hospdrugcode'      => $hospdrugcode,
                    'productcat'        => $sheet->getCell('C' . $row)->getValue(),
                    'tmtid'             => $sheet->getCell('D' . $row)->getValue(),
                    'specprep'          => $sheet->getCell('E' . $row)->getValue(),
                    'genericname'       => $sheet->getCell('F' . $row)->getValue(),
                    'tradename'         => $sheet->getCell('G' . $row)->getValue(),
                    'dfscode'           => $sheet->getCell('H' . $row)->getValue(),
                    'dosageform'        => $sheet->getCell('I' . $row)->getValue(),
                    'strength'          => $sheet->getCell('J' . $row)->getValue(),
                    'content'           => $sheet->getCell('K' . $row)->getValue(),
                    'unitprice'         => $unitprice,
                    'distributor'       => $sheet->getCell('M' . $row)->getValue(),
                    'manufacturer'      => $sheet->getCell('N' . $row)->getValue(),
                    'ised'              => $sheet->getCell('O' . $row)->getValue(),
                    'ndc24'             => $sheet->getCell('P' . $row)->getValue(),
                    'packsize'          => $sheet->getCell('Q' . $row)->getValue(),
                    'packprice'         => $sheet->getCell('R' . $row)->getValue(),
                    'updateflag'        => $sheet->getCell('S' . $row)->getValue(),
                    'datechange'        => $datechange,
                    'dateupdate'        => $dateupdate,
                    'dateeffective'     => $dateeffective,
                    'date_approved'     => $date_approved,
                    'ised_status'       => $sheet->getCell('X' . $row)->getValue(),
                    'stm_filename'      => $file_name,
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $key => $data_) {
                Drugcat_chi::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.drugcat_chi')->with('success', $file_name);
    }

    //Drug ทั้งหมดใน HOSxP (CSMBS)-----------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_chi()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug ไม่พบที่ สกส.----------------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_non_nhso()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.hospdrugcode IS NULL  
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug Catalog ราคาไม่ตรงกับ HOSxP (CSMBS)-------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_price_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm    
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.unitprice <> d.unitprice
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug Catalog รหัส TMT ไม่ตรงกับ HOSxP (CSMBS)
    public function drugcat_chi_tmt_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm   
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.tmtid <> d3.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug Catalog รหัส 24 หลักไม่ตรงกับ HOSxP (CSMBS)---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_code24_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm 
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.ndc24 <> d2.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug Catalog ยาสมุนไพร (CSMBS)---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_herb()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d2.ref_code LIKE '4%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug Catalog บัญชียาหลักไม่ตรงกัน (ED/NED Mismatch - CSMBS)-----------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_ised_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND nd.hospdrugcode IS NOT NULL 
              AND CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END <> CASE WHEN (nd.ised LIKE 'E%') THEN 'E' ELSE 'N' END
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug Catalog ลืมผูกรหัส 24 หลักใน HOSxP (CSMBS)-----------------------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_code24_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND (d2.ref_code IS NULL OR d2.ref_code = '') 
              AND nd.ndc24 IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //Drug Catalog ลืมผูกรหัส TMT ใน HOSxP (CSMBS)-----------------------------------------------------------------------------------------------------------------------------
    public function drugcat_chi_tmt_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND (d3.ref_code IS NULL OR d3.ref_code = '') 
              AND nd.tmtid IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_chi', compact('drug'));
    }

    //ส่งออกรายการใหม่ สกส (กรณีไม่พบที่ สกส) - UpdateFlag = A-------------------------------------------------------------------------------------------------
    public function drugcat_chi_export_new(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcel([], $hosp_code . 'DrugN' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";
        
        $drugs = DB::connection('hosxp')->select("
            SELECT  
                d.icode AS HospDrugCode,
                d.sks_product_category_id AS ProductCat,
                d.sks_drug_code AS TMTID,
                IFNULL(s.SpecPrep, '') AS SpecPrep,
                IFNULL(d.generic_name, d.`name`) AS GenericName,
                IFNULL(d.trade_name, s.TradeName) AS TradeName,
                IFNULL(d.sks_dfs_code, s.DSFCode) AS DFSCode,
                IFNULL(d.dosageform, s.DosageForm) AS DosageForm,
                IFNULL(d.strength, s.Strength) AS Strength,
                IFNULL(d.dosageform, s.DosageForm) AS Content,
                d.unitprice AS UnitPrice,
                dr.comp AS Distributor,
                CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN tc.manufacturer ELSE dr.manufacturer END AS Manufacturer,
                CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' WHEN d.drugaccount <> '' THEN 'E' END AS ISED,
                d.did AS NDC24,
                CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE p.provis_medication_unit_name END AS Packsize,
                d.unitprice AS Packprice,
                'A' AS UpdateFlag,
                '' AS DateChange,
                '' AS DateUpdate,
                DATE_FORMAT(IFNULL(nd.dateeffective, IFNULL(s.DateEffective, IFNULL(d.last_update, NOW()))), '%d/%m/%Y') AS DateEffective,
                NULL AS Reimbprice
            FROM drugitems d
            LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = d.sks_drug_code
            LEFT JOIN drugitems_register_unique dr ON dr.std_code = d.did
            LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
            LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
              {$where_icode}
            ORDER BY d.icode
        ");

        return $this->exportToExcel($drugs, $hosp_code . 'DrugN' . $seq . '.xlsx');
    }

    //ส่งออกรายการแก้ไข สกส (กรณีต้องการแก้ไขข้อมูลผลิตภัณฑ์) - UpdateFlag = E--------------------------------------------------------------------------------------
    public function drugcat_chi_export_edit(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcel([], $hosp_code . 'DrugN' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";

        $drugs = DB::connection('hosxp')->select("
            SELECT  
                d.icode AS HospDrugCode,
                d.sks_product_category_id AS ProductCat,
                d.sks_drug_code AS TMTID,
                IFNULL(s.SpecPrep, '') AS SpecPrep,
                IFNULL(d.generic_name, d.`name`) AS GenericName,
                IFNULL(d.trade_name, s.TradeName) AS TradeName,
                IFNULL(d.sks_dfs_code, s.DSFCode) AS DFSCode,
                IFNULL(d.dosageform, s.DosageForm) AS DosageForm,
                IFNULL(d.strength, s.Strength) AS Strength,
                IFNULL(d.dosageform, s.DosageForm) AS Content,
                d.unitprice AS UnitPrice,
                dr.comp AS Distributor,
                CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN tc.manufacturer ELSE dr.manufacturer END AS Manufacturer,
                CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' WHEN d.drugaccount <> '' THEN 'E' END AS ISED,
                d.did AS NDC24,
                CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE p.provis_medication_unit_name END AS Packsize,
                d.unitprice AS Packprice,
                'E' AS UpdateFlag,
                DATE_FORMAT(IFNULL(d.last_update, NOW()), '%d/%m/%Y') AS DateChange,
                '' AS DateUpdate,
                DATE_FORMAT(CASE 
                    WHEN nd.dateeffective IS NOT NULL AND nd.dateeffective >= CURRENT_DATE() THEN DATE_ADD(nd.dateeffective, INTERVAL 1 DAY)
                    WHEN nd.dateeffective IS NOT NULL THEN CURRENT_DATE()
                    WHEN s.DateEffective IS NOT NULL AND s.DateEffective >= CURRENT_DATE() THEN DATE_ADD(s.DateEffective, INTERVAL 1 DAY)
                    ELSE CURRENT_DATE()
                END, '%d/%m/%Y') AS DateEffective,
                NULL AS Reimbprice
            FROM drugitems d
            LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = d.sks_drug_code
            LEFT JOIN drugitems_register_unique dr ON dr.std_code = d.did
            LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
            LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
              {$where_icode}
            ORDER BY d.icode
        ");

        return $this->exportToExcel($drugs, $hosp_code . 'DrugN' . $seq . '.xlsx');
    }

    //ส่งออกรายการแก้ไข สกส (กรณีต้องการแก้ไขราคาส่งขาย) - UpdateFlag = U--------------------------------------------------------------------------------------
    public function drugcat_chi_export_update(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcel([], $hosp_code . 'DrugN' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";

        $drugs = DB::connection('hosxp')->select("
            SELECT  
                d.icode AS HospDrugCode,
                d.sks_product_category_id AS ProductCat,
                d.sks_drug_code AS TMTID,
                IFNULL(s.SpecPrep, '') AS SpecPrep,
                IFNULL(d.generic_name, d.`name`) AS GenericName,
                IFNULL(d.trade_name, s.TradeName) AS TradeName,
                IFNULL(d.sks_dfs_code, s.DSFCode) AS DFSCode,
                IFNULL(d.dosageform, s.DosageForm) AS DosageForm,
                IFNULL(d.strength, s.Strength) AS Strength,
                IFNULL(d.dosageform, s.DosageForm) AS Content,
                d.unitprice AS UnitPrice,
                dr.comp AS Distributor,
                CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN tc.manufacturer ELSE dr.manufacturer END AS Manufacturer,
                CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' WHEN d.drugaccount <> '' THEN 'E' END AS ISED,
                d.did AS NDC24,
                CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE p.provis_medication_unit_name END AS Packsize,
                d.unitprice AS Packprice,
                'U' AS UpdateFlag,
                '' AS DateChange,
                DATE_FORMAT(IFNULL(d.lastupdatestdprice, NOW()), '%d/%m/%Y') AS DateUpdate,
                DATE_FORMAT(CASE 
                    WHEN nd.dateeffective IS NOT NULL AND nd.dateeffective >= CURRENT_DATE() THEN DATE_ADD(nd.dateeffective, INTERVAL 1 DAY)
                    WHEN nd.dateeffective IS NOT NULL THEN CURRENT_DATE()
                    WHEN s.DateEffective IS NOT NULL AND s.DateEffective >= CURRENT_DATE() THEN DATE_ADD(s.DateEffective, INTERVAL 1 DAY)
                    ELSE CURRENT_DATE()
                END, '%d/%m/%Y') AS DateEffective,
                NULL AS Reimbprice
            FROM drugitems d
            LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = d.sks_drug_code
            LEFT JOIN drugitems_register_unique dr ON dr.std_code = d.did
            LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
            LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
              {$where_icode}
            ORDER BY d.icode
        ");

        return $this->exportToExcel($drugs, $hosp_code . 'DrugN' . $seq . '.xlsx');
    }

    public function drugcat_chi_export_preview(Request $request)
    {
        $type = $request->input('type', 'new');
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการยาอย่างน้อย 1 รายการ', 'data' => []]);
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";
        
        $updateFlag = 'A';
        $dateChangeExpr = "'' AS DateChange";
        $dateUpdateExpr = "'' AS DateUpdate";
        $dateEffectiveExpr = "DATE_FORMAT(IFNULL(nd.dateeffective, IFNULL(s.DateEffective, IFNULL(d.last_update, NOW()))), '%d/%m/%Y') AS DateEffective";
        
        if ($type === 'edit') {
            $updateFlag = 'E';
            $dateChangeExpr = "DATE_FORMAT(IFNULL(d.last_update, NOW()), '%d/%m/%Y') AS DateChange";
            $dateEffectiveExpr = "DATE_FORMAT(CASE 
                WHEN nd.dateeffective IS NOT NULL AND nd.dateeffective >= CURRENT_DATE() THEN DATE_ADD(nd.dateeffective, INTERVAL 1 DAY)
                WHEN nd.dateeffective IS NOT NULL THEN CURRENT_DATE()
                WHEN s.DateEffective IS NOT NULL AND s.DateEffective >= CURRENT_DATE() THEN DATE_ADD(s.DateEffective, INTERVAL 1 DAY)
                ELSE CURRENT_DATE()
            END, '%d/%m/%Y') AS DateEffective";
        } elseif ($type === 'update') {
            $updateFlag = 'U';
            $dateUpdateExpr = "DATE_FORMAT(IFNULL(d.lastupdatestdprice, NOW()), '%d/%m/%Y') AS DateUpdate";
            $dateEffectiveExpr = "DATE_FORMAT(CASE 
                WHEN nd.dateeffective IS NOT NULL AND nd.dateeffective >= CURRENT_DATE() THEN DATE_ADD(nd.dateeffective, INTERVAL 1 DAY)
                WHEN nd.dateeffective IS NOT NULL THEN CURRENT_DATE()
                WHEN s.DateEffective IS NOT NULL AND s.DateEffective >= CURRENT_DATE() THEN DATE_ADD(s.DateEffective, INTERVAL 1 DAY)
                ELSE CURRENT_DATE()
            END, '%d/%m/%Y') AS DateEffective";
        }

        try {
            $drugs = DB::connection('hosxp')->select("
                SELECT  
                    d.icode AS HospDrugCode,
                    d.sks_product_category_id AS ProductCat,
                    d.sks_drug_code AS TMTID,
                    IFNULL(s.SpecPrep, '') AS SpecPrep,
                    IFNULL(d.generic_name, d.`name`) AS GenericName,
                    IFNULL(d.trade_name, s.TradeName) AS TradeName,
                    IFNULL(d.sks_dfs_code, s.DSFCode) AS DFSCode,
                    IFNULL(d.dosageform, s.DosageForm) AS DosageForm,
                    IFNULL(d.strength, s.Strength) AS Strength,
                    IFNULL(d.dosageform, s.DosageForm) AS Content,
                    d.unitprice AS UnitPrice,
                    dr.comp AS Distributor,
                    CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN tc.manufacturer ELSE dr.manufacturer END AS Manufacturer,
                    CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' WHEN d.drugaccount <> '' THEN 'E' END AS ISED,
                    d.did AS NDC24,
                    CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE p.provis_medication_unit_name END AS Packsize,
                    d.unitprice AS Packprice,
                    '{$updateFlag}' AS UpdateFlag,
                    {$dateChangeExpr},
                    {$dateUpdateExpr},
                    {$dateEffectiveExpr},
                    NULL AS Reimbprice
                FROM drugitems d
                LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = d.sks_drug_code
                LEFT JOIN drugitems_register_unique dr ON dr.std_code = d.did
                LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
                LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode
                LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE dc.date_approved = (SELECT MAX(dc1.date_approved) 
                    FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode
                WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
                  {$where_icode}
                ORDER BY d.icode
            ");
            
            return response()->json([
                'success' => true,
                'data' => $drugs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลตัวอย่าง: ' . $e->getMessage()
            ]);
        }
    }

    //ฟังก์ชันหลักในการแปลงข้อมูล SQL เป็น Excel ตาม format สกส (CSMBS)---------------------------------------------------------------------------------
    private function exportToExcel($data, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'HOSPDRUGCODE', 'PRODUCTCAT', 'TMTID', 'SPECPREP', 'GENERICNAME', 'TRADENAME', 
            'DFSCODE', 'DOSAGEFORM', 'STRENGTH', 'CONTENT', 'UNITPRICE', 'DISTRIBUTOR', 
            'MANUFACTURER', 'ISED', 'NDC24', 'PACKSIZE', 'PACKPRICE', 'UPDATEFLAG', 
            'DATECHANGE', 'DATEUPDATE', 'DATEEFFECTIVE', 'Reimbprice'
        ];
        
        // Write headers
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        // Write data
        $rowNum = 2;
        foreach ($data as $row) {
            $colIndex = 1;
            foreach ((array)$row as $key => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $rowNum, $val);
                $colIndex++;
            }
            $rowNum++;
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    //นำเข้า Drug Catalog FDH-----------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_save(Request $request)
    {
        set_time_limit(300);

        Drugcat_fdh::truncate();

        $this->validate($request, [
            'file' => 'required|file'
        ]);
        $the_file = $request->file('file');
        if (!in_array(strtolower($the_file->getClientOriginalExtension()), ['xls', 'xlsx'])) {
            return back()->withErrors('กรุณาเลือกเฉพาะไฟล์นามสกุล .xls หรือ .xlsx เท่านั้น');
        }
        $file_name = $the_file->getClientOriginalName();

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->setActiveSheetIndex(0);
            $row_limit    = $sheet->getHighestDataRow();
            $row_range    = range('6', $row_limit);

            $parseExcelDate = function ($value) {
                if (empty($value) || $value === '-' || trim($value) === '') {
                    return null;
                }
                $value = trim($value);
                if (is_numeric($value)) {
                    try {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
                foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d\TH:i:s\Z', 'Y-m-d H:i:s', 'd-m-Y H:i'] as $format) {
                    try {
                        return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // continue
                    }
                }
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            };

            $cleanExcelRate = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = array();
            foreach ($row_range as $row) {
                $hospdrugcode = $sheet->getCell('B' . $row)->getValue();
                if (empty($hospdrugcode)) {
                    continue;
                }

                $unitprice = $cleanExcelRate($sheet->getCell('I' . $row)->getValue());
                $packprice = $cleanExcelRate($sheet->getCell('P' . $row)->getValue());
                
                $date_import = $parseExcelDate($sheet->getCell('Q' . $row)->getValue());
                $datechange = $parseExcelDate($sheet->getCell('R' . $row)->getValue());
                $dateupdate = $parseExcelDate($sheet->getCell('S' . $row)->getValue());
                $dateeffective = $parseExcelDate($sheet->getCell('T' . $row)->getValue());

                $data[] = [
                    'hospdrugcode'      => $hospdrugcode,
                    'productcat'        => $sheet->getCell('C' . $row)->getValue(),
                    'tmtid'             => $sheet->getCell('D' . $row)->getValue(),
                    'genericname'       => $sheet->getCell('E' . $row)->getValue(),
                    'tradename'         => $sheet->getCell('F' . $row)->getValue(),
                    'dosageform'        => $sheet->getCell('G' . $row)->getValue(),
                    'strength'          => $sheet->getCell('H' . $row)->getValue(),
                    'unitprice'         => $unitprice,
                    'distributor'       => $sheet->getCell('J' . $row)->getValue(),
                    'manufacturer'      => $sheet->getCell('K' . $row)->getValue(),
                    'ised'              => $sheet->getCell('L' . $row)->getValue(),
                    'specprep'          => $sheet->getCell('M' . $row)->getValue(),
                    'ndc24'             => $sheet->getCell('N' . $row)->getValue(),
                    'packsize'          => $sheet->getCell('O' . $row)->getValue(),
                    'packprice'         => $packprice,
                    'date_import'       => $date_import,
                    'datechange'        => $datechange,
                    'dateupdate'        => $dateupdate,
                    'dateeffective'     => $dateeffective,
                    'filename'          => $sheet->getCell('U' . $row)->getValue(),
                    'hospcode'          => $sheet->getCell('V' . $row)->getValue(),
                    'stm_filename'      => $file_name,
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $key => $data_) {
                Drugcat_fdh::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.drugcat_fdh')->with('success', $file_name);
    }

    //Drug ทั้งหมดใน HOSxP (FDH)-----------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug ไม่พบที่ FDH----------------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_non_nhso()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.hospdrugcode IS NULL  
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ราคาไม่ตรงกับ HOSxP (FDH)-------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_price_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm    
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.unitprice <> d.unitprice
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog รหัส TMT ไม่ตรงกับ HOSxP (FDH)
    public function drugcat_fdh_tmt_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm   
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.tmtid <> d3.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog รหัส 24 หลักไม่ตรงกับ HOSxP (FDH)---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_code24_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm 
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND nd.ndc24 <> d2.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ยาสมุนไพร (FDH)---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_herb()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d2.ref_code LIKE '4%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog บัญชียาหลักไม่ตรงกัน (ED/NED Mismatch - FDH)-----------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_ised_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND nd.hospdrugcode IS NOT NULL 
              AND CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END <> CASE WHEN (nd.ised LIKE 'E%') THEN 'E' ELSE 'N' END
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ยังไม่ผูกรหัส 24 หลักใน HOSxP (FDH)-------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_code24_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND (d2.ref_code IS NULL OR d2.ref_code = '') 
              AND nd.ndc24 IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ยังไม่ผูกรหัส TMT ใน HOSxP (FDH)-----------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_tmt_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' 
              AND (d3.ref_code IS NULL OR d3.ref_code = '') 
              AND nd.tmtid IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('check.drugcat_fdh', compact('drug'));
    }

    //ส่งออกรายการ FDH-------------------------------------------------------------------------------------------------
    public function drugcat_fdh_export(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcelFDH([], $hosp_code . 'DrugFDH' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";
        
        $drugs = DB::connection('hosxp')->select("
            SELECT  
                d.icode AS HospDrugCode,
                d.sks_product_category_id AS ProductCat,
                d.sks_drug_code AS TMTID,
                IFNULL(d.generic_name, d.`name`) AS GenericName,
                IFNULL(d.trade_name, s.TradeName) AS TradeName,
                IFNULL(d.sks_dfs_code, s.DSFCode) AS DFSCode,
                IFNULL(d.dosageform, s.DosageForm) AS DosageForm,
                IFNULL(d.strength, s.Strength) AS Strength,
                d.unitprice AS UnitPrice,
                dr.comp AS Distributor,
                CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN tc.manufacturer ELSE dr.manufacturer END AS Manufacturer,
                CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' WHEN d.drugaccount <> '' THEN 'E' END AS ISED,
                IFNULL(s.SpecPrep, '') AS SpecPrep,
                d.did AS NDC24,
                CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE p.provis_medication_unit_name END AS Packsize,
                d.unitprice AS Packprice,
                '' AS DateChange,
                '' AS DateUpdate,
                DATE_FORMAT(IFNULL(nd.dateeffective, IFNULL(s.DateEffective, IFNULL(d.last_update, NOW()))), '%Y-%m-%d') AS DateEffective,
                IFNULL(nd.filename, '') AS FileName,
                '{$hosp_code}' AS HospCode
            FROM drugitems d
            LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = d.sks_drug_code
            LEFT JOIN drugitems_register_unique dr ON dr.std_code = d.did
            LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
            LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
              {$where_icode}
            ORDER BY d.icode
        ");

        return $this->exportToExcelFDH($drugs, $hosp_code . 'DrugFDH' . $seq . '.xlsx');
    }

    //ส่งออก Preview FDH-------------------------------------------------------------------------------------------------
    public function drugcat_fdh_export_preview(Request $request)
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";

        try {
            $drugs = DB::connection('hosxp')->select("
                SELECT  
                    d.icode AS HospDrugCode,
                    d.sks_product_category_id AS ProductCat,
                    d.sks_drug_code AS TMTID,
                    IFNULL(d.generic_name, d.`name`) AS GenericName,
                    IFNULL(d.trade_name, s.TradeName) AS TradeName,
                    IFNULL(d.sks_dfs_code, s.DSFCode) AS DFSCode,
                    IFNULL(d.dosageform, s.DosageForm) AS DosageForm,
                    IFNULL(d.strength, s.Strength) AS Strength,
                    d.unitprice AS UnitPrice,
                    dr.comp AS Distributor,
                    CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN tc.manufacturer ELSE dr.manufacturer END AS Manufacturer,
                    CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' WHEN d.drugaccount <> '' THEN 'E' END AS ISED,
                    IFNULL(s.SpecPrep, '') AS SpecPrep,
                    d.did AS NDC24,
                    CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE p.provis_medication_unit_name END AS Packsize,
                    d.unitprice AS Packprice,
                    '' AS DateChange,
                    '' AS DateUpdate,
                    DATE_FORMAT(IFNULL(nd.dateeffective, IFNULL(s.DateEffective, IFNULL(d.last_update, NOW()))), '%Y-%m-%d') AS DateEffective,
                    IFNULL(nd.filename, '') AS FileName,
                    '{$hosp_code}' AS HospCode
                FROM drugitems d
                LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = d.sks_drug_code
                LEFT JOIN drugitems_register_unique dr ON dr.std_code = d.did
                LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
                LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode
                LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                    FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode
                WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
                  {$where_icode}
                ORDER BY d.icode
            ");
            
            return response()->json([
                'success' => true,
                'data' => $drugs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลตัวอย่าง: ' . $e->getMessage()
            ]);
        }
    }

    //ส่งออก FDH Excel---------------------------------------------------------------------------------
    private function exportToExcelFDH($data, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'row', 'รหัสยา Hosp Drug Code', 'ประเภทยาและเวชภัณฑ์', 'รหัสยา TMT', 'ชื่อยาสามัญ', 'ชื่อทางการค้า', 
            'DSF Code', 'ลักษณะยา', 'ปริมาณยาต่อหน่วยยา', 'ราคากลางต่อหน่วยที่เบิกได้', 'Distributor', 
            'Manufacturer', 'ISED', 'SPEC PREP', 'รหัสยา 24 หลักจากหน่วยบริการ', 'Pack Size', 
            'Pack Price', 'Date Change', 'Date Update', 'Date Effective', 'File Name', 'รหัสโรงพยาบาล'
        ];
        
        // Write headers
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        // Write data
        $rowNum = 2;
        foreach ($data as $index => $row) {
            $rowArray = (array)$row;
            
            $rowData = [
                $rowNum - 1,
                $rowArray['HospDrugCode'] ?? '',
                $rowArray['ProductCat'] ?? '',
                $rowArray['TMTID'] ?? '',
                $rowArray['GenericName'] ?? '',
                $rowArray['TradeName'] ?? '',
                $rowArray['DFSCode'] ?? '',
                $rowArray['DosageForm'] ?? '',
                $rowArray['Strength'] ?? '',
                $rowArray['UnitPrice'] ?? '',
                $rowArray['Distributor'] ?? '',
                $rowArray['Manufacturer'] ?? '',
                $rowArray['ISED'] ?? '',
                $rowArray['SpecPrep'] ?? '',
                $rowArray['NDC24'] ?? '',
                $rowArray['Packsize'] ?? '',
                $rowArray['Packprice'] ?? '',
                $rowArray['DateChange'] ?? '',
                $rowArray['DateUpdate'] ?? '',
                $rowArray['DateEffective'] ?? '',
                $rowArray['FileName'] ?? '',
                $rowArray['HospCode'] ?? ''
            ];

            $colIndex = 1;
            foreach ($rowData as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $rowNum, $val);
                $colIndex++;
            }
            $rowNum++;
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    ###################################################################################################################################################
    //สิทธิการักษา HOSxP---------------------------------------------------------------------------------------------------------------------------
    public function pttype()
    {
        $pttype =  DB::connection('hosxp')->select('
            SELECT p.pttype,inscl.nhso_subinscl,p.`name`,CONCAT(p1.paidst,SPACE(1),p1.`name`) AS paidst,p.export_eclaim,p.hipdata_code,p.pttype_std_code,
            CONCAT(pi.`code`,SPACE(1),pi.`name`) AS pi_name,pi.pttype_std_code AS pi_pttype_std_code,pg.pttype_price_group_name
            FROM pttype p
            LEFT JOIN paidst p1 ON p1.paidst=p.paidst
            LEFT JOIN pttype_price_group pg ON pg.pttype_price_group_id=p.pttype_price_group_id
            LEFT JOIN provis_instype pi ON pi.`code`=p.nhso_code
            LEFT JOIN pttype_nhso_subinscl inscl ON inscl.pttype=p.pttype
            WHERE p.isuse = "Y" ORDER BY p.hipdata_code,p.pttype');

        $pttype_close =  DB::connection('hosxp')->select('
            SELECT p.pttype,inscl.nhso_subinscl,p.`name`,CONCAT(p1.paidst,SPACE(1),p1.`name`) AS paidst,p.export_eclaim,p.hipdata_code,p.pttype_std_code,
            CONCAT(pi.`code`,SPACE(1),pi.`name`) AS pi_name,pi.pttype_std_code AS pi_pttype_std_code,pg.pttype_price_group_name
            FROM pttype p
            LEFT JOIN paidst p1 ON p1.paidst=p.paidst
            LEFT JOIN pttype_price_group pg ON pg.pttype_price_group_id=p.pttype_price_group_id
            LEFT JOIN provis_instype pi ON pi.`code`=p.nhso_code
            LEFT JOIN pttype_nhso_subinscl inscl ON inscl.pttype=p.pttype
            WHERE p.isuse <> "Y" ORDER BY p.hipdata_code,p.pttype');

        return view('check.pttype', compact('pttype', 'pttype_close'));
    }
    //สิทธิการักษา nhso_subinscl---------------------------------------------------------------------------------------------------------------------------
    public function nhso_subinscl()
    {
        $subinscl =  DB::connection('hosxp')->select('
            SELECT s.*,p.pttype,p.`name` AS pttype_name,p.hipdata_code 
            FROM hrims.subinscl s
            LEFT JOIN pttype p ON p.pttype=s.`code`');

        $subinscl_found = [];
        $subinscl_notfound = [];

        foreach ($subinscl as $row) {
            if ($row->pttype !== null) {
                $subinscl_found[] = $row;
            } else {
                $subinscl_notfound[] = $row;
            }
        }

        return view('check.nhso_subinscl', compact('subinscl', 'subinscl_found', 'subinscl_notfound'));
    }

    //สิทธิการักษา nhso_subinscl---------------------------------------------------------------------------------------------------------------------------
    public function nondrugitems()
    {
        // Cache for loaded rules files from database lookup_nhso_adp_code
        $rulesCache = [];
        $loadRules = function(int $typeId) use (&$rulesCache): ?array {
            if (array_key_exists($typeId, $rulesCache)) return $rulesCache[$typeId];
            
            if (!Schema::hasTable('lookup_nhso_adp_code')) {
                $rulesCache[$typeId] = null;
                return null;
            }

            $records = DB::table('lookup_nhso_adp_code')
                ->where('nhso_adp_type_id', $typeId)
                ->get();

            if ($records->isEmpty()) {
                $rulesCache[$typeId] = null;
                return null;
            }

            $rules = [];
            foreach ($records as $r) {
                $rules[$r->nhso_adp_code] = [
                    'name' => $r->nhso_adp_code_name,
                    'category' => $r->category,
                    'prices' => [
                        'UCS' => floatval($r->price_ucs),
                        'OFC' => floatval($r->price_ofc),
                        'SSS' => floatval($r->price_sss),
                        'LGO' => floatval($r->price_lgo),
                        'FS' => floatval($r->price_fs),
                        'UCEP' => floatval($r->price_ucep),
                    ]
                ];
            }

            $rulesCache[$typeId] = $rules;
            return $rules;
        };

        $defaultPrices = ['UCS' => 0.0, 'OFC' => 0.0, 'SSS' => 0.0, 'LGO' => 0.0, 'FS' => 0.0, 'UCEP' => 0.0];

        // Check if HOSxP v4 pttype_items_price table exists
        $hasPttypeItemsPrice = false;
        try {
            $checkTable = DB::connection('hosxp')->select("SHOW TABLES LIKE 'pttype_items_price'");
            if (!empty($checkTable)) {
                $hasPttypeItemsPrice = true;
            }
        } catch (\Exception $e) {
            $hasPttypeItemsPrice = false;
        }

        $attachPriceInfo = function(array $rows) use ($loadRules, $defaultPrices, $hasPttypeItemsPrice): array {
            $icodes = array_column($rows, 'icode');
            if (empty($icodes)) return $rows;

            $placeholders = implode(',', array_fill(0, count($icodes), '?'));
            $typeMap = DB::connection('hosxp')->select(
                "SELECT icode, nhso_adp_type_id, nhso_adp_code FROM nondrugitems WHERE icode IN ({$placeholders})",
                $icodes
            );
            $typeById = [];
            foreach ($typeMap as $t) {
                $typeById[$t->icode] = ['type_id' => $t->nhso_adp_type_id, 'adp_code' => $t->nhso_adp_code];
            }

            // If HOSxP v4 table exists, fetch overrides for these icodes
            $overrides = [];
            if ($hasPttypeItemsPrice) {
                // Convert icodes to integers to bind to items_table_code_int
                $icodesInt = array_map('intval', $icodes);
                $bindings = array_merge($icodes, $icodesInt);
                
                $v4Prices = DB::connection('hosxp')->select(
                    "SELECT pip.items_table_code, pip.items_table_code_int, pip.pttype_price_group_id, pg.pttype_price_group_name, pip.price 
                     FROM pttype_items_price pip
                     LEFT JOIN pttype_price_group pg ON pg.pttype_price_group_id = pip.pttype_price_group_id
                     WHERE (pip.items_table_code IN ({$placeholders}) OR pip.items_table_code_int IN ({$placeholders}))",
                    $bindings
                );
                foreach ($v4Prices as $vp) {
                    // Normalize lookup key to match either string code or integer code
                    $key = !empty($vp->items_table_code) ? $vp->items_table_code : strval($vp->items_table_code_int);
                    $overrides[$key][$vp->pttype_price_group_id] = [
                        'pttype_price_group_id' => $vp->pttype_price_group_id,
                        'pttype_price_group_name' => $vp->pttype_price_group_name ?? ('กลุ่มที่ ' . $vp->pttype_price_group_id),
                        'price' => floatval($vp->price),
                    ];
                }
            }

            return array_map(function($row) use ($loadRules, $typeById, $defaultPrices, $overrides, $hasPttypeItemsPrice) {
                $row = (array) $row;
                $icode = $row['icode'];
                $meta  = $typeById[$icode] ?? null;
                $typeId  = $meta['type_id']  ?? null;
                $adpCode = $meta['adp_code'] ?? $row['nhso_adp_code'] ?? null;

                // Override prices from HOSxP v4 pttype_items_price if exists
                // Under HOSxP v4, if the table pttype_items_price exists, then:
                // UCS (group 2), OFC (group 3), OOP/FS (group 1), SSS (group 4), LGO (group 5)
                // all default to $row['price'] (the main price) unless there is an override in pttype_items_price.
                // We ignore the legacy columns price2 and price3 from nondrugitems in v4 mode.
                $row['v4_override'] = [];
                $row['v4_all_overrides'] = [];
                if ($hasPttypeItemsPrice) {
                    $basePrice = floatval($row['price']);
                    
                    // Set all OPD prices to base price initially
                    $row['price_ucs'] = $basePrice; // UCS
                    $row['price2'] = $basePrice; // OFC
                    $row['price3'] = $basePrice; // OOP/FS
                    $row['price_sss'] = $basePrice; // SSS
                    $row['price_lgo'] = $basePrice; // LGO

                    $lookupKey = isset($overrides[$icode]) ? $icode : strval(intval($icode));
                    if (isset($overrides[$lookupKey])) {
                        $itemOverrides = $overrides[$lookupKey];
                        // Group 2: UCS -> price_ucs
                        if (isset($itemOverrides[2])) {
                            $row['price_ucs'] = $itemOverrides[2]['price'];
                            $row['v4_override'][] = 'UCS';
                        }
                        // Group 3: OFC -> price2
                        if (isset($itemOverrides[3])) {
                            $row['price2'] = $itemOverrides[3]['price'];
                            $row['v4_override'][] = 'OFC';
                        }
                        // Group 1: OOP -> price3
                        if (isset($itemOverrides[1])) {
                            $row['price3'] = $itemOverrides[1]['price'];
                            $row['v4_override'][] = 'OOP';
                        }
                        // Group 4: SSS -> price_sss
                        if (isset($itemOverrides[4])) {
                            $row['price_sss'] = $itemOverrides[4]['price'];
                            $row['v4_override'][] = 'SSS';
                        }
                        // Group 5: LGO -> price_lgo
                        if (isset($itemOverrides[5])) {
                            $row['price_lgo'] = $itemOverrides[5]['price'];
                            $row['v4_override'][] = 'LGO';
                        }
                        
                        $row['v4_all_overrides'] = array_values($itemOverrides);
                    }
                }

                if (!$typeId) {
                    $row['priceStatus'] = 'notype';
                    $row['rulePrices']  = $defaultPrices;
                    return (object) $row;
                }

                $rules = $loadRules((int)$typeId);
                if ($rules === null || !isset($rules[$adpCode])) {
                    $row['priceStatus'] = 'notfound';
                    $row['rulePrices']  = $defaultPrices;
                    return (object) $row;
                }

                $rulePrices = array_merge($defaultPrices, $rules[$adpCode]['prices'] ?? []);
                
                // Compare logic:
                // ตรงกันเบื้องต้นตรวจจาก nondrugitems.price = hrims.lookup_nhso_adp_code.price_ofc
                $p1 = floatval($row['price']);
                $ofcPrice = floatval($rulePrices['OFC'] ?? 0);

                $status = 'mismatch';
                if ($ofcPrice <= 0.5) {
                    // If OFC price is 0 or not set in rules, fallback to notfound
                    $status = 'notfound';
                } elseif (abs($p1 - $ofcPrice) < 0.1 || intval($p1) === intval($ofcPrice)) {
                    $status = 'match';
                }

                // If status is 'match', also check all overrides in v4_all_overrides.
                // If any of the overrides doesn't match its corresponding rule price, change status to 'mismatch'.
                if ($status === 'match' && !empty($row['v4_all_overrides'])) {
                    foreach ($row['v4_all_overrides'] as $override) {
                        $grpId = $override['pttype_price_group_id'] ?? 0;
                        $grpName = $override['pttype_price_group_name'] ?? '';
                        $priceVal = floatval($override['price'] ?? 0);

                        $ruleKey = '';
                        $grpLower = mb_strtolower($grpName);
                        if ($grpId == 2 || strpos($grpLower, 'ucs') !== false || strpos($grpLower, 'บัตรทอง') !== false || strpos($grpLower, 'หลักประกัน') !== false) {
                            $ruleKey = 'UCS';
                        } elseif ($grpId == 3 || strpos($grpLower, 'ofc') !== false || strpos($grpLower, 'ข้าราชการ') !== false || strpos($grpLower, 'กรมบัญชีกลาง') !== false) {
                            $ruleKey = 'OFC';
                        } elseif ($grpId == 4 || strpos($grpLower, 'sss') !== false || strpos($grpLower, 'ประกันสังคม') !== false) {
                            $ruleKey = 'SSS';
                        } elseif ($grpId == 5 || strpos($grpLower, 'lgo') !== false || strpos($grpLower, 'อปท') !== false || strpos($grpLower, 'ส่วนท้องถิ่น') !== false) {
                            $ruleKey = 'LGO';
                        } elseif ($grpId == 1 || strpos($grpLower, 'ชำระเงินเอง') !== false || strpos($grpLower, 'cash') !== false || strpos($grpLower, 'fs') !== false) {
                            $ruleKey = 'FS';
                        }

                        if (!empty($ruleKey) && isset($rulePrices[$ruleKey])) {
                            $rulePrice = floatval($rulePrices[$ruleKey]);
                            if ($rulePrice > 0) {
                                $isOverrideMatch = (abs($rulePrice - $priceVal) < 0.1 || intval($rulePrice) === intval($priceVal));
                                if (!$isOverrideMatch) {
                                    $status = 'mismatch';
                                    break;
                                }
                            }
                        }
                    }
                }

                $row['priceStatus'] = $status;

                $row['rulePrices'] = $rulePrices;
                $row['ruleName']   = $rules[$adpCode]['name'] ?? '';
                return (object) $row;
            }, $rows);
        };

        $nondrugitemsRaw = DB::connection('hosxp')->select('
            SELECT CONCAT(i.income, " ", i.`name`) AS income,n.icode,n.`name`,
                n.price, n.price2, n.price3, n.ipd_price, n.ipd_price2, n.ipd_price3, n.billcode,
                nc.nhso_adp_code,nc.nhso_adp_code_name,nt.nhso_adp_type_name,
                n.paidst, ps.name AS paidst_name
            FROM nondrugitems n
            LEFT JOIN income i ON i.income = n.income
            LEFT JOIN nhso_adp_code nc ON nc.nhso_adp_code = n.nhso_adp_code
            LEFT JOIN nhso_adp_type nt ON nt.nhso_adp_type_id=n.nhso_adp_type_id
            LEFT JOIN paidst ps ON ps.paidst = n.paidst
            WHERE n.istatus = "Y"
            ORDER BY n.income');

        $nondrugitems = $attachPriceInfo(array_map(fn($r) => (array)$r, $nondrugitemsRaw));

        $nondrugitems_non_raw = DB::connection('hosxp')->select('
            SELECT CONCAT(i.income, " ", i.`name`) AS income,n.icode,n.`name`,
                n.price, n.price2, n.price3, n.ipd_price, n.ipd_price2, n.ipd_price3, n.billcode,
                nc.nhso_adp_code,nc.nhso_adp_code_name,nt.nhso_adp_type_name,
                n.paidst, ps.name AS paidst_name
            FROM nondrugitems n
            LEFT JOIN income i ON i.income = n.income
            LEFT JOIN nhso_adp_code nc ON nc.nhso_adp_code = n.nhso_adp_code
            LEFT JOIN nhso_adp_type nt ON nt.nhso_adp_type_id=n.nhso_adp_type_id
            LEFT JOIN paidst ps ON ps.paidst = n.paidst
            WHERE n.istatus <> "Y"
            ORDER BY n.income');

        $nondrugitems_non = $attachPriceInfo(array_map(fn($r) => (array)$r, $nondrugitems_non_raw));

        // Fetch unique categories (incomes) for filtering, concatenated with code
        $categories = DB::connection('hosxp')->table('nondrugitems')
            ->join('income', 'nondrugitems.income', '=', 'income.income')
            ->select(DB::raw('CONCAT(income.income, " ", income.name) as combined_name'))
            ->distinct()
            ->orderBy('combined_name')
            ->pluck('combined_name');


        return view('check.nondrugitems', compact('nondrugitems', 'nondrugitems_non', 'categories', 'hasPttypeItemsPrice'));
    }

    // sss_equipdev_aipn -----------------------------------------------------------------------------------------
    public function sss_equipdev_aipn(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('lookup_sss_equipdev_aipn');

            // Tab filter: active = dateexp >= today, expired = dateexp < today
            $tab = $request->input('tab', 'all');
            $today = now()->format('Y-m-d');
            if ($tab === 'active') {
                $query->where('dateexp', '>=', $today);
            } elseif ($tab === 'expired') {
                $query->where('dateexp', '<', $today);
            }

            // Searching
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%$search%")
                      ->orWhere('desc', 'like', "%$search%")
                      ->orWhere('billgroup', 'like', "%$search%")
                      ->orWhere('dtcond', 'like', "%$search%");
                });
            }

            $recordsTotal = DB::table('lookup_sss_equipdev_aipn')->count();
            $recordsFiltered = $query->count();

            // Pagination
            $start = $request->start ?? 0;
            $length = $request->length ?? 50;
            
            // Order
            if ($request->has('order')) {
                $columns = [
                    0 => 'billgroup',
                    1 => 'code',
                    2 => 'unit',
                    3 => 'rate',
                    4 => 'rate2',
                    5 => 'desc',
                    6 => 'daterev',
                    7 => 'dateeff',
                    8 => 'dateexp',
                    9 => 'lastupd',
                    10 => 'dtcond',
                    11 => 'note'
                ];
                foreach ($request->order as $order) {
                    if (isset($columns[$order['column']])) {
                        $query->orderBy($columns[$order['column']], $order['dir']);
                    }
                }
            } else {
                $query->orderBy('id', 'asc');
            }

            $data = $query->offset($start)->limit($length)->get();

            return response()->json([
                "draw" => intval($request->draw),
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $data
            ]);
        }

        $total_records = DB::table('lookup_sss_equipdev_aipn')->count();
        $active_records = DB::table('lookup_sss_equipdev_aipn')->where('dateexp', '>=', now()->format('Y-m-d'))->count();
        $expired_records = DB::table('lookup_sss_equipdev_aipn')->where('dateexp', '<', now()->format('Y-m-d'))->count();
        return view('check.sss_equipdev_aipn', compact('total_records', 'active_records', 'expired_records'));
    }

    // sss_equipdev_aipn_save ------------------------------------------------------------------------------------
    public function sss_equipdev_aipn_save(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $this->validate($request, [
            'file' => 'required|file|extensions:xls,xlsx'
        ]);

        $the_file = $request->file('file');
        $file_name = $the_file->getClientOriginalName();

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet = $spreadsheet->setActiveSheetIndex(0);
            $row_limit = $sheet->getHighestDataRow();

            $data = [];

            // Helper function to format Excel date safely
            $parseDate = function ($value) {
                if (empty($value) || $value === '-' || trim($value) === '') {
                    return null;
                }
                
                $value = trim($value);
                if (is_numeric($value)) {
                    try {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
                
                foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y'] as $format) {
                    try {
                        return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // continue
                    }
                }
                
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            };

            $cleanRate = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            for ($row = 2; $row <= $row_limit; $row++) {
                $billgroup = $sheet->getCell('A' . $row)->getValue();
                $code = $sheet->getCell('B' . $row)->getValue();

                if (empty($billgroup) && empty($code)) {
                    continue;
                }

                $rate = $cleanRate($sheet->getCell('D' . $row)->getValue());
                $rate2 = $cleanRate($sheet->getCell('E' . $row)->getValue());

                $daterev = $parseDate($sheet->getCell('G' . $row)->getValue());
                $dateeff = $parseDate($sheet->getCell('H' . $row)->getValue());
                $dateexp = $parseDate($sheet->getCell('I' . $row)->getValue());

                $data[] = [
                    'billgroup' => $sheet->getCell('A' . $row)->getValue(),
                    'code' => $sheet->getCell('B' . $row)->getValue(),
                    'unit' => $sheet->getCell('C' . $row)->getValue(),
                    'rate' => $rate,
                    'rate2' => $rate2,
                    'desc' => $sheet->getCell('F' . $row)->getValue(),
                    'daterev' => $daterev,
                    'dateeff' => $dateeff,
                    'dateexp' => $dateexp,
                    'lastupd' => $sheet->getCell('J' . $row)->getValue(),
                    'dtcond' => $sheet->getCell('K' . $row)->getValue(),
                    'note' => $sheet->getCell('L' . $row)->getValue(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($data)) {
                \App\Models\LookupSssEquipdevAipn::truncate();
                DB::transaction(function () use ($data) {
                    $chunks = array_chunk($data, 1000);
                    foreach ($chunks as $chunk) {
                        \App\Models\LookupSssEquipdevAipn::insert($chunk);
                    }
                });
            }

            return redirect()->route('check.sss_equipdev_aipn')->with('success', 'นำเข้าข้อมูล ' . $file_name . ' สำเร็จ จำนวน ' . count($data) . ' รายการ');

        } catch (\Exception $e) {
            return redirect()->route('check.sss_equipdev_aipn')->with('error', 'เกิดข้อผิดพลาดในการนำเข้า: ' . $e->getMessage());
        }
    }

    public function labcat_nhso_save(Request $request)
    {
        set_time_limit(300);

        Labcat_nhso::truncate();

        $this->validate($request, [
            'file' => 'required|file'
        ]);
        $the_file = $request->file('file');
        if (!in_array(strtolower($the_file->getClientOriginalExtension()), ['xls', 'xlsx'])) {
            return back()->withErrors('กรุณาเลือกเฉพาะไฟล์นามสกุล .xls หรือ .xlsx เท่านั้น');
        }
        $file_name = $the_file->getClientOriginalName();

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->setActiveSheetIndex(0);
            $row_limit    = $sheet->getHighestDataRow();
            $row_range    = range('2', $row_limit);
            
            $cleanPrice = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = [];
            foreach ($row_range as $row) {
                $lccode = $sheet->getCell('B' . $row)->getValue();
                if ($lccode instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $lccode = $lccode->getPlainText();
                }
                $lccode = trim($lccode ?? '');
                if ($lccode === '') {
                    continue;
                }

                $data[] = [
                    'lccode'       => trim($lccode),
                    'billgroup'    => $sheet->getCell('C' . $row)->getValue(),
                    'cscode'       => $sheet->getCell('D' . $row)->getValue(),
                    'tmlt'         => $sheet->getCell('E' . $row)->getValue(),
                    'loinc'        => $sheet->getCell('F' . $row)->getValue(),
                    'panel'        => $sheet->getCell('G' . $row)->getValue(),
                    'name'         => $sheet->getCell('H' . $row)->getValue(),
                    'sflag'        => $sheet->getCell('I' . $row)->getValue(),
                    'chargecat'    => $sheet->getCell('J' . $row)->getValue(),
                    'unitprice'    => $cleanPrice($sheet->getCell('K' . $row)->getValue()),
                    'benefitplan'  => $sheet->getCell('L' . $row)->getValue(),
                    'reimbprice'   => $cleanPrice($sheet->getCell('M' . $row)->getValue()),
                    'updateflag'   => $sheet->getCell('N' . $row)->getValue(),
                    'updatebeg'    => $sheet->getCell('O' . $row)->getValue(),
                    'updateend'    => $sheet->getCell('P' . $row)->getValue(),
                    'rpdatebeg'    => $sheet->getCell('Q' . $row)->getValue(),
                    'rpdateend'    => $sheet->getCell('R' . $row)->getValue(),
                    'dateupd'      => $sheet->getCell('S' . $row)->getValue(),
                    'hcode'        => $sheet->getCell('T' . $row)->getValue(),
                    'message'      => $sheet->getCell('U' . $row)->getValue(),
                    'stm_filename' => $file_name,
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $data_) {
                Labcat_nhso::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.labcat_nhso')->with('success', $file_name);
    }

    private function getLabItems($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE l.active_status = 'Y' AND (l.icode IS NOT NULL AND l.icode <> '')";
        $whereClause .= " AND l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT l.lab_items_code, l.icode, l.lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso, n.price AS service_price, l.service_price_ipd, l.tmlt_code, l.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_nhso dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_nhso dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = l.icode
            {$whereClause}
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getLabPanels($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NOT NULL AND sg.group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso,
                   n.price AS service_price, sg.group_price_ipd AS service_price_ipd, sg.tmlt_code, sg.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_nhso dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_nhso dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = sg.group_icode
            {$whereClause}
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    private function getUnmappedLabItems()
    {
        return DB::connection('hosxp')->select("
            SELECT 'I' AS lab_type, l.lab_items_code, l.icode, l.lab_items_name, l.tmlt_code, l.loinc_code
            FROM lab_items l
            LEFT JOIN nondrugitems n ON n.icode = l.icode
            WHERE l.active_status = 'Y'
              AND (l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '') OR l.icode IS NULL OR l.icode = '')
              AND n.icode IS NULL
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getUnmappedLabPanels()
    {
        return DB::connection('hosxp')->select("
            SELECT 'P' AS lab_type, sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, sg.tmlt_code, sg.loinc_code
            FROM lab_items_sub_group sg
            LEFT JOIN nondrugitems n ON n.icode = sg.group_icode
            WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL)
              AND n.icode IS NULL
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    public function labcat_nhso()
    {
        $items_i = $this->getLabItems();
        $items_p = $this->getLabPanels();
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_non_nhso()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NULL");
        $items_p = $this->getLabPanels("AND ln.lccode IS NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_price_notmatch_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_tmlt_notmatch_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND ln.tmlt <> l.tmlt_code");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND ln.tmlt <> sg.tmlt_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_loinc_notmatch_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND ln.loinc <> l.loinc_code");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND ln.loinc <> sg.loinc_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_tmlt_missing_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND (l.tmlt_code IS NULL OR l.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND (sg.tmlt_code IS NULL OR sg.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_loinc_missing_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND (l.loinc_code IS NULL OR l.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND (sg.loinc_code IS NULL OR sg.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    // =========================================================================
    // LAB CATALOG CHI (สกส.) --------------------------------------------------
    // =========================================================================
    public function labcat_chi_save(Request $request)
    {
        set_time_limit(300);

        Labcat_chi::truncate();

        $this->validate($request, [
            'file' => 'required|file'
        ]);
        $the_file = $request->file('file');
        if (!in_array(strtolower($the_file->getClientOriginalExtension()), ['xls', 'xlsx'])) {
            return back()->withErrors('กรุณาเลือกเฉพาะไฟล์นามสกุล .xls หรือ .xlsx เท่านั้น');
        }
        $file_name = $the_file->getClientOriginalName();

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->setActiveSheetIndex(0);
            $row_limit    = $sheet->getHighestDataRow();
            $row_range    = range('5', $row_limit); // Starts at row 5 (Header is on row 4)
            
            $cleanPrice = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = [];
            foreach ($row_range as $row) {
                $lccode = $sheet->getCell('B' . $row)->getValue();
                if ($lccode instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $lccode = $lccode->getPlainText();
                }
                $lccode = trim($lccode ?? '');
                if ($lccode === '') {
                    continue;
                }

                $data[] = [
                    'lccode'       => trim($lccode),
                    'billgroup'    => $sheet->getCell('C' . $row)->getValue(),
                    'cscode'       => $sheet->getCell('D' . $row)->getValue(),
                    'tmlt'         => $sheet->getCell('E' . $row)->getValue(),
                    'loinc'        => $sheet->getCell('F' . $row)->getValue(),
                    'panel'        => $sheet->getCell('G' . $row)->getValue(),
                    'name'         => $sheet->getCell('H' . $row)->getValue(),
                    'sflag'        => $sheet->getCell('I' . $row)->getValue(),
                    'chargecat'    => $sheet->getCell('J' . $row)->getValue(),
                    'unitprice'    => $cleanPrice($sheet->getCell('K' . $row)->getValue()),
                    'benefitplan'  => $sheet->getCell('L' . $row)->getValue(),
                    'reimbprice'   => $cleanPrice($sheet->getCell('M' . $row)->getValue()),
                    'updateflag'   => $sheet->getCell('N' . $row)->getValue(),
                    'updatebeg'    => $sheet->getCell('O' . $row)->getValue(),
                    'updateend'    => $sheet->getCell('P' . $row)->getValue(),
                    'rpdatebeg'    => $sheet->getCell('Q' . $row)->getValue(),
                    'rpdateend'    => $sheet->getCell('R' . $row)->getValue(),
                    'dateupd'      => $sheet->getCell('S' . $row)->getValue(),
                    'hcode'        => '', // Default empty if not in file
                    'message'      => '', // Default empty if not in file
                    'stm_filename' => $file_name,
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $data_) {
                Labcat_chi::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.labcat_chi')->with('success', $file_name);
    }

    private function getLabItemsChi($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE l.active_status = 'Y' AND (l.icode IS NOT NULL AND l.icode <> '')";
        $whereClause .= " AND l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT l.lab_items_code, l.icode, l.lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso, n.price AS service_price, l.service_price_ipd, l.tmlt_code, l.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = l.icode
            {$whereClause}
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getLabPanelsChi($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NOT NULL AND sg.group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso,
                   n.price AS service_price, sg.group_price_ipd AS service_price_ipd, sg.tmlt_code, sg.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = sg.group_icode
            {$whereClause}
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    public function labcat_chi()
    {
        $items_i = $this->getLabItemsChi();
        $items_p = $this->getLabPanelsChi();
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_non_nhso()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NULL");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_price_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_tmlt_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND ln.tmlt <> l.tmlt_code");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND ln.tmlt <> sg.tmlt_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_loinc_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND ln.loinc <> l.loinc_code");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND ln.loinc <> sg.loinc_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_tmlt_missing_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND (l.tmlt_code IS NULL OR l.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND (sg.tmlt_code IS NULL OR sg.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_loinc_missing_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND (l.loinc_code IS NULL OR l.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND (sg.loinc_code IS NULL OR sg.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    // =========================================================================
    // LAB CATALOG FDH ---------------------------------------------------------
    // =========================================================================
    private function getLabItemsFdh($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE l.active_status = 'Y' AND (l.icode IS NOT NULL AND l.icode <> '')";
        $whereClause .= " AND l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT l.lab_items_code, l.icode, l.lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso, n.price AS service_price, l.service_price_ipd, l.tmlt_code, l.loinc_code,
                   ln.unitprice AS price_nhso, ln.unitprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   '' AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = l.icode
            {$whereClause}
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getLabPanelsFdh($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NOT NULL AND sg.group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso,
                   n.price AS service_price, sg.group_price_ipd AS service_price_ipd, sg.tmlt_code, sg.loinc_code,
                   ln.unitprice AS price_nhso, ln.unitprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   '' AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = sg.group_icode
            {$whereClause}
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    public function labcat_fdh_save(Request $request)
    {
        set_time_limit(300);

        \App\Models\Labcat_fdh::truncate();

        $this->validate($request, [
            'file' => 'required|file'
        ]);
        $the_file = $request->file('file');
        if (!in_array(strtolower($the_file->getClientOriginalExtension()), ['xls', 'xlsx'])) {
            return back()->withErrors('กรุณาเลือกเฉพาะไฟล์นามสกุล .xls หรือ .xlsx เท่านั้น');
        }
        $file_name = $the_file->getClientOriginalName();

        try {
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->setActiveSheetIndex(0);
            $row_limit    = $sheet->getHighestDataRow();
            $row_range    = range('6', $row_limit);

            $parseExcelDate = function ($value) {
                if (empty($value) || $value === '-' || trim($value) === '') {
                    return null;
                }
                $value = trim($value);
                if (is_numeric($value)) {
                    try {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
                foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d\TH:i:s\Z', 'Y-m-d H:i:s', 'd-m-Y H:i'] as $format) {
                    try {
                        return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // continue
                    }
                }
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            };

            $cleanExcelRate = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = [];
            foreach ($row_range as $row) {
                $lccode = $sheet->getCell('M' . $row)->getValue();
                if ($lccode instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $lccode = $lccode->getPlainText();
                }
                $lccode = trim($lccode ?? '');
                if (empty($lccode)) {
                    continue;
                }

                $unitprice = $cleanExcelRate($sheet->getCell('F' . $row)->getValue());
                $updatebeg = $parseExcelDate($sheet->getCell('H' . $row)->getValue());
                $updateend = $parseExcelDate($sheet->getCell('I' . $row)->getValue());

                $data[] = [
                    'benefitplan'  => $sheet->getCell('B' . $row)->getValue(),
                    'cscode'       => $sheet->getCell('C' . $row)->getValue(),
                    'name'         => $sheet->getCell('D' . $row)->getValue(),
                    'unit'         => $sheet->getCell('E' . $row)->getValue(),
                    'unitprice'    => $unitprice,
                    'gyear'        => $sheet->getCell('G' . $row)->getValue(),
                    'updatebeg'    => $updatebeg,
                    'updateend'    => $updateend,
                    'updateflag'   => $sheet->getCell('J' . $row)->getValue(),
                    'tmlt'         => $sheet->getCell('K' . $row)->getValue(),
                    'tmlt_name'    => $sheet->getCell('L' . $row)->getValue(),
                    'lccode'       => $lccode,
                    'loinc'        => $sheet->getCell('N' . $row)->getValue(),
                    'exception'    => $sheet->getCell('O' . $row)->getValue(),
                    'stm_filename' => $file_name,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $data_) {
                \App\Models\Labcat_fdh::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.labcat_fdh')->with('success', $file_name);
    }

    public function labcat_fdh()
    {
        $items_i = $this->getLabItemsFdh();
        $items_p = $this->getLabPanelsFdh();
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_non_nhso()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NULL");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_price_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_tmlt_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND ln.tmlt <> l.tmlt_code");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND ln.tmlt <> sg.tmlt_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_loinc_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND ln.loinc <> l.loinc_code");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND ln.loinc <> sg.loinc_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_tmlt_missing_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND (l.tmlt_code IS NULL OR l.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND (sg.tmlt_code IS NULL OR sg.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_loinc_missing_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND (l.loinc_code IS NULL OR l.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND (sg.loinc_code IS NULL OR sg.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_export(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcelLabFDH([], $hosp_code . 'LabFDH' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);
        
        $labs = DB::connection('hosxp')->select("
            SELECT 
                'NHS/LGO/OFC' AS benefitplan,
                IFNULL(lf.cscode, '') AS cscode,
                l.lab_items_name AS name,
                'Test' AS unit,
                n.price AS unitprice,
                IFNULL(lf.gyear, '2026') AS gyear,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                IFNULL(lf.updateend, '') AS updateend,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                l.tmlt_code AS tmlt,
                IFNULL(lf.tmlt_name, '') AS tmlt_name,
                l.icode AS lccode,
                l.loinc_code AS loinc,
                IFNULL(lf.exception, '') AS exception
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                )
            ) lf ON lf.lccode = l.icode
            WHERE l.icode IN ({$where_icode})
            
            UNION ALL
            
            SELECT 
                'NHS/LGO/OFC' AS benefitplan,
                IFNULL(lf.cscode, '') AS cscode,
                sg.lab_items_sub_group_name AS name,
                'Profile' AS unit,
                n.price AS unitprice,
                IFNULL(lf.gyear, '2026') AS gyear,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                IFNULL(lf.updateend, '') AS updateend,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                sg.tmlt_code AS tmlt,
                IFNULL(lf.tmlt_name, '') AS tmlt_name,
                sg.group_icode AS lccode,
                sg.loinc_code AS loinc,
                IFNULL(lf.exception, '') AS exception
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=sg.group_icode
                )
            ) lf ON lf.lccode = sg.group_icode
            WHERE sg.group_icode IN ({$where_icode})
        ");

        return $this->exportToExcelLabFDH($labs, $hosp_code . 'LabFDH' . $seq . '.xlsx');
    }

    public function labcat_fdh_export_preview(Request $request)
    {
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);

        try {
            $labs = DB::connection('hosxp')->select("
                SELECT 
                    'NHS/LGO/OFC' AS benefitplan,
                    IFNULL(lf.cscode, '') AS cscode,
                    l.lab_items_name AS name,
                    'Test' AS unit,
                    n.price AS unitprice,
                    IFNULL(lf.gyear, '2026') AS gyear,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                    IFNULL(lf.updateend, '') AS updateend,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    l.tmlt_code AS tmlt,
                    IFNULL(lf.tmlt_name, '') AS tmlt_name,
                    l.icode AS lccode,
                    l.loinc_code AS loinc,
                    IFNULL(lf.exception, '') AS exception
                FROM lab_items l
                INNER JOIN nondrugitems n ON n.icode = l.icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                    WHERE dc.id = (
                        SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                    )
                ) lf ON lf.lccode = l.icode
                WHERE l.icode IN ({$where_icode})
                
                UNION ALL
                
                SELECT 
                    'NHS/LGO/OFC' AS benefitplan,
                    IFNULL(lf.cscode, '') AS cscode,
                    sg.lab_items_sub_group_name AS name,
                    'Profile' AS unit,
                    n.price AS unitprice,
                    IFNULL(lf.gyear, '2026') AS gyear,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                    IFNULL(lf.updateend, '') AS updateend,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    sg.tmlt_code AS tmlt,
                    IFNULL(lf.tmlt_name, '') AS tmlt_name,
                    sg.group_icode AS lccode,
                    sg.loinc_code AS loinc,
                    IFNULL(lf.exception, '') AS exception
                FROM lab_items_sub_group sg
                INNER JOIN nondrugitems n ON n.icode = sg.group_icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                    WHERE dc.id = (
                        SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=sg.group_icode
                    )
                ) lf ON lf.lccode = sg.group_icode
                WHERE sg.group_icode IN ({$where_icode})
            ");
            
            return response()->json([
                'success' => true,
                'data' => $labs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function exportToExcelLabFDH($data, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'row', 'สิทธิประโยชน์', 'รหัสกรมบัญชีกลาง', 'ชื่อ', 'หน่วย', 'ราคากลาง', 
            'gyear', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', 'flag', 'TMLT Code', 
            'TMLT Name', 'Lab Code', 'LOINC', 'Exception'
        ];
        
        // Write headers
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        // Write data
        $rowNum = 2;
        foreach ($data as $index => $row) {
            $rowArray = (array)$row;
            
            $rowData = [
                $rowNum - 1,
                $rowArray['benefitplan'] ?? '',
                $rowArray['cscode'] ?? '',
                $rowArray['name'] ?? '',
                $rowArray['unit'] ?? '',
                $rowArray['unitprice'] ?? '',
                $rowArray['gyear'] ?? '',
                $rowArray['updatebeg'] ?? '',
                $rowArray['updateend'] ?? '',
                $rowArray['updateflag'] ?? '',
                $rowArray['tmlt'] ?? '',
                $rowArray['tmlt_name'] ?? '',
                $rowArray['lccode'] ?? '',
                $rowArray['loinc'] ?? '',
                $rowArray['exception'] ?? ''
            ];

            $colIndex = 1;
            foreach ($rowData as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $rowNum, $val);
                $colIndex++;
            }
            $rowNum++;
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function labcat_chi_export(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcelLabChi([], $hosp_code . 'LabChi' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);
        
        $labs = DB::connection('hosxp')->select("
            SELECT 
                l.icode AS lccode,
                IFNULL(lf.billgroup, '7') AS billgroup,
                IFNULL(lf.cscode, '') AS cscode,
                IFNULL(l.tmlt_code, '') AS tmlt,
                IFNULL(l.loinc_code, '') AS loinc,
                'I' AS panel,
                l.lab_items_name AS name,
                IFNULL(lf.sflag, 'R') AS sflag,
                IFNULL(lf.chargecat, 'R') AS chargecat,
                n.price AS unitprice,
                'CS' AS benefitplan,
                IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                IFNULL(lf.rpdateend, '') AS rpdateend,
                IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                )
            ) lf ON lf.lccode = l.icode
            WHERE l.icode IN ({$where_icode})
            
            UNION ALL
            
            SELECT 
                sg.group_icode AS lccode,
                IFNULL(lf.billgroup, '7') AS billgroup,
                IFNULL(lf.cscode, '') AS cscode,
                IFNULL(sg.tmlt_code, '') AS tmlt,
                IFNULL(sg.loinc_code, '') AS loinc,
                'P' AS panel,
                sg.lab_items_sub_group_name AS name,
                IFNULL(lf.sflag, 'R') AS sflag,
                IFNULL(lf.chargecat, 'R') AS chargecat,
                n.price AS unitprice,
                'CS' AS benefitplan,
                IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                IFNULL(lf.rpdateend, '') AS rpdateend,
                IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=sg.group_icode
                )
            ) lf ON lf.lccode = sg.group_icode
            WHERE sg.group_icode IN ({$where_icode})
        ");

        return $this->exportToExcelLabChi($labs, $hosp_code . 'LabChi' . $seq . '.xlsx');
    }

    public function labcat_chi_export_preview(Request $request)
    {
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);

        try {
            $labs = DB::connection('hosxp')->select("
                SELECT 
                    l.icode AS lccode,
                    IFNULL(lf.billgroup, '7') AS billgroup,
                    IFNULL(lf.cscode, '') AS cscode,
                    IFNULL(l.tmlt_code, '') AS tmlt,
                    IFNULL(l.loinc_code, '') AS loinc,
                    'I' AS panel,
                    l.lab_items_name AS name,
                    IFNULL(lf.sflag, 'R') AS sflag,
                    IFNULL(lf.chargecat, 'R') AS chargecat,
                    n.price AS unitprice,
                    'CS' AS benefitplan,
                    IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                    IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                    IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                    IFNULL(lf.rpdateend, '') AS rpdateend,
                    IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
                FROM lab_items l
                INNER JOIN nondrugitems n ON n.icode = l.icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_chi dc 
                    WHERE dc.dateupd = (
                        SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                    )
                ) lf ON lf.lccode = l.icode
                WHERE l.icode IN ({$where_icode})
                
                UNION ALL
                
                SELECT 
                    sg.group_icode AS lccode,
                    IFNULL(lf.billgroup, '7') AS billgroup,
                    IFNULL(lf.cscode, '') AS cscode,
                    IFNULL(sg.tmlt_code, '') AS tmlt,
                    IFNULL(sg.loinc_code, '') AS loinc,
                    'P' AS panel,
                    sg.lab_items_sub_group_name AS name,
                    IFNULL(lf.sflag, 'R') AS sflag,
                    IFNULL(lf.chargecat, 'R') AS chargecat,
                    n.price AS unitprice,
                    'CS' AS benefitplan,
                    IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                    IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                    IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                    IFNULL(lf.rpdateend, '') AS rpdateend,
                    IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
                FROM lab_items_sub_group sg
                INNER JOIN nondrugitems n ON n.icode = sg.group_icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_chi dc 
                    WHERE dc.dateupd = (
                        SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=sg.group_icode
                    )
                ) lf ON lf.lccode = sg.group_icode
                WHERE sg.group_icode IN ({$where_icode})
            ");

            // Format data for preview
            foreach ($labs as &$row) {
                $row->unitprice = $this->formatChiPrice($row->unitprice);
                $row->reimbprice = $this->formatChiPrice($row->reimbprice);
                $row->updatebeg = $this->formatChiIsoDateTime($row->updatebeg);
                $row->updateend = $this->formatChiIsoDateTime($row->updateend);
                $row->rpdatebeg = $this->formatChiIsoDateTime($row->rpdatebeg);
                $row->rpdateend = $this->formatChiIsoDateTime($row->rpdateend);
                $row->dateupd = $this->formatChiIsoDateTime($row->dateupd);
            }
            
            return response()->json([
                'success' => true,
                'data' => $labs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function exportToExcelLabChi($data, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'LCCode', 'BillGroup', 'CsCode', 'TMLT', 'LOINC', 'Panel', 
            'Name', 'SFlag', 'ChargeCat', 'UnitPrice', 'BenefitPlan', 
            'ReimbPrice', 'UpdateFlag', 'UPDateBeg', 'UPDateEnd', 
            'RPDateBeg', 'RPDateEnd', 'DateUpd'
        ];
        
        // Write headers
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        
        // Write data
        $rowNum = 2;
        foreach ($data as $index => $row) {
            $rowArray = (array)$row;
            
            $rowData = [
                $rowArray['lccode'] ?? '',
                $rowArray['billgroup'] ?? '',
                $rowArray['cscode'] ?? '',
                $rowArray['tmlt'] ?? '',
                $rowArray['loinc'] ?? '',
                $rowArray['panel'] ?? '',
                $rowArray['name'] ?? '',
                $rowArray['sflag'] ?? '',
                $rowArray['chargecat'] ?? '',
                $this->formatChiPrice($rowArray['unitprice'] ?? null),
                $rowArray['benefitplan'] ?? '',
                $this->formatChiPrice($rowArray['reimbprice'] ?? null),
                $rowArray['updateflag'] ?? '',
                $this->formatChiIsoDateTime($rowArray['updatebeg'] ?? null),
                $this->formatChiIsoDateTime($rowArray['updateend'] ?? null),
                $this->formatChiIsoDateTime($rowArray['rpdatebeg'] ?? null),
                $this->formatChiIsoDateTime($rowArray['rpdateend'] ?? null),
                $this->formatChiIsoDateTime($rowArray['dateupd'] ?? null)
            ];

            $colIndex = 1;
            foreach ($rowData as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $rowNum, $val);
                $colIndex++;
            }
            $rowNum++;
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    private function formatChiIsoDateTime($value, $default = '')
    {
        if ($value === null || trim($value) === '' || trim($value) === '-') {
            return $default;
        }
        $val = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $val)) {
            return $val;
        }
        try {
            $cleaned = str_replace('T', ' ', $val);
            $dt = new \DateTime($cleaned);
            return $dt->format('Y-m-d\TH:i:s');
        } catch (\Exception $e) {
            return $default;
        }
    }

    private function formatChiPrice($value)
    {
        if ($value === null || trim($value) === '' || trim($value) === '-') {
            return '0.00';
        }
        $val = str_replace(',', '', $value);
        if (!is_numeric($val)) {
            return '0.00';
        }
        return number_format((float)$val, 2, '.', '');
    }
}

