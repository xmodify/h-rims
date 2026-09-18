<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jsonFile = __DIR__ . '/smt_token.json';
if (!file_exists($jsonFile)) {
    echo "smt_token.json not found!\n";
    exit(1);
}

$data = json_decode(file_get_contents($jsonFile), true);
$token = $data['token'] ?? '';

if (!$token) {
    echo "Token is empty in json!\n";
    exit(1);
}

DB::table('users')->where('id', 3)->update([
    'eclaim_session_token' => 'ACCESS_TOKEN=' . $token
]);

echo "Updated DB users table for user 3 with SMT token successfully! Token length: " . strlen($token) . "\n";
