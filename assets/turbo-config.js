// Configuration Turbo avancée pour éviter les problèmes de cache
import { Turbo } from '@hotwired/turbo';

// Configuration globale Turbo
Turbo.session.drive = true;
Turbo.session.cache = false;

// Middleware pour ajouter des headers anti-cache
document.addEventListener('turbo:before-fetch-request', (event) => {
    const url = new URL(event.detail.url);

    // Ajouter un timestamp pour éviter le cache
    url.searchParams.set('_t', Date.now());
    event.detail.url = url.toString();

    // Ajouter des headers anti-cache
    event.detail.fetchOptions = event.detail.fetchOptions || {};
    event.detail.fetchOptions.headers = {
        ...event.detail.fetchOptions.headers,
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
    };
});

// Gérer les réponses pour forcer le rechargement après certaines actions
document.addEventListener('turbo:before-fetch-response', (event) => {
    const response = event.detail.fetchResponse;
    const method = event.detail.fetchOptions?.method;

    if (response.succeeded && response.statusCode === 200) {
        // Vérifier si c'est une action de modification
        if (method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            // Vérifier si la réponse contient un indicateur de succès
            const contentType = response.response.headers.get('content-type');
            if (contentType && contentType.includes('text/html')) {
                // Attendre un peu puis recharger
                setTimeout(() => {
                    window.location.reload();
                }, 300);
            }
        }
    }
});

// Gérer les erreurs de cache
document.addEventListener('turbo:fetch-request-error', (event) => {
    console.warn('Erreur de requête Turbo:', event.detail);

    // Si c'est une erreur de cache, recharger la page
    if (event.detail.error && event.detail.error.message.includes('cache')) {
        window.location.reload();
    }
});

// Fonction utilitaire pour forcer le rechargement
window.forceReload = function() {
    window.location.reload();
};

// Fonction utilitaire pour recharger via Turbo
window.turboReload = function(url = null) {
    if (url) {
        fetch('/turbo/reload/' + encodeURIComponent(url), {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
    } else {
        fetch('/turbo/refresh', {
            method: 'POST',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
    }
};

// Désactiver Turbo pour certains formulaires critiques
document.addEventListener('DOMContentLoaded', () => {
    // Désactiver Turbo pour les formulaires de modification critiques
    const criticalForms = document.querySelectorAll('form[data-turbo="false"], form.critical-form');
    criticalForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            // Forcer le rechargement après soumission
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    });

    // Ajouter des attributs data-turbo="false" aux liens critiques
    const criticalLinks = document.querySelectorAll('a.critical-link, a[href*="delete"], a[href*="edit"]');
    criticalLinks.forEach(link => {
        link.setAttribute('data-turbo', 'false');
    });
});

// Intercepter les clics sur les boutons de suppression/modification
document.addEventListener('click', (event) => {
    const target = event.target.closest('button, a');
    if (target) {
        const classes = target.className;
        const href = target.href || '';

        // Si c'est un bouton/lien critique, désactiver Turbo
        if (classes.includes('btn-danger') ||
            classes.includes('btn-warning') ||
            classes.includes('btn-primary') ||
            href.includes('delete') ||
            href.includes('edit') ||
            href.includes('create')) {
            target.setAttribute('data-turbo', 'false');
        }
    }
});

console.log('Configuration Turbo avancée chargée ! 🚀');
