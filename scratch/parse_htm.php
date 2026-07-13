<?php
$htmFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.HTM';
if (!file_exists($htmFile)) {
    die("File not found: $htmFile\n");
}

$content = file_get_contents($htmFile);

// Let's count how many times "<tr>" or "1,500.00" or similar appears
// Or let's parse using DOMDocument
$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="UTF-8">' . $content);

$rows = $dom->getElementsByTagName('tr');
echo "Total rows in HTML: " . $rows->length . "\n";

$headers = [];
$dataRows = [];

foreach ($rows as $row) {
    $cells = $row->getElementsByTagName('td');
    if ($cells->length === 0) {
        $cells = $row->getElementsByTagName('th');
    }
    
    $rowText = [];
    foreach ($cells as $cell) {
        $rowText[] = trim(preg_replace('/\s+/', ' ', $cell->textContent));
    }
    
    if (empty($rowText)) {
        continue;
    }
    
    if (strpos(implode(' ', $rowText), 'เลขที่ทั่วไป') !== false || strpos(implode(' ', $rowText), 'ลำดับ') !== false) {
        $headers[] = $rowText;
    } else {
        $dataRows[] = $rowText;
    }
}

echo "Found " . count($headers) . " header-like rows.\n";
echo "Found " . count($dataRows) . " data rows.\n";

// Write all data rows to a file
$outFile = 'd:/Project Laravel/h-rims/scratch/htm_rows.txt';
$fh = fopen($outFile, 'w');
foreach ($dataRows as $i => $r) {
    fwrite($fh, sprintf("%3d: %s\n", $i + 1, implode(' | ', $r)));
}
fclose($fh);

echo "Written all data rows to $outFile\n";

// Let's print out rows containing specific status or text
echo "\n--- Interesting rows in HTM ---\n";
foreach ($dataRows as $i => $r) {
    $rowStr = implode(' | ', $r);
    // Print rows that look like they have a hold or status or zero amount
    if (strpos($rowStr, 'รอ') !== false || strpos($rowStr, 'ไม่') !== false || strpos($rowStr, 'Hold') !== false || strpos($rowStr, ' 0.00 ') !== false) {
        echo ($i + 1) . ": $rowStr\n";
    }
}
