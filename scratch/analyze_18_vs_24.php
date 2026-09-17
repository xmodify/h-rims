<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'docs/nhso_1322973228.xlsx';
$sp = IOFactory::load($file);
$sheet = $sp->getActiveSheet();
$rows = $sheet->toArray();

$startDate = '2026-09-01';
$endDate = '2026-09-17';

$controller = new \App\Http\Controllers\SmartMoneyController();
$ref = new \ReflectionClass($controller);
$pMethod = $ref->getMethod('parseDate');
$pMethod->setAccessible(true);
$cMethod = $ref->getMethod('cleanNumber');
$cMethod->setAccessible(true);

$items = [];
$uniqueOrders = [];
$uniqueBatches = [];

foreach (array_slice($rows, 5) as $idx => $r) {
    if (empty($r[1]) || $r[0] === 'รวม') continue;
    $dateStr = $r[1];
    $dateYmd = $pMethod->invoke($controller, $dateStr);
    if ($dateYmd && $dateYmd >= $startDate && $dateYmd <= $endDate) {
        $orderNo = $r[0]; // ลำดับ
        $uniqueOrders[$orderNo] = ($uniqueOrders[$orderNo] ?? 0) + 1;
        $uniqueBatches[$r[2]] = ($uniqueBatches[$r[2]] ?? 0) + 1;
        $items[] = [
            'order' => $orderNo,
            'date' => $dateStr,
            'batch' => $r[2],
            'round' => $r[3],
            'account' => $r[4],
            'fund' => $r[5] . ' ' . $r[6],
            'net' => $cMethod->invoke($controller, $r[14])
        ];
    }
}

echo "Total raw rows in date range: " . count($items) . "\n";
echo "Distinct 'ลำดับ' (Order No): " . count($uniqueOrders) . " (Orders: " . implode(', ', array_keys($uniqueOrders)) . ")\n";
echo "Distinct 'Batch No': " . count($uniqueBatches) . "\n\n";

echo "Breakdown of 18 ลำดับ vs 24 rows:\n";
$currentOrder = null;
foreach ($items as $it) {
    if ($currentOrder !== $it['order']) {
        $currentOrder = $it['order'];
        echo "ลำดับ {$it['order']} | Batch: {$it['batch']} | Date: {$it['date']}\n";
    }
    echo "   -> Account: {$it['account']} | Fund: {$it['fund']} | Net: " . number_format($it['net'], 2) . "\n";
}
