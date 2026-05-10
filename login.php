<?php
// ============================================================
//  LOGIN.PHP — Formulário e processamento de autenticação
// ============================================================

require_once 'auth.php';
iniciarSessao();

// Se já está logado, vai directo para index
if (estaLogado()) {
    header('Location: index.php');
    exit;
}

require_once 'conexao.php';

$erro    = '';
$sucesso = '';

// Mensagem de sucesso vinda do registo
if (isset($_GET['registado'])) {
    $sucesso = 'Conta criada com sucesso! Podes fazer login agora.';
}

// ── Processar o formulário ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preenche todos os campos.';
    } else {
        $pdo = obterConexao();

        $stmt = $pdo->prepare("
            SELECT id_utilizador, nome, email, senha_hash, perfil, ativo
            FROM utilizadores
            WHERE email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $utilizador = $stmt->fetch();

        if (!$utilizador || !password_verify($senha, $utilizador['senha_hash'])) {
            $erro = 'Credenciais inválidas.';
        } elseif (!$utilizador['ativo']) {
            $erro = 'Credenciais inválidas.';
        } else {
            // Login bem-sucedido
            session_regenerate_id(true);

            $_SESSION['id_utilizador'] = $utilizador['id_utilizador'];
            $_SESSION['nome']          = $utilizador['nome'];
            $_SESSION['email']         = $utilizador['email'];
            $_SESSION['perfil']        = $utilizador['perfil'];

            // ── Guarda sessão anónima para migração ──────────
            $id_sessao_anonimo = trim($_POST['id_sessao_anonimo'] ?? '');
            if ($id_sessao_anonimo !== '') {
                $_SESSION['migrar_sessao'] = $id_sessao_anonimo;
            }

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — ChatBot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
    
</head>
<body>

<div class="auth-card">
    <div class="auth-logo">
        <div class="auth-logo-icone">
            <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
                <circle cx="14" cy="14" r="13" stroke="var(--cor-acento)" stroke-width="1.5"/>
                <path d="M8 14c0-3.3 2.7-6 6-6s6 2.7 6 6-2.7 6-6 6" stroke="var(--cor-acento)" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="14" cy="14" r="2.5" fill="var(--cor-acento)"/>
            </svg>
        </div>
        <span class="auth-logo-nome">ChatBot</span>
    </div>

    <h1 class="auth-titulo">Bem-vindo de volta</h1>
    <p class="auth-subtitulo">Entra na tua conta para continuar</p>

    <?php if ($erro): ?>
        <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>

        <!-- ── Campo oculto para migração de sessão anónima ── -->
        <input type="hidden" id="id_sessao_anonimo" name="id_sessao_anonimo" value="">

        <div class="campo-grupo">
            <label for="email">Email</label>
            <input
                type="email" id="email" name="email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                placeholder="o-teu@email.com"
                autocomplete="email" required
            >
        </div>

        <div class="campo-grupo">
            <label for="senha">Palavra-passe</label>
            <input
                type="password" id="senha" name="senha"
                placeholder="••••••••"
                autocomplete="current-password" required
            >
        </div>

        <button type="submit" class="btn-auth">Entrar</button>
    </form>

    <p class="auth-link">
        Ainda não tens conta? <a href="registo.php">Criar conta</a>
    </p>
</div>

<!-- Preenche o campo oculto com o id_sessao anónimo guardado -->
<script>
    const sessaoAnonima = localStorage.getItem('chat_id_sessao');
    if (sessaoAnonima) {
        document.getElementById('id_sessao_anonimo').value = sessaoAnonima;
    }
</script>

</body>
</html>