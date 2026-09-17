<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$ipFiles = glob(base_path('docs/*6908_IP*.xlsx'));
if (!empty($ipFiles)) {
    $ipFile = $ipFiles[0];
    echo "=== INSPECTING IP SMART MONEY FILE: " . basename($ipFile) . " ===\n";
    $sp = IOFactory::load($ipFile);
    $sheet = $sp->getActiveSheet();
    $rows = $sheet->toArray();
    
    echo "Header (Row 7):\n";
    print_r($rows[6]);
    
    echo "\nSample IP Data Rows (Rows 8-12):\n";
    for ($i = 7; $i <= min(11, count($rows) - 1); $i++) {
        $r = $rows[$i];
        echo "Row $i: HN={$r[2]} | AN={$r[3]} | Type={$r[4]} | CID={$r[5]} | Name={$r[6]} | Date={$r[7]} | Net={$r[8]} | REP={$r[9]} | Fund={$r[10]}/{$r[11]} | SEQ_NO=" . ($r[15] ?? '-') . " | Invoice=" . ($r[16] ?? '-') . "\n";
    }
}

$stmIpFiles = glob(base_path('docs/*IPUCS*.xls'));
if (!empty($stmIpFiles)) {
    $stmIpFile = $stmIpFiles[0];
    echo "\n=== INSPECTING STM IP FILE: " . basename($stmIpFile) . " ===\n";
    $sp = IOFactory::load($stmIpFile);
    $sheet = $sp->getActiveSheet();
    $rows = $sheet->toArray();
    
    echo "Total Rows in STM IP: " . count($rows) . "\n";
    // Find header
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $nonEmpty = array_filter($rows[$i], fn($v) => $v !== null && $v !== '');
        if (count($nonEmpty) > 5) {
            echo "STM IP Header at Row $i:\n";
            print_r(array_values($nonEmpty));
            if (isset($rows[$i+1])) {
                echo "STM IP Sample Data Row ($i+1):\n";
                print_r(array_values(array_filter($rows[$i+1], fn($v) => $v !== null && $v !== '')));
            }
            break;
        }
    }
}
