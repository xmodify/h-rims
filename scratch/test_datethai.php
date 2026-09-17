<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "DateThai(2025-10-01): " . DateThai('2025-10-01') . "\n";
echo "DateThai(2026-09-30): " . DateThai('2026-09-30') . "\n";
