document.addEventListener('DOMContentLoaded', function() {
    // Gestion du menu déroulant du profil
    const profileDropdown = document.querySelector('.nav-link.nav-profile');
    const dropdownMenu = document.querySelector('.dropdown-menu.dropdown-menu-arrow.profile');
    
    if (profileDropdown && dropdownMenu) {
        // Ouvrir/fermer le menu au clic
        profileDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.classList.toggle('show');
            profileDropdown.setAttribute('aria-expanded', 
                profileDropdown.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
            );
        });

        // Fermer le menu si on clique en dehors
        document.addEventListener('click', function(e) {
            if (!profileDropdown.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                profileDropdown.setAttribute('aria-expanded', 'false');
            }
        });

        // Gestion du hover sur les éléments du menu
        const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f6f9ff';
            });
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }
});
