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
use App\Models\Labcat_nhso;
use App\Models\Labcat_chi;

class CheckLabcatController extends Controller
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

    public function labcat_nhso_save(Request $request)
    {
        set_time_limit(300);

        Labcat_nhso::truncate();

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
            $row_range    = range('2', $row_limit);
            
            $cleanPrice = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = [];
            foreach ($row_range as $row) {
                $lccode = $sheet->getCell('B' . $row)->getValue();
                if ($lccode instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $lccode = $lccode->getPlainText();
                }
                $lccode = trim($lccode ?? '');
                if ($lccode === '') {
                    continue;
                }

                $data[] = [
                    'lccode'       => trim($lccode),
                    'billgroup'    => $sheet->getCell('C' . $row)->getValue(),
                    'cscode'       => $sheet->getCell('D' . $row)->getValue(),
                    'tmlt'         => $sheet->getCell('E' . $row)->getValue(),
                    'loinc'        => $sheet->getCell('F' . $row)->getValue(),
                    'panel'        => $sheet->getCell('G' . $row)->getValue(),
                    'name'         => $sheet->getCell('H' . $row)->getValue(),
                    'sflag'        => $sheet->getCell('I' . $row)->getValue(),
                    'chargecat'    => $sheet->getCell('J' . $row)->getValue(),
                    'unitprice'    => $cleanPrice($sheet->getCell('K' . $row)->getValue()),
                    'benefitplan'  => $sheet->getCell('L' . $row)->getValue(),
                    'reimbprice'   => $cleanPrice($sheet->getCell('M' . $row)->getValue()),
                    'updateflag'   => $sheet->getCell('N' . $row)->getValue(),
                    'updatebeg'    => $sheet->getCell('O' . $row)->getValue(),
                    'updateend'    => $sheet->getCell('P' . $row)->getValue(),
                    'rpdatebeg'    => $sheet->getCell('Q' . $row)->getValue(),
                    'rpdateend'    => $sheet->getCell('R' . $row)->getValue(),
                    'dateupd'      => $sheet->getCell('S' . $row)->getValue(),
                    'hcode'        => $sheet->getCell('T' . $row)->getValue(),
                    'message'      => $sheet->getCell('U' . $row)->getValue(),
                    'stm_filename' => $file_name,
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $data_) {
                Labcat_nhso::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.labcat_nhso')->with('success', $file_name);
    }

    private function getLabItems($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE l.active_status = 'Y' AND (l.icode IS NOT NULL AND l.icode <> '')";
        $whereClause .= " AND l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT l.lab_items_code, l.icode, l.lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso, n.price AS service_price, l.service_price_ipd, l.tmlt_code, l.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_nhso dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_nhso dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = l.icode
            {$whereClause}
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getLabPanels($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NOT NULL AND sg.group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso,
                   n.price AS service_price, sg.group_price_ipd AS service_price_ipd, sg.tmlt_code, sg.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_nhso dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_nhso dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = sg.group_icode
            {$whereClause}
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    private function getUnmappedLabItems()
    {
        return DB::connection('hosxp')->select("
            SELECT 'I' AS lab_type, l.lab_items_code, l.icode, l.lab_items_name, l.tmlt_code, l.loinc_code
            FROM lab_items l
            LEFT JOIN nondrugitems n ON n.icode = l.icode
            WHERE l.active_status = 'Y'
              AND (l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '') OR l.icode IS NULL OR l.icode = '')
              AND n.icode IS NULL
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getUnmappedLabPanels()
    {
        return DB::connection('hosxp')->select("
            SELECT 'P' AS lab_type, sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, sg.tmlt_code, sg.loinc_code
            FROM lab_items_sub_group sg
            LEFT JOIN nondrugitems n ON n.icode = sg.group_icode
            WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL)
              AND n.icode IS NULL
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    public function labcat_nhso()
    {
        $items_i = $this->getLabItems();
        $items_p = $this->getLabPanels();
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_non_nhso()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NULL");
        $items_p = $this->getLabPanels("AND ln.lccode IS NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_price_notmatch_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_tmlt_notmatch_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND ln.tmlt <> l.tmlt_code");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND ln.tmlt <> sg.tmlt_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_loinc_notmatch_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND ln.loinc <> l.loinc_code");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND ln.loinc <> sg.loinc_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_tmlt_missing_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND (l.tmlt_code IS NULL OR l.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND (sg.tmlt_code IS NULL OR sg.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_nhso_loinc_missing_hosxp()
    {
        $items_i = $this->getLabItems("AND ln.lccode IS NOT NULL AND (l.loinc_code IS NULL OR l.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_p = $this->getLabPanels("AND ln.lccode IS NOT NULL AND (sg.loinc_code IS NULL OR sg.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_nhso', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    // =========================================================================
    // LAB CATALOG CHI (สกส.) --------------------------------------------------
    // =========================================================================
    public function labcat_chi_save(Request $request)
    {
        set_time_limit(300);

        Labcat_chi::truncate();

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
            $row_range    = range('5', $row_limit); // Starts at row 5 (Header is on row 4)
            
            $cleanPrice = function ($val) {
                if ($val === null || $val === '-' || trim($val) === '') {
                    return null;
                }
                $val = str_replace(',', '', $val);
                return is_numeric($val) ? (float) $val : null;
            };

            $data = [];
            foreach ($row_range as $row) {
                $lccode = $sheet->getCell('B' . $row)->getValue();
                if ($lccode instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $lccode = $lccode->getPlainText();
                }
                $lccode = trim($lccode ?? '');
                if ($lccode === '') {
                    continue;
                }

                $data[] = [
                    'lccode'       => trim($lccode),
                    'billgroup'    => $sheet->getCell('C' . $row)->getValue(),
                    'cscode'       => $sheet->getCell('D' . $row)->getValue(),
                    'tmlt'         => $sheet->getCell('E' . $row)->getValue(),
                    'loinc'        => $sheet->getCell('F' . $row)->getValue(),
                    'panel'        => $sheet->getCell('G' . $row)->getValue(),
                    'name'         => $sheet->getCell('H' . $row)->getValue(),
                    'sflag'        => $sheet->getCell('I' . $row)->getValue(),
                    'chargecat'    => $sheet->getCell('J' . $row)->getValue(),
                    'unitprice'    => $cleanPrice($sheet->getCell('K' . $row)->getValue()),
                    'benefitplan'  => $sheet->getCell('L' . $row)->getValue(),
                    'reimbprice'   => $cleanPrice($sheet->getCell('M' . $row)->getValue()),
                    'updateflag'   => $sheet->getCell('N' . $row)->getValue(),
                    'updatebeg'    => $sheet->getCell('O' . $row)->getValue(),
                    'updateend'    => $sheet->getCell('P' . $row)->getValue(),
                    'rpdatebeg'    => $sheet->getCell('Q' . $row)->getValue(),
                    'rpdateend'    => $sheet->getCell('R' . $row)->getValue(),
                    'dateupd'      => $sheet->getCell('S' . $row)->getValue(),
                    'hcode'        => '', // Default empty if not in file
                    'message'      => '', // Default empty if not in file
                    'stm_filename' => $file_name,
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $data_) {
                Labcat_chi::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.labcat_chi')->with('success', $file_name);
    }

    private function getLabItemsChi($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE l.active_status = 'Y' AND (l.icode IS NOT NULL AND l.icode <> '')";
        $whereClause .= " AND l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT l.lab_items_code, l.icode, l.lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso, n.price AS service_price, l.service_price_ipd, l.tmlt_code, l.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = l.icode
            {$whereClause}
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getLabPanelsChi($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NOT NULL AND sg.group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso,
                   n.price AS service_price, sg.group_price_ipd AS service_price_ipd, sg.tmlt_code, sg.loinc_code,
                   ln.unitprice AS price_nhso, ln.reimbprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   ln.panel AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = sg.group_icode
            {$whereClause}
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    public function labcat_chi()
    {
        $items_i = $this->getLabItemsChi();
        $items_p = $this->getLabPanelsChi();
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_non_nhso()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NULL");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_price_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_tmlt_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND ln.tmlt <> l.tmlt_code");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND ln.tmlt <> sg.tmlt_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_loinc_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND ln.loinc <> l.loinc_code");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND ln.loinc <> sg.loinc_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_tmlt_missing_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND (l.tmlt_code IS NULL OR l.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND (sg.tmlt_code IS NULL OR sg.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_chi_loinc_missing_hosxp()
    {
        $items_i = $this->getLabItemsChi("AND ln.lccode IS NOT NULL AND (l.loinc_code IS NULL OR l.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_p = $this->getLabPanelsChi("AND ln.lccode IS NOT NULL AND (sg.loinc_code IS NULL OR sg.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_chi', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    // =========================================================================
    // LAB CATALOG FDH ---------------------------------------------------------
    // =========================================================================
    private function getLabItemsFdh($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE l.active_status = 'Y' AND (l.icode IS NOT NULL AND l.icode <> '')";
        $whereClause .= " AND l.icode NOT IN (SELECT DISTINCT group_icode FROM lab_items_sub_group WHERE group_icode IS NOT NULL AND group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT l.lab_items_code, l.icode, l.lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso, n.price AS service_price, l.service_price_ipd, l.tmlt_code, l.loinc_code,
                   ln.unitprice AS price_nhso, ln.unitprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   '' AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = l.icode
            {$whereClause}
            ORDER BY l.lab_items_name ASC
        ");
    }

    private function getLabPanelsFdh($extraWhere = '')
    {
        $local_db = config('database.connections.mysql.database');
        $whereClause = "WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NOT NULL AND sg.group_icode <> '')";
        if ($extraWhere) {
            $whereClause .= " " . $extraWhere;
        }

        return DB::connection('hosxp')->select("
            SELECT sg.lab_items_sub_group_code AS lab_items_code, sg.group_icode AS icode, sg.lab_items_sub_group_name AS lab_items_name, n.name AS nondrug_name, ln.name AS name_nhso,
                   n.price AS service_price, sg.group_price_ipd AS service_price_ipd, sg.tmlt_code, sg.loinc_code,
                   ln.unitprice AS price_nhso, ln.unitprice AS reimb_nhso, ln.tmlt AS tmlt_nhso, ln.loinc AS loinc_nhso,
                   '' AS panel_nhso,
                   IF(ln.lccode IS NULL, 'N', 'Y') AS chk_nhso_labcat
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                )
            ) ln ON ln.lccode = sg.group_icode
            {$whereClause}
            ORDER BY sg.lab_items_sub_group_name ASC
        ");
    }

    public function labcat_fdh_save(Request $request)
    {
        set_time_limit(300);

        \App\Models\Labcat_fdh::truncate();

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
            $row_range    = range('6', $row_limit);

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
                foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d\TH:i:s\Z', 'Y-m-d H:i:s', 'd-m-Y H:i'] as $format) {
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

            $data = [];
            foreach ($row_range as $row) {
                $lccode = $sheet->getCell('M' . $row)->getValue();
                if ($lccode instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $lccode = $lccode->getPlainText();
                }
                $lccode = trim($lccode ?? '');
                if (empty($lccode)) {
                    continue;
                }

                $unitprice = $cleanExcelRate($sheet->getCell('F' . $row)->getValue());
                $updatebeg = $parseExcelDate($sheet->getCell('H' . $row)->getValue());
                $updateend = $parseExcelDate($sheet->getCell('I' . $row)->getValue());

                $data[] = [
                    'benefitplan'  => $sheet->getCell('B' . $row)->getValue(),
                    'cscode'       => $sheet->getCell('C' . $row)->getValue(),
                    'name'         => $sheet->getCell('D' . $row)->getValue(),
                    'unit'         => $sheet->getCell('E' . $row)->getValue(),
                    'unitprice'    => $unitprice,
                    'gyear'        => $sheet->getCell('G' . $row)->getValue(),
                    'updatebeg'    => $updatebeg,
                    'updateend'    => $updateend,
                    'updateflag'   => $sheet->getCell('J' . $row)->getValue(),
                    'tmlt'         => $sheet->getCell('K' . $row)->getValue(),
                    'tmlt_name'    => $sheet->getCell('L' . $row)->getValue(),
                    'lccode'       => $lccode,
                    'loinc'        => $sheet->getCell('N' . $row)->getValue(),
                    'exception'    => $sheet->getCell('O' . $row)->getValue(),
                    'stm_filename' => $file_name,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            $for_insert = array_chunk($data, 1000);
            foreach ($for_insert as $data_) {
                \App\Models\Labcat_fdh::insert($data_);
            }
        } catch (\Exception $e) {
            return back()->withErrors('เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }

        return redirect()->route('check.labcat_fdh')->with('success', $file_name);
    }

    public function labcat_fdh()
    {
        $items_i = $this->getLabItemsFdh();
        $items_p = $this->getLabPanelsFdh();
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_non_nhso()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NULL");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_price_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND ln.unitprice <> n.price");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_tmlt_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND ln.tmlt <> l.tmlt_code");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND ln.tmlt <> sg.tmlt_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_loinc_notmatch_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND ln.loinc <> l.loinc_code");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND ln.loinc <> sg.loinc_code");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_tmlt_missing_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND (l.tmlt_code IS NULL OR l.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND (sg.tmlt_code IS NULL OR sg.tmlt_code = '') AND ln.tmlt IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_loinc_missing_hosxp()
    {
        $items_i = $this->getLabItemsFdh("AND ln.lccode IS NOT NULL AND (l.loinc_code IS NULL OR l.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_p = $this->getLabPanelsFdh("AND ln.lccode IS NOT NULL AND (sg.loinc_code IS NULL OR sg.loinc_code = '') AND ln.loinc IS NOT NULL");
        $items_unmapped_i = $this->getUnmappedLabItems();
        $items_unmapped_p = $this->getUnmappedLabPanels();
        return view('check.labcat_fdh', compact('items_i', 'items_p', 'items_unmapped_i', 'items_unmapped_p'));
    }

    public function labcat_fdh_export(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcelLabFDH([], $hosp_code . 'LabFDH' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);
        
        $labs = DB::connection('hosxp')->select("
            SELECT 
                'NHS/LGO/OFC' AS benefitplan,
                IFNULL(lf.cscode, '') AS cscode,
                l.lab_items_name AS name,
                'Test' AS unit,
                n.price AS unitprice,
                IFNULL(lf.gyear, '2026') AS gyear,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                IFNULL(lf.updateend, '') AS updateend,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                l.tmlt_code AS tmlt,
                IFNULL(lf.tmlt_name, '') AS tmlt_name,
                l.icode AS lccode,
                l.loinc_code AS loinc,
                IFNULL(lf.exception, '') AS exception
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                )
            ) lf ON lf.lccode = l.icode
            WHERE l.icode IN ({$where_icode})
            
            UNION ALL
            
            SELECT 
                'NHS/LGO/OFC' AS benefitplan,
                IFNULL(lf.cscode, '') AS cscode,
                sg.lab_items_sub_group_name AS name,
                'Profile' AS unit,
                n.price AS unitprice,
                IFNULL(lf.gyear, '2026') AS gyear,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                IFNULL(lf.updateend, '') AS updateend,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                sg.tmlt_code AS tmlt,
                IFNULL(lf.tmlt_name, '') AS tmlt_name,
                sg.group_icode AS lccode,
                sg.loinc_code AS loinc,
                IFNULL(lf.exception, '') AS exception
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                WHERE dc.id = (
                    SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=sg.group_icode
                )
            ) lf ON lf.lccode = sg.group_icode
            WHERE sg.group_icode IN ({$where_icode})
        ");

        return $this->exportToExcelLabFDH($labs, $hosp_code . 'LabFDH' . $seq . '.xlsx');
    }

    public function labcat_fdh_export_preview(Request $request)
    {
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);

        try {
            $labs = DB::connection('hosxp')->select("
                SELECT 
                    'NHS/LGO/OFC' AS benefitplan,
                    IFNULL(lf.cscode, '') AS cscode,
                    l.lab_items_name AS name,
                    'Test' AS unit,
                    n.price AS unitprice,
                    IFNULL(lf.gyear, '2026') AS gyear,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                    IFNULL(lf.updateend, '') AS updateend,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    l.tmlt_code AS tmlt,
                    IFNULL(lf.tmlt_name, '') AS tmlt_name,
                    l.icode AS lccode,
                    l.loinc_code AS loinc,
                    IFNULL(lf.exception, '') AS exception
                FROM lab_items l
                INNER JOIN nondrugitems n ON n.icode = l.icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                    WHERE dc.id = (
                        SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=dc1.lccode
                    )
                ) lf ON lf.lccode = l.icode
                WHERE l.icode IN ({$where_icode})
                
                UNION ALL
                
                SELECT 
                    'NHS/LGO/OFC' AS benefitplan,
                    IFNULL(lf.cscode, '') AS cscode,
                    sg.lab_items_sub_group_name AS name,
                    'Profile' AS unit,
                    n.price AS unitprice,
                    IFNULL(lf.gyear, '2026') AS gyear,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d')) AS updatebeg,
                    IFNULL(lf.updateend, '') AS updateend,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    sg.tmlt_code AS tmlt,
                    IFNULL(lf.tmlt_name, '') AS tmlt_name,
                    sg.group_icode AS lccode,
                    sg.loinc_code AS loinc,
                    IFNULL(lf.exception, '') AS exception
                FROM lab_items_sub_group sg
                INNER JOIN nondrugitems n ON n.icode = sg.group_icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_fdh dc 
                    WHERE dc.id = (
                        SELECT MAX(dc1.id) FROM {$local_db}.labcat_fdh dc1 WHERE dc.lccode=sg.group_icode
                    )
                ) lf ON lf.lccode = sg.group_icode
                WHERE sg.group_icode IN ({$where_icode})
            ");
            
            return response()->json([
                'success' => true,
                'data' => $labs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function exportToExcelLabFDH($data, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'row', 'สิทธิประโยชน์', 'รหัสกรมบัญชีกลาง', 'ชื่อ', 'หน่วย', 'ราคากลาง', 
            'gyear', 'วันที่เริ่มต้น', 'วันที่สิ้นสุด', 'flag', 'TMLT Code', 
            'TMLT Name', 'Lab Code', 'LOINC', 'Exception'
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
                $rowArray['benefitplan'] ?? '',
                $rowArray['cscode'] ?? '',
                $rowArray['name'] ?? '',
                $rowArray['unit'] ?? '',
                $rowArray['unitprice'] ?? '',
                $rowArray['gyear'] ?? '',
                $rowArray['updatebeg'] ?? '',
                $rowArray['updateend'] ?? '',
                $rowArray['updateflag'] ?? '',
                $rowArray['tmlt'] ?? '',
                $rowArray['tmlt_name'] ?? '',
                $rowArray['lccode'] ?? '',
                $rowArray['loinc'] ?? '',
                $rowArray['exception'] ?? ''
            ];

            $colIndex = 1;
            foreach ($rowData as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $rowNum, $val);
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

    public function labcat_chi_export(Request $request, $seq = '001')
    {
        $hosp_code = \App\Models\MainSetting::where('name', 'hospital_code')->value('value') ?: '10989';
        $seq = str_pad($seq, 3, '0', STR_PAD_LEFT);
        
        $icodes = $request->input('icodes', []);
        if (empty($icodes) || !is_array($icodes)) {
            return $this->exportToExcelLabChi([], $hosp_code . 'LabChi' . $seq . '.xlsx');
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);
        
        $labs = DB::connection('hosxp')->select("
            SELECT 
                l.icode AS lccode,
                IFNULL(lf.billgroup, '7') AS billgroup,
                IFNULL(lf.cscode, '') AS cscode,
                IFNULL(l.tmlt_code, '') AS tmlt,
                IFNULL(l.loinc_code, '') AS loinc,
                'I' AS panel,
                l.lab_items_name AS name,
                IFNULL(lf.sflag, 'R') AS sflag,
                IFNULL(lf.chargecat, 'R') AS chargecat,
                n.price AS unitprice,
                'CS' AS benefitplan,
                IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                IFNULL(lf.rpdateend, '') AS rpdateend,
                IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
            FROM lab_items l
            INNER JOIN nondrugitems n ON n.icode = l.icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                )
            ) lf ON lf.lccode = l.icode
            WHERE l.icode IN ({$where_icode})
            
            UNION ALL
            
            SELECT 
                sg.group_icode AS lccode,
                IFNULL(lf.billgroup, '7') AS billgroup,
                IFNULL(lf.cscode, '') AS cscode,
                IFNULL(sg.tmlt_code, '') AS tmlt,
                IFNULL(sg.loinc_code, '') AS loinc,
                'P' AS panel,
                sg.lab_items_sub_group_name AS name,
                IFNULL(lf.sflag, 'R') AS sflag,
                IFNULL(lf.chargecat, 'R') AS chargecat,
                n.price AS unitprice,
                'CS' AS benefitplan,
                IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                IFNULL(lf.updateflag, 'A') AS updateflag,
                IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                IFNULL(lf.rpdateend, '') AS rpdateend,
                IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
            FROM lab_items_sub_group sg
            INNER JOIN nondrugitems n ON n.icode = sg.group_icode
            LEFT JOIN (
                SELECT dc.* FROM {$local_db}.labcat_chi dc 
                WHERE dc.dateupd = (
                    SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=sg.group_icode
                )
            ) lf ON lf.lccode = sg.group_icode
            WHERE sg.group_icode IN ({$where_icode})
        ");

        return $this->exportToExcelLabChi($labs, $hosp_code . 'LabChi' . $seq . '.xlsx');
    }

    public function labcat_chi_export_preview(Request $request)
    {
        $icodes = $request->input('icodes', []);
        
        if (empty($icodes) || !is_array($icodes)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $local_db = config('database.connections.mysql.database');
        $quoted = array_map(function($val) {
            return DB::connection('hosxp')->getPdo()->quote($val);
        }, $icodes);
        $where_icode = implode(',', $quoted);

        try {
            $labs = DB::connection('hosxp')->select("
                SELECT 
                    l.icode AS lccode,
                    IFNULL(lf.billgroup, '7') AS billgroup,
                    IFNULL(lf.cscode, '') AS cscode,
                    IFNULL(l.tmlt_code, '') AS tmlt,
                    IFNULL(l.loinc_code, '') AS loinc,
                    'I' AS panel,
                    l.lab_items_name AS name,
                    IFNULL(lf.sflag, 'R') AS sflag,
                    IFNULL(lf.chargecat, 'R') AS chargecat,
                    n.price AS unitprice,
                    'CS' AS benefitplan,
                    IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                    IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                    IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                    IFNULL(lf.rpdateend, '') AS rpdateend,
                    IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
                FROM lab_items l
                INNER JOIN nondrugitems n ON n.icode = l.icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_chi dc 
                    WHERE dc.dateupd = (
                        SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=dc1.lccode
                    )
                ) lf ON lf.lccode = l.icode
                WHERE l.icode IN ({$where_icode})
                
                UNION ALL
                
                SELECT 
                    sg.group_icode AS lccode,
                    IFNULL(lf.billgroup, '7') AS billgroup,
                    IFNULL(lf.cscode, '') AS cscode,
                    IFNULL(sg.tmlt_code, '') AS tmlt,
                    IFNULL(sg.loinc_code, '') AS loinc,
                    'P' AS panel,
                    sg.lab_items_sub_group_name AS name,
                    IFNULL(lf.sflag, 'R') AS sflag,
                    IFNULL(lf.chargecat, 'R') AS chargecat,
                    n.price AS unitprice,
                    'CS' AS benefitplan,
                    IFNULL(lf.reimbprice, 0.00) AS reimbprice,
                    IFNULL(lf.updateflag, 'A') AS updateflag,
                    IFNULL(lf.updatebeg, DATE_FORMAT(NOW(), '%Y-%m-%d 00:00:00')) AS updatebeg,
                    IFNULL(lf.updateend, '9999-12-31 23:59:59') AS updateend,
                    IFNULL(lf.rpdatebeg, '') AS rpdatebeg,
                    IFNULL(lf.rpdateend, '') AS rpdateend,
                    IFNULL(lf.dateupd, DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')) AS dateupd
                FROM lab_items_sub_group sg
                INNER JOIN nondrugitems n ON n.icode = sg.group_icode
                LEFT JOIN (
                    SELECT dc.* FROM {$local_db}.labcat_chi dc 
                    WHERE dc.dateupd = (
                        SELECT MAX(dc1.dateupd) FROM {$local_db}.labcat_chi dc1 WHERE dc.lccode=sg.group_icode
                    )
                ) lf ON lf.lccode = sg.group_icode
                WHERE sg.group_icode IN ({$where_icode})
            ");

            // Format data for preview
            foreach ($labs as &$row) {
                $row->unitprice = $this->formatChiPrice($row->unitprice);
                $row->reimbprice = $this->formatChiPrice($row->reimbprice);
                $row->updatebeg = $this->formatChiIsoDateTime($row->updatebeg);
                $row->updateend = $this->formatChiIsoDateTime($row->updateend);
                $row->rpdatebeg = $this->formatChiIsoDateTime($row->rpdatebeg);
                $row->rpdateend = $this->formatChiIsoDateTime($row->rpdateend);
                $row->dateupd = $this->formatChiIsoDateTime($row->dateupd);
            }
            
            return response()->json([
                'success' => true,
                'data' => $labs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function exportToExcelLabChi($data, $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'LCCode', 'BillGroup', 'CsCode', 'TMLT', 'LOINC', 'Panel', 
            'Name', 'SFlag', 'ChargeCat', 'UnitPrice', 'BenefitPlan', 
            'ReimbPrice', 'UpdateFlag', 'UPDateBeg', 'UPDateEnd', 
            'RPDateBeg', 'RPDateEnd', 'DateUpd'
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
                $rowArray['lccode'] ?? '',
                $rowArray['billgroup'] ?? '',
                $rowArray['cscode'] ?? '',
                $rowArray['tmlt'] ?? '',
                $rowArray['loinc'] ?? '',
                $rowArray['panel'] ?? '',
                $rowArray['name'] ?? '',
                $rowArray['sflag'] ?? '',
                $rowArray['chargecat'] ?? '',
                $this->formatChiPrice($rowArray['unitprice'] ?? null),
                $rowArray['benefitplan'] ?? '',
                $this->formatChiPrice($rowArray['reimbprice'] ?? null),
                $rowArray['updateflag'] ?? '',
                $this->formatChiIsoDateTime($rowArray['updatebeg'] ?? null),
                $this->formatChiIsoDateTime($rowArray['updateend'] ?? null),
                $this->formatChiIsoDateTime($rowArray['rpdatebeg'] ?? null),
                $this->formatChiIsoDateTime($rowArray['rpdateend'] ?? null),
                $this->formatChiIsoDateTime($rowArray['dateupd'] ?? null)
            ];

            $colIndex = 1;
            foreach ($rowData as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->setCellValue($colLetter . $rowNum, $val);
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

    private function formatChiIsoDateTime($value, $default = '')
    {
        if ($value === null || trim($value) === '' || trim($value) === '-') {
            return $default;
        }
        $val = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $val)) {
            return $val;
        }
        try {
            $cleaned = str_replace('T', ' ', $val);
            $dt = new \DateTime($cleaned);
            return $dt->format('Y-m-d\TH:i:s');
        } catch (\Exception $e) {
            return $default;
        }
    }

    private function formatChiPrice($value)
    {
        if ($value === null || trim($value) === '' || trim($value) === '-') {
            return '0.00';
        }
        $val = str_replace(',', '', $value);
        if (!is_numeric($val)) {
            return '0.00';
        }
        return number_format((float)$val, 2, '.', '');
    }
}
