<?php
require 'vendor/autoload.php';

$js = file_get_contents('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$pos = strpos($js, 'intercept(');
while ($pos !== false) {
    echo "--- intercept at $pos ---\n";
    echo substr($js, max(0, $pos - 100), 500) . "\n\n";
    $pos = strpos($js, 'intercept(', $pos + 20);
    if ($pos > 3000000) break;
}
