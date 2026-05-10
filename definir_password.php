<?php
// ============================================================
//  DEFINIR_PASSWORD.PHP — Permite a contas Google definirem
//  uma palavra-passe para também poderem usar login tradicional
// ============================================================

require_once 'auth.php';
exigirLogin();
require_once 'conexao.php';

$erro    = '';
$sucesso = '';

$pdo = obterConexao();

// Verificar se a conta já tem password
$stmt = $pdo->prepare("SELECT senha_hash, google_id FROM utilizadores WHERE id_utilizador = :id");
$stmt->execute([':id' => $_SESSION['id_utilizador']]);
$conta = $stmt->fetch();

$tem_password = !empty($conta['senha_hash']);
$tem_google   = !empty($conta['google_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual    = $_POST['senha_atual']    ?? '';
    $nova_senha     = $_POST['nova_senha']     ?? '';
    $confirmar      = $_POST['confirmar']      ?? '';

    if (mb_strlen($nova_senha) < 8) {
        $erro = 'A nova palavra-passe deve ter pelo menos 8 caracteres.';
    } elseif ($nova_senha !== $confirmar) {
        $erro = 'As palavras-passe não coincidem.';
    } elseif ($tem_password && !password_verify($senha_atual, $conta['senha_hash'])) {
        // Só valida a senha atual se a conta já tiver uma
        $erro = 'A palavra-passe atual está incorreta.';
    } else {
        $hash = password_hash($nova_senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd  = $pdo->prepare("UPDATE utilizadores SET senha_hash = :hash WHERE id_utilizador = :id");
        $upd->execute([':hash' => $hash, ':id' => $_SESSION['id_utilizador']]);

        // Termina a sessão e redireciona para login com mensagem
        session_unset();
        session_destroy();
        header('Location: login.php?senha_definida=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Definir palavra-passe</title>
    <link rel="icon" type="image/svg+xml" href="logo.svg">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .card {
            width:100%; max-width:420px;
            background:var(--cor-fundo-2); border:1px solid var(--cor-borda);
            border-radius:var(--raio); padding:2.5rem 2rem;
            box-shadow:var(--sombra);
        }
        .card h1 { font-size:20px; font-weight:600; margin-bottom:.25rem; }
        .card p.sub { font-size:13px; color:var(--cor-texto-2); margin-bottom:1.8rem; }
        .campo-grupo { margin-bottom:1.1rem; }
        .campo-grupo label {
            display:block; font-size:12px; font-weight:500;
            color:var(--cor-texto-2); margin-bottom:.4rem;
            text-transform:uppercase; letter-spacing:.05em;
        }
        .campo-grupo input {
            width:100%; background:var(--cor-fundo-3);
            border:1px solid var(--cor-borda-forte); border-radius:var(--raio-sm);
            padding:.65rem .9rem; font-family:var(--fonte-ui); font-size:14px;
            color:var(--cor-texto); outline:none;
            transition:border-color var(--transicao), box-shadow var(--transicao);
        }
        .campo-grupo input:focus {
            border-color:var(--cor-acento);
            box-shadow:0 0 0 3px var(--cor-acento-suave);
        }
        .btn {
            width:100%; padding:.75rem;
            background:var(--cor-acento); color:#000;
            border:none; border-radius:var(--raio-sm);
            font-family:var(--fonte-ui); font-size:14px; font-weight:600;
            cursor:pointer; margin-top:.5rem;
            transition:background var(--transicao);
        }
        .btn:hover { background:var(--cor-acento-hover); }
        .alerta { padding:.7rem 1rem; border-radius:var(--raio-sm); font-size:13px; margin-bottom:1.2rem; }
        .alerta-erro    { background:rgba(248,113,113,.1); border:1px solid rgba(248,113,113,.3); color:var(--cor-erro); }
        .alerta-sucesso { background:rgba(74,222,128,.1);  border:1px solid rgba(74,222,128,.3);  color:var(--cor-sucesso); }
        .badge-metodo {
            display:inline-flex; align-items:center; gap:.4rem;
            font-size:12px; padding:.3rem .7rem;
            border-radius:999px; margin-bottom:1.5rem;
            background:var(--cor-acento-suave); border:1px solid var(--cor-borda-forte);
            color:var(--cor-texto-2);
        }
        .voltar { display:block; text-align:center; margin-top:1.2rem; font-size:13px; color:var(--cor-acento); text-decoration:none; }
        .voltar:hover { text-decoration:underline; }
    </style>
</head>
<body>
<div class="card">
    <h1><?= $tem_password ? 'Alterar palavra-passe' : 'Definir palavra-passe' ?></h1>
    <p class="sub">
        <?php if ($tem_password): ?>
            Altera a tua palavra-passe de acesso.
        <?php else: ?>
            A tua conta foi criada via Google. Define uma palavra-passe para poderes
            também usar o login tradicional.
        <?php endif; ?>
    </p>

    <?php if ($tem_google): ?>
        <div class="badge-metodo">
            <svg width="14" height="14" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Conta Google ligada
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alerta alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <div class="alerta alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <?php if ($tem_password): ?>
        <div class="campo-grupo">
            <label for="senha_atual">Palavra-passe atual</label>
            <input type="password" id="senha_atual" name="senha_atual" placeholder="••••••••" required>
        </div>
        <?php endif; ?>

        <div class="campo-grupo">
            <label for="nova_senha">Nova palavra-passe</label>
            <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 8 caracteres" required>
        </div>

        <div class="campo-grupo">
            <label for="confirmar">Confirmar palavra-passe</label>
            <input type="password" id="confirmar" name="confirmar" placeholder="Repete a palavra-passe" required>
        </div>

        <button type="submit" class="btn">
            <?= $tem_password ? 'Alterar palavra-passe' : 'Definir palavra-passe' ?>
        </button>
    </form>

    <a href="menu.php" class="voltar">← Voltar ao menu</a>
</div>
</body>
</html>