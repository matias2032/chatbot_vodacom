// ============================================================
//  SIDEBAR.JS — Toggle da barra lateral (hamburger)
//  Reutilizável em todas as páginas com .barra-lateral
// ============================================================

(function () {
    const sidebar = document.querySelector('.barra-lateral');
    if (!sidebar) return;

    // ── Criar botão hamburger ────────────────────────────────
    const btn = document.createElement('button');
    btn.id    = 'btn-hamburger';
    btn.title = 'Menu';
    btn.innerHTML = `
        <svg id="ico-menu" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <svg id="ico-fechar" width="20" height="20" viewBox="0 0 20 20" fill="none" style="display:none">
            <path d="M4 4l12 12M16 4L4 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
    `;

    // ── Injectar sempre no body — posição controlada por CSS ─
    document.body.appendChild(btn);

    // ── Overlay (mobile) ────────────────────────────────────
    const overlay = document.createElement('div');
    overlay.id = 'sidebar-overlay';
    document.body.appendChild(overlay);

    // ── Lógica de estado ─────────────────────────────────────
    function isMobile() {
        return window.innerWidth <= 768;
    }

    function estaAberta() {
        return isMobile()
            ? sidebar.classList.contains('sidebar-aberta')
            : !sidebar.classList.contains('sidebar-aberta');
    }

    function abrirSidebar() {
        if (isMobile()) {
            sidebar.classList.add('sidebar-aberta');
            overlay.classList.add('overlay-visivel');
        } else {
            sidebar.classList.remove('sidebar-aberta');
        }
        document.getElementById('ico-menu').style.display   = 'none';
        document.getElementById('ico-fechar').style.display = 'block';
    }

    function fecharSidebar() {
        if (isMobile()) {
            sidebar.classList.remove('sidebar-aberta');
            overlay.classList.remove('overlay-visivel');
        } else {
            sidebar.classList.add('sidebar-aberta');
        }
        document.getElementById('ico-menu').style.display   = 'block';
        document.getElementById('ico-fechar').style.display = 'none';
    }

    function toggleSidebar() {
        estaAberta() ? fecharSidebar() : abrirSidebar();
    }

    btn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', fecharSidebar);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') fecharSidebar();
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            overlay.classList.remove('overlay-visivel');
            sidebar.classList.remove('sidebar-aberta');
            document.getElementById('ico-menu').style.display   = 'block';
            document.getElementById('ico-fechar').style.display = 'none';
        }
    });
})();