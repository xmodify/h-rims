<?php
$xmlFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.XML';
$content = file_get_contents($xmlFile);
$xml = simplexml_load_string($content);

foreach ($xml->HDBills->HDBill as $hdbill) {
    foreach ($hdbill->TBill as $tb) {
        if ((string)$tb->invno === '860659131') {
            echo "Found TBill for invno 860659131:\n";
            echo $tb->asXML();
            echo "\nParent HDBill:\n";
            // We can't easily get parent using SimpleXML, let's print some parent fields
            echo "HN: " . $hdbill->hn . ", Name: " . $hdbill->name . ", Wkno: " . $hdbill->wkno . "\n";
        }
    }
}
