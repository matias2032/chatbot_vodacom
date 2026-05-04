<?php

define('WEBHOOK_VERIFY_TOKEN', 'chatbot_vodacom_xxx');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $mode = $_GET['hub.mode'] ?? '';
    $token = $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub.challenge'] ?? '';

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
echo "Webhook ativo";