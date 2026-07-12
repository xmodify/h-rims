<?php
$content = file_get_contents('resources/views/claim_op/ucs_incup.blade.php');
$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (stripos($line, 'modal') !== false || stripos($line, 'detail') !== false) {
        if (stripos($line, 'class') !== false || stripos($line, 'function') !== false || stripos($line, 'id=') !== false) {
            echo "Line " . ($idx + 1) . ": $line\n";
        }
    }
}
