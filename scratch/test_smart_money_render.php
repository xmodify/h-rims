<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\SmartMoneyController;

$controller = new SmartMoneyController();
$req = Request::create('/import/smart-money', 'GET', [
    'budget_year' => 2569,
    'start_date' => '2025-10-01',
    'end_date' => '2026-09-30'
]);

$response = $controller->index($req);
echo "Response status: OK! View rendered: " . $response->name() . "\n";
$data = $response->getData();
echo "Budget Year: " . $data['budget_year'] . "\n";
echo "Start Date: " . $data['start_date'] . "\n";
echo "End Date: " . $data['end_date'] . "\n";
echo "Batches count: " . $data['batches']->count() . "\n";
echo "Total count: " . $data['total_count'] . "\n";
echo "Issued count: " . $data['issued_count'] . "\n";
echo "Pending count: " . $data['pending_count'] . "\n";
