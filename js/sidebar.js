// ============================================================
//  SIDEBAR.JS — Toggle da barra lateral (hamburger)
//  Reutilizável em todas as páginas com .barra-lateral
// ============================================================

(function () {
    // Cria o botão hamburger dinamicamente
    const btn = document.createElement('button');
    btn.id        = 'btn-hamburger';
    btn.title     = 'Menu';
    btn.innerHTML = `
        <svg id="ico-menu" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
        <svg id="ico-fechar" width="20" height="20" viewBox="0 0 20 20" fill="none" style="display:none">
            <path d="M4 4l12 12M16 4L4 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </svg>
    `;

    // Injeta o botão como primeiro filho do cabecalho
    const cabecalho = document.querySelector('.cabecalho-chat');
    if (cabecalho) {
        cabecalho.insertBefore(btn, cabecalho.firstChild);
    }

    // Cria o overlay para fechar ao clicar fora (mobile)
    const overlay = document.createElement('div');
    overlay.id = 'sidebar-overlay';
    document.body.appendChild(overlay);

    const sidebar = document.querySelector('.barra-lateral');

    function abrirSidebar() {
        sidebar.classList.add('sidebar-aberta');
        overlay.classList.add('overlay-visivel');
        document.getElementById('ico-menu').style.display   = 'none';
        document.getElementById('ico-fechar').style.display = 'block';
    }

    function fecharSidebar() {
        sidebar.classList.remove('sidebar-aberta');
        overlay.classList.remove('overlay-visivel');
        document.getElementById('ico-menu').style.display   = 'block';
        document.getElementById('ico-fechar').style.display = 'none';
    }

    function toggleSidebar() {
        sidebar.classList.contains('sidebar-aberta') ? fecharSidebar() : abrirSidebar();
    }

    btn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', fecharSidebar);

    // Fechar com ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fecharSidebar();
    });
})();