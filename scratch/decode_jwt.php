<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);
$jwt = $m[1] ?? null;

$parts = explode('.', $jwt);
$header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

echo "=== HEADER ===\n";
print_r($header);

echo "=== PAYLOAD ===\n";
print_r($payload);
