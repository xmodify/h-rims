<?php
$xmlFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.XML';
$xml = simplexml_load_file($xmlFile);

$patients = [];
$invnos = [];

foreach ($xml->HDBills->HDBill as $hdbill) {
    $hn = (string)$hdbill->hn;
    $name = (string)$hdbill->name;
    $wkno = (string)$hdbill->wkno;
    
    if (!isset($patients[$hn])) {
        $patients[$hn] = [];
    }
    if (!isset($patients[$hn][$wkno])) {
        $patients[$hn][$wkno] = [];
    }
    
    foreach ($hdbill->TBill as $tb) {
        $invno = (string)$tb->invno;
        $dttran = (string)$tb->dttran;
        $amount = (float)$tb->amount;
        
        $invnos[$invno][] = [
            'hn' => $hn,
            'name' => $name,
            'date' => $dttran
        ];
        
        $patients[$hn][$wkno][] = [
            'invno' => $invno,
            'date' => $dttran,
            'amount' => $amount
        ];
    }
}

echo "=== Duplicate invno check ===\n";
foreach ($invnos as $inv => $occs) {
    if (count($occs) > 1) {
        echo "Invoice $inv appears " . count($occs) . " times:\n";
        foreach ($occs as $occ) {
            echo "  HN: {$occ['hn']}, Name: {$occ['name']}, Date: {$occ['date']}\n";
        }
    }
}

echo "\n=== Patients with > 3 sessions per week ===\n";
foreach ($patients as $hn => $weeks) {
    // Let's get the patient's name
    $name = '';
    // find name
    foreach ($xml->HDBills->HDBill as $hdbill) {
        if ((string)$hdbill->hn === $hn) {
            $name = (string)$hdbill->name;
            break;
        }
    }
    
    foreach ($weeks as $wk => $sessions) {
        // Count sessions with amount > 0
        $paid_sessions = 0;
        foreach ($sessions as $s) {
            if ($s['amount'] > 0) {
                $paid_sessions++;
            }
        }
        
        if (count($sessions) > 3) {
            echo "HN: $hn ($name) has " . count($sessions) . " sessions (paid: $paid_sessions) in Week $wk:\n";
            foreach ($sessions as $s) {
                echo "  Inv: {$s['invno']}, Date: {$s['date']}, Amt: {$s['amount']}\n";
            }
        }
    }
}
