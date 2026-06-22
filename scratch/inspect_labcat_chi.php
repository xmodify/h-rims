<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/../docs/LabCatalog_10989_25690622_150432.xlsx';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->setActiveSheetIndex(0);
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();

echo "Highest Row: $highestRow\n";
echo "Highest Column: $highestColumn\n";

echo "\n--- First 10 rows ---\n";
for ($row = 1; $row <= 10; $row++) {
    $rowVals = [];
    for ($col = 'A'; $col <= $highestColumn; $col++) {
        $val = $sheet->getCell($col . $row)->getValue();
        $rowVals[$col] = $val;
    }
    echo "Row $row: " . json_encode($rowVals, JSON_UNESCAPED_UNICODE) . "\n";
}
