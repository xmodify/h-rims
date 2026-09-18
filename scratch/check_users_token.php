<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== USER RECORDS WITH SESSION TOKENS ===\n";
$usersWithTokens = DB::table('users')
    ->whereNotNull('eclaim_session_token')
    ->where('eclaim_session_token', '<>', '')
    ->select('id', 'name', 'eclaim_session_user', 'eclaim_session_time', DB::raw('LENGTH(eclaim_session_token) as token_len'))
    ->get();

print_r($usersWithTokens);

$firstUser = DB::table('users')
    ->whereNotNull('eclaim_session_token')
    ->where('eclaim_session_token', '<>', '')
    ->first();

if ($firstUser) {
    echo "\nSample Token Cookies (keys):\n";
    preg_match_all('/([a-zA-Z0-9_\-]+)=/', $firstUser->eclaim_session_token, $matches);
    print_r(array_unique($matches[1] ?? []));
}
