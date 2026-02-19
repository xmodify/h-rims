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
        $hospcode = DB::table('lookup_hospcode')->value('hospcode');

        $notify_summary = route('notify_summary');
        $nhso_endpoint_pull_yesterday = route('nhso_endpoint_pull_yesterday');

        $settings = MainSetting::orderBy('name_th', 'asc')->get();

        // Grouping settings into categories
        $categories = [
            'Basic Information' => [
                'hospital_name',
                'hospital_code',
                'bed_qty',
                'k_value',
                'base_rate',
                'base_rate2',
                'base_rate_ofc',
                'base_rate_lgo',
                'base_rate_sss'
            ],
            'HOSxP Mapping (PTTYPE/LAB/DRUG)' => [
                'pttype_act',
                'pttype_sss_fund',
                'pttype_checkup',
                'pttype_iclaim',
                'pttype_sss_72',
                'pttype_sss_ae',
                'lab_prt',
                'drug_clopidogrel'
            ],
            'Claim (FDH)' => ['fdh_user', 'fdh_pass', 'fdh_secretKey'],
            'Integration Tokens' => ['token_authen_kiosk_nhso', 'telegram_token', 'telegram_chat_id_register', 'telegram_chat_id_ipdsummary', 'opoh_token'],
        ];

        $groupedData = [];
        $allocatedNames = [];

        foreach ($categories as $catName => $names) {
            // Sort settings based on the order in the $names array
            $groupedData[$catName] = collect($names)->map(function ($name) use ($settings) {
                return $settings->where('name', $name)->first();
            })->filter();

            $allocatedNames = array_merge($allocatedNames, $names);
        }

        // Catch-all for any settings not explicitly categorized
        $others = $settings->whereNotIn('name', $allocatedNames);
        if ($others->count() > 0) {
            $groupedData['Other Settings'] = $others;
        }

        return view('admin.main_setting', compact('groupedData', 'notify_summary', 'nhso_endpoint_pull_yesterday', 'hospcode'));
    }
    // Update Table main_setting------------------------------------------------------------------------------
    public function update(Request $request, $name)
    {
        $request->validate([
            'value' => 'nullable|string',
        ]);

        $setting = MainSetting::where('name', $name)->firstOrFail();
        $setting->value = $request->value;
        $setting->save();

        return redirect()->back()->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }
    #######################################################################################################################################    
// UP Structure -----------------------------------------------------------------------------------------------------------------------    
    public function up_structure(Request $request)
    {
        try {
            // 1. Run all migrations safely
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--force' => true
            ], $output);
            $migrate_result = $output->fetch();

            // 2. Update/Insert default settings in main_setting table
            $main_setting = [
                ['name' => 'bed_qty', 'name_th' => 'IPD จำนวนเตียง', 'value' => ''],
                ['name' => 'token_authen_kiosk_nhso', 'name_th' => 'Token Authen Kiosk สปสช.', 'value' => ''],
                ['name' => 'telegram_token', 'name_th' => 'Telegram Token', 'value' => ''],
                ['name' => 'telegram_chat_id_register', 'name_th' => 'Telegram ChatID Register ', 'value' => ''],
                ['name' => 'telegram_chat_id_ipdsummary', 'name_th' => 'Telegram ChatID IPD Summary', 'value' => ''],
                ['name' => 'k_value', 'name_th' => 'IPD ค่า K ', 'value' => '1'],
                ['name' => 'base_rate', 'name_th' => 'IPD BaseRate UCS ในเขต', 'value' => '3350'],
                ['name' => 'base_rate2', 'name_th' => 'IPD BaseRate UCS นอกเขต', 'value' => '9600'],
                ['name' => 'base_rate_ofc', 'name_th' => 'IPD BaseRate OFC', 'value' => '6200'],
                ['name' => 'base_rate_lgo', 'name_th' => 'IPD BaseRate LGO', 'value' => '6194'],
                ['name' => 'base_rate_sss', 'name_th' => 'IPD BaseRate SSS', 'value' => '6200'],
                ['name' => 'pttype_act', 'name_th' => 'สิทธิ พรบ. (รหัสสิทธิ HOSxP)', 'value' => '000'],
                ['name' => 'pttype_sss_fund', 'name_th' => 'สิทธิ ปกส. กองทุนทดแทน (รหัสสิทธิ HOSxP)', 'value' => '000'],
                ['name' => 'pttype_checkup', 'name_th' => 'สิทธิ ตรวจสุขภาพหน่วยงานภาครัฐ (รหัสสิทธิ HOSxP)', 'value' => '000'],
                ['name' => 'pttype_iclaim', 'name_th' => 'สิทธิ ประกันชีวิต iClaim (รหัสสิทธิ HOSxP)', 'value' => '000'],
                ['name' => 'pttype_sss_72', 'name_th' => 'สิทธิ ปกส. 72 ชั่วโมงแรก (รหัสสิทธิ HOSxP)', 'value' => '000'],
                ['name' => 'lab_prt', 'name_th' => 'LAB Pregnancy Test (รหัส lab_items HOSxP)', 'value' => '000'],
                ['name' => 'drug_clopidogrel', 'name_th' => 'ยา Clopidogrel (รหัส drugitems HOSxP)', 'value' => '0000000'],
                ['name' => 'hospital_name', 'name_th' => 'ชื่อโรงพยาบาล', 'value' => '"โรงพยาบาลทดสอบ"'],
                ['name' => 'hospital_code', 'name_th' => 'รหัส 5 หลักโรงพยาบาล', 'value' => '00000'],
                ['name' => 'opoh_token', 'name_th' => 'Token AOPOD', 'value' => ''],
                ['name' => 'fdh_user', 'name_th' => 'FDH User', 'value' => ''],
                ['name' => 'fdh_pass', 'name_th' => 'FDH Pass', 'value' => ''],
                ['name' => 'fdh_secretKey', 'name_th' => 'FDH Secret Key', 'value' => '$jwt@moph#'],
                ['name' => 'pttype_sss_ae', 'name_th' => 'สิทธิ ปกส. อุบัติเหตุ/ฉุกเฉิน (รหัสสิทธิ HOSxP)', 'value' => '000'],
            ];

            // Clean up obsolete settings
            MainSetting::where('name', 'telegram_chat_id')->delete();

            foreach ($main_setting as $row) {
                // Ensure record exists with default value if new, then sync name_th metadata
                MainSetting::firstOrCreate(
                    ['name' => $row['name']],
                    ['name_th' => $row['name_th'], 'value' => $row['value']]
                )->update([
                            'name_th' => $row['name_th']
                        ]);
            }

            return redirect()->route('admin.main_setting')
                ->with('success', 'อัปเกรดโครงสร้างฐานข้อมูลเสร็จสิ้น')
                ->with('migrate_output', $migrate_result);

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาดในการอัปเกรด: ' . $e->getMessage());
        }
    }
}
