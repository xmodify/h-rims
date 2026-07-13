<?php
$xmlFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.XML';
$xml = simplexml_load_file($xmlFile);

$other_hcode_tbills = [];
$self_n_tbills = [];

foreach ($xml->HDBills->HDBill as $hdbill) {
    $parent_hn = (string)$hdbill->hn;
    $parent_name = (string)$hdbill->name;
    $parent_wkno = (string)$hdbill->wkno;
    
    foreach ($hdbill->TBill as $tb) {
        $hcode = (string)$tb->hcode;
        $hn = (string)$tb->hn;
        $invno = (string)$tb->invno;
        $dttran = (string)$tb->dttran;
        $amount = (float)$tb->amount;
        $self = (string)$tb['self'];
        
        $rec = [
            'parent_hn' => $parent_hn,
            'parent_name' => $parent_name,
            'parent_wkno' => $parent_wkno,
            'hcode' => $hcode,
            'hn' => $hn,
            'invno' => $invno,
            'dttran' => $dttran,
            'amount' => $amount,
            'self' => $self
        ];
        
        if ($hcode !== '10703') {
            $other_hcode_tbills[] = $rec;
        }
        
        if ($self === 'N') {
            $self_n_tbills[] = $rec;
        }
    }
}

echo "Total TBills with hcode !== 10703: " . count($other_hcode_tbills) . "\n";
foreach ($other_hcode_tbills as $tb) {
    echo "Parent HN: {$tb['parent_hn']} ({$tb['parent_name']}), Parent Wk: {$tb['parent_wkno']}\n";
    echo "  TBill self: '{$tb['self']}', hcode: '{$tb['hcode']}', TBill HN: '{$tb['hn']}', InvNo: '{$tb['invno']}', Date: '{$tb['dttran']}', Amt: {$tb['amount']}\n";
}

echo "\nTotal TBills with self='N': " . count($self_n_tbills) . "\n";
foreach ($self_n_tbills as $tb) {
    echo "Parent HN: {$tb['parent_hn']} ({$tb['parent_name']}), Parent Wk: {$tb['parent_wkno']}\n";
    echo "  TBill self: '{$tb['self']}', hcode: '{$tb['hcode']}', TBill HN: '{$tb['hn']}', InvNo: '{$tb['invno']}', Date: '{$tb['dttran']}', Amt: {$tb['amount']}\n";
}
