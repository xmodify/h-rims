<?php

$zipPath = 'd:\\Project Laravel\\h-rims\\docs\\10703_SSOPACD_202606.zip';
if (!file_exists($zipPath)) {
    echo "Zip file not found at $zipPath\n";
    exit;
}

$zip = new ZipArchive();
if ($zip->open($zipPath) === TRUE) {
    echo "Zip opened successfully. Number of files: " . $zip->numFiles . "\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        echo "File $i: $filename\n";
        if (str_ends_with(strtolower($filename), '.txt') || str_ends_with(strtolower($filename), '.xml')) {
            // print first 5 lines of the file
            $content = $zip->getFromIndex($i);
            $lines = explode("\n", $content);
            echo "--- First 10 lines of $filename ---\n";
            for ($j = 0; $j < min(10, count($lines)); $j++) {
                echo $lines[$j] . "\n";
            }
            echo "-----------------------------------\n";
        }
    }
    $zip->close();
} else {
    echo "Failed to open zip file\n";
}
