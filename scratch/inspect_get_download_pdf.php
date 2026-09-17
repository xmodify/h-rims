<?php
$js = file_get_contents('scratch/chunk_2153.js');

$pos = strpos($js, 'getDownloadPdf(e)');
if ($pos !== false) {
    echo substr($js, $pos, 800) . "\n";
}
