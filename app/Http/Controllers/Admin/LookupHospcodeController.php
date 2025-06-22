<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LookupHospcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LookupHospcodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = LookupHospcode::all();
        return view('admin.lookup_hospcode.index', compact('data'));
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
            'hospcode' => 'required|unique:lookup_hospcode,hospcode',
            'hospcode_name' => 'required',
        ]);

        LookupHospcode::create($request->all());

        return redirect()->route('admin.lookup_hospcode.index')->with('success', 'เพิ่มข้อมูลสำเร็จ');
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $hospcode)
    {
        $item = LookupHospcode::findOrFail($hospcode);

        $request->validate([
            'hospcode' => 'required|unique:lookup_hospcode,hospcode,' . $hospcode . ',hospcode',
            'hospcode_name' => 'required'
        ]);

        $data = [           
            'hospcode_name' => $request->hospcode_name,
            'hmain_ucs' => $request->has('hmain_ucs') ? 'Y' : '',
            'hmain_sss' => $request->has('hmain_sss') ? 'Y' : '',
            'in_province' => $request->has('in_province') ? 'Y' : '',
        ]; 

        $item->update($data);

        return redirect()->route('admin.lookup_hospcode.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $hospcode)
    {
        LookupHospcode::destroy($hospcode);
        return redirect()->route('admin.lookup_hospcode.index')->with('success', 'ลบข้อมูลเรียบร้อย');
    }
}
