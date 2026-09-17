<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;

$controller = new SmartMoneyController();
$searchReq = new Request([
    'start_date' => '2026-09-01',
    'end_date' => '2026-09-17',
]);

$searchRes = $controller->searchBotStatements($searchReq);
$searchData = json_decode($searchRes->getContent(), true);

echo "Found " . count($searchData['data']) . " items from search.\n";

$importReq = new Request(['items' => $searchData['data']]);
$importRes = $controller->importBotStatements($importReq);
echo "Import Response: " . $importRes->getContent() . "\n";
