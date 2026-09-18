<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rounds = DB::table('stm_ucs')
    ->whereNotNull('round_no')
    ->where('round_no', '<>', '')
    ->groupBy('round_no')
    ->select('round_no', DB::raw('count(*) as count'), DB::raw('sum(receive_total) as sum_net'))
    ->orderByDesc('round_no')
    ->limit(10)
    ->get();

echo "Top 10 Recent Rounds in stm_ucs:\n";
foreach ($rounds as $r) {
    echo "Round: {$r->round_no} | Count: {$r->count} | Sum: " . number_format($r->sum_net, 2) . "\n";
}
