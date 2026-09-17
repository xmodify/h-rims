<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$res = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/');
$html = $res->body();
preg_match_all('/src="([^"]+\.js)"/', $html, $m);

echo "Loaded scripts in HTML:\n";
print_r($m[1]);

// Check chunks from runtime or main
$resRuntime = Http::withoutVerifying()->timeout(15)->get('https://smt.nhso.go.th/smtf/runtime.64184ff8a25acc5b.js');
$runtimeJs = $resRuntime->body();
preg_match_all('/([0-9]+\.[a-f0-9]+\.js)/', $runtimeJs, $chunkMatches);
$chunks = array_unique($chunkMatches[1]);
echo "Found " . count($chunks) . " chunks\n";

foreach ($chunks as $chk) {
    $chkRes = Http::withoutVerifying()->timeout(15)->get("https://smt.nhso.go.th/smtf/{$chk}");
    $cJs = $chkRes->body();
    if (stripos($cJs, 'budgetSummaryByVendorReportDetail') !== false || stripos($cJs, 'summary-detail') !== false) {
        echo "Found in chunk: {$chk} (size: " . strlen($cJs) . ")\n";
        // Find exports or Excel download
        $pos = strpos($cJs, 'budgetSummaryByVendorReportDetail');
        while ($pos !== false) {
            echo "--- match in $chk at $pos ---\n";
            echo substr($cJs, max(0, $pos - 200), 500) . "\n\n";
            $pos = strpos($cJs, 'budgetSummaryByVendorReportDetail', $pos + 30);
        }
    }
}
