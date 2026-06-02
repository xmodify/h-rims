<?php
$file = 'd:/Project Laravel/h-rims/resources/views/debtor/1102050101_402.blade.php';
$content = file_get_contents($file);

$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'debtor_search') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
unlink(__FILE__);
