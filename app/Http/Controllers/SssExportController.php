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
    private function generate_ssop_raw_data($vns, $sess_no, $station_id, $tflag = 'A')
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
                   osb.invno AS sss_invno, osb.billno AS sss_billno,
                   pu.pttype_upp_type_code AS payplan
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN pttype p ON p.pttype = o.pttype
            LEFT JOIN pttype_upp_type pu ON pu.pttype_upp_type_id = p.pttype_upp_type_id
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            WHERE o.vn IN ($visits_placeholders)
        ", $vns);
        $visits = collect($visits); // Convert to Collection to preserve helper methods

        $visits_map = $visits->keyBy('vn');

        // Query REP invoices to match against multiple HOSxP invoices
        $vns_list = $visits->pluck('vn')->toArray();
        $rep_invs_by_vn = [];
        if (!empty($vns_list)) {
            $rep_records = DB::table('sss_ssop_rep')
                ->whereIn('vn', $vns_list)
                ->select('vn', 'invno')
                ->get();
            foreach ($rep_records as $r) {
                $rep_invs_by_vn[$r->vn][] = trim($r->invno);
            }
        }

        // 1. Generate BILLTRAN & BillItems content
        $billtran_rows = [];
        foreach ($visits as $row) {
            $raw_invo = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($row->vn, $raw_invo, $rep_invs_by_vn);
            $sub_id = !empty($row->sss_billno) ? $row->sss_billno : '';
            $ptname = trim($row->pname . $row->fname . ' ' . $row->lname);
            $payplan = !empty($row->payplan) ? trim($row->payplan) : '80';
            $paid = number_format($row->paid_money, 2, '.', '');
            $income = number_format($row->income, 2, '.', '');
            $claim = number_format($row->uc_money, 2, '.', '');
            
            $billtran_rows[] = "01||{$row->vstdate} {$row->vsttime}|{$hcode}|{$invoice_no}|{$sub_id}|{$row->hn}||{$income}|{$paid}||{$tflag}|{$row->cid}|{$ptname}|{$row->hospmain}|{$payplan}|{$claim}||0.00";
        }

        // Fetch BillItems (Raw SQL for all items/charges prescribed in these visits)
        $billitems_rows = [];
        $billitems_raw = DB::connection('hosxp')->select("
            SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price, op.income, op.hos_guid,
                   sd.name AS drug_name, n.name AS nondrug_name,
                   COALESCE(nd.tmtid, di.sks_drug_code) AS tmtid
            FROM opitemrece op
            LEFT JOIN s_drugitems sd ON sd.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems di ON di.icode = op.icode
            LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                AND nd.date_approved = (
                    SELECT MAX(nd1.date_approved) 
                    FROM hrims.drugcat_chi nd1 
                    WHERE nd.hospdrugcode = nd1.hospdrugcode 
                    AND nd1.updateflag IN ('A','U','E')
                )
            WHERE op.vn IN ($visits_placeholders)
        ", $vns);

        $map_income_to_ssop_group = function($inc) {
            $inc = str_pad($inc, 2, '0', STR_PAD_LEFT);
            switch ($inc) {
                case '01': return '1';
                case '02': return '2';
                case '03':
                case '04':
                case '17': return '3'; // Drugs
                case '05': return '5'; // Supplies
                case '06': return '6'; // Blood
                case '07': return '7'; // Lab
                case '08': return '8'; // X-ray
                case '09': return '9'; // Special diagnostics
                case '10': return 'A'; // Equipment
                case '11': return 'B'; // Anesthesia/Procedures
                case '12':
                case '18': return 'C'; // Nursing & service fees
                case '13': return 'D'; // Dental
                case '14': return 'E'; // Physical therapy
                case '15': return 'F'; // Alternative medicine
                default: return 'G';   // Other
            }
        };

        foreach ($billitems_raw as $item) {
            $v = $visits_map->get($item->vn);
            if (!$v) continue;
            $raw_invo = !empty($v->sss_invno) ? $v->sss_invno : (!empty($v->debt_id_list) ? $v->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($v->vn, $raw_invo, $rep_invs_by_vn);
            $billgr = $map_income_to_ssop_group($item->income);
            $name = trim($item->drug_name ?: $item->nondrug_name ?: '');
            $qty = number_format($item->qty, 0, '.', '');
            $unitprice = number_format($item->unitprice, 2, '.', '');
            $sum_price = number_format($item->sum_price, 2, '.', '');
            
            // RefID/DispID: If in s_drugitems, use rx_no_invoice_no, else use vn
            if (!empty($item->drug_name)) {
                $rx_no = !empty($item->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $item->hos_guid), 0, 9) : $item->vn;
                if (empty($rx_no)) $rx_no = $item->vn;
                $disp_id = "{$rx_no}_{$invoice_no}";
            } else {
                $disp_id = $item->vn;
            }

            $billitems_rows[] = "{$invoice_no}|{$v->vstdate}|{$billgr}|{$item->icode}|{$item->tmtid}|{$name}|{$qty}|{$unitprice}|{$sum_price}|{$unitprice}|{$sum_price}|{$disp_id}|OP1";
        }

        $billtran_count = count($billtran_rows);
        $billtran_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\r\n" .
            '<ClaimRec System="OP" PayPlan="SS" Version="0.93" Prgs="HX">' . "\r\n" .
            '<Header>' . "\r\n" .
            "<HCODE>{$hcode}</HCODE>\r\n" .
            "<HNAME>{$hname}</HNAME>\r\n" .
            "<DATETIME>{$datetime}</DATETIME>\r\n" .
            "<SESSNO>{$sess_no}</SESSNO>\r\n" .
            "<RECCOUNT>{$billtran_count}</RECCOUNT>\r\n" .
            '</Header>' . "\r\n" .
            '<BILLTRAN>' . "\r\n" .
            implode("\r\n", $billtran_rows) . "\r\n" .
            '</BILLTRAN>' . "\r\n" .
            '<BillItems>' . "\r\n" .
            implode("\r\n", $billitems_rows) . "\r\n" .
            '</BillItems>' . "\r\n" .
            '</ClaimRec>' . "\r\n";

        // Convert to TIS-620 and compute Checksum MD5
        $billtran_tis = iconv('UTF-8', 'TIS-620//IGNORE', $billtran_xml);
        $billtran_md5 = strtoupper(md5($billtran_tis));
        $billtran_xml .= '<?EndNote Checksum="' . $billtran_md5 . '"?>' . "\r\n";

        // 2. Generate BILLDISP & DispensedItems content
        $billdisp_rows = [];
        $dispensed_rows = [];
        $disp_sessions = [];

        // Fetch drug items (excluding icode LIKE '1%' to warn about bad registration items in s_drugitems)
        $disp_items = DB::connection('hosxp')->select("
            SELECT op.vn, op.icode, op.qty, op.sum_price, op.unitprice, op.hos_guid, op.rxtime,
                   COALESCE(nd.tmtid, di.sks_drug_code) AS tmtid,
                   sd.name, sd.sks_product_category_id,
                   di.capacity_name, di.capacity_qty,
                   op.drugusage, du.opi_usage_code, du.opi_unit_name,
                   CONCAT(IFNULL(du.name1,''), ' ', IFNULL(du.name2,''), ' ', IFNULL(du.name3,'')) AS drugusage_text
            FROM opitemrece op
            INNER JOIN s_drugitems sd ON sd.icode = op.icode
            LEFT JOIN drugitems di ON di.icode = op.icode
            LEFT JOIN drugusage du ON du.drugusage = op.drugusage
            LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                AND nd.date_approved = (
                    SELECT MAX(nd1.date_approved) 
                    FROM hrims.drugcat_chi nd1 
                    WHERE nd.hospdrugcode = nd1.hospdrugcode 
                    AND nd1.updateflag IN ('A','U','E')
                )
            WHERE op.vn IN ($visits_placeholders)
            AND op.icode LIKE '1%'
        ", $vns);

        foreach ($disp_items as $item) {
            $v = $visits_map->get($item->vn);
            if (!$v) continue;

            $raw_invo = !empty($v->sss_invno) ? $v->sss_invno : (!empty($v->debt_id_list) ? $v->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($v->vn, $raw_invo, $rep_invs_by_vn);
            $rx_no = !empty($item->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $item->hos_guid), 0, 9) : $v->vn;
            if (empty($rx_no)) $rx_no = $v->vn;
            $disp_id = "{$rx_no}_{$invoice_no}";

            // Group Dispensing rows by unique disp_id
            if (!isset($disp_sessions[$disp_id])) {
                $disp_date = "{$v->vstdate}T{$v->vsttime}";
                $end_date = "{$v->vstdate}T" . (!empty($item->rxtime) ? $item->rxtime : date('H:i:s', strtotime($v->vsttime . ' + 30 minutes')));
                $license = !empty($v->rx_license_no) ? $v->rx_license_no : '-';
                
                // Count items in this session
                $session_items = array_filter($disp_items, function($x) use ($item, $rx_no) {
                    $x_rx_no = !empty($x->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $x->hos_guid), 0, 9) : $x->vn;
                    return $x->vn === $item->vn && $x_rx_no === $rx_no;
                });
                $session_count = count($session_items);
                $session_sum = array_sum(array_column($session_items, 'sum_price'));
                $total_amt = number_format($session_sum, 2, '.', '');
                
                $billdisp_rows[] = "{$hcode}|{$disp_id}|{$rx_no}|{$v->hn}|{$v->cid}|{$disp_date}|{$end_date}|{$license}|2|{$total_amt}|{$total_amt}|0.00|0.00|HP|SS|{$session_count}|{$v->vn}|";
                $disp_sessions[$disp_id] = true;
            }

            $prdcat = !empty($item->sks_product_category_id) ? $item->sks_product_category_id : '';
            $tmtid = !empty($item->tmtid) ? $item->tmtid : '';
            $sigcode = !empty($item->opi_usage_code) ? str_pad($item->opi_usage_code, 7, '0', STR_PAD_LEFT) . ':0000000' : '0000000:0000000';
            $sigtext = !empty($item->drugusage_text) ? trim($item->drugusage_text) : '';
            
            // Fallback to 'ตามแพทย์สั่ง' for PrdCat 1-5 (drugs) if sigtext is empty
            $prdcat_int = intval($prdcat);
            if ($prdcat_int >= 1 && $prdcat_int <= 5) {
                if (empty($sigtext)) {
                    $sigtext = 'ตามแพทย์สั่ง';
                }
            }
            $qty = number_format($item->qty, 0, '.', '');
            $unit_price = number_format($item->unitprice, 2, '.', '');
            $total_amt = number_format($item->sum_price, 2, '.', '');

            $dispensed_rows[] = "{$disp_id}|{$prdcat}|{$item->icode}|{$tmtid}|{$item->capacity_name}|{$item->name}|{$item->opi_unit_name}|{$sigcode}|{$sigtext}|{$qty}|{$unit_price}|{$total_amt}|{$unit_price}|{$total_amt}||OD|||";
        }

        $billdisp_count = count($billdisp_rows);
        $billdisp_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\r\n" .
            '<ClaimRec System="OP" PayPlan="SS" Version="0.93" Prgs="HX">' . "\r\n" .
            '<Header>' . "\r\n" .
            "<HCODE>{$hcode}</HCODE>\r\n" .
            "<HNAME>{$hname}</HNAME>\r\n" .
            "<DATETIME>{$datetime_iso}</DATETIME>\r\n" .
            "<SESSNO>{$sess_no}</SESSNO>\r\n" .
            "<RECCOUNT>{$billdisp_count}</RECCOUNT>\r\n" .
            '</Header>' . "\r\n" .
            '<Dispensing>' . "\r\n" .
            implode("\r\n", $billdisp_rows) . "\r\n" .
            '</Dispensing>' . "\r\n" .
            '<DispensedItems>' . "\r\n" .
            implode("\r\n", $dispensed_rows) . "\r\n" .
            '</DispensedItems>' . "\r\n" .
            '</ClaimRec>' . "\r\n";

        // Convert to TIS-620 and compute Checksum MD5
        $billdisp_tis = iconv('UTF-8', 'TIS-620//IGNORE', $billdisp_xml);
        $billdisp_md5 = strtoupper(md5($billdisp_tis));
        $billdisp_xml .= '<?EndNote Checksum="' . $billdisp_md5 . '"?>' . "\r\n";

        // 3. Generate OPServices & OPDx content
        $opservices_rows = [];
        $opdx_rows = [];

        foreach ($visits as $row) {
            $raw_invo = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($row->vn, $raw_invo, $rep_invs_by_vn);
            $start_dt = "{$row->vstdate}T{$row->vsttime}";
            $end_dt = "{$row->vstdate}T" . date('H:i:s', strtotime($row->vsttime . ' + 2 hours'));
            
            // Fetch diagnosis
            $diags = DB::connection('hosxp')->select("
                SELECT icd10, diagtype 
                FROM ovstdiag 
                WHERE vn = ?
            ", [$row->vn]);
                
            foreach ($diags as $d) {
                $icd_type = (str_starts_with(strtoupper($d->icd10), 'K') || preg_match('/^U[567]/i', $d->icd10)) ? 'TT' : 'IT';
                $clean_diag = str_replace('.', '', trim($d->icd10));
                $opdx_rows[] = "EC|{$row->vn}|{$d->diagtype}|{$icd_type}|{$clean_diag}|";
            }
            
            $opservices_rows[] = "{$invoice_no}|{$row->vn}|EC|{$hcode}|{$row->hn}|{$row->cid}|1|01|9|9||-|99|{$start_dt}|{$end_dt}||||0.00|Y||OP1";
        }

        $opservices_count = count($opservices_rows);
        $opservices_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\r\n" .
            '<ClaimRec System="OP" PayPlan="SS" Version="0.93" Prgs="HX">' . "\r\n" .
            '<Header>' . "\r\n" .
            "<HCODE>{$hcode}</HCODE>\r\n" .
            "<HNAME>{$hname}</HNAME>\r\n" .
            "<DATETIME>{$datetime_iso}</DATETIME>\r\n" .
            "<SESSNO>{$sess_no}</SESSNO>\r\n" .
            "<RECCOUNT>{$opservices_count}</RECCOUNT>\r\n" .
            '</Header>' . "\r\n" .
            '<OPServices>' . "\r\n" .
            implode("\r\n", $opservices_rows) . "\r\n" .
            '</OPServices>' . "\r\n" .
            '<OPDx>' . "\r\n" .
            implode("\r\n", $opdx_rows) . "\r\n" .
            '</OPDx>' . "\r\n" .
            '</ClaimRec>' . "\r\n";

        // Convert to TIS-620 and compute Checksum MD5
        $opservices_tis = iconv('UTF-8', 'TIS-620//IGNORE', $opservices_xml);
        $opservices_md5 = strtoupper(md5($opservices_tis));
        $opservices_xml .= '<?EndNote Checksum="' . $opservices_md5 . '"?>' . "\r\n";

        return [
            'hcode' => $hcode,
            'sess_no' => $sess_no,
            'station_id' => $station_id,
            'date_suffix' => $date_suffix,
            'billtran_xml' => $billtran_xml,
            'billdisp_xml' => $billdisp_xml,
            'opservices_xml' => $opservices_xml,
            'billtran_rows' => $billtran_rows,
            'billitems_rows' => $billitems_rows,
            'billdisp_rows' => $billdisp_rows,
            'dispensed_rows' => $dispensed_rows,
            'opservices_rows' => $opservices_rows,
            'opdx_rows' => $opdx_rows,
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

        $tflag = $request->input('tflag') ?: 'A';
        $data = $this->generate_ssop_raw_data($vns, $sess_no, $station_id, $tflag);

        $billtran_table = [];
        foreach ($data['billtran_rows'] as $idx => $row) {
            $fields = explode('|', $row);
            $fields[] = $data['visits_list'][$idx]->vn ?? '';
            $billtran_table[] = $fields;
        }

        $billitems_table = [];
        foreach ($data['billitems_rows'] as $row) {
            $fields = explode('|', $row);
            $billitems_table[] = $fields;
        }

        $billdisp_table = [];
        foreach ($data['billdisp_rows'] as $row) {
            $fields = explode('|', $row);
            $billdisp_table[] = $fields;
        }

        $dispenseditems_table = [];
        foreach ($data['dispensed_rows'] as $row) {
            $fields = explode('|', $row);
            $dispenseditems_table[] = $fields;
        }

        $opservices_table = [];
        foreach ($data['opservices_rows'] as $row) {
            $fields = explode('|', $row);
            $opservices_table[] = $fields;
        }

        $opdx_table = [];
        foreach ($data['opdx_rows'] as $row) {
            $fields = explode('|', $row);
            $opdx_table[] = $fields;
        }

        // Query REP invoices for validation matching
        $rep_invs_by_vn = [];
        if (!empty($vns)) {
            $rep_records = DB::table('sss_ssop_rep')
                ->whereIn('vn', $vns)
                ->select('vn', 'invno')
                ->get();
            foreach ($rep_records as $r) {
                $rep_invs_by_vn[$r->vn][] = trim($r->invno);
            }
        }

        // Perform backend validation to detect missing required fields
        $validation = [];
        foreach ($data['visits_list'] as $row) {
            $vn = $row->vn;
            $errors = [];

            $raw_invo = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($vn, $raw_invo, $rep_invs_by_vn);
            
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
                'hn' => $row->hn,
                'name' => trim($row->pname . $row->fname . ' ' . $row->lname),
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
            'billitems_table' => $billitems_table,
            'billdisp_table' => $billdisp_table,
            'dispenseditems_table' => $dispenseditems_table,
            'opservices_table' => $opservices_table,
            'opdx_table' => $opdx_table,
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

        $tflag = $request->input('tflag') ?: 'A';
        $data = $this->generate_ssop_raw_data($vns, $sess_no, $station_id, $tflag);

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

    /**
     * Resolve single invoice number from HOSxP and REP records
     */
    private function resolve_invoice_no($vn, $raw_invo, $rep_invs_by_vn = [])
    {
        if (empty($raw_invo)) {
            return '';
        }
        
        $h_invoices = [];
        foreach (explode(',', $raw_invo) as $part) {
            foreach (explode('.', $part) as $subpart) {
                $trimmed = trim($subpart);
                if ($trimmed !== '') {
                    $h_invoices[] = $trimmed;
                }
            }
        }
        
        if (count($h_invoices) <= 1) {
            return !empty($h_invoices) ? $h_invoices[0] : '';
        }
        
        if (isset($rep_invs_by_vn[$vn])) {
            foreach ($h_invoices as $h_inv) {
                if (in_array($h_inv, $rep_invs_by_vn[$vn])) {
                    return $h_inv;
                }
            }
        }
        
        return $h_invoices[0];
    }

}