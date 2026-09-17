<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Models\SmartMoneyDetail;
use App\Models\SmartMoneyBatch;

$controller = new SmartMoneyController();

echo "=== IMPORTING DETAIL FILES INTO DATABASE ===\n";

// 1. Import 6908_IP_02
$ipFiles = glob(base_path('docs/*6908_IP*.xlsx'));
if (!empty($ipFiles)) {
    $ipFile = $ipFiles[0];
    echo "Importing IP File: " . basename($ipFile) . "\n";
    $uploadedFile = new UploadedFile($ipFile, basename($ipFile), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    $req = new Request([], [], [], [], ['detail_excel' => $uploadedFile]);
    $res = $controller->importDetailExcel($req);
    echo "IP Import Result: " . $res->getContent() . "\n";
}

// 2. Import 6908_OP_01
$opFiles = glob(base_path('docs/*6908_OP*.xlsx'));
if (!empty($opFiles)) {
    $opFile = $opFiles[0];
    echo "\nImporting OP File: " . basename($opFile) . "\n";
    $uploadedFile = new UploadedFile($opFile, basename($opFile), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    $req = new Request([], [], [], [], ['detail_excel' => $uploadedFile]);
    $res = $controller->importDetailExcel($req);
    echo "OP Import Result: " . $res->getContent() . "\n";
}

echo "\n=== VERIFYING DATABASE AFTER IMPORT ===\n";
$total = SmartMoneyDetail::count();
echo "Total Details in DB: {$total}\n";

$b3271Count = SmartMoneyDetail::where('batch_no', '3271')->count();
echo "Batch 3271 (6908_IP_02) count: {$b3271Count}\n";

$b3034Count = SmartMoneyDetail::where('batch_no', '3034')->count();
echo "Batch 3034 (6908_OP_01) count: {$b3034Count}\n";

echo "\n=== TESTING getDetailJson for Batch 3271 ===\n";
$reqApi = new Request(['per_page' => 5]);
$resApi = $controller->getDetailJson($reqApi, '3271');
$apiData = json_decode($resApi->getContent(), true);
echo "Status: " . $apiData['status'] . "\n";
echo "Batch info: Batch " . $apiData['batch']['batch_no'] . " | Patients: " . $apiData['stats']['total_count'] . " | Total Amount: " . $apiData['stats']['total_amount_formatted'] . "\n";
echo "First 2 patients:\n";
foreach (array_slice($apiData['data'], 0, 2) as $idx => $p) {
    echo "  [{$idx}] HN: {$p['hn']} | AN: {$p['an']} | Name: {$p['pt_name']} | Date: {$p['vstdate_thai']} | Total: {$p['receive_total_formatted']} | Fund: {$p['sub_fund']}\n";
}
