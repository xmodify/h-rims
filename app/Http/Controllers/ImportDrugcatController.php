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

class ImportDrugcatController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_import !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'นำเข้าข้อมูล'], 403);
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

        return redirect()->route('import.drugcat_nhso')->with('success', $file_name);
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.hospdrugcode IS NULL  
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.unitprice <> d.unitprice
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.tmtid <> d3.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.ndc24 <> d2.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND d2.ref_code LIKE '4%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' 
              AND nd.hospdrugcode IS NOT NULL 
              AND CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END <> CASE WHEN (nd.ised LIKE 'E%') THEN 'E' ELSE 'N' END
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' 
              AND (d2.ref_code IS NULL OR d2.ref_code = '') 
              AND nd.ndc24 IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' 
              AND (d3.ref_code IS NULL OR d3.ref_code = '') 
              AND nd.tmtid IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_nhso', compact('drug'));
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

        return redirect()->route('import.drugcat_chi')->with('success', $file_name);
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.hospdrugcode IS NULL  
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.unitprice <> d.unitprice
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.tmtid <> d3.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.ndc24 <> d2.ref_code
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND d2.ref_code LIKE '4%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%'
              AND nd.hospdrugcode IS NOT NULL 
              AND CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END <> CASE WHEN (nd.ised LIKE 'E%') THEN 'E' ELSE 'N' END
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%'
              AND (d2.ref_code IS NULL OR d2.ref_code = '') 
              AND nd.ndc24 IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
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
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm,
            COALESCE(d.sks_product_category_id, sd.sks_product_category_id) AS prdcat_hos, nd.productcat AS prdcat_nhso
            FROM drugitems d
            LEFT JOIN s_drugitems sd ON sd.icode = d.icode
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%'
              AND (d3.ref_code IS NULL OR d3.ref_code = '') 
              AND nd.tmtid IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_chi', compact('drug'));
    }

    //ดึงและประมวลผลข้อมูลสำหรับส่งออก สกส (CSMBS)------------------------------------------------------------------
    private function getChiExportDrugs($icodes, $type = 'new')
    {
        if (empty($icodes) || !is_array($icodes)) {
            return [];
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";
        
        $has_sks = Schema::connection('hosxp')->hasTable('sks_drugcatalog');
        $s_join = $has_sks ? "LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode" : "";
        $s_spec = $has_sks ? "IFNULL(s.SpecPrep, '')" : "''";
        $s_trade = $has_sks ? "s.TradeName" : "NULL";
        $s_dfs = $has_sks ? "s.DSFCode" : "NULL";
        $s_dose = $has_sks ? "s.DosageForm" : "NULL";
        $s_strength = $has_sks ? "s.Strength" : "NULL";
        $s_date = $has_sks ? "s.DateEffective" : "NULL";

        $updateFlag = 'A';
        $dateChangeExpr = "'' AS DateChange";
        $dateUpdateExpr = "'' AS DateUpdate";
        $dateEffectiveExpr = "DATE_FORMAT(IFNULL(nd.dateeffective, IFNULL({$s_date}, IFNULL(d.last_update, NOW()))), '%d/%m/%Y') AS DateEffective";
        
        $sksEffectiveCondition = $has_sks 
            ? "WHEN s.DateEffective IS NOT NULL AND s.DateEffective >= CURRENT_DATE() THEN DATE_ADD(s.DateEffective, INTERVAL 1 DAY)" 
            : "";

        if ($type === 'edit') {
            $updateFlag = 'E';
            $dateChangeExpr = "DATE_FORMAT(IFNULL(d.last_update, NOW()), '%d/%m/%Y') AS DateChange";
            $dateEffectiveExpr = "DATE_FORMAT(CASE 
                WHEN nd.dateeffective IS NOT NULL AND nd.dateeffective >= CURRENT_DATE() THEN DATE_ADD(nd.dateeffective, INTERVAL 1 DAY)
                WHEN nd.dateeffective IS NOT NULL THEN CURRENT_DATE()
                {$sksEffectiveCondition}
                ELSE CURRENT_DATE()
            END, '%d/%m/%Y') AS DateEffective";
        } elseif ($type === 'update') {
            $updateFlag = 'U';
            $dateUpdateExpr = "DATE_FORMAT(IFNULL(d.lastupdatestdprice, NOW()), '%d/%m/%Y') AS DateUpdate";
            $dateEffectiveExpr = "DATE_FORMAT(CASE 
                WHEN nd.dateeffective IS NOT NULL AND nd.dateeffective >= CURRENT_DATE() THEN DATE_ADD(nd.dateeffective, INTERVAL 1 DAY)
                WHEN nd.dateeffective IS NOT NULL THEN CURRENT_DATE()
                {$sksEffectiveCondition}
                ELSE CURRENT_DATE()
            END, '%d/%m/%Y') AS DateEffective";
        }

        $drugs = DB::connection('hosxp')->select("
            SELECT  
                d.icode AS HospDrugCode,
                d.sks_product_category_id AS ProductCat,
                COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS TMTID,
                {$s_spec} AS SpecPrep,
                IFNULL(d.generic_name, d.`name`) AS GenericName,
                IFNULL(d.trade_name, {$s_trade}) AS TradeName,
                IFNULL(d.sks_dfs_code, {$s_dfs}) AS DFSCode,
                IFNULL(d.dosageform, {$s_dose}) AS DosageForm,
                IFNULL(d.strength, {$s_strength}) AS Strength,
                IFNULL(d.dosageform, {$s_dose}) AS Content,
                d.unitprice AS UnitPrice,
                dr.comp AS Distributor,
                CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN tc.manufacturer ELSE dr.manufacturer END AS Manufacturer,
                CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' WHEN d.drugaccount <> '' THEN 'E' END AS ISED,
                COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS NDC24,
                CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE p.provis_medication_unit_name END AS Packsize,
                d.unitprice AS Packprice,
                '{$updateFlag}' AS UpdateFlag,
                {$dateChangeExpr},
                {$dateUpdateExpr},
                {$dateEffectiveExpr},
                NULL AS Reimbprice
            FROM drugitems d
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''))
            LEFT JOIN drugitems_register_unique dr ON dr.std_code = COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''))
            LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
            {$s_join}
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_chi dc WHERE dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM {$local_db}.drugcat_chi dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ('A','U','E'))) nd ON nd.hospdrugcode=d.icode
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
              {$where_icode}
            ORDER BY d.icode
        ");

        return $drugs;
    }

    //ส่งออกรายการใหม่ สกส (กรณีไม่พบที่ สกส) - UpdateFlag = A-------------------------------------------------------------------------------------------------
    public function drugcat_chi_export_new(Request $request, $seq = '0001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 4, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        $drugs = $this->getChiExportDrugs($icodes, 'new');

        return $this->exportToExcel($drugs, $hosp_code . 'DrugN' . $seq . '.xlsx');
    }

    //ส่งออกรายการแก้ไข สกส (กรณีต้องการแก้ไขข้อมูลผลิตภัณฑ์) - UpdateFlag = E--------------------------------------------------------------------------------------
    public function drugcat_chi_export_edit(Request $request, $seq = '0001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 4, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        $drugs = $this->getChiExportDrugs($icodes, 'edit');

        return $this->exportToExcel($drugs, $hosp_code . 'DrugE' . $seq . '.xlsx');
    }

    //ส่งออกรายการแก้ไข สกส (กรณีต้องการแก้ไขราคาส่งขาย) - UpdateFlag = U--------------------------------------------------------------------------------------
    public function drugcat_chi_export_update(Request $request, $seq = '0001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 4, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        $drugs = $this->getChiExportDrugs($icodes, 'update');

        return $this->exportToExcel($drugs, $hosp_code . 'DrugU' . $seq . '.xlsx');
    }

    public function drugcat_chi_export_preview(Request $request)
    {
        $type = $request->input('type', 'new');
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการยาอย่างน้อย 1 รายการ', 'data' => []]);
        }

        try {
            $drugs = $this->getChiExportDrugs($icodes, $type);
            
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
                if (in_array($colIndex, [1, 3, 15])) { // HOSPDRUGCODE, TMTID, NDC24
                    $sheet->setCellValueExplicit($colLetter . $rowNum, (string)($val ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($colLetter . $rowNum, $val);
                }
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
            $row_range    = range(2, $row_limit);

            $parseExcelDate = function ($value) {
                if (empty($value) || $value === '-' || trim((string)$value) === '') {
                    return null;
                }
                $value = trim((string)$value);
                if (is_numeric($value)) {
                    try {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
                foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:s.u\Z', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d', 'd-m-Y H:i:s', 'd-m-Y H:i', 'd-m-Y'] as $format) {
                    try {
                        $dt = \Carbon\Carbon::createFromFormat($format, $value);
                        if ($dt->year > 2400) {
                            $dt->subYears(543);
                        }
                        return $dt->format('Y-m-d');
                    } catch (\Exception $e) {
                        // continue
                    }
                }
                try {
                    $dt = \Carbon\Carbon::parse($value);
                    if ($dt->year > 2400) {
                        $dt->subYears(543);
                    }
                    return $dt->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            };

            $cleanExcelRate = function ($val) {
                if ($val === null || $val === '-' || trim((string)$val) === '') {
                    return null;
                }
                $val = str_replace(',', '', (string)$val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = array();
            foreach ($row_range as $row) {
                $hospdrugcode = trim((string)$sheet->getCell('B' . $row)->getValue());
                if (empty($hospdrugcode)) {
                    continue;
                }

                $unitprice = $cleanExcelRate($sheet->getCell('J' . $row)->getValue());
                $packprice = $cleanExcelRate($sheet->getCell('Q' . $row)->getValue());
                
                $datechange = $parseExcelDate($sheet->getCell('R' . $row)->getValue());
                $dateupdate = $parseExcelDate($sheet->getCell('S' . $row)->getValue());
                $dateeffective = $parseExcelDate($sheet->getCell('T' . $row)->getValue());

                $data[] = [
                    'hospdrugcode'      => $hospdrugcode,
                    'productcat'        => $sheet->getCell('C' . $row)->getValue(),
                    'tmtid'             => $sheet->getCell('D' . $row)->getValue(),
                    'genericname'       => $sheet->getCell('E' . $row)->getValue(),
                    'tradename'         => $sheet->getCell('F' . $row)->getValue(),
                    'dfscode'           => $sheet->getCell('G' . $row)->getValue(),
                    'dosageform'        => $sheet->getCell('H' . $row)->getValue(),
                    'strength'          => $sheet->getCell('I' . $row)->getValue(),
                    'unitprice'         => $unitprice,
                    'distributor'       => $sheet->getCell('K' . $row)->getValue(),
                    'manufacturer'      => $sheet->getCell('L' . $row)->getValue(),
                    'ised'              => $sheet->getCell('M' . $row)->getValue(),
                    'specprep'          => $sheet->getCell('N' . $row)->getValue(),
                    'ndc24'             => (string)$sheet->getCell('O' . $row)->getValue(),
                    'packsize'          => $sheet->getCell('P' . $row)->getValue(),
                    'packprice'         => $packprice,
                    'datechange'        => $datechange,
                    'dateupdate'        => $dateupdate,
                    'dateeffective'     => $dateeffective,
                    'filename'          => $sheet->getCell('U' . $row)->getValue(),
                    'hospcode'          => (string)$sheet->getCell('V' . $row)->getValue(),
                    'date_import'       => date('Y-m-d'),
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

        return redirect()->route('import.drugcat_fdh')->with('success', $file_name);
    }

    //Drug ทั้งหมดใน HOSxP (FDH)-----------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug ไม่พบที่ FDH----------------------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_non_nhso()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.hospdrugcode IS NULL  
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ราคาไม่ตรงกับ HOSxP (FDH)-------------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_price_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm    
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.unitprice <> d.unitprice
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog รหัส TMT ไม่ตรงกับ HOSxP (FDH)
    public function drugcat_fdh_tmt_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm   
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.tmtid <> COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '')
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog รหัส 24 หลักไม่ตรงกับ HOSxP (FDH)---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_code24_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm 
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND nd.ndc24 <> COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '')
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ยาสมุนไพร (FDH)---------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_herb()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' AND d2.ref_code LIKE '4%'
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog บัญชียาหลักไม่ตรงกัน (ED/NED Mismatch - FDH)-----------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_ised_notmatch_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' 
              AND nd.hospdrugcode IS NOT NULL 
              AND CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END <> CASE WHEN (nd.ised LIKE 'E%') THEN 'E' ELSE 'N' END
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ยังไม่ผูกรหัส 24 หลักใน HOSxP (FDH)-------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_code24_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' 
              AND (d2.ref_code IS NULL OR d2.ref_code = '') AND (d.did IS NULL OR d.did = '')
              AND nd.ndc24 IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //Drug Catalog ยังไม่ผูกรหัส TMT ใน HOSxP (FDH)-----------------------------------------------------------------------------------------------------------------------------
    public function drugcat_fdh_tmt_missing_hosxp()
    {
        $local_db = config('database.connections.mysql.database');
        $drug =  DB::connection('hosxp')->select("
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE '4%','Y','') AS herb,IF(nd.hospdrugcode IS NULL,'N','Y') AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '') THEN 'N' ELSE 'E' END AS ised_hos, nd.ised AS ised_nhso, d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%' AND d.`name` NOT LIKE '(ยาผู้ป่วย)%' AND d.`name` NOT LIKE 'ยาเดิม%' AND d.`name` NOT LIKE 'ยาผู้ป่วย%' AND d.`name` NOT LIKE '%รพ.อื่น%' 
              AND (d3.ref_code IS NULL OR d3.ref_code = '') AND (d.sks_drug_code IS NULL OR d.sks_drug_code = '')
              AND nd.tmtid IS NOT NULL
            ORDER BY d.NAME,d.strength,d.units");

        return view('import.drugcat_fdh', compact('drug'));
    }

    //ดึงและประมวลผลข้อมูลสำหรับส่งออก FDH------------------------------------------------------------------
    private function getFdhExportDrugs($icodes)
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        
        if (empty($icodes) || !is_array($icodes)) {
            return [];
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = " AND d.icode IN (" . implode(',', $quoted) . ") ";
        
        $has_sks = Schema::connection('hosxp')->hasTable('sks_drugcatalog');
        $s_join = $has_sks ? "LEFT JOIN sks_drugcatalog s ON s.HospDrugCode = d.icode" : "";
        $s_spec = $has_sks ? "IFNULL(s.SpecPrep, '')" : "''";
        $s_trade = $has_sks ? "s.TradeName" : "NULL";
        $s_dfs = $has_sks ? "s.DSFCode" : "NULL";
        $s_dose = $has_sks ? "s.DosageForm" : "NULL";
        $s_strength = $has_sks ? "s.Strength" : "NULL";
        $s_date = $has_sks ? "s.DateEffective" : "NULL";
        $s_datechange = $has_sks ? "s.DateChange" : "NULL";
        $s_dateupdate = $has_sks ? "s.DateUpdate" : "NULL";

        $rawDrugs = DB::connection('hosxp')->select("
            SELECT  
                d.icode AS HospDrugCode,
                IFNULL(d.sks_product_category_id, '1') AS ProductCat,
                COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''), '') AS TMTID,
                IFNULL(d.generic_name, d.`name`) AS GenericName,
                IFNULL(d.trade_name, IFNULL({$s_trade}, '')) AS TradeName,
                IFNULL(d.sks_dfs_code, IFNULL({$s_dfs}, '')) AS DFSCode,
                IFNULL(d.dosageform, IFNULL({$s_dose}, '')) AS DosageForm,
                IFNULL(d.strength, IFNULL({$s_strength}, '')) AS Strength,
                d.unitprice AS UnitPrice,
                IFNULL(dr.comp, '') AS Distributor,
                CASE WHEN dr.manufacturer IS NULL OR dr.manufacturer = '' THEN IFNULL(tc.manufacturer, '') ELSE dr.manufacturer END AS Manufacturer,
                CASE WHEN (d.drugaccount = '-' OR d.drugaccount = '' OR d.drugaccount IS NULL) THEN 'N' ELSE 'E' END AS ISED,
                {$s_spec} AS SpecPrep,
                COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''), '') AS NDC24,
                CASE WHEN d.provis_medication_unit_code = '' OR d.provis_medication_unit_code IS NULL THEN d.units ELSE IFNULL(p.provis_medication_unit_name, d.units) END AS Packsize,
                d.unitprice AS Packprice,
                nd.datechange AS fdh_datechange,
                nd.dateupdate AS fdh_dateupdate,
                nd.dateeffective AS fdh_dateeffective,
                {$s_datechange} AS sks_datechange,
                {$s_dateupdate} AS sks_dateupdate,
                {$s_date} AS sks_dateeffective,
                d.last_update,
                IFNULL(nd.filename, '') AS FileName,
                '{$hosp_code}' AS HospCode
            FROM drugitems d
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN tmt_tpu_code tc ON tc.tpu_code = COALESCE(NULLIF(d3.ref_code, ''), NULLIF(d.sks_drug_code, ''))
            LEFT JOIN drugitems_register_unique dr ON dr.std_code = COALESCE(NULLIF(d2.ref_code, ''), NULLIF(d.did, ''))
            LEFT JOIN provis_medication_unit p ON p.provis_medication_unit_code = d.provis_medication_unit_code 
            {$s_join}
            LEFT JOIN (SELECT dc.* FROM {$local_db}.drugcat_fdh dc WHERE dc.id = (SELECT MAX(dc1.id) 
                FROM {$local_db}.drugcat_fdh dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode)) nd ON nd.hospdrugcode=d.icode
            WHERE d.istatus = 'Y' AND d.`name` NOT LIKE '*%'
              {$where_icode}
            ORDER BY d.icode
        ");

        $today = \Carbon\Carbon::now();
        $formatDate = function($val, $fallback = null) use ($today) {
            if (empty($val) || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') {
                $val = $fallback;
            }
            if (empty($val) || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') {
                $val = $today;
            }
            try {
                $dt = \Carbon\Carbon::parse($val);
                if ($dt->year > 2400) {
                    $dt->subYears(543);
                }
                if ($dt->gt($today)) {
                    $dt = $today->copy();
                }
                return $dt->format('Y-m-d\TH:i:s\Z');
            } catch (\Exception $e) {
                return $today->format('Y-m-d\TH:i:s\Z');
            }
        };

        $drugs = [];
        foreach ($rawDrugs as $row) {
            $dateEffective = $formatDate($row->fdh_dateeffective, $row->sks_dateeffective ?: ($row->last_update ?: $today));
            $dateUpdate    = $formatDate($row->fdh_dateupdate, $row->sks_dateupdate ?: ($row->last_update ?: $dateEffective));
            $dateChange    = $formatDate($row->fdh_datechange, $row->sks_datechange ?: ($row->last_update ?: $dateEffective));

            $drugs[] = (object)[
                'HospDrugCode' => (string)($row->HospDrugCode ?? ''),
                'ProductCat'   => (string)($row->ProductCat ?? '1'),
                'TMTID'        => (string)($row->TMTID ?? ''),
                'GenericName'  => (string)($row->GenericName ?? ''),
                'TradeName'    => (string)($row->TradeName ?? ''),
                'DFSCode'      => (string)($row->DFSCode ?? ''),
                'DosageForm'   => (string)($row->DosageForm ?? ''),
                'Strength'     => (string)($row->Strength ?? ''),
                'UnitPrice'    => $row->UnitPrice !== null ? (float)$row->UnitPrice : 0,
                'Distributor'  => (string)($row->Distributor ?? ''),
                'Manufacturer' => (string)($row->Manufacturer ?? ''),
                'ISED'         => (string)($row->ISED ?? 'N'),
                'SpecPrep'     => (string)($row->SpecPrep ?? ''),
                'NDC24'        => (string)($row->NDC24 ?? ''),
                'Packsize'     => (string)($row->Packsize ?? ''),
                'Packprice'    => $row->Packprice !== null ? (float)$row->Packprice : ($row->UnitPrice !== null ? (float)$row->UnitPrice : 0),
                'DateChange'   => $dateChange,
                'DateUpdate'   => $dateUpdate,
                'DateEffective'=> $dateEffective,
                'FileName'     => (string)($row->FileName ?? ''),
                'HospCode'     => (string)($row->HospCode ?? $hosp_code),
            ];
        }

        return $drugs;
    }

    //ส่งออกรายการ FDH-------------------------------------------------------------------------------------------------
    public function drugcat_fdh_export(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        $drugs = $this->getFdhExportDrugs($icodes);

        return $this->exportToExcelFDH($drugs, $hosp_code . 'DrugFDH' . $seq . '.xlsx');
    }

    //ส่งออก Preview FDH-------------------------------------------------------------------------------------------------
    public function drugcat_fdh_export_preview(Request $request)
    {
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        try {
            $drugs = $this->getFdhExportDrugs($icodes);
            
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
                if (in_array($colIndex, [2, 3, 4, 7, 13, 14, 15, 18, 19, 20, 21, 22])) {
                    $sheet->setCellValueExplicit($colLetter . $rowNum, (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($colLetter . $rowNum, $val);
                }
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
