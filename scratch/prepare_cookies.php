<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::find(3);
$rawToken = $u->eclaim_session_token;

// Parse cookies
$pairs = explode(';', $rawToken);
$cookies = [];
foreach ($pairs as $p) {
    $p = trim($p);
    if (!$p) continue;
    $parts = explode('=', $p, 2);
    if (count($parts) === 2) {
        $k = trim($parts[0]);
        $v = trim($parts[1]);
        $cookies[] = [
            'name' => $k,
            'value' => $v,
            'domain' => '.nhso.go.th',
            'path' => '/',
        ];
    }
}

echo "Parsed " . count($cookies) . " cookies.\n";
file_put_contents(__DIR__ . '/cookies_for_playwright.json', json_encode($cookies, JSON_PRETTY_PRINT));
echo "Saved scratch/cookies_for_playwright.json\n";
