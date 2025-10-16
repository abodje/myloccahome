<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TurboCacheService
{
    /**
     * Ajoute des headers anti-cache à une réponse
     */
    public function addNoCacheHeaders(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('ETag', md5(time()));

        return $response;
    }

    /**
     * Force le rechargement d'une page via Turbo
     */
    public function forceTurboReload(Response $response, string $url = null): Response
    {
        $this->addNoCacheHeaders($response);

        if ($url) {
            $response->headers->set('Turbo-Location', $url);
        } else {
            $response->headers->set('Turbo-Location', '/');
        }

        return $response;
    }

    /**
     * Désactive Turbo pour une réponse spécifique
     */
    public function disableTurbo(Response $response): Response
    {
        $this->addNoCacheHeaders($response);
        $response->headers->set('Turbo-Frame', 'false');

        return $response;
    }

    /**
     * Ajoute un timestamp pour éviter le cache
     */
    public function addCacheBuster(string $url): string
    {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . '_t=' . time();
    }

    /**
     * Vérifie si une requête nécessite un rechargement complet
     */
    public function shouldForceReload(Request $request): bool
    {
        // Vérifier les méthodes qui modifient les données
        $modifyingMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (in_array($request->getMethod(), $modifyingMethods)) {
            return true;
        }

        // Vérifier les paramètres spéciaux
        if ($request->query->has('force_reload') || $request->query->has('refresh')) {
            return true;
        }

        // Vérifier les headers Turbo
        if ($request->headers->get('Turbo-Frame') === 'false') {
            return true;
        }

        return false;
    }

    /**
     * Génère une réponse avec rechargement forcé
     */
    public function createReloadResponse(string $url = null): Response
    {
        $response = new Response();
        $this->forceTurboReload($response, $url);

        return $response;
    }
}
