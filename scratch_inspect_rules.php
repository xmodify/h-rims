<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Load all rules
$rulesFiles = [
    'ins_rules.php',
    'ppfs_rules.php',
    'dental_rules.php',
    'acupuncture_rules.php',
    'blood_rules.php',
    'lab_rules.php',
    'xray_rules.php',
    'nursing_rules.php',
    'medical_device_rules.php',
    'procedure_rules.php',
    'physical_therapy_rules.php',
];

$allRules = [];
foreach ($rulesFiles as $file) {
    $path = config_path('claims/' . $file);
    if (file_exists($path)) {
        $data = require $path;
        foreach ($data as $code => $rule) {
            $allRules[$code] = [
                'file' => $file,
                'name' => $rule['name'] ?? '',
                'prices' => $rule['prices'] ?? []
            ];
        }
    }
}

// Fetch all ADP codes and names from HOSxP nhso_adp_code table
$adpCodes = DB::connection('hosxp')->select("SELECT nhso_adp_code, nhso_adp_code_name FROM nhso_adp_code");

echo "Checking discrepancies between HOSxP nhso_adp_code names and rules config prices..." . PHP_EOL . PHP_EOL;

$discrepancies = [];

foreach ($adpCodes as $adp) {
    $code = trim($adp->nhso_adp_code);
    $name = $adp->nhso_adp_code_name;

    if (!isset($allRules[$code])) {
        continue;
    }

    $rule = $allRules[$code];
    $prices = $rule['prices'];

    // Parse price and rights from name, e.g. "เดือยฟัน (Pin Tooth)(OFC&UCS&NHS 1000)"
    // Or "FS-การทำกายบำบัด...-100.00,FS-120.00"
    // Let's parse patterns like (RIGHT1&RIGHT2 1000) or (RIGHT1,RIGHT2 1000)
    // We also look for matches like "OFC&UCS&NHS 1000"
    if (preg_match('/\(([^)]+)\s+(\d+(?:\.\d+)?)\)/u', $name, $matches)) {
        $rightsStr = strtoupper($matches[1]);
        $expectedPrice = floatval($matches[2]);

        // Split by &, +, commas, or spaces
        $rights = preg_split('/[&+,;\s]+/u', $rightsStr);
        foreach ($rights as $right) {
            $right = trim($right);
            if (empty($right)) continue;

            // Map NHS to UCS (sometimes NHS represents UCS/LGO)
            $targetRights = [];
            if ($right === 'UCS' || $right === 'NHS' || $right === 'UC') {
                $targetRights[] = 'UCS';
            } elseif ($right === 'OFC') {
                $targetRights[] = 'OFC';
            } elseif ($right === 'LGO') {
                $targetRights[] = 'LGO';
            } elseif ($right === 'SSS') {
                $targetRights[] = 'SSS';
            }

            foreach ($targetRights as $tr) {
                $currentPrice = floatval($prices[$tr] ?? 0);
                if ($currentPrice <= 0.1 && $expectedPrice > 0) {
                    $discrepancies[] = [
                        'code' => $code,
                        'name' => $name,
                        'file' => $rule['file'],
                        'right' => $tr,
                        'rule_price' => $currentPrice,
                        'expected_price' => $expectedPrice,
                        'reason' => "Name says '{$right}' should have price {$expectedPrice}, but rule has {$currentPrice}"
                    ];
                }
            }
        }
    }
}

// Display results
if (empty($discrepancies)) {
    echo "No discrepancies found!" . PHP_EOL;
} else {
    echo "Found " . count($discrepancies) . " discrepancies:" . PHP_EOL;
    foreach ($discrepancies as $d) {
        echo "ADP: [{$d['code']}] | File: {$d['file']}" . PHP_EOL;
        echo "  HOSxP Name: {$d['name']}" . PHP_EOL;
        echo "  Right: {$d['right']} | Rule Price: {$d['rule_price']} | Expected: {$d['expected_price']}" . PHP_EOL;
        echo "  Reason: {$d['reason']}" . PHP_EOL . PHP_EOL;
    }
}
