<?php
$tokenData = json_decode(file_get_contents(__DIR__ . '/smt_token.json'), true);
$token = $tokenData['token'];

$url = 'https://smt.nhso.go.th/adapter/report/person/dmis';
$payload = json_encode([
    'refDocNo' => 'DCKD6931080031',
    'vendorId' => '10989',
    'postingDate' => '25690915',
    'batchNo' => '3270',
    'sfundCd' => '13',
    'efundCd' => '1',
]);

echo "Testing curl with HTTP/2 and full browser headers...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        'Origin: https://smt.nhso.go.th',
        'Referer: https://smt.nhso.go.th/smtf/',
        'Sec-Ch-Ua: "Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
        'Sec-Ch-Ua-Mobile: ?0',
        'Sec-Ch-Ua-Platform: "Windows"',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: same-origin',
    ],
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($err) echo "Curl Error: $err\n";
echo "Response (first 300 chars): " . substr($response, 0, 300) . "\n";
