<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

$pos = strpos($js, '["matColumnDef","transferDetailURL"]');
// Find the template function that follows
$posTpl = strpos($js, 'template:function', $pos);
echo "Template function:\n";
echo substr($js, $posTpl, 2000);
