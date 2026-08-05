<?php
$file = 'd:/Project Laravel/h-rims/app/Http/Controllers/CsopExportController.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (strpos($line, 'doctor') !== false || strpos($line, 'license') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
