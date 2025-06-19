<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MainSetting;

class MainSettingController extends Controller
{
    public function index()
    {
        $data = MainSetting::all();
        return view('admin.main_setting', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([           
            'value' => 'required|string',
        ]);

        $setting = MainSetting::findOrFail($id);        
        $setting->value = $request->value;
        $setting->save();

return redirect()->back()->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }
    
}
