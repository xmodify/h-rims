<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Stm_ucs;
use App\Models\Stm_ucsexcel;

class ImportController extends Controller
{
//Check Login
public function __construct()
    {
        $this->middleware('auth');
    }

//Create stm_ucs
public function stm_ucs(Request $request)
{  
    $stm_ucs=DB::select('
        SELECT IF(SUBSTRING(stm_filename,11) LIKE "O%","OPD","IPD") AS dep,
        stm_filename,COUNT(DISTINCT repno) AS repno,COUNT(cid) AS count_cid,SUM(charge) AS charge,
        SUM(fund_ip_payrate) AS fund_ip_payrate,SUM(receive_total) AS receive_total FROM stm_ucs 
        GROUP BY stm_filename ORDER BY stm_filename DESC');

    return view('import.stm_ucs',compact('stm_ucs'));
}

// stm_ucs_save
public function stm_ucs_save(Request $request)
    {
        // Set the execution time to 300 seconds (5 minutes)
        set_time_limit(300);

        $this->validate($request, [
            'file' => 'required|file|mimes:xls,xlsx'
        ]);
        $the_file = $request->file('file');
        $file_name = $request->file('file')->getClientOriginalName(); //ชื่อไฟล์

        try{
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->setActiveSheetIndex(2); //sheet
            $row_limit    = $sheet->getHighestDataRow();
            // $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range( '15', $row_limit );
            $startcount = '15';
            
            $data = array();
            foreach ($row_range as $row ) {

                $adm = $sheet->getCell( 'H' . $row )->getValue(); 
                $day = substr($adm, 0, 2);
                $mo = substr($adm, 3, 2);
                $year = substr($adm, 6, 4);     
                $admtime = substr($adm, 11, 8);  
                $datetimeadm = $year.'-'.$mo.'-'.$day.' '.$admtime;

                $dch = $sheet->getCell( 'I' . $row )->getValue();
                $dchday = substr($dch, 0, 2);
                $dchmo = substr($dch, 3, 2);
                $dchyear = substr($dch, 6, 4);
                $dchtime = substr($dch, 11, 8);
                $datetimedch = $dchyear.'-'.$dchmo.'-'.$dchday.' '.$dchtime;    
                
                $s = $sheet->getCell( 'S' . $row )->getValue();
                $del_s = str_replace(",","",$s);
                $t = $sheet->getCell( 'T' . $row )->getValue();
                $del_t = str_replace(",","",$t);
                $u = $sheet->getCell( 'U' . $row )->getValue();
                $del_u = str_replace(",","",$u);
                $v= $sheet->getCell( 'V' . $row )->getValue();
                $del_v = str_replace(",","",$v);
                $w = $sheet->getCell( 'W' . $row )->getValue();
                $del_w = str_replace(",","",$w);
                $x = $sheet->getCell( 'X' . $row )->getValue();
                $del_x = str_replace(",","",$x);
                $y = $sheet->getCell( 'Y' . $row )->getValue();
                $del_y = str_replace(",","",$y);
                $z = $sheet->getCell( 'Z' . $row )->getValue();
                $del_z = str_replace(",","",$z);
                $aa = $sheet->getCell( 'AA' . $row )->getValue();
                $del_aa = str_replace(",","",$aa);
                $ab = $sheet->getCell( 'AB' . $row )->getValue();
                $del_ab = str_replace(",","",$ab);
                $ac = $sheet->getCell( 'AC' . $row )->getValue();
                $del_ac = str_replace(",","",$ac);
                $ad = $sheet->getCell( 'AD' . $row )->getValue();
                $del_ad = str_replace(",","",$ad);
                $ae = $sheet->getCell( 'AE' . $row )->getValue();
                $del_ae = str_replace(",","",$ae);
                $af = $sheet->getCell( 'AF' . $row )->getValue();
                $del_af = str_replace(",","",$af);
                $ag = $sheet->getCell( 'AG' . $row )->getValue();
                $del_ag = str_replace(",","",$ag);
                $ah = $sheet->getCell( 'AH' . $row )->getValue();
                $del_ah = str_replace(",","",$ah);
                $ai = $sheet->getCell( 'AI' . $row )->getValue();
                $del_ai = str_replace(",","",$ai);
                $aj = $sheet->getCell( 'AJ' . $row )->getValue();
                $del_aj = str_replace(",","",$aj);
                $ak = $sheet->getCell( 'AK' . $row )->getValue();
                $del_ak = str_replace(",","",$ak);
                $al = $sheet->getCell( 'AL' . $row )->getValue();
                $del_al = str_replace(",","",$al);

                    $data[] = [
                        'repno'                         =>$sheet->getCell( 'A' . $row )->getValue(),
                        'no'                            =>$sheet->getCell( 'B' . $row )->getValue(),
                        'tran_id'                       =>$sheet->getCell( 'C' . $row )->getValue(),
                        'hn'                            =>$sheet->getCell( 'D' . $row )->getValue(),
                        'an'                            =>$sheet->getCell( 'E' . $row )->getValue(),
                        'cid'                           =>$sheet->getCell( 'F' . $row )->getValue(),
                        'pt_name'                       =>$sheet->getCell( 'G' . $row )->getValue(),                    
                        'datetimeadm'                   =>$datetimeadm,
                        'vstdate'                       => date('Y-m-d', strtotime($datetimeadm)),
                        'vsttime'                       => date('H:i:s', strtotime($datetimeadm)),
                        'datetimedch'                   =>$datetimedch,
                        'dchdate'                       => date('Y-m-d', strtotime($datetimedch)),
                        'dchtime'                       => date('H:i:s', strtotime($datetimedch)),
                        'maininscl'                     =>$sheet->getCell( 'J' . $row )->getValue(),
                        'projcode'                      =>$sheet->getCell( 'K' . $row )->getValue(),
                        'charge'                        =>$sheet->getCell( 'L' . $row )->getValue(),
                        'fund_ip_act'                   =>$sheet->getCell( 'M' . $row )->getValue(),
                        'fund_ip_adjrw'                 =>$sheet->getCell( 'N' . $row )->getValue(),
                        'fund_ip_ps'                    =>$sheet->getCell( 'O' . $row )->getValue(),
                        'fund_ip_ps2'                   =>$sheet->getCell( 'P' . $row )->getValue(),
                        'fund_ip_ccuf'                  =>$sheet->getCell( 'Q' . $row )->getValue(),
                        'fund_ip_adjrw2'                =>$sheet->getCell( 'R' . $row )->getValue(),
                        'fund_ip_payrate'               =>$del_s,
                        'fund_ip_salary'                =>$del_t,
                        'fund_compensate_salary'        =>$del_u,
                        'receive_op'                    =>$del_v,
                        'receive_ip_compensate_cal'     =>$del_w,
                        'receive_ip_compensate_pay'     =>$del_x,
                        'receive_hc_hc'                 =>$del_y,
                        'receive_hc_drug'               =>$del_z,
                        'receive_ae_ae'                 =>$del_aa,
                        'receive_ae_drug'               =>$del_ab,
                        'receive_inst'                  =>$del_ac,
                        'receive_dmis_compensate_cal'   =>$del_ad,
                        'receive_dmis_compensate_pay'   =>$del_ae,
                        'receive_dmis_drug'             =>$del_af,
                        'receive_palliative'            =>$del_ag,
                        'receive_dmishd'                =>$del_ah,
                        'receive_pp'                    =>$del_ai,
                        'receive_fs'                    =>$del_aj,
                        'receive_opbkk'                 =>$del_ak,
                        'receive_total'                 =>$del_al, 
                        'va'                            =>$sheet->getCell( 'AM' . $row )->getValue(), 
                        'covid'                         =>$sheet->getCell( 'AN' . $row )->getValue(), 
                        'resources'                     =>$sheet->getCell( 'AO' . $row )->getValue(), 
                        'stm_filename'                  =>$file_name,
                    ]; 
                $startcount++;            
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $key => $data_) {
                Stm_ucsexcel::insert($data_);                 
            }
        }    
        catch (Exception $e) {
            $error_code = $e->errorInfo[1];
            return back()->withErrors('There was a problem uploading the data!');
        }
    // ***************************************************************************************************************************** 
            $stm_ucsexcel=Stm_ucsexcel::whereNotNull('charge')->get();
                
            foreach ($stm_ucsexcel as $key => $value) {
                $check = Stm_ucs::where('repno','=',$value->repno)->where('no','=',$value->no)->count();
                if ($check > 0) {
                    Stm_ucs::where('repno','=',$value->repno)->where('no','=',$value->no)->update([
                            'datetimeadm'                   => $value->datetimeadm,
                            'datetimedch'                   => $value->datetimedch,
                            'charge'                        => $value->charge,
                            'receive_op'                    => $value->receive_op,
                            'receive_ip_compensate_pay'     => $value->receive_ip_compensate_pay,
                            'receive_hc_hc'                 => $value->receive_hc_hc,
                            'receive_hc_drug'               => $value->receive_hc_drug,
                            'receive_ae_ae'                 => $value->receive_ae_ae,
                            'receive_ae_drug'               => $value->receive_ae_drug,
                            'receive_inst'                  => $value->receive_inst,
                            'receive_dmis_compensate_pay'   => $value->receive_dmis_compensate_pay,
                            'receive_dmis_drug'             => $value->receive_dmis_drug,
                            'receive_palliative'            => $value->receive_palliative,
                            'receive_pp'                    => $value->receive_pp,
                            'receive_fs'                    => $value->receive_fs,                     
                            'receive_total'                 => $value->receive_total,
                            'stm_filename'                  => $value->stm_filename
                            ]); 
                } else {
                        $add = new Stm_ucs();
                        $add->repno                         = $value->repno;
                        $add->no                            = $value->no;
                        $add->tran_id                       = $value->tran_id;
                        $add->hn                            = $value->hn;
                        $add->an                            = $value->an;
                        $add->cid                           = $value->cid;
                        $add->pt_name                       = $value->pt_name;                   
                        $add->datetimeadm                   = $value->datetimeadm;
                        $add->vstdate                       = $value->vstdate;
                        $add->vsttime                       = $value->vsttime;
                        $add->datetimedch                   = $value->datetimedch;
                        $add->dchdate                       = $value->dchdate;
                        $add->dchtime                       = $value->dchtime;
                        $add->maininscl                     = $value->maininscl;
                        $add->projcode                      = $value->projcode;
                        $add->charge                        = $value->charge;
                        $add->fund_ip_act                   = $value->fund_ip_act;
                        $add->fund_ip_adjrw                 = $value->fund_ip_adjrw;
                        $add->fund_ip_ps                    = $value->fund_ip_ps;
                        $add->fund_ip_ps2                   = $value->fund_ip_ps2;
                        $add->fund_ip_ccuf                  = $value->fund_ip_ccuf;
                        $add->fund_ip_adjrw2                = $value->fund_ip_adjrw2;
                        $add->fund_ip_payrate               = $value->fund_ip_payrate;
                        $add->fund_ip_salary                = $value->fund_ip_salary;
                        $add->fund_compensate_salary        = $value->fund_compensate_salary;
                        $add->receive_op                    = $value->receive_op;
                        $add->receive_ip_compensate_cal     = $value->receive_ip_compensate_cal;
                        $add->receive_ip_compensate_pay     = $value->receive_ip_compensate_pay;
                        $add->receive_hc_hc                 = $value->receive_hc_hc;
                        $add->receive_hc_drug               = $value->receive_hc_drug;
                        $add->receive_ae_ae                 = $value->receive_ae_ae;
                        $add->receive_ae_drug               = $value->receive_ae_drug;
                        $add->receive_inst                  = $value->receive_inst;
                        $add->receive_dmis_compensate_cal   = $value->receive_dmis_compensate_cal;
                        $add->receive_dmis_compensate_pay   = $value->receive_dmis_compensate_pay;
                        $add->receive_dmis_drug             = $value->receive_dmis_drug;
                        $add->receive_palliative            = $value->receive_palliative;
                        $add->receive_dmishd                = $value->receive_dmishd;
                        $add->receive_pp                    = $value->receive_pp;
                        $add->receive_fs                    = $value->receive_fs;
                        $add->receive_opbkk                 = $value->receive_opbkk;
                        $add->receive_total                 = $value->receive_total;
                        $add->va                            = $value->va;
                        $add->covid                         = $value->covid;
                        $add->resources                     = $value->resources;
                        $add->stm_filename                  = $value->stm_filename;
                        $add->save(); 
                } 
            }                
                Stm_ucsexcel::truncate(); 
            
        return redirect()->route('stm_ucs')->with('success',$file_name); 
        
    }

}
