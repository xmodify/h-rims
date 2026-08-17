<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\LicenseVerificationService;
use ZipArchive;

class SssExportController extends Controller
{
    private function escape_xml($val)
    {
        if (empty($val)) return '';
        return str_replace(['&', '<', '>', '"', "'"], ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'], $val);
    }

    /**
     * Helper to generate raw SSOP data (raw XML strings and rows)
     */
    private function generate_ssop_raw_data($vns, $sess_no, $station_id, $tflag = 'A')
    {
        $hcode = LicenseVerificationService::getHcode();
        
        $hname = Cache::remember('hospitalname_licensed', 86400, function() {
            try {
                return DB::connection('hosxp')->table('opdconfig')->value('hospitalname');
            } catch (\Throwable $e) {
                return 'รพ. ';
            }
        });
        $hname = $this->escape_xml($hname);

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
                   doc.licenseno AS doctor_license, doc.name AS doctor_name,
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
            $rep_records = DB::table('rep_sss_ssop')
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
                   COALESCE(li.tmlt_code, lsg.tmlt_code, nd.tmtid, sd.sks_drug_code, d3.ref_code, di.sks_drug_code, n.nhso_adp_code) AS tmtid
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
            LEFT JOIN lab_items li ON li.icode = op.icode
            LEFT JOIN lab_items_sub_group lsg ON lsg.group_icode = op.icode
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
            $ptname = $this->escape_xml(trim($row->pname . $row->fname . ' ' . $row->lname));
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
                $name = $this->escape_xml(trim($item->drug_name ?: $item->nondrug_name ?: ''));
                
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
                $license = !empty($v->doctor_license) ? $v->doctor_license : '-';
                
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

            $prdcat = !empty($item->sks_product_category_id) ? (string)$item->sks_product_category_id : '';
            if (str_starts_with($item->icode, '3')) {
                // If it starts with 3, it's a non-drug, so it must be 6 or 7 (never 1-5)
                if ($item->income === '05') {
                    $prdcat = '6';
                } else {
                    $prdcat = '7';
                }
            } elseif (empty($prdcat) || !in_array($prdcat, ['1', '2', '3', '4', '5'])) {
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

            $item_name_escaped = $this->escape_xml(trim($item->name));
            $sigtext_escaped = $this->escape_xml($sigtext);
            $capacity_name_escaped = $this->escape_xml($capacity_name);
            $unit_name_escaped = $this->escape_xml($unit_name);

            $dispensed_rows[] = "{$disp_id}|{$prdcat}|{$item->icode}|{$tmtid}|{$capacity_name_escaped}|{$item_name_escaped}|{$unit_name_escaped}|{$sigcode}|{$sigtext_escaped}|{$qty}|{$unit_price}|{$total_amt}|{$reimb_price}|{$total_reimb}|{$item_paid}|OD|||";
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
            
            $doc_license = !empty($row->doctor_license) ? $row->doctor_license : '-';
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
            'exclude_pttypes' => $exclude_pttypes,
            'sss_pttypes_by_vn' => $sss_pttypes_by_vn,
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
        $exclude_pttypes = $data['exclude_pttypes'] ?? [];
        $sss_pttypes_by_vn = $data['sss_pttypes_by_vn'] ?? [];

        $billtran_table = [];
        foreach ($data['billtran_rows'] as $row) {
            $fields = explode('|', $row);
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
            $rep_records = DB::table('rep_sss_ssop')
                ->whereIn('vn', $vns)
                ->select('vn', 'invno')
                ->get();
            foreach ($rep_records as $r) {
                $rep_invs_by_vn[$r->vn][] = trim($r->invno);
            }
        }

        // Fetch doctor names by license
        $licenses = [];
        foreach ($opservices_table as $os) {
            $lic = trim($os[11] ?? '');
            if ($lic !== '' && $lic !== '-') {
                $licenses[] = $lic;
            }
        }
        $doctor_names_by_license = [];
        if (!empty($licenses)) {
            $docs = DB::connection('hosxp')
                ->table('doctor')
                ->whereIn('licenseno', $licenses)
                ->select('licenseno', 'name')
                ->get();
            foreach ($docs as $d) {
                $doctor_names_by_license[trim($d->licenseno)] = trim($d->name);
            }
        }

        // Perform backend validation to detect missing required fields
        $validation = [];
        foreach ($data['visits_list'] as $row) {
            $rowObj = (object)$row;
            if (empty($rowObj->sss_pttype)) {
                continue;
            }
            $vn = $rowObj->vn;
            $errors = [];

            $raw_invo = !empty($rowObj->sss_invno) ? $rowObj->sss_invno : (!empty($rowObj->debt_id_list) ? $rowObj->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($vn, $raw_invo, $rep_invs_by_vn, $data['sss_debt_map'] ?? []);
            
            // Match BILLTRAN row by invoice_no
            $bt_row = null;
            foreach ($billtran_table as $bt) {
                if (isset($bt[4]) && $bt[4] === $invoice_no) {
                    $bt_row = $bt;
                    break;
                }
            }

            // 1. BILLTRAN checks
            if (empty($invoice_no)) {
                $errors['billtran'][] = "ไม่พบเลขใบแจ้งหนี้ (InvNo)";
            } elseif ($invoice_no === $vn) {
                $errors['billtran'][] = "เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้)";
            }
            if (empty($rowObj->cid) || strlen($rowObj->cid) !== 13) {
                $errors['billtran'][] = "เลขบัตรประชาชน (CID) ว่างหรือความยาวไม่ครบ 13 หลัก";
            }
            if (empty($rowObj->hn)) {
                $errors['billtran'][] = "ไม่พบ HN";
            }
            $vn_claim_sum = $bt_row ? (float)($bt_row[16] ?? 0.0) : 0.0;
            if ($vn_claim_sum <= 0) {
                $errors['billtran'][] = "ยอดเงินเรียกเก็บ (ClaimAmt) ต้องมากกว่า 0";
            }

            // 2. BILLDISP checks for this VN
            if (empty($invoice_no)) {
                $errors['billdisp'][] = "ไม่พบเลขใบแจ้งหนี้ (InvNo)";
            } elseif ($invoice_no === $vn) {
                $errors['billdisp'][] = "เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้)";
            }
            $vn_disp_items = array_filter($data['disp_items'], function($item) use ($vn, $exclude_pttypes, $sss_pttypes_by_vn) {
                if ($item->vn !== $vn) {
                    return false;
                }
                if (in_array($item->pttype, $exclude_pttypes)) {
                    return false;
                }
                $sss_pttype = $sss_pttypes_by_vn[$item->vn] ?? null;
                if ($sss_pttype !== null && !empty($item->pttype) && $item->pttype !== $sss_pttype) {
                    return false;
                }
                return true;
            });
            
            $has_dispense = !empty($vn_disp_items);
            if ($has_dispense) {
                $disp_row = null;
                foreach ($billdisp_table as $bd) {
                    if (isset($bd[16]) && $bd[16] === $vn) {
                        $disp_row = $bd;
                        break;
                    }
                }
                if (!$disp_row) {
                    $errors['billdisp'][] = "ไม่พบรายการจัดจ่ายยาสำหรับผู้ป่วย";
                } else {
                    if (empty($disp_row[7]) || $disp_row[7] === '-') {
                        $errors['billdisp'][] = "ไม่พบเลขใบอนุญาตผู้สั่งยา/เภสัชกร";
                    }
                }
            }

            foreach ($vn_disp_items as $item) {
                $item_prdcat = !empty($item->sks_product_category_id) ? (string)$item->sks_product_category_id : '';
                if (str_starts_with($item->icode, '3')) {
                    if ($item->income === '05') {
                        $item_prdcat = '6';
                    } else {
                        $item_prdcat = '7';
                    }
                } elseif (empty($item_prdcat) || !in_array($item_prdcat, ['1', '2', '3', '4', '5'])) {
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

            $op_row = null;
            foreach ($opservices_table as $os) {
                if (isset($os[1]) && $os[1] === $vn) {
                    $op_row = $os;
                    break;
                }
            }
            if ($op_row) {
                $lic = trim($op_row[11] ?? '');
                $doc_name = $doctor_names_by_license[$lic] ?? (!empty($rowObj->doctor_name) ? trim($rowObj->doctor_name) : 'ไม่ระบุชื่อแพทย์');
                if (empty($lic)) {
                    $errors['opservices'][] = "ไม่พบเลขใบอนุญาตประกอบวิชาชีพเวชกรรมผู้สั่งตรวจรักษา (แพทย์ผู้รักษา: {$doc_name})";
                } else {
                    $is_valid_format = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
                    if (!$is_valid_format) {
                        $errors['opservices'][] = "เลขใบประกอบวิชาชีพแพทย์ '{$lic}' มีรูปแบบไม่ถูกต้อง (แพทย์ผู้รักษา: {$doc_name}) (ต้องขึ้นต้นด้วย ว, ท, ภ, พ หรือ - และตามด้วยตัวเลขเท่านั้น) (S15)";
                    }
                }
            }
            $diags = DB::connection('hosxp')->select("
                SELECT icd10, diagtype 
                FROM ovstdiag 
                WHERE vn = ?
            ", [$vn]);
            $has_pdx = false;
            $pdx_code = null;
            foreach ($diags as $d) {
                if ($d->diagtype == '1') {
                    $has_pdx = true;
                    $pdx_code = trim($d->icd10 ?? '');
                    break;
                }
            }
            if (!$has_pdx || empty($pdx_code)) {
                $errors['opservices'][] = "ไม่พบรหัสวินิจฉัยโรคหลัก (PDX)";
            } else {
                $validator = new \App\Services\ClaimValidator();
                $res = $validator->validateIcd10Chi($pdx_code, '1');
                if (!$res['is_valid']) {
                    $errors['opservices'][] = "รหัสวินิจฉัยหลัก {$pdx_code} ไม่ถูกต้องตามบัญชี สกส. (S54) (กรุณาแก้ไขรหัสโรคให้ถูกต้องใน HOSxP)";
                }
            }

            $validation[$vn] = [
                'hn' => $rowObj->hn,
                'name' => trim($rowObj->pname . $rowObj->fname . ' ' . $rowObj->lname),
                'vstdate' => $rowObj->vstdate,
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
     * Pre-Audit and Preview AIPN Export
     */
    public function sss_export_preview_aipn(Request $request)
    {
        $ans = $request->input('ans', []);
        $session_no = $request->input('session_no');
        $tcode = $request->input('tcode', '');
        $care_as = $request->input('care_as', 'M');

        if (empty($ans)) {
            return response()->json(['success' => false, 'message' => 'กรุณาเลือก AN ที่ต้องการส่งออก']);
        }

        try {
            $data = $this->generate_aipn_data_array($ans, $session_no, $tcode, $care_as);
            return response()->json(array_merge(['success' => true], $data));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage() . ' at line ' . $e->getLine()
            ], 500);
        }
    }

    /**
     * Download AIPN ZIP
     */
    public function sss_export_aipn(Request $request)
    {
        $ans = $request->input('ans', []);
        $session_no = $request->input('session_no');
        $tcode = $request->input('tcode', '');
        $care_as = $request->input('care_as', 'M');

        if (empty($ans)) {
            return redirect()->back()->with('error', 'กรุณาเลือก AN ที่ต้องการส่งออก');
        }

        try {
            $data = $this->generate_aipn_data_array($ans, $session_no, $tcode, $care_as);
            
            $hcode = LicenseVerificationService::getHcode();
            $tcode_suffix = !empty($tcode) ? "-{$tcode}" : "";
            $zip_name = "{$hcode}AIPN{$tcode_suffix}{$session_no}.zip";
            
            $temp_dir = storage_path('app/temp_aipn');
            if (!file_exists($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }
            $zip_path = "{$temp_dir}/{$zip_name}";

            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($data['xml_files'] as $filename => $xml_content) {
                    // Convert UTF-8 XML string to windows-874 using iconv
                    $encoded_content = iconv('UTF-8', 'windows-874//IGNORE', $xml_content);
                    $zip->addFromString($filename, $encoded_content);
                }
                $zip->close();
            } else {
                return redirect()->back()->with('error', 'ไม่สามารถสร้างไฟล์ ZIP ได้');
            }

            return response()->download($zip_path)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการสร้างไฟล์ ZIP: ' . $e->getMessage());
        }
    }

    /**
     * Core logic to fetch data, perform pre-audit, and generate XMLs
     */
    private function generate_aipn_data_array($ans, $session_no, $tcode = '', $care_as = 'M')
    {
        $hcode = LicenseVerificationService::getHcode();
        
        $hname = Cache::remember('hospitalname_licensed', 86400, function() {
            try {
                return DB::connection('hosxp')->table('opdconfig')->value('hospitalname');
            } catch (\Throwable $e) {
                return 'รพ.';
            }
        });

        $subm_dt = date('YmdHis');
        $datetime_iso = date('Y-m-d\TH:i:s');

        $ipadt_data = [];
        $ipdx_data = [];
        $ipop_data = [];
        $billitems_data = [];
        $audit_results = [];
        $xml_files = [];
        $first_xml = '';

        // Query Patient Inpatient records
        $ans_placeholders = implode(',', array_fill(0, count($ans), '?'));
        $admissions = DB::connection('hosxp')->select("
            SELECT i.an, i.hn, pt.pname, pt.fname, pt.lname, pt.cid, pt.birthday AS dob, pt.sex, pt.marrystatus AS marry, pt.nationality,
                   i.regdate, i.regtime, i.dchdate, i.dchtime, i.dchstts AS dch_status, i.dchtype AS dch_type,
                   i.bw AS admwt,
                   i.ward, i.spclty,
                   COALESCE(NULLIF(ip.auth_code, ''), NULLIF(vp.auth_code, '')) AS auth_code,
                   ip.hospmain AS hospmain,
                   pu.pttype_upp_type_code AS payplan,
                   i.vn
            FROM ipt i
            LEFT JOIN an_stat a ON a.an = i.an
            LEFT JOIN patient pt ON pt.hn = i.hn
            LEFT JOIN ipt_pttype ip ON ip.an = i.an
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN visit_pttype vp ON vp.vn = i.vn
            LEFT JOIN pttype_upp_type pu ON pu.pttype_upp_type_id = p.pttype_upp_type_id
            WHERE i.an IN ($ans_placeholders)
        ", $ans);

        $validator = new \App\Services\ClaimValidator();

        foreach ($admissions as $adm) {
            $an = $adm->an;
            $hn = $adm->hn;
            $ptname = trim($adm->pname . $adm->fname . ' ' . $adm->lname);

            // DTAdm & DTDisch
            $dtadm = $adm->regdate . 'T' . ($adm->regtime ?: '00:00:00');
            $dtdisch = $adm->dchdate . 'T' . ($adm->dchtime ?: '00:00:00');

            // Map demographic fields
            $idtype = (!empty($adm->cid) && strlen($adm->cid) === 13) ? '0' : '9';
            $pidpat = $adm->cid ?: '';
            
            // Sex: 1=ชาย, 2=หญิง
            $sex = ($adm->sex == '1' || $adm->sex == 'ชาย') ? '1' : (($adm->sex == '2' || $adm->sex == 'หญิง') ? '2' : '9');
            
            // Marry mapping
            $marry = '9';
            if ($adm->marry == '1' || $adm->marry == 'โสด') $marry = '1';
            elseif ($adm->marry == '2' || $adm->marry == 'คู่' || $adm->marry == 'สมรส') $marry = '2';
            elseif ($adm->marry == '3' || $adm->marry == 'หม้าย' || $adm->marry == 'หย่า') $marry = '3';

            // Nation mapping
            $nation = ($adm->nationality == '99' || $adm->nationality == 'TH' || strpos($adm->nationality, 'ไทย') !== false) ? '99' : '97';

            // AdmType
            $admtype = 'O';
            if (isset($adm->adm_type)) {
                if ($adm->adm_type == 'A') $admtype = 'A';
                elseif ($adm->adm_type == 'E') $admtype = 'E';
                elseif ($adm->adm_type == 'C') $admtype = 'C';
                elseif ($adm->adm_type == 'L') $admtype = 'L';
                elseif ($adm->adm_type == 'N') $admtype = 'N';
                elseif ($adm->adm_type == 'U') $admtype = 'U';
            }

            // AdmSource
            $admsource = 'O';
            if (isset($adm->adm_source)) {
                if ($adm->adm_source == 'O') $admsource = 'O';
                elseif ($adm->adm_source == 'E') $admsource = 'E';
                elseif ($adm->adm_source == 'B') $admsource = 'B';
                elseif ($adm->adm_source == 'T') $admsource = 'T';
                elseif ($adm->adm_source == 'R') $admsource = 'R';
            }

            // Weight
            $admwt = $adm->admwt ? number_format($adm->admwt, 3, '.', '') : '0.000';

            // UPayPlan check
            $upayplan = $adm->payplan ?: '80';

            // Build IPADT array for preview
            $ipadt_data[] = [
                'an' => $an,
                'hn' => $hn,
                'idtype' => $idtype,
                'pidpat' => $pidpat,
                'ptname' => $ptname,
                'dob' => $adm->dob,
                'sex' => $sex,
                'admtype' => $admtype,
                'admsource' => $admsource,
                'dtadm' => $dtadm,
                'dtdisch' => $dtdisch,
                'dischstat' => $adm->dch_status ?: '1',
                'dischtype' => $adm->dch_type ?: '1',
                'admwt' => $admwt
            ];

            // 1. Check Authen
            if (empty($adm->auth_code)) {
                $audit_results[] = [
                    'an' => $an,
                    'hn' => $hn,
                    'ptname' => $ptname,
                    'message' => "ยังไม่มีเลขอนุมัติสิทธิ์ (Authen Code) หรือขอสิทธิ์ไม่สำเร็จ",
                    'level' => 'error'
                ];
            }

            // Coinsurance Check
            // Query all patient rights registered for this admission in ipt_pttype
            $pttypes = DB::connection('hosxp')->select("
                SELECT ip.pttype, p.hipdata_code, p.name, s.cipn_instype_code
                FROM ipt_pttype ip
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN sks_benefit_plan_type s ON s.sks_benefit_plan_type_id = p.sks_benefit_plan_type_id
                WHERE ip.an = ?
            ", [$an]);

            $has_ssem72 = false;
            foreach ($pttypes as $pt) {
                if ($pt->cipn_instype_code === 'SSEM72' || $pt->pttype === 'SSEM72' || strpos($pt->pttype, 'SSEM72') !== false || strpos($pt->name, 'SSEM72') !== false) {
                    $has_ssem72 = true;
                    break;
                }
            }

            if (in_array($upayplan, ['85', '95'])) {
                if (!$has_ssem72) {
                    $audit_results[] = [
                        'an' => $an,
                        'hn' => $hn,
                        'ptname' => $ptname,
                        'message' => "สิทธิหลัก (UPayPlan {$upayplan}) ต้องการสิทธิร่วมจ่าย Coinsurance SSEM72 (Error 369) กรุณาเพิ่มสิทธิร่วมให้ถูกต้อง",
                        'level' => 'error'
                    ];
                }
            }

            // 1. Diagnosis
            $diags = DB::connection('hosxp')->select("
                SELECT d.icd10, d.diagtype, d.doctor, doc.licenseno, doc.name AS doctor_name, d.entry_datetime
                FROM iptdiag d
                LEFT JOIN doctor doc ON doc.code = d.doctor
                WHERE d.an = ?
                ORDER BY d.diagtype ASC, d.diag_no ASC
            ", [$an]);

            $ipdx_rows = [];
            $has_pdx = false;
            foreach ($diags as $idx => $d) {
                $diagtype = $d->diagtype ?: '3';
                if ($diagtype == '1') {
                    $has_pdx = true;
                }

                $icd10 = trim($d->icd10);
                
                // Validate ICD10 against standard
                $val_res = $validator->validateIcd10Chi($icd10, $diagtype);
                if (!$val_res['is_valid'] && !in_array(substr($icd10, 0, 2), ['U5', 'U6', 'U7'])) {
                    $audit_results[] = [
                        'an' => $an,
                        'hn' => $hn,
                        'ptname' => $ptname,
                        'message' => "รหัสวินิจฉัยโรค {$icd10} ประเภท {$diagtype} ไม่ถูกต้องตามบัญชี สกส. (S54)",
                        'level' => 'error'
                    ];
                }

                $ipdx_rows[] = [
                    'an' => $an,
                    'seq' => $idx + 1,
                    'dxtype' => $diagtype,
                    'codesys' => 'ICD-10',
                    'code' => $icd10,
                    'diagterm' => $this->escape_xml($d->icd10),
                    'dr' => $d->licenseno ?: 'ว00000',
                    'datediag' => substr($d->entry_datetime ?: $adm->regdate, 0, 10)
                ];
                $ipdx_data[] = end($ipdx_rows);
            }

            if (!$has_pdx) {
                $audit_results[] = [
                    'an' => $an,
                    'hn' => $hn,
                    'ptname' => $ptname,
                    'message' => "ไม่พบรหัสวินิจฉัยโรคหลัก (PDX)",
                    'level' => 'error'
                ];
            }

            // 2. Operation/Procedure
            $procs = DB::connection('hosxp')->select("
                SELECT o.icd9, o.opdate, o.optime, o.enddate, o.endtime, o.doctor, doc.licenseno, o.oper_note_text
                FROM iptoprt o
                LEFT JOIN doctor doc ON doc.code = o.doctor
                WHERE o.an = ?
                ORDER BY o.iptoprt_id ASC
            ", [$an]);

            $ipop_rows = [];
            foreach ($procs as $idx => $p) {
                $datein = ($p->opdate ?: $adm->regdate) . 'T' . ($p->optime ?: '00:00:00');
                $dateout = ($p->enddate ?: $adm->dchdate) . 'T' . ($p->endtime ?: '00:00:00');

                // Check datein and dateout are within admission range
                if ($p->opdate < $adm->regdate || $p->opdate > $adm->dchdate) {
                    $audit_results[] = [
                        'an' => $an,
                        'hn' => $hn,
                        'ptname' => $ptname,
                        'message' => "วันที่ทำหัตถการ {$p->opdate} อยู่นอกช่วงการนอนโรงพยาบาล (Error 251)",
                        'level' => 'error'
                    ];
                }

                $ipop_rows[] = [
                    'an' => $an,
                    'seq' => $idx + 1,
                    'codesys' => 'ICD9CM',
                    'code' => trim($p->icd9),
                    'procterm' => $this->escape_xml(trim($p->icd9)),
                    'dr' => $p->licenseno ?: 'ว00000',
                    'datein' => $datein,
                    'dateout' => $dateout,
                    'location' => '1'
                ];
                $ipop_data[] = end($ipop_rows);
            }

            // 3. BillItems (Inpatient item charges)
            $billitems_raw = DB::connection('hosxp')->select("
                SELECT o.vstdate, o.icode, o.qty, o.sum_price, o.unitprice, o.discount, o.income, inc.income_csmbs_code,
                       n.nhso_adp_code,
                       COALESCE((SELECT name FROM drugitems WHERE icode = o.icode), (SELECT name FROM nondrugitems WHERE icode = o.icode)) AS item_name
                FROM opitemrece o
                LEFT JOIN income inc ON inc.income = o.income
                LEFT JOIN nondrugitems n ON n.icode = o.icode
                WHERE o.an = ?
            ", [$an]);

            $billitems_rows = [];
            $seq_item = 1;
            foreach ($billitems_raw as $item) {
                $qty = (float)$item->qty;
                $unitprice = (float)$item->unitprice;
                $charge_amt = (float)$item->sum_price ?: ($qty * $unitprice);
                $discount = (float)($item->discount ?: 0.0);

                // Skip items with price or quantity <= 0 (e.g., patient's own medicines)
                if ($charge_amt <= 0 || $qty <= 0) {
                    continue;
                }

                // Map to AIPN BillGr
                $billgr = '19';
                if (!empty($item->income_csmbs_code)) {
                    $csmbs_code = trim($item->income_csmbs_code);
                    if ($csmbs_code === '88') {
                        $billgr = '17';
                    } else {
                        $billgr = str_pad($csmbs_code, 2, '0', STR_PAD_LEFT);
                    }
                } else {
                    $inc = str_pad($item->income, 2, '0', STR_PAD_LEFT);
                    switch ($inc) {
                        case '01': $billgr = '01'; break;
                        case '02': $billgr = '02'; break;
                        case '10': $billgr = '10'; break;
                        case '03': $billgr = '03'; break;
                        case '04': $billgr = '04'; break;
                        case '05': $billgr = '05'; break;
                        case '06': $billgr = '06'; break;
                        case '07': $billgr = '07'; break;
                        case '08': $billgr = '08'; break;
                        case '09': $billgr = '09'; break;
                        case '16': $billgr = '12'; break;
                        case '12': $billgr = '16'; break;
                        case '13': $billgr = '14'; break;
                        case '14': $billgr = '13'; break;
                        case '15': $billgr = '15'; break;
                        case '11': $billgr = '11'; break;
                    }
                }

                $billgrcs = $billgr;
                $stdcode = '';
                $claimcat = 'D';
                $daterev = null;

                // If claimcat is T (income category 10/02), check lookup_sss_equipdev_aipn
                if ($billgr === '02') {
                    $claimcat = 'T';
                    // Fetch limit rate from lookup_sss_equipdev_aipn
                    $std_adp_code = !empty($item->nhso_adp_code) ? trim($item->nhso_adp_code) : $item->icode;
                    $equip = DB::table('lookup_sss_equipdev_aipn')->where('code', $std_adp_code)->first();
                    if ($equip) {
                        $stdcode = $equip->code;
                        $daterev = $equip->daterev;
                        if ($unitprice > $equip->rate) {
                            $audit_results[] = [
                                'an' => $an,
                                'hn' => $hn,
                                'ptname' => $ptname,
                                'message' => "รหัสอุปกรณ์ {$std_adp_code} ราคาเรียกเก็บ (" . number_format($unitprice, 2) . ") เกินอัตราที่กำหนด (" . number_format($equip->rate, 2) . ") (Error 365)",
                                'level' => 'error'
                            ];
                        }
                    } else {
                        if (!empty($item->nhso_adp_code)) {
                            $stdcode = trim($item->nhso_adp_code);
                        }
                    }
                }

                // Drugs check against catalog (03, 04)
                if (in_array($billgr, ['03', '04'])) {
                    $drug = DB::table('drugcat_chi')->where('hospdrugcode', $item->icode)->first();
                    if ($drug) {
                        $stdcode = $drug->tmtid ?: '';
                        if (empty($stdcode)) {
                            // Herbal medicine, supplies, etc. (productcat 3-7) does not require TMT
                            if ((int)$drug->productcat < 3) {
                                $audit_results[] = [
                                    'an' => $an,
                                    'hn' => $hn,
                                    'ptname' => $ptname,
                                    'message' => "รหัสยา {$item->icode} (" . trim($item->item_name) . ") ไม่มีรหัส TMTID/STDCode (Error 644)",
                                    'level' => 'error'
                                ];
                            }
                        }
                    } else {
                        if (str_starts_with(trim($item->icode), '1')) {
                            // Remap drug not in catalog to other services
                            $billgr = '17';
                            $billgrcs = '88';
                            $stdcode = '';
                            $claimcat = 'D';

                            $audit_results[] = [
                                'an' => $an,
                                'hn' => $hn,
                                'ptname' => $ptname,
                                'message' => "รหัสยา {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Drug Catalog (Error 666)",
                                'level' => 'error'
                            ];
                        } else {
                            $audit_results[] = [
                                'an' => $an,
                                'hn' => $hn,
                                'ptname' => $ptname,
                                'message' => "รหัสยา {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Drug Catalog (Error 666)",
                                'level' => 'error'
                            ];
                        }
                    }
                }

                // Lab/Blood Catalog check (06, 07)
                if (in_array($billgr, ['06', '07'])) {
                    $lab = DB::table('labcat_chi')->where('lccode', $item->icode)->orWhere('cscode', $item->icode)->first();
                    if ($lab) {
                        $stdcode = $lab->tmlt ?: '';
                        if (empty($stdcode)) {
                            $audit_results[] = [
                                'an' => $an,
                                'hn' => $hn,
                                'ptname' => $ptname,
                                'message' => "รหัสตรวจวิเคราะห์/โลหิต {$item->icode} (" . trim($item->item_name) . ") ไม่มีรหัส TMLT/STDCode (Error 644)",
                                'level' => 'error'
                            ];
                            // FALLBACK: map to other service categories
                            $billgr = '17';
                            $billgrcs = '88';
                            $stdcode = '';
                        }
                    } else {
                        if (str_starts_with(trim($item->icode), '3')) {
                            // Remap lab not in catalog to other services
                            $billgr = '17';
                            $billgrcs = '88';
                            $stdcode = '';
                            $claimcat = 'D';

                            $audit_results[] = [
                                'an' => $an,
                                'hn' => $hn,
                                'ptname' => $ptname,
                                'message' => "รหัสตรวจวิเคราะห์/โลหิต {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Lab Catalog (Error 661)",
                                'level' => 'error'
                            ];
                        } else {
                            $audit_results[] = [
                                'an' => $an,
                                'hn' => $hn,
                                'ptname' => $ptname,
                                'message' => "รหัสตรวจวิเคราะห์/โลหิต {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Lab Catalog (Error 661)",
                                'level' => 'error'
                            ];
                            // FALLBACK: map to other service categories
                            $billgr = '17';
                            $billgrcs = '88';
                            $stdcode = '';
                        }
                    }
                }

                $billitems_rows[] = [
                    'an' => $an,
                    'seq' => $seq_item++,
                    'servdate' => $item->vstdate,
                    'billgr' => $billgr,
                    'lccode' => $item->icode,
                    'descript' => $this->escape_xml(trim($item->item_name ?: '')),
                    'qty' => number_format($qty, 2, '.', ''),
                    'unitprice' => number_format($unitprice, 2, '.', ''),
                    'chargeamt' => number_format($charge_amt, 2, '.', ''),
                    'discount' => number_format($discount, 2, '.', ''),
                    'claimsys' => 'SS',
                    'billgrcs' => $billgrcs,
                    'cscode' => ($billgr === '02' && !empty($stdcode)) ? $stdcode : $item->icode,
                    'codesys' => (!empty($stdcode) && $billgr !== '02') ? (in_array($billgr, ['06', '07']) ? 'TMLT' : 'TMT') : '',
                    'stdcode' => ($billgr === '02') ? '' : $stdcode,
                    'claimcat' => $claimcat,
                    'daterev' => !empty($daterev) ? $daterev : $item->vstdate,
                    'claimup' => ($claimcat === 'D') ? '0.00' : number_format($unitprice, 2, '.', ''),
                    'claimamt' => ($claimcat === 'D') ? '0.00' : number_format($charge_amt - $discount, 2, '.', '')
                ];
                $billitems_data[] = end($billitems_rows);
            }

            // Calculate Invoices summary
            $drg_charge = 0.00;
            $xdrg_claim = 0.00;
            foreach ($billitems_rows as $row) {
                $net = (float)$row['chargeamt'] - (float)$row['discount'];
                if ($row['claimcat'] === 'D') {
                    $drg_charge += $net;
                } elseif ($row['claimcat'] === 'T') {
                    $claim_amt = (float)$row['claimamt'];
                    $xdrg_claim += min($net, $claim_amt);
                }
            }

            // 4. Generate XML content
            $xml = '<?xml version="1.0" encoding="windows-874"?>' . "\r\n";
            $xml .= '<CIPN submissionType="A">' . "\r\n";
            
            // Header
            $tcode_suffix = !empty($tcode) ? "-{$tcode}" : "";
            $xml .= '  <Header>' . "\r\n";
            $xml .= '    <DocClass>IPClaim</DocClass>' . "\r\n";
            $xml .= '    <DocSysID version="2.1">AIPN' . $tcode_suffix . '</DocSysID>' . "\r\n";
            $xml .= '    <serviceEvent>ADT</serviceEvent>' . "\r\n";
            $xml .= '    <authorID>' . $hcode . '</authorID>' . "\r\n";
            $xml .= '    <authorName>' . $this->escape_xml($hname) . '</authorName>' . "\r\n";
            $xml .= '    <effectiveTime>' . $datetime_iso . '</effectiveTime>' . "\r\n";
            $xml .= '  </Header>' . "\r\n";

            // ClaimAuth
            $hmain = $adm->hospmain ?: $hcode;
            $xml .= '  <ClaimAuth>' . "\r\n";
            $xml .= '    <AuthCode>' . $this->escape_xml($adm->auth_code ?: '') . '</AuthCode>' . "\r\n";
            $xml .= '    <AuthDT>' . $dtadm . '</AuthDT>' . "\r\n";
            $xml .= '    <UPayPlan>' . $upayplan . '</UPayPlan>' . "\r\n";
            $xml .= '    <ServiceType>IP</ServiceType>' . "\r\n";
            $xml .= '    <ProjectCode></ProjectCode>' . "\r\n";
            $xml .= '    <EventCode></EventCode>' . "\r\n";
            $xml .= '    <UserReserve></UserReserve>' . "\r\n";
            $xml .= '    <Hmain>' . $hmain . '</Hmain>' . "\r\n";
            $xml .= '    <Hcare>' . $hcode . '</Hcare>' . "\r\n";
            $actual_care_as = $care_as;
            if ($actual_care_as === 'AUTO') {
                if ($hmain === $hcode) {
                    $actual_care_as = 'M';
                } else {
                    $actual_care_as = in_array($upayplan, ['85', '95']) ? 'X' : 'B';
                }
            }
            $xml .= '    <CareAs>' . $actual_care_as . '</CareAs>' . "\r\n";
            $xml .= '    <ServiceSubType></ServiceSubType>' . "\r\n";
            $xml .= '  </ClaimAuth>' . "\r\n";

            // IPADT (Delimited String)
            // AN|HN|IDTYPE|PIDPAT|TITLE|NAMEPAT|DOB|SEX|MARRIAGE|CHANGWAT|AMPHUR|NATION|AdmType|AdmSource|DTAdm|DTDisch|LeaveDay|DischStat|DischType|AdmWt|DischWard|Dept
            $ipadt_fields = [
                $an,
                $hn,
                $idtype,
                $pidpat,
                $this->escape_xml($adm->pname ?: ''),
                $this->escape_xml(trim($adm->fname . ' ' . $adm->lname)),
                $adm->dob ?: '',
                $sex,
                $marry,
                '', // CHANGWAT
                '', // AMPHUR
                $nation,
                $admtype,
                $admsource,
                $dtadm,
                $dtdisch,
                '', // LeaveDay
                $adm->dch_status ?: '1',
                $adm->dch_type ?: '1',
                $admwt,
                $adm->ward ?: '',
                $adm->spclty ?: '12' // Dept
            ];
            $xml .= '  <IPADT>' . implode('|', $ipadt_fields) . '</IPADT>' . "\r\n";

            // IPDx (Delimited String)
            // sequence|DxType|CodeSys|Code|DiagTerm|DR|DateDiag
            $xml .= '  <IPDx Reccount="' . count($ipdx_rows) . '">' . "\r\n";
            foreach ($ipdx_rows as $row) {
                $dx_fields = [
                    $row['seq'],
                    $row['dxtype'],
                    $row['codesys'],
                    $row['code'],
                    $row['diagterm'],
                    $row['dr'],
                    $row['datediag']
                ];
                $xml .= implode('|', $dx_fields) . "\r\n";
            }
            $xml .= '  </IPDx>' . "\r\n";

            // IPOp (Delimited String)
            // sequence|CodeSys|Code|ProcTerm|DR|DateIn|DateOut|Location
            $xml .= '  <IPOp Reccount="' . count($ipop_rows) . '">' . "\r\n";
            foreach ($ipop_rows as $row) {
                $op_fields = [
                    $row['seq'],
                    $row['codesys'],
                    $row['code'],
                    $row['procterm'],
                    $row['dr'],
                    $row['datein'],
                    $row['dateout'],
                    $row['location']
                ];
                $xml .= implode('|', $op_fields) . "\r\n";
            }
            $xml .= '  </IPOp>' . "\r\n";

            // Invoices (Delimited String inside BillItems)
            // sequence|ServDate|BillGr|LCCode|Descript|QTY|UnitPrice|ChargeAmt|Discount|ProcedureSeq|DiagnosisSeq|ClaimSys|BillGrCS|CSCode|CodeSys|STDCode|ClaimCat|DateRev|ClaimUP|ClaimAmt
            $xml .= '  <Invoices>' . "\r\n";
            $xml .= '    <InvNumber>' . $an . '</InvNumber>' . "\r\n";
            $xml .= '    <InvDT>' . $dtdisch . '</InvDT>' . "\r\n";
            $xml .= '    <BillItems Reccount="' . count($billitems_rows) . '">' . "\r\n";
            foreach ($billitems_rows as $row) {
                $bi_fields = [
                    $row['seq'],
                    $row['servdate'],
                    $row['billgr'],
                    $row['lccode'],
                    $row['descript'],
                    $row['qty'],
                    $row['unitprice'],
                    $row['chargeamt'],
                    $row['discount'],
                    '', // ProcedureSeq
                    '', // DiagnosisSeq
                    $row['claimsys'],
                    $row['billgrcs'],
                    $row['cscode'],
                    $row['codesys'],
                    $row['stdcode'],
                    $row['claimcat'],
                    $row['daterev'], // DateRev
                    $row['claimup'],
                    $row['claimamt']
                ];
                $xml .= implode('|', $bi_fields) . "\r\n";
            }
            $xml .= '    </BillItems>' . "\r\n";
            $xml .= '    <InvAddDiscount>0.00</InvAddDiscount>' . "\r\n";
            $xml .= '    <DRGCharge>' . number_format($drg_charge, 2, '.', '') . '</DRGCharge>' . "\r\n";
            $xml .= '    <XDRGClaim>' . number_format($xdrg_claim, 2, '.', '') . '</XDRGClaim>' . "\r\n";
            $xml .= '  </Invoices>' . "\r\n";

            // Coinsurance
            $xml .= '  <Coinsurance>' . "\r\n";
            
            // Calculate total net for fallback if needed
            $total_net = 0.00;
            foreach ($billitems_rows as $row) {
                $total_net += ((float)$row['chargeamt'] - (float)$row['discount']);
            }

            // Get registered pttypes for this admission to dynamically write Coinsurance entries
            $pttypes = DB::connection('hosxp')->select("
                SELECT ip.pttype, p.hipdata_code, p.name, s.cipn_instype_code, ip.debt_amount
                FROM ipt_pttype ip
                LEFT JOIN pttype p ON p.pttype = ip.pttype
                LEFT JOIN sks_benefit_plan_type s ON s.sks_benefit_plan_type_id = p.sks_benefit_plan_type_id
                WHERE ip.an = ?
            ", [$an]);

            $coinsurance_items = [];
            $has_ssem72_entry = false;
            
            foreach ($pttypes as $pt) {
                $code = null;
                if ($pt->cipn_instype_code === 'SSEM72' || $pt->pttype === 'SSEM72' || strpos($pt->pttype, 'SSEM72') !== false || strpos($pt->name, 'SSEM72') !== false) {
                    $code = 'SSEM72';
                    $has_ssem72_entry = true;
                } elseif ($pt->cipn_instype_code === 'RTAA' || $pt->pttype === 'RTAA' || strpos($pt->pttype, 'RTAA') !== false || strpos($pt->name, 'พรบ') !== false) {
                    $code = 'RTAA';
                } elseif ($pt->cipn_instype_code === 'CSMBS') {
                    $code = 'CSMBS';
                } elseif ($pt->cipn_instype_code === 'SSEC') {
                    $code = 'SSEC';
                } elseif ($pt->cipn_instype_code === 'MSSDLV') {
                    $code = 'MSSDLV';
                } elseif ($pt->cipn_instype_code === 'PRIV') {
                    $code = 'PRIV';
                }
                
                if ($code) {
                    $amount = (float)($pt->debt_amount ?: 0.00);
                    $coinsurance_items[$code] = $amount;
                }
            }
            
            // If UPayPlan is 85 or 95, and SSEM72 is not in the list but required, we force it
            if (in_array($upayplan, ['85', '95']) && !$has_ssem72_entry) {
                $coinsurance_items['SSEM72'] = 0.00;
            }
            
            // If SSEM72 is the only item in coinsurance, and its amount is 0, we fallback to total_net
            if (count($coinsurance_items) === 1 && isset($coinsurance_items['SSEM72']) && $coinsurance_items['SSEM72'] == 0.00) {
                $coinsurance_items['SSEM72'] = $total_net;
            }
            
            // Generate XML elements for Coinsurance
            foreach ($coinsurance_items as $ins_code => $ins_amount) {
                $xml .= '    <Insurance>' . "\r\n";
                $xml .= '      <InsTypeCode>' . $ins_code . '</InsTypeCode>' . "\r\n";
                $xml .= '      <InsTotal>' . number_format($ins_amount, 2, '.', '') . '</InsTotal>' . "\r\n";
                $xml .= '      <InsRoomBoard>0.00</InsRoomBoard>' . "\r\n";
                $xml .= '      <InsProfFee>0.00</InsProfFee>' . "\r\n";
                $xml .= '      <InsOther>' . number_format($ins_amount, 2, '.', '') . '</InsOther>' . "\r\n";
                $xml .= '    </Insurance>' . "\r\n";
            }
            
            $xml .= '  </Coinsurance>' . "\r\n";
            $xml .= '</CIPN>' . "\r\n";

            // Compute MD5 / HMAC signature
            $xml_main = substr($xml, strpos($xml, '<CIPN'));
            // Ensure $xml_main ends exactly at </CIPN> and we add exactly one empty line after it (which is \r\n\r\n)
            $xml_main_trimmed = rtrim($xml_main);
            $xml_main_encoded = iconv('UTF-8', 'windows-874//IGNORE', $xml_main_trimmed . "\r\n\r\n");
            $hmac = md5($xml_main_encoded);
            
            // Rebuild the final XML content ensuring exactly one empty line after </CIPN> and then the <?EndNote tag
            $xml = substr($xml, 0, strpos($xml, '<CIPN')) . $xml_main_trimmed . "\r\n\r\n" . '<?EndNote HMAC="' . $hmac . '" ?>';

            $xml_filename = "{$hcode}-AIPN-{$an}-{$subm_dt}.xml";
            $xml_files[$xml_filename] = $xml;

            if (empty($first_xml)) {
                $first_xml = $xml;
            }
        }

        return [
            'raw_xml' => $first_xml,
            'ipadt_data' => $ipadt_data,
            'ipdx_data' => $ipdx_data,
            'ipop_data' => $ipop_data,
            'billitems_data' => $billitems_data,
            'audit_results' => $audit_results,
            'xml_files' => $xml_files
        ];
    }

    /**
     * Inpatient AN Details for modal check
     */
    public function sss_an_details(Request $request)
    {
        $an = $request->input('an');
        
        $adm = DB::connection('hosxp')->table('an_stat as a')
            ->leftJoin('patient as pt', 'pt.hn', '=', 'a.hn')
            ->leftJoin('ipt as i', 'i.an', '=', 'a.an')
            ->leftJoin('ipt_pttype as ip', 'ip.an', '=', 'a.an')
            ->leftJoin('pttype as p', 'p.pttype', '=', 'ip.pttype')
            ->leftJoin('visit_pttype as vp', 'vp.vn', '=', 'i.vn')
            ->leftJoin('ward as w', 'w.ward', '=', 'a.ward')
            ->leftJoin('spclty as sp', 'sp.spclty', '=', 'a.spclty')
            ->leftJoin('doctor as doc', 'doc.code', '=', 'a.dx_doctor')
            ->select('a.an', 'a.hn', 'pt.pname', 'pt.fname', 'pt.lname', 'pt.cid', 'pt.birthday', 'pt.sex', 'pt.marrystatus as marry_status', 'pt.nationality',
                     'i.regdate', 'i.regtime', 'i.dchdate', 'i.dchtime', 'i.dchstts as dch_status', 'i.dchtype as dch_type',
                     'i.bw as weight', 'w.name as ward_name', 'sp.name as spclty_name', 'doc.name as doctor_name',
                     'p.name as pttype_name', 'a.pdx', 'a.income', 'a.rcpt_money',
                     DB::raw("COALESCE(NULLIF(ip.auth_code, ''), NULLIF(vp.auth_code, '')) as auth_code"))
            ->where('a.an', $an)
            ->first();

        if (!$adm) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูล AN นี้']);
        }

        $errors = [];
        $warnings = [];

        // 1. Check Authen
        if (empty($adm->auth_code)) {
            $errors[] = "ยังไม่มีเลขอนุมัติสิทธิ์ (Authen Code) หรือขอสิทธิ์ไม่สำเร็จ";
        }


        $validator = new \App\Services\ClaimValidator();

        // Fetch Diagnoses & Check PDX
        $diags = DB::connection('hosxp')->select("
            SELECT d.icd10, d.diagtype, doc.name AS doctor_name, d.entry_datetime,
                   COALESCE((SELECT name FROM icd101 WHERE code = d.icd10), '') as icd_name
            FROM iptdiag d
            LEFT JOIN doctor doc ON doc.code = d.doctor
            WHERE d.an = ?
            ORDER BY d.diagtype ASC, d.diag_no ASC
        ", [$an]);

        $has_pdx = false;
        foreach ($diags as $d) {
            $diagtype = $d->diagtype ?: '3';
            if ($diagtype == '1') {
                $has_pdx = true;
            }
            $icd10 = trim($d->icd10);
            $val_res = $validator->validateIcd10Chi($icd10, $diagtype);
            if (!$val_res['is_valid'] && !in_array(substr($icd10, 0, 2), ['U5', 'U6', 'U7'])) {
                $errors[] = "รหัสวินิจฉัยโรค {$icd10} ประเภท {$diagtype} ไม่ถูกต้องตามบัญชี สกส. (S54)";
            }
        }

        if (!$has_pdx) {
            $errors[] = "ไม่พบรหัสวินิจฉัยโรคหลัก (PDX)";
        }

        // Fetch Procedures & Check operation dates
        $procs = DB::connection('hosxp')->select("
            SELECT o.icd9, o.opdate, o.optime, o.enddate, o.endtime, doc.name AS doctor_name,
                   COALESCE((SELECT name FROM icd9cm1 WHERE code = o.icd9), '') as icd_name
            FROM iptoprt o
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.an = ?
            ORDER BY o.iptoprt_id ASC
        ", [$an]);

        foreach ($procs as $p) {
            if ($p->opdate < $adm->regdate || $p->opdate > $adm->dchdate) {
                $errors[] = "วันที่ทำหัตถการ {$p->opdate} ({$p->icd9}) อยู่นอกช่วงการนอนโรงพยาบาล (Error 251)";
            }
        }

        // Coinsurance / SSEM72 check
        $pttype_upp = DB::connection('hosxp')->table('ipt_pttype as ip')
            ->leftJoin('pttype as p', 'p.pttype', '=', 'ip.pttype')
            ->leftJoin('pttype_upp_type as pu', 'pu.pttype_upp_type_id', '=', 'p.pttype_upp_type_id')
            ->where('ip.an', $an)
            ->select('pu.pttype_upp_type_code as payplan')
            ->first();
        $upayplan = $pttype_upp->payplan ?? '80';

        $pttypes = DB::connection('hosxp')->select("
            SELECT ip.pttype, p.hipdata_code, p.name, s.cipn_instype_code
            FROM ipt_pttype ip
            LEFT JOIN pttype p ON p.pttype = ip.pttype
            LEFT JOIN sks_benefit_plan_type s ON s.sks_benefit_plan_type_id = p.sks_benefit_plan_type_id
            WHERE ip.an = ?
        ", [$an]);

        $has_ssem72 = false;
        foreach ($pttypes as $pt) {
            if ($pt->cipn_instype_code === 'SSEM72' || $pt->pttype === 'SSEM72' || strpos($pt->pttype, 'SSEM72') !== false || strpos($pt->name, 'SSEM72') !== false) {
                $has_ssem72 = true;
                break;
            }
        }

        if (in_array($upayplan, ['85', '95']) && !$has_ssem72) {
            $errors[] = "สิทธิหลัก (UPayPlan {$upayplan}) ต้องการสิทธิร่วมจ่าย Coinsurance SSEM72 (Error 369) กรุณาเพิ่มสิทธิร่วมให้ถูกต้อง";
        }

        // Fetch Charge Items & catalog validations
        $items = DB::connection('hosxp')->select("
            SELECT o.icode, SUM(o.qty) AS qty, SUM(o.sum_price) AS sum_price, MIN(o.unitprice) AS unitprice, SUM(o.discount) AS discount, MIN(o.income) AS income, inc.income_csmbs_code,
                   MIN(n.nhso_adp_code) AS nhso_adp_code,
                   COALESCE((SELECT name FROM drugitems WHERE icode = o.icode), (SELECT name FROM nondrugitems WHERE icode = o.icode)) AS item_name
            FROM opitemrece o
            LEFT JOIN income inc ON inc.income = o.income
            LEFT JOIN nondrugitems n ON n.icode = o.icode
            WHERE o.an = ?
            GROUP BY o.icode
            ORDER BY income ASC, icode ASC
        ", [$an]);

        foreach ($items as $item) {
            $qty = (float)$item->qty;
            $sum_price = (float)$item->sum_price;
            
            // Skip validation checks for items with price or quantity <= 0 (e.g., patient's own medicines)
            if ($sum_price <= 0 || $qty <= 0) {
                continue;
            }

            $billgr = '19';
            if (!empty($item->income_csmbs_code)) {
                $csmbs_code = trim($item->income_csmbs_code);
                if ($csmbs_code === '88') {
                    $billgr = '17';
                } else {
                    $billgr = str_pad($csmbs_code, 2, '0', STR_PAD_LEFT);
                }
            } else {
                $inc = str_pad($item->income, 2, '0', STR_PAD_LEFT);
                switch ($inc) {
                    case '01': $billgr = '01'; break;
                    case '02': $billgr = '02'; break;
                    case '10': $billgr = '10'; break;
                    case '03': $billgr = '03'; break;
                    case '04': $billgr = '04'; break;
                    case '05': $billgr = '05'; break;
                    case '06': $billgr = '06'; break;
                    case '07': $billgr = '07'; break;
                    case '08': $billgr = '08'; break;
                    case '09': $billgr = '09'; break;
                    case '16': $billgr = '12'; break;
                    case '12': $billgr = '16'; break;
                    case '13': $billgr = '14'; break;
                    case '14': $billgr = '13'; break;
                    case '15': $billgr = '15'; break;
                    case '11': $billgr = '11'; break;
                }
            }
            
            $unitprice = (float)$item->unitprice;

            if ($billgr === '02') {
                $std_adp_code = !empty($item->nhso_adp_code) ? trim($item->nhso_adp_code) : $item->icode;
                $equip = DB::table('lookup_sss_equipdev_aipn')->where('code', $std_adp_code)->first();
                if ($equip) {
                    if ($unitprice > $equip->rate) {
                        $errors[] = "รหัสอุปกรณ์ {$std_adp_code} (" . trim($item->item_name) . ") ราคาเรียกเก็บ (" . number_format($unitprice, 2) . ") เกินอัตราที่กำหนด (" . number_format($equip->rate, 2) . ") (Error 365)";
                    }
                }
            }

            if (in_array($billgr, ['03', '04'])) {
                $drug = DB::table('drugcat_chi')->where('hospdrugcode', $item->icode)->first();
                if ($drug) {
                    if (empty($drug->tmtid)) {
                        if ((int)$drug->productcat < 3) {
                            $errors[] = "รหัสยา {$item->icode} (" . trim($item->item_name) . ") ไม่มีรหัส TMTID/STDCode (Error 644)";
                        }
                    }
                } else {
                    if (str_starts_with(trim($item->icode), '1')) {
                        // Remap drug not in catalog to other services
                        $billgr = '17';
                        $errors[] = "รหัสยา {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Drug Catalog (Error 666)";
                    } else {
                        $errors[] = "รหัสยา {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Drug Catalog (Error 666)";
                    }
                }
            }

            if (in_array($billgr, ['06', '07'])) {
                $lab = DB::table('labcat_chi')->where('lccode', $item->icode)->orWhere('cscode', $item->icode)->first();
                if ($lab) {
                    if (empty($lab->tmlt)) {
                        $errors[] = "รหัสตรวจวิเคราะห์/โลหิต {$item->icode} (" . trim($item->item_name) . ") ไม่มีรหัส TMLT/STDCode (Error 644)";
                    }
                } else {
                    if (str_starts_with(trim($item->icode), '3')) {
                        // Remap lab not in catalog to other services
                        $billgr = '17';
                        $errors[] = "รหัสตรวจวิเคราะห์/โลหิต {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Lab Catalog (Error 661)";
                    } else {
                        $errors[] = "รหัสตรวจวิเคราะห์/โลหิต {$item->icode} (" . trim($item->item_name) . ") ไม่พบใน Lab Catalog (Error 661)";
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'admission' => $adm,
            'diags' => $diags,
            'procs' => $procs,
            'items' => $items,
            'errors' => $errors,
            'warnings' => $warnings
        ]);
    }

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
        return !empty($h_invoices) ? $h_invoices[0] : '';
    }

}