<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dirs = [
    storage_path('app/temp_stm_api'),
    storage_path('app/public'),
    base_path('scratch'),
];

foreach ($dirs as $d) {
    echo "=== Dir: $d ===\n";
    foreach (glob($d . '/*') as $f) {
        echo basename($f) . " (" . filesize($f) . " bytes)\n";
    }
}
