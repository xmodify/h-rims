<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "Fetching main.js from SMTF...\n";
$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();
echo "Downloaded JS size: " . strlen($js) . " bytes\n";

// Search for API paths or keywords: api/, /bs/, export, report, detail, individual
preg_match_all('/"(https?:\/\/[^"]+|\/[a-zA-Z0-9_\-\/]+(?:api|service|report|individual|detail|statement|transfer)[^"]*)"/i', $js, $matches);
echo "Potential API endpoints found: " . count($matches[1]) . "\n";
print_r(array_slice(array_unique($matches[1]), 0, 30));

// Search for strings containing smt or nhso.go.th
preg_match_all('/"(https?:\/\/[a-zA-Z0-9_\-\.]*nhso\.go\.th[^"]*)"/i', $js, $matches2);
echo "\nNHSO URLs found:\n";
print_r(array_unique($matches2[1]));
