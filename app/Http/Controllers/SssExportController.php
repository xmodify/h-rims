<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\LicenseService;
use ZipArchive;

class SssExportController extends Controller
{
    /**
     * Helper to generate raw SSOP data (raw XML strings and rows)
     */
    private function generate_ssop_raw_data($vns, $sess_no, $station_id)
    {
        $hcode = LicenseService::getCurrentHospcode() ?: '10989';
        
        $hname = Cache::remember('hospitalname_licensed', 86400, function() {
            try {
                return DB::connection('hosxp')->table('opdconfig')->value('hospitalname');
            } catch (\Throwable $e) {
                return 'รพ. ';
            }
        });

        // Current timestamp formatted for SSOP
        $datetime = date('Y-m-d H:i:ss');
        $datetime_iso = date('Y-m-d\TH:i:ss');
        $date_suffix = date('Ymd');

        // Fetch visits (Raw SQL with LEFT JOIN ovst_sss_billtran to pull actual HOSxP invoice numbers)
        $visits_placeholders = implode(',', array_fill(0, count($vns), '?'));
        $visits = DB::connection('hosxp')->select("
            SELECT o.vn, o.vstdate, o.vsttime, o.hn, pt.pname, pt.fname, pt.lname, pt.cid, 
                   v.income, v.paid_money, v.remain_money, v.uc_money, v.spclty, v.hospmain, v.debt_id_list, v.rx_license_no,
                   osb.invno AS sss_invno, osb.billno AS sss_billno
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            WHERE o.vn IN ($visits_placeholders)
        ", $vns);
        $visits = collect($visits); // Convert to Collection to preserve helper methods

        // 1. Generate BILLTRAN content
        $billtran_rows = [];
        foreach ($visits as $row) {
            $invoice_no = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $sub_id = !empty($row->sss_billno) ? $row->sss_billno : '';
            $ptname = trim($row->pname . $row->fname . ' ' . $row->lname);
            $spclty = !empty($row->spclty) ? str_pad($row->spclty, 2, '0', STR_PAD_LEFT) : '01';
            if ($spclty === '01') {
                $spclty = '80';
            }
            $paid = number_format($row->paid_money, 2, '.', '');
            $income = number_format($row->income, 2, '.', '');
            $claim = number_format($row->uc_money, 2, '.', '');
            
            $billtran_rows[] = "01||{$row->vstdate} {$row->vsttime}|{$hcode}|{$invoice_no}|{$sub_id}|{$row->hn}||{$income}|{$paid}||A|{$row->cid}|{$ptname}|{$row->hospmain}|{$spclty}|{$claim}||0.00";
        }
        $billtran_count = count($billtran_rows);
        $billtran_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\n" .
            '<ClaimRec System="OP" PayPlan="SS" Version="0.93" Prgs="HX">' . "\n" .
            '<Header>' . "\n" .
            "<HCODE>{$hcode}</HCODE>\n" .
            "<HNAME>{$hname}</HNAME>\n" .
            "<DATETIME>{$datetime}</DATETIME>\n" .
            "<SESSNO>{$sess_no}</SESSNO>\n" .
            "<RECCOUNT>{$billtran_count}</RECCOUNT>\n" .
            '</Header>' . "\n" .
            '<BillTran>' . "\n" .
            implode("\n", $billtran_rows) . "\n" .
            '</BillTran>' . "\n" .
            '</ClaimRec>';

        // 2. Generate BILLDISP content
        $billdisp_rows = [];
        // Fetch drug items (Raw SQL for easy debugging/copy-pasting in Navicat)
        $disp_placeholders = implode(',', array_fill(0, count($vns), '?'));
        $disp_items = DB::connection('hosxp')->select("
            SELECT op.vn, op.icode, op.qty, op.sum_price, op.unitprice, op.hos_guid, op.rxtime,
                   COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid
            FROM opitemrece op
            INNER JOIN s_drugitems sd ON sd.icode = op.icode
            LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                AND nd.date_approved = (
                    SELECT MAX(nd1.date_approved) 
                    FROM hrims.drugcat_chi nd1 
                    WHERE nd.hospdrugcode = nd1.hospdrugcode 
                    AND nd1.updateflag IN ('A','U','E')
                )
            WHERE op.vn IN ($disp_placeholders)
            AND op.icode LIKE '1%'
        ", $vns);

        $visits_map = $visits->keyBy('vn');

        foreach ($disp_items as $item) {
            $v = $visits_map->get($item->vn);
            if (!$v) continue;

            $invoice_no = !empty($v->sss_invno) ? $v->sss_invno : (!empty($v->debt_id_list) ? $v->debt_id_list : '');
            $rx_no = !empty($item->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $item->hos_guid), 0, 9) : $item->vn;
            if (empty($rx_no)) {
                $rx_no = $item->vn;
            }
            $disp_id = "{$rx_no}_{$invoice_no}";
            
            $disp_date = "{$v->vstdate}T{$v->vsttime}";
            $end_date = "{$v->vstdate}T" . (!empty($item->rxtime) ? $item->rxtime : date('H:i:s', strtotime($v->vsttime . ' + 30 minutes')));
            
            $license = !empty($v->rx_license_no) ? $v->rx_license_no : '-';
            $qty = number_format($item->qty, 0, '.', '');
            $unit_price = number_format($item->unitprice, 2, '.', '');
            $total_amt = number_format($item->sum_price, 2, '.', '');
            
            $billdisp_rows[] = "{$hcode}|{$disp_id}|{$rx_no}|{$v->hn}|{$v->cid}|{$disp_date}|{$end_date}|{$license}|2|{$unit_price}|{$total_amt}|0.00|0.00|HP|SS|{$qty}|{$v->vn}|";
        }
        $billdisp_count = count($billdisp_rows);
        $billdisp_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\n" .
            '<ClaimRec System="OP" PayPlan="SS" Version="0.93" Prgs="HX">' . "\n" .
            '<Header>' . "\n" .
            "<HCODE>{$hcode}</HCODE>\n" .
            "<HNAME>{$hname}</HNAME>\n" .
            "<DATETIME>{$datetime_iso}</DATETIME>\n" .
            "<SESSNO>{$sess_no}</SESSNO>\n" .
            "<RECCOUNT>{$billdisp_count}</RECCOUNT>\n" .
            '</Header>' . "\n" .
            '<Dispensing>' . "\n" .
            implode("\n", $billdisp_rows) . "\n" .
            '</Dispensing>' . "\n" .
            '</ClaimRec>';

        // 3. Generate OPServices content
        $opservices_rows = [];
        foreach ($visits as $row) {
            $invoice_no = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $start_dt = "{$row->vstdate}T{$row->vsttime}";
            $end_dt = "{$row->vstdate}T" . date('H:i:s', strtotime($row->vsttime . ' + 2 hours'));
            
            // Fetch diagnosis (Raw SQL for easy debugging/copy-pasting in Navicat)
            $diags = DB::connection('hosxp')->select("
                SELECT icd10, diagtype 
                FROM ovstdiag 
                WHERE vn = ?
            ", [$row->vn]);
                
            $pdx = '';
            $sdx_list = [];
            foreach ($diags as $d) {
                if ($d->diagtype == '1') {
                    $pdx = $d->icd10;
                } else {
                    $sdx_list[] = $d->icd10;
                }
            }
            
            $opservices_rows[] = "{$invoice_no}|{$row->vn}|EC|{$hcode}|{$row->hn}|{$row->cid}|1|01|9|9||-|99|{$start_dt}|{$end_dt}||||0.00|Y||OP1";
        }
        $opservices_count = count($opservices_rows);
        $opservices_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\n" .
            '<ClaimRec System="OP" PayPlan="SS" Version="0.93" Prgs="HX">' . "\n" .
            '<Header>' . "\n" .
            "<HCODE>{$hcode}</HCODE>\n" .
            "<HNAME>{$hname}</HNAME>\n" .
            "<DATETIME>{$datetime_iso}</DATETIME>\n" .
            "<SESSNO>{$sess_no}</SESSNO>\n" .
            "<RECCOUNT>{$opservices_count}</RECCOUNT>
" .
            '</Header>' . "\n" .
            '<OPServices>' . "\n" .
            implode("\n", $opservices_rows) . "\n" .
            '</OPServices>' . "\n" .
            '</ClaimRec>';

        return [
            'hcode' => $hcode,
            'sess_no' => $sess_no,
            'station_id' => $station_id,
            'date_suffix' => $date_suffix,
            'billtran_xml' => $billtran_xml,
            'billdisp_xml' => $billdisp_xml,
            'opservices_xml' => $opservices_xml,
            'billtran_rows' => $billtran_rows,
            'billdisp_rows' => $billdisp_rows,
            'opservices_rows' => $opservices_rows,
            'visits_list' => $visits->toArray(),
            'disp_items' => $disp_items
        ];
    }

    /**
     * Preview export data inside UI
     */
    public function sss_export_preview(Request $request)
    {
        $vns = $request->input('vns', []);
        if (empty($vns)) {
            return response()->json(['error' => 'กรุณาเลือกรายการที่ต้องการส่งออก'], 400);
        }
        $sess_no = $request->input('session_id') ?: rand(1000, 9999);
        $station_id = $request->input('station_id') ?: '01';

        $data = $this->generate_ssop_raw_data($vns, $sess_no, $station_id);

        // Convert pipe-delimited rows to array of fields for UI preview
        // Also append VN at the end of each row for mapping
        $billtran_table = [];
        foreach ($data['billtran_rows'] as $idx => $row) {
            $fields = explode('|', $row);
            $fields[] = $data['visits_list'][$idx]->vn ?? '';
            $billtran_table[] = $fields;
        }

        $billdisp_table = [];
        foreach ($data['billdisp_rows'] as $row) {
            $fields = explode('|', $row);
            $billdisp_table[] = $fields;
        }

        $opservices_table = [];
        foreach ($data['opservices_rows'] as $row) {
            $fields = explode('|', $row);
            $opservices_table[] = $fields;
        }

        // Perform backend validation to detect missing required fields and output to frontend
        $validation = [];
        foreach ($data['visits_list'] as $row) {
            $vn = $row->vn;
            $errors = [];

            $invoice_no = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            
            // 1. BILLTRAN checks
            if (empty($invoice_no)) {
                $errors['billtran'][] = "ไม่พบเลขใบแจ้งหนี้ (InvNo)";
            } elseif ($invoice_no === $vn) {
                $errors['billtran'][] = "เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้)";
            }
            if (empty($row->cid) || strlen($row->cid) !== 13) {
                $errors['billtran'][] = "เลขบัตรประชาชน (CID) ว่างหรือความยาวไม่ครบ 13 หลัก";
            }
            if (empty($row->hn)) {
                $errors['billtran'][] = "ไม่พบ HN";
            }
            if (empty($row->uc_money) || $row->uc_money <= 0) {
                $errors['billtran'][] = "ยอดเงินเรียกเก็บ (ClaimAmt) ต้องมากกว่า 0";
            }

            // 2. BILLDISP checks for this VN
            if (empty($invoice_no)) {
                $errors['billdisp'][] = "ไม่พบเลขใบแจ้งหนี้ (InvNo)";
            } elseif ($invoice_no === $vn) {
                $errors['billdisp'][] = "เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้)";
            }
            $vn_disp_items = array_filter($data['disp_items'], function($item) use ($vn) {
                return $item->vn === $vn;
            });
            foreach ($vn_disp_items as $item) {
                if (empty($item->tmtid)) {
                    $errors['billdisp'][] = "ยา icode {$item->icode} ไม่มีรหัสมาตรฐาน TMT";
                }
            }

            // 3. OPServices checks
            if (empty($invoice_no)) {
                $errors['opservices'][] = "ไม่พบเลขใบแจ้งหนี้ (InvoiceNo)";
            } elseif ($invoice_no === $vn) {
                $errors['opservices'][] = "เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้)";
            }
            $diags = DB::connection('hosxp')->select("
                SELECT icd10, diagtype 
                FROM ovstdiag 
                WHERE vn = ?
            ", [$vn]);
            $has_pdx = false;
            foreach ($diags as $d) {
                if ($d->diagtype == '1') {
                    $has_pdx = true;
                    break;
                }
            }
            if (!$has_pdx) {
                $errors['opservices'][] = "ไม่พบรหัสวินิจฉัยโรคหลัก (PDX)";
            }

            $validation[$vn] = [
                'billtran_ok' => empty($errors['billtran']),
                'billtran_err' => implode(', ', $errors['billtran'] ?? []),
                'billdisp_ok' => empty($errors['billdisp']),
                'billdisp_err' => implode(', ', $errors['billdisp'] ?? []),
                'opservices_ok' => empty($errors['opservices']),
                'opservices_err' => implode(', ', $errors['opservices'] ?? []),
            ];
        }

        return response()->json([
            'success' => true,
            'billtran_raw' => $data['billtran_xml'],
            'billdisp_raw' => $data['billdisp_xml'],
            'opservices_raw' => $data['opservices_xml'],
            'billtran_table' => $billtran_table,
            'billdisp_table' => $billdisp_table,
            'opservices_table' => $opservices_table,
            'validation' => $validation
        ]);
    }

    /**
     * Download the pre-built ZIP file
     */
    public function sss_export_ssop(Request $request)
    {
        $vns = $request->input('vns', []);
        if (empty($vns)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่ต้องการส่งออก');
        }

        $sess_no = $request->input('session_id') ?: rand(1000, 9999);
        $station_id = $request->input('station_id') ?: '01';

        $data = $this->generate_ssop_raw_data($vns, $sess_no, $station_id);

        // Encode all to TIS-620 using iconv
        $billtran_encoded = iconv('UTF-8', 'TIS-620//IGNORE', $data['billtran_xml']);
        $billdisp_encoded = iconv('UTF-8', 'TIS-620//IGNORE', $data['billdisp_xml']);
        $opservices_encoded = iconv('UTF-8', 'TIS-620//IGNORE', $data['opservices_xml']);

        // Create Zip
        $zip_name = "{$data['hcode']}_SSOPBIL_{$sess_no}_{$station_id}_" . date('Ymd-His') . ".zip";
        $temp_dir = storage_path('app/temp_ssop');
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0777, true);
        }
        
        $zip_path = "{$temp_dir}/{$zip_name}";
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $zip->addFromString("BILLTRAN{$data['date_suffix']}.txt", $billtran_encoded);
            $zip->addFromString("BILLDISP{$data['date_suffix']}.txt", $billdisp_encoded);
            $zip->addFromString("OPServices{$data['date_suffix']}.txt", $opservices_encoded);
            $zip->close();
        } else {
            return redirect()->back()->with('error', 'ไม่สามารถสร้างไฟล์ ZIP ได้');
        }

        return response()->download($zip_path)->deleteFileAfterSend(true);
    }
}
