<?php
require_once 'auth.php';
require_once 'configuracao.php';
require_once 'conexao.php';

// NÃO redireciona — o index é público
iniciarSessao();

$logado     = estaLogado();
$utilizador = $logado ? utilizadorActual() : [];
$pdo        = obterConexao();

// Busca dados do bot
$stmt = $pdo->prepare("
    SELECT b.nome AS nome_bot, b.descricao,
           p.nome_completo, p.profissao
    FROM configuracao_bot b
    LEFT JOIN perfil_criador p ON p.id_configuracao_bot = b.id_configuracao_bot
    WHERE b.id_configuracao_bot = :bot
    LIMIT 1
");
$stmt->execute([':bot' => BOT_ID]);
$info = $stmt->fetch();

$nome_bot      = $info['nome_bot']      ?? 'ChatBot';
$descricao_bot = $info['descricao']     ?? 'Assistente inteligente';
$nome_criador  = $info['nome_completo'] ?? '';
$profissao     = $info['profissao']     ?? '';

// Só busca conversas se estiver logado
$conversas = [];
if ($logado) {
    $stmt = $pdo->prepare("
        SELECT c.id_conversa, c.id_sessao, c.iniciada_em, c.ultima_mensagem_em,
               COUNT(m.id_mensagem) AS total_msgs,
               (SELECT conteudo FROM mensagens
                WHERE id_conversa = c.id_conversa AND papel = 'utilizador'
                ORDER BY enviada_em ASC LIMIT 1) AS primeira_msg
        FROM conversas c
        LEFT JOIN mensagens m ON m.id_conversa = c.id_conversa
        WHERE c.id_configuracao_bot = :bot
          AND c.id_utilizador = :uid
        GROUP BY c.id_conversa, c.id_sessao, c.iniciada_em, c.ultima_mensagem_em
        ORDER BY c.ultima_mensagem_em DESC
    ");
    $stmt->execute([':bot' => BOT_ID, ':uid' => $utilizador['id_utilizador']]);
    $conversas = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nome_bot) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
   
</head>
<body>

<aside class="barra-lateral">
    <div class="logo-area">
        <div class="logo-icone">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <circle cx="14" cy="14" r="13" stroke="var(--cor-acento)" stroke-width="1.5"/>
                <path d="M8 14c0-3.3 2.7-6 6-6s6 2.7 6 6-2.7 6-6 6" stroke="var(--cor-acento)" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="14" cy="14" r="2.5" fill="var(--cor-acento)"/>
            </svg>
        </div>
        <span class="logo-nome"><?= htmlspecialchars($nome_bot) ?></span>
    </div>

    <div class="bot-info-lateral">
        <p class="bot-descricao"><?= htmlspecialchars($descricao_bot) ?></p>
    </div>

    <?php if ($nome_criador): ?>
    <div class="criador-lateral">
        <div class="criador-etiqueta">Criado por</div>
        <div class="criador-nome"><?= htmlspecialchars($nome_criador) ?></div>
        <?php if ($profissao): ?>
        <div class="criador-profissao"><?= htmlspecialchars($profissao) ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Utilizador logado OU CTA de login ── -->
    <?php if ($logado): ?>
    <div class="utilizador-lateral">
        <div class="utilizador-avatar">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($utilizador['nome'], 0, 1))) ?>
        </div>
        <div class="utilizador-info">
            <div class="utilizador-nome"><?= htmlspecialchars($utilizador['nome']) ?></div>
            <div class="utilizador-perfil"><?= htmlspecialchars($utilizador['perfil']) ?></div>
        </div>
        <a href="logout.php" class="btn-logout" title="Terminar sessão">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M5 2H2.5A1.5 1.5 0 001 3.5v7A1.5 1.5 0 002.5 12H5M9 10l3-3-3-3M13 7H5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    </div>
    <?php else: ?>
    <div class="cta-login">
        <div class="cta-login-titulo">Acede a mais recursos</div>
        <p class="cta-login-desc">Cria uma conta gratuita e desbloqueia tudo</p>
        <ul class="cta-login-beneficios">
            <li>Histórico de conversas guardado</li>
            <li>Múltiplos chats organizados</li>
            <li>Retoma onde paraste</li>
        </ul>
        <a href="login.php" class="btn-cta-login">Entrar na conta</a>
        <a href="registo.php" class="btn-cta-registo">Ainda não tens conta? Regista-te</a>
    </div>
    <?php endif; ?>

    <!-- ── Gestão de Chats ── -->
    <div class="seccao-chats">
        <div class="seccao-chats-header">
            <span class="seccao-chats-titulo">Conversas</span>
            <?php if ($logado): ?>
            <button class="btn-novo-chat" id="btn-novo-chat">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Nova
            </button>
            <?php endif; ?>
        </div>

        <?php if ($logado): ?>
        <div class="lista-chats" id="lista-chats">
            <?php if (empty($conversas)): ?>
                <p class="sem-chats">Nenhuma conversa ainda</p>
            <?php else: foreach ($conversas as $conv):
                $titulo = $conv['primeira_msg']
                    ? mb_strimwidth($conv['primeira_msg'], 0, 35, '…')
                    : 'Conversa sem mensagens';
                $data = date('d/m H:i', strtotime($conv['ultima_mensagem_em']));
            ?>
                <div class="item-chat" data-id="<?= htmlspecialchars($conv['id_conversa']) ?>" data-sessao="<?= htmlspecialchars($conv['id_sessao']) ?>">
                    <div class="item-chat-icon">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M2 2h10a1 1 0 011 1v6a1 1 0 01-1 1H5l-3 2V3a1 1 0 011-1z" stroke="currentColor" stroke-width="1.2"/>
                        </svg>
                    </div>
                    <div class="item-chat-info">
                        <div class="item-chat-titulo"><?= htmlspecialchars($titulo) ?></div>
                        <div class="item-chat-meta"><?= $data ?> · <?= $conv['total_msgs'] ?> msgs</div>
                    </div>
                    <button class="btn-apagar-chat" title="Apagar conversa" data-id="<?= htmlspecialchars($conv['id_conversa']) ?>">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                            <path d="M2 3h9M5 3V2h3v1M4 3l.5 7h4L9 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php else: ?>
        <div class="chats-bloqueados">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.5"/>
                <path d="M8 11V7a4 4 0 018 0v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <p class="chats-bloqueados-texto">Entra na conta<br>para ver os teus chats</p>
        </div>
        <?php endif; ?>
    </div>

    <nav class="nav-lateral">
        <?php if (eAdmin()): ?>
        <a href="admin.php" class="nav-item">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect x="2" y="2" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                <rect x="9" y="2" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                <rect x="2" y="9" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
                <rect x="9" y="9" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.5"/>
            </svg>
            Painel Admin
        </a>
        <?php endif; ?>
    </nav>

    <div class="rodape-lateral">
        <span>Criado por Matias Alberto Matavel</span>
    </div>
</aside>

<main class="area-chat">
    <header class="cabecalho-chat">
        <div class="cabecalho-info">
            <div class="status-indicador"></div>
            <div>
                <h1 class="cabecalho-titulo" id="titulo-chat"><?= htmlspecialchars($nome_bot) ?></h1>
                <p class="cabecalho-subtitulo">Online · Responde em segundos</p>
            </div>
        </div>
        <?php if ($logado): ?>
        <button class="btn-limpar" id="btn-limpar" title="Nova conversa">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
        <?php endif; ?>
    </header>

    <div class="janela-mensagens" id="janela-mensagens">
        <div class="mensagem mensagem-bot" id="msg-boas-vindas">
            <div class="avatar-bot">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <circle cx="9" cy="9" r="8" stroke="var(--cor-acento)" stroke-width="1.2"/>
                    <circle cx="9" cy="9" r="2" fill="var(--cor-acento)"/>
                </svg>
            </div>
            <div class="balao">
                <?php if ($logado): ?>
                    <p>Olá, <strong><?= htmlspecialchars($utilizador['nome']) ?></strong>! Sou o <strong><?= htmlspecialchars($nome_bot) ?></strong>. Como posso ajudar?</p>
                <?php else: ?>
                    <p>Olá! Sou o <strong><?= htmlspecialchars($nome_bot) ?></strong>. Como posso ajudar?</p>
                <?php endif; ?>
                <div class="sugestoes">
                    <button class="sugestao" onclick="usarSugestao(this)">Quem te criou?</button>
                    <button class="sugestao" onclick="usarSugestao(this)">O que sabes fazer?</button>
                    <button class="sugestao" onclick="usarSugestao(this)">Que documentos tens disponíveis?</button>
                </div>
            </div>
        </div>
    </div>

    <div class="indicador-digitacao" id="indicador-digitacao" style="display:none">
        <div class="avatar-bot">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                <circle cx="9" cy="9" r="8" stroke="var(--cor-acento)" stroke-width="1.2"/>
                <circle cx="9" cy="9" r="2" fill="var(--cor-acento)"/>
            </svg>
        </div>
        <div class="balao balao-digitacao"><span></span><span></span><span></span></div>
    </div>

    <div class="area-entrada">
        <div class="caixa-entrada">
            <textarea
                id="campo-mensagem"
                class="campo-mensagem"
                placeholder="Escreve a tua mensagem..."
                rows="1"
                maxlength="2000"
            ></textarea>
            <button class="btn-enviar" id="btn-enviar" disabled>
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <path d="M15 9L3 3l3 6-3 6 12-6z" fill="currentColor"/>
                </svg>
            </button>
        </div>
        <p class="aviso-rodape">
            <?php if (!$logado): ?>
                <a href="login.php" style="color: var(--cor-acento); text-decoration: none;">Entra na conta</a>
                para guardar o histórico das tuas conversas.
            <?php else: ?>
                As respostas baseiam-se no conhecimento configurado.
            <?php endif; ?>
        </p>
    </div>
</main>

<script>
    const UTILIZADOR_LOGADO = <?= $logado ? 'true' : 'false' ?>;
    <?php if ($logado && isset($_SESSION['migrar_sessao'])): ?>
    const MIGRAR_SESSAO = <?= json_encode($_SESSION['migrar_sessao']) ?>;
    <?php unset($_SESSION['migrar_sessao']); ?>
    <?php else: ?>
    const MIGRAR_SESSAO = null;
    <?php endif; ?>
</script>
<script src="js/chat.js"></script>
</body>
</html>