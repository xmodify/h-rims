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

            // 6. Upgrade lookup_icode table with sss_hc column
            try {
                if (Schema::hasTable('lookup_icode')) {
                    Schema::table('lookup_icode', function (Blueprint $tableObj) {
                        if (!Schema::hasColumn('lookup_icode', 'sss_hc')) {
                            $tableObj->string('sss_hc', 1)->nullable()->after('ems');
                        }
                    });
                    
                    try {
                        Schema::table('lookup_icode', function (Blueprint $tableObj) {
                            $tableObj->index('sss_hc');
                        });
                    } catch (\Exception $e) {
                        // Index might already exist, ignore
                    }
                    
                    $migrate_result .= " and updated lookup_icode (added sss_hc).";
                }
            } catch (\Exception $e) {
                Log::warning("Could not upgrade lookup_icode: " . $e->getMessage());
            }

            // 7. Create/Rename lookup_sss_equipdev_aipn table if not exists
            try {
                if (Schema::hasTable('sss_equipdev_aipn') && !Schema::hasTable('lookup_sss_equipdev_aipn')) {
                    Schema::rename('sss_equipdev_aipn', 'lookup_sss_equipdev_aipn');
                    $migrate_result .= " and renamed sss_equipdev_aipn to lookup_sss_equipdev_aipn.";
                }

                if (!Schema::hasTable('lookup_sss_equipdev_aipn')) {
                    Schema::create('lookup_sss_equipdev_aipn', function (Blueprint $tableObj) {
                        $tableObj->id();
                        $tableObj->string('billgroup', 50)->nullable()->index();
                        $tableObj->string('code', 50)->nullable()->index();
                        $tableObj->string('unit', 50)->nullable();
                        $tableObj->decimal('rate', 15, 2)->nullable();
                        $tableObj->decimal('rate2', 15, 2)->nullable();
                        $tableObj->text('desc')->nullable();
                        $tableObj->date('daterev')->nullable();
                        $tableObj->date('dateeff')->nullable();
                        $tableObj->date('dateexp')->nullable();
                        $tableObj->string('lastupd', 50)->nullable();
                        $tableObj->string('dtcond', 100)->nullable();
                        $tableObj->text('note')->nullable();
                        $tableObj->timestamps();
                    });
                    $migrate_result .= " and created lookup_sss_equipdev_aipn table.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not create/rename lookup_sss_equipdev_aipn table: " . $e->getMessage());
            }

            // 8. Drop legacy lookup_adp_sss table (replaced by sss_equipdev_aipn)
            try {
                if (Schema::hasTable('lookup_adp_sss')) {
                    Schema::drop('lookup_adp_sss');
                    $migrate_result .= " and dropped legacy lookup_adp_sss table.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not drop lookup_adp_sss table: " . $e->getMessage());
            }

            // Drop legacy drugcat_aipn table and create drugcat_chi table
            try {
                if (Schema::hasTable('drugcat_aipn')) {
                    Schema::drop('drugcat_aipn');
                    $migrate_result .= " and dropped legacy drugcat_aipn table.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not drop drugcat_aipn table: " . $e->getMessage());
            }

            try {
                if (!Schema::hasTable('drugcat_chi')) {
                    Schema::create('drugcat_chi', function (Blueprint $table) {
                        $table->string('hospdrugcode', 255)->nullable();
                        $table->string('productcat', 255)->nullable();
                        $table->string('tmtid', 255)->nullable();
                        $table->string('specprep', 255)->nullable();
                        $table->string('genericname', 255)->nullable();
                        $table->string('tradename', 255)->nullable();
                        $table->string('dfscode', 255)->nullable();
                        $table->string('dosageform', 255)->nullable();
                        $table->string('strength', 255)->nullable();
                        $table->string('content', 255)->nullable();
                        $table->double('unitprice')->nullable();
                        $table->string('distributor', 255)->nullable();
                        $table->string('manufacturer', 255)->nullable();
                        $table->string('ised', 255)->nullable();
                        $table->string('ndc24', 255)->nullable();
                        $table->string('packsize', 255)->nullable();
                        $table->string('packprice', 255)->nullable();
                        $table->string('updateflag', 255)->nullable();
                        $table->date('datechange')->nullable();
                        $table->date('dateupdate')->nullable();
                        $table->date('dateeffective')->nullable();
                        $table->string('ised_approved', 255)->nullable();
                        $table->string('ndc24_approved', 255)->nullable();
                        $table->date('date_approved')->nullable();
                        $table->string('ised_status', 255)->nullable();
                        $table->string('stm_filename', 255)->nullable();
                    });
                    $migrate_result .= " and created drugcat_chi table.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not create drugcat_chi table: " . $e->getMessage());
            }

            // 8.1 Import/Upsert lookup_sss_equipdev_aipn data from docs/lookup/EquipdevAIPN.xlsx
            try {
                $filePath = base_path('docs/lookup/EquipdevAIPN.xlsx');
                if (file_exists($filePath)) {
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
                            } catch (\Exception $e) {
                                // ignore
                            }
                        }
                        
                        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd/m/y', 'd-m-y'] as $format) {
                            try {
                                return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
                            } catch (\Exception $e) {
                                // continue
                            }
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

                    $migrate_result .= " and updated lookup_sss_equipdev_aipn data (Inserted: $insertedCount, Updated: $updatedCount).";
                } else {
                    $migrate_result .= " and lookup_sss_equipdev_aipn Excel file not found at docs/lookup/EquipdevAIPN.xlsx.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not import lookup_sss_equipdev_aipn Excel data: " . $e->getMessage());
                $migrate_result .= " and error importing lookup_sss_equipdev_aipn Excel: " . $e->getMessage();
            }



            // 8.1.5 Create and import lookup_nhso_adp_type table
            try {
                if (!Schema::hasTable('lookup_nhso_adp_type')) {
                    Schema::create('lookup_nhso_adp_type', function (Blueprint $table) {
                        $table->integer('nhso_adp_type_id')->primary();
                        $table->string('nhso_adp_type_name', 150)->nullable();
                        $table->timestamps();
                    });
                    $migrate_result .= " and created lookup_nhso_adp_type table.";
                }

                if (Schema::hasTable('lookup_nhso_adp_type')) {
                    $hosxp_types = DB::connection('hosxp')->table('nhso_adp_type')->get();
                    $insertedTypes = 0;
                    foreach ($hosxp_types as $type) {
                        DB::table('lookup_nhso_adp_type')->updateOrInsert(
                            ['nhso_adp_type_id' => $type->nhso_adp_type_id],
                            [
                                'nhso_adp_type_name' => $type->nhso_adp_type_name,
                                'updated_at' => now(),
                                'created_at' => now()
                            ]
                        );
                        $insertedTypes++;
                    }
                    $migrate_result .= " and copied $insertedTypes types to lookup_nhso_adp_type.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not setup lookup_nhso_adp_type table: " . $e->getMessage());
                $migrate_result .= " and error setting up lookup_nhso_adp_type: " . $e->getMessage();
            }

            // 8.2 Create and import lookup_nhso_adp_code table
            try {
                // Drop if exists to ensure structure matches
                Schema::dropIfExists('lookup_nhso_adp_code');

                // Create matching HOSxP structure but with added prices
                Schema::create('lookup_nhso_adp_code', function (Blueprint $table) {
                    $table->string('nhso_adp_code', 50);
                    $table->integer('nhso_adp_type_id');
                    $table->string('nhso_adp_code_name', 255);
                    $table->string('category', 100)->nullable()->index();
                    $table->decimal('price_ucs', 10, 2)->default(0.00);
                    $table->decimal('price_ofc', 10, 2)->default(0.00);
                    $table->decimal('price_sss', 10, 2)->default(0.00);
                    $table->decimal('price_lgo', 10, 2)->default(0.00);
                    $table->decimal('price_fs', 10, 2)->default(0.00);
                    $table->decimal('price_ucep', 10, 2)->default(0.00);
                    $table->string('ins_ucs', 10)->nullable()->default('');
                    $table->string('ins_ofc', 10)->nullable()->default('');
                    $table->timestamps();

                    $table->primary(['nhso_adp_code', 'nhso_adp_type_id']);
                });
                $migrate_result .= " and created lookup_nhso_adp_code table.";

                // Import from Excel docs/lookup/lookup_nhso_adp_code.xlsx
                $filePath = base_path('docs/lookup/lookup_nhso_adp_code.xlsx');
                if (file_exists($filePath)) {
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
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $insertedCount++;
                    }
                    $migrate_result .= " and imported $insertedCount records from Excel into lookup_nhso_adp_code.";

                    // Update price_sss from lookup_sss_equipdev_aipn (active records)
                    if (Schema::hasTable('lookup_sss_equipdev_aipn')) {
                        $affectedSss = DB::update("
                            UPDATE lookup_nhso_adp_code c
                            INNER JOIN lookup_sss_equipdev_aipn a ON a.code = c.nhso_adp_code
                            SET c.price_sss = COALESCE(a.rate, 0.00)
                            WHERE c.nhso_adp_type_id = 2
                              AND a.dateexp >= DATE(NOW())
                        ");
                        $migrate_result .= " and updated $affectedSss records with SSS prices.";
                    }
                } else {
                    $migrate_result .= " and Excel file lookup_nhso_adp_code.xlsx not found at docs/lookup/.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not setup lookup_nhso_adp_code table: " . $e->getMessage());
                $migrate_result .= " and error setting up lookup_nhso_adp_code: " . $e->getMessage();
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
