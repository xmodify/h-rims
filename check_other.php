<?php
$ins = include 'config/claims/ins_rules.php';

// รหัสที่ UCS = 0 (จะอยู่ใน Other tab ถ้ามีใน lookup_icode)
echo "=== รหัสใน ins_rules ที่ UCS = 0 (อยู่ใน Other tab) ===" . PHP_EOL;
$ucsZero = array_filter($ins, fn($r) => ($r['prices']['UCS'] ?? 0) == 0);
foreach ($ucsZero as $code => $r) {
    echo "  $code | " . substr($r['name'], 0, 60) . " | OFC=" . $r['prices']['OFC'] . " LGO=" . $r['prices']['LGO'] . PHP_EOL;
}
echo PHP_EOL . "Total UCS=0: " . count($ucsZero) . PHP_EOL;

// รหัส special ที่อยู่ใน insert query แต่ไม่ใน ins_rules
echo PHP_EOL . "=== รหัส special UC ที่ไม่อยู่ใน ins_rules ===" . PHP_EOL;
$specialCodes = ['TELMED','DRUGP','Cons01','Eva001','30001','80001','80002','80003','80004','80005','80006','80007','80008','80015','80024','80025','80026','80027','80028','STEMI1'];
foreach ($specialCodes as $c) {
    $found = isset($ins[$c]) ? 'มีใน ins_rules' : 'ไม่มีใน ins_rules → Other tab = ไม่มีหมวดหมู่';
    echo "  $c => $found" . PHP_EOL;
}
