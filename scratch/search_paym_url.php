<?php
$js = file_get_contents('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
$pos = strpos($js, 'downloadPAYMFileName');
while ($pos !== false) {
    echo "Found at $pos:\n" . substr($js, max(0, $pos - 150), 400) . "\n-----------------\n";
    $pos = strpos($js, 'downloadPAYMFileName', $pos + 1);
}
