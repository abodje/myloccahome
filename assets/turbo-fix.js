// Script simple pour résoudre les problèmes Turbo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Configuration Turbo chargée...');

    // Désactiver Turbo pour tous les formulaires critiques
    const criticalForms = document.querySelectorAll('form');
    criticalForms.forEach(form => {
        const action = form.action || '';
        const method = form.method || 'GET';

        // Désactiver Turbo pour les formulaires POST et les actions critiques
        if (method === 'POST' ||
            action.includes('delete') ||
            action.includes('edit') ||
            action.includes('create') ||
            action.includes('update')) {
            form.setAttribute('data-turbo', 'false');
            console.log('📝 Formulaire critique désactivé:', action);
        }
    });

    // Désactiver Turbo pour les boutons critiques
    const criticalButtons = document.querySelectorAll('button[type="submit"], .btn-danger, .btn-warning, .btn-primary');
    criticalButtons.forEach(button => {
        button.setAttribute('data-turbo', 'false');
        console.log('🔘 Bouton critique désactivé:', button.textContent.trim());
    });

    // Désactiver Turbo pour les liens critiques
    const criticalLinks = document.querySelectorAll('a[href*="delete"], a[href*="edit"], a[href*="create"]');
    criticalLinks.forEach(link => {
        link.setAttribute('data-turbo', 'false');
        console.log('🔗 Lien critique désactivé:', link.href);
    });

    // Forcer le rechargement après soumission de formulaire
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.getAttribute('data-turbo') === 'false') {
            console.log('🔄 Rechargement forcé après soumission...');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });

    // Forcer le rechargement après clic sur bouton critique
    document.addEventListener('click', function(e) {
        const target = e.target.closest('button, a');
        if (target && target.getAttribute('data-turbo') === 'false') {
            console.log('🔄 Rechargement forcé après clic...');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    });

    console.log('✅ Configuration Turbo terminée !');
});
