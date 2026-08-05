<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\LicenseVerificationService;
use ZipArchive;

class CsopExportController extends Controller
{
    private function escape_xml($val)
    {
        if (empty($val)) return '';
        return str_replace(['&', '<', '>', '"', "'"], ['&amp;', '&lt;', '&gt;', '&quot;', '&apos;'], $val);
    }

    /**
     * Helper to generate raw CSOP data (raw XML strings and rows)
     */
    private function generate_csop_raw_data($vns, $sess_no, $station_id, $tflag = 'A')
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

        // Current timestamp formatted for CSOP
        $datetime = date('Y-m-d H:i:s');
        $datetime_iso = date('Y-m-d\TH:i:s');
        $date_suffix = date('Ymd');

        // Dynamic CSOP pttypes query
        $csop_pttypes = DB::connection('hosxp')
            ->table('pttype as p')
            ->join('sks_benefit_plan_type as sks', 'sks.sks_benefit_plan_type_id', '=', 'p.sks_benefit_plan_type_id')
            ->join('pttype_upp_type as put', 'put.pttype_upp_type_id', '=', 'p.pttype_upp_type_id')
            ->where('sks.sks_code', 'CS')
            ->where('put.pttype_upp_type_code', '31')
            ->pluck('p.pttype')
            ->toArray();

        if (empty($csop_pttypes)) {
            $csop_pttypes = [''];
        }
        $csop_pttypes_str = "'" . implode("','", array_map(function($x) { return str_replace("'", "\\'", $x); }, $csop_pttypes)) . "'";

        // Fetch visits
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
                WHERE vp2.vn = o.vn 
                  AND vp2.pttype IN ($csop_pttypes_str)
                LIMIT 1
            )
            LEFT JOIN pttype p ON p.pttype = o.pttype
            LEFT JOIN pttype_upp_type pu ON pu.pttype_upp_type_id = p.pttype_upp_type_id
            LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
            LEFT JOIN doctor doc ON doc.code = o.doctor
            WHERE o.vn IN ($visits_placeholders)
        ", $vns);
        $visits = collect($visits);

        $visits_map = $visits->keyBy('vn');

        // Query REP invoices (not strictly required for CSOP but kept for structure)
        $vns_list = $visits->pluck('vn')->toArray();
        $rep_invs_by_vn = [];

        // Query multiple invoices from rcpt_debt to map CSOP pttype invoice
        $csop_debt_map = [];
        if (!empty($vns_list)) {
            $debt_records = DB::connection('hosxp')
                ->table('rcpt_debt as rd')
                ->whereIn('rd.vn', $vns_list)
                ->select('rd.vn', 'rd.debt_id', 'rd.pttype')
                ->get();
            foreach ($debt_records as $r) {
                if (in_array($r->pttype, $csop_pttypes)) {
                    $csop_debt_map[$r->vn] = $r->debt_id;
                }
            }
        }

        // Fetch BillItems (with inc.income_csmbs_code join)
        $billitems_raw = DB::connection('hosxp')->select("
            SELECT op.vn, op.icode, op.qty, op.unitprice, op.sum_price, op.income, op.hos_guid, op.pttype,
                   sd.name AS drug_name, n.name AS nondrug_name,
                   inc.income_csmbs_code,
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
            LEFT JOIN income inc ON inc.income = op.income
            WHERE op.vn IN ($visits_placeholders)
        ", $vns);

        // 17 Groups CSOP Mapping function
        $map_income_to_csop_group = function($csmbs_code, $fallback_income) {
            if (empty($csmbs_code)) {
                $fallback_income = str_pad($fallback_income, 2, '0', STR_PAD_LEFT);
                switch ($fallback_income) {
                    case '01': return '1';
                    case '02': return '2';
                    case '03':
                    case '04':
                    case '17': return '3';
                    case '05': return '5';
                    case '06': return '6';
                    case '07': return '7';
                    case '08': return '8';
                    case '09': return '9';
                    case '10': return 'A';
                    case '11': return 'B';
                    case '12':
                    case '18': return 'C';
                    case '13': return 'D';
                    case '14': return 'E';
                    case '15': return 'F';
                    case '16': return 'H';
                    default: return 'G';
                }
            }
            switch (trim($csmbs_code)) {
                case '01': return '1';
                case '02': return '2';
                case '03':
                case '04': return '3';
                case '05': return '5';
                case '06': return '6';
                case '07': return '7';
                case '08': return '8';
                case '09': return '9';
                case '10': return 'A';
                case '11': return 'B';
                case '12': return 'C';
                case '13': return 'D';
                case '14': return 'E';
                case '15': return 'F';
                case '16': return 'H';
                case '17': return 'I';
                default: return 'G';
            }
        };

        $billitems_rows = [];
        $billitems_by_vn = [];
        foreach ($billitems_raw as $item) {
            $billitems_by_vn[$item->vn][] = $item;
        }

        $item_claim_map = [];
        $billtran_rows = [];
        foreach ($visits as $row) {
            $raw_invo = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($row->vn, $raw_invo, [], $csop_debt_map);
            $sub_id = !empty($row->sss_billno) ? $row->sss_billno : '';
            $ptname = $this->escape_xml(trim($row->pname . $row->fname . ' ' . $row->lname));
            $payplan = !empty($row->payplan) ? trim($row->payplan) : '31';
            $paid_val = (float)($row->sss_paid_amount ?: 0.0);
            $paid = number_format($paid_val, 2, '.', '');
            
            $visit_items = $billitems_by_vn[$row->vn] ?? [];
            $total_charge = 0.0;
            $total_claim = 0.0;
            
            $paid_to_deduct = $paid_val;
            
            foreach ($visit_items as $item) {
                $billgr = $map_income_to_csop_group($item->income_csmbs_code, $item->income);
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
                
                if ($billgr === '3' || $billgr === '5') {
                    $rx_no = !empty($item->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $item->hos_guid), 0, 9) : $item->vn;
                    if (empty($rx_no)) $rx_no = $item->vn;
                    $disp_id = "{$rx_no}_{$invoice_no}";
                } else {
                    $disp_id = $item->vn;
                }

                $tmtid = !empty($item->tmtid) ? $item->tmtid : '';
                $billitems_rows[] = "{$invoice_no}|{$row->vstdate}|{$billgr}|{$item->icode}|{$tmtid}|{$name}|{$qty}|{$unitprice}|{$sum_charge}|{$sum_claim_up}|{$sum_claim}|{$disp_id}|OP1";
            }

            $income = number_format($total_charge, 2, '.', '');
            $claim = number_format($total_claim, 2, '.', '');
            
            $dttran = date('Y-m-d\TH:i:s', strtotime("{$row->vstdate} {$row->vsttime}"));
            $billtran_rows[] = "01||{$dttran}|{$hcode}|{$invoice_no}|{$sub_id}|{$row->hn}||{$income}|{$paid}||{$tflag}|{$row->cid}|{$ptname}|{$row->hospmain}|{$payplan}|{$claim}||0.00";
        }

        $billtran_count = count($billtran_rows);
        $billtran_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\r\n" .
            '<ClaimRec System="OP" PayPlan="CS" Version="0.93">' . "\r\n" .
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

        // Checksum MD5
        $billtran_tis = iconv('UTF-8', 'TIS-620//IGNORE', $billtran_xml);
        $billtran_md5 = strtoupper(md5($billtran_tis));
        $billtran_xml .= '<?EndNote Checksum="' . $billtran_md5 . '"?>' . "\r\n";

        // 2. Generate BILLDISP & DispensedItems content
        $billdisp_rows = [];
        $dispensed_rows = [];
        $disp_sessions = [];

        // Fetch drug and supply items
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

            $raw_invo = !empty($v->sss_invno) ? $v->sss_invno : (!empty($v->debt_id_list) ? $v->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($v->vn, $raw_invo, [], $csop_debt_map);
            $rx_no = !empty($item->hos_guid) ? substr(preg_replace('/[^0-9]/', '', $item->hos_guid), 0, 9) : $v->vn;
            if (empty($rx_no)) $rx_no = $v->vn;
            $disp_id = "{$rx_no}_{$invoice_no}";

            // Group Dispensing rows
            if (!isset($disp_sessions[$disp_id])) {
                $disp_date = date('Y-m-d\TH:i:s', strtotime("{$v->vstdate} {$v->vsttime}"));
                $rxtime_val = !empty($item->rxtime) ? $item->rxtime : date('H:i:s', strtotime($v->vsttime . ' + 30 minutes'));
                $end_date = date('Y-m-d\TH:i:s', strtotime("{$v->vstdate} {$rxtime_val}"));
                $license = !empty($v->rx_license_no) ? $v->rx_license_no : (!empty($v->doctor_license) ? $v->doctor_license : '-');
                
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
                
                // CSOP Dispensing: PayPlan CS instead of SS
                $billdisp_rows[] = "{$hcode}|{$disp_id}|{$invoice_no}|{$v->hn}|{$v->cid}|{$disp_date}|{$end_date}|{$license}|{$session_count}|{$total_amt_session}|{$total_claim_session}|{$total_paid_session_str}|0.00|HP|CS|1|{$v->vn}|";
                $disp_sessions[$disp_id] = true;
            }

            $prdcat = !empty($item->sks_product_category_id) ? $item->sks_product_category_id : '';
            if (str_starts_with($item->icode, '3')) {
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
            
            $prdcat_int = intval($prdcat);
            if ($prdcat_int >= 1 && $prdcat_int <= 5) {
                if (empty($sigtext)) {
                    $sigtext = 'ตามแพทย์สั่ง';
                }
            }
            
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
            '<ClaimRec System="OP" PayPlan="CS" Version="0.93">' . "\r\n" .
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

        $billdisp_tis = iconv('UTF-8', 'TIS-620//IGNORE', $billdisp_xml);
        $billdisp_md5 = strtoupper(md5($billdisp_tis));
        $billdisp_xml .= '<?EndNote Checksum="' . $billdisp_md5 . '"?>' . "\r\n";

        // 3. Generate OPServices & OPDx content
        $opservices_rows = [];
        $opdx_rows = [];

        foreach ($visits as $row) {
            $raw_invo = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
            $invoice_no = $this->resolve_invoice_no($row->vn, $raw_invo, [], $csop_debt_map);
            $start_dt = date('Y-m-d\TH:i:s', strtotime("{$row->vstdate} {$row->vsttime}"));
            $end_dt = date('Y-m-d\TH:i:s', strtotime("{$row->vstdate} {$row->vsttime} + 2 hours"));
            
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
            // Default ClaimCat to OP1
            $opservices_rows[] = "{$invoice_no}|{$row->vn}|EC|{$hcode}|{$row->hn}|{$row->cid}|1|01|9|9||{$doc_license}|99|{$start_dt}|{$end_dt}||||0.00|Y||OP1";
        }

        $opservices_count = count($opservices_rows);
        $opservices_xml = '<?xml version="1.0" encoding="windows-874"?>' . "\r\n" .
            '<ClaimRec System="OP" PayPlan="CS" Version="0.93">' . "\r\n" .
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

        $opservices_tis = iconv('UTF-8', 'TIS-620//IGNORE', $opservices_xml);
        $opservices_md5 = strtoupper(md5($opservices_tis));
        $opservices_xml .= '<?EndNote Checksum="' . $opservices_md5 . '"?>' . "\r\n";

        return [
            'hcode' => $hcode,
            'date_suffix' => $date_suffix,
            'billtran_xml' => $billtran_xml,
            'billdisp_xml' => $billdisp_xml,
            'opservices_xml' => $opservices_xml,
            'billtran_rows' => $billtran_rows,
            'billitems_rows' => $billitems_rows,
            'billdisp_rows' => $billdisp_rows,
            'dispensed_rows' => $dispensed_rows,
            'opservices_rows' => $opservices_rows,
            'opdx_rows' => $opdx_rows
        ];
    }

    /**
     * Preview XML structures and validation list
     */
    public function csop_export_preview(Request $request)
    {
        $vns = $request->input('vns', []);
        if (empty($vns)) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ VN ที่เลือก'], 400);
        }

        $sess_no = $request->input('session_id') ?: rand(1000, 9999);
        $station_id = $request->input('station_id') ?: '01';
        $tflag = $request->input('tflag') ?: 'A';

        $data = $this->generate_csop_raw_data($vns, $sess_no, $station_id, $tflag);

        // Map arrays to visual table formats
        $billtran_table = [];
        foreach ($data['billtran_rows'] as $idx => $row) {
            $fields = explode('|', $row);
            $billtran_table[] = $fields;
        }

        $billitems_table = [];
        foreach ($data['billitems_rows'] as $row) {
            $billitems_table[] = explode('|', $row);
        }

        $billdisp_table = [];
        foreach ($data['billdisp_rows'] as $row) {
            $billdisp_table[] = explode('|', $row);
        }

        $dispenseditems_table = [];
        foreach ($data['dispensed_rows'] as $row) {
            $dispenseditems_table[] = explode('|', $row);
        }

        $opservices_table = [];
        foreach ($data['opservices_rows'] as $row) {
            $opservices_table[] = explode('|', $row);
        }

        $opdx_table = [];
        foreach ($data['opdx_rows'] as $row) {
            $opdx_table[] = explode('|', $row);
        }

        // Basic Pre-Audit audit
        $validation = [];
        $vns_placeholders = implode(',', array_fill(0, count($vns), '?'));
        $visits_info = DB::connection('hosxp')->select("
            SELECT o.vn, o.hn, pt.pname, pt.fname, pt.lname, pt.cid, o.vstdate
            FROM ovst o
            LEFT JOIN patient pt ON pt.hn = o.hn
            WHERE o.vn IN ($vns_placeholders)
        ", $vns);

        foreach ($visits_info as $row) {
            $errors = ['billtran' => [], 'billdisp' => [], 'opservices' => []];
            
            // Match OPSERVICE row by VN
            $op_row = null;
            foreach ($opservices_table as $os) {
                if (isset($os[1]) && $os[1] === $row->vn) {
                    $op_row = $os;
                    break;
                }
            }
            $invoice_no = $op_row[0] ?? '';

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
            } elseif ($invoice_no === $row->vn) {
                $errors['billtran'][] = "เลขใบแจ้งหนี้ใช้เลข VN (ยังไม่ได้ออกใบแจ้งหนี้)";
            }
            if (empty($row->cid) || strlen(trim($row->cid)) !== 13) {
                $errors['billtran'][] = "เลขบัตรประชาชน (CID) ว่างหรือความยาวไม่ครบ 13 หลัก";
            }
            if (empty($row->hn)) {
                $errors['billtran'][] = "ไม่พบ HN";
            }
            $vn_claim_sum = $bt_row ? (float)($bt_row[16] ?? 0.0) : 0.0;
            if ($vn_claim_sum <= 0.0) {
                $errors['billtran'][] = "ยอดเงินเรียกเก็บ (ClaimAmt) ต้องมากกว่า 0";
            }

            // 2. BILLDISP checks (Only if dispensing items exist)
            $has_dispense = false;
            foreach ($dispenseditems_table as $d_row) {
                if (isset($d_row[0]) && str_contains($d_row[0], $row->vn)) {
                    $has_dispense = true;
                    break;
                }
            }
            if ($has_dispense) {
                // Verify Dispensing details
                $disp_row = null;
                foreach ($billdisp_table as $bd) {
                    if (isset($bd[16]) && $bd[16] === $row->vn) {
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

            // 3. OPSERVICE checks
            if ($op_row) {
                $lic = trim($op_row[11] ?? '');
                $doc_name = !empty($row->doctor_name) ? trim($row->doctor_name) : 'ไม่ระบุชื่อแพทย์';
                if (empty($lic)) {
                    $errors['opservices'][] = "ไม่พบเลขใบอนุญาตประกอบวิชาชีพเวชกรรมผู้สั่งตรวจรักษา (แพทย์ผู้รักษา: {$doc_name})";
                } else {
                    $is_valid_format = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
                    if (!$is_valid_format) {
                        $errors['opservices'][] = "เลขใบประกอบวิชาชีพแพทย์ '{$lic}' มีรูปแบบไม่ถูกต้อง (แพทย์ผู้รักษา: {$doc_name}) (ต้องขึ้นต้นด้วย ว, ท, ภ, พ หรือ - และตามด้วยตัวเลขเท่านั้น) (S15)";
                    }
                }
            }

            // 4. OPDX / ICD-10 (S54) checks
            $pdx_code = null;
            foreach ($opdx_table as $dx) {
                if (isset($dx[1]) && $dx[1] === $row->vn && isset($dx[2]) && $dx[2] === '1') {
                    $pdx_code = trim($dx[4] ?? '');
                    break;
                }
            }
            if (empty($pdx_code)) {
                $errors['opservices'][] = "ไม่พบรหัสวินิจฉัยโรคหลัก (PDX)";
            } else {
                $validator = new \App\Services\ClaimValidator();
                $res = $validator->validateIcd10Chi($pdx_code, '1');
                if (!$res['is_valid']) {
                    $errors['opservices'][] = "รหัสวินิจฉัยหลัก {$pdx_code} ไม่ถูกต้องตามบัญชี สกส. (S54)";
                }
            }

            // 5. CSOP rules (T72, T78, T84) checks
            $has_op_service_fee = false;
            $has_room_fee = false;
            $other_groups = [];

            foreach ($billitems_table as $item_row) {
                if (isset($item_row[0]) && $item_row[0] === $invoice_no) {
                    $muad = $item_row[2] ?? '';
                    $std_code = trim($item_row[4] ?? '');
                    
                    if ($std_code === '55020' || $std_code === '55021') {
                        $has_op_service_fee = true;
                        continue;
                    }
                    
                    if ($muad === '2') {
                        $has_room_fee = true;
                    }
                    $other_groups[$muad] = true;
                }
            }

            if ($has_op_service_fee) {
                // Rule T72
                if ($has_room_fee) {
                    $errors['opservices'][] = "เบิกค่าบริการผู้ป่วยนอกร่วมกับค่าเตียงสังเกตอาการ (T72)";
                }

                // Rule T78
                if (count($other_groups) === 1 && isset($other_groups['F'])) {
                    $errors['opservices'][] = "เบิกค่าบริการผู้ป่วยนอกร่วมกับบริการฝังเข็มหมวด 15 เท่านั้น (T78)";
                }

                // Rule T84
                $allowed_t84_groups = ['8', 'B', 'E', 'F'];
                $only_t84_groups = true;
                $has_any_item = !empty($other_groups);

                foreach (array_keys($other_groups) as $g) {
                    if (!in_array($g, $allowed_t84_groups)) {
                        $only_t84_groups = false;
                    }
                }

                $has_doctor = false;
                if ($op_row) {
                    $lic = strtoupper(trim($op_row[11] ?? ''));
                    if (str_starts_with($lic, 'ว')) {
                        $has_doctor = true;
                    }
                }

                if ($has_any_item && $only_t84_groups && !$has_doctor) {
                    $errors['opservices'][] = "เบิกค่าบริการผู้ป่วยนอกโดยไม่มีการพบแพทย์ (T84)";
                }
            }

            $validation[] = [
                'vn' => $row->vn,
                'hn' => $row->hn,
                'name' => trim($row->pname . $row->fname . ' ' . $row->lname),
                'vstdate' => $row->vstdate,
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
     * Download Zip file containing BILLTRAN, BILLDISP, OPServices
     */
    public function csop_export(Request $request)
    {
        $vns = $request->input('vns', []);
        if (empty($vns)) {
            return redirect()->back()->with('error', 'กรุณาเลือกรายการที่ต้องการส่งออก');
        }

        $sess_no = $request->input('session_id') ?: rand(1000, 9999);
        $station_id = $request->input('station_id') ?: '01';
        $tflag = $request->input('tflag') ?: 'A';

        $data = $this->generate_csop_raw_data($vns, $sess_no, $station_id, $tflag);

        $billtran_encoded = iconv('UTF-8', 'TIS-620//IGNORE', $data['billtran_xml']);
        $billdisp_encoded = iconv('UTF-8', 'TIS-620//IGNORE', $data['billdisp_xml']);
        $opservices_encoded = iconv('UTF-8', 'TIS-620//IGNORE', $data['opservices_xml']);

        // CSOP naming convention for Zip: CSOPBIL
        $zip_name = "{$data['hcode']}_CSOPBIL_{$sess_no}_{$station_id}_" . date('Ymd-His') . ".zip";
        $temp_dir = storage_path('app/temp_csop');
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

    private function resolve_invoice_no($vn, $raw_invo, $rep_invs_by_vn = [], $csop_debt_map = [])
    {
        if (isset($csop_debt_map[$vn])) {
            return (string)$csop_debt_map[$vn];
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
