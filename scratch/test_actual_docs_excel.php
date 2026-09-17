<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\SmartMoneyController;

echo "=== Files in docs/ ===\n";
foreach (glob('docs/*') as $f) {
    echo $f . " (" . filesize($f) . " bytes)\n";
}

if (file_exists('docs/nhso_1322973228.xlsx')) {
    echo "\n=== Testing importSummaryExcel on docs/nhso_1322973228.xlsx ===\n";
    $uploadedFile = new UploadedFile('docs/nhso_1322973228.xlsx', 'nhso_1322973228.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $req = Request::create('/import/smart-money/import-summary', 'POST', [
        'budget_year' => 2569
    ], [], [
        'excel_file' => $uploadedFile
    ]);
    $req->headers->set('Accept', 'application/json');

    $controller = new SmartMoneyController();
    $res = $controller->importSummaryExcel($req);

    echo "Status Code: " . $res->getStatusCode() . "\n";
    echo "Content: " . $res->getContent() . "\n";
}
