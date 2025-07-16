<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MainSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MainSettingController extends Controller
{

    public function index()
    {
        $notify_summary=route('notify_summary');
        $data = MainSetting::all();
        return view('admin.main_setting', compact('data','notify_summary'));
    }
// Update Table main_setting------------------------------------------------------------------------------
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
#######################################################################################################################################    
// UP Structure -----------------------------------------------------------------------------------------------------------------------    
    public function up_structure(Request $request)
    {
    //Table main_setting-------------------------------------------------------------------------------------------------------------
        $main_setting = [
            ['id' => 1, 'name_th' => 'จำนวนเตียง', 'name' => 'bed_qty', 'value' => ''],
            ['id' => 2, 'name_th' => 'Token Authen Kiosk สปสช.', 'name' => 'token_authen_kiosk_nhso', 'value' => ''],
            ['id' => 3, 'name_th' => 'Telegram Token', 'name' => 'telegram_token', 'value' => ''],
            ['id' => 4, 'name_th' => 'Telegram Chat ID Notify_Summary', 'name' => 'telegram_chat_id', 'value' => ''], 
            ['id' => 5, 'name_th' => 'ค่า K ', 'name' => 'k_value', 'value' => '1'],   
            ['id' => 6, 'name_th' => 'Base Rate UCS ในเขต', 'name' => 'base_rate', 'value' => '8350'],
            ['id' => 7, 'name_th' => 'Base Rate UCS นอกเขต', 'name' => 'base_rate2', 'value' => '9600'],  
            ['id' => 8, 'name_th' => 'Base Rate OFC', 'name' => 'base_rate_ofc', 'value' => '6200'],  
            ['id' => 9, 'name_th' => 'Base Rate LGO', 'name' => 'base_rate_lgo', 'value' => '6194'],  
            ['id' => 10, 'name_th' => 'Base Rate SSS', 'name' => 'base_rate_sss', 'value' => '8350'],
        ];
        
        foreach ($main_setting as $row) {
            $check = MainSetting::where('id', $row['id'])->count();
            if ($check > 0) {
                DB::table('main_setting')
                ->where('id', $row['id']) 
                ->update([
                    'name_th' => $row['name_th'],
                ]);
            } else {
                DB::table('main_setting')
                ->insert([
                    'id' => $row['id'],
                    'name_th' => $row['name_th'],
                    'name' => $row['name'],
                    'value' => $row['value'],
                ]);
            }
        }
    //Table lookup_icode-----------------------------------------------------------------------------------------------------------
        $table = 'lookup_icode';
        $columnsToAdd = [        
            ['name' => 'herb32', 'definition' => 'VARCHAR(1) NULL'], 
            ['name' => 'kidney', 'definition' => 'VARCHAR(1) NULL AFTER `herb32`'],
            ['name' => 'ems', 'definition' => 'VARCHAR(1) NULL AFTER `kidney`']
        ];

        try {
            foreach ($columnsToAdd as $col) {
                if (!Schema::hasColumn($table, $col['name'])) {
                    DB::statement("ALTER TABLE $table ADD COLUMN {$col['name']} {$col['definition']}");
                }
            }

            return redirect()->route('admin.main_setting')->with('success', 'ปรับโครงสร้างสำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
