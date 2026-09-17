<?php
$files = glob('scratch/*');
foreach ($files as $f) {
    if (stripos($f, 'detail') !== false || stripos($f, 'summary') !== false || stripos($f, 'scan') !== false) {
        echo "$f\n";
    }
}
