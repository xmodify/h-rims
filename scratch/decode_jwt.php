<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$user = DB::table('users')->where('id', 3)->first();
$token = $user->eclaim_session_token;

if (preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $token, $m)) {
    $jwt = $m[1];
    $parts = explode('.', $jwt);
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    echo "ACCESS_TOKEN Payload:\n";
    print_r(array_intersect_key($payload, array_flip(['sub', 'name', 'preferred_username', 'email', 'hospMain', 'iss', 'aud', 'exp', 'scope', 'resource_access'])));
} else {
    echo "No ACCESS_TOKEN found\n";
}
