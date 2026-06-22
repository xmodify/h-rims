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

class CheckDrugcatController extends Controller
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
}
