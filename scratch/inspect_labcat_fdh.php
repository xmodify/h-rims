<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/../docs/10989 - โรงพยาบาลหัวตะพาน - LabCatalog.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->setActiveSheetIndex(0);
for ($row = 5; $row <= 10; $row++) {
    $rowVals = [];
    for ($col = 'A'; $col <= 'O'; $col++) {
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $val = $val->getPlainText();
        }
        $rowVals[$col] = $val;
    }
    echo "Row $row: " . json_encode($rowVals, JSON_UNESCAPED_UNICODE) . "\n";
}
