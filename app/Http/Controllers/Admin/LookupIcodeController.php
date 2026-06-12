<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LookupIcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // ดึงข้อมูล ADP จาก HOSxP สำหรับ PPFS (เฉพาะ nondrugitems ที่มีสถานะใช้งาน)
        $hosxp_ppfs_all = DB::connection('hosxp')
            ->table('nondrugitems')
            ->whereIn('nhso_adp_code', $valid_ppfs_adps)
            ->where('istatus', 'Y')
            ->select('nhso_adp_code', 'icode', 'price')
            ->get()
            ->groupBy('nhso_adp_code');

        $ppfs_details = [];
        foreach ($valid_ppfs_adps as $code) {
            $rule = $ppfs_rules[$code];
            $hosxp_items = $hosxp_ppfs_all->get($code);
            $found_codes = [];
            $found_prices = [];
            if ($hosxp_items) {
                foreach ($hosxp_items as $h_item) {
                    $found_codes[] = $h_item->icode;
                    $found_prices[] = number_format($h_item->price, 2);
                }
            }
            $rule['source'] = 'PPFS';
            $rule['hosxp_icode'] = !empty($found_codes) ? implode(', ', $found_codes) : 'ไม่พบ';
            $rule['hosxp_price'] = !empty($found_prices) ? implode(', ', $found_prices) : '-';
            $ppfs_details[$code] = $rule;
        }

        $ins_rules = [];
        if (Schema::hasTable('lookup_nhso_adp_code')) {
            $records = DB::table('lookup_nhso_adp_code')->where('nhso_adp_type_id', 2)->get();
            foreach ($records as $r) {
                $ins_rules[$r->nhso_adp_code] = [
                    'name' => $r->nhso_adp_code_name,
                    'category' => $r->category,
                    'prices' => [
                        'UCS' => floatval($r->price_ucs),
                        'OFC' => floatval($r->price_ofc),
                        'SSS' => floatval($r->price_sss),
                        'LGO' => floatval($r->price_lgo),
                        'FS' => floatval($r->price_fs),
                        'UCEP' => floatval($r->price_ucep),
                    ],
                    'ins_ucs' => $r->ins_ucs,
                    'ins_ofc' => $r->ins_ofc,
                ];
            }
        }
        $all_ins_adps = array_keys($ins_rules);
        $valid_ins_adps = array_keys(array_filter($ins_rules, fn($r) => ($r['prices']['UCS'] ?? 0) > 0)); // ใช้กรองหน้า UC-CR

        // ดึงข้อมูล ADP จาก HOSxP สำหรับ Instrument ทั้งหมด (เฉพาะ nondrugitems ที่มีสถานะใช้งาน)
        $hosxp_ins_all = DB::connection('hosxp')
            ->table('nondrugitems')
            ->whereIn('nhso_adp_code', $all_ins_adps)
            ->where('istatus', 'Y')
            ->select('nhso_adp_code', 'icode', 'price')
            ->get()
            ->groupBy('nhso_adp_code');

        $ins_details = [];
        foreach ($all_ins_adps as $code) {
            $rule = $ins_rules[$code];
            $hosxp_items = $hosxp_ins_all->get($code);
            $found_codes = [];
            $found_prices = [];
            if ($hosxp_items) {
                foreach ($hosxp_items as $h_item) {
                    $found_codes[] = $h_item->icode;
                    $found_prices[] = number_format($h_item->price, 2);
                }
            }
            $rule['source'] = 'INSTRUMENT';
            $rule['hosxp_icode'] = !empty($found_codes) ? implode(', ', $found_codes) : 'ไม่พบ';
            $rule['hosxp_price'] = !empty($found_prices) ? implode(', ', $found_prices) : '-';
            $ins_details[$code] = $rule;
        }

        $total_rules_count = count($ppfs_details) + count($ins_details);

        // แยก UC-CR เป็น Instrument (เฉพาะที่ UCS > 0) และ Other (ที่ไม่ใช่รหัสใน Instrument)
        $uc_cr_icodes = $uc_cr->pluck('icode')->toArray();
        $hosxp_prices = [];
        if (!empty($uc_cr_icodes)) {
            $placeholders = implode(',', array_fill(0, count($uc_cr_icodes), '?'));
            $nondrug_prices = DB::connection('hosxp')->select(
                "SELECT icode, price FROM nondrugitems WHERE icode IN ($placeholders)",
                $uc_cr_icodes
            );
            foreach ($nondrug_prices as $p) {
                $hosxp_prices[$p->icode] = floatval($p->price);
            }
            $drug_prices = DB::connection('hosxp')->select(
                "SELECT icode, unitprice AS price FROM drugitems WHERE icode IN ($placeholders)",
                $uc_cr_icodes
            );
            foreach ($drug_prices as $p) {
                $hosxp_prices[$p->icode] = floatval($p->price);
            }
        }

        $uc_cr_instrument = $uc_cr->filter(function($item) use ($valid_ins_adps) {
            return in_array($item->nhso_adp_code, $valid_ins_adps);
        });
        $uc_cr_other = $uc_cr->reject(function($item) use ($valid_ins_adps) {
            return in_array($item->nhso_adp_code, $valid_ins_adps);
        });

        $sss_prices = [];
        if (Schema::hasTable('lookup_sss_equipdev_aipn')) {
            $sss_records = DB::table('lookup_sss_equipdev_aipn')
                ->where('dateexp', '>=', DB::raw('DATE(NOW())'))
                ->select('code', 'rate')
                ->get();
            foreach ($sss_records as $r) {
                $sss_prices[$r->code] = floatval($r->rate);
            }
        }

        return view('admin.lookup_icode.index', compact(
            'all', 'uc_cr', 'ppfs', 'herb32', 'kidney', 'ems', 'sss_hc', 
            'valid_ppfs_adps', 'ppfs_details', 'ins_details', 'total_rules_count',
            'uc_cr_instrument', 'uc_cr_other', 'valid_ins_adps', 'ins_rules',
            'hosxp_prices', 'sss_prices'
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
        $ins_rules = [];
        if (Schema::hasTable('lookup_nhso_adp_code')) {
            $records = DB::table('lookup_nhso_adp_code')->where('nhso_adp_type_id', 2)->get();
            foreach ($records as $r) {
                $ins_rules[$r->nhso_adp_code] = [
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
        }
        $ins_adps = array_keys(array_filter($ins_rules, fn($r) => ($r['prices']['UCS'] ?? 0) > 0));

        if (empty($ins_adps)) {
            $ins_adps = ['INVALID_CODE_HOLDER'];
        }

        $placeholders = implode(',', array_fill(0, count($ins_adps), '?'));

        $query = '
            SELECT n.icode, n.`name`, n.nhso_adp_code, "Y" AS uc_cr 
            FROM nondrugitems n
            WHERE n.icode NOT IN (SELECT icode FROM hrims.lookup_icode WHERE uc_cr = "Y" AND COALESCE(nhso_adp_code, "") = COALESCE(n.nhso_adp_code, ""))
            AND n.nhso_adp_code IS NOT NULL AND n.nhso_adp_code <> ""
            AND n.istatus = "Y"
            AND (
                n.nhso_adp_code IN ("TELMED","DRUGP","Cons01","Eva001","30001","80001","80002","80003",
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
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'นำเข้าข้อมูล UC_CR สำเร็จ']);
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
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'นำเข้าข้อมูล PPFS สำเร็จ']);
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
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'นำเข้าข้อมูล Herb32 สำเร็จ']);
        }
        return redirect()->route('admin.lookup_icode.index')->with('success', 'นำเข้าข้อมูลสำเร็จ');
    }

    public function insert_lookup_sss_hc(Request $request)
    {
        $hosxp_data = DB::connection('hosxp')->select('
            SELECT n.icode, n.`name`, n.nhso_adp_code, "Y" AS sss_hc 
            FROM nondrugitems n
            INNER JOIN hrims.lookup_sss_equipdev_aipn a ON a.`code` = n.nhso_adp_code AND a.dateexp >= DATE(NOW())
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
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'นำเข้า SSS-HC สำเร็จ']);
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
