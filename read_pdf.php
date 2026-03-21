<?php
$file = 'd:/Projec Laravel/h-rims/' . urldecode('%E0%B8%84%E0%B8%B9%E0%B9%88%E0%B8%A1%E0%B8%B7%E0%B8%AD') . '/API ' . urldecode('%E0%B8%9B%E0%B8%B4%E0%B8%94%E0%B8%AA%E0%B8%B4%E0%B8%97%E0%B8%98%E0%B8%B4') . '_DataSet20231207_For_ORG_v8.0.pdf';
$content = @file_get_contents($file);

if (!$content) { die("Cannot read file: " . $file . "\n"); }

echo "File size: " . strlen($content) . " bytes\n\n";

// Extract readable ASCII strings >= 5 chars
preg_match_all('/[a-zA-Z0-9\/\-\_\.]{5,120}/', $content, $m);
$unique = array_unique($m[0]);

echo "--- API/URL related strings ---\n";
foreach ($unique as $u) {
    if (preg_match('/(api|save|authen|status|code|endpoint|nhso|url|path|service)/i', $u)) {
        echo $u . "\n";
    }
}
