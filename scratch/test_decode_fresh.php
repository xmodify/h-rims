<?php
require __DIR__ . '/../vendor/autoload.php';

$json = json_decode(file_get_contents(__DIR__ . '/smt_fresh_token.json'), true);
if (!$json) {
    // let's grab from browser or run a quick dump
    echo "smt_fresh_token.json not found\n";
}
