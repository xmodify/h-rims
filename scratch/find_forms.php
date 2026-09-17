<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$js = $res->body();

// Look for formGroup or FormBuilder or formControlName in budget summary vendor component
preg_match_all('/([a-zA-Z0-9_$]+Form|[a-zA-Z0-9_$]+Group)\s*=\s*this\.[a-zA-Z0-9_$]+\.group\(\{([^}]+)\}/i', $js, $m);
foreach ($m[0] as $form) {
    if (stripos($form, 'vendor') !== false || stripos($form, 'budget') !== false || stripos($form, 'date') !== false || stripos($form, 'year') !== false) {
        echo "Found Form: $form\n\n";
    }
}
