<?php
// webhook.php — Adaptado para Twilio WhatsApp Sandbox

// A Twilio NÃO faz GET de verificação como a Meta.
// Apenas recebe POSTs com application/x-www-form-urlencoded.

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
$body = $_POST['Body']    ?? '';   // texto da mensagem
$de   = $_POST['From']    ?? '';   // ex: whatsapp:+258876821594
$para = $_POST['To']      ?? '';   // ex: whatsapp:+14155238886

// Ignorar se não vier texto ou número
if (empty($body) || empty($de)) {
    http_response_code(200);
    echo '<Response></Response>';
    exit;
}

// Normalizar o número (remover prefixo "whatsapp:")
$telNormalizado = str_replace('whatsapp:', '', $de); // +258876821594

$pdo = obterConexao();

// Buscar ou criar conversa activa (últimas 24h)
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
}

$id_conversa = $conversa['id_conversa'];

// Guardar mensagem do utilizador
$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'utilizador', :txt)
");
$stmt->execute([':id' => $id_conversa, ':txt' => $body]);

// RAG + Gemini (inalterados)
$contexto = buscarContexto($pdo, $body);
$resposta  = chamarGemini($body, $contexto, $id_conversa, $pdo);

// Guardar resposta do bot
$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'assistente', :txt)
");
$stmt->execute([':id' => $id_conversa, ':txt' => $resposta]);

// Enviar resposta via Twilio
enviarWhatsApp($de, $resposta);  // passa o "whatsapp:+258..." original

http_response_code(200);
header('Content-Type: text/xml');
echo '<Response></Response>';  // Twilio espera TwiML vazio ou com conteúdo
exit;


// ─────────────────────────────────────────
// Envio via Twilio REST API
// ─────────────────────────────────────────
function enviarWhatsApp(string $para, string $mensagem): void {
    $sid   = TWILIO_ACCOUNT_SID;
    $token = TWILIO_AUTH_TOKEN;
    $from  = TWILIO_WHATSAPP_FROM; // whatsapp:+14155238886

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
        CURLOPT_USERPWD        => "{$sid}:{$token}",  // Basic Auth
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $resultado = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 201) {
        error_log("Twilio erro [{$httpCode}]: {$resultado}");
    }
}