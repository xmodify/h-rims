<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$files = glob(base_path('docs/*6908_OP*.xlsx'));
if (empty($files)) {
    echo "File not found!\n";
    exit;
}

$file = $files[0];
echo "Analyzing file: " . basename($file) . "\n";

$spreadsheet = IOFactory::load($file);
$sheetNames = $spreadsheet->getSheetNames();
echo "Sheet names: " . implode(', ', $sheetNames) . "\n\n";

foreach ($sheetNames as $sName) {
    $sheet = $spreadsheet->getSheetByName($sName);
    $rows = $sheet->toArray();
    echo "=== Sheet: {$sName} (Total Rows: " . count($rows) . ") ===\n";
    
    // Print first 15 rows
    foreach (array_slice($rows, 0, 15) as $idx => $r) {
        $nonEmpty = array_filter($r, fn($v) => $v !== null && $v !== '');
        if (!empty($nonEmpty)) {
            echo "Row {$idx}: " . json_encode(array_values($nonEmpty), JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "\n";
}
