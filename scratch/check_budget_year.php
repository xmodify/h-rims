<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$years = DB::table('budget_year')->orderByDesc('LEAVE_YEAR_ID')->limit(6)->get();
foreach ($years as $y) {
    echo "ID: {$y->LEAVE_YEAR_ID} | Name: {$y->LEAVE_YEAR_NAME} | Begin: {$y->DATE_BEGIN} | End: {$y->DATE_END}\n";
}
