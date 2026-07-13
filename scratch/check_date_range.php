<?php
$xmlFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.XML';
$xml = simplexml_load_file($xmlFile);

$hd_count_gt_zero = 0;
$hd_count_eq_zero = 0;
$epo_count = 0;
$epo_only_count = 0; // count of bills that have EPO but amount == 0
$both_count = 0; // count of bills that have both amount > 0 and EPO > 0

$detailed_list = [];

foreach ($xml->HDBills->HDBill as $hdbill) {
    $hn = (string)$hdbill->hn;
    $name = (string)$hdbill->name;
    $wkno = (string)$hdbill->wkno;
    
    foreach ($hdbill->TBill as $tb) {
        $invno = (string)$tb->invno;
        $dttran = (string)$tb->dttran;
        $amount = (float)$tb->amount;
        $cstat = (string)$tb->cstat;
        $pstat = (string)$tb->pstat;
        $epopay = isset($tb->EPOs) ? (float)($tb->EPOs->EPOpay ?? 0) : 0;
        $epoadm = isset($tb->EPOs) ? (float)($tb->EPOs->EPOadm ?? 0) : 0;
        
        $has_hd = ($amount > 0);
        $has_epo = ($epopay > 0 || $epoadm > 0);
        
        if ($has_hd) {
            $hd_count_gt_zero++;
        } else {
            $hd_count_eq_zero++;
        }
        
        if ($has_epo) {
            $epo_count++;
        }
        
        if ($has_hd && $has_epo) {
            $both_count++;
        } elseif ($has_epo && !$has_hd) {
            $epo_only_count++;
        }
        
        $detailed_list[] = [
            'hn' => $hn,
            'name' => $name,
            'wkno' => $wkno,
            'invno' => $invno,
            'dttran' => $dttran,
            'amount' => $amount,
            'epopay' => $epopay,
            'epoadm' => $epoadm,
            'cstat' => $cstat,
            'pstat' => $pstat,
            'has_hd' => $has_hd,
            'has_epo' => $has_epo
        ];
    }
}

echo "Total TBills: " . count($detailed_list) . "\n";
echo "Bills with amount > 0 (HD Billed): $hd_count_gt_zero\n";
echo "Bills with amount == 0 (HD Unbilled): $hd_count_eq_zero\n";
echo "Bills with EPO > 0: $epo_count\n";
echo "Bills with BOTH HD > 0 and EPO > 0: $both_count\n";
echo "Bills with ONLY EPO > 0 (amount == 0): $epo_only_count\n\n";

echo "--- Details of bills with amount == 0 ---\n";
foreach ($detailed_list as $b) {
    if (!$b['has_hd']) {
        echo "HN: {$b['hn']}, Name: {$b['name']}, Wk: {$b['wkno']}, Inv: {$b['invno']}, Date: {$b['dttran']}, Amt: {$b['amount']}, EpoPay: {$b['epopay']}, EpoAdm: {$b['epoadm']}, cstat: '{$b['cstat']}', pstat: '{$b['pstat']}'\n";
    }
}
