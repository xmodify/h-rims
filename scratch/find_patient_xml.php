<?php
$xmlFile = 'd:/Project Laravel/h-rims/scratch/10703_SOCDSTM_20260501/10703_SOCDSTM_20260501.XML';
$content = file_get_contents($xmlFile);
$xml = simplexml_load_string($content);

foreach ($xml->HDBills->HDBill as $hdbill) {
    if ((string)$hdbill->hn === '000332286') {
        echo "Found HDBill for 000332286:\n";
        echo $hdbill->asXML();
        echo "\n";
    }
}
