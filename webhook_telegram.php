<?php
// webhook_telegram.php — Telegram

require_once 'configuracao.php';
require_once 'conexao.php';
require_once 'funcoes_chat.php';

$input = json_decode(file_get_contents('php://input'), true);

// Ignorar se não for mensagem de texto
$texto   = $input['message']['text']       ?? '';
$chat_id = (string)($input['message']['chat']['id'] ?? '');

if (empty($texto) || empty($chat_id)) {
    http_response_code(200);
    exit;
}

$pdo = obterConexao();

// Buscar ou criar conversa activa (últimas 24h)
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
            'plataforma'  => 'telegram',
            'first_name'  => $input['message']['from']['first_name'] ?? '',
            'username'    => $input['message']['from']['username']   ?? '',
        ])
    ]);
    $conversa = $stmt->fetch();
}

$id_conversa = $conversa['id_conversa'];

// Guardar mensagem do utilizador
$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'utilizador', :txt)
");
$stmt->execute([':id' => $id_conversa, ':txt' => $texto]);

// RAG + Gemini
$contexto = buscarContexto($pdo, $texto);
$resposta = chamarGemini($texto, $contexto, $id_conversa, $pdo);

// Guardar resposta
$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'assistente', :txt)
");
$stmt->execute([':id' => $id_conversa, ':txt' => $resposta]);

// Enviar resposta via Telegram
enviarTelegram($chat_id, $resposta);

http_response_code(200);
exit;


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
    ]);
    $resultado = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Telegram erro [{$httpCode}]: {$resultado}");
    }
}