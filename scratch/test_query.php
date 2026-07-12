<?php
$file_path = __DIR__ . '/../scratch/zip_extract/BILLTRAN20260713.txt';
if (file_exists($file_path)) {
    $content_bytes = file_get_contents($file_path);
    $content = iconv('TIS-620', 'UTF-8//IGNORE', $content_bytes);
    
    // Replace raw & with &amp; if not already escaped
    // A simple regex replacement: match & but not &amp; or other entities
    // In our case, we can just replace '&' with '&amp;'
    $escaped = str_replace('&', '&amp;', $content);
    
    // Convert back to TIS-620
    $encoded = iconv('UTF-8', 'TIS-620//IGNORE', $escaped);
    
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $ok = $doc->loadXML($encoded);
    if (!$ok) {
        echo "XML Validation FAILED after escaping:\n";
        foreach (libxml_get_errors() as $error) {
            echo "  Line {$error->line}: {$error->message}\n";
        }
        libxml_clear_errors();
    } else {
        echo "XML Validation SUCCESS after escaping!\n";
    }
} else {
    echo "BILLTRAN file not found.\n";
}
