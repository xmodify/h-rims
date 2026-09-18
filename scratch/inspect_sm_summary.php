<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Project Laravel/h-rims/docs/nhso_1322973228.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

echo "Sheet Name: " . $sheet->getTitle() . "\n";
echo "Highest Row: " . $sheet->getHighestRow() . "\n";

for ($i = 1; $i <= 10; $i++) {
    $row = $sheet->rangeToArray("A{$i}:N{$i}")[0];
    echo "Row $i: " . json_encode(array_values(array_filter($row, fn($v) => $v !== null && $v !== '')), JSON_UNESCAPED_UNICODE) . "\n";
}
