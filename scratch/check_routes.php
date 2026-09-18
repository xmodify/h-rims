<?php
$js = file_get_contents('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
foreach (['summary-detail-lgohd', 'summary-detail-dmis', 'summary-detail/', 'report/person'] as $kw) {
    $pos = strpos($js, $kw);
    echo "$kw: " . ($pos !== false ? "Found at $pos" : "Not found") . "\n";
    if ($pos !== false) {
        echo substr($js, max(0, $pos - 50), 300) . "\n-----------------\n";
    }
}
