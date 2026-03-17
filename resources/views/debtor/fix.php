<?php
$files = ['1102050101_308.blade.php', '1102050101_308_indiv_excel.blade.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'เธ') !== false) {
            $raw_bytes = mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
            $fixed_utf8 = iconv('TIS-620', 'UTF-8', $raw_bytes);
            if ($fixed_utf8) {
                file_put_contents($file, $fixed_utf8);
                echo "Fixed $file\n";
            }
        } else {
            echo "No mojibake found in $file\n";
        }
    }
}
