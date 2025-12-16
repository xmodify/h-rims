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
        $nhso_endpoint_pull_yesterday=route('nhso_endpoint_pull_yesterday');      
        $data = MainSetting::orderBy('name_th', 'asc')->get();
        return view('admin.main_setting', compact('data','notify_summary','nhso_endpoint_pull_yesterday'));
    }
// Update Table main_setting------------------------------------------------------------------------------
    public function update(Request $request, $id)
    {
        $request->validate([           
            'value' => 'nullable|string',
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
    //Update Table main_setting-----------------------------------------------------------------------------------------------------------
        $main_setting = [
            ['id' => 1, 'name_th' => 'IPD จำนวนเตียง', 'name' => 'bed_qty', 'value' => ''],
            ['id' => 2, 'name_th' => 'Token Authen Kiosk สปสช.', 'name' => 'token_authen_kiosk_nhso', 'value' => ''],
            ['id' => 3, 'name_th' => 'Telegram Token', 'name' => 'telegram_token', 'value' => ''],
            ['id' => 4, 'name_th' => 'Telegram ChatID Notify_Summary', 'name' => 'telegram_chat_id', 'value' => ''], 
            ['id' => 5, 'name_th' => 'IPD ค่า K ', 'name' => 'k_value', 'value' => '1'],   
            ['id' => 6, 'name_th' => 'IPD BaseRate UCS ในเขต', 'name' => 'base_rate', 'value' => '8350'],
            ['id' => 7, 'name_th' => 'IPD BaseRate UCS นอกเขต', 'name' => 'base_rate2', 'value' => '9600'],  
            ['id' => 8, 'name_th' => 'IPD BaseRate OFC', 'name' => 'base_rate_ofc', 'value' => '6200'],  
            ['id' => 9, 'name_th' => 'IPD BaseRate LGO', 'name' => 'base_rate_lgo', 'value' => '6194'],  
            ['id' => 10, 'name_th' => 'IPD BaseRate SSS', 'name' => 'base_rate_sss', 'value' => '6200'],           
            ['id' => 11, 'name_th' => 'สิทธิ พรบ. (รหัสสิทธิ HOSxP)', 'name' => 'pttype_act', 'value' => '29'],
            ['id' => 12, 'name_th' => 'สิทธิ ปกส. กองทุนทดแทน (รหัสสิทธิ HOSxP)', 'name' => 'pttype_sss_fund', 'value' => '"S6",25,31'],
            ['id' => 13, 'name_th' => 'สิทธิ ตรวจสุขภาพหน่วยงานภาครัฐ (รหัสสิทธิ HOSxP)', 'name' => 'pttype_checkup', 'value' => '14,27'],
            ['id' => 14, 'name_th' => 'สิทธิ ประกันชีวิต iClaim (รหัสสิทธิ HOSxP)', 'name' => 'pttype_iclaim', 'value' => '26'],
            ['id' => 15, 'name_th' => 'สิทธิ ปกส. 72 ชั่วโมงแรก (รหัสสิทธิ HOSxP)', 'name' => 'pttype_sss_72', 'value' => '32'],
            ['id' => 16, 'name_th' => 'LAB Pregnancy Test (รหัส lab_items HOSxP)', 'name' => 'lab_prt', 'value' => '444'],
            ['id' => 17, 'name_th' => 'ยา Clopidogrel (รหัส drugitems HOSxP)', 'name' => 'drug_clopidogrel', 'value' => '1520019'],
            ['id' => 18, 'name_th' => 'ชื่อโรงพยาบาล', 'name' => 'hospital_name', 'value' => 'โรงพยาบาลหัวตะพาน'],
            ['id' => 19, 'name_th' => 'รหัส 5 หลักโรงพยาบาล', 'name' => 'hospital_code', 'value' => '10989'],
            ['id' => 20, 'name_th' => 'OPOH Token', 'name' => 'opoh_token', 'value' => ''],
            ['id' => 21, 'name_th' => 'FDH User', 'name' => 'fdh_user', 'value' => ''],
            ['id' => 22, 'name_th' => 'FDH Pass', 'name' => 'fdh_pass', 'value' => ''],
            ['id' => 23, 'name_th' => 'FDH Secret Key', 'name' => 'fdh_secretKey', 'value' => '$jwt@moph#'],
        ];
        
        foreach ($main_setting as $row) {
            $check = MainSetting::where('id', $row['id'])->count();
            if ($check > 0) {
                DB::table('main_setting')
                ->where('id', $row['id']) 
                ->update([
                    'name_th' => $row['name_th'],
                    'name' => $row['name'],
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

    //After Table-----------------------------------------------------------------------------------------------------------
        $tables = [

            // ---------------- lookup ----------------
            'lookup_icode' => [
                ['name' => 'ems', 'type' => 'VARCHAR(1) NULL', 'after' => 'kidney'],
            ],
            'lookup_ward' => [
                ['name' => 'ward_normal', 'type' => 'VARCHAR(1) NULL', 'after' => 'ward_name'],
                ['name' => 'bed_qty', 'type' => 'INT UNSIGNED NULL', 'after' => 'ward_homeward'],
            ],
            // ---------------- STM ----------------
            'stm_lgo' => [
                ['name' => 'round_no',   'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
                ['name' => 'receive_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'stm_filename'],
            ],
            'stm_lgo_kidney' => [
                ['name' => 'round_no',   'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
                ['name' => 'receive_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'stm_filename'],
            ],
            'stm_ofc' => [
                ['name' => 'round_no',   'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
                ['name' => 'receive_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'stm_filename'],
            ],
            'stm_ofc_kidney' => [
                ['name' => 'round_no',   'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
                ['name' => 'receive_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'hdflag'],
            ],
            'stm_sss_kidney' => [
                ['name' => 'round_no',   'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
                ['name' => 'receive_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'hdflag'],
            ],
            'stm_ucs' => [
                ['name' => 'round_no',   'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
                ['name' => 'receive_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'stm_filename'],
            ],
            'stm_ucs_kidney' => [
                ['name' => 'round_no',   'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
                ['name' => 'receive_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'stm_filename'],
            ],
            // ---------------- STM EXCEL (staging) ----------------
            'stm_lgo_kidneyexcel' => [
                ['name' => 'round_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
            ],
            'stm_lgoexcel' => [
                ['name' => 'round_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
            ],
            'stm_ofcexcel' => [
                ['name' => 'round_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
            ],
            'stm_ucs_kidneyexcel' => [
                ['name' => 'round_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
            ],
            'stm_ucsexcel' => [
                ['name' => 'round_no', 'type' => 'VARCHAR(20) NULL', 'after' => 'id'],
            ],
        ];
        try {
            foreach ($tables as $table => $columns) {
                // ✅ ต้องมี table ก่อน
                if (!Schema::hasTable($table)) {
                    continue;
                }
                foreach ($columns as $col) {
                    if (!Schema::hasColumn($table, $col['name'])) {
                        // ตำแหน่ง column
                        $afterSql = '';
                        if (
                            isset($col['after']) &&
                            $col['after'] !== '' &&
                            Schema::hasColumn($table, $col['after'])
                        ) {
                            $afterSql = " AFTER `{$col['after']}`";
                        }
                        DB::statement("
                            ALTER TABLE `$table`
                            ADD COLUMN `{$col['name']}` {$col['type']}{$afterSql}
                        ");
                    }
                }
            }

    // CREATE TABLE fdh_claim_status ----------------------------------------------------------------------------------------
        if (!Schema::hasTable('fdh_claim_status')) {
            DB::statement("
                CREATE TABLE `fdh_claim_status` (
                    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
                    `hn` VARCHAR(50) NOT NULL,
                    `seq` VARCHAR(50) DEFAULT NULL,
                    `an` VARCHAR(50) DEFAULT NULL,
                    `hcode` VARCHAR(10) NOT NULL,
                    `status` VARCHAR(50) NOT NULL,
                    `process_status` VARCHAR(10) DEFAULT NULL,
                    `status_message_th` VARCHAR(255) DEFAULT NULL,
                    `stm_period` VARCHAR(50) DEFAULT NULL,
                    `created_at` TIMESTAMP NULL DEFAULT NULL,
                    `updated_at` TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hn` (`hn`),
                    KEY `idx_an` (`an`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }
        // END --------------------------------------------------------------------------------------------------------
            return redirect()->route('admin.main_setting')
                ->with('success', 'Upgrade Structure สำเร็จ');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
