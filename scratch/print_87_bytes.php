<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$user = DB::table('users')->where('id', 3)->first();
preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $user->eclaim_session_token, $m);

$res = Http::withHeaders([
    'Authorization' => 'Bearer ' . $m[1],
])->withoutVerifying()->get('https://smt.nhso.go.th/smtf/api/adapter/export-excel-lgo-hd-by-person?refDocNo=LGO-HD69-M11&vendorId=10989');

echo "Body:\n";
echo $res->body() . "\n";
print_r($res->json());
