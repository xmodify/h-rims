<?php
$content = file_get_contents('app/Http/Controllers/ClaimOpController.php');
$lines = explode("\n", $content);
$in_sss_main = false;
foreach ($lines as $idx => $line) {
    if (strpos($line, 'public function sss_main') !== false) {
        $in_sss_main = true;
    }
    if ($in_sss_main) {
        if (strpos($line, 'public function sss_detail') !== false) {
            $in_sss_main = false;
        }
        if (strpos($line, 'kidney') !== false || strpos($line, '71641') !== false) {
            echo "Line " . ($idx + 1) . ": $line\n";
        }
    }
}
