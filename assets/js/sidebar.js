document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleSidebarBtn = document.querySelector('.toggle-sidebar-btn');
    const body = document.querySelector('body');

    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function(e) {
            body.classList.toggle('toggle-sidebar');
            // Sauvegarder l'état dans localStorage
            localStorage.setItem('sidebar-state', body.classList.contains('toggle-sidebar') ? 'closed' : 'open');
        });
    }

    // Restaurer l'état du sidebar au chargement
    const sidebarState = localStorage.getItem('sidebar-state');
    if (sidebarState === 'closed') {
        body.classList.add('toggle-sidebar');
    }

    // Gestion des sous-menus
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('data-bs-toggle') === 'collapse') {
            link.addEventListener('click', function(e) {
                if (!link.classList.contains('collapsed')) {
                    localStorage.setItem('active-menu', link.getAttribute('data-bs-target'));
                } else {
                    localStorage.removeItem('active-menu');
                }
            });
        }
    });

    // Restaurer le menu actif
    const activeMenu = localStorage.getItem('active-menu');
    if (activeMenu) {
        const activeLink = document.querySelector(`[data-bs-target="${activeMenu}"]`);
        if (activeLink && activeLink.classList.contains('collapsed')) {
            activeLink.click();
        }
    }
});
