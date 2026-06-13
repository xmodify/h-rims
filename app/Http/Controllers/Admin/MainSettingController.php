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
    // UP Structure ------------------------------------------------------------
    public function up_structure(Request $request)
    {
        $step = intval($request->input('step', 0));
        
        try {
            switch ($step) {
                case 1:
                    // ==========================================
                    // STEP 1: Artisan Migrate & Verify All Tables Schema from extracted_schemas.json
                    // ==========================================
                    $output = new \Symfony\Component\Console\Output\BufferedOutput();
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true], $output);
                    $migrate_result = $output->fetch();

                    // Load expected schemas
                    $schemas = $this->getExpectedSchemas();

                    $details = [];
                    foreach ($schemas as $table => $schemaDef) {
                        if ($table === 'debtor_template') {
                            continue; // Skip the template structure helper
                        }
                        $res = $this->verifyTableSchema($table, $schemaDef);
                        if ($res) {
                            $details[] = "$table: $res";
                        }
                    }

                    // Set Admin default permissions in users table
                    $permissionColumns = [
                        'allow_home', 'allow_import', 'allow_check', 'allow_emr', 
                        'allow_claim_op', 'allow_claim_ip', 'allow_mishos', 
                        'allow_debtor', 'allow_debtor_lock', 'allow_debtor_acc', 'allow_receipt',
                        'allow_nhso_endpoint'
                    ];
                    DB::table('users')->where('status', 'admin')->update(
                        array_fill_keys($permissionColumns, 'Y')
                    );

                    $msg = 'ตรวจสอบโครงสร้างทุกตารางสำเร็จ';
                    if (!empty($details)) {
                        $msg .= ' (ปรับปรุง: ' . implode(', ', $details) . ')';
                    } else {
                        $msg .= ' (โครงสร้างตารางทั้งหมดเป็นปัจจุบันแล้ว)';
                    }

                    return response()->json([
                        'success' => true,
                        'message' => $msg
                    ]);

                case 2:
                    // ==========================================
                    // STEP 2: Import/Sync Lookup (EquipdevAIPN.xlsx)
                    // ==========================================
                    $filePath = base_path('docs/lookup/EquipdevAIPN.xlsx');
                    if (!file_exists($filePath)) {
                        throw new \Exception("ไม่พบไฟล์ Excel EquipdevAIPN.xlsx ที่ docs/lookup/");
                    }

                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                    $sheet = $spreadsheet->setActiveSheetIndex(0);
                    $row_limit = $sheet->getHighestDataRow();

                    $parseDate = function ($value) {
                        if (empty($value) || $value === '-' || trim($value) === '') {
                            return null;
                        }
                        $value = trim($value);
                        if (is_numeric($value)) {
                            try {
                                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
                            } catch (\Exception $e) {}
                        }
                        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y'] as $format) {
                            try {
                                return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
                            } catch (\Exception $e) {}
                        }
                        try {
                            return \Carbon\Carbon::parse($value)->format('Y-m-d');
                        } catch (\Exception $e) {
                            return null;
                        }
                    };

                    $cleanRate = function ($val) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            return null;
                        }
                        $val = str_replace(',', '', $val);
                        return is_numeric($val) ? (float) $val : null;
                    };

                    $updatedCount = 0;
                    $insertedCount = 0;

                    for ($row = 2; $row <= $row_limit; $row++) {
                        $billgroup = $sheet->getCell('A' . $row)->getValue();
                        $code = $sheet->getCell('B' . $row)->getValue();

                        if (empty($billgroup) && empty($code)) {
                            continue;
                        }

                        $rate = $cleanRate($sheet->getCell('D' . $row)->getValue());
                        $rate2 = $cleanRate($sheet->getCell('E' . $row)->getValue());

                        $daterev = $parseDate($sheet->getCell('G' . $row)->getValue());
                        $dateeff = $parseDate($sheet->getCell('H' . $row)->getValue());
                        $dateexp = $parseDate($sheet->getCell('I' . $row)->getValue());

                        $recordData = [
                            'billgroup' => $billgroup,
                            'unit' => $sheet->getCell('C' . $row)->getValue(),
                            'rate' => $rate,
                            'rate2' => $rate2,
                            'desc' => $sheet->getCell('F' . $row)->getValue(),
                            'daterev' => $daterev,
                            'dateeff' => $dateeff,
                            'dateexp' => $dateexp,
                            'lastupd' => $sheet->getCell('J' . $row)->getValue(),
                            'dtcond' => $sheet->getCell('K' . $row)->getValue(),
                            'note' => $sheet->getCell('L' . $row)->getValue(),
                            'updated_at' => now(),
                        ];

                        $exists = DB::table('lookup_sss_equipdev_aipn')->where('code', $code)->exists();
                        if ($exists) {
                            $updatedCount++;
                        } else {
                            $recordData['created_at'] = now();
                            $recordData['code'] = $code;
                            $insertedCount++;
                        }

                        DB::table('lookup_sss_equipdev_aipn')->updateOrInsert(
                            ['code' => $code],
                            $recordData
                        );
                    }

                    return response()->json([
                        'success' => true,
                        'message' => "นำเข้า EquipdevAIPN เพิ่ม: $insertedCount, อัปเดต: $updatedCount แถว"
                    ]);

                case 3:
                    // ==========================================
                    // STEP 3: Import/Sync Lookup (lookup_nhso_adp_type.xlsx)
                    // ==========================================
                    $filePath = base_path('docs/lookup/lookup_nhso_adp_type.xlsx');
                    if (!file_exists($filePath)) {
                        throw new \Exception("ไม่พบไฟล์ Excel lookup_nhso_adp_type.xlsx ที่ docs/lookup/");
                    }

                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $row_limit = $sheet->getHighestDataRow();

                    $insertedTypes = 0;
                    for ($row = 2; $row <= $row_limit; $row++) {
                        $type_id = $sheet->getCell('A' . $row)->getValue();
                        $type_name = $sheet->getCell('B' . $row)->getValue();

                        if (empty($type_id)) {
                            continue;
                        }

                        DB::table('lookup_nhso_adp_type')->updateOrInsert(
                            ['nhso_adp_type_id' => intval($type_id)],
                            [
                                'nhso_adp_type_name' => $type_name,
                                'updated_at' => now(),
                                'created_at' => now()
                            ]
                        );
                        $insertedTypes++;
                    }

                    return response()->json([
                        'success' => true,
                        'message' => "นำเข้า lookup_nhso_adp_type สำเร็จทั้งหมด $insertedTypes รายการ"
                    ]);

                case 4:
                    // ==========================================
                    // STEP 4: Import/Sync Lookup (lookup_nhso_adp_code.xlsx)
                    // ==========================================
                    $filePath = base_path('docs/lookup/lookup_nhso_adp_code.xlsx');
                    if (!file_exists($filePath)) {
                        throw new \Exception("ไม่พบไฟล์ Excel lookup_nhso_adp_code.xlsx ที่ docs/lookup/");
                    }

                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $row_limit = $sheet->getHighestDataRow();

                    $cleanPrice = function ($val) {
                        if ($val === null || $val === '-' || trim($val) === '') {
                            return 0.00;
                        }
                        $val = str_replace(',', '', $val);
                        return is_numeric($val) ? (float) $val : 0.00;
                    };

                    // Truncate first to have a clean import
                    DB::table('lookup_nhso_adp_code')->truncate();

                    $insertedCount = 0;
                    for ($row = 2; $row <= $row_limit; $row++) {
                        $adp_code = $sheet->getCell('A' . $row)->getValue();
                        $adp_type_id = $sheet->getCell('B' . $row)->getValue();

                        if (empty($adp_code) || empty($adp_type_id)) {
                            continue;
                        }

                        $price_ucs = $cleanPrice($sheet->getCell('E' . $row)->getValue());
                        $price_ofc = $cleanPrice($sheet->getCell('F' . $row)->getValue());
                        $price_sss = $cleanPrice($sheet->getCell('G' . $row)->getValue());
                        $price_lgo = $cleanPrice($sheet->getCell('H' . $row)->getValue());
                        $price_fs = $cleanPrice($sheet->getCell('I' . $row)->getValue());
                        $price_ucep = $cleanPrice($sheet->getCell('J' . $row)->getValue());
                        $ins_ucs = trim($sheet->getCell('K' . $row)->getValue() ?? '');
                        $ins_ofc = trim($sheet->getCell('L' . $row)->getValue() ?? '');
                        $fs = trim($sheet->getCell('M' . $row)->getValue() ?? '');

                        DB::table('lookup_nhso_adp_code')->insert([
                            'nhso_adp_code' => trim($adp_code),
                            'nhso_adp_type_id' => intval($adp_type_id),
                            'nhso_adp_code_name' => $sheet->getCell('C' . $row)->getValue() ?? '',
                            'category' => $sheet->getCell('D' . $row)->getValue(),
                            'price_ucs' => $price_ucs,
                            'price_ofc' => $price_ofc,
                            'price_sss' => $price_sss,
                            'price_lgo' => $price_lgo,
                            'price_fs' => $price_fs,
                            'price_ucep' => $price_ucep,
                            'ins_ucs' => $ins_ucs,
                            'ins_ofc' => $ins_ofc,
                            'fs' => $fs,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $insertedCount++;
                    }

                    return response()->json([
                        'success' => true,
                        'message' => "นำเข้า lookup_nhso_adp_code สำเร็จทั้งหมด $insertedCount รายการ"
                    ]);

                case 5:
                    // ==========================================
                    // STEP 5: Settings Synchronization (main_setting)
                    // ==========================================
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
                        MainSetting::firstOrCreate(
                            ['name' => $row['name']],
                            ['name_th' => $row['name_th'], 'value' => $row['value']]
                        )->update([
                            'name_th' => $row['name_th']
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'ซิงค์ข้อมูลตั้งค่าหลักเรียบร้อยแล้ว'
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'ขั้นตอนไม่ถูกต้อง'
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error("Upgrade Structure Error (Step $step): " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'ข้อผิดพลาดในขั้นตอนที่ ' . $step . ': ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load expected schemas from JSON and inject lookup tables schemas
     */
    protected function getExpectedSchemas()
    {
        $jsonPath = base_path('docs/lookup/extracted_schemas.json');
        $schemas = [];
        if (file_exists($jsonPath)) {
            $schemas = json_decode(file_get_contents($jsonPath), true);
        }
        return $schemas;
    }

    /**
     * Verifies and synchronizes table schema structure (columns and indexes)
     */
    protected function verifyTableSchema($tableName, $schemaDef)
    {
        $updated = [];

        // 1. If table does not exist, create it from scratch
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($schemaDef) {
                foreach ($schemaDef['columns'] as $colName => $col) {
                    $this->addColumnToBlueprint($table, $colName, $col);
                }
                
                // Add indexes (except primary which is usually handled by auto_increment)
                if (isset($schemaDef['indexes'])) {
                    foreach ($schemaDef['indexes'] as $indexName => $columnsInfo) {
                        if ($indexName === 'PRIMARY') {
                            // Primary keys with multiple columns
                            if (count($columnsInfo) > 1) {
                                $cols = collect($columnsInfo)->pluck('column')->toArray();
                                $table->primary($cols);
                            }
                            continue;
                        }
                        $cols = collect($columnsInfo)->pluck('column')->toArray();
                        $isUnique = collect($columnsInfo)->first()['unique'] ?? false;
                        if ($isUnique) {
                            $table->unique($cols, $indexName);
                        } else {
                            $table->index($cols, $indexName);
                        }
                    }
                }
            });
            return "Created Table";
        }

        // 2. If table exists, verify columns and update them
        $existingColumns = Schema::getColumnListing($tableName);
        foreach ($schemaDef['columns'] as $colName => $col) {
            if (!in_array($colName, $existingColumns)) {
                // Column is missing! Add it.
                Schema::table($tableName, function (Blueprint $table) use ($colName, $col) {
                    $this->addColumnToBlueprint($table, $colName, $col);
                });
                $updated[] = "+$colName";
            } else {
                // Column exists. Verify matches standard schema
                $actualCol = DB::select("SHOW COLUMNS FROM `$tableName` WHERE Field = ?", [$colName]);
                if (!empty($actualCol)) {
                    $actual = $actualCol[0];
                    $actualType = strtolower($actual->Type);
                    $actualNullable = ($actual->Null === 'YES');
                    $actualDefault = $actual->Default;
                    
                    $expectedType = strtolower($col['type']);
                    $expectedNullable = $col['nullable'];
                    $expectedDefault = $col['default'];
                    
                    $typeMismatch = false;
                    // Check if types are different (excluding minor display widths e.g. int(11) vs int)
                    $cleanActualType = preg_replace('/\(\d+\)/', '', $actualType);
                    $cleanExpectedType = preg_replace('/\(\d+\)/', '', $expectedType);
                    if ($cleanActualType !== $cleanExpectedType) {
                        $typeMismatch = true;
                    }
                    
                    if ($typeMismatch || $actualNullable !== $expectedNullable || $actualDefault !== $expectedDefault) {
                        try {
                            Schema::table($tableName, function (Blueprint $table) use ($colName, $col) {
                                $this->addColumnToBlueprint($table, $colName, $col, true);
                            });
                            $updated[] = "*$colName";
                        } catch (\Exception $e) {
                            // Fallback to raw ALTER statement
                            try {
                                $nullStr = $expectedNullable ? "NULL" : "NOT NULL";
                                $defaultStr = ($expectedDefault === null) ? ($expectedNullable ? "DEFAULT NULL" : "") : "DEFAULT '" . addslashes($expectedDefault) . "'";
                                DB::statement("ALTER TABLE `$tableName` MODIFY COLUMN `$colName` {$col['type']} {$nullStr} {$defaultStr}");
                                $updated[] = "*$colName(raw)";
                            } catch (\Exception $e2) {
                                Log::error("Failed to alter column $tableName.$colName: " . $e2->getMessage());
                            }
                        }
                    }
                }
            }
        }

        // 3. Verify and create indexes
        if (isset($schemaDef['indexes'])) {
            foreach ($schemaDef['indexes'] as $indexName => $columnsInfo) {
                if ($indexName === 'PRIMARY') {
                    continue;
                }
                
                $indexExists = count(DB::select("SHOW INDEX FROM `$tableName` WHERE Key_name = ?", [$indexName])) > 0;
                if (!$indexExists) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($indexName, $columnsInfo) {
                            $cols = collect($columnsInfo)->pluck('column')->toArray();
                            $isUnique = collect($columnsInfo)->first()['unique'] ?? false;
                            if ($isUnique) {
                                $table->unique($cols, $indexName);
                            } else {
                                $table->index($cols, $indexName);
                            }
                        });
                        $updated[] = "+idx:$indexName";
                    } catch (\Exception $e) {
                        Log::warning("Could not create index $indexName on $tableName: " . $e->getMessage());
                    }
                }
            }
        }

        return empty($updated) ? "" : implode(', ', $updated);
    }

    /**
     * Add column definition to Laravel Blueprint
     */
    protected function addColumnToBlueprint($table, $colName, $col, $isChange = false)
    {
        $type = $col['type'];
        $nullable = $col['nullable'];
        $default = $col['default'];
        $extra = $col['extra'];

        $colObj = null;
        if (strpos($type, 'bigint') !== false) {
            if (strpos($type, 'unsigned') !== false) {
                if ($extra === 'auto_increment') {
                    $colObj = $table->bigIncrements($colName);
                } else {
                    $colObj = $table->bigInteger($colName)->unsigned();
                }
            } else {
                if ($extra === 'auto_increment') {
                    $colObj = $table->bigIncrements($colName);
                } else {
                    $colObj = $table->bigInteger($colName);
                }
            }
        } elseif (strpos($type, 'int') !== false) {
            if (strpos($type, 'unsigned') !== false) {
                if ($extra === 'auto_increment') {
                    $colObj = $table->increments($colName);
                } else {
                    $colObj = $table->integer($colName)->unsigned();
                }
            } else {
                if ($extra === 'auto_increment') {
                    $colObj = $table->increments($colName);
                } else {
                    $colObj = $table->integer($colName);
                }
            }
        } elseif (strpos($type, 'varchar') !== false) {
            preg_match('/varchar\((\d+)\)/', $type, $matches);
            $length = isset($matches[1]) ? intval($matches[1]) : 255;
            $colObj = $table->string($colName, $length);
        } elseif (strpos($type, 'char') !== false) {
            preg_match('/char\((\d+)\)/', $type, $matches);
            $length = isset($matches[1]) ? intval($matches[1]) : 255;
            $colObj = $table->char($colName, $length);
        } elseif (strpos($type, 'decimal') !== false) {
            preg_match('/decimal\((\d+),(\d+)\)/', $type, $matches);
            $precision = isset($matches[1]) ? intval($matches[1]) : 15;
            $scale = isset($matches[2]) ? intval($matches[2]) : 2;
            $colObj = $table->decimal($colName, $precision, $scale);
        } elseif (strpos($type, 'double') !== false) {
            $colObj = $table->double($colName);
        } elseif (strpos($type, 'text') !== false) {
            $colObj = $table->text($colName);
        } elseif (strpos($type, 'timestamp') !== false) {
            $colObj = $table->timestamp($colName);
        } elseif (strpos($type, 'datetime') !== false) {
            $colObj = $table->dateTime($colName);
        } elseif (strpos($type, 'date') !== false) {
            $colObj = $table->date($colName);
        } elseif (strpos($type, 'time') !== false) {
            $colObj = $table->time($colName);
        } else {
            $colObj = $table->string($colName);
        }

        if ($colObj) {
            if ($nullable) {
                $colObj->nullable();
            } else {
                $colObj->nullable(false);
            }

            if ($default !== null) {
                $colObj->default($default);
            } else {
                if ($nullable) {
                    $colObj->default(null);
                }
            }

            if ($isChange) {
                $colObj->change();
            }
        }
        return $colObj;
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

    public function showScheduleLogs()
    {
        $hospcode = DB::table('lookup_hospcode')->value('hospcode');

        $aopodLogRaw = '';
        $nhsoLogRaw = '';
        $fdhLogRaw = '';
        $notifyLogRaw = '';

        if (\Illuminate\Support\Facades\File::exists(storage_path('logs/aopod_schedule.log'))) {
            $aopodLogRaw = \Illuminate\Support\Facades\File::get(storage_path('logs/aopod_schedule.log'));
        }

        if (\Illuminate\Support\Facades\File::exists(storage_path('logs/nhso_endpoint_schedule.log'))) {
            $nhsoLogRaw = \Illuminate\Support\Facades\File::get(storage_path('logs/nhso_endpoint_schedule.log'));
        }

        if (\Illuminate\Support\Facades\File::exists(storage_path('logs/fdh_claim_status_schedule.log'))) {
            $fdhLogRaw = \Illuminate\Support\Facades\File::get(storage_path('logs/fdh_claim_status_schedule.log'));
        }

        if (\Illuminate\Support\Facades\File::exists(storage_path('logs/notify_schedule.log'))) {
            $notifyLogRaw = \Illuminate\Support\Facades\File::get(storage_path('logs/notify_schedule.log'));
        }

        $aopodLogs = $this->parseLogs($aopodLogRaw);
        $nhsoLogs = $this->parseLogs($nhsoLogRaw);
        $fdhLogs = $this->parseLogs($fdhLogRaw);
        $notifyLogs = $this->parseLogs($notifyLogRaw);

        return view('admin.logs.schedule_log', compact('aopodLogs', 'nhsoLogs', 'fdhLogs', 'notifyLogs', 'hospcode'));
    }

    private function parseLogs($logContent)
    {
        if (empty($logContent)) {
            return [];
        }

        $lines = explode("\n", trim($logContent));
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Pattern: [timestamp] Name output: json
            if (preg_match('/^\[(.*?)\]\s+(.*?)\s+output:\s+(.*)$/u', $line, $matches)) {
                $timestamp = $matches[1];
                $type = $matches[2];
                $jsonData = json_decode($matches[3], true);

                $parsed[] = [
                    'timestamp' => $timestamp,
                    'type' => $type,
                    'data' => $jsonData,
                    'raw' => $line
                ];
            } else {
                $parsed[] = [
                    'timestamp' => '',
                    'type' => 'Raw',
                    'data' => null,
                    'raw' => $line
                ];
            }
        }

        // Sort by timestamp descending
        usort($parsed, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return $parsed;
    }

    public function manualAopodSend(Request $request)
    {
        try {
            $res = app(\App\Http\Controllers\Api\AmnosendController::class)->send($request);
            $responseData = $res->getData();
            $logMessage = "[" . now()->toDateTimeString() . "] AOPOD output: " . json_encode($responseData, JSON_UNESCAPED_UNICODE) . "\n";
            appendAndLimitLog('aopod_schedule.log', $logMessage, 24);

            return response()->json([
                'status' => isset($responseData->ok) && $responseData->ok ? 'success' : 'error',
                'message' => 'ส่งข้อมูล AOPOD เสร็จสิ้น',
                'data' => $responseData
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการส่ง AOPOD: ' . $e->getMessage()
            ], 500);
        }
    }

    public function testTelegramConnection(Request $request)
    {
        $token = DB::table('main_setting')->where('name', 'telegram_token')->value('value');
        $telegram_chat_id = DB::table('main_setting')->where('name', 'telegram_chat_id_notify_summary')->value('value');
        
        if (!$token || !$telegram_chat_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาตั้งค่า Telegram Token และ Chat ID ในหน้าตั้งค่าระบบก่อน'
            ], 400);
        }
        
        try {
            $url = "https://api.telegram.org/bot$token/getMe";
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
            if ($response->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token Telegram ไม่ถูกต้อง หรือไม่สามารถติดต่อ API ได้'
                ]);
            }
            
            $chat_ids = explode(',', $telegram_chat_id);
            $success_count = 0;
            $failed_ids = [];
            foreach ($chat_ids as $chat_id) {
                $chat_id = trim($chat_id);
                if (empty($chat_id)) continue;
                
                $sendUrl = "https://api.telegram.org/bot$token/sendMessage";
                $sendRes = \Illuminate\Support\Facades\Http::timeout(10)->post($sendUrl, [
                    'chat_id' => $chat_id,
                    'text' => '🔔 ทดสอบการเชื่อมต่อ Telegram จากระบบ H-RiMS สำเร็จ ณ วันที่ ' . now()->toDateTimeString()
                ]);
                if ($sendRes->successful()) {
                    $success_count++;
                } else {
                    $failed_ids[] = $chat_id;
                }
            }
            
            if (count($failed_ids) > 0) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'เชื่อมต่อ Token สำเร็จ แต่ส่งข้อความล้มเหลวในบาง Chat ID: ' . implode(', ', $failed_ids)
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'ทดสอบการเชื่อมต่อสำเร็จ ส่งข้อความทดสอบไปยัง Telegram เรียบร้อยแล้ว'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อ Telegram: ' . $e->getMessage()
            ], 500);
        }
    }

    public function manualNotifySend(Request $request)
    {
        try {
            $res = app(\App\Http\Controllers\NotifyController::class)->notify_summary($request);
            $responseData = $res->getData();
            $logMessage = "[" . now()->toDateTimeString() . "] Notify summary output: " . json_encode($responseData, JSON_UNESCAPED_UNICODE) . "\n";
            appendAndLimitLog('notify_schedule.log', $logMessage, 30);

            return response()->json([
                'status' => 'success',
                'message' => 'ส่งรายงานสรุปบริการไปยัง Telegram สำเร็จแล้ว',
                'data' => $responseData
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการส่ง Notify: ' . $e->getMessage()
            ], 500);
        }
    }
}
