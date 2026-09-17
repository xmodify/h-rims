<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;

$controller = new SmartMoneyController();
$req = new Request([
    'start_date' => '1 ก.ย. 2569',
    'end_date' => '17 ก.ย. 2569',
    'keyword' => '3271'
]);

$res = $controller->searchBotStatements($req);
$data = json_decode($res->getContent(), true);

echo "Keyword: 3271 -> Count: {$data['count']}\n";
foreach ($data['data'] as $item) {
    echo "  - Batch: {$item['batch_no']} | Account: {$item['account_code']} | Fund: {$item['fund_full']} | Net: {$item['net_amount_formatted']}\n";
}

$req2 = new Request([
    'start_date' => '1 ก.ย. 2569',
    'end_date' => '17 ก.ย. 2569',
    'keyword' => 'ไตวาย'
]);
$res2 = $controller->searchBotStatements($req2);
$data2 = json_decode($res2->getContent(), true);
echo "\nKeyword: ไตวาย -> Count: {$data2['count']}\n";
foreach ($data2['data'] as $item) {
    echo "  - Batch: {$item['batch_no']} | Account: {$item['account_code']} | Fund: {$item['fund_full']} | Net: {$item['net_amount_formatted']}\n";
}
