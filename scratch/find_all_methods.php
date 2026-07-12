<?php
$content = file_get_contents('app/Http/Controllers/ClaimOpController.php');
$lines = explode("\n", $content);
foreach ($lines as $idx => $line) {
    if (preg_match('/public function ([a-zA-Z0-9_]+)/', $line, $matches)) {
        echo "Line " . ($idx + 1) . ": " . $matches[1] . "\n";
    }
}
