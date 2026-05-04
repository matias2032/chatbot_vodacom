<?php
// ============================================================
//  WEBHOOK.PHP — Canal WhatsApp via Twilio Sandbox
//  Lógica RAG + Gemini delegada para funcoes_chat.php
// ============================================================

require_once 'configuracao.php';
require_once 'conexao.php';
require_once 'funcoes_chat.php';   // ← funções partilhadas

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido';
    exit;
}

// ------------------------------------------------------------
// 1. Twilio envia POST com campos form-encoded
// ------------------------------------------------------------
$body = trim($_POST['Body'] ?? '');
$de   = trim($_POST['From'] ?? '');
$para = trim($_POST['To']   ?? '');

error_log("=== TWILIO WEBHOOK INICIO ===");
error_log("From: $de | To: $para | Body: $body");

// Responde imediatamente 200 para a Twilio não fazer retry
// (a resposta real ao utilizador é enviada pelo REST API abaixo)
if (empty($body) || empty($de)) {
    error_log("SAIU CEDO — Body ou From vazio");
    http_response_code(200);
    header('Content-Type: text/xml');
    echo '<Response></Response>';
    exit;
}

$telNormalizado = str_replace('whatsapp:', '', $de);
error_log("Tel normalizado: $telNormalizado");

$pdo = obterConexao();
error_log("BD ligada com sucesso");

// ------------------------------------------------------------
// 2. Obtém ou cria conversa (janela de 24h por número)
// ------------------------------------------------------------
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
    error_log("Sem conversa activa — a criar nova");
    $stmt = $pdo->prepare("
        INSERT INTO conversas
            (id_configuracao_bot, identificador_usuario, canal, metadados)
        VALUES (:bot, :tel, 'whatsapp', :meta)
        RETURNING id_conversa
    ");
    $stmt->execute([
        ':bot'  => BOT_ID,
        ':tel'  => $telNormalizado,
        ':meta' => json_encode(['plataforma' => 'twilio_whatsapp']),
    ]);
    $conversa = $stmt->fetch();
} else {
    error_log("Conversa activa encontrada");
}

$id_conversa = $conversa['id_conversa'];
error_log("id_conversa: $id_conversa");

// ------------------------------------------------------------
// 3. Guarda mensagem do utilizador
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    INSERT INTO mensagens (id_conversa, papel, conteudo)
    VALUES (:id, 'utilizador', :txt)
    RETURNING id_mensagem
");
$stmt->execute([':id' => $id_conversa, ':txt' => $body]);
$id_msg_user = $stmt->fetchColumn();
error_log("Mensagem do utilizador guardada: $id_msg_user");

// ------------------------------------------------------------
// 4. RAG — busca contexto
// ------------------------------------------------------------
$contexto = buscarContexto($pdo, $body);
error_log("Contexto RAG: " . count($contexto['partes']) . " partes, " . count($contexto['fontes']) . " fontes");

// ------------------------------------------------------------
// 5. Chama Gemini
// ------------------------------------------------------------
$resultado = chamarGemini($body, $contexto, $id_conversa, $pdo);
error_log("Gemini tempo_ms: " . $resultado['tempo_ms']);

if ($resultado['erro'] !== null) {
    error_log("[webhook] Erro Gemini: " . $resultado['erro']);
    // Envia mensagem de erro amigável ao utilizador
    $resultado['texto'] = 'Desculpa, ocorreu um erro interno. Tenta novamente em instantes.';
}

$resposta = $resultado['texto'];
error_log("Resposta Gemini: " . substr($resposta, 0, 200));

// ------------------------------------------------------------
// 6. Guarda resposta do assistente
// ------------------------------------------------------------
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
error_log("Resposta guardada: $id_msg_bot");

// ------------------------------------------------------------
// 7. Regista fontes RAG
// ------------------------------------------------------------
registarFontes($pdo, $id_msg_bot, $contexto['fontes']);

// ------------------------------------------------------------
// 8. Envia resposta ao utilizador via Twilio REST API
// ------------------------------------------------------------
enviarWhatsApp($de, $resposta);

error_log("=== TWILIO WEBHOOK FIM ===");

http_response_code(200);
header('Content-Type: text/xml');
echo '<Response></Response>';
exit;


// ------------------------------------------------------------
// enviarWhatsApp()
// Envia mensagem ao utilizador via Twilio REST API
// Mantida aqui porque é exclusiva do canal WhatsApp
// ------------------------------------------------------------
function enviarWhatsApp(string $para, string $mensagem): void {
    $sid   = TWILIO_ACCOUNT_SID;
    $token = TWILIO_AUTH_TOKEN;
    $from  = TWILIO_WHATSAPP_FROM;

    $url  = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
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
        CURLOPT_TIMEOUT        => 10,
    ]);

    $resultado = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Twilio REST API — httpCode: $httpCode");
    if ($httpCode !== 201) {
        error_log("Twilio ERRO [{$httpCode}]: {$resultado}");
    }
}