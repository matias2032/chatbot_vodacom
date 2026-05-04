<?php
// ============================================================
//  API_CHAT.PHP — Endpoint do chat web
//  Lógica RAG + Gemini delegada para funcoes_chat.php
// ============================================================

require_once 'auth.php';
require_once 'configuracao.php';
require_once 'conexao.php';
require_once 'funcoes_chat.php';   // ← funções partilhadas

// Exige sessão activa
iniciarSessao();
$utilizador    = utilizadorActual();
$id_utilizador = $utilizador['id_utilizador'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respostaJson(false, null, 'Método não permitido.');

$corpo     = json_decode(file_get_contents('php://input'), true);
$mensagem  = trim($corpo['mensagem']  ?? '');
$id_sessao = trim($corpo['id_sessao'] ?? '');

if ($mensagem === '') respostaJson(false, null, 'Mensagem vazia.');
if ($id_sessao === '') respostaJson(false, null, 'Sessão inválida.');

$pdo = obterConexao();

// ------------------------------------------------------------
// 1. Obtém ou cria conversa
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT id_conversa FROM conversas
    WHERE id_sessao = :s AND id_configuracao_bot = :bot
      AND (id_utilizador = :uid OR (:uid2::text IS NULL AND id_utilizador IS NULL))
    LIMIT 1
");
$stmt->execute([':s' => $id_sessao, ':bot' => BOT_ID, ':uid' => $id_utilizador, ':uid2' => $id_utilizador]);
$conversa = $stmt->fetch();

if (!$conversa) {
    $stmt = $pdo->prepare("
        INSERT INTO conversas (id_configuracao_bot, id_sessao, id_utilizador)
        VALUES (:bot, :s, :uid) RETURNING id_conversa
    ");
    $stmt->execute([':bot' => BOT_ID, ':s' => $id_sessao, ':uid' => $id_utilizador]);
    $id_conversa = $stmt->fetchColumn();
} else {
    $id_conversa = $conversa['id_conversa'];
}

// ------------------------------------------------------------
// 2. Guarda mensagem do utilizador
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:c, 'utilizador', :m) RETURNING id_mensagem
");
$stmt->execute([':c' => $id_conversa, ':m' => $mensagem]);
$id_msg_user = $stmt->fetchColumn();

// ------------------------------------------------------------
// 3. RAG — busca contexto relevante
// ------------------------------------------------------------
$contexto = buscarContexto($pdo, $mensagem);

// ------------------------------------------------------------
// 4. Chama Gemini
// ------------------------------------------------------------
$resultado = chamarGemini($mensagem, $contexto, $id_conversa, $pdo, $id_msg_user);

if ($resultado['erro'] !== null) {
    error_log("[api_chat] Erro Gemini: " . $resultado['erro']);
    respostaJson(false, null, $resultado['erro']);
}

$texto_resp = $resultado['texto'];
$t_entrada  = $resultado['t_entrada'];
$t_saida    = $resultado['t_saida'];
$tempo_ms   = $resultado['tempo_ms'];

// ------------------------------------------------------------
// 5. Guarda resposta do assistente
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    INSERT INTO mensagens
        (id_conversa, papel, conteudo, tokens_entrada, tokens_saida, tempo_resposta_ms)
    VALUES (:c, 'assistente', :m, :te, :ts, :t)
    RETURNING id_mensagem
");
$stmt->execute([
    ':c'  => $id_conversa,
    ':m'  => $texto_resp,
    ':te' => $t_entrada,
    ':ts' => $t_saida,
    ':t'  => $tempo_ms,
]);
$id_msg_bot = $stmt->fetchColumn();

// ------------------------------------------------------------
// 6. Regista fontes RAG usadas
// ------------------------------------------------------------
registarFontes($pdo, $id_msg_bot, $contexto['fontes']);

// ------------------------------------------------------------
// 7. Resposta ao frontend
// ------------------------------------------------------------
respostaJson(true, [
    'resposta'    => $texto_resp,
    'id_conversa' => $id_conversa,
    'tokens'      => ['entrada' => $t_entrada, 'saida' => $t_saida],
    'tempo_ms'    => $tempo_ms,
    'fontes'      => count($contexto['fontes']),
]);