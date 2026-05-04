<?php
// webhook.php — Adaptado para Twilio WhatsApp Sandbox

require_once 'configuracao.php';
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido';
    exit;
}

// ─────────────────────────────────────────
// Twilio envia POST com campos form-encoded
// ─────────────────────────────────────────
$body = $_POST['Body'] ?? '';
$de   = $_POST['From'] ?? '';
$para = $_POST['To']   ?? '';

// 🔍 DEBUG — apagar depois
error_log("=== TWILIO WEBHOOK INICIO ===");
error_log("From: $de | To: $para | Body: $body");
error_log("POST completo: " . json_encode($_POST));
// 🔍 FIM DEBUG

if (empty($body) || empty($de)) {
    // 🔍 DEBUG — apagar depois
    error_log("SAIU CEDO — Body ou From vazio");
    // 🔍 FIM DEBUG
    http_response_code(200);
    echo '<Response></Response>';
    exit;
}

$telNormalizado = str_replace('whatsapp:', '', $de);

// 🔍 DEBUG — apagar depois
error_log("Tel normalizado: $telNormalizado");
// 🔍 FIM DEBUG

$pdo = obterConexao();

// 🔍 DEBUG — apagar depois
error_log("BD ligada com sucesso");
// 🔍 FIM DEBUG

$stmt = $pdo->prepare("
    SELECT id_conversa FROM conversas
    WHERE identificador_usuario = :tel
      AND canal = 'whatsapp'
      AND ultima_mensagem_em > NOW() - INTERVAL '24 hours'
    ORDER BY iniciada_em DESC LIMIT 1
");
$stmt->execute([':tel' => $telNormalizado]);
$conversa = $stmt->fetch();

if (!$conversa) {
    // 🔍 DEBUG — apagar depois
    error_log("Sem conversa activa — a criar nova");
    // 🔍 FIM DEBUG

    $stmt = $pdo->prepare("
        INSERT INTO conversas
          (id_configuracao_bot, identificador_usuario, canal, metadados)
        VALUES (:bot, :tel, 'whatsapp', :meta)
        RETURNING id_conversa
    ");
    $stmt->execute([
        ':bot'  => BOT_ID,
        ':tel'  => $telNormalizado,
        ':meta' => json_encode(['plataforma' => 'twilio_whatsapp'])
    ]);
    $conversa = $stmt->fetch();
} else {
    // 🔍 DEBUG — apagar depois
    error_log("Conversa activa encontrada");
    // 🔍 FIM DEBUG
}

$id_conversa = $conversa['id_conversa'];

// 🔍 DEBUG — apagar depois
error_log("id_conversa: $id_conversa");
// 🔍 FIM DEBUG

$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'utilizador', :txt)
");
$stmt->execute([':id' => $id_conversa, ':txt' => $body]);

// 🔍 DEBUG — apagar depois
error_log("Mensagem do utilizador guardada");
// 🔍 FIM DEBUG

$contexto = buscarContexto($pdo, $body);

// 🔍 DEBUG — apagar depois
error_log("Contexto RAG obtido: " . substr(json_encode($contexto), 0, 200));
// 🔍 FIM DEBUG

$resposta = chamarGemini($body, $contexto, $id_conversa, $pdo);

// 🔍 DEBUG — apagar depois
error_log("Resposta Gemini: " . substr($resposta, 0, 300));
// 🔍 FIM DEBUG

$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'assistente', :txt)
");
$stmt->execute([':id' => $id_conversa, ':txt' => $resposta]);

// 🔍 DEBUG — apagar depois
error_log("Resposta guardada na BD");
// 🔍 FIM DEBUG

enviarWhatsApp($de, $resposta);

// 🔍 DEBUG — apagar depois
error_log("=== TWILIO WEBHOOK FIM ===");
// 🔍 FIM DEBUG

http_response_code(200);
header('Content-Type: text/xml');
echo '<Response></Response>';
exit;


function enviarWhatsApp(string $para, string $mensagem): void {
    $sid   = TWILIO_ACCOUNT_SID;
    $token = TWILIO_AUTH_TOKEN;
    $from  = TWILIO_WHATSAPP_FROM;

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

    $data = http_build_query([
        'From' => $from,
        'To'   => $para,
        'Body' => $mensagem,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_USERPWD        => "{$sid}:{$token}",
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $resultado = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 🔍 DEBUG — apagar depois
    error_log("Twilio API — httpCode: $httpCode");
    error_log("Twilio API — resultado: $resultado");
    // 🔍 FIM DEBUG

    if ($httpCode !== 201) {
        error_log("Twilio ERRO [{$httpCode}]: {$resultado}");
    }
}