<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Table stm_seamless_dmis ===\n";
$dmis = DB::table('stm_seamless_dmis')->first();
print_r((array)$dmis);

echo "\n=== Table stm_ucs ===\n";
$ucs = DB::table('stm_ucs')->first();
print_r((array)$ucs);
