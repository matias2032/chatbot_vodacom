<?php
// verify.php — APENAS para verificação da Meta, zero dependências

$token     = getenv('WEBHOOK_VERIFY_TOKEN') ?: '';
$mode      = $_GET['hub_mode']         ?? '';
$received  = $_GET['hub_verify_token'] ?? '';
$challenge = $_GET['hub_challenge']    ?? '';

if ($mode === 'subscribe' && $received === $token) {
    http_response_code(200);
    header('Content-Type: text/plain');
    echo $challenge;
} else {
    http_response_code(403);
    echo 'forbidden';
}