<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::find(3);
$tok = $u->eclaim_session_token;

$jwt = '';
if (preg_match('/(?:ACCESS_TOKEN|KEYCLOAK_IDENTITY)=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $tok, $m)) {
    $jwt = $m[1];
} elseif (strpos($tok, '.') !== false) {
    $jwt = trim($tok);
}

$d = json_decode(file_get_contents(__DIR__ . '/smt_token.json'), true);
$jwt = $d['token'];
$parts = explode('.', $jwt);
if (count($parts) >= 2) {
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    echo "Client ID (azp): " . ($payload['azp'] ?? 'N/A') . "\n";
    echo "User: " . ($payload['name'] ?? ($payload['nameTh'] ?? 'N/A')) . "\n";
    echo "Expires: " . date('Y-m-d H:i:s', $payload['exp'] ?? 0) . "\n";
}
