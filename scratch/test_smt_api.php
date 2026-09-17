<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

$user = DB::table('users')->whereNotNull('eclaim_session_token')->first();
$token = $user ? $user->eclaim_session_token : null;
echo "User: " . ($user ? $user->name : 'none') . "\n";
echo "Token: " . ($token ? substr($token, 0, 30) . "..." : 'none') . "\n";

$hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';

// Test SMT API
$urls = [
    "https://smt.nhso.go.th/smtf/api/budgetreport/search",
    "https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport",
    "https://smt.nhso.go.th/smtf/api/budget/list",
    "https://smt.nhso.go.th/smtf/api/auth/profile",
];

foreach ($urls as $url) {
    try {
        $res = Http::withoutVerifying()->timeout(5)->get($url);
        echo "GET $url -> Status: " . $res->status() . " (Size: " . strlen($res->body()) . ")\n";
    } catch (\Exception $e) {
        echo "GET $url -> Error: " . $e->getMessage() . "\n";
    }
}
