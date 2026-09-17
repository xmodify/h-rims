<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\SmartMoneyController;

$file1 = 'docs/6908_OP_01_10989_17 ก.ย. 2569.xlsx';
if (file_exists($file1)) {
    echo "=== Testing importDetailExcel on $file1 ===\n";
    $uploadedFile = new UploadedFile($file1, basename($file1), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $req = Request::create('/import/smart-money/import-detail', 'POST', [
        'budget_year' => 2569
    ], [], [
        'detail_excel' => $uploadedFile
    ]);
    $req->headers->set('Accept', 'application/json');

    $controller = new SmartMoneyController();
    $res = $controller->importDetailExcel($req);

    echo "Status Code: " . $res->getStatusCode() . "\n";
    echo "Content: " . $res->getContent() . "\n";
}

$file2 = 'docs/6908_IP_02_10989_17 ก.ย. 2569.xlsx';
if (file_exists($file2)) {
    echo "\n=== Testing importDetailExcel on $file2 ===\n";
    $uploadedFile = new UploadedFile($file2, basename($file2), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $req = Request::create('/import/smart-money/import-detail', 'POST', [
        'budget_year' => 2569
    ], [], [
        'detail_excel' => $uploadedFile
    ]);
    $req->headers->set('Accept', 'application/json');

    $controller = new SmartMoneyController();
    $res = $controller->importDetailExcel($req);

    echo "Status Code: " . $res->getStatusCode() . "\n";
    echo "Content: " . $res->getContent() . "\n";
}
