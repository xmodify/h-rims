<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "stm_ucs_kidney cols:\n";
print_r(Schema::getColumnListing('stm_ucs_kidney'));
echo "\nstm_ucs_kidney rows:\n";
print_r(DB::table('stm_ucs_kidney')->limit(3)->get()->toArray());

echo "\nstm_lgo_kidney cols:\n";
print_r(Schema::getColumnListing('stm_lgo_kidney'));
echo "\nstm_lgo_kidney rows:\n";
print_r(DB::table('stm_lgo_kidney')->limit(3)->get()->toArray());
