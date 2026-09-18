<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;

$user = User::find(3);
$token = $user->eclaim_session_token;

$ch = curl_init('https://smt.nhso.go.th/smtf/api/adapter/report/person/dmis');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json, text/plain, */*'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'sfundCd' => '13',
    'efundCd' => '1',
    'batchNo' => '3270'
]));
$res = curl_exec($ch);
$info = curl_getinfo($ch);
echo 'HTTP CODE: ' . $info['http_code'] . "\n";
echo "RESPONSE HEADERS + BODY:\n" . $res . "\n";
