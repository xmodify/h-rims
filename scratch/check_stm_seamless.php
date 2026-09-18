<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Columns of stm_seamless_dmis:\n";
print_r(Schema::getColumnListing('stm_seamless_dmis'));

$hasDckd = DB::table('stm_seamless_dmis')->where('round_no', 'DCKD6931080031')->count();
echo "\nRows matching DCKD6931080031: $hasDckd\n";

if ($hasDckd == 0) {
    echo "Check latest round_no in stm_seamless_dmis:\n";
    $latest = DB::table('stm_seamless_dmis')->select('round_no', DB::raw('count(*) as c'))->groupBy('round_no')->orderByDesc('round_no')->limit(10)->get();
    print_r($latest);
}

$hasLgo = DB::table('stm_lgo')->where('round_no', 'like', '%LGO-HD%')->count();
echo "\nRows in stm_lgo matching LGO-HD: $hasLgo\n";
