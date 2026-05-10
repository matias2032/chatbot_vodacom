<?php
// ============================================================
//  GOOGLE_OAUTH.PHP — Configuração e funções OAuth 2.0
//  Implementado com cURL puro (sem Composer)
// ============================================================

define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI',  getenv('GOOGLE_REDIRECT_URI')); // ex: https://seusite.com/google_callback.php

define('GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v3/userinfo');

/**
 * Gera o URL de redirecionamento para o Google.
 * Inclui um estado CSRF guardado na sessão.
 */
function googleUrlAutorizacao(): string {
    $estado = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_estado'] = $estado;

    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $estado,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ]);

    return GOOGLE_AUTH_URL . '?' . $params;
}

/**
 * Troca o código de autorização pelo token de acesso.
 */
function googleTrocarCodigo(string $codigo): ?array {
    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'code'          => $codigo,
            'client_id'     => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        ]),
    ]);
    $resposta = curl_exec($ch);
    curl_close($ch);

    $dados = json_decode($resposta, true);
    return isset($dados['access_token']) ? $dados : null;
}

/**
 * Obtém o perfil do utilizador a partir do token de acesso.
 */
function googleObterPerfil(string $accessToken): ?array {
    $ch = curl_init(GOOGLE_USERINFO_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
    ]);
    $resposta = curl_exec($ch);
    curl_close($ch);

    $dados = json_decode($resposta, true);
    // Campos esperados: sub (google_id), email, name, picture
    return isset($dados['sub']) ? $dados : null;
}