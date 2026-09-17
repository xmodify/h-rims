<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$js = $res->body();

preg_match('/2153\s*:\s*["\']([^"\']+)["\']/', $js, $m);
echo "Chunk 2153: " . ($m[1] ?? 'not found') . "\n";
if (!empty($m[1])) {
    $cRes = Http::withoutVerifying()->timeout(15)->get("https://smt.nhso.go.th/smtf/2153.{$m[1]}.js");
    echo "Chunk 2153 downloaded, length: " . strlen($cRes->body()) . "\n";
    file_put_contents('scratch/chunk_2153.js', $cRes->body());
}
