<?php
// webhook_telegram.php — Canal Telegram
// Lógica RAG + Gemini delegada para funcoes_chat.php

require_once 'configuracao.php';
require_once 'conexao.php';
require_once 'funcoes_chat.php';

// ─────────────────────────────────────────────────────────────
// 1. Lê o payload JSON enviado pelo Telegram
// ─────────────────────────────────────────────────────────────
$input   = json_decode(file_get_contents('php://input'), true);
$texto   = trim($input['message']['text']        ?? '');
$chat_id = (string)($input['message']['chat']['id'] ?? '');

error_log("=== TELEGRAM WEBHOOK INICIO ===");
error_log("chat_id: $chat_id | texto: $texto");

// Ignorar se não vier texto ou chat_id (ex: stickers, fotos, etc.)
if (empty($texto) || empty($chat_id)) {
    error_log("TELEGRAM — ignorado (sem texto ou chat_id)");
    http_response_code(200);
    exit;
}

// ─────────────────────────────────────────────────────────────
// 2. Ligação à BD
// ─────────────────────────────────────────────────────────────
$pdo = obterConexao();
error_log("TELEGRAM — BD ligada");

// ─────────────────────────────────────────────────────────────
// 3. Buscar ou criar conversa activa (janela de 24h)
// ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT id_conversa FROM conversas
    WHERE identificador_usuario = :id
      AND canal = 'telegram'
      AND ultima_mensagem_em > NOW() - INTERVAL '24 hours'
    ORDER BY iniciada_em DESC LIMIT 1
");
$stmt->execute([':id' => $chat_id]);
$conversa = $stmt->fetch();

if (!$conversa) {
    error_log("TELEGRAM — sem conversa activa, a criar nova");
    $stmt = $pdo->prepare("
        INSERT INTO conversas
            (id_configuracao_bot, identificador_usuario, canal, metadados)
        VALUES (:bot, :id, 'telegram', :meta)
        RETURNING id_conversa
    ");
    $stmt->execute([
        ':bot'  => BOT_ID,
        ':id'   => $chat_id,
        ':meta' => json_encode([
            'plataforma' => 'telegram',
            'first_name' => $input['message']['from']['first_name'] ?? '',
            'username'   => $input['message']['from']['username']   ?? '',
        ]),
    ]);
    $conversa = $stmt->fetch();
} else {
    error_log("TELEGRAM — conversa activa encontrada");
}

$id_conversa = $conversa['id_conversa'];
error_log("TELEGRAM — id_conversa: $id_conversa");

// ─────────────────────────────────────────────────────────────
// 4. Guardar mensagem do utilizador
// ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'utilizador', :txt)
    RETURNING id_mensagem
");
$stmt->execute([':id' => $id_conversa, ':txt' => $texto]);
$id_msg_user = $stmt->fetchColumn();
error_log("TELEGRAM — mensagem utilizador guardada: $id_msg_user");

// ─────────────────────────────────────────────────────────────
// 5. RAG — busca contexto relevante
// ─────────────────────────────────────────────────────────────
$contexto = buscarContexto($pdo, $texto);
error_log("TELEGRAM — contexto RAG: " . count($contexto['partes']) . " partes, " . count($contexto['fontes']) . " fontes");

// ─────────────────────────────────────────────────────────────
// 6. Gemini — gera resposta
// ─────────────────────────────────────────────────────────────
$resultado = chamarGemini($texto, $contexto, $id_conversa, $pdo);
error_log("TELEGRAM — Gemini tempo_ms: " . $resultado['tempo_ms']);

if ($resultado['erro'] !== null) {
    error_log("TELEGRAM — Gemini erro: " . $resultado['erro']);
}

$resposta = $resultado['texto'];
error_log("TELEGRAM — resposta: " . substr($resposta, 0, 200));

// ─────────────────────────────────────────────────────────────
// 7. Guardar resposta do assistente
// ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT INTO mensagens
        (id_conversa, papel, conteudo, tokens_entrada, tokens_saida, tempo_resposta_ms)
    VALUES (:id, 'assistente', :txt, :te, :ts, :t)
    RETURNING id_mensagem
");
$stmt->execute([
    ':id'  => $id_conversa,
    ':txt' => $resposta,
    ':te'  => $resultado['t_entrada'],
    ':ts'  => $resultado['t_saida'],
    ':t'   => $resultado['tempo_ms'],
]);
$id_msg_bot = $stmt->fetchColumn();
error_log("TELEGRAM — resposta guardada: $id_msg_bot");

// ─────────────────────────────────────────────────────────────
// 8. Registar fontes RAG
// ─────────────────────────────────────────────────────────────
registarFontes($pdo, $id_msg_bot, $contexto['fontes']);

// ─────────────────────────────────────────────────────────────
// 9. Enviar resposta ao utilizador via Telegram API
// ─────────────────────────────────────────────────────────────
enviarTelegram($chat_id, $resposta);

error_log("=== TELEGRAM WEBHOOK FIM ===");

http_response_code(200);
exit;


// ─────────────────────────────────────────────────────────────
// enviarTelegram()
// ─────────────────────────────────────────────────────────────
function enviarTelegram(string $chat_id, string $mensagem): void {
    $token = TELEGRAM_BOT_TOKEN;
    $url   = "https://api.telegram.org/bot{$token}/sendMessage";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'chat_id' => $chat_id,
            'text'    => $mensagem,
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $resultado = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("TELEGRAM — enviarTelegram httpCode: $httpCode");
    if ($httpCode !== 200) {
        error_log("TELEGRAM ERRO [{$httpCode}]: {$resultado}");
    }
}