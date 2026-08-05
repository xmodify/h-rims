<?php
$file = 'd:/Project Laravel/h-rims/app/Http/Controllers/SssExportController.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'license') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
