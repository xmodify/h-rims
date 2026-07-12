<?php
$content = file_get_contents('app/Http/Controllers/ClaimOpController.php');
$lines = explode("\n", $content);
$method_name = '';
foreach ($lines as $idx => $line) {
    if (preg_match('/public function ([a-zA-Z0-9_]+)/', $line, $matches)) {
        $method_name = $matches[1];
    }
    if (strpos($line, 'li.kidney = "Y"') !== false || strpos($line, 'li.kidney = \'Y\'') !== false || strpos($line, 'li.kidney="Y"') !== false) {
        if (strpos($line, 'NOT EXISTS') !== false || strpos($line, 'is_kidney') !== false) {
            echo "Line " . ($idx + 1) . " in method $method_name: $line\n";
        }
    }
}
