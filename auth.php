<?php
// ============================================================
//  AUTH.PHP — Funções de autenticação e controlo de sessão
// ============================================================

// Iniciar sessão com parâmetros seguros (invocar antes de qualquer output)
function iniciarSessao(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // No Render, o site corre sempre em HTTPS. 
        // Mudamos 'Strict' para 'Lax' para permitir o redirecionamento do Google.
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,      // Forçamos true porque o Render usa HTTPS
            'httponly' => true,
            'samesite' => 'Lax',     // 'Lax' permite que o cookie seja enviado em redirecionamentos
        ]);
        session_start();
    }
}

/**
 * Verifica se existe uma sessão activa válida.
 */
function estaLogado(): bool {
    iniciarSessao();
    return isset($_SESSION['id_utilizador'], $_SESSION['perfil']);
}

/**
 * Verifica se o utilizador logado tem perfil 'admin'.
 */
function eAdmin(): bool {
    return estaLogado() && $_SESSION['perfil'] === 'admin';
}

/**
 * Devolve os dados do utilizador logado ou array vazio.
 */
function utilizadorActual(): array {
    if (!estaLogado()) {
        return [];
    }
    return [
        'id_utilizador' => $_SESSION['id_utilizador'],
        'nome'          => $_SESSION['nome'],
        'email'         => $_SESSION['email'],
        'perfil'        => $_SESSION['perfil'],
    ];
}

/**
 * Força login: redireciona para login.php se não estiver autenticado.
 */
function exigirLogin(): void {
    iniciarSessao();
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Força perfil admin: redireciona para index.php se não for admin.
 */
function exigirAdmin(): void {
    exigirLogin();
    if (!eAdmin()) {
        header('Location: index.php');
        exit;
    }
}