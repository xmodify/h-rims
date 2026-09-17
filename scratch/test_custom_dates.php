<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\SmartMoneyController;

$controller = new SmartMoneyController();
$req = Request::create('/import/smart-money', 'GET', [
    'budget_year' => 2569,
    'start_date' => '2026-09-01',
    'end_date' => '2026-09-17'
]);

$response = $controller->index($req);
$data = $response->getData();
echo "Custom range: " . $data['start_date'] . " to " . $data['end_date'] . "\n";
echo "Batches found: " . $data['batches']->count() . "\n";
foreach ($data['batches'] as $b) {
    echo "  - Batch {$b->batch_no} | Date: {$b->transfer_date} | Rounds: " . implode(',', $b->round_nos) . "\n";
}
