<?php
$xmlFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.XML';
$xml = simplexml_load_file($xmlFile);

$patients = [];
$total_amount_sum = 0;
$total_epopay_sum = 0;
$total_epoadm_sum = 0;
$total_bill_count = 0;
$hold_records = [];

foreach ($xml->HDBills->HDBill as $hdbill) {
    $hn = (string)$hdbill->hn;
    $name = (string)$hdbill->name;
    $wkno = (string)$hdbill->wkno;
    
    if (!isset($patients[$hn])) {
        $patients[$hn] = [
            'name' => $name,
            'bills' => []
        ];
    }
    
    foreach ($hdbill->TBill as $tb) {
        $total_bill_count++;
        $invno = (string)$tb->invno;
        $dttran = (string)$tb->dttran;
        $amount = (float)$tb->amount;
        $cstat = (string)$tb->cstat;
        $pstat = (string)$tb->pstat;
        $paychk = (string)$tb->paychk;
        $epopay = isset($tb->EPOs) ? (float)($tb->EPOs->EPOpay ?? 0) : 0;
        $epoadm = isset($tb->EPOs) ? (float)($tb->EPOs->EPOadm ?? 0) : 0;
        
        $total_amount_sum += $amount;
        $total_epopay_sum += $epopay;
        $total_epoadm_sum += $epoadm;
        
        $patients[$hn]['bills'][] = [
            'wkno' => $wkno,
            'invno' => $invno,
            'dttran' => $dttran,
            'amount' => $amount,
            'cstat' => $cstat,
            'pstat' => $pstat,
            'paychk' => $paychk,
            'epopay' => $epopay,
            'epoadm' => $epoadm,
        ];
        
        if ($cstat === 'H' || $pstat === '1' || $amount == 0) {
            $hold_records[] = [
                'hn' => $hn,
                'name' => $name,
                'wkno' => $wkno,
                'invno' => $invno,
                'dttran' => $dttran,
                'amount' => $amount,
                'epopay' => $epopay,
                'epoadm' => $epoadm,
                'cstat' => $cstat,
                'pstat' => $pstat
            ];
        }
    }
}

$outputFile = 'd:/Project Laravel/h-rims/scratch/patient_bills_full.txt';
$fh = fopen($outputFile, 'w');
fwrite($fh, "=== Full Patient Bills Listing ===\n\n");
foreach ($patients as $hn => $p) {
    fwrite($fh, "HN: $hn - {$p['name']} (Total Bills: " . count($p['bills']) . ")\n");
    foreach ($p['bills'] as $b) {
        fwrite($fh, sprintf("  Wk: %s, Inv: %s, Date: %s, Amt: %7.2f, EpoPay: %7.2f, EpoAdm: %7.2f, paychk: %s, cstat: '%s', pstat: '%s'\n",
            $b['wkno'], $b['invno'], $b['dttran'], $b['amount'], $b['epopay'], $b['epoadm'], $b['paychk'], $b['cstat'], $b['pstat']));
    }
}
fclose($fh);

echo "Total Unique Patients (HNs): " . count($patients) . "\n";
echo "Total TBill Tags in XML: $total_bill_count\n";
echo "Sum of 'amount' (HD): $total_amount_sum\n";
echo "Sum of 'EPOpay': $total_epopay_sum\n";
echo "Sum of 'EPOadm': $total_epoadm_sum\n";
echo "Grand Total (amount + epopay + epoadm) in XML: " . ($total_amount_sum + $total_epopay_sum + $total_epoadm_sum) . "\n";
echo "\nHold / Unpaid Records (cstat == H or pstat == 1 or amount == 0):\n";
foreach ($hold_records as $hr) {
    echo "HN: {$hr['hn']}, Name: {$hr['name']}, Wk: {$hr['wkno']}, Inv: {$hr['invno']}, Date: {$hr['dttran']}, Amt: {$hr['amount']}, EpoPay: {$hr['epopay']}, EpoAdm: {$hr['epoadm']}, cstat: '{$hr['cstat']}', pstat: '{$hr['pstat']}'\n";
}
echo "\nFull list written to $outputFile\n";
