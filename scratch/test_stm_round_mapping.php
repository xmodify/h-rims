<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$stmTables = [
    'stm_ucs',
    'stm_ucs_kidney',
    'stm_ofc',
    'stm_ofc_csop',
    'stm_ofc_cipn',
    'stm_bkk',
    'stm_bkk_kidney',
    'stm_bmt',
    'stm_bmt_kidney',
    'stm_srt',
    'stm_pvt',
    'stm_lgo',
    'stm_lgo_kidney',
    'stm_sss_kidney',
];

// Get sample distinct rounds from all Smart Money files and DB
$rawBatches = App\Models\SmartMoneyBatch::all();
$rounds = [];
foreach ($rawBatches as $b) {
    if ($b->round_no) $rounds[$b->round_no] = ['batch' => $b->batch_no, 'account' => $b->account_code, 'fund' => $b->fund_main];
}

// Add known rounds from the 18 Batches
$rounds['6908_IP_02'] = ['batch' => '3271', 'account' => '1102050101.202', 'fund' => 'กองทุนผู้ป่วยใน'];
$rounds['DCKD6931080031'] = ['batch' => '3270', 'account' => '1102050101.216/217', 'fund' => 'กองทุนไตวายเรื้อรัง'];
$rounds['6908_IP_02_อุทธรณ์'] = ['batch' => '3269', 'account' => '1102050101.202', 'fund' => 'กองทุนผู้ป่วยใน'];
$rounds['6908_OP_04_อุทธรณ์'] = ['batch' => '3223A', 'account' => '4301020105.228', 'fund' => 'งบแพทย์แผนไทย'];
$rounds['6908_OP_06'] = ['batch' => '3221', 'account' => '4301020105.228', 'fund' => 'งบแพทย์แผนไทย'];
$rounds['LGO-HD69-M11'] = ['batch' => '3150', 'account' => '1102050102.801/802', 'fund' => 'สวัสดิการรักษาพยาบาลของพนักงานส่วนท้องถิ่น'];
$rounds['6908_OP_02'] = ['batch' => '3167', 'account' => '4301020105.228', 'fund' => 'ค่าบริการสาธารณสุขเพิ่มเติม'];
$rounds['DDSA6931080031'] = ['batch' => '3152', 'account' => '4301020105.228', 'fund' => 'ค่าบริการฟื้นฟูสมรรถภาพ'];
$rounds['OP-AE ในจังหวัด/ข้ามจังหวัด (Fix Cost)'] = ['batch' => '3128', 'account' => '4301020105.214', 'fund' => 'กองทุนผู้ป่วยนอก'];
$rounds['6908_OP_01'] = ['batch' => '3034', 'account' => '1102050101.222', 'fund' => 'กองทุน CENTRAL REIMBURSE'];

echo "=== MAPPING ROUND_NO TO STM TABLES ===\n";
foreach ($rounds as $rNo => $info) {
    echo "Round: '{$rNo}' (Batch {$info['batch']} | {$info['account']} | {$info['fund']})\n";
    $foundTables = [];
    foreach ($stmTables as $tbl) {
        if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'round_no')) {
            $cnt = DB::table($tbl)->where('round_no', $rNo)->count();
            if ($cnt > 0) {
                $foundTables[] = "{$tbl} ({$cnt} แถว)";
            }
        }
    }
    if (!empty($foundTables)) {
        echo "  ==> MATCHED IN: " . implode(', ', $foundTables) . "\n";
    } else {
        echo "  ==> Status: ยังไม่ได้นำเข้าไฟล์ STM ในระบบ (หรือเป็นงวดเหมาจ่ายที่ไม่มี STM รายตัว)\n";
    }
    echo "------------------------------------------------------------\n";
}
