<?php
require 'vendor/autoload.php';

$js = file_get_contents('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$pos = 0;
while (($pos = strpos($js, 'getOverview', $pos)) !== false) {
    echo "--- getOverview at $pos ---\n";
    echo substr($js, max(0, $pos - 100), 300) . "\n\n";
    $pos += 15;
    if ($pos > 2000000) break;
}
