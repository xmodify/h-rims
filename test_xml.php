<?php
$xmlStr = "<STMSTM>
  <TBills code='CD'>
    <TBill>Bill 1</TBill>
    <TBill>Bill 2</TBill>
  </TBills>
</STMSTM>";
$xml = simplexml_load_string($xmlStr);

echo "Method 1 (foreach xml->TBills):\n";
foreach ($xml->TBills as $tg) {
    echo "Group name: " . $tg->getName() . " code: " . $tg['code'] . "\n";
    foreach ($tg->TBill as $b) {
        echo "  Bill: " . (string) $b . "\n";
    }
}

$xmlStr2 = "<STMSTM>
  <TBills code='CD'><TBill>B1</TBill></TBills>
  <TBills code='HD'><TBill>B2</TBill></TBills>
</STMSTM>";
$xml2 = simplexml_load_string($xmlStr2);
echo "\nMethod 1 with multiple TBills:\n";
foreach ($xml2->TBills as $tg) {
    echo "Group name: " . $tg->getName() . " code: " . $tg['code'] . "\n";
    foreach ($tg->TBill as $b) {
        echo "  Bill: " . (string) $b . "\n";
    }
}
