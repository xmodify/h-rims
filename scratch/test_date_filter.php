<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'docs/nhso_1322973228.xlsx';
$sp = IOFactory::load($file);
$sheet = $sp->getActiveSheet();
$rows = $sheet->toArray();

$startDate = '2026-09-01';
$endDate = '2026-09-17';

$controller = new SmartMoneyController();
$ref = new \ReflectionClass($controller);
$parseDateMethod = $ref->getMethod('parseDate');
$parseDateMethod->setAccessible(true);
$cleanNumMethod = $ref->getMethod('cleanNumber');
$cleanNumMethod->setAccessible(true);

$found = [];
foreach (array_slice($rows, 5) as $r) {
    if (empty($r[1]) || $r[0] === 'รวม') continue;
    $dateStr = $r[1];
    $dateYmd = $parseDateMethod->invoke($controller, $dateStr);
    if ($dateYmd && $dateYmd >= $startDate && $dateYmd <= $endDate) {
        $found[] = [
            'transfer_date' => $dateYmd,
            'transfer_date_thai' => $dateStr,
            'batch_no' => $r[2],
            'round_no' => $r[3],
            'account_code' => $r[4],
            'fund_main' => $r[5],
            'fund_sub' => $r[6],
            'net_amount' => $cleanNumMethod->invoke($controller, $r[14]),
        ];
    }
}

echo "Found in date range $startDate to $endDate: " . count($found) . " batches!\n";
foreach (array_slice($found, 0, 5) as $i => $item) {
    echo " [" . ($i+1) . "] Date: {$item['transfer_date_thai']} | Batch: {$item['batch_no']} | Round: {$item['round_no']} | Account: {$item['account_code']} | Net: " . number_format($item['net_amount'], 2) . "\n";
}
