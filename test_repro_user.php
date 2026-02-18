<?php

// Mocking the behavior of ImportController@stm_ofc_csop_save for reproduction
$xmlString = <<<'XML'
<?xml version = "1.0" encoding="UTF-8" standalone="yes"?>
<?xml-stylesheet type="text/xsl" href="coctstm2025.xsl"?>
<STMSTM>
<stmAccountID>COCD</stmAccountID>
<hcode id = "EA0010703">10703</hcode>
<hname>โรงพยาบาลอำนาจเจริญ</hname>
<AccPeriod>260101</AccPeriod>
<STMdoc>10703_COCDSTM_20260101</STMdoc>
<dateStart>1  มกราคม 2569</dateStart>
<dateEnd>15  มกราคม 2569</dateEnd>
<datedue>22  มกราคม  2569</datedue>
<dateIssue>19  มกราคม  2569</dateIssue>
<amount>5065640.1400</amount>
<thamount>ห้าล้านหกหมื่นห้าพันหกร้อยสี่สิบบาทสิบสี่สตางค์</thamount>
<gst>5</gst>
<rel/>
<STMdat  code ="CD"   name = 'รายการผู้ป่วยนอกรักษาต่อเนื่อง' desc1 ='ประเภทผู้ป่วยนอกทั่วไป' desc = 'บริการผู้ป่วยนอก' >
<Gtotal>4068764.1400</Gtotal>
</STMdat>
<TBills code ='CD'>
<TBill>
<sys>CD</sys>
<station>01</station>
<hreg/>
<hn>000046542</hn>
<namepat>นางบัวลอย  สารทอง</namepat>
<invno>681205003017-003</invno>
<dttran>2025-12-05T00:30:17</dttran>
<amount>631.0000</amount>
<paid>0.0000</paid>
<ExtP code = "N">0.0000</ExtP>
<rid>8863001</rid>
<cstat/>
<HDflag/>
</TBill>
</TBills>
</STMSTM>
XML;

$xmlString = preg_replace('/^\xEF\xBB\xBF/', '', $xmlString);
libxml_use_internal_errors(true);
$xml = simplexml_load_string($xmlString);

if ($xml === false) {
    echo "ERROR: XML Parse Failed\n";
    foreach (libxml_get_errors() as $error) {
        echo "- " . $error->message . " at line " . $error->line . "\n";
    }
    exit;
}

echo "Root Name: " . $xml->getName() . "\n";

// Metastat extraction reproduction
$STMdoc = null;
if (isset($xml->STMdoc))
    $STMdoc = (string) $xml->STMdoc;

echo "STMdoc: $STMdoc\n";
echo "hcode: " . (string) ($xml->hcode ?? $xml->stmdat->hcode ?? $xml->Hcode ?? 'MISSING') . "\n";
echo "AccPeriod: " . (string) ($xml->AccPeriod ?? $xml->acc_period ?? 'MISSING') . "\n";

$bills = [];
if (isset($xml->TBills)) {
    echo "TBills found via isset\n";
    foreach ($xml->TBills as $tbGroup) {
        $groupCode = (string) ($tbGroup['code'] ?? '');
        echo "Processing group: $groupCode\n";
        if (isset($tbGroup->TBill)) {
            foreach ($tbGroup->TBill as $b) {
                if (!isset($b->sys) && !empty($groupCode))
                    $b->sys = $groupCode;
                if (!isset($b->station) && !empty($groupCode))
                    $b->station = '01';
                $bills[] = $b;
            }
        }
    }
}

if (empty($bills)) {
    echo "Using XPath Fallback...\n";
    $potentialBills = $xml->xpath('//TBill');
    foreach ($potentialBills as $b) {
        $bills[] = $b;
    }
}

echo "Total TBills found: " . count($bills) . "\n";
if (!empty($bills)) {
    $bill = $bills[0];
    echo "First Bill HN: " . (string) $bill->hn . "\n";
    echo "First Bill namepat: " . (string) ($bill->namepat ?? $bill->pt_name ?? 'MISSING') . "\n";
    echo "First Bill sys: " . (string) ($bill->sys ?? 'MISSING') . "\n";
    echo "First Bill station: " . (string) ($bill->station ?? 'MISSING') . "\n";
}
