<?php

$file = 'd:/Projec Laravel/h-rims/app/Http/Controllers/DebtorController.php';
$content = file_get_contents($file);

// Replace MAX with GROUP_CONCAT
$content = str_replace(
    'MAX(round_no) AS round_no, MAX(receipt_date) AS receipt_date, MAX(receive_no) AS receive_no',
    'GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no) AS receive_no',
    $content
);
$content = str_replace(
    'MAX(round_no) AS round_no, MAX(receipt_date) AS receipt_date, MAX(receive_no)',
    'GROUP_CONCAT(round_no) AS round_no, GROUP_CONCAT(receipt_date) AS receipt_date, GROUP_CONCAT(receive_no)',
    $content
);

// Replace COALESCE with CONCAT_WS (3 arguments)
$content = preg_replace(
    '/COALESCE\(([a-zA-Z0-9_]+)\.round_no,\s*([a-zA-Z0-9_]+)\.round_no,\s*([a-zA-Z0-9_]+)\.round_no\)/',
    'CONCAT_WS(CHAR(44), $1.round_no, $2.round_no, $3.round_no)',
    $content
);
$content = preg_replace(
    '/COALESCE\(([a-zA-Z0-9_]+)\.receipt_date,\s*([a-zA-Z0-9_]+)\.receipt_date,\s*([a-zA-Z0-9_]+)\.receipt_date\)/',
    'CONCAT_WS(CHAR(44), $1.receipt_date, $2.receipt_date, $3.receipt_date)',
    $content
);
$content = preg_replace(
    '/COALESCE\(([a-zA-Z0-9_]+)\.receive_no,\s*([a-zA-Z0-9_]+)\.receive_no,\s*([a-zA-Z0-9_]+)\.receive_no\)/',
    'CONCAT_WS(CHAR(44), $1.receive_no, $2.receive_no, $3.receive_no)',
    $content
);

// Replace COALESCE with CONCAT_WS (2 arguments)
$content = preg_replace(
    '/COALESCE\(([a-zA-Z0-9_]+)\.round_no,\s*([a-zA-Z0-9_]+)\.round_no\)/',
    'CONCAT_WS(CHAR(44), $1.round_no, $2.round_no)',
    $content
);
$content = preg_replace(
    '/COALESCE\(([a-zA-Z0-9_]+)\.receipt_date,\s*([a-zA-Z0-9_]+)\.receipt_date\)/',
    'CONCAT_WS(CHAR(44), $1.receipt_date, $2.receipt_date)',
    $content
);
$content = preg_replace(
    '/COALESCE\(([a-zA-Z0-9_]+)\.receive_no,\s*([a-zA-Z0-9_]+)\.receive_no\)/',
    'CONCAT_WS(CHAR(44), $1.receive_no, $2.receive_no)',
    $content
);

file_put_contents($file, $content);
echo "Successfully updated DebtorController.php\n";
