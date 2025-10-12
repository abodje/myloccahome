<?php

namespace App\Twig;

use App\Service\MenuService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig pour les menus avec ACL
 */
class MenuExtension extends AbstractExtension
{
    public function __construct(
        private MenuService $menuService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_menu', [$this, 'getMenu']),
            new TwigFunction('can_access_route', [$this, 'canAccessRoute']),
            new TwigFunction('user_permissions', [$this, 'getUserPermissions']),
        ];
    }

    /**
     * Retourne le menu autorisé pour l'utilisateur connecté
     */
    public function getMenu(): array
    {
        return $this->menuService->getAuthorizedMenu();
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une route
     */
    public function canAccessRoute(string $route): bool
    {
        return $this->menuService->canAccessRoute($route);
    }

    /**
     * Retourne les permissions de l'utilisateur
     */
    public function getUserPermissions(): array
    {
        return $this->menuService->getUserPermissions();
    }
}

