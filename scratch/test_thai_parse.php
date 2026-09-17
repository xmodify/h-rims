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
    'keyword' => ''
]);

// Test parse date on Thai date string
$ref = new \ReflectionClass($controller);
$pMethod = $ref->getMethod('parseDate');
$pMethod->setAccessible(true);
echo "Parsed start: " . $pMethod->invoke($controller, '1 ก.ย. 2569') . "\n";
echo "Parsed end: " . $pMethod->invoke($controller, '17 ก.ย. 2569') . "\n";
