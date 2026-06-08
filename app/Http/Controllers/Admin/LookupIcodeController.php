<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LookupIcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LookupIcodeController extends Controller
{
    public function index()
    {
        $all = LookupIcode::all();
        $uc_cr = $all->where('uc_cr', 'Y');
        $ppfs = $all->where('ppfs', 'Y');
        $herb32 = $all->where('herb32', 'Y');
        $kidney = $all->where('kidney', 'Y');
        $ems = $all->where('ems', 'Y');
        $sss_hc = $all->where('sss_hc', 'Y');

        $ppfs_rules = require config_path('claims/ppfs_rules.php');
        $valid_ppfs_adps = array_keys($ppfs_rules);

        // ตรวจสอบข้อมูลรหัส PPFS ที่ไม่มีใน HOSxP
        $existing_nondrug = DB::connection('hosxp')
            ->table('nondrugitems')
            ->whereIn('nhso_adp_code', $valid_ppfs_adps)
            ->pluck('nhso_adp_code')
            ->toArray();

        $existing_drugs = DB::connection('hosxp')
            ->table('drugitems')
            ->whereIn('nhso_adp_code', $valid_ppfs_adps)
            ->pluck('nhso_adp_code')
            ->toArray();

        $existing_adps_in_hosxp = array_unique(array_merge($existing_nondrug, $existing_drugs));
        $missing_adp_codes = array_diff($valid_ppfs_adps, $existing_adps_in_hosxp);

        $missing_ppfs_details = [];
        foreach ($missing_adp_codes as $code) {
            $rule = $ppfs_rules[$code];
            if (isset($rule['amount']) && $rule['amount'] > 0) {
                $rule['source'] = 'PPFS';
                $missing_ppfs_details[$code] = $rule;
            }
        }

        // ตรวจสอบข้อมูลรหัส Instrument ที่ไม่มีใน HOSxP
        $ins_rules = require config_path('claims/ins_ucs_rules.php');
        $valid_ins_adps = array_keys($ins_rules);

        $existing_ins_nondrug = DB::connection('hosxp')
            ->table('nondrugitems')
            ->whereIn('nhso_adp_code', $valid_ins_adps)
            ->pluck('nhso_adp_code')
            ->toArray();

        $existing_ins_drugs = DB::connection('hosxp')
            ->table('drugitems')
            ->whereIn('nhso_adp_code', $valid_ins_adps)
            ->pluck('nhso_adp_code')
            ->toArray();

        $existing_ins_adps_in_hosxp = array_unique(array_merge($existing_ins_nondrug, $existing_ins_drugs));
        $missing_ins_adp_codes = array_diff($valid_ins_adps, $existing_ins_adps_in_hosxp);

        $missing_ins_details = [];
        foreach ($missing_ins_adp_codes as $code) {
            $rule = $ins_rules[$code];
            if (isset($rule['amount']) && $rule['amount'] > 0) {
                $rule['source'] = 'INSTRUMENT';
                $missing_ins_details[$code] = $rule;
            }
        }

        $total_missing_count = count($missing_ppfs_details) + count($missing_ins_details);

        // แยก UC-CR เป็น Instrument และ Other
        $uc_cr_instrument = $uc_cr->filter(function($item) use ($valid_ins_adps) {
            return in_array($item->nhso_adp_code, $valid_ins_adps);
        });
        $uc_cr_other = $uc_cr->reject(function($item) use ($valid_ins_adps) {
            return in_array($item->nhso_adp_code, $valid_ins_adps);
        });

        return view('admin.lookup_icode.index', compact(
            'all', 'uc_cr', 'ppfs', 'herb32', 'kidney', 'ems', 'sss_hc', 
            'valid_ppfs_adps', 'missing_ppfs_details', 'missing_ins_details', 'total_missing_count',
            'uc_cr_instrument', 'uc_cr_other', 'valid_ins_adps', 'ins_rules'
        ));
    }

    public function create()
    {
        return view('admin.lookup_icode.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'icode' => 'required|unique:lookup_icode,icode',
            'name' => 'required',
        ]);

        LookupIcode::create($request->all());

        return redirect()->route('admin.lookup_icode.index')->with('success', 'เพิ่มข้อมูลสำเร็จ');
    }

    public function show(LookupIcode $icode)
    {
        //
    }

    public function edit($icode)
    {
        $item = LookupIcode::findOrFail($icode);
        return view('admin.lookup_icode.edit', compact('item'));
    }

    public function update(Request $request, $icode)
    {
        $item = LookupIcode::findOrFail($icode);

        $request->validate([
            'icode' => 'required|unique:lookup_icode,icode,' . $icode . ',icode',
            'name' => 'required'
        ]);

        $data = [
            'name' => $request->name,
            'nhso_adp_code' => $request->nhso_adp_code,
            'uc_cr' => $request->has('uc_cr') ? 'Y' : '',
            'ppfs' => $request->has('ppfs') ? 'Y' : '',
            'herb32' => $request->has('herb32') ? 'Y' : '',
            'kidney' => $request->has('kidney') ? 'Y' : '',
            'ems' => $request->has('ems') ? 'Y' : '',
            'sss_hc' => $request->has('sss_hc') ? 'Y' : '',
        ];

        $item->update($data);

        return redirect()->route('admin.lookup_icode.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }


    public function destroy($icode)
    {
        LookupIcode::destroy($icode);
        return redirect()->route('admin.lookup_icode.index')->with('success', 'ลบข้อมูลเรียบร้อย');
    }

    public function insert_lookup_uc_cr(Request $request)
    {
        $ins_rules = require config_path('claims/ins_ucs_rules.php');
        $ins_adps = array_keys($ins_rules);

        if (empty($ins_adps)) {
            $ins_adps = ['INVALID_CODE_HOLDER'];
        }

        $placeholders = implode(',', array_fill(0, count($ins_adps), '?'));

        $query = '
            SELECT n.icode, n.`name`, n.nhso_adp_code, "Y" AS uc_cr 
            FROM nondrugitems n
            WHERE n.icode NOT IN (SELECT icode FROM hrims.lookup_icode WHERE uc_cr = "Y" AND COALESCE(nhso_adp_code, "") = COALESCE(n.nhso_adp_code, ""))
            AND n.nhso_adp_code IS NOT NULL AND n.nhso_adp_code <> ""
            AND (
                (n.nhso_adp_type_id = "02" AND n.istatus = "Y")
                OR n.nhso_adp_code IN ("TELMED","DRUGP","Cons01","Eva001","30001","80001","80002","80003",
                "80004","80005","80006","80007","80008","80015","80024","80025","80026","80027","80028")
                OR n.nhso_adp_code IN (' . $placeholders . ')
            )
            UNION
            SELECT d.icode, d.`name`, d.nhso_adp_code, "Y" AS uc_cr
            FROM drugitems d
            WHERE d.icode NOT IN (SELECT icode FROM hrims.lookup_icode WHERE uc_cr = "Y" AND COALESCE(nhso_adp_code, "") = COALESCE(d.nhso_adp_code, ""))
            AND (
                d.nhso_adp_code IN ("STEMI1")
                OR d.nhso_adp_code IN (' . $placeholders . ')
            )';

        $params = array_merge($ins_adps, $ins_adps);
        $hosxp_data = DB::connection('hosxp')->select($query, $params);

        foreach ($hosxp_data as $row) {
            $check = LookupIcode::where('icode', $row->icode)->count();
            if ($check > 0) {
                DB::table('lookup_icode')
                    ->where('icode', $row->icode) // เพิ่มบรรทัดนี้เพื่อ update เฉพาะ record
                    ->update([
                        'name' => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'uc_cr' => "Y",
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('lookup_icode')
                    ->insert([
                        'icode' => $row->icode,
                        'name' => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'uc_cr' => "Y",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
        return redirect()->route('admin.lookup_icode.index')->with('success', 'นำเข้าข้อมูลสำเร็จ');
    }

    public function insert_lookup_ppfs(Request $request)
    {
        $rules = require config_path('claims/ppfs_rules.php');
        $adp_codes = array_keys($rules);

        if (empty($adp_codes)) {
            return redirect()->route('admin.lookup_icode.index')->with('error', 'ไม่พบรหัส ADP ในไฟล์เงื่อนไข claims/ppfs_rules.php');
        }

        $placeholders = implode(',', array_fill(0, count($adp_codes), '?'));

        $query = '
            SELECT n.icode, n.`name`, n.nhso_adp_code, "Y" AS ppfs 
            FROM nondrugitems n
            WHERE n.icode NOT IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y" AND COALESCE(nhso_adp_code, "") = COALESCE(n.nhso_adp_code, ""))
                AND n.istatus = "Y" 
                AND n.nhso_adp_code IS NOT NULL AND n.nhso_adp_code <> ""
                AND n.nhso_adp_code IN (' . $placeholders . ')
            UNION
            SELECT d.icode, d.`name`, d.nhso_adp_code, "Y" AS ppfs
            FROM drugitems d
                WHERE d.icode NOT IN (SELECT icode FROM hrims.lookup_icode WHERE ppfs = "Y" AND COALESCE(nhso_adp_code, "") = COALESCE(d.nhso_adp_code, ""))
                AND d.nhso_adp_code IN (' . $placeholders . ')';

        $params = array_merge($adp_codes, $adp_codes);
        $hosxp_data = DB::connection('hosxp')->select($query, $params);

        foreach ($hosxp_data as $row) {
            $check = LookupIcode::where('icode', $row->icode)->count();
            if ($check > 0) {
                DB::table('lookup_icode')
                    ->where('icode', $row->icode) // เพิ่มบรรทัดนี้เพื่อ update เฉพาะ record
                    ->update([
                        'name' => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'ppfs' => "Y",
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('lookup_icode')
                    ->insert([
                        'icode' => $row->icode,
                        'name' => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'ppfs' => "Y",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
        return redirect()->route('admin.lookup_icode.index')->with('success', 'นำเข้าข้อมูลสำเร็จ');
    }
    public function insert_lookup_herb32(Request $request)
    {
        $hosxp_data = DB::connection('hosxp')->select('
            SELECT icode,CONCAT(`name`,strength) AS name,nhso_adp_code,"Y" AS herb32 
            FROM drugitems 
            WHERE icode NOT IN (SELECT icode FROM hrims.lookup_icode WHERE herb32 = "Y" AND COALESCE(nhso_adp_code, "") = COALESCE(drugitems.nhso_adp_code, ""))
            AND (ttmt_code <>"" OR ttmt_code IS NOT NULL) ');

        foreach ($hosxp_data as $row) {
            $check = LookupIcode::where('icode', $row->icode)->count();
            if ($check > 0) {
                DB::table('lookup_icode')
                    ->where('icode', $row->icode) // เพิ่มบรรทัดนี้เพื่อ update เฉพาะ record
                    ->update([
                        'name' => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'herb32' => "Y",
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('lookup_icode')
                    ->insert([
                        'icode' => $row->icode,
                        'name' => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'herb32' => "Y",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
        return redirect()->route('admin.lookup_icode.index')->with('success', 'นำเข้าข้อมูลสำเร็จ');
    }

    public function insert_lookup_sss_hc(Request $request)
    {
        $hosxp_data = DB::connection('hosxp')->select('
            SELECT n.icode, n.`name`, n.nhso_adp_code, "Y" AS sss_hc 
            FROM nondrugitems n
            INNER JOIN hrims.sss_equipdev_aipn a ON a.`code` = n.nhso_adp_code AND a.dateexp >= DATE(NOW())
            WHERE n.istatus = "Y"');

        foreach ($hosxp_data as $row) {
            $check = LookupIcode::where('icode', $row->icode)->count();
            if ($check > 0) {
                DB::table('lookup_icode')
                    ->where('icode', $row->icode)
                    ->update([
                        'name'          => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'sss_hc'        => 'Y',
                        'updated_at'    => now(),
                    ]);
            } else {
                DB::table('lookup_icode')
                    ->insert([
                        'icode'         => $row->icode,
                        'name'          => $row->name,
                        'nhso_adp_code' => $row->nhso_adp_code,
                        'sss_hc'        => 'Y',
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
            }
        }
        return redirect()->route('admin.lookup_icode.index')->with('success', 'นำเข้า SSS-HC สำเร็จ');
    }

    public function search_items(Request $request)
    {
        $search = $request->q;

        $sql = '
            SELECT icode, name, nhso_adp_code 
            FROM nondrugitems 
            WHERE (icode LIKE ? OR name LIKE ? OR nhso_adp_code LIKE ?) 
            AND istatus = "Y"
            AND icode NOT IN (SELECT icode FROM hrims.lookup_icode)
            UNION
            SELECT icode, name, nhso_adp_code 
            FROM drugitems 
            WHERE (icode LIKE ? OR name LIKE ? OR nhso_adp_code LIKE ?) 
            AND istatus = "Y"
            AND icode NOT IN (SELECT icode FROM hrims.lookup_icode)
            LIMIT 50';

        $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
        $items = DB::connection('hosxp')->select($sql, $params);

        return response()->json($items);
    }
}
