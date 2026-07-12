<?php
$log_path = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($log_path)) {
    $content = file_get_contents($log_path);
    $lines = explode("\n", $content);
    $found = false;
    foreach ($lines as $line) {
        if (str_contains($line, '0669')) {
            echo $line . "\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "No logs found for session 0669.\n";
    }
} else {
    echo "Log file does not exist.\n";
}
