import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// Configuration Turbo pour éviter les problèmes de cache
import { Turbo } from '@hotwired/turbo';

// Configuration Turbo pour forcer le rafraîchissement des données
Turbo.session.drive = true;

// Désactiver le cache pour les requêtes AJAX
Turbo.session.cache = false;

// Forcer le rechargement des pages après certaines actions
document.addEventListener('turbo:before-fetch-request', (event) => {
    // Ajouter un timestamp pour éviter le cache
    const url = new URL(event.detail.url);
    url.searchParams.set('_t', Date.now());
    event.detail.url = url.toString();
});

// Rafraîchir automatiquement après les actions de modification
document.addEventListener('turbo:before-fetch-response', (event) => {
    const response = event.detail.fetchResponse;
    if (response.succeeded && response.statusCode === 200) {
        // Vérifier si c'est une action de modification (POST, PUT, DELETE)
        const method = event.detail.fetchOptions?.method;
        if (method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            // Forcer le rechargement de la page après 500ms
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    }
});

// Gérer les erreurs de cache
document.addEventListener('turbo:fetch-request-error', (event) => {
    console.warn('Erreur de requête Turbo:', event.detail);
    // Recharger la page en cas d'erreur
    window.location.reload();
});

// Désactiver Turbo pour certains formulaires qui nécessitent un rechargement complet
document.addEventListener('DOMContentLoaded', () => {
    // Désactiver Turbo pour les formulaires de modification critiques
    const criticalForms = document.querySelectorAll('form[data-turbo="false"]');
    criticalForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            // Forcer le rechargement après soumission
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    });
});

console.log('Configuration Turbo optimisée chargée ! 🚀');
