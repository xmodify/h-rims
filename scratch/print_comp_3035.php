<?php
$js = file_get_contents('scratch/chunk_3035.js');
// Find the class definition in chunk 3035
$pos = strpos($js, 'class ');
if ($pos === false) $pos = strpos($js, 'SummaryTransactionModule');
if ($pos !== false) {
    echo substr($js, max(0, $pos - 4000), 8000);
}
