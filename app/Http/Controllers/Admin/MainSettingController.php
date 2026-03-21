<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MainSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Schema\Blueprint;

class MainSettingController extends Controller
{

    public function index()
    {
        $hospcode = DB::table('lookup_hospcode')->value('hospcode');

        $notify_summary = route('notify_summary');
        $nhso_endpoint_pull_yesterday = route('nhso_endpoint_pull_yesterday');
        $fdh_check_claim_lastdays = route('api.fdh.check_claim_lastdays');

        $settings = MainSetting::orderBy('name_th', 'asc')->get();

        $integrationTokens = [
            'token_authen_kiosk_nhso',
            'git_token',
            'telegram_token',
            'telegram_chat_id_register',
            'telegram_chat_id_notify_summary',
            'moph_notify_secret',
            'moph_notify_client_id'
        ];

        if ($hospcode === '00025') {
            // แทรก opoh_token ไว้ลำดับแรก
            array_splice($integrationTokens, 0, 0, 'opoh_token');
        }

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
            'Integration Tokens' => $integrationTokens,
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

        return view('admin.main_setting', compact('groupedData', 'notify_summary', 'nhso_endpoint_pull_yesterday', 'fdh_check_claim_lastdays', 'hospcode'));
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
                ['name' => 'token_authen_kiosk_nhso', 'name_th' => 'NHSO Authen Kiosk Token', 'value' => ''],
                ['name' => 'telegram_token', 'name_th' => 'Telegram Token', 'value' => ''],
                ['name' => 'telegram_chat_id_register', 'name_th' => 'Telegram ChatID Register', 'value' => ''],
                ['name' => 'telegram_chat_id_notify_summary', 'name_th' => 'Telegram ChatID NotifySummary', 'value' => ''],
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
                ['name' => 'opoh_token', 'name_th' => 'AOPOD Token', 'value' => ''],
                ['name' => 'fdh_user', 'name_th' => 'FDH User', 'value' => ''],
                ['name' => 'fdh_pass', 'name_th' => 'FDH Pass', 'value' => ''],
                ['name' => 'fdh_secretKey', 'name_th' => 'FDH Secret Key', 'value' => '$jwt@moph#'],
                ['name' => 'pttype_sss_ae', 'name_th' => 'สิทธิ ปกส. อุบัติเหตุ/ฉุกเฉิน (รหัสสิทธิ HOSxP)', 'value' => '000'],
                ['name' => 'git_token', 'name_th' => 'GitHub Token (สำหรับ Private Repo)', 'value' => ''],
                ['name' => 'moph_notify_secret', 'name_th' => 'Moph Notify SecretKEY', 'value' => ''],
                ['name' => 'moph_notify_client_id', 'name_th' => 'Moph Notify ClientID', 'value' => ''],
            ];

            // Clean up obsolete settings
            MainSetting::whereIn('name', ['telegram_chat_id', 'git_user', 'telegram_chat_id_ipdsummary'])->delete();

            foreach ($main_setting as $row) {
                // Ensure record exists with default value if new, then sync name_th metadata
                MainSetting::firstOrCreate(
                    ['name' => $row['name']],
                    ['name_th' => $row['name_th'], 'value' => $row['value']]
                )->update([
                    'name_th' => $row['name_th']
                ]);
            }

            // 3. Upgrade users table with permission columns
            $newColumns = [
                'allow_home', 'allow_import', 'allow_check', 'allow_emr', 
                'allow_claim_op', 'allow_claim_ip', 'allow_mishos', 
                'allow_debtor', 'allow_debtor_lock', 'allow_debtor_acc', 'allow_receipt',
                'allow_nhso_endpoint'
            ];

            Schema::table('users', function (Blueprint $table) use ($newColumns) {
                foreach ($newColumns as $column) {
                    if (!Schema::hasColumn('users', $column)) {
                        $table->string($column, 1)->default('N')->after('status');
                    }
                }

                // Handle 'cid' column specifically
                if (!Schema::hasColumn('users', 'cid')) {
                    $table->string('cid', 13)->nullable()->after('email');
                } else {
                    try {
                        // Attempt to modify the column type if it already exists
                        DB::statement("ALTER TABLE users MODIFY COLUMN cid VARCHAR(13) NULL");
                    } catch (\Exception $e) {
                        Log::warning("Could not modify users.cid to VARCHAR(13): " . $e->getMessage());
                    }
                }
            });

            // Set Admin to have all permissions by default
            DB::table('users')->where('status', 'admin')->update(
                array_fill_keys($newColumns, 'Y')
            );

            // 4. Upgrade all debtor_1* tables with new columns
            $tablesResult = DB::select("SHOW TABLES LIKE 'debtor_1%'");
            $tables = [];
            foreach ($tablesResult as $row) {
                $tables[] = array_values((array)$row)[0];
            }
            
            $debtorColumns = [
                'charge_date' => ['type' => 'date', 'params' => [], 'nullable' => true, 'after' => 'status'],
                'charge_no' => ['type' => 'string', 'params' => [100], 'nullable' => true, 'after' => 'charge_date'],
                'charge' => ['type' => 'decimal', 'params' => [10, 2], 'nullable' => true, 'after' => 'charge_no'],
                'receive_date' => ['type' => 'date', 'params' => [], 'nullable' => true, 'after' => 'charge'],
                'receive_no' => ['type' => 'string', 'params' => [100], 'nullable' => true, 'after' => 'receive_date'],
                'receive' => ['type' => 'decimal', 'params' => [10, 2], 'nullable' => true, 'after' => 'receive_no'],
                'repno' => ['type' => 'string', 'params' => [100], 'nullable' => true, 'after' => 'receive'],
                'adj_inc' => ['type' => 'decimal', 'params' => [10, 2], 'nullable' => true, 'after' => 'repno'],
                'adj_dec' => ['type' => 'decimal', 'params' => [10, 2], 'nullable' => true, 'after' => 'adj_inc'],
                'adj_note' => ['type' => 'string', 'params' => [255], 'nullable' => true, 'after' => 'adj_dec'],
                'adj_date' => ['type' => 'date', 'params' => [], 'nullable' => true, 'after' => 'adj_note'],
                'debtor_change' => ['type' => 'decimal', 'params' => [10, 2], 'nullable' => true, 'after' => 'adj_date']
            ];

            $upgradedTables = 0;
            $addedColumnsLog = [];
            
            foreach ($tables as $tableName) {
                if (strpos($tableName, '_tracking') === false) {
                    $currentCols = Schema::getColumnListing($tableName);
                    $colsToAdd = [];
                    foreach ($debtorColumns as $colName => $colDef) {
                        if (!in_array($colName, $currentCols)) {
                            $colsToAdd[$colName] = $colDef;
                        }
                    }
                    
                    if (!empty($colsToAdd)) {
                        $currentColsListing = $currentCols; // ใช้ list เดิมที่ดึงมาแล้วสำหรับการเช็ค safety after
                        Schema::table($tableName, function (Blueprint $table) use ($colsToAdd, &$currentColsListing) {
                            foreach ($colsToAdd as $colName => $colDef) {
                                $method = $colDef['type'];
                                $params = $colDef['params'];
                                $column = call_user_func_array([$table, $method], array_merge([$colName], $params));
                                
                                if ($colDef['nullable']) {
                                    $column->nullable();
                                }
                                
                                if (isset($colDef['after'])) {
                                    // Safety Check: ตรวจสอบว่าคอลัมน์ที่จะไปต่อท้าย (after) มีอยู่ในตารางจริงๆ หรือเพิ่งถูกสร้างใน batch นี้
                                    if (in_array($colDef['after'], $currentColsListing)) {
                                        $column->after($colDef['after']);
                                    }
                                }
                                // เพิ่มชื่อคอลัมน์ที่เพิ่งสร้างเข้าไปใน list เพื่อให้ตัวถัดไปสามารถอ้างอิง after ได้
                                $currentColsListing[] = $colName;
                            }
                        });
                        $addedColumnsLog[] = $tableName;
                    }
                    $upgradedTables++;
                }
            }
            $migrate_result .= "\n Checked $upgradedTables debtor tables.";
            if (count($addedColumnsLog) > 0) {
                $migrate_result .= " Added columns to " . count($addedColumnsLog) . " tables: " . implode(', ', $addedColumnsLog);
            } else {
                $migrate_result .= " All tables already have required columns.";
            }

            // 5. Upgrade nhso_endpoint table with claim status columns
            $nhso_tables = [
                ['connection' => 'mysql', 'table' => 'nhso_endpoint'],
                ['connection' => 'hosxp', 'table' => 'hrims.nhso_endpoint']
            ];

            foreach ($nhso_tables as $item) {
                try {
                    $conn = $item['connection'];
                    $table = $item['table'];
                    
                    if (Schema::connection($conn)->hasTable($table)) {
                        Schema::connection($conn)->table($table, function (Blueprint $tableObj) use ($conn, $table) {
                            if (!Schema::connection($conn)->hasColumn($table, 'claim_status')) {
                                $tableObj->string('claim_status', 20)->nullable()->after('claimType');
                            }
                            if (!Schema::connection($conn)->hasColumn($table, 'saved_at')) {
                                $tableObj->dateTime('saved_at')->nullable()->after('claim_status');
                            }
                            if (!Schema::connection($conn)->hasColumn($table, 'nhso_response')) {
                                $tableObj->text('nhso_response')->nullable()->after('saved_at');
                            }
                        });
                    }
                } catch (\Exception $e) {
                    // ข้ามถ้าไม่มีสิทธิ์หรือไม่มีตารางใน connection นั้น
                    Log::warning("Could not upgrade $table on $conn: " . $e->getMessage());
                }
            }

            $migrate_result .= " and updated nhso_endpoint.";

            return redirect()->route('admin.main_setting')
                ->with('success', 'อัปเกรดโครงสร้างฐานข้อมูลเสร็จสิ้น')
                ->with('migrate_output', $migrate_result);
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาดในการอัปเกรด: ' . $e->getMessage());
        }
    }

    public function gitPull()
    {
        try {
            // ดึงค่า Token จากการตั้งค่า
            $git_token = MainSetting::where('name', 'git_token')->value('value');
            $git_user = 'xmodify'; // ฝังชื่อ User ไว้ในโค้ดเลยเพื่อความง่าย

            $base_path = base_path();

            // หากมีการตั้งค่า Token ไว้ ให้ทำการอัปเดต Remote URL ก่อน
            if (!empty($git_token)) {
                $remote_url = "https://{$git_user}:{$git_token}@github.com/xmodify/h-rims.git";
                shell_exec("cd {$base_path} && git remote set-url origin {$remote_url}");
            }

            // รันคำสั่งอัปเดต: Reset -> Pull -> Clear Cache
            $command = "cd {$base_path} && git reset --hard && git pull origin main && php artisan optimize:clear 2>&1";
            $output = shell_exec($command);

            // --- ขั้นตอนการกรองข้อมูลเพื่อความปลอดภัย ---
            // 1. ซ่อน Token ใน Output (ถ้ามีหลุดออกมา)
            $filteredOutput = preg_replace('/https:\/\/.*:.*@github\.com/i', 'https://GITHUB_TOKEN@github.com', $output);

            // 2. ซ่อนบรรทัด "From https://github.com/..." เพื่อความเป็นระเบียบและปลอดภัย
            $filteredOutput = preg_replace('/^From https:\/\/github\.com\/.*$/m', '', $filteredOutput);

            return response()->json([
                'output' => trim($filteredOutput)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
