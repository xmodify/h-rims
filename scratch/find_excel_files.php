<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$files = glob(storage_path('app/public/smart_money/*'));
if (empty($files)) {
    $files = glob(public_path('uploads/*'));
}
if (empty($files)) {
    $files = glob(base_path('*.xlsx'));
}
echo "Found files:\n";
print_r($files);

foreach (glob(storage_path('app/*')) as $f) {
    echo $f . "\n";
}
