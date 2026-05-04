<?php
// webhook.php
require_once 'configuracao.php';  // ← reutiliza as tuas configs
require_once 'conexao.php';       // ← reutiliza a tua BD

// Token secreto que defines no painel da Meta
define('WHATSAPP_TOKEN',      getenv('WHATSAPP_TOKEN'));
define('WHATSAPP_PHONE_ID',   getenv('WHATSAPP_PHONE_ID'));
define('WEBHOOK_VERIFY_TOKEN', getenv('WEBHOOK_VERIFY_TOKEN'));

// ─────────────────────────────────────────
// GET: Meta verifica se o webhook é válido
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode']         ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge']    ?? '';

    if ($mode === 'subscribe' && $token === WEBHOOK_VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge; // Meta exige isto para confirmar
    } else {
        http_response_code(403);
        echo 'Token inválido';
    }
    exit;
}

// ─────────────────────────────────────────
// POST: Meta envia mensagem do utilizador
// ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Extrair número e texto
    $mensagens = $input['entry'][0]['changes'][0]['value']['messages'] ?? [];
    if (empty($mensagens)) { http_response_code(200); exit; }

    $msg  = $mensagens[0];
    $de   = $msg['from'];          // ex: 258876821594
    $text = $msg['text']['body'];  // texto enviado

    $pdo = obterConexao();

    // Buscar ou criar conversa (igual ao teu chat web)
    $stmt = $pdo->prepare("
        SELECT id_conversa FROM conversas
        WHERE identificador_usuario = :tel
          AND canal = 'whatsapp'
          AND ultima_mensagem_em > NOW() - INTERVAL '24 hours'
        ORDER BY iniciada_em DESC LIMIT 1
    ");
    $stmt->execute([':tel' => $de]);
    $conversa = $stmt->fetch();

    if (!$conversa) {
        $stmt = $pdo->prepare("
            INSERT INTO conversas
              (id_configuracao_bot, identificador_usuario, canal, metadados)
            VALUES (:bot, :tel, 'whatsapp', :meta)
            RETURNING id_conversa
        ");
        $stmt->execute([
            ':bot' => BOT_ID,
            ':tel' => $de,
            ':meta' => json_encode(['plataforma' => 'whatsapp'])
        ]);
        $conversa = $stmt->fetch();
    }

    $id_conversa = $conversa['id_conversa'];

    // Guardar mensagem do utilizador
    $stmt = $pdo->prepare("
        INSERT INTO mensagens (id_conversa, papel, conteudo)
        VALUES (:id, 'utilizador', :txt)
    ");
    $stmt->execute([':id' => $id_conversa, ':txt' => $text]);

    // Buscar contexto (o teu RAG já existente)
    $contexto = buscarContexto($pdo, $text);

    // Chamar Gemini (igual ao teu chat web)
    $resposta = chamarGemini($text, $contexto, $id_conversa, $pdo);

    // Guardar resposta
    $stmt = $pdo->prepare("
        INSERT INTO mensagens (id_conversa, papel, conteudo)
        VALUES (:id, 'assistente', :txt)
    ");
    $stmt->execute([':id' => $id_conversa, ':txt' => $resposta]);

    // Enviar resposta ao utilizador via Meta API
    enviarWhatsApp($de, $resposta);

    http_response_code(200);
    echo 'OK';
    exit;
}

function enviarWhatsApp(string $para, string $mensagem): void {
    $url  = 'https://graph.facebook.com/v19.0/' . WHATSAPP_PHONE_ID . '/messages';
    $data = [
        'messaging_product' => 'whatsapp',
        'to'   => $para,
        'type' => 'text',
        'text' => ['body' => $mensagem]
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . WHATSAPP_TOKEN,
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}