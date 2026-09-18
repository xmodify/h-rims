<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SmartMoneyDetail;
use App\Models\SmartMoneyBatch;

echo "=== CHECKING TOKEN & DB STATUS ===\n";

$tokenFile = __DIR__ . '/smt_token.json';
if (file_exists($tokenFile)) {
    $data = json_decode(file_get_contents($tokenFile), true);
    echo "smt_token.json exists!\n";
    echo "Updated at: " . ($data['updated_at'] ?? 'N/A') . "\n";
    echo "Token length: " . strlen($data['token'] ?? '') . "\n";
} else {
    echo "smt_token.json NOT FOUND!\n";
}

$user = User::find(3);
if ($user) {
    echo "User 3 eclaim_session_token length: " . strlen($user->eclaim_session_token ?? '') . "\n";
}

$count = SmartMoneyDetail::count();
echo "\nTotal records in smart_money_details: $count\n";

$rounds = SmartMoneyDetail::groupBy('round_no')
    ->selectRaw('round_no, count(*) as total_patients, sum(receive_total) as total_amount')
    ->get();

echo "Grouped by round_no:\n";
print_r($rounds->toArray());

echo "Testing syncDetailFromSmt:\n";
$c = new \App\Http\Controllers\SmartMoneyController();
$req = new \Illuminate\Http\Request();
$syncRes = $c->syncDetailFromSmt($req, '3270');
echo $syncRes->getContent() . "\n";
