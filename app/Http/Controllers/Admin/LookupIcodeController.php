<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LookupIcode;
use Illuminate\Http\Request;

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


    public function show(Lookup_icode $lookup_icode)
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
}
