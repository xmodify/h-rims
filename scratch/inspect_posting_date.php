<?php
$js = file_get_contents('scratch/chunk_2153.js');

$pos = 0;
while (($pos = strpos($js, 'postingDate', $pos)) !== false) {
    echo "=== postingDate at $pos ===\n";
    echo substr($js, max(0, $pos - 100), 300) . "\n\n";
    $pos += strlen('postingDate');
}
