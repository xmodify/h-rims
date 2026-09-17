<?php
$content = file_get_contents('resources/views/import/smart_money_index.blade.php');

$pos = 0;
while (($pos = mb_stripos($content, 'เกิดข้อผิดพลาด', $pos, 'UTF-8')) !== false) {
    echo "=== Match at $pos ===\n";
    echo mb_substr($content, max(0, $pos - 100), 300, 'UTF-8') . "\n\n";
    $pos += 10;
}

$pos = 0;
while (($pos = mb_stripos($content, 'importSummary', $pos, 'UTF-8')) !== false) {
    echo "=== importSummary at $pos ===\n";
    echo mb_substr($content, max(0, $pos - 100), 300, 'UTF-8') . "\n\n";
    $pos += 10;
}
