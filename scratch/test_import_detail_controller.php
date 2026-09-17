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

// 1. Test importing OP detail
$opFiles = glob(base_path('docs/*6908_OP*.xlsx'));
if (!empty($opFiles)) {
    echo "--- Testing OP Detail Import ---\n";
    $filePath = $opFiles[0];
    $uploadedFile = new UploadedFile($filePath, basename($filePath), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    
    $req = new Request([], [], [], [], ['detail_excel' => $uploadedFile]);
    $res = $controller->importDetailExcel($req);
    echo "Response: " . $res->getContent() . "\n";
}

// 2. Test importing IP detail
$ipFiles = glob(base_path('docs/*6908_IP*.xlsx'));
if (!empty($ipFiles)) {
    echo "\n--- Testing IP Detail Import ---\n";
    $filePath = $ipFiles[0];
    $uploadedFile = new UploadedFile($filePath, basename($filePath), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    
    $req = new Request([], [], [], [], ['detail_excel' => $uploadedFile]);
    $res = $controller->importDetailExcel($req);
    echo "Response: " . $res->getContent() . "\n";
}

// 3. Verify counts in DB
echo "\n--- Verifying Database Records ---\n";
$totalDetails = SmartMoneyDetail::count();
$opCount = SmartMoneyDetail::where('pt_type', 'ผู้ป่วยนอก')->count();
$ipCount = SmartMoneyDetail::where('pt_type', 'ผู้ป่วยใน')->count();
$withSeq = SmartMoneyDetail::whereNotNull('seq_no')->count();
$withAn = SmartMoneyDetail::whereNotNull('an')->count();
$withBatch = SmartMoneyDetail::whereNotNull('batch_no')->count();

echo "Total Details in DB: {$totalDetails}\n";
echo "OPD records: {$opCount}\n";
echo "IPD records: {$ipCount}\n";
echo "Records with seq_no: {$withSeq}\n";
echo "Records with AN: {$withAn}\n";
echo "Records linked with Batch: {$withBatch}\n";

// 4. Test getDetailJson
echo "\n--- Testing getDetailJson API for Batch 3034 ---\n";
$reqJson = new Request(['per_page' => 5]);
$resJson = $controller->getDetailJson($reqJson, '3034');
$json = json_decode($resJson->getContent(), true);
echo "Status: " . ($json['status'] ?? 'error') . "\n";
echo "Batch info: Batch {$json['batch']['batch_no']}, Total Patients: {$json['stats']['total_count']}, Amount: " . number_format($json['stats']['total_amount'], 2) . "\n";
echo "Sample Patient Row 0: " . json_encode($json['data'][0] ?? [], JSON_UNESCAPED_UNICODE) . "\n";

// 5. Test syncReceiptsFromStm
echo "\n--- Testing syncReceiptsFromStm ---\n";
$resSync = $controller->syncReceiptsFromStm(new Request());
echo "Sync Response: " . $resSync->getContent() . "\n";
