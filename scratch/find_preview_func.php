<?php
$content = file_get_contents('resources/views/import/smart_money_index.blade.php');

$pos = 0;
while (($pos = mb_stripos($content, 'previewSelectedFile', $pos, 'UTF-8')) !== false) {
    echo "=== previewSelectedFile at $pos ===\n";
    echo mb_substr($content, max(0, $pos - 50), 200, 'UTF-8') . "\n\n";
    $pos += 20;
}
