<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== LGO-HD (9050) constructor ===\n";
$res1 = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/9050.dd6372a3a88aca37.js");
$js1 = $res1->body();
$pos1 = strpos($js1, 'constructor(');
echo substr($js1, $pos1, 1500) . "\n\n";

echo "=== DMIS (9933) constructor ===\n";
$res2 = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js");
$js2 = $res2->body();
$pos2 = strpos($js2, 'getDmisByPerson');
echo substr($js2, max(0, $pos2 - 200), 1000) . "\n\n";
