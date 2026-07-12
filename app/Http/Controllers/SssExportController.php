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
        $datetime = date('Y-m-d H:i:s');
        $datetime_iso = date('Y-m-d\TH:i:s');
        $date_suffix = date('Ymd');

        $pttype_sss_fund_raw = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: '';
        $pttype_sss_ae_raw = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: '';
        $exclude_pttypes = [];
        foreach (explode(',', $pttype_sss_fund_raw . ',' . $pttype_sss_ae_raw) as $p) {
            $trimmed = trim($p, " \t\n\r\0\x0B'");
            if ($trimmed !== '') {
                $exclude_pttypes[] = $trimmed;
            }
        }
        $exclude_pttypes_str = !empty($exclude_pttypes) ? "'" . implode("','", $exclude_pttypes) . "'" : "''";

        // Fetch visits (Raw SQL with LEFT JOIN visit_pttype to pull actual HOSxP main hospital codes)
        $visits_placeholders = implode(',', array_fill(0, count($vns), '?'));
        $visits = DB::connection('hosxp')->select("
            SELECT o.vn, o.vstdate, o.vsttime, o.hn, pt.pname, pt.fname, pt.lname, pt.cid, 
                   v.spclty, COALESCE(vp.hospmain, v.hospmain) AS hospmain, vp.pttype AS sss_pttype, v.debt_id_list, v.rx_license_no,
                   osb.invno AS sss_invno, osb.billno AS sss_billno,
                   pu.pttype_upp_type_code AS payplan,
                   doc.licenseno AS doctor_license,
                   o.pttype AS ovst_pttype,
                   (SELECT SUM(r.total_amount) FROM rcpt_print r LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno WHERE r.vn = o.vn AND r.pttype = vp.pttype AND a.rcpno IS NULL) AS sss_paid_amount
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            LEFT JOIN vn_stat v ON v.vn = o.vn
            LEFT JOIN visit_pttype vp ON vp.vn = o.vn AND vp.pttype = (
                SELECT vp2.pttype 
                FROM visit_pttype vp2
                LEFT JOIN pttype p2 ON p2.pttype = vp2.pttype
                WHERE vp2.vn = o.vn 
                  AND p2.hipdata_code = 'SSS'
                  AND vp2.pttype NOT IN ($exclude_pttypes_str)
                LIMIT 1
            )
            LEFT JOIN pttype p ON p.pttype = o.pttype
            LEFT JOIN pttype_upp_type pu ON pu.pttype_upp_type_id = p.pttype_upp_type_id
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
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

        // Query SSS pttypes for these VNs from visit_pttype
        $sss_pttypes_by_vn = [];
        if (!empty($vns_list)) {
            $vp_records = DB::connection('hosxp')
                ->table('visit_pttype as vp')
                ->leftJoin('pttype as p', 'p.pttype', '=', 'vp.pttype')
                ->whereIn('vp.vn', $vns_list)
                ->where('p.hipdata_code', 'SSS')
                ->select('vp.vn', 'vp.pttype')
                ->get();
            foreach ($vp_records as $r) {
                $sss_pttypes_by_vn[$r->vn] = $r->pttype;
            }
        }
        foreach ($visits as $row) {
            if (!isset($sss_pttypes_by_vn[$row->vn])) {
                if (!empty($row->ovst_pttype)) {
                    $sss_pttypes_by_vn[$row->vn] = $row->ovst_pttype;
                }
            }
        }

        // Query multiple invoices from rcpt_debt to map SSS pttype invoice using vn and SSS pttype
        $sss_debt_map = [];
        if (!empty($vns_list)) {
            $debt_records = DB::connection('hosxp')
                ->table('rcpt_debt as rd')
                ->whereIn('rd.vn', $vns_list)
                ->select('rd.vn', 'rd.debt_id', 'rd.pttype')
                ->get();
            foreach ($debt_records as $r) {
                $sss_pttype = $sss_pttypes_by_vn[$r->vn] ?? null;
                if ($sss_pttype !== null && $r->pttype === $sss_pttype) {
                    $sss_debt_map[$r->vn] = $r->debt_id;
                }
            }
        }

        // Query SSS Fund and SSS AE pttypes to exclude them from main SSS claim
        $pttype_sss_fund_raw = DB::table('main_setting')->where('name', 'pttype_sss_fund')->value('value') ?: '';
        $pttype_sss_ae_raw = DB::table('main_setting')->where('name', 'pttype_sss_ae')->value('value') ?: '';
        $exclude_pttypes = [];
        foreach (explode(',', $pttype_sss_fund_raw . ',' . $pttype_sss_ae_raw) as $p) {
            $trimmed = trim($p, " \t\n\r\0\x0B'");
            if ($trimmed !== '') {
                $exclude_pttypes[] = $trimmed;
            }
        }

        // Fetch BillItems (Raw SQL for all items/charges prescribed in these visits)
        $billitems_raw = DB::connection('hosxp')->select("
            SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price, op.income, op.hos_guid, op.pttype,
                   sd.name AS drug_name, n.name AS nondrug_name,
                   COALESCE(nd.tmtid, sd.sks_drug_code, d3.ref_code, di.sks_drug_code, n.nhso_adp_code) AS tmtid
            FROM opitemrece op
            LEFT JOIN s_drugitems sd ON sd.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems di ON di.icode = op.icode
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
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

        // 1. Generate BILLTRAN & BillItems content
        $billitems_rows = [];
        $billitems_by_vn = [];
        foreach ($billitems_raw as $item) {
            if (in_array($item->pttype, $exclude_pttypes)) {
                continue;
            }
            $sss_pttype = $sss_pttypes_by_vn[$item->vn] ?? null;
            if ($sss_pttype !== null && !empty($item->pttype) && $item->pttype !== $sss_pttype) {
                continue;
            }
            $billitems_by_vn[$item->vn][] = $item;
        }

        $item_claim_map = []; // Map to share calculated claim amounts and claim unit prices with BILLDISP
        $billtran_rows = [];
        foreach ($visits as $row) {
            if (empty($row->sss_pttype)) {
                continue;
            }
            $raw_invo = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($row->vn, $raw_invo, $rep_invs_by_vn, $sss_debt_map);
            $sub_id = !empty($row->sss_billno) ? $row->sss_billno : '';
            $ptname = trim($row->pname . $row->fname . ' ' . $row->lname);
            $payplan = !empty($row->payplan) ? trim($row->payplan) : '80';
            $paid_val = (float)($row->sss_paid_amount ?: 0.0);
            $paid = number_format($paid_val, 2, '.', '');
            
            $visit_items = $billitems_by_vn[$row->vn] ?? [];
            $total_charge = 0.0;
            $total_claim = 0.0;
            
            // Distribute paid_money across items
            $paid_to_deduct = $paid_val;
            
            foreach ($visit_items as $item) {
                $billgr = $map_income_to_ssop_group($item->income);
                $name = trim($item->drug_name ?: $item->nondrug_name ?: '');
                
                $qty = max(1, intval($item->qty));
                $unitprice = number_format($item->unitprice, 2, '.', '');
                
                $charge_amt = (float)$qty * (float)$unitprice;
                
                $deduct = min($paid_to_deduct, $charge_amt);
                $claim_amt_val = $charge_amt - $deduct;
                $paid_to_deduct -= $deduct;
                
                $claim_up_val = $qty > 0 ? ($claim_amt_val / $qty) : 0.0;
                
                if (!empty($item->hos_guid)) {
                    $item_claim_map[$item->hos_guid] = [
                        'claim_amt' => $claim_amt_val,
                        'claim_up' => $claim_up_val,
                    ];
                }
                
                $sum_charge = number_format($charge_amt, 2, '.', '');
                $sum_claim = number_format($claim_amt_val, 2, '.', '');
                $sum_claim_up = number_format($claim_up_val, 2, '.', '');
                
                $total_charge += $charge_amt;
                $total_claim += $claim_amt_val;
                
                // RefID/DispID: If in category 3 (drugs) or category 5 (supplies), use rx_no_invoice_no, else use vn
                if ($billgr === '3' || $billgr === '5') {
                    $rx_no = !empty($item->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $item->hos_guid), 0, 9) : $item->vn;
                    if (empty($rx_no)) $rx_no = $item->vn;
                    $disp_id = "{$rx_no}_{$invoice_no}";
                } else {
                    $disp_id = $item->vn;
                }

                $billitems_rows[] = "{$invoice_no}|{$row->vstdate}|{$billgr}|{$item->icode}|{$item->tmtid}|{$name}|{$qty}|{$unitprice}|{$sum_charge}|{$sum_claim_up}|{$sum_claim}|{$disp_id}|OP1";
            }
            
            $income = number_format($total_charge, 2, '.', '');
            $claim = number_format($total_claim, 2, '.', '');
            
            $dttran = date('Y-m-d\TH:i:s', strtotime("{$row->vstdate} {$row->vsttime}"));
            $billtran_rows[] = "01||{$dttran}|{$hcode}|{$invoice_no}|{$sub_id}|{$row->hn}||{$income}|{$paid}||{$tflag}|{$row->cid}|{$ptname}|{$row->hospmain}|{$payplan}|{$claim}||0.00";
        }

        $billtran_count = count($billtran_rows);
        $billtran_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\r\n" .
            '<ClaimRec System="OP" PayPlan="SS" Version="0.93" Prgs="HX">' . "\r\n" .
            '<Header>' . "\r\n" .
            "<HCODE>{$hcode}</HCODE>\r\n" .
            "<HNAME>{$hname}</HNAME>\r\n" .
            "<DATETIME>{$datetime_iso}</DATETIME>\r\n" .
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

        // Fetch drug and supply items matching the group 3 and 5 items in BillItems
        $disp_items = DB::connection('hosxp')->select("
            SELECT op.vn, op.icode, op.qty, op.sum_price, op.unitprice, op.hos_guid, op.rxtime,
                   op.income, op.pttype,
                   COALESCE(nd.tmtid, sd.sks_drug_code, d3.ref_code, di.sks_drug_code) AS tmtid,
                   COALESCE(sd.name, n.name) AS name,
                   COALESCE(nd.productcat, di.sks_product_category_id, sd.sks_product_category_id, '1') AS sks_product_category_id,
                   di.capacity_name, di.capacity_qty,
                   op.drugusage, du.opi_usage_code, du.opi_unit_name,
                   CONCAT(IFNULL(du.name1,''), ' ', IFNULL(du.name2,''), ' ', IFNULL(du.name3,'')) AS drugusage_text,
                   sd.units, nd.packsize
            FROM opitemrece op
            LEFT JOIN s_drugitems sd ON sd.icode = op.icode
            LEFT JOIN nondrugitems n ON n.icode = op.icode
            LEFT JOIN drugitems di ON di.icode = op.icode
            LEFT JOIN drugusage du ON du.drugusage = op.drugusage
            LEFT JOIN drugitems_ref_code d3 ON d3.icode = op.icode AND d3.drugitems_ref_code_type_id = 3
            LEFT JOIN hrims.drugcat_chi nd ON nd.hospdrugcode = op.icode 
                AND nd.date_approved = (
                    SELECT MAX(nd1.date_approved) 
                    FROM hrims.drugcat_chi nd1 
                    WHERE nd.hospdrugcode = nd1.hospdrugcode 
                    AND nd1.updateflag IN ('A','U','E')
                )
            WHERE op.vn IN ($visits_placeholders)
            AND op.income IN ('03', '04', '05', '17')
        ", $vns);


        foreach ($disp_items as $item) {
            $v = $visits_map->get($item->vn);
            if (!$v) continue;

            if (in_array($item->pttype, $exclude_pttypes)) {
                continue;
            }
            $sss_pttype = $sss_pttypes_by_vn[$item->vn] ?? null;
            if ($sss_pttype !== null && !empty($item->pttype) && $item->pttype !== $sss_pttype) {
                continue;
            }

            $raw_invo = !empty($v->sss_invno) ? $v->sss_invno : (!empty($v->debt_id_list) ? $v->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($v->vn, $raw_invo, $rep_invs_by_vn, $sss_debt_map);
            $rx_no = !empty($item->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $item->hos_guid), 0, 9) : $v->vn;
            if (empty($rx_no)) $rx_no = $v->vn;
            $disp_id = "{$rx_no}_{$invoice_no}";

            // Group Dispensing rows by unique disp_id
            if (!isset($disp_sessions[$disp_id])) {
                $disp_date = date('Y-m-d\TH:i:s', strtotime("{$v->vstdate} {$v->vsttime}"));
                $rxtime_val = !empty($item->rxtime) ? $item->rxtime : date('H:i:s', strtotime($v->vsttime . ' + 30 minutes'));
                $end_date = date('Y-m-d\TH:i:s', strtotime("{$v->vstdate} {$rxtime_val}"));
                $license = !empty($v->rx_license_no) ? $v->rx_license_no : (!empty($v->doctor_license) ? $v->doctor_license : '-');
                
                // Count items in this session
                $session_items = array_filter($disp_items, function($x) use ($item, $rx_no) {
                    $x_rx_no = !empty($x->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $x->hos_guid), 0, 9) : $x->vn;
                    return $x->vn === $item->vn && $x_rx_no === $rx_no;
                });
                $session_count = count($session_items);
                
                $session_sum_charge = 0.0;
                $session_sum_claim = 0.0;
                foreach ($session_items as $x) {
                    $x_qty = max(1, intval($x->qty));
                    $x_up = number_format($x->unitprice, 2, '.', '');
                    $session_sum_charge += (float)$x_qty * (float)$x_up;
                    
                    $x_claim_info = $item_claim_map[$x->hos_guid] ?? null;
                    if ($x_claim_info) {
                        $session_sum_claim += $x_claim_info['claim_amt'];
                    } else {
                        $session_sum_claim += (float)$x_qty * (float)$x_up;
                    }
                }
                $total_amt_session = number_format($session_sum_charge, 2, '.', '');
                $total_claim_session = number_format($session_sum_claim, 2, '.', '');
                $total_paid_session = (float)$total_amt_session - (float)$total_claim_session;
                $total_paid_session_str = number_format($total_paid_session, 2, '.', '');
                
                // SSOP Dispensing row layout: hcode|disp_id|invoice_no|hn|cid|disp_date|end_date|license|Itemcnt|total_amt|total_amt|0.00|0.00|HP|SS|DispeStat|vn|
                // Swapped fields bug fixed here: put $session_count in 9th field, and 1 (DispeStat) in 16th field.
                $billdisp_rows[] = "{$hcode}|{$disp_id}|{$invoice_no}|{$v->hn}|{$v->cid}|{$disp_date}|{$end_date}|{$license}|{$session_count}|{$total_amt_session}|{$total_claim_session}|{$total_paid_session_str}|0.00|HP|SS|1|{$v->vn}|";
                $disp_sessions[$disp_id] = true;
            }

            $prdcat = !empty($item->sks_product_category_id) ? $item->sks_product_category_id : '';
            if (str_starts_with($item->icode, '3')) {
                // If it starts with 3, it's a non-drug, so it must be 6 or 7 (never 1-5)
                if ($item->income === '05') {
                    $prdcat = '6';
                } else {
                    $prdcat = '7';
                }
            } elseif (empty($prdcat)) {
                $prdcat = '1';
            }
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
            
            // Resolve capacity_name and unit of measure fallbacks
            // Leaving them empty for category 5 supplies (non-drugs) to match successful files
            $is_drug = ($item->income !== '05');
            $capacity_name = '';
            $unit_name = '';
            if ($is_drug) {
                $capacity_name = !empty($item->capacity_name) ? trim($item->capacity_name) : (!empty($item->packsize) ? trim($item->packsize) : (!empty($item->units) ? trim($item->units) : 'ชิ้น'));
                $unit_name = !empty($item->opi_unit_name) ? trim($item->opi_unit_name) : (!empty($item->units) ? trim($item->units) : 'ชิ้น');
            }

            $qty = max(1, intval($item->qty));
            $unit_price = number_format($item->unitprice, 2, '.', '');
            $total_amt_val = (float)$qty * (float)$unit_price;
            $total_amt = number_format($total_amt_val, 2, '.', '');

            $claim_info = $item_claim_map[$item->hos_guid] ?? null;
            if ($claim_info) {
                $total_reimb_val = $claim_info['claim_amt'];
                $reimb_price_val = $claim_info['claim_up'];
            } else {
                $total_reimb_val = $total_amt_val;
                $reimb_price_val = (float)$unit_price;
            }

            $reimb_price = number_format($reimb_price_val, 2, '.', '');
            $total_reimb = number_format($total_reimb_val, 2, '.', '');
            
            $paid_for_item = $total_amt_val - $total_reimb_val;
            $item_paid = number_format($paid_for_item, 2, '.', '');

            $dispensed_rows[] = "{$disp_id}|{$prdcat}|{$item->icode}|{$tmtid}|{$capacity_name}|{$item->name}|{$unit_name}|{$sigcode}|{$sigtext}|{$qty}|{$unit_price}|{$total_amt}|{$reimb_price}|{$total_reimb}|{$item_paid}|OD|||";
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
            $invoice_no = $this->resolve_invoice_no($row->vn, $raw_invo, $rep_invs_by_vn, $sss_debt_map);
            $start_dt = date('Y-m-d\TH:i:s', strtotime("{$row->vstdate} {$row->vsttime}"));
            $end_dt = date('Y-m-d\TH:i:s', strtotime("{$row->vstdate} {$row->vsttime} + 2 hours"));
            
            // Fetch diagnosis
            $diags = DB::connection('hosxp')->select("
                SELECT icd10, diagtype 
                FROM ovstdiag 
                WHERE vn = ?
            ", [$row->vn]);
                
             foreach ($diags as $d) {
                $diag_code = trim($d->icd10);
                if (empty($diag_code) || preg_match('/^[0-9]/', $diag_code)) {
                    continue;
                }
                $icd_type = (str_starts_with(strtoupper($diag_code), 'K') || preg_match('/^U[567]/i', $diag_code)) ? 'TT' : 'IT';
                $clean_diag = str_replace('.', '', $diag_code);
                $opdx_rows[] = "EC|{$row->vn}|{$d->diagtype}|{$icd_type}|{$clean_diag}|";
            }
            
            $doc_license = !empty($row->doctor_license) ? $row->doctor_license : (!empty($row->rx_license_no) ? $row->rx_license_no : '-');
            $opservices_rows[] = "{$invoice_no}|{$row->vn}|EC|{$hcode}|{$row->hn}|{$row->cid}|1|01|9|9||{$doc_license}|99|{$start_dt}|{$end_dt}||||0.00|Y||OP1";
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
            'disp_items' => $disp_items,
            'sss_debt_map' => $sss_debt_map
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
            $invoice_no = $this->resolve_invoice_no($vn, $raw_invo, $rep_invs_by_vn, $data['sss_debt_map'] ?? []);
            
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
                $item_prdcat = !empty($item->sks_product_category_id) ? (string)$item->sks_product_category_id : '';
                if (str_starts_with($item->icode, '3')) {
                    if ($item->income === '05') {
                        $item_prdcat = '6';
                    } else {
                        $item_prdcat = '7';
                    }
                } elseif (empty($item_prdcat)) {
                    $item_prdcat = '1';
                }
                // Only require TMT for Modern Medicine (prdcat = 1)
                if ($item_prdcat === '1' && empty($item->tmtid)) {
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
    private function resolve_invoice_no($vn, $raw_invo, $rep_invs_by_vn = [], $sss_debt_map = [])
    {
        if (isset($sss_debt_map[$vn])) {
            return (string)$sss_debt_map[$vn];
        }

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