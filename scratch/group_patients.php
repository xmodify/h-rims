<?php
$xmlFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.XML';
$xml = simplexml_load_file($xmlFile);

$patients = [];
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
        $patients[$hn]['bills'][] = [
            'wkno' => $wkno,
            'invno' => (string)$tb->invno,
            'dttran' => (string)$tb->dttran,
            'amount' => (float)$tb->amount,
            'cstat' => (string)$tb->cstat,
            'pstat' => (string)$tb->pstat,
            'paychk' => (string)$tb->paychk,
            'epopay' => isset($tb->EPOs) ? (float)($tb->EPOs->EPOpay ?? 0) : 0,
            'epoadm' => isset($tb->EPOs) ? (float)($tb->EPOs->EPOadm ?? 0) : 0,
        ];
    }
}

foreach ($patients as $hn => $p) {
    echo "HN: $hn - {$p['name']} (Total Bills: " . count($p['bills']) . ")\n";
    foreach ($p['bills'] as $b) {
        echo "  Wk: {$b['wkno']}, Inv: {$b['invno']}, Date: {$b['dttran']}, Amt: {$b['amount']}, EpoPay: {$b['epopay']}, EpoAdm: {$b['epoadm']}, paychk: {$b['paychk']}, cstat: '{$b['cstat']}', pstat: '{$b['pstat']}'\n";
    }
}
