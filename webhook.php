<?php

define('WEBHOOK_VERIFY_TOKEN', getenv('WEBHOOK_VERIFY_TOKEN'));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === WEBHOOK_VERIFY_TOKEN) {

        http_response_code(200);

        echo $challenge;

        exit;
    }

    http_response_code(403);

    echo 'Token inválido';

    exit;
}

http_response_code(200);

echo 'Webhook ativo';