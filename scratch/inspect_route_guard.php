<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$mainJs = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js')->body();

$pos = strpos($mainJs, 'budget/summary-detail-dmis');
echo substr($mainJs, max(0, $pos - 300), 500) . "\n\n";

$pos2 = strpos($mainJs, 'ไม่มีสิทธิ์เข้าใช้งานระบบ');
if ($pos2 !== false) {
    echo "=== 'ไม่มีสิทธิ์เข้าใช้งานระบบ' ===\n";
    echo substr($mainJs, max(0, $pos2 - 200), 500) . "\n\n";
}
