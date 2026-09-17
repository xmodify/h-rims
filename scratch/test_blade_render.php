<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Http\Request;

View::share('errors', new ViewErrorBag());

$user = User::first();
if ($user) Auth::login($user);

try {
    $controller = new \App\Http\Controllers\SmartMoneyController();
    $request = new Request(['budget_year' => 2569]);
    $response = $controller->index($request);
    $html = $response->render();
    echo "Blade View Rendered Successfully! HTML Length: " . strlen($html) . " bytes\n";
} catch (\Exception $e) {
    echo "Render Error: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile() . "\n";
}
