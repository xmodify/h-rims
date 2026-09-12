<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Ai\AiService;
use App\Services\Ai\Context\HosxpContextService;
use App\Services\Ai\RagSearchService;

class HosxpSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to access HOSxP Setting / ข้อมูลพื้นฐาน
     */
    protected function checkPermission()
    {
        if (!auth()->check()) {
            abort(403, 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }
        $user = auth()->user();
        if ($user->status !== 'admin' && ($user->allow_emr ?? 'N') !== 'Y') {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงระบบข้อมูลพื้นฐาน HOSxP');
        }
    }

    /**
     * Main Hub View
     */
    public function index(Request $request)
    {
        $this->checkPermission();

        $activeTab = $request->input('tab', 'doctor');
        $search = trim($request->input('search', ''));
        $filter = $request->input('filter', ($activeTab === 'pttype' ? 'active' : 'all'));

        $hosxpAlive = false;
        try {
            $test = DB::connection('hosxp')->select('SELECT 1 as alive');
            $hosxpAlive = !empty($test);
        } catch (\Throwable $e) {
            $hosxpAlive = false;
        }

        if (!$hosxpAlive) {
            return view('emr.hosxp_setting.index', [
                'hosxpAlive' => false,
                'activeTab' => $activeTab,
                'stats' => [],
                'records' => collect(),
                'search' => $search,
                'filter' => $filter,
                'errorMessage' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล HOSxP ได้ กรุณาตรวจสอบการตั้งค่า DB_HOST_HOSXP ใน .env'
            ]);
        }

        $hosxp = DB::connection('hosxp');

        // ==========================================
        // 1. Calculate Summary Stats for KPI Cards
        // ==========================================
        $activeDocs = [];
        $inactiveDocs = [];
        $invalidLicenseDocs = [];
        $invalidCidDocs = [];
        $doctorTabConfigs = [];

        if ($activeTab === 'doctor') {
            $doctors = $hosxp->select('
                SELECT d.code, d.name, d.licenseno, d.cid, d.active, d.council_code, d.provider_type_code, d.sex, d.birth_date,
                       dp.name AS position_name, s.name AS spclty_name, c.name AS clinic_name
                FROM doctor d
                LEFT JOIN doctor_position dp ON dp.id = d.position_id
                LEFT JOIN spclty s ON s.spclty = d.spclty
                LEFT JOIN clinic c ON c.clinic = d.clinic
                ORDER BY d.active DESC, d.name ASC
            ');

            foreach ($doctors as $doc) {
                $lic = trim($doc->licenseno ?? '');
                $isLicValid = (bool) preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);

                $cid = trim($doc->cid ?? '');
                $isCidValid = (!empty($cid) && strlen($cid) === 13);

                $doc_errors = [];
                if (empty($lic)) {
                    $doc_errors[] = 'ไม่มีเลขใบประกอบวิชาชีพ';
                } elseif (!$isLicValid) {
                    $doc_errors[] = "เลขใบประกอบฯ '{$lic}' รูปแบบไม่ถูกต้อง (ต้องขึ้นต้นด้วย ว, ท, ภ, พ หรือ - และตามด้วยตัวเลข)";
                }

                if (empty($cid)) {
                    $doc_errors[] = 'ไม่มีเลขบัตรประชาชน';
                } elseif (!$isCidValid) {
                    $doc_errors[] = 'เลขบัตรประชาชนต้องยาว 13 หลัก';
                }

                $doc->doc_errors = $doc_errors;
                $doc->is_lic_valid = $isLicValid;
                $doc->is_cid_valid = $isCidValid;

                if ($doc->active === 'Y') {
                    $activeDocs[] = $doc;
                } else {
                    $inactiveDocs[] = $doc;
                }

                if (empty($lic) || !$isLicValid) {
                    $invalidLicenseDocs[] = $doc;
                }

                if (empty($cid) || !$isCidValid) {
                    $invalidCidDocs[] = $doc;
                }
            }

            $stats['doctor'] = [
                'total' => count($doctors),
                'active' => count($activeDocs),
                'inactive' => count($inactiveDocs),
                'invalid_license' => count($invalidLicenseDocs),
                'invalid_cid' => count($invalidCidDocs),
            ];

            $doctorTabConfigs = [
                [
                    'id' => 'active-tab-pane',
                    'active' => true,
                    'title' => 'รายชื่อแพทย์ที่เปิดใช้งาน',
                    'data' => $activeDocs,
                    'table_id' => 'table-active'
                ],
                [
                    'id' => 'inactive-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่ปิดใช้งาน',
                    'data' => $inactiveDocs,
                    'table_id' => 'table-inactive'
                ],
                [
                    'id' => 'invalid-license-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่เลขใบประกอบวิชาชีพไม่ถูกต้อง',
                    'data' => $invalidLicenseDocs,
                    'table_id' => 'table-invalid-license'
                ],
                [
                    'id' => 'invalid-cid-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่เลขบัตรประชาชนไม่ถูกต้อง',
                    'data' => $invalidCidDocs,
                    'table_id' => 'table-invalid-cid'
                ]
            ];
        } else {
            $docTotal = $hosxp->table('doctor')->count();
            $docActive = $hosxp->table('doctor')->where('active', 'Y')->count();
            $stats['doctor'] = [
                'total' => $docTotal,
                'active' => $docActive,
                'inactive' => $docTotal - $docActive,
                'invalid_license' => 0,
                'invalid_cid' => 0,
            ];
        }

        $itemTotal = $hosxp->table('nondrugitems')->where('price', '>', 0)->count();
        $itemActive = $hosxp->table('nondrugitems')->where('istatus', 'Y')->where('price', '>', 0)->count();
        $itemMissingAdp = $hosxp->table('nondrugitems')->where('istatus', 'Y')->where('price', '>', 0)
            ->where(function ($q) {
                $q->whereNull('nhso_adp_code')->orWhere('nhso_adp_code', '');
            })->count();

        $stats['nondrugitems'] = [
            'total' => $itemTotal,
            'active' => $itemActive,
            'missing_adp' => $itemMissingAdp,
            'mapped_adp' => $itemActive - $itemMissingAdp,
            'health_rate' => $itemActive > 0 ? round((($itemActive - $itemMissingAdp) / $itemActive) * 100, 1) : 100,
        ];

        // Stats for pttype
        $validHipdataCodes = [
            'UCS', 'WEL', 'OFC', 'LGO', 'SSS', 'STP', 'NHS', 'BKK', 'BMT', 'SRT', 'KKT', 'PTY',
            'A1', 'CSH', 'A9', 'INS', 'GOF', 'NRD', 'NRH', 'SSI', 'PVT', 'FWF'
        ];

        try {
            $pttypeAllRows = $hosxp->select('
                SELECT p.pttype, inscl.nhso_subinscl, p.`name`, CONCAT(p1.paidst, SPACE(1), p1.`name`) AS paidst,
                       p.export_eclaim, p.hipdata_code, p.pttype_std_code, p.isuse, p.pcode, p.nhso_code,
                       CONCAT(pi.`code`, SPACE(1), pi.`name`) AS pi_name, pi.pttype_std_code AS pi_pttype_std_code,
                       pg.pttype_price_group_name
                FROM pttype p
                LEFT JOIN paidst p1 ON p1.paidst = p.paidst
                LEFT JOIN pttype_price_group pg ON pg.pttype_price_group_id = p.pttype_price_group_id
                LEFT JOIN provis_instype pi ON pi.`code` = p.nhso_code
                LEFT JOIN (
                    SELECT pttype, GROUP_CONCAT(nhso_subinscl ORDER BY nhso_subinscl SEPARATOR ", ") AS nhso_subinscl
                    FROM pttype_nhso_subinscl
                    GROUP BY pttype
                ) inscl ON inscl.pttype = p.pttype
                ORDER BY p.isuse DESC, p.hipdata_code ASC, p.pttype ASC
            ');

            $ptTotal = count($pttypeAllRows);
            $ptActiveRows = array_filter($pttypeAllRows, fn($r) => ($r->isuse ?? '') === 'Y');
            $ptActive = count($ptActiveRows);
            $ptInactive = $ptTotal - $ptActive;
            
            $ptActiveInvalid = 0;
            foreach ($ptActiveRows as $r) {
                $hasErr = false;
                if (empty($r->pi_name)) {
                    $hasErr = true;
                } elseif (empty($r->pttype_std_code)) {
                    $hasErr = true;
                } elseif (strtoupper(trim($r->hipdata_code ?? '')) === 'UCS' && $r->pttype_std_code !== '0100') {
                    $hasErr = true;
                } elseif ($r->pttype_std_code !== $r->pi_pttype_std_code) {
                    $hasErr = true;
                } elseif (empty($r->hipdata_code) || !in_array(strtoupper(trim($r->hipdata_code)), $validHipdataCodes)) {
                    $hasErr = true;
                }
                if ($hasErr) {
                    $ptActiveInvalid++;
                }
            }
            $ptActiveValid = $ptActive - $ptActiveInvalid;

            $stats['pttype'] = [
                'total' => $ptTotal,
                'active' => $ptActive,
                'inactive' => $ptInactive,
                'invalid' => $ptActiveInvalid,
                'valid' => $ptActiveValid,
                'health_rate' => $ptActive > 0 ? round(($ptActiveValid / $ptActive) * 100, 1) : 100,
            ];
        } catch (\Throwable $e) {
            $stats['pttype'] = [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'invalid' => 0,
                'valid' => 0,
                'health_rate' => 100,
            ];
        }

        // Stats for nhso_subinscl
        try {
            $subinsclRows = $hosxp->select('
                SELECT s.code, p.pttype 
                FROM hrims.subinscl s
                LEFT JOIN pttype p ON p.pttype = s.`code`
            ');
            $subTotal = count($subinsclRows);
            $subFound = count(array_filter($subinsclRows, fn($r) => !is_null($r->pttype)));
            $subNotfound = $subTotal - $subFound;
            $stats['nhso_subinscl'] = [
                'total' => $subTotal,
                'found' => $subFound,
                'notfound' => $subNotfound,
                'match_rate' => $subTotal > 0 ? round(($subFound / $subTotal) * 100, 1) : 0,
            ];
        } catch (\Throwable $e) {
            $stats['nhso_subinscl'] = [
                'total' => 0,
                'found' => 0,
                'notfound' => 0,
                'match_rate' => 0,
            ];
        }

        // Stats for drug
        try {
            $drugTotal = $hosxp->table('drugitems')->count();
            $drugActive = $hosxp->table('drugitems')->where('istatus', 'Y')->count();
            $drugMissing24 = $hosxp->select("
                SELECT COUNT(*) as c FROM drugitems d
                LEFT JOIN drugitems_ref_code r ON r.icode = d.icode AND r.drugitems_ref_code_type_id = 1
                WHERE d.istatus = 'Y' AND (d.did IS NULL OR LENGTH(TRIM(d.did)) < 24) AND (r.ref_code IS NULL OR LENGTH(TRIM(r.ref_code)) < 24)
            ")[0]->c ?? 0;
            $drugMissingTmt = $hosxp->select("
                SELECT COUNT(*) as c FROM drugitems d
                LEFT JOIN drugitems_ref_code r ON r.icode = d.icode AND r.drugitems_ref_code_type_id = 3
                WHERE d.istatus = 'Y' AND (d.tmt_tp_code IS NULL OR d.tmt_tp_code = '') AND (r.ref_code IS NULL OR r.ref_code = '')
            ")[0]->c ?? 0;

            $stats['drug'] = [
                'total' => $drugTotal,
                'active' => $drugActive,
                'inactive' => $drugTotal - $drugActive,
                'missing_24' => $drugMissing24,
                'missing_tmt' => $drugMissingTmt,
                'health_rate' => $drugActive > 0 ? round((($drugActive - $drugMissing24) / $drugActive) * 100, 1) : 100,
            ];
        } catch (\Throwable $e) {
            $stats['drug'] = ['total' => 0, 'active' => 0, 'inactive' => 0, 'missing_24' => 0, 'missing_tmt' => 0, 'health_rate' => 100];
        }

        // Stats for lab
        try {
            $labTotal = $hosxp->table('lab_items')->count();
            $labActive = $hosxp->table('lab_items')->where('active_status', 'Y')->count();
            $labUnmapped = $hosxp->select("
                SELECT COUNT(*) as c FROM lab_items l
                LEFT JOIN nondrugitems n ON n.icode = l.icode
                WHERE l.active_status = 'Y' AND (l.icode IS NULL OR l.icode = '' OR n.icode IS NULL)
            ")[0]->c ?? 0;
            $labMissingTmlt = $hosxp->table('lab_items')->where('active_status', 'Y')->where(function($q) {
                $q->whereNull('tmlt_code')->orWhere('tmlt_code', '');
            })->count();
            $labProfileTotal = $hosxp->table('lab_items_sub_group')->count();
            $labProfileUnmapped = $hosxp->select("
                SELECT COUNT(*) as c FROM lab_items_sub_group sg
                LEFT JOIN nondrugitems n ON n.icode = sg.group_icode
                WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NULL OR sg.group_icode = '' OR n.icode IS NULL)
            ")[0]->c ?? 0;

            $stats['lab'] = [
                'total_items' => $labTotal,
                'active_items' => $labActive,
                'unmapped_items' => $labUnmapped,
                'missing_tmlt' => $labMissingTmlt,
                'total_profiles' => $labProfileTotal,
                'unmapped_profiles' => $labProfileUnmapped,
                'health_rate' => $labActive > 0 ? round((($labActive - $labUnmapped) / $labActive) * 100, 1) : 100,
            ];
        } catch (\Throwable $e) {
            $stats['lab'] = ['total_items' => 0, 'active_items' => 0, 'unmapped_items' => 0, 'missing_tmlt' => 0, 'total_profiles' => 0, 'unmapped_profiles' => 0, 'health_rate' => 100];
        }

        // ==========================================
        // 2. Query Tab Records with Pagination & Search
        // ==========================================
        $records = collect();
        $subTab = $request->input('subtab', 'items');

        if ($activeTab === 'doctor') {
            // Handled via $doctorTabConfigs and client-side DataTables matching check/doctor
        } elseif ($activeTab === 'nondrugitems') {
            $query = $hosxp->table('nondrugitems as n')
                ->leftJoin('income as i', 'n.income', '=', 'i.income')
                ->leftJoin('paidst as p', 'n.paidst', '=', 'p.paidst')
                ->select([
                    'n.icode', 'n.name', 'n.price', 'n.nhso_adp_code',
                    'n.nhso_adp_type_id', 'n.istatus', 'n.unit',
                    'n.income', 'n.billcode', 'n.paidst',
                    'i.name as income_name',
                    'p.name as paidst_name'
                ])
                ->where('n.price', '>', 0);

            if ($filter === 'active') {
                $query->where('n.istatus', 'Y');
            } elseif ($filter === 'inactive') {
                $query->where(function ($q) {
                    $q->whereNull('n.istatus')->orWhere('n.istatus', '!=', 'Y');
                });
            } elseif ($filter === 'missing_adp') {
                $query->where('n.istatus', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('n.nhso_adp_code')->orWhere('n.nhso_adp_code', '');
                    });
            } elseif ($filter === 'mapped_adp') {
                $query->where('n.istatus', 'Y')
                    ->whereNotNull('n.nhso_adp_code')
                    ->where('n.nhso_adp_code', '!=', '');
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('n.name', 'like', "%{$search}%")
                      ->orWhere('n.icode', 'like', "%{$search}%")
                      ->orWhere('n.nhso_adp_code', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('n.istatus', 'desc')
                ->orderBy('n.icode', 'asc')
                ->get();

            // Validate each nondrugitem record
            foreach ($records as $item) {
                $itemErrors = [];
                if (empty(trim($item->nhso_adp_code ?? ''))) {
                    $itemErrors[] = 'ยังไม่ผูกรหัส ADP';
                }
                if (empty(trim($item->income ?? ''))) {
                    $itemErrors[] = 'ยังไม่ระบุหมวดรายได้ (income)';
                }
                $item->item_errors = $itemErrors;
                $item->is_valid = empty($itemErrors);
            }

        } elseif ($activeTab === 'pttype') {
            $validHipdataCodes = [
                'UCS', 'WEL', 'OFC', 'LGO', 'SSS', 'STP', 'NHS', 'BKK', 'BMT', 'SRT', 'KKT', 'PTY',
                'A1', 'CSH', 'A9', 'INS', 'GOF', 'NRD', 'NRH', 'SSI', 'PVT', 'FWF'
            ];

            $queryRows = $hosxp->select('
                SELECT p.pttype, inscl.nhso_subinscl, p.`name`, CONCAT(p1.paidst, SPACE(1), p1.`name`) AS paidst,
                       p.export_eclaim, p.hipdata_code, p.pttype_std_code, p.isuse, p.pcode, p.nhso_code,
                       CONCAT(pi.`code`, SPACE(1), pi.`name`) AS pi_name, pi.pttype_std_code AS pi_pttype_std_code,
                       pg.pttype_price_group_name
                FROM pttype p
                LEFT JOIN paidst p1 ON p1.paidst = p.paidst
                LEFT JOIN pttype_price_group pg ON pg.pttype_price_group_id = p.pttype_price_group_id
                LEFT JOIN provis_instype pi ON pi.`code` = p.nhso_code
                LEFT JOIN (
                    SELECT pttype, GROUP_CONCAT(nhso_subinscl ORDER BY nhso_subinscl SEPARATOR ", ") AS nhso_subinscl
                    FROM pttype_nhso_subinscl
                    GROUP BY pttype
                ) inscl ON inscl.pttype = p.pttype
                ORDER BY p.isuse DESC, p.hipdata_code ASC, p.pttype ASC
            ');

            foreach ($queryRows as $pt) {
                $ptErrors = [];
                $statusType = 'valid';
                $statusText = 'ปกติ';

                if (empty($pt->pi_name)) {
                    $ptErrors[] = 'ไม่ได้เชื่อมรหัสมาตรฐาน (nhso_code)';
                    $statusType = 'danger';
                    $statusText = 'ไม่ได้เชื่อมรหัสมาตรฐาน (nhso_code)';
                } elseif (empty($pt->pttype_std_code)) {
                    $ptErrors[] = 'ไม่ได้ระบุรหัสส่งออกใน HOSxP';
                    $statusType = 'danger';
                    $statusText = 'ไม่ได้ระบุรหัสส่งออกใน HOSxP';
                } elseif (strtoupper(trim($pt->hipdata_code ?? '')) === 'UCS' && $pt->pttype_std_code !== '0100') {
                    $ptErrors[] = 'สิทธิหลักประกันสุขภาพ (UCS) รหัสส่งออกต้องเป็น 0100';
                    $statusType = 'danger';
                    $statusText = 'รหัสส่งออกต้องเป็น 0100';
                } elseif ($pt->pttype_std_code !== $pt->pi_pttype_std_code) {
                    $ptErrors[] = 'รหัสส่งออกไม่ตรงกัน (HOSxP: ' . ($pt->pttype_std_code ?: '-') . ' != PROVIS: ' . ($pt->pi_pttype_std_code ?: '-') . ')';
                    $statusType = 'danger';
                    $statusText = 'รหัสส่งออกไม่ตรงกัน';
                } elseif (empty($pt->hipdata_code) || !in_array(strtoupper(trim($pt->hipdata_code)), $validHipdataCodes)) {
                    $ptErrors[] = empty($pt->hipdata_code) ? 'รหัส Hipdata ว่าง (ไม่ได้ระบุ)' : 'รหัส Hipdata (' . $pt->hipdata_code . ') ไม่ถูกต้อง';
                    $statusType = 'warning';
                    $statusText = 'รหัส Hipdata ไม่ถูกต้อง';
                }

                $pt->item_errors = $ptErrors;
                $pt->is_valid = empty($ptErrors);
                $pt->status_type = $statusType;
                $pt->status_text = $statusText;
            }

            if ($filter === 'active') {
                $queryRows = array_filter($queryRows, fn($r) => ($r->isuse ?? '') === 'Y');
            } elseif ($filter === 'inactive') {
                $queryRows = array_filter($queryRows, fn($r) => ($r->isuse ?? '') !== 'Y');
            } elseif ($filter === 'invalid') {
                $queryRows = array_filter($queryRows, fn($r) => ($r->isuse ?? '') === 'Y' && !$r->is_valid);
            }

            if (!empty($search)) {
                $searchLower = mb_strtolower($search);
                $queryRows = array_filter($queryRows, function ($r) use ($searchLower) {
                    return str_contains(mb_strtolower($r->pttype ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->name ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->paidst ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->hipdata_code ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->pttype_std_code ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->nhso_subinscl ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->pi_name ?? ''), $searchLower);
                });
            }

            $records = collect($queryRows)->values();
        } elseif ($activeTab === 'nhso_subinscl') {
            $queryRows = $hosxp->select('
                SELECT s.*, p.pttype, p.`name` AS pttype_name, p.hipdata_code 
                FROM hrims.subinscl s
                LEFT JOIN pttype p ON p.pttype = s.`code`
                ORDER BY CAST(s.`code` AS UNSIGNED) ASC, s.`code` ASC
            ');

            if ($filter === 'found') {
                $queryRows = array_filter($queryRows, fn($r) => !is_null($r->pttype));
            } elseif ($filter === 'notfound') {
                $queryRows = array_filter($queryRows, fn($r) => is_null($r->pttype));
            }

            if (!empty($search)) {
                $searchLower = mb_strtolower($search);
                $queryRows = array_filter($queryRows, function ($r) use ($searchLower) {
                    return str_contains(mb_strtolower($r->code ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->name ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->maininscl ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->pttype ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->pttype_name ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->hipdata_code ?? ''), $searchLower);
                });
            }

            $records = collect($queryRows)->values();

            foreach ($records as $row) {
                $rowErrors = [];
                if (empty($row->pttype)) {
                    $rowErrors[] = 'ไม่พบรหัสสิทธินี้ในตาราง pttype ของ HOSxP';
                }
                $row->item_errors = $rowErrors;
                $row->is_valid = empty($rowErrors);
            }
        } elseif ($activeTab === 'drug') {
            $localDb = config('database.connections.mysql.database');
            $queryRows = $hosxp->select("
                SELECT d.icode, d.name, d.strength, d.units, d.dosageform, d.drugaccount,
                       d.unitprice, d.price2, d.price3, d.ipd_price, d.ipd_price2, d.ipd_price3,
                       d.unitcost, d.stdprice, d.sks_price, d.sks_reimb_price,
                       d.did, d2.ref_code AS code_24, d.tmt_tp_code, d3.ref_code AS code_tmt,
                       d.nhso_adp_code, d.income, i.name AS income_name, d.istatus,
                       nd.unitprice AS nhso_price, nd.ised AS nhso_ised, nd.ndc24 AS code_24_nhso
                FROM drugitems d
                LEFT JOIN income i ON i.income = d.income
                LEFT JOIN drugitems_ref_code d2 ON d2.icode = d.icode AND d2.drugitems_ref_code_type_id = 1
                LEFT JOIN drugitems_ref_code d3 ON d3.icode = d.icode AND d3.drugitems_ref_code_type_id = 3
                LEFT JOIN (
                    SELECT hospdrugcode, unitprice, ised, ndc24
                    FROM {$localDb}.drugcat_nhso
                    GROUP BY hospdrugcode
                ) nd ON nd.hospdrugcode = d.icode
                ORDER BY d.istatus DESC, d.name ASC, d.icode ASC
            ");

            // Validate each drug record
            foreach ($queryRows as $item) {
                $code24 = !empty($item->code_24) ? trim($item->code_24) : trim($item->did ?? '');
                $codeTmt = !empty($item->code_tmt) ? trim($item->code_tmt) : trim($item->tmt_tp_code ?? '');
                $has24 = (strlen($code24) >= 24);
                $hasTmt = !empty($codeTmt);

                $item->resolved_code_24 = $code24;
                $item->resolved_code_tmt = $codeTmt;
                $item->has_24 = $has24;
                $item->has_tmt = $hasTmt;

                $itemErrors = [];
                if (!$has24) {
                    $itemErrors[] = 'ยังไม่มีรหัสมาตรฐาน 24 หลัก (NDC 24 digits)';
                }
                if (!$hasTmt) {
                    $itemErrors[] = 'ยังไม่มีรหัสยามาตรฐาน TMT';
                }
                if (empty(trim($item->income ?? ''))) {
                    $itemErrors[] = 'ยังไม่ระบุหมวดรายได้ (income)';
                }
                if (($item->unitprice ?? 0) <= 0) {
                    $itemErrors[] = 'ยังไม่ได้ระบุราคาจำหน่าย OPD (unitprice)';
                }

                $item->item_errors = $itemErrors;
                $item->is_valid = empty($itemErrors);
            }

            if ($filter === 'active') {
                $queryRows = array_filter($queryRows, fn($r) => ($r->istatus ?? '') === 'Y');
            } elseif ($filter === 'inactive') {
                $queryRows = array_filter($queryRows, fn($r) => ($r->istatus ?? '') !== 'Y');
            } elseif ($filter === 'missing_code24') {
                $queryRows = array_filter($queryRows, fn($r) => ($r->istatus ?? '') === 'Y' && !$r->has_24);
            } elseif ($filter === 'missing_tmt') {
                $queryRows = array_filter($queryRows, fn($r) => ($r->istatus ?? '') === 'Y' && !$r->has_tmt);
            }

            if (!empty($search)) {
                $searchLower = mb_strtolower($search);
                $queryRows = array_filter($queryRows, function ($r) use ($searchLower) {
                    return str_contains(mb_strtolower($r->icode ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->name ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->strength ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->resolved_code_24 ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->resolved_code_tmt ?? ''), $searchLower)
                        || str_contains(mb_strtolower($r->income_name ?? ''), $searchLower);
                });
            }

            $records = collect($queryRows)->values();
        } elseif ($activeTab === 'lab') {
            $localDb = config('database.connections.mysql.database');

            if ($subTab === 'profiles') {
                $queryRows = $hosxp->select("
                    SELECT sg.lab_items_sub_group_code, sg.lab_items_sub_group_name, sg.group_icode,
                           n.name AS nondrug_name, n.price AS nondrug_price,
                           sg.group_price, sg.group_price_ipd, sg.tmlt_code, sg.loinc_code,
                           sg.active_status,
                           lc.unitprice AS chi_price, lc.reimbprice AS chi_reimb
                    FROM lab_items_sub_group sg
                    LEFT JOIN nondrugitems n ON n.icode = sg.group_icode
                    LEFT JOIN (
                        SELECT lccode, unitprice, reimbprice
                        FROM {$localDb}.labcat_chi
                        GROUP BY lccode
                    ) lc ON lc.lccode = sg.group_icode
                    ORDER BY sg.lab_items_sub_group_name ASC
                ");

                foreach ($queryRows as $item) {
                    $itemErrors = [];
                    $isMapped = (!empty($item->group_icode) && !empty($item->nondrug_name));
                    $item->is_mapped = $isMapped;

                    if (!$isMapped) {
                        $itemErrors[] = 'ยังไม่ผูกรหัสค่ารักษาชุดตรวจ (group_icode ใน nondrugitems) ทำให้คิดค่าบริการและส่งเคลมไม่ได้';
                    }
                    if (empty(trim($item->tmlt_code ?? ''))) {
                        $itemErrors[] = 'ยังไม่ได้ระบุรหัสมาตรฐาน TMLT';
                    }

                    $item->item_errors = $itemErrors;
                    $item->is_valid = empty($itemErrors);
                }

                if ($filter === 'mapped') {
                    $queryRows = array_filter($queryRows, fn($r) => $r->is_mapped);
                } elseif ($filter === 'unmapped') {
                    $queryRows = array_filter($queryRows, fn($r) => !$r->is_mapped);
                } elseif ($filter === 'missing_tmlt') {
                    $queryRows = array_filter($queryRows, fn($r) => empty(trim($r->tmlt_code ?? '')));
                }

                if (!empty($search)) {
                    $searchLower = mb_strtolower($search);
                    $queryRows = array_filter($queryRows, function ($r) use ($searchLower) {
                        return str_contains(mb_strtolower($r->lab_items_sub_group_code ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->lab_items_sub_group_name ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->group_icode ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->nondrug_name ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->tmlt_code ?? ''), $searchLower);
                    });
                }

                $records = collect($queryRows)->values();
            } else {
                // Default: Lab Items (ตรวจเดี่ยว)
                $queryRows = $hosxp->select("
                    SELECT l.lab_items_code, l.lab_items_name, l.icode, n.name AS nondrug_name, n.price AS nondrug_price,
                           l.service_price, l.service_price_ipd, l.service_cost, l.tmlt_code, l.loinc_code,
                           l.lab_items_unit, l.lab_items_normal_value, l.critical_value,
                           sg.lab_items_sub_group_name, g.lab_items_group_name, sp.specimen_name,
                           l.active_status,
                           lc.unitprice AS chi_price, lc.reimbprice AS chi_reimb
                    FROM lab_items l
                    LEFT JOIN nondrugitems n ON n.icode = l.icode
                    LEFT JOIN lab_items_sub_group sg ON sg.lab_items_sub_group_code = l.lab_items_sub_group_code
                    LEFT JOIN lab_items_group g ON g.lab_items_group_code = l.lab_items_group
                    LEFT JOIN lab_specimen_items sp ON sp.specimen_code = l.specimen_code
                    LEFT JOIN (
                        SELECT lccode, unitprice, reimbprice
                        FROM {$localDb}.labcat_chi
                        GROUP BY lccode
                    ) lc ON lc.lccode = l.icode
                    ORDER BY l.active_status DESC, l.lab_items_name ASC
                ");

                foreach ($queryRows as $item) {
                    $itemErrors = [];
                    $isMapped = (!empty($item->icode) && !empty($item->nondrug_name));
                    $item->is_mapped = $isMapped;

                    if (!$isMapped) {
                        $itemErrors[] = 'ยังไม่ผูกรหัสค่ารักษา (icode ใน nondrugitems) ทำให้คิดค่าบริการและส่งเคลมไม่ได้';
                    }
                    if (empty(trim($item->tmlt_code ?? ''))) {
                        $itemErrors[] = 'ยังไม่ได้ระบุรหัสมาตรฐาน TMLT';
                    }
                    if (empty(trim($item->lab_items_normal_value ?? ''))) {
                        $itemErrors[] = 'ยังไม่กำหนดค่าปกติ (Normal Value)';
                    }

                    $item->item_errors = $itemErrors;
                    $item->is_valid = empty($itemErrors);
                }

                if ($filter === 'active') {
                    $queryRows = array_filter($queryRows, fn($r) => ($r->active_status ?? '') === 'Y');
                } elseif ($filter === 'inactive') {
                    $queryRows = array_filter($queryRows, fn($r) => ($r->active_status ?? '') !== 'Y');
                } elseif ($filter === 'mapped') {
                    $queryRows = array_filter($queryRows, fn($r) => ($r->active_status ?? '') === 'Y' && $r->is_mapped);
                } elseif ($filter === 'unmapped') {
                    $queryRows = array_filter($queryRows, fn($r) => ($r->active_status ?? '') === 'Y' && !$r->is_mapped);
                } elseif ($filter === 'missing_tmlt') {
                    $queryRows = array_filter($queryRows, fn($r) => ($r->active_status ?? '') === 'Y' && empty(trim($r->tmlt_code ?? '')));
                }

                if (!empty($search)) {
                    $searchLower = mb_strtolower($search);
                    $queryRows = array_filter($queryRows, function ($r) use ($searchLower) {
                        return str_contains(mb_strtolower($r->lab_items_code ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->lab_items_name ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->icode ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->nondrug_name ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->tmlt_code ?? ''), $searchLower)
                            || str_contains(mb_strtolower($r->lab_items_group_name ?? ''), $searchLower);
                    });
                }

                $records = collect($queryRows)->values();
            }
        }

        // Get AI model info configured for HOSxP
        $aiModelInfo = [
            'provider' => AiService::getProvider('hosxp'),
            'model' => AiService::getHosxpModelName(),
        ];

        return view('emr.hosxp_setting.index', compact(
            'hosxpAlive',
            'activeTab',
            'subTab',
            'stats',
            'records',
            'activeDocs',
            'inactiveDocs',
            'invalidLicenseDocs',
            'invalidCidDocs',
            'doctorTabConfigs',
            'search',
            'filter',
            'aiModelInfo'
        ));
    }

    /**
     * Dedicated AI Copilot Query for HOSxP ข้อมูลพื้นฐาน
     */
    public function copilotAsk(Request $request)
    {
        $this->checkPermission();

        if (!\App\Services\LicenseVerificationService::isModuleLicensed('ai_knowledge')) {
            return response()->json([
                'success' => false,
                'message' => 'ระบบ AI ยังไม่ได้รับอนุญาตสิทธิ์การใช้งาน License หรือลิขสิทธิ์หมดอายุ กรุณาติดต่อผู้ดูแลระบบ'
            ], 403);
        }

        $query = trim($request->input('query', ''));
        $tab = $request->input('tab', 'doctor');

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุคำถามหรือข้อสงสัยครับ'
            ], 422);
        }

        try {
            // 1. Fetch relevant context from HOSxP Context Service (passing $tab for direct table routing)
            $contextService = app(HosxpContextService::class);
            $hosxpContext = $contextService->getContext($query, $tab);

            // 2. Fetch relevant standard guidelines from RAG if available
            $ragContext = null;
            try {
                if (class_exists(RagSearchService::class)) {
                    $ragService = app(RagSearchService::class);
                    $ragResult = $ragService->search($query, 3);
                    if (!empty($ragResult['results'])) {
                        $chunks = [];
                        foreach ($ragResult['results'] as $r) {
                            $chunks[] = "• [จากคู่มือ {$r['filename']}]: {$r['content']}";
                        }
                        $ragContext = implode("\n", $chunks);
                    }
                }
            } catch (\Throwable $re) {
                Log::info("RAG search optional skip: " . $re->getMessage());
            }

            // 3. Build Prompt with Domain Instructions
            $systemPrompt = "คุณคือ 'น้องมีตังค์' (RiMS AI) ผู้ช่วยสาว AI อัจฉริยะเพศหญิงด้านข้อมูลระบบ HOSxP และงานเวชระเบียนของโรงพยาบาล RiMS
- เพศและบุคลิกภาพ: เพศหญิง สุภาพ น่ารัก อ่อนหวาน มั่นใจ เป็นมืออาชีพ
- สรรพนามแทนตัวเอง: ให้แทนตัวเองว่า 'น้องมีตังค์' หรือ 'หนู' เท่านั้น
- คำลงท้าย: ต้องใช้คำลงท้ายเพศหญิง เช่น 'ค่ะ', 'นะคะ' เสมอ ห้ามใช้ 'ครับ', 'ครับ/ค่ะ', หรือแทนตัวเองว่า 'ผม' โดยเด็ดขาด
- ชื่อระบบ: ให้เรียกชื่อระบบว่า 'RiMS' เท่านั้น ห้ามเรียกหรือเขียนว่า 'HRiMS' โดยเด็ดขาด
คุณมีหน้าที่ช่วยตรวจสอบความถูกต้อง แนะนำการตรวจสอบข้อมูล และให้คำแนะนำที่ถูกต้องตามมาตรฐาน สปสช., กรมบัญชีกลาง และกระทรวงสาธารณสุข

*** ข้อกำหนดและกฎสำคัญเด็ดขาด (Strict Rules) ***:
1. ห้ามเขียนคำสั่ง SQL ทุกชนิด (เช่น SELECT, UPDATE, INSERT, DELETE) เด็ดขาด ไม่ต้องแสดงบล็อกคำสั่ง SQL ใดๆ ให้ผู้ใช้ แม้ผู้ใช้จะขอโดยตรง ให้แจ้งอย่างสุภาพว่าระบบสงวนสิทธิ์ไม่แสดงคำสั่งแก้ฐานข้อมูลโดยตรงเพื่อความปลอดภัยของข้อมูลโรงพยาบาล
2. ห้ามเอ่ยชื่อตารางฐานข้อมูลภายในโดยตรง (เช่น doctor, nondrugitems, pttype, spclty, income ฯลฯ) ให้ใช้ภาษาทางการแพทย์หรือชื่อหมวดข้อมูลเชิงธุรกิจแทน เช่น 'ข้อมูลแพทย์และบุคลากร', 'รายการค่าบริการและค่ารักษาพยาบาล', 'สิทธิการรักษาพยาบาล'
3. ให้คำแนะนำผู้ใช้ในการตรวจสอบและแก้ไขข้อมูลผ่าน 'หน้าจอเมนูของโปรแกรม HOSxP' เป็นหลัก เช่น:
   - ข้อมูลแพทย์/บุคลากร: แนะนำให้เข้าเมนู 'เครื่องมือ (Tools) > ตั้งค่าระบบ (System Setting) > กำหนดข้อมูลแพทย์/ผู้ให้บริการ' เพื่อแก้ไขหรือบันทึกเลขที่ใบประกอบวิชาชีพ, เลขประจำตัวประชาชน 13 หลัก, และรหัสสภาวิชาชีพ
   - ข้อมูลค่ารักษาพยาบาล: แนะนำให้เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดรายการค่ารักษาพยาบาล (Non-Drug Items)' เพื่อตรวจสอบการผูกรหัส ADP และหมวดรายได้
   - ข้อมูลสิทธิการรักษา: แนะนำให้เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดสิทธิการรักษา (Pttype)' เพื่อตรวจสอบรหัสสิทธิมาตรฐานและรหัสส่งออกเคลม
   - ข้อมูลยา: แนะนำให้เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดรายการยา (Drug Items)' เพื่อตรวจสอบและบันทึกรหัสมาตรฐาน 24 หลัก, รหัส TMT, หมวดรายได้ และกำหนดโครงสร้างราคาแยกเก็บ (OPD 1, 2, 3, IPD, ราคากรมบัญชีกลาง SKS)
   - ข้อมูล Lab: แนะนำให้เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดรายการตรวจทางห้องปฏิบัติการ (Lab Items)' และ 'กำหนดชุดตรวจ (Lab Sub Group)' เพื่อผูกรหัสค่าบริการ icode เข้ากับ nondrugitems และระบุรหัสมาตรฐาน TMLT/LOINC
4. อธิบายอย่างเป็นมืออาชีพ สุภาพ ชัดเจน เข้าใจง่าย ชี้ให้เห็นว่าข้อมูลขาดอะไรและจะส่งผลกระทบต่อการส่งออก 43 แฟ้ม หรือการส่งเบิกเคลมอย่างไร พร้อมบอกวิธีบันทึกแก้ไขใน HOSxP
5. จัดรูปแบบด้วย Markdown ใช้หัวข้อ, bullet points, และตัวหนาให้อ่านง่าย สบายตา
6. กฎสำคัญเรื่องการใช้คำ: ให้ใช้คำว่า 'ข้อมูลพื้นฐาน' เสมอ และห้ามใช้คำว่า 'Master Data' ในคำตอบเด็ดขาด";

            $userPrompt = "คำถามจากเจ้าหน้าที่ (หมวด {$tab}):\n\"{$query}\"\n\n";

            if ($hosxpContext && !empty($hosxpContext['text'])) {
                $userPrompt .= "--- ข้อมูลบริบทจริงจากระบบ HOSxP ---\n" . $hosxpContext['text'] . "\n\n";
            }

            if ($ragContext) {
                $userPrompt .= "--- ข้อกำหนดและแนวทางมาตรฐานจากคลังความรู้ RAG ---\n" . $ragContext . "\n\n";
            }

            $userPrompt .= "กรุณาตอบคำถาม วิเคราะห์จุดที่ข้อมูลไม่สมบูรณ์หรือความเสี่ยงต่อการส่งเคลม และให้คำแนะนำการตรวจสอบ/บันทึกแก้ไขข้อมูลผ่านหน้าจอโปรแกรม HOSxP โดยห้ามระบุคำสั่ง SQL หรือชื่อตารางฐานข้อมูลครับ";

            $aiService = app(AiService::class);
            $answer = $aiService->generateChat($userPrompt, $systemPrompt, 'hosxp');

            return response()->json([
                'success' => true,
                'answer' => $answer,
                'provider' => AiService::getProvider('hosxp'),
                'model' => AiService::getHosxpModelName(),
                'sources' => $hosxpContext['sources'] ?? []
            ]);

        } catch (\Throwable $e) {
            Log::error("HOSxP Setting Copilot Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการประมวลผล AI: ' . $e->getMessage()
            ], 500);
        }
    }
}
