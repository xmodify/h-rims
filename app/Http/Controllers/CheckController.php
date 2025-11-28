<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Drugcat_nhso;

class CheckController extends Controller
{
###################################################################################################################################################
//ข้อมูลปิดสิทธิ สปสช---------------------------------------------------------------------------------------------------------------------------
    public function nhso_endpoint(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        // อัปเดตค่าเก็บใน Session เผื่อครั้งถัดไป
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        $sql=DB::select('
            SELECT * FROM nhso_endpoint WHERE vstdate BETWEEN ? AND ?'
            ,[$start_date,$end_date]);

        return view('check.nhso_endpoint',compact('start_date','end_date','sql'));            
    }
###################################################################################################################################################
//ข้อมูล FDH Claim Status---------------------------------------------------------------------------------------------------------------------------
    public function fdh_claim_status(Request $request)
    {
        $start_date = $request->start_date ?: Session::get('start_date') ?: date('Y-m-d');
        $end_date = $request->end_date ?: Session::get('end_date') ?: date('Y-m-d');
        // อัปเดตค่าเก็บใน Session เผื่อครั้งถัดไป
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        $sql=DB::connection('hosxp')->select('
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
            GROUP BY i.an' ,[$start_date,$end_date,$start_date,$end_date]);

        return view('check.fdh_claim_status',compact('start_date','end_date','sql'));            
    }
####################################################################################################################################
//นำเข้า Drug Catalog-----------------------------------------------------------------------------------------------------------------

    public function drug_cat_nhso_save(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        Drugcat_nhso::truncate(); 

        $this->validate($request, [
            'file' => 'required|file|mimes:xls,xlsx'
        ]);
        $the_file = $request->file('file');
        $file_name = $request->file('file')->getClientOriginalName(); //ชื่อไฟล์

        try{
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            // $sheet        = $spreadsheet->getActiveSheet();
            $sheet        = $spreadsheet->setActiveSheetIndex(0);
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range( '2', $row_limit );
            // $row_range    = range( "!", $row_limit );
            $column_range = range( 'Y', $column_limit );
            $startcount = '2';
            // $row_range_namefile  = range( 9, $sheet->getCell( 'A' . $row )->getValue() );
            $data = array();
            foreach ($row_range as $row ) {

                $dc = $sheet->getCell( 'S' . $row )->getValue();          
                $dcday = substr($dc, 0, 2);
                $dcmo = substr($dc, 3, 2);
                $dcyear = substr($dc, 8, 4);  
                $datechange = $dcyear.'-'.$dcmo.'-'.$dcday;  

                $du = $sheet->getCell( 'T' . $row )->getValue();          
                $duday = substr($du, 0, 2);
                $dumo = substr($du, 3, 2);
                $duyear = substr($du, 8, 4);  
                $dateupdate = $duyear.'-'.$dumo.'-'.$duday;    
                
                $de = $sheet->getCell( 'U' . $row )->getValue();          
                $deday = substr($de, 0, 2);
                $demo = substr($de, 3, 2);
                $deyear = substr($de, 8, 4);  
                $dateeffective = $deyear.'-'.$demo.'-'.$deday;      

                $da = $sheet->getCell( 'X' . $row )->getValue();          
                $daday = substr($da, 0, 2);
                $damo = substr($da, 3, 2);
                $dayear = substr($da, 8, 4);  
                $date_approved = $dayear.'-'.$damo.'-'.$daday;    

                    $data[] = [
                        'hospdrugcode'      =>$sheet->getCell( 'A' . $row )->getValue(),
                        'productcat'        =>$sheet->getCell( 'B' . $row )->getValue(),
                        'tmtid'             =>$sheet->getCell( 'C' . $row )->getValue(),
                        'specprep'          =>$sheet->getCell( 'D' . $row )->getValue(),
                        'genericname'       =>$sheet->getCell( 'E' . $row )->getValue(),
                        'tradename'         =>$sheet->getCell( 'F' . $row )->getValue(),
                        'dfscode'           =>$sheet->getCell( 'G' . $row )->getValue(),
                        'dosageform'        =>$sheet->getCell( 'H' . $row )->getValue(),
                        'strength'          =>$sheet->getCell( 'I' . $row )->getValue(),
                        'content'           =>$sheet->getCell( 'J' . $row )->getValue(),
                        'unitprice'         =>$sheet->getCell( 'K' . $row )->getValue(),
                        'distributor'       =>$sheet->getCell( 'L' . $row )->getValue(),
                        'manufacturer'      =>$sheet->getCell( 'M' . $row )->getValue(),
                        'ised'              =>$sheet->getCell( 'N' . $row )->getValue(),
                        'ndc24'             =>$sheet->getCell( 'O' . $row )->getValue(),
                        'packsize'          =>$sheet->getCell( 'P' . $row )->getValue(),
                        'packprice'         =>$sheet->getCell( 'Q' . $row )->getValue(),
                        'updateflag'        =>$sheet->getCell( 'R' . $row )->getValue(),
                        'datechange'        =>$datechange,
                        'dateupdate'        =>$dateupdate,
                        'dateeffective'     =>$dateeffective,
                        'ised_approved'     =>$sheet->getCell( 'V' . $row )->getValue(),
                        'ndc24_approved'    =>$sheet->getCell( 'W' . $row )->getValue(),
                        'date_approved'     =>$date_approved,
                        'ised_status'       =>$sheet->getCell( 'Y' . $row )->getValue(),
                        'stm_filename'      =>$file_name,
                    ]; 
                $startcount++;            
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $key => $data_) {              
                Drugcat_nhso::insert($data_);                 
            }
        }     

        catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            return back()->withErrors('There was a problem uploading the data!');
        }       
            
        return redirect()->route('drug_cat')->with('success',$file_name);
    }
//Drug ทั้งหมดใน HOSxP-----------------------------------------------------------------------------------------------------------------------------------------
    public function drug_cat()
    {
        $drug =  DB::connection('hosxp')->select('
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE "4%","Y","") AS herb,IF(nd.hospdrugcode IS NULL,"N","Y") AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = "-" OR d.drugaccount = "") THEN"N" WHEN drugaccount <> "" THEN "E" END AS ISED,d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM hrims.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM hrims.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ("A","U","E"))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = "Y" AND d.`name` NOT LIKE "*%" AND d.`name` NOT LIKE "(ยาผู้ป่วย)%"
            ORDER BY d.NAME,d.strength,d.units');

        return view('check.drug_cat',compact('drug'));            
    }
//Drug ไม่พบที่ NHSO----------------------------------------------------------------------------------------------------------------------------------------------
    public function drug_cat_non_nhso()
    {
        $drug =  DB::connection('hosxp')->select('
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE "4%","Y","") AS herb,IF(nd.hospdrugcode IS NULL,"N","Y") AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = "-" OR d.drugaccount = "") THEN"N" WHEN drugaccount <> "" THEN "E" END AS ISED,d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM hrims.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM hrims.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ("A","U","E"))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = "Y" AND d.`name` NOT LIKE "*%" AND d.`name` NOT LIKE "(ยาผู้ป่วย)%" AND nd.hospdrugcode IS NULL  
            ORDER BY d.NAME,d.strength,d.units');

        return view('check.drug_cat',compact('drug'));            
    }
//Drug Catalog ราคาไม่ตรงกับ HOSxP-------------------------------------------------------------------------------------------------------------------------------
    public function drug_cat_nhso_price_notmatch_hosxp()
    {
        $drug =  DB::connection('hosxp')->select('
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE "4%","Y","") AS herb,IF(nd.hospdrugcode IS NULL,"N","Y") AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = "-" OR d.drugaccount = "") THEN"N" WHEN drugaccount <> "" THEN "E" END AS ISED,d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm    
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3           
            LEFT JOIN (SELECT dc.* FROM hrims.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM hrims.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ("A","U","E"))) nd ON nd.hospdrugcode=d.icode             
            WHERE d.istatus = "Y" AND d.`name` NOT LIKE "*%" AND d.`name` NOT LIKE "(ยาผู้ป่วย)%" AND nd.unitprice <> d.unitprice
            ORDER BY d.NAME,d.strength,d.units');

        return view('check.drug_cat',compact('drug'));            
    }
//Drug Catalog รหัส TMT ไม่ตรงกับ HOSxP
    public function drug_cat_nhso_tmt_notmatch_hosxp()
    {
        $drug =  DB::connection('hosxp')->select('
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE "4%","Y","") AS herb,IF(nd.hospdrugcode IS NULL,"N","Y") AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = "-" OR d.drugaccount = "") THEN"N" WHEN drugaccount <> "" THEN "E" END AS ISED,d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm   
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM hrims.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM hrims.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ("A","U","E"))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = "Y" AND d.`name` NOT LIKE "*%" AND d.`name` NOT LIKE "(ยาผู้ป่วย)%" AND nd.tmtid <> d3.ref_code
            ORDER BY d.NAME,d.strength,d.units');

        return view('check.drug_cat',compact('drug'));            
    }
//Drug Catalog รหัส 24 หลักไม่ตรงกับ HOSxP---------------------------------------------------------------------------------------------------------------------------
    public function drug_cat_nhso_code24_notmatch_hosxp()
    {
        $drug =  DB::connection('hosxp')->select('
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE "4%","Y","") AS herb,IF(nd.hospdrugcode IS NULL,"N","Y") AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = "-" OR d.drugaccount = "") THEN"N" WHEN drugaccount <> "" THEN "E" END AS ISED,d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm 
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM hrims.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM hrims.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ("A","U","E"))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = "Y" AND d.`name` NOT LIKE "*%" AND d.`name` NOT LIKE "(ยาผู้ป่วย)%" AND nd.ndc24 <> d2.ref_code
            ORDER BY d.NAME,d.strength,d.units');

        return view('check.drug_cat',compact('drug'));            
    }
//Drug Catalog ยาสมุนไพร---------------------------------------------------------------------------------------------------------------------------
    public function drug_cat_herb()
    {
        $drug =  DB::connection('hosxp')->select('
            SELECT  d.icode,CONCAT(d.`name`,SPACE(1),d.strength) AS dname,d.units,d.ttmt_code,
			IF(d2.ref_code LIKE "4%","Y","") AS herb,IF(nd.hospdrugcode IS NULL,"N","Y") AS chk_nhso_drugcat,
            d.unitprice AS price_hos,nd.unitprice AS price_nhso,d3.ref_code AS code_tmt_hos,nd.tmtid AS code_tmt_nhso,            
            d2.ref_code AS code_24_hos,nd.ndc24 AS code_24_nhso,i.NAME AS income_name,  
            CASE WHEN (d.drugaccount = "-" OR d.drugaccount = "") THEN"N" WHEN drugaccount <> "" THEN "E" END AS ISED,d.drugaccount,
            IFNULL(d.generic_name,d.`name`) AS GenericName,d.trade_name AS TradeName,d.dosageform AS DosageForm     
            FROM drugitems d
            LEFT JOIN ttmt_code t ON t.ttmt_code=d.ttmt_code
            LEFT JOIN income i ON i.income = d.income
            LEFT JOIN drugitems_ref_code d2 ON d2.icode=d.icode AND d2.drugitems_ref_code_type_id=1
            LEFT JOIN drugitems_ref_code d3 ON d3.icode=d.icode AND d3.drugitems_ref_code_type_id=3
            LEFT JOIN (SELECT dc.* FROM hrims.drugcat_nhso dc WHERE  dc.date_approved = (SELECT MAX(dc1.date_approved) 
                FROM hrims.drugcat_nhso dc1 WHERE dc.hospdrugcode=dc1.hospdrugcode AND dc1.updateflag IN ("A","U","E"))) nd ON nd.hospdrugcode=d.icode 
            WHERE d.istatus = "Y" AND d.`name` NOT LIKE "*%" AND d.`name` NOT LIKE "(ยาผู้ป่วย)%" AND d2.ref_code LIKE "4%"
            ORDER BY d.NAME,d.strength,d.units');

        return view('check.drug_cat',compact('drug'));            
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

        return view('check.pttype',compact('pttype','pttype_close'));            
    }
//สิทธิการักษา nhso_subinscl---------------------------------------------------------------------------------------------------------------------------
    public function nhso_subinscl()
    {
        $subinscl =  DB::connection('hosxp')->select('
            SELECT s.*,p.pttype,p.`name` AS pttype_name,p.hipdata_code 
            FROM hrims.subinscl s
            LEFT JOIN pttype p ON p.pttype=s.`code`');


        return view('check.nhso_subinscl',compact('subinscl'));            
    }
  
}
