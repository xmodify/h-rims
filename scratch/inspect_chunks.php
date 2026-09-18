<?php
foreach (['6295', '9933'] as $chunkId) {
    echo "=== CHUNK $chunkId ===\n";
    $url = "https://smt.nhso.go.th/smtf/$chunkId.js";
    $content = @file_get_contents($url);
    if (!$content) {
        // Find chunk hash from main.js
        $mainJs = file_get_contents('https://smt.nhso.go.th/smtf/main.d1ffb3e6ff2aabf5.js');
        if (preg_match('/' . $chunkId . ':\"([a-f0-9]+)\"/', $mainJs, $m)) {
            $url = "https://smt.nhso.go.th/smtf/" . $chunkId . "." . $m[1] . ".js";
            $content = file_get_contents($url);
        }
    }
    if ($content) {
        echo "Found chunk: " . strlen($content) . " bytes\n";
        // search for exportExcel or report/person
        foreach (['exportExcel', 'export', 'person/lgo', 'person/dmis', 'mophId'] as $kw) {
            $pos = strpos($content, $kw);
            if ($pos !== false) {
                echo "Match for $kw at $pos:\n";
                echo substr($content, max(0, $pos - 100), 400) . "\n---\n";
            }
        }
    } else {
        echo "Chunk $chunkId not found\n";
    }
}
