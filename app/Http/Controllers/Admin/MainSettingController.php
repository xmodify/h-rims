<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MainSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MainSettingController extends Controller
{

    public function index()
    {
        $notify_summary=route('notify_summary');       
        $data = MainSetting::orderBy('name_th', 'asc')->get();
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
        DB::beginTransaction();

        try {
            // 1. อัพเดตหรือเพิ่ม main_setting
            $main_setting = [
                ['id' => 1, 'name_th' => 'IPD จำนวนเตียง', 'name' => 'bed_qty', 'value' => ''],
                ['id' => 2, 'name_th' => 'Token Authen Kiosk สปสช.', 'name' => 'token_authen_kiosk_nhso', 'value' => ''],
                ['id' => 3, 'name_th' => 'Telegram Token', 'name' => 'telegram_token', 'value' => ''],
                ['id' => 4, 'name_th' => 'Telegram Chat ID Notify_Summary', 'name' => 'telegram_chat_id', 'value' => ''], 
                ['id' => 5, 'name_th' => 'IPD ค่า K ', 'name' => 'k_value', 'value' => '1'],   
                ['id' => 6, 'name_th' => 'IPD BaseRate UCS ในเขต', 'name' => 'base_rate', 'value' => '8350'],
                ['id' => 7, 'name_th' => 'IPD BaseRate UCS นอกเขต', 'name' => 'base_rate2', 'value' => '9600'],  
                ['id' => 8, 'name_th' => 'IPD BaseRate OFC', 'name' => 'base_rate_ofc', 'value' => '6200'],  
                ['id' => 9, 'name_th' => 'IPD BaseRate LGO', 'name' => 'base_rate_lgo', 'value' => '6194'],  
                ['id' => 10, 'name_th' => 'IPD BaseRate SSS', 'name' => 'base_rate_sss', 'value' => '6200'],
                ['id' => 11, 'name_th' => 'สิทธิ พรบ. (รหัสสิทธิ HOSxP)', 'name' => 'pttype_act', 'value' => '29'],
                ['id' => 12, 'name_th' => 'สิทธิ ปกส.กองทุนทดแทน (รหัสสิทธิ HOSxP)', 'name' => 'pttype_sss_fund', 'value' => '"S6",25,31'],
                ['id' => 13, 'name_th' => 'สิทธิ ตรวจสุขภาพหน่วยงานภาครัฐ (รหัสสิทธิ HOSxP)', 'name' => 'pttype_checkup', 'value' => '14,27'],
                ['id' => 14, 'name_th' => 'สิทธิ ประกันชีวิต iClaim (รหัสสิทธิ HOSxP)', 'name' => 'pttype_iclaim', 'value' => '26'],
                ['id' => 15, 'name_th' => 'สิทธิ ปกส. 72 ชั่วโมงแรก (รหัสสิทธิ HOSxP)', 'name' => 'pttype_sss_72', 'value' => '32'],
                ['id' => 16, 'name_th' => 'LAB Pregnancy Test (รหัส lab_items HOSxP)', 'name' => 'lab_prt', 'value' => '444'],
                ['id' => 17, 'name_th' => 'ยา Clopidogrel (รหัส drugitems HOSxP)', 'name' => 'drug_clopidogrel', 'value' => '1520019'],
                ['id' => 18, 'name_th' => 'โรงพยาบาล ชื่อ', 'name' => 'hospital_name', 'value' => 'โรงพยาบาล'],
                ['id' => 19, 'name_th' => 'โรงพยาบาล รหัส 5 หลัก', 'name' => 'hospital_code', 'value' => '00000'],
            ];

            foreach ($main_setting as $row) {
                $exists = MainSetting::where('id', $row['id'])->exists();
                if ($exists) {
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

            // 2. เพิ่มคอลัมน์ใน lookup_icode
            $table_lookup = 'lookup_icode';
            $columnsToAdd_lookup = [        
                ['name' => 'herb32', 'definition' => 'VARCHAR(1) NULL'], 
                ['name' => 'kidney', 'definition' => 'VARCHAR(1) NULL AFTER `herb32`'],
                ['name' => 'ems', 'definition' => 'VARCHAR(1) NULL AFTER `kidney`']
            ];

            if (Schema::hasTable($table_lookup)) {
                foreach ($columnsToAdd_lookup as $col) {
                    if (!Schema::hasColumn($table_lookup, $col['name'])) {
                        DB::statement("ALTER TABLE $table_lookup ADD COLUMN {$col['name']} {$col['definition']}");
                    }
                }
            } 

            // 3.... 
            
            DB::commit();

            return redirect()->route('admin.main_setting')->with('success', 'Upgrade Structure สำเร็จ');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
