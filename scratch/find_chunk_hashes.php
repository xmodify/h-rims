<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();

// Find chunk mapping: e.g. {9050:"...", 6295:"...", 9933:"..."}
preg_match('/\{(?:\d+\s*:\s*"[a-f0-9]+"\s*,?\s*)+\}/u', $mJs, $m);
if (!empty($m[0])) {
    echo "Found chunk map!\n";
    preg_match_all('/(\d+)\s*:\s*"([a-f0-9]+)"/u', $m[0], $chunks, PREG_SET_ORDER);
    $map = [];
    foreach ($chunks as $c) {
        $map[$c[1]] = $c[2];
    }
    foreach ([9050, 6295, 9933, 2688, 4276, 8402] as $id) {
        if (isset($map[$id])) {
            echo "Chunk {$id} => {$id}.{$map[$id]}.js\n";
        } else {
            echo "Chunk {$id} not in primary map\n";
        }
    }
} else {
    echo "Chunk map regex failed, searching specifically for 9050\n";
    $pos = strpos($mJs, '9050:');
    if ($pos !== false) {
        echo substr($mJs, $pos - 50, 200) . "\n";
    }
}
