<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$user = DB::table('users')->where('id', 3)->first();
echo "Token column length: " . strlen($user->eclaim_session_token) . "\n";
echo "First 150 chars: " . substr($user->eclaim_session_token, 0, 150) . "\n";

if (preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m)) {
    echo "Found JWT in cookie string!\n";
    $token = $m[1];
} else if (substr($user->eclaim_session_token, 0, 3) === 'eyJ') {
    echo "Direct JWT string!\n";
    $token = $user->eclaim_session_token;
} else {
    echo "Unknown format!\n";
    $token = $user->eclaim_session_token;
}

echo "JWT length: " . strlen($token) . "\n";
echo "JWT prefix: " . substr($token, 0, 20) . "...\n";
