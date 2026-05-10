<?php
// ============================================================
//  GOOGLE_CALLBACK.PHP — Endpoint de retorno OAuth 2.0
// ============================================================

require_once 'auth.php';
iniciarSessao();

require_once 'conexao.php';
require_once 'google_oauth.php';

// ── Verificação CSRF ─────────────────────────────────────────
$estado = $_GET['state'] ?? '';
if (!googleValidarEstado($estado)) {
    die('Estado inválido. Possível ataque CSRF.');
}

// ── Verificar erro devolvido pelo Google ─────────────────────
if (isset($_GET['error'])) {
    header('Location: login.php?erro=google_cancelado');
    exit;
}

// ── Trocar código por token ──────────────────────────────────
$codigo = $_GET['code'] ?? '';
$token  = googleTrocarCodigo($codigo);

if (!$token) {
    header('Location: login.php?erro=google_token');
    exit;
}

// ── Obter perfil do utilizador ───────────────────────────────
$perfil = googleObterPerfil($token['access_token']);

if (!$perfil) {
    header('Location: login.php?erro=google_perfil');
    exit;
}

$google_id = $perfil['sub'];
$email     = $perfil['email'];
$nome      = $perfil['name'];

$pdo = obterConexao();

// ── Procurar conta existente ─────────────────────────────────
// 1.º por google_id (já ligada); 2.º por email (conta tradicional)
$stmt = $pdo->prepare("
    SELECT id_utilizador, nome, email, perfil, ativo, google_id
    FROM utilizadores
    WHERE google_id = :gid OR email = :email
    LIMIT 1
");
$stmt->execute([':gid' => $google_id, ':email' => $email]);
$utilizador = $stmt->fetch();

if ($utilizador) {
    // Conta encontrada — verificar se está ativa
    if (!$utilizador['ativo']) {
        header('Location: login.php?erro=conta_inativa');
        exit;
    }

    // Se conta existe pelo email mas ainda não tem google_id, liga agora
    if (empty($utilizador['google_id'])) {
        $upd = $pdo->prepare("UPDATE utilizadores SET google_id = :gid WHERE id_utilizador = :id");
        $upd->execute([':gid' => $google_id, ':id' => $utilizador['id_utilizador']]);
    }
} else {
    // Conta nova — criar automaticamente (sem senha)
    $stmt = $pdo->prepare("
        INSERT INTO utilizadores (nome, email, senha_hash, perfil, google_id)
        VALUES (:nome, :email, NULL, 'utilizador', :gid)
    ");
    $stmt->execute([':nome' => $nome, ':email' => $email, ':gid' => $google_id]);

    $stmt = $pdo->prepare("
        SELECT id_utilizador, nome, email, perfil, ativo
        FROM utilizadores WHERE email = :email LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $utilizador = $stmt->fetch();
}

// ── Iniciar sessão (igual ao login tradicional) ──────────────
session_regenerate_id(true);

$_SESSION['id_utilizador'] = $utilizador['id_utilizador'];
$_SESSION['nome']          = $utilizador['nome'];
$_SESSION['email']         = $utilizador['email'];
$_SESSION['perfil']        = $utilizador['perfil'];

header('Location: menu.php');
exit;