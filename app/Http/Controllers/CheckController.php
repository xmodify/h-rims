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

}
