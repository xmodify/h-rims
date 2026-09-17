<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\SmartMoneyController;

$file1 = 'docs/6908_OP_01_10989_17 ก.ย. 2569.xlsx';
$file2 = 'docs/6908_IP_02_10989_17 ก.ย. 2569.xlsx';

$uploadedFiles = [
    new UploadedFile($file1, basename($file1), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
    new UploadedFile($file2, basename($file2), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true)
];

echo "=== Testing Multi-File Detail Import (2 files simultaneously) ===\n";

$req = Request::create('/import/smart-money/import-detail', 'POST', [
    'budget_year' => 2569
], [], [
    'detail_excel' => $uploadedFiles
]);
$req->headers->set('Accept', 'application/json');

$controller = new SmartMoneyController();
$res = $controller->importDetailExcel($req);

echo "Status Code: " . $res->getStatusCode() . "\n";
echo "Response Content: " . $res->getContent() . "\n";
