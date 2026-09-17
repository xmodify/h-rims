<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = glob(base_path('docs/*6908_IP*.xlsx'));
if (!empty($files)) {
    echo "Found IP file: " . basename($files[0]) . "\n";
    $sp = PhpOffice\PhpSpreadsheet\IOFactory::load($files[0]);
    $sheet = $sp->getActiveSheet();
    $rows = $sheet->toArray();
    for ($i = 0; $i < 12; $i++) {
        $nonEmpty = array_filter($rows[$i] ?? [], fn($v) => $v !== null && $v !== '');
        echo "Row {$i}: " . json_encode(array_values($nonEmpty), JSON_UNESCAPED_UNICODE) . "\n";
    }
}
