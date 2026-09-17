<?php
$js = file_get_contents('scratch/chunk_3035.js');
$pos = strpos($js, 'reloadData(){');
if ($pos !== false) {
    echo substr($js, $pos, 4000);
}
