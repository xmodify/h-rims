<?php
$files = [
    'd:/Project Laravel/h-rims/app/Http/Controllers/ClaimOpController.php',
    'd:/Project Laravel/h-rims/app/Http/Controllers/SssExportController.php',
    'd:/Project Laravel/h-rims/app/Http/Controllers/CsopExportController.php'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        $content = file_get_contents($f);
        if (strpos($content, 'regist_no') !== false) {
            echo "Found in $f\n";
        }
    }
}
