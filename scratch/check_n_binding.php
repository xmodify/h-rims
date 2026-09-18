<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/9933.df09f300c5af9f4d.js');
$js = $res->body();

$pos = strpos($js, 't.YNc(22,N,4,0,"mat-chip",16)');
if ($pos !== false) {
    // Find template part 2
    $pos2 = strpos($js, '2&e', $pos);
    echo "Part 2 at $pos2:\n";
    echo substr($js, $pos2, 1000) . "\n";
}
