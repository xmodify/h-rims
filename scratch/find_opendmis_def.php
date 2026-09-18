<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/8402.73fad498a0cb1199.js');
$js = $res->body();

$pos = strpos($js, 'openDmisNewTab(');
// find definition
$posDef = strpos($js, 'openDmisNewTab(', $pos + 1);
if ($posDef !== false) {
    echo substr($js, $posDef, 1000);
} else {
    echo "only one match, searching backwards from first match:\n";
    echo substr($js, $pos, 1000);
}
