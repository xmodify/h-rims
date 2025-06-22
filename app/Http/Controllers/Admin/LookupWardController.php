<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LookupWard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LookupWardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = LookupWard::all();
        return view('admin.lookup_ward.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $request->validate([
            'ward' => 'required|unique:lookup_ward,ward',
            'ward_name' => 'required',
        ]);

        LookupWard::create($request->all());

        return redirect()->route('admin.lookup_ward.index')->with('success', 'เพิ่มข้อมูลสำเร็จ');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $ward)
    {
        $item = LookupWard::findOrFail($ward);
        return view('admin.lookup_ward.edit', compact('item'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $ward)
    {
        $item = LookupWard::findOrFail($ward);

        $request->validate([
            'ward' => 'required|unique:lookup_ward,ward,' . $ward . ',ward',
            'ward_name' => 'required'
        ]);

        $data = [           
            'ward_name' => $request->ward_name,
            'ward_m' => $request->has('ward_m') ? 'Y' : '',
            'ward_f' => $request->has('ward_f') ? 'Y' : '',
            'ward_vip' => $request->has('ward_vip') ? 'Y' : '',
            'ward_lr' => $request->has('ward_lr') ? 'Y' : '',
            'ward_homeward' => $request->has('ward_homeward') ? 'Y' : '',

        ]; 

        $item->update($data);

        return redirect()->route('admin.lookup_ward.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function destroy(string $ward)
    {
        LookupWard::destroy($ward);
        return redirect()->route('admin.lookup_ward.index')->with('success', 'ลบข้อมูลเรียบร้อย');
    }

   public function insert_lookup_ward(Request $request)
{
    $hosxp_data = DB::connection('hosxp')->select('
        SELECT ward, `name` AS ward_name FROM ward WHERE ward_active = "Y"');

    foreach ($hosxp_data as $row) {
        $check = LookupWard::where('ward', $row->ward)->count();

        if ($check > 0) {
            DB::table('lookup_ward')
                ->where('ward', $row->ward) // เพิ่มบรรทัดนี้เพื่อ update เฉพาะ record
                ->update([
                    'ward_name' => $row->ward_name
                ]);
        } else {
            DB::table('lookup_ward')->insert([
                'ward' => $row->ward,
                'ward_name' => $row->ward_name
            ]);
        }
    }

    return redirect()->route('admin.lookup_ward.index')->with('success', 'นำเข้าข้อมูลสำเร็จ');
}

}
