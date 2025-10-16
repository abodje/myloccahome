<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TurboController extends AbstractController
{
    /**
     * Middleware pour gérer les problèmes de cache avec Turbo
     */
    #[Route('/turbo/refresh', name: 'app_turbo_refresh', methods: ['POST'])]
    public function refresh(Request $request): Response
    {
        // Forcer le rechargement de la page
        $response = new Response();
        $response->headers->set('Turbo-Location', $request->headers->get('referer', '/'));
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Forcer le rechargement d'une page spécifique
     */
    #[Route('/turbo/reload/{url}', name: 'app_turbo_reload', methods: ['GET'])]
    public function reload(string $url): Response
    {
        $response = new Response();
        $response->headers->set('Turbo-Location', urldecode($url));
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
