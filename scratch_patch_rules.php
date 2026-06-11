<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

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

// 1. Fetch ADP Code mapping from HOSxP
$adpCodes = DB::connection('hosxp')->select("SELECT nhso_adp_code, nhso_adp_code_name FROM nhso_adp_code");
$adpMap = [];
foreach ($adpCodes as $adp) {
    $adpMap[trim($adp->nhso_adp_code)] = $adp->nhso_adp_code_name;
}

echo "Starting Auto-Patch process for Rules files..." . PHP_EOL;

$patchedCount = 0;

foreach ($rulesFiles as $file) {
    $path = config_path('claims/' . $file);
    if (!file_exists($path)) {
        echo "File not found: {$file}, skipping." . PHP_EOL;
        continue;
    }

    $rules = require $path;
    $fileUpdated = false;

    foreach ($rules as $code => &$rule) {
        if (!isset($adpMap[$code])) continue;
        $name = $adpMap[$code];

        // Parse expected price and rights from the HOSxP name
        // Pattern: e.g. "(OFC&UCS&NHS 1000)" or "(OFC&UCS&NHS 5000)"
        if (preg_match('/\(([^)]+)\s+(\d+(?:\.\d+)?)\)/u', $name, $matches)) {
            $rightsStr = strtoupper($matches[1]);
            $expectedPrice = floatval($matches[2]);

            // Split rights
            $rights = preg_split('/[&+,;\s]+/u', $rightsStr);
            foreach ($rights as $right) {
                $right = trim($right);
                if (empty($right)) continue;

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
                    if (!isset($rule['prices'][$tr]) || floatval($rule['prices'][$tr]) <= 0.1) {
                        $rule['prices'][$tr] = $expectedPrice;
                        $fileUpdated = true;
                        $patchedCount++;
                        echo "  [{Patch}] ADP: {$code} in {$file} -> set {$tr} to {$expectedPrice}" . PHP_EOL;
                    }
                }
            }
        }
    }
    unset($rule); // break reference

    if ($fileUpdated) {
        // We write the array back to the file formatted cleanly
        $exportContent = "<?php" . PHP_EOL . PHP_EOL . "return [" . PHP_EOL;
        foreach ($rules as $code => $rule) {
            $exportContent .= "    '{$code}' => [" . PHP_EOL;
            $exportContent .= "        'name'     => " . var_export($rule['name'], true) . "," . PHP_EOL;
            if (isset($rule['category'])) {
                $exportContent .= "        'category' => " . var_export($rule['category'], true) . "," . PHP_EOL;
            }
            $exportContent .= "        'prices'   => [" . PHP_EOL;
            foreach ($rule['prices'] as $right => $price) {
                $priceFormatted = number_format($price, 2, '.', '');
                $exportContent .= "            '{$right}'  => {$priceFormatted}," . PHP_EOL;
            }
            $exportContent .= "        ]," . PHP_EOL;
            $exportContent .= "    ]," . PHP_EOL;
        }
        $exportContent .= "];" . PHP_EOL;

        file_put_contents($path, $exportContent);
        echo "  [Saved] {$file} has been updated." . PHP_EOL;
    }
}

echo PHP_EOL . "Auto-Patch process complete! Patched {$patchedCount} rule prices." . PHP_EOL;
