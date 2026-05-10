<?php
// ============================================================
//  GOOGLE_OAUTH.PHP — Configuração e funções OAuth 2.0
//  Implementado com cURL puro (sem Composer)
// ============================================================

define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI',  getenv('GOOGLE_REDIRECT_URI'));

define('GOOGLE_AUTH_URL',     'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL',    'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v3/userinfo');

// ============================================================
//  FUNÇÃO 1 — Gerar URL de autorização Google
//  O estado CSRF é assinado com HMAC (não depende de sessão)
// ============================================================
function googleUrlAutorizacao(): string {
    $aleatorio  = bin2hex(random_bytes(16));
    $assinatura = hash_hmac('sha256', $aleatorio, GOOGLE_CLIENT_SECRET);
    $estado     = $aleatorio . '.' . $assinatura;

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

// ============================================================
//  FUNÇÃO 2 — Validar estado CSRF por HMAC
//  Formato esperado: "aleatorio.assinatura"
// ============================================================
function googleValidarEstado(string $estado): bool {
    $partes = explode('.', $estado, 2);
    if (count($partes) !== 2) {
        return false;
    }

    [$aleatorio, $assinatura_recebida] = $partes;
    $assinatura_esperada = hash_hmac('sha256', $aleatorio, GOOGLE_CLIENT_SECRET);

    return hash_equals($assinatura_esperada, $assinatura_recebida);
}

// ============================================================
//  FUNÇÃO 3 — Trocar código de autorização por token de acesso
// ============================================================
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

// ============================================================
//  FUNÇÃO 4 — Obter perfil do utilizador via token de acesso
//  Campos devolvidos: sub (google_id), email, name, picture
// ============================================================
function googleObterPerfil(string $accessToken): ?array {
    $ch = curl_init(GOOGLE_USERINFO_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
    ]);
    $resposta = curl_exec($ch);
    curl_close($ch);

    $dados = json_decode($resposta, true);
    return isset($dados['sub']) ? $dados : null;
}