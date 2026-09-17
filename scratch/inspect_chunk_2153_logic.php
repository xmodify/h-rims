<?php
$js = file_get_contents('scratch/chunk_2153.js');

// Find all navigate calls
preg_match_all('/navigate\(\[[^\]]*\]/i', $js, $m);
print_r($m[0]);

// Find all occurrences of summary-detail
$pos = 0;
while (($pos = strpos($js, 'summary-detail', $pos)) !== false) {
    echo "=== summary-detail in chunk 2153 at $pos ===\n";
    echo substr($js, max(0, $pos - 200), 500) . "\n\n";
    $pos += strlen('summary-detail');
}

// Find all occurrences of getDownload or PDF
preg_match_all('/(?:getDownload|download|Pdf|pdf)[a-zA-Z0-9_\$]*/i', $js, $m2);
print_r(array_unique($m2[0]));
