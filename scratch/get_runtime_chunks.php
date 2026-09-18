<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$js = $res->body();

preg_match_all('/(\d+)\s*:\s*"([a-f0-9]+)"/u', $js, $chunks, PREG_SET_ORDER);
$map = [];
foreach ($chunks as $c) {
    $map[$c[1]] = $c[2];
}

foreach ([9050, 6295, 9933, 2688, 4276, 8402, 3800, 1138] as $id) {
    if (isset($map[$id])) {
        echo "Chunk {$id} => {$id}.{$map[$id]}.js\n";
    } else {
        echo "Chunk {$id} not in runtime map\n";
    }
}
