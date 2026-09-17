<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;

$controller = new SmartMoneyController();
$req = new Request([
    'start_date' => '1 ก.ย. 2569',
    'end_date' => '17 ก.ย. 2569',
    'keyword' => ''
]);

$res = $controller->searchBotStatements($req);
$data = json_decode($res->getContent(), true);

echo "Status: {$data['status']}\n";
echo "Date Range: {$data['start_date']} ถึง {$data['end_date']}\n";
echo "Batch Count: " . ($data['batch_count'] ?? '-') . " Batches\n";
echo "Total Rows: {$data['count']} ผังบัญชี\n";
