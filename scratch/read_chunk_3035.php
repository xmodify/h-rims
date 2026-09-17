<?php
require 'vendor/autoload.php';

$js = file_get_contents('scratch/chunk_3035.js');
echo "Chunk 3035 length: " . strlen($js) . "\n";

// Find all form definitions and api calls in chunk 3035
preg_match_all('/(https?:\/\/[^\s"\']+|api\/[a-zA-Z0-9_\-\/]+|\/[a-zA-Z0-9_\-\/]+)/i', $js, $m);
echo "URLs/Paths in chunk 3035:\n";
print_r(array_unique($m[0]));

// Find functions in chunk 3035
preg_match_all('/([a-zA-Z0-9_$]+)\s*\([^)]*\)\s*\{[^}]*\}/i', $js, $m2);
echo "\nFunctions in chunk 3035:\n";
print_r(array_slice($m2[0], 0, 15));
