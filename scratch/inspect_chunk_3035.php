<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$runtime = $res->body();

if (preg_match('/3035:"([a-f0-9]+)"/i', $runtime, $m)) {
    echo "Chunk 3035 hash: " . $m[1] . "\n";
    $chunkUrl = "https://smt.nhso.go.th/smtf/3035.{$m[1]}.js";
    echo "Chunk URL: {$chunkUrl}\n";
    $cRes = Http::withoutVerifying()->get($chunkUrl);
    $cJs = $cRes->body();
    echo "Chunk 3035 size: " . strlen($cJs) . " bytes\n";
    
    // Find APIs inside chunk 3035
    preg_match_all('/(?:http|post|get)\([`"][^`"]*api[^`"]*[`"]/i', $cJs, $apis);
    print_r(array_unique($apis[0] ?? []));

    preg_match_all('/`\$\{[^`]+api\/[^`]+`/i', $cJs, $apis2);
    print_r(array_unique($apis2[0] ?? []));
}
