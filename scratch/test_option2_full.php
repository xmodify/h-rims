<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmartMoneyBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// 1. Log in a user (e.g. admin or test user)
$user = User::first();
if ($user) {
    Auth::login($user);
    echo "Logged in as: " . $user->name . " (status: " . $user->status . ")\n";
}

$controller = new \App\Http\Controllers\SmartMoneyController();

// 2. Call index
$request = new Request(['budget_year' => 2569]);
$response = $controller->index($request);
$viewData = $response->getData();

echo "--- INDEX VIEW DATA ---\n";
echo "Total Count (Batches): " . $viewData['total_count'] . "\n";
echo "Total Net Amount: " . number_format($viewData['total_net_amount'], 2) . "\n";
echo "Batches in collection: " . count($viewData['batches']) . "\n";

foreach ($viewData['batches'] as $b) {
    echo " > Batch: {$b->batch_no} | Sub-items: {$b->items->count()} | Net: " . number_format($b->total_net_amount, 2) . " | Rec: " . ($b->receive_no ?: 'None') . " | Issuer: " . ($b->receipt_by ?: '-') . "\n";
    foreach ($b->items as $idx => $it) {
        echo "     [#{$idx}] Round: {$it->round_no} | Acc: {$it->account_code} | Fund: {$it->fund_sub} | Net: " . number_format($it->net_amount, 2) . " | Rec: " . ($it->receive_no ?: '-') . " | By: " . ($it->receipt_by ?: '-') . "\n";
    }
}

// 3. Test issuing a receipt for Batch 3065
echo "\n--- TEST ISSUING RECEIPT FOR BATCH 3065 ---\n";
$reqReceipt = new Request([
    'batch_no' => '3065',
    'receive_no' => 'RC-69/001',
    'receipt_date' => '2026-09-17'
]);
$resReceipt = $controller->updateReceipt($reqReceipt);
echo "Update Receipt Result: " . json_encode($resReceipt->getData(), JSON_UNESCAPED_UNICODE) . "\n";

// Check database rows for 3065
$b3065Rows = SmartMoneyBatch::where('batch_no', '3065')->get();
echo "Checking DB for Batch 3065 rows:\n";
foreach ($b3065Rows as $r) {
    echo " - ID: {$r->id} | Batch: {$r->batch_no} | Round: {$r->round_no} | RecNo: {$r->receive_no} | Date: {$r->receipt_date} | By: {$r->receipt_by}\n";
}

// Clean up receipt for 3065 back to null to keep pristine state if needed, or leave it
echo "\nTesting reverting back to null...\n";
SmartMoneyBatch::where('batch_no', '3065')->update([
    'receive_no' => null,
    'receipt_date' => null,
    'receipt_by' => null,
]);
echo "Reverted Batch 3065 back to null.\n";
