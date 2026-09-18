<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::find(3);
if (!$u || empty($u->eclaim_session_token)) {
    die("User 3 not found or empty token\n");
}

$tok = $u->eclaim_session_token;
if (preg_match('/ACCESS_TOKEN=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $tok, $m)) {
    $jwt = $m[1];
    $parts = explode('.', $jwt);
    $p = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    echo "AZP: " . ($p['azp'] ?? '') . "\n";
    echo "Exp: " . date('Y-m-d H:i:s', $p['exp']) . "\n";
    echo "Name: " . ($p['nameTh'] ?? '') . "\n";
    echo "Roles smtf: " . json_encode($p['resource_access']['smtf']['roles'] ?? []) . "\n";
    
    file_put_contents(__DIR__ . '/smt_token.json', json_encode([
        'token' => $jwt,
        'raw_cookie' => $tok,
        'updated_at' => date('c')
    ], JSON_PRETTY_PRINT));
    echo "SUCCESS: Saved ACCESS_TOKEN to scratch/smt_token.json\n";
} else {
    echo "ERROR: ACCESS_TOKEN not found in token string!\n";
}
