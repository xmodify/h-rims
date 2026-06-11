<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/docs/claim/nhso_adp_code.xls';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, true);

echo "Total Rows: " . count($rows) . "\n";
echo "Header row (first 3 rows):\n";
for ($i = 1; $i <= 5; $i++) {
    if (isset($rows[$i])) {
        echo "Row $i: " . json_encode($rows[$i], JSON_UNESCAPED_UNICODE) . "\n";
    }
}
