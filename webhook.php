<?php

define('WEBHOOK_VERIFY_TOKEN', 'chatbot_vodacom_xxx');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $query = $_SERVER['QUERY_STRING'] ?? '';

    parse_str($query, $params);

    $mode = $params['hub_mode'] ?? $params['hub.mode'] ?? '';
    $token = $params['hub_verify_token'] ?? $params['hub.verify_token'] ?? '';
    $challenge = $params['hub_challenge'] ?? $params['hub.challenge'] ?? '';

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