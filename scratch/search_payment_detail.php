<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Search for PaymentDetailAttachment
preg_match_all('/.{0,200}PaymentDetailAttachment.{0,200}/u', $js, $matches);

echo "PaymentDetailAttachment matches:\n";
foreach ($matches[0] as $idx => $m) {
    echo "[$idx] " . trim($m) . "\n";
}

// Search for environment T5 (baseUrl)
preg_match_all('/T5\s*:\s*"([^"]+)"/i', $js, $t5);
echo "T5 values:\n";
print_r(array_unique($t5[1] ?? []));
