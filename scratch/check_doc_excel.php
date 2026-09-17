<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'docs/nhso_1322973228.xlsx';
if (file_exists($file)) {
    $sp = IOFactory::load($file);
    $sheet = $sp->getActiveSheet();
    $rows = $sheet->toArray();
    echo "Total rows in sample file: " . count($rows) . "\n";
    echo "Headers: " . json_encode($rows[4], JSON_UNESCAPED_UNICODE) . "\n";
    echo "First data row: " . json_encode($rows[5], JSON_UNESCAPED_UNICODE) . "\n";
    echo "Last data row: " . json_encode($rows[count($rows)-1], JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "File not found\n";
}
