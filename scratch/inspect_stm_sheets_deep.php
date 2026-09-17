<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$fileSmartMoney = 'd:/Project Laravel/h-rims/docs/6908_IP_02_10989_17 ก.ย. 2569.xlsx';
$fileStm = 'd:/Project Laravel/h-rims/docs/STM_10989_IPUCS256908_02.xls';

$smSpreadsheet = IOFactory::load($fileSmartMoney);
$smSheet = $smSpreadsheet->getActiveSheet();
$smRows = $smSheet->toArray();

echo "=== SMART MONEY FILE ROWS ===\n";
for ($i = 0; $i < min(10, count($smRows)); $i++) {
    echo "Row $i: " . json_encode(array_values(array_filter($smRows[$i], fn($v) => $v !== null && $v !== '')), JSON_UNESCAPED_UNICODE) . "\n";
}

$stmSpreadsheet = IOFactory::load($fileStm);
echo "\n=== STM SHEETS & SUMMARIES ===\n";
foreach ($stmSpreadsheet->getSheetNames() as $sName) {
    $sheet = $stmSpreadsheet->getSheetByName($sName);
    $rows = $sheet->toArray();
    echo "\nSheet: [{$sName}] (Rows: " . count($rows) . ")\n";
    for ($i = 0; $i < min(7, count($rows)); $i++) {
        echo "  Row $i: " . json_encode(array_values(array_filter($rows[$i], fn($v) => $v !== null && $v !== '')), JSON_UNESCAPED_UNICODE) . "\n";
    }
}
