<?php
// ============================================================
//  INDEX.PHP — Landing page do ChatBot de Educação Financeira
// ============================================================
require_once 'auth.php';
iniciarSessao();
$logado = estaLogado();
$nome   = $logado ? ($_SESSION['nome'] ?? 'Utilizador') : '';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinBot — O teu assistente de educação financeira</title>
    <link rel="icon" type="image/svg+xml" href="logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        /* ── Reset para página de landing ── */
        body {
            display: block;
            overflow: auto;
            overflow-x: hidden;
        }

        /* ============================================================
           NAVBAR
        ============================================================ */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            height: 64px;
            background: rgba(13,15,20,0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(37,41,53,0.6);
            transition: background var(--transicao);
        }
        .navbar.scrolled {
            background: rgba(13,15,20,0.97);
            border-bottom-color: var(--cor-borda);
        }
        .nav-logo {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .nav-logo-icone {
            width: 36px; height: 36px;
            background: var(--cor-acento-suave);
            border: 1px solid var(--cor-borda-forte);
            border-radius: var(--raio-sm);
            display: flex; align-items: center; justify-content: center;
        }
        .nav-logo-nome {
            font-size: 16px; font-weight: 700;
            color: var(--cor-texto); letter-spacing: -0.02em;
        }
        .nav-links {
            display: flex; align-items: center; gap: 6px;
        }
        .nav-link {
            padding: 7px 14px; border-radius: var(--raio-sm);
            font-size: 13px; font-weight: 500;
            color: var(--cor-texto-2); text-decoration: none;
            transition: all var(--transicao);
        }
        .nav-link:hover { color: var(--cor-texto); background: var(--cor-fundo-3); }
        .nav-cta {
            padding: 8px 18px;
            background: var(--cor-acento); color: #fff;
            border: none; border-radius: var(--raio-sm);
            font-family: var(--fonte-ui); font-size: 13px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: background var(--transicao), transform var(--transicao);
        }
        .nav-cta:hover { background: var(--cor-acento-hover); transform: translateY(-1px); }
        .nav-user {
            display: flex; align-items: center; gap: 10px;
        }
        .nav-user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--cor-acento-suave);
            border: 1px solid var(--cor-borda-forte);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; color: var(--cor-acento);
        }
        .nav-user-nome { font-size: 13px; font-weight: 500; color: var(--cor-texto-2); }

        /* ============================================================
           HERO PRINCIPAL
        ============================================================ */
        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 120px 40px 80px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        /* Fundo animado */
        .hero-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .hero-bg-glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.12;
        }
        .hero-bg-glow-1 {
            width: 600px; height: 600px;
            background: var(--cor-acento);
            top: -200px; left: 50%;
            transform: translateX(-50%);
            animation: floatGlow 8s ease-in-out infinite;
        }
        .hero-bg-glow-2 {
            width: 400px; height: 400px;
            background: #4ade80;
            bottom: -100px; right: -100px;
            opacity: 0.07;
            animation: floatGlow 10s ease-in-out infinite reverse;
        }
        .hero-bg-glow-3 {
            width: 300px; height: 300px;
            background: #60a5fa;
            bottom: 100px; left: -50px;
            opacity: 0.06;
            animation: floatGlow 12s ease-in-out infinite;
        }
        @keyframes floatGlow {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-30px); }
        }

        /* Grid de fundo */
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(108,143,255,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(108,143,255,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 40%, transparent 100%);
        }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 6px 14px; border-radius: 20px;
            background: var(--cor-acento-suave);
            border: 1px solid rgba(108,143,255,0.25);
            font-size: 12px; font-weight: 600; color: var(--cor-acento);
            margin-bottom: 28px;
            animation: entrarBadge 0.6s ease both;
        }
        .hero-badge-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--cor-sucesso);
            box-shadow: 0 0 6px var(--cor-sucesso);
            animation: pulsar 2s ease-in-out infinite;
        }
        @keyframes entrarBadge {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .hero-titulo {
            font-size: clamp(2.4rem, 6vw, 4.2rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: var(--cor-texto);
            max-width: 820px;
            margin-bottom: 24px;
            animation: entrarTexto 0.7s ease 0.1s both;
        }
        .hero-titulo-destaque {
            background: linear-gradient(135deg, var(--cor-acento) 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        @keyframes entrarTexto {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .hero-subtitulo {
            font-size: 17px; font-weight: 400;
            color: var(--cor-texto-2); max-width: 560px;
            line-height: 1.7; margin-bottom: 40px;
            animation: entrarTexto 0.7s ease 0.2s both;
        }

        .hero-acoes {
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
            justify-content: center;
            animation: entrarTexto 0.7s ease 0.3s both;
            margin-bottom: 64px;
        }
        .btn-hero-primario {
            display: flex; align-items: center; gap: 8px;
            padding: 14px 28px; background: var(--cor-acento); color: #fff;
            border: none; border-radius: var(--raio);
            font-family: var(--fonte-ui); font-size: 15px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: background var(--transicao), transform var(--transicao), box-shadow var(--transicao);
            box-shadow: 0 4px 20px rgba(108,143,255,0.3);
        }
        .btn-hero-primario:hover {
            background: var(--cor-acento-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(108,143,255,0.4);
        }
        .btn-hero-secundario {
            display: flex; align-items: center; gap: 8px;
            padding: 14px 24px;
            background: transparent;
            border: 1px solid var(--cor-borda-forte);
            border-radius: var(--raio);
            font-family: var(--fonte-ui); font-size: 15px; font-weight: 500;
            color: var(--cor-texto-2); text-decoration: none;
            transition: all var(--transicao);
        }
        .btn-hero-secundario:hover {
            border-color: var(--cor-acento);
            color: var(--cor-acento);
            background: var(--cor-acento-suave);
        }

        /* Stats rápidas */
        .hero-stats {
            display: flex; gap: 40px; flex-wrap: wrap; justify-content: center;
            animation: entrarTexto 0.7s ease 0.4s both;
        }
        .hero-stat { text-align: center; }
        .hero-stat-valor {
            font-size: 26px; font-weight: 700; color: var(--cor-texto);
            letter-spacing: -0.02em;
        }
        .hero-stat-label { font-size: 12px; color: var(--cor-texto-3); margin-top: 2px; }
        .hero-stat-sep {
            width: 1px; background: var(--cor-borda);
            align-self: stretch;
        }

        /* ============================================================
           CARROSSEL DE HEROES
        ============================================================ */
        .secao-carrossel {
            padding: 0 0 80px;
            position: relative;
        }
        .secao-label {
            text-align: center;
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.12em;
            color: var(--cor-texto-3); margin-bottom: 32px;
        }

        .carrossel-pista {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            padding: 0 40px 16px;
            cursor: grab;
        }
        .carrossel-pista::-webkit-scrollbar { display: none; }
        .carrossel-pista.arrastando { cursor: grabbing; }

        .hero-card {
            flex: 0 0 480px;
            height: 300px;
            border-radius: var(--raio);
            overflow: hidden;
            position: relative;
            scroll-snap-align: start;
            border: 1px solid var(--cor-borda);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hero-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .hero-card img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .hero-card:hover img { transform: scale(1.05); }
        .hero-card-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(13,15,20,0.95) 0%, rgba(13,15,20,0.3) 60%, transparent 100%);
        }
        .hero-card-conteudo {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 24px;
        }
        .hero-card-tag {
            display: inline-block; padding: 3px 10px;
            border-radius: 20px; font-size: 11px; font-weight: 600;
            margin-bottom: 10px;
        }
        .tag-investimento { background: rgba(108,143,255,0.2); color: var(--cor-acento); border: 1px solid rgba(108,143,255,0.3); }
        .tag-poupanca     { background: rgba(74,222,128,0.15);  color: #4ade80; border: 1px solid rgba(74,222,128,0.3); }
        .tag-credito      { background: rgba(251,191,36,0.15);  color: #fbbf24; border: 1px solid rgba(251,191,36,0.3); }
        .tag-orcamento    { background: rgba(96,165,250,0.15);  color: #60a5fa; border: 1px solid rgba(96,165,250,0.3); }

        .hero-card-titulo {
            font-size: 18px; font-weight: 700;
            color: #fff; line-height: 1.2; margin-bottom: 6px;
        }
        .hero-card-desc {
            font-size: 13px; color: rgba(255,255,255,0.6);
            line-height: 1.5;
        }

        /* Dots do carrossel */
        .carrossel-dots {
            display: flex; justify-content: center; gap: 6px;
            margin-top: 20px;
        }
        .carrossel-dot {
            width: 6px; height: 6px; border-radius: 3px;
            background: var(--cor-borda-forte);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .carrossel-dot.ativo {
            width: 20px;
            background: var(--cor-acento);
        }

        /* ============================================================
           SECÇÃO: COMO FUNCIONA
        ============================================================ */
        .secao {
            padding: 80px 40px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .secao-cabecalho {
            text-align: center; margin-bottom: 56px;
        }
        .secao-titulo {
            font-size: 2rem; font-weight: 700;
            letter-spacing: -0.025em; color: var(--cor-texto);
            margin-bottom: 12px;
        }
        .secao-subtitulo {
            font-size: 15px; color: var(--cor-texto-2);
            max-width: 500px; margin: 0 auto; line-height: 1.7;
        }

        .passos {
            display: grid; grid-template-columns: repeat(3,1fr); gap: 24px;
        }
        .passo {
            background: var(--cor-fundo-2);
            border: 1px solid var(--cor-borda);
            border-radius: var(--raio);
            padding: 28px;
            position: relative;
            transition: border-color var(--transicao), transform var(--transicao);
        }
        .passo:hover {
            border-color: var(--cor-borda-forte);
            transform: translateY(-3px);
        }
        .passo-numero {
            font-family: var(--fonte-mono);
            font-size: 11px; color: var(--cor-acento);
            font-weight: 500; margin-bottom: 16px;
            opacity: 0.7;
        }
        .passo-icone {
            width: 44px; height: 44px;
            border-radius: var(--raio-sm);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
            font-size: 20px;
        }
        .passo-titulo { font-size: 15px; font-weight: 600; margin-bottom: 8px; }
        .passo-desc { font-size: 13px; color: var(--cor-texto-2); line-height: 1.6; }
        .passo-linha {
            position: absolute; top: 50px; right: -13px;
            width: 26px; height: 1px;
            background: linear-gradient(90deg, var(--cor-borda-forte), transparent);
            z-index: 1;
        }
        .passo:last-child .passo-linha { display: none; }

        /* ============================================================
           SECÇÃO: FUNCIONALIDADES
        ============================================================ */
        .funcionalidades {
            display: grid; grid-template-columns: repeat(2,1fr); gap: 16px;
        }
        .func-card {
            background: var(--cor-fundo-2);
            border: 1px solid var(--cor-borda);
            border-radius: var(--raio);
            padding: 24px;
            display: flex; gap: 16px;
            transition: all var(--transicao);
            cursor: default;
        }
        .func-card:hover {
            border-color: rgba(108,143,255,0.3);
            background: var(--cor-fundo-3);
        }
        .func-icone {
            width: 42px; height: 42px; flex-shrink: 0;
            border-radius: var(--raio-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .func-texto {}
        .func-titulo { font-size: 14px; font-weight: 600; margin-bottom: 6px; }
        .func-desc { font-size: 13px; color: var(--cor-texto-2); line-height: 1.6; }

        /* ============================================================
           DEMO DE CHAT
        ============================================================ */
        .secao-demo {
            padding: 80px 40px;
            background: var(--cor-fundo-2);
            border-top: 1px solid var(--cor-borda);
            border-bottom: 1px solid var(--cor-borda);
        }
        .demo-inner {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: 1fr 1fr; gap: 60px;
            align-items: center;
        }
        .demo-texto {}
        .demo-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px;
            background: rgba(74,222,128,0.1);
            border: 1px solid rgba(74,222,128,0.25);
            font-size: 11px; font-weight: 600; color: #4ade80;
            margin-bottom: 20px;
        }
        .demo-titulo {
            font-size: 1.8rem; font-weight: 700;
            letter-spacing: -0.025em; margin-bottom: 16px;
            line-height: 1.2;
        }
        .demo-desc {
            font-size: 14px; color: var(--cor-texto-2);
            line-height: 1.7; margin-bottom: 28px;
        }
        .demo-lista { list-style: none; display: flex; flex-direction: column; gap: 10px; }
        .demo-lista li {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; color: var(--cor-texto-2);
        }
        .demo-lista li::before {
            content: ''; width: 6px; height: 6px; border-radius: 50%;
            background: var(--cor-acento); flex-shrink: 0;
        }

        /* Mockup de chat */
        .chat-mockup {
            background: var(--cor-fundo);
            border: 1px solid var(--cor-borda);
            border-radius: var(--raio);
            overflow: hidden;
            box-shadow: var(--sombra);
        }
        .chat-mockup-header {
            padding: 12px 16px;
            background: var(--cor-fundo-2);
            border-bottom: 1px solid var(--cor-borda);
            display: flex; align-items: center; gap: 8px;
        }
        .chat-mockup-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--cor-sucesso);
            box-shadow: 0 0 6px var(--cor-sucesso);
            animation: pulsar 2s ease-in-out infinite;
        }
        .chat-mockup-nome { font-size: 13px; font-weight: 600; }
        .chat-mockup-body {
            padding: 20px; display: flex; flex-direction: column; gap: 14px;
            min-height: 280px;
        }
        .chat-msg {
            display: flex; gap: 10px; align-items: flex-start;
            opacity: 0; transform: translateY(8px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }
        .chat-msg.visivel { opacity: 1; transform: translateY(0); }
        .chat-msg-user { flex-direction: row-reverse; }
        .chat-msg-avatar {
            width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
            background: var(--cor-acento-suave);
            border: 1px solid var(--cor-borda-forte);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
        }
        .chat-msg-balao {
            padding: 10px 14px; border-radius: 12px;
            font-size: 13px; line-height: 1.6; max-width: 80%;
        }
        .chat-msg-bot .chat-msg-balao {
            background: var(--cor-bot-balao);
            border: 1px solid var(--cor-borda);
            border-top-left-radius: 3px;
            color: var(--cor-texto);
        }
        .chat-msg-user .chat-msg-balao {
            background: var(--cor-user-balao);
            border: 1px solid rgba(108,143,255,0.2);
            border-top-right-radius: 3px;
            color: var(--cor-user-texto);
        }
        .chat-mockup-input {
            padding: 12px 16px;
            border-top: 1px solid var(--cor-borda);
            display: flex; gap: 8px; align-items: center;
        }
        .chat-mockup-input-field {
            flex: 1; background: var(--cor-fundo-3);
            border: 1px solid var(--cor-borda-forte);
            border-radius: var(--raio-sm);
            padding: 8px 12px;
            font-family: var(--fonte-ui); font-size: 13px;
            color: var(--cor-texto-3);
        }
        .chat-mockup-btn {
            width: 32px; height: 32px; border-radius: 6px;
            background: var(--cor-acento); border: none;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
        }

        /* ============================================================
           TÓPICOS FINANCEIROS (chips interactivos)
        ============================================================ */
        .secao-topicos { padding: 80px 40px; max-width: 1100px; margin: 0 auto; }
        .topicos-grid {
            display: flex; flex-wrap: wrap; gap: 10px;
            justify-content: center; margin-top: 40px;
        }
        .topico-chip {
            padding: 10px 18px;
            background: var(--cor-fundo-2);
            border: 1px solid var(--cor-borda);
            border-radius: 30px;
            font-size: 13px; font-weight: 500;
            color: var(--cor-texto-2);
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex; align-items: center; gap: 7px;
            text-decoration: none;
        }
        .topico-chip:hover {
            border-color: var(--cor-acento);
            color: var(--cor-acento);
            background: var(--cor-acento-suave);
            transform: translateY(-2px);
        }
        .topico-chip span { font-size: 16px; }

        /* ============================================================
           TESTEMUNHOS
        ============================================================ */
        .secao-testemunhos {
            background: var(--cor-fundo-2);
            border-top: 1px solid var(--cor-borda);
            border-bottom: 1px solid var(--cor-borda);
            padding: 80px 40px;
        }
        .testemunhos-inner { max-width: 1100px; margin: 0 auto; }
        .testemunhos-grid {
            display: grid; grid-template-columns: repeat(3,1fr); gap: 20px;
            margin-top: 48px;
        }
        .testemunho {
            background: var(--cor-fundo);
            border: 1px solid var(--cor-borda);
            border-radius: var(--raio);
            padding: 24px;
            transition: border-color var(--transicao);
        }
        .testemunho:hover { border-color: var(--cor-borda-forte); }
        .testemunho-estrelas { color: #fbbf24; font-size: 13px; margin-bottom: 14px; }
        .testemunho-texto {
            font-size: 14px; color: var(--cor-texto-2);
            line-height: 1.7; margin-bottom: 20px;
            font-style: italic;
        }
        .testemunho-autor { display: flex; align-items: center; gap: 10px; }
        .testemunho-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--cor-acento-suave);
            border: 1px solid var(--cor-borda-forte);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600; color: var(--cor-acento);
        }
        .testemunho-nome { font-size: 13px; font-weight: 600; }
        .testemunho-cargo { font-size: 11px; color: var(--cor-texto-3); }

        /* ============================================================
           CTA FINAL
        ============================================================ */
        .secao-cta {
            padding: 100px 40px;
            text-align: center;
            position: relative; overflow: hidden;
        }
        .cta-bg {
            position: absolute; inset: 0; pointer-events: none;
        }
        .cta-glow {
            position: absolute;
            width: 500px; height: 300px;
            background: var(--cor-acento);
            filter: blur(100px); opacity: 0.07;
            top: 50%; left: 50%;
            transform: translate(-50%,-50%);
        }
        .cta-titulo {
            font-size: 2.4rem; font-weight: 800;
            letter-spacing: -0.03em; margin-bottom: 16px;
            max-width: 600px; margin-left: auto; margin-right: auto;
            line-height: 1.1;
        }
        .cta-desc {
            font-size: 16px; color: var(--cor-texto-2);
            max-width: 440px; margin: 0 auto 40px;
            line-height: 1.7;
        }
        .cta-acoes {
            display: flex; justify-content: center;
            gap: 12px; flex-wrap: wrap;
        }

        /* ============================================================
           RODAPÉ
        ============================================================ */
        .rodape {
            border-top: 1px solid var(--cor-borda);
            padding: 40px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px;
        }
        .rodape-logo { display: flex; align-items: center; gap: 8px; }
        .rodape-nome { font-size: 14px; font-weight: 600; color: var(--cor-texto); }
        .rodape-copy { font-size: 12px; color: var(--cor-texto-3); }
        .rodape-links { display: flex; gap: 20px; }
        .rodape-link { font-size: 13px; color: var(--cor-texto-3); text-decoration: none; transition: color var(--transicao); }
        .rodape-link:hover { color: var(--cor-acento); }

        /* ============================================================
           CONTADOR ANIMADO
        ============================================================ */
        .contador-animado { display: inline-block; }

        /* ============================================================
           RESPONSIVO
        ============================================================ */
        @media (max-width: 900px) {
            .passos { grid-template-columns: 1fr; }
            .funcionalidades { grid-template-columns: 1fr; }
            .demo-inner { grid-template-columns: 1fr; }
            .testemunhos-grid { grid-template-columns: 1fr; }
            .grelha-stats { grid-template-columns: repeat(2,1fr); }
        }
        @media (max-width: 640px) {
            .navbar { padding: 0 20px; }
            .hero { padding: 100px 20px 60px; }
            .hero-titulo { font-size: 2rem; }
            .secao { padding: 60px 20px; }
            .secao-demo { padding: 60px 20px; }
            .hero-card { flex: 0 0 320px; height: 220px; }
            .carrossel-pista { padding: 0 20px 16px; }
            .nav-links { display: none; }
            .hero-stats { gap: 24px; }
            .rodape { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<!-- ============================================================
     NAVBAR
============================================================ -->
<nav class="navbar" id="navbar">
    <a href="index.php" class="nav-logo">
        <div class="nav-logo-icone">
            <svg width="20" height="20" viewBox="0 0 28 28" fill="none">
                <circle cx="14" cy="14" r="13" stroke="var(--cor-acento)" stroke-width="1.5"/>
                <path d="M8 14c0-3.3 2.7-6 6-6s6 2.7 6 6-2.7 6-6 6" stroke="var(--cor-acento)" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="14" cy="14" r="2.5" fill="var(--cor-acento)"/>
            </svg>
        </div>
        <span class="nav-logo-nome">FinBot</span>
    </a>

    <div class="nav-links">
        <a href="#como-funciona" class="nav-link">Como funciona</a>
        <a href="#funcionalidades" class="nav-link">Funcionalidades</a>
        <a href="#topicos" class="nav-link">Tópicos</a>
    </div>

    <?php if ($logado): ?>
    <div class="nav-user">
        <div class="nav-user-avatar"><?= mb_strtoupper(mb_substr($nome, 0, 1)) ?></div>
        <span class="nav-user-nome"><?= htmlspecialchars($nome) ?></span>
        <a href="menu.php" class="nav-cta">Abrir Chat</a>
    </div>
    <?php else: ?>
    <div style="display:flex;gap:8px;align-items:center;">
        <a href="login.php" class="nav-link">Entrar</a>
        <a href="registo.php" class="nav-cta">Começar grátis</a>
    </div>
    <?php endif; ?>
</nav>


<!-- ============================================================
     HERO PRINCIPAL
============================================================ -->
<section class="hero">
    <div class="hero-bg">
        <div class="hero-grid"></div>
        <div class="hero-bg-glow hero-bg-glow-1"></div>
        <div class="hero-bg-glow hero-bg-glow-2"></div>
        <div class="hero-bg-glow hero-bg-glow-3"></div>
    </div>

    <div class="hero-badge">
        <div class="hero-badge-dot"></div>
        Assistente de IA activo e pronto a ajudar
    </div>

    <h1 class="hero-titulo">
        O teu guia pessoal para<br>
        <span class="hero-titulo-destaque">liberdade financeira</span>
    </h1>

    <p class="hero-subtitulo">
        Faz perguntas, aprende a investir, controla o orçamento e toma decisões financeiras mais inteligentes — tudo numa conversa natural.
    </p>

    <div class="hero-acoes">
        <?php if ($logado): ?>
            <a href="menu.php" class="btn-hero-primario">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Continuar conversa
            </a>
        <?php else: ?>
            <a href="registo.php" class="btn-hero-primario">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Começar agora — é grátis
            </a>
            <a href="menu.php" class="btn-hero-secundario">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" stroke="currentColor" stroke-width="2"/><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/></svg>
                Experimentar sem conta
            </a>
        <?php endif; ?>
    </div>

    <div class="hero-stats">
        <div class="hero-stat">
            <div class="hero-stat-valor"><span class="contador-animado" data-alvo="12">0</span>k+</div>
            <div class="hero-stat-label">Perguntas respondidas</div>
        </div>
        <div class="hero-stat-sep"></div>
        <div class="hero-stat">
            <div class="hero-stat-valor"><span class="contador-animado" data-alvo="98">0</span>%</div>
            <div class="hero-stat-label">Taxa de satisfação</div>
        </div>
        <div class="hero-stat-sep"></div>
        <div class="hero-stat">
            <div class="hero-stat-valor"><span class="contador-animado" data-alvo="24">0</span>/7</div>
            <div class="hero-stat-label">Disponível sempre</div>
        </div>
        <div class="hero-stat-sep"></div>
        <div class="hero-stat">
            <div class="hero-stat-valor"><span class="contador-animado" data-alvo="50">0</span>+</div>
            <div class="hero-stat-label">Tópicos financeiros</div>
        </div>
    </div>
</section>


<!-- ============================================================
     CARROSSEL DE HEROES
============================================================ -->
<section class="secao-carrossel">
    <p class="secao-label">Explora os temas</p>

    <div class="carrossel-pista" id="carrossel">

        <div class="hero-card">
            <img
                src="https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&q=80"
                alt="Investimentos"
                loading="lazy"
            >
            <div class="hero-card-overlay"></div>
            <div class="hero-card-conteudo">
                <span class="hero-card-tag tag-investimento">Investimentos</span>
                <h3 class="hero-card-titulo">Faz o teu dinheiro trabalhar por ti</h3>
                <p class="hero-card-desc">Aprende sobre acções, fundos e obrigações de forma simples.</p>
            </div>
        </div>

        <div class="hero-card">
            <img
                src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&q=80"
                alt="Poupança"
                loading="lazy"
            >
            <div class="hero-card-overlay"></div>
            <div class="hero-card-conteudo">
                <span class="hero-card-tag tag-poupanca">Poupança</span>
                <h3 class="hero-card-titulo">Constrói o teu fundo de emergência</h3>
                <p class="hero-card-desc">Estratégias práticas para poupar mais todos os meses.</p>
            </div>
        </div>

        <div class="hero-card">
            <img
                src="https://images.unsplash.com/photo-1434626881859-194d67b2b86f?w=800&q=80"
                alt="Orçamento"
                loading="lazy"
            >
            <div class="hero-card-overlay"></div>
            <div class="hero-card-conteudo">
                <span class="hero-card-tag tag-orcamento">Orçamento</span>
                <h3 class="hero-card-titulo">Controla as tuas finanças pessoais</h3>
                <p class="hero-card-desc">Métodos como 50/30/20 explicados passo a passo.</p>
            </div>
        </div>

        <div class="hero-card">
            <img
                src="https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800&q=80"
                alt="Crédito"
                loading="lazy"
            >
            <div class="hero-card-overlay"></div>
            <div class="hero-card-conteudo">
                <span class="hero-card-tag tag-credito">Crédito</span>
                <h3 class="hero-card-titulo">Entende e melhora o teu score</h3>
                <p class="hero-card-desc">Como o crédito funciona e como usá-lo a teu favor.</p>
            </div>
        </div>

    </div>

    <div class="carrossel-dots" id="carrossel-dots">
        <div class="carrossel-dot ativo" data-idx="0"></div>
        <div class="carrossel-dot" data-idx="1"></div>
        <div class="carrossel-dot" data-idx="2"></div>
        <div class="carrossel-dot" data-idx="3"></div>
    </div>
</section>


<!-- ============================================================
     COMO FUNCIONA
============================================================ -->
<section class="secao" id="como-funciona">
    <div class="secao-cabecalho">
        <p class="secao-label">Como funciona</p>
        <h2 class="secao-titulo">Simples como uma conversa</h2>
        <p class="secao-subtitulo">Sem formulários complexos. Sem linguagem técnica. Apenas perguntas e respostas claras.</p>
    </div>

    <div class="passos">
        <div class="passo">
            <div class="passo-numero">01</div>
            <div class="passo-icone" style="background:rgba(108,143,255,0.12);">💬</div>
            <div class="passo-titulo">Faz a tua pergunta</div>
            <div class="passo-desc">Escreve o que quiseres saber sobre finanças, desde o básico ao avançado, sem julgamentos.</div>
            <div class="passo-linha"></div>
        </div>
        <div class="passo">
            <div class="passo-numero">02</div>
            <div class="passo-icone" style="background:rgba(74,222,128,0.1);">🧠</div>
            <div class="passo-titulo">O FinBot analisa</div>
            <div class="passo-desc">A IA processa a tua questão com base em conhecimento financeiro actualizado e contextualizado para a tua situação.</div>
            <div class="passo-linha"></div>
        </div>
        <div class="passo">
            <div class="passo-numero">03</div>
            <div class="passo-icone" style="background:rgba(251,191,36,0.1);">✨</div>
            <div class="passo-titulo">Recebe orientação clara</div>
            <div class="passo-desc">Obtens uma resposta personalizada, com exemplos práticos e sugestões de próximos passos.</div>
        </div>
    </div>
</section>


<!-- ============================================================
     DEMO DE CHAT
============================================================ -->
<section class="secao-demo">
    <div class="demo-inner">
        <div class="demo-texto">
            <div class="demo-badge">
                <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
                Conversa em tempo real
            </div>
            <h2 class="demo-titulo">Respostas que realmente ajudam</h2>
            <p class="demo-desc">
                O FinBot não te dá respostas genéricas. Entende o teu contexto e responde de forma directa e útil, como um consultor financeiro acessível.
            </p>
            <ul class="demo-lista">
                <li>Explica conceitos complexos de forma simples</li>
                <li>Adapta-se ao teu nível de conhecimento</li>
                <li>Sugere recursos e próximos passos concretos</li>
                <li>Disponível 24 horas, 7 dias por semana</li>
            </ul>
        </div>

        <div class="chat-mockup">
            <div class="chat-mockup-header">
                <div class="chat-mockup-dot"></div>
                <span class="chat-mockup-nome">FinBot — Online</span>
            </div>
            <div class="chat-mockup-body" id="chat-demo">
                <div class="chat-msg chat-msg-bot" id="msg1">
                    <div class="chat-msg-avatar">🤖</div>
                    <div class="chat-msg-balao">Olá! Como posso ajudar-te hoje com as tuas finanças?</div>
                </div>
                <div class="chat-msg chat-msg-user" id="msg2">
                    <div class="chat-msg-avatar">👤</div>
                    <div class="chat-msg-balao">O que é um fundo de emergência e quanto devo ter?</div>
                </div>
                <div class="chat-msg chat-msg-bot" id="msg3">
                    <div class="chat-msg-avatar">🤖</div>
                    <div class="chat-msg-balao">Um fundo de emergência é uma reserva para imprevistos. O ideal é ter <strong style="color:var(--cor-acento)">3 a 6 meses</strong> de despesas guardados numa conta de fácil acesso. Quer que te explique como calcular o valor certo para o teu caso?</div>
                </div>
                <div class="chat-msg chat-msg-user" id="msg4">
                    <div class="chat-msg-avatar">👤</div>
                    <div class="chat-msg-balao">Sim, por favor!</div>
                </div>
            </div>
            <div class="chat-mockup-input">
                <div class="chat-mockup-input-field">Faz uma pergunta…</div>
                <button class="chat-mockup-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24"><path d="M22 2L11 13M22 2L15 22l-4-9-9-4 20-7z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================
     FUNCIONALIDADES
============================================================ -->
<section class="secao" id="funcionalidades">
    <div class="secao-cabecalho">
        <p class="secao-label">Funcionalidades</p>
        <h2 class="secao-titulo">Tudo o que precisas para evoluir</h2>
        <p class="secao-subtitulo">Uma plataforma completa para a tua educação financeira.</p>
    </div>

    <div class="funcionalidades">
        <div class="func-card">
            <div class="func-icone" style="background:rgba(108,143,255,0.12);">📊</div>
            <div class="func-texto">
                <div class="func-titulo">Análise de orçamento</div>
                <div class="func-desc">Descreve as tuas receitas e despesas e recebe sugestões personalizadas para optimizar as tuas finanças.</div>
            </div>
        </div>
        <div class="func-card">
            <div class="func-icone" style="background:rgba(74,222,128,0.1);">📈</div>
            <div class="func-texto">
                <div class="func-titulo">Guia de investimentos</div>
                <div class="func-desc">Aprende sobre acções, ETFs, fundos de investimento, criptomoedas e muito mais, com linguagem acessível.</div>
            </div>
        </div>
        <div class="func-card">
            <div class="func-icone" style="background:rgba(251,191,36,0.1);">🎯</div>
            <div class="func-texto">
                <div class="func-titulo">Objectivos financeiros</div>
                <div class="func-desc">Define metas como comprar casa, reformar cedo ou pagar dívidas, e recebe um plano de acção estruturado.</div>
            </div>
        </div>
        <div class="func-card">
            <div class="func-icone" style="background:rgba(96,165,250,0.1);">🔐</div>
            <div class="func-texto">
                <div class="func-titulo">Histórico de conversas</div>
                <div class="func-desc">Com conta criada, o teu histórico fica guardado para poderes retomar onde ficaste ou revisitar conselhos anteriores.</div>
            </div>
        </div>
        <div class="func-card">
            <div class="func-icone" style="background:rgba(167,139,250,0.1);">📚</div>
            <div class="func-texto">
                <div class="func-titulo">Base de conhecimento</div>
                <div class="func-desc">Respaldado por uma base de dados financeira constantemente actualizada com conceitos, estratégias e exemplos práticos.</div>
            </div>
        </div>
        <div class="func-card">
            <div class="func-icone" style="background:rgba(248,113,113,0.1);">⚡</div>
            <div class="func-texto">
                <div class="func-titulo">Respostas instantâneas</div>
                <div class="func-desc">Sem esperas. Recebe respostas detalhadas em segundos, disponível a qualquer hora do dia ou da noite.</div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================
     TÓPICOS INTERACTIVOS
============================================================ -->
<section class="secao-topicos" id="topicos">
    <div class="secao-cabecalho">
        <p class="secao-label">Explora tópicos</p>
        <h2 class="secao-titulo">O que queres aprender hoje?</h2>
        <p class="secao-subtitulo">Clica num tópico para começar uma conversa directamente sobre esse tema.</p>
    </div>

    <div class="topicos-grid">
        <button class="topico-chip" onclick="abrirTopico('O que são juros compostos e como funcionam?')">Juros compostos</button>
        <button class="topico-chip" onclick="abrirTopico('O que é Poupança e Xitique e como posso usá-los?')">Poupança/Xitique</button>
        <button class="topico-chip" onclick="abrirTopico('Como criar um orçamento familiar eficiente?')">Orçamento familiar</button>
        <button class="topico-chip" onclick="abrirTopico('Explica-me a regra 50/30/20 para gerir o meu dinheiro.')">Regra 50/30/20</button>
        <button class="topico-chip" onclick="abrirTopico('O que é a inflação e como me afecta?')">Inflação</button>
        <button class="topico-chip" onclick="abrirTopico('Como posso sair das dívidas de forma eficaz?')">Sair das dívidas</button>
        <button class="topico-chip" onclick="abrirTopico('O que são acções e como investir nelas?')">Acções</button>
        <button class="topico-chip" onclick="abrirTopico('Como calcular e planear uma reforma antecipada?')">Reforma antecipada</button>
        <button class="topico-chip" onclick="abrirTopico('O que é o score de crédito e como melhorá-lo?')">Score de crédito</button>
        <button class="topico-chip" onclick="abrirTopico('Como posso investir com pouco dinheiro?')"><span>💰</span> Investir com pouco</button>
        <button class="topico-chip" onclick="abrirTopico('O que é diversificação de investimentos e por que é importante?')"><span>🌐</span> Diversificação</button>
        <button class="topico-chip" onclick="abrirTopico('O que é um PPR e como funciona para a reforma?')"><span>🛡️</span> PPR / Reforma</button>
        <button class="topico-chip" onclick="abrirTopico('O que são criptomoedas e como investir com segurança?')"><span>🔐</span> Criptomoedas</button>
    </div>
</section>


<!-- ============================================================
     TESTEMUNHOS
============================================================ -->
<section class="secao-testemunhos">
    <div class="testemunhos-inner">
        <div class="secao-cabecalho">
            <p class="secao-label">Testemunhos</p>
            <h2 class="secao-titulo">O que dizem os utilizadores</h2>
            <p class="secao-subtitulo">Pessoas reais que melhoraram a sua relação com o dinheiro.</p>
        </div>
        <div class="testemunhos-grid">
            <div class="testemunho">
                <div class="testemunho-estrelas">★★★★★</div>
                <p class="testemunho-texto">"Finalmente percebi o que são juros compostos e como usá-los a meu favor. O FinBot explica tudo sem linguagem chata."</p>
                <div class="testemunho-autor">
                    <div class="testemunho-avatar">MA</div>
                    <div>
                        <div class="testemunho-nome">Maria A.</div>
                        <div class="testemunho-cargo">Professora</div>
                    </div>
                </div>
            </div>
            <div class="testemunho">
                <div class="testemunho-estrelas">★★★★★</div>
                <p class="testemunho-texto">"Consegui criar o meu primeiro orçamento a sério depois de perguntar ao FinBot como aplicar a regra 50/30/20 ao meu salário."</p>
                <div class="testemunho-autor">
                    <div class="testemunho-avatar">JC</div>
                    <div>
                        <div class="testemunho-nome">João C.</div>
                        <div class="testemunho-cargo">Engenheiro</div>
                    </div>
                </div>
            </div>
            <div class="testemunho">
                <div class="testemunho-estrelas">★★★★☆</div>
                <p class="testemunho-texto">"Uso quase todos os dias para tirar dúvidas sobre investimentos. É como ter um consultor financeiro sempre disponível."</p>
                <div class="testemunho-autor">
                    <div class="testemunho-avatar">SP</div>
                    <div>
                        <div class="testemunho-nome">Sofia P.</div>
                        <div class="testemunho-cargo">Empreendedora</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ============================================================
     CTA FINAL
============================================================ -->
<section class="secao-cta">
    <div class="cta-bg"><div class="cta-glow"></div></div>
    <h2 class="cta-titulo">Pronto para transformar as tuas finanças?</h2>
    <p class="cta-desc">Junta-te a milhares de pessoas que já usam o FinBot para tomar melhores decisões financeiras todos os dias.</p>
    <div class="cta-acoes">
        <?php if ($logado): ?>
            <a href="menu.php" class="btn-hero-primario">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Abrir o Chat
            </a>
        <?php else: ?>
            <a href="registo.php" class="btn-hero-primario">
                Criar conta gratuita
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="menu.php" class="btn-hero-secundario">Experimentar sem conta</a>
        <?php endif; ?>
    </div>
</section>


<!-- ============================================================
     RODAPÉ
============================================================ -->
<footer class="rodape">
    <div class="rodape-logo">
        <div class="nav-logo-icone" style="width:28px;height:28px;">
            <svg width="16" height="16" viewBox="0 0 28 28" fill="none">
                <circle cx="14" cy="14" r="13" stroke="var(--cor-acento)" stroke-width="1.5"/>
                <circle cx="14" cy="14" r="2.5" fill="var(--cor-acento)"/>
            </svg>
        </div>
        <span class="rodape-nome">FinBot</span>
    </div>
    <span class="rodape-copy">© <?= date('Y') ?> FinBot — Educação Financeira com IA</span>
    <div class="rodape-links">
        <a href="login.php" class="rodape-link">Entrar</a>
        <a href="registo.php" class="rodape-link">Registar</a>
        <a href="menu.php" class="rodape-link">Chat</a>
    </div>
</footer>


<!-- ============================================================
     JAVASCRIPT
============================================================ -->
<script>
(function() {

    /* ── Navbar scroll ── */
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });


    /* ── Smooth scroll para âncoras ── */
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const alvo = document.querySelector(a.getAttribute('href'));
            if (alvo) {
                e.preventDefault();
                alvo.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });


    /* ── Carrossel (drag + dots) ── */
    const pista   = document.getElementById('carrossel');
    const dots    = document.querySelectorAll('.carrossel-dot');
    let arrastando = false, startX = 0, scrollStart = 0;

    pista.addEventListener('mousedown', e => {
        arrastando = true;
        startX = e.pageX - pista.offsetLeft;
        scrollStart = pista.scrollLeft;
        pista.classList.add('arrastando');
    });
    window.addEventListener('mousemove', e => {
        if (!arrastando) return;
        const x = e.pageX - pista.offsetLeft;
        pista.scrollLeft = scrollStart - (x - startX);
    });
    window.addEventListener('mouseup', () => {
        arrastando = false;
        pista.classList.remove('arrastando');
    });

    /* Touch */
    pista.addEventListener('touchstart', e => {
        startX = e.touches[0].pageX;
        scrollStart = pista.scrollLeft;
    }, { passive: true });
    pista.addEventListener('touchmove', e => {
        const x = e.touches[0].pageX;
        pista.scrollLeft = scrollStart - (x - startX);
    }, { passive: true });

    /* Dots */
    pista.addEventListener('scroll', () => {
        const largura = pista.querySelector('.hero-card').offsetWidth + 20;
        const idx = Math.round(pista.scrollLeft / largura);
        dots.forEach((d, i) => d.classList.toggle('ativo', i === idx));
    }, { passive: true });

    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const largura = pista.querySelector('.hero-card').offsetWidth + 20;
            pista.scrollTo({ left: parseInt(dot.dataset.idx) * largura, behavior: 'smooth' });
        });
    });


    /* ── Contador animado ── */
    function animarContador(el) {
        const alvo = parseInt(el.dataset.alvo);
        const duracao = 1800;
        const inicio = performance.now();
        const step = ts => {
            const prog = Math.min((ts - inicio) / duracao, 1);
            const ease = 1 - Math.pow(1 - prog, 3);
            el.textContent = Math.floor(ease * alvo);
            if (prog < 1) requestAnimationFrame(step);
            else el.textContent = alvo;
        };
        requestAnimationFrame(step);
    }

    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                animarContador(e.target);
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.contador-animado').forEach(el => observer.observe(el));


    /* ── Demo de chat animado ── */
    const msgs = ['msg1','msg2','msg3','msg4'];
    const demoObserver = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) {
            msgs.forEach((id, i) => {
                setTimeout(() => {
                    const el = document.getElementById(id);
                    if (el) el.classList.add('visivel');
                }, i * 600);
            });
            demoObserver.disconnect();
        }
    }, { threshold: 0.3 });

    const demoEl = document.getElementById('chat-demo');
    if (demoEl) demoObserver.observe(demoEl);


    /* ── Fade-in ao scroll (passos, cards, testemunhos) ── */
    const fadeObserver = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.opacity = '1';
                e.target.style.transform = 'translateY(0)';
                fadeObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.passo, .func-card, .testemunho, .topico-chip').forEach((el, i) => {
        el.style.opacity    = '0';
        el.style.transform  = 'translateY(24px)';
        el.style.transition = `opacity 0.5s ease ${i * 0.06}s, transform 0.5s ease ${i * 0.06}s`;
        fadeObserver.observe(el);
    });


})();

        /* ── Tópicos: guardar no localStorage e ir para o chat ── */
function abrirTopico(pergunta) {
    window.location.href = 'menu.php?topico=' + encodeURIComponent(pergunta);
}

</script>

</body>
</html>