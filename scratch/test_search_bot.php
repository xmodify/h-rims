<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;

$u = App\Models\User::find(3);
auth()->login($u);

$controller = app()->make(SmartMoneyController::class);

$req = Request::create('/import/smart-money/search-bot', 'POST', [
    'start_date' => '2026-09-01',
    'end_date' => '2026-09-18',
    'keyword' => '',
]);

$res = $controller->searchBotStatements($req);
$data = $res->getData();
$items = $data->data;
echo "Total items: " . count($items) . "\n";
foreach ($items as $it) {
    if (in_array($it->round_no, ['LGO-HD69-M11', 'DCKD6931080031'])) {
        echo json_encode($it, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n-----------------\n";
    }
}
