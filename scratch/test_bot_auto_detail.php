<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;
use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;

echo "=== TESTING importBotStatements WITH AUTO-DETAIL IMPORT ===\n";

$controller = new SmartMoneyController();

// Simulate importing Batch 3271 and Batch 3034 via bot
$items = [
    [
        'batch_no' => '3271',
        'round_no' => '6908_IP_02',
        'account_code' => '1102050101.202',
        'transfer_date' => '2026-09-15',
        'amount' => 1248382.53,
        'net_amount' => 1248382.53,
        'fund_main' => 'กองทุนผู้ป่วยใน',
        'fund_sub' => 'งบค่าบริการเพิ่มเติม',
    ],
    [
        'batch_no' => '3034',
        'round_no' => '6908_OP_01',
        'account_code' => '1102050101.222',
        'transfer_date' => '2026-09-02',
        'amount' => 84157.38,
        'net_amount' => 84157.38,
        'fund_main' => 'กองทุนผู้ป่วยนอก',
        'fund_sub' => 'งบผู้ป่วยนอก',
    ]
];

$req = new Request(['items' => $items]);
$res = $controller->importBotStatements($req);
echo "Response: " . $res->getContent() . "\n";

echo "Total Details in DB: " . SmartMoneyDetail::count() . "\n";
