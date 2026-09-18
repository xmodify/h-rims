<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/Project Laravel/h-rims/docs/6908_IP_02_10989_17 ก.ย. 2569.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

echo "Sheet Name: " . $sheet->getTitle() . "\n";
echo "Highest Row: " . $sheet->getHighestRow() . "\n";
echo "Highest Column: " . $sheet->getHighestColumn() . "\n";

for ($i = 1; $i <= 10; $i++) {
    $row = $sheet->rangeToArray("A{$i}:N{$i}")[0];
    echo "Row $i: " . json_encode(array_filter($row, fn($v) => $v !== null && $v !== ''), JSON_UNESCAPED_UNICODE) . "\n";
}
