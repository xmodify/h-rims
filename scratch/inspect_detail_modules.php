<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

function inspectChunk($name, $file) {
    echo "========================================================\n";
    echo "Inspecting {$name}: {$file}\n";
    echo "========================================================\n";
    $res = Http::withoutVerifying()->get("https://smt.nhso.go.th/smtf/{$file}");
    $js = $res->body();
    echo "Length: " . strlen($js) . " bytes\n";
    
    // Find all API endpoints called (e.g. /api/...)
    preg_match_all('/(?:post|get|delete|put)\([`\'"]([^`\'"]+)[`\'"]/i', $js, $apis);
    echo "HTTP calls:\n";
    print_r(array_unique($apis[1]));
    
    // Find excel / export / download functions
    preg_match_all('/(?:export|excel|download)[A-Za-z0-9_]*\s*\([^\)]*\)\s*\{[^\}]{0,600}/iu', $js, $funcs);
    echo "Export / Excel / Download functions:\n";
    foreach ($funcs[0] as $f) {
        echo $f . "\n---\n";
    }
}

inspectChunk('LGO-HD (9050)', '9050.dd6372a3a88aca37.js');
inspectChunk('Lgohd (6295)', '6295.7a385a9b7f390beb.js');
inspectChunk('DMIS (9933)', '9933.df09f300c5af9f4d.js');
