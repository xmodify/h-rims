<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::find(3);
echo "User: " . $u->name . "\n";
echo "Session User: " . $u->eclaim_session_user . "\n";
echo "Session Time: " . $u->eclaim_session_time . "\n";
echo "Token Len: " . strlen($u->eclaim_session_token) . "\n";
echo "Token Sample: " . substr($u->eclaim_session_token, 0, 150) . "\n";

// Test getStatus response
$controller = app()->make(App\Http\Controllers\EclaimBotController::class);
$req = Illuminate\Http\Request::create('/import/eclaim-bot/status', 'POST');
auth()->login($u);
$res = $controller->getStatus($req);
echo "\ngetStatus result:\n" . json_encode($res->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
