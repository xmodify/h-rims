<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$runtime = $res->body();

$chunks = ['2153', '8402', '2688', '9050'];
foreach ($chunks as $chk) {
    if (preg_match('/' . $chk . ':"([a-f0-9]+)"/i', $runtime, $m)) {
        echo "Chunk {$chk} hash: " . $m[1] . "\n";
        $cUrl = "https://smt.nhso.go.th/smtf/{$chk}.{$m[1]}.js";
        $cRes = Http::withoutVerifying()->get($cUrl);
        $js = $cRes->body();
        echo "Chunk {$chk} size: " . strlen($js) . " bytes\n";

        // Find API calls like post(`${m}/...`) or post(`${this...}/...`)
        preg_match_all('/post\([`"][^`"]*[`"]/i', $js, $posts);
        echo "POST calls in {$chk}:\n";
        print_r(array_unique($posts[0] ?? []));

        preg_match_all('/["\'](\/[a-zA-Z0-9_\-\/]+)["\']/u', $js, $paths);
        $filteredPaths = array_filter($paths[1], fn($p) => str_starts_with($p, '/') && !str_starts_with($p, '//') && strlen($p) > 2);
        echo "Paths in {$chk}:\n";
        print_r(array_slice(array_unique($filteredPaths), 0, 15));
        echo "===================================\n";
    }
}
