<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$cRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/2153.fe2a1dd64a0e8426.js');
$js = $cRes->body();

preg_match_all('/download[A-Za-z0-9_]*\s*\([^\)]*\)\s*\{/u', $js, $m);
echo "Download functions in 2153:\n";
print_r($m[0]);

// Also look for downloadExcelHost definition
preg_match_all('/downloadExcelHost\s*[:=]\s*\{[^\}]+\}/u', $js, $h);
if (!empty($h[0])) {
    echo "downloadExcelHost in 2153:\n";
    print_r($h[0]);
}

// Check main.js
$mRes = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$mJs = $mRes->body();
preg_match_all('/downloadExcelHost\s*[:=]\s*\{[^\}]+\}/u', $mJs, $mh);
if (!empty($mh[0])) {
    echo "downloadExcelHost in main.js:\n";
    print_r($mh[0]);
}

// Also search for "downloadExcel" in main.js
preg_match_all('/download[A-Za-z0-9_]*Host/u', $mJs, $mh2);
echo "download*Host in main.js:\n";
print_r(array_unique($mh2[0]));

// Search for any occurrence of eclaim or export or excel in main.js and 2153
preg_match_all('/https?:\/\/[a-zA-Z0-9\.\-\_\/]+(?:excel|Download|export|sev_)[a-zA-Z0-9\.\-\_\/\?]*/iu', $mJs . $js, $urls);
echo "Matched URLs:\n";
print_r(array_unique($urls[0]));
