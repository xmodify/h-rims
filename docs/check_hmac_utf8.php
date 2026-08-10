<?php
$z = new ZipArchive();
if ($z->open('d:/Project Laravel/h-rims/docs/10989AIPN47793.zip') === TRUE) {
    for ($i = 0; $i < $z->numFiles; $i++) {
        $st = $z->statIndex($i);
        $name = $st['name'];
        if (strtolower(substr($name, -4)) === '.xml') {
            $raw = $z->getFromIndex($i);
            preg_match('/HMAC="([A-Fa-f0-9]+)"/', $raw, $m);
            $expected = isset($m[1]) ? $m[1] : '';
            
            $cipn_start = strpos($raw, '<CIPN');
            $cipn_end = strpos($raw, '</CIPN>') + 7;
            $cipn_content = substr($raw, $cipn_start, $cipn_end - $cipn_start);
            
            // Check trailing bytes of $cipn_content
            echo "File: $name" . PHP_EOL;
            echo "Expected: $expected" . PHP_EOL;
            
            // Let's try hashing:
            // 1. iconv to UTF-8 then md5?
            $utf8 = iconv('windows-874', 'UTF-8//IGNORE', $cipn_content);
            echo "  UTF8 + 2CRLF (UTF-8 bytes): " . strtoupper(md5($utf8 . "\r\n\r\n")) . PHP_EOL;
            echo "  UTF8 + 2CRLF (win-874 bytes):" . strtoupper(md5(iconv('UTF-8', 'windows-874//IGNORE', $utf8 . "\r\n\r\n"))) . PHP_EOL;
            
            // Wait, let's look at the actual HMAC for 690002347: "78190e335543cf12916bcd9f860176fe"
            // Let's compute md5 of windows-874 content of $cipn_content + "\r\n\r\n"
            // Wait, our previous test showed:
            // Computed 2CRLF: 8E9B0BF5443FFC905B11C36A5F289680
            // Expected: 78190e335543cf12916bcd9f860176fe
            // Why are they different? Let's check if the file was modified or let's read the PDF for the exact HMAC algorithm!
            break;
        }
    }
    $z->close();
}
