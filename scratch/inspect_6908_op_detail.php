<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$files = glob(base_path('docs/*6908_OP*.xlsx'));
$file = $files[0];
$sp = IOFactory::load($file);
$sheet = $sp->getActiveSheet();
$rows = $sheet->toArray();

$headers = $rows[6];
print_r($headers);

echo "\n--- Sample Data Rows ---\n";
for ($i = 7; $i <= 15; $i++) {
    $r = $rows[$i];
    $combined = [];
    foreach ($headers as $cIdx => $h) {
        $combined[$h ?: "Col_$cIdx"] = $r[$cIdx] ?? null;
    }
    echo "Row $i:\n";
    print_r($combined);
    echo "----------------------------------------\n";
}
