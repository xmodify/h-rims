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
        $data = LookupIcode::all();
        return view('admin.lookup_icode.index', compact('data'));
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

    public function show(Lookup_icode $icode)
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
        $hosxp_data = DB::connection('hosxp')->select('
            SELECT n.icode,n.`name`,n.nhso_adp_code,"Y" AS uc_cr 
            FROM nondrugitems n
            WHERE n.icode NOT IN (SELECT icode FROM hrims.lookup_icode)
            AND n.nhso_adp_type_id = "02" AND n.istatus = "Y"
            OR n.nhso_adp_code IN ("TELMED","DRUGP","Cons01","Eva001","30001","80001","80002","80003",
            "80004","80005","80006","80007","80008","80015","80024","80025","80026","80027","80028")
            UNION
            SELECT d.icode,d.`name`,d.nhso_adp_code,"Y" AS uc_cr
            FROM drugitems d
            WHERE d.icode NOT IN (SELECT icode FROM hrims.lookup_icode)
            AND d.nhso_adp_code IN ("STEMI1")');
        
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
        $hosxp_data = DB::connection('hosxp')->select('
            SELECT n.icode,n.`name`,n.nhso_adp_code,"Y" AS ppfs 
            FROM nondrugitems n
            WHERE n.icode NOT IN (SELECT icode FROM hrims.lookup_icode)
            AND n.istatus = "Y" AND n.nhso_adp_code IN ("12003","12004","13001","14001","15001"
            ,"30008","30009","30010","30011","30012","30013","30014","30015","30016","90005")
            UNION
            SELECT d.icode,d.`name`,d.nhso_adp_code,"Y" AS ppfs
            FROM drugitems d
            WHERE d.icode NOT IN (SELECT icode FROM hrims.lookup_icode)
            AND d.nhso_adp_code IN ("FP002_1","FP003_1","FP003_2")');
        
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
            WHERE icode NOT IN (SELECT icode FROM hrims.lookup_icode)
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

}
