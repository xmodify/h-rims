<?php
$content = file_get_contents('app/Http/Controllers/ClaimOpController.php');
$lines = explode("\n", $content);
$methods = [
    'ucs_kidney', 'ucs',
    'ofc_kidney', 'ofc',
    'lgo_kidney', 'lgo',
    'bkk_kidney', 'bkk',
    'bmt_kidney', 'bmt',
    'sss_kidney', 'sss'
];
$in_method = false;
$method_name = '';
$start_line = 0;
foreach ($lines as $idx => $line) {
    foreach ($methods as $m) {
        if (preg_match('/public function ' . $m . '\b/', $line)) {
            $in_method = true;
            $method_name = $m;
            $start_line = $idx + 1;
        }
    }
    if ($in_method) {
        if (trim($line) == '}' && $idx > $start_line + 10 && strpos($lines[$idx-1], 'return view') !== false) {
            $in_method = false;
            echo "Method $method_name: Start Line $start_line, End Line " . ($idx + 1) . "\n";
        }
    }
}
