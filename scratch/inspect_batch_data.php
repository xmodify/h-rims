<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;
use Illuminate\Support\Facades\Http;

$user = User::find(3);
$token = $user->eclaim_session_token;

$res = Http::withoutVerifying()->withToken($token)->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', [
    'vendorId' => '0000010989',
    'dateStart' => '2026-09-01T00:00:00.000Z',
    'dateEnd' => '2026-09-18T23:59:59.999Z',
    'batchNo' => 3270,
]);

$items = $res->json('item') ?? [];
echo "Items found: " . count($items) . "\n";
foreach ($items as $it) {
    if (($it['batchNo'] ?? '') == 3270) {
        echo "=== BATCH 3270 ITEM ===\n";
        print_r($it);
    }
}
