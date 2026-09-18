<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== LGO-HD (9050) ===\n";
$res1 = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/9050.dd6372a3a88aca37.js");
$js1 = $res1->body();
$pos1 = strpos($js1, 'exportExcel(){');
echo substr($js1, $pos1, 1500) . "\n\n";

echo "=== DMIS (9933) ===\n";
$res2 = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js");
$js2 = $res2->body();
$pos2 = strpos($js2, 'exportExcel(){');
echo substr($js2, $pos2, 1500) . "\n\n";
