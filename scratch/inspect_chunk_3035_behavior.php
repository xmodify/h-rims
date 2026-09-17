<?php
$js = file_get_contents('scratch/chunk_3035.js');
echo "Length: " . strlen($js) . "\n";

// Find all occurrences of navigate or summary-detail
preg_match_all('/summary-detail[^\`"\'\)]+/i', $js, $m);
print_r(array_unique($m[0]));

// Find all occurrences of download or pdf or report
preg_match_all('/[a-zA-Z0-9_\$]+\.[a-zA-Z0-9_\$]+\([^\)]*\)\s*\{[^}]*download[^}]*\}/i', $js, $m2);
print_r($m2[0]);

// Find buttons / icons
preg_match_all('/(?:getDownload|print|report|budgetSummary)[a-zA-Z0-9_\$]*/i', $js, $m3);
print_r(array_unique($m3[0]));
