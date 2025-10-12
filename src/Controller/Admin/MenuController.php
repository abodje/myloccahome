<?php

namespace App\Controller\Admin;

use App\Entity\MenuItem;
use App\Repository\MenuItemRepository;
use App\Service\MenuService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/menus')]
class MenuController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MenuItemRepository $menuRepository,
        private MenuService $menuService
    ) {
    }

    #[Route('', name: 'app_admin_menu_index', methods: ['GET'])]
    public function index(): Response
    {
        $menus = $this->menuRepository->findRootMenus();

        return $this->render('admin/menu/index.html.twig', [
            'menus' => $menus,
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $menuItem = new MenuItem();
            $menuItem->setLabel($request->request->get('label'));
            $menuItem->setMenuKey($request->request->get('menu_key'));
            $menuItem->setIcon($request->request->get('icon'));
            $menuItem->setRoute($request->request->get('route'));
            $menuItem->setRoles($request->request->all('roles') ?? []);
            $menuItem->setDisplayOrder((int) $request->request->get('display_order', 0));
            $menuItem->setType($request->request->get('type', 'menu'));
            $menuItem->setBadgeType($request->request->get('badge_type'));
            $menuItem->setDescription($request->request->get('description'));

            // Parent
            $parentId = $request->request->get('parent');
            if ($parentId) {
                $parent = $this->menuRepository->find($parentId);
                if ($parent) {
                    $menuItem->setParent($parent);
                }
            }

            $this->entityManager->persist($menuItem);
            $this->entityManager->flush();

            $this->addFlash('success', 'Menu créé avec succès !');
            return $this->redirectToRoute('app_admin_menu_index');
        }

        $parentMenus = $this->menuRepository->findRootMenus();

        return $this->render('admin/menu/new.html.twig', [
            'parent_menus' => $parentMenus,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_menu_edit', methods: ['GET', 'POST'])]
    public function edit(MenuItem $menuItem, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $menuItem->setLabel($request->request->get('label'));
            $menuItem->setMenuKey($request->request->get('menu_key'));
            $menuItem->setIcon($request->request->get('icon'));
            $menuItem->setRoute($request->request->get('route'));
            $menuItem->setRoles($request->request->all('roles') ?? []);
            $menuItem->setDisplayOrder((int) $request->request->get('display_order', 0));
            $menuItem->setType($request->request->get('type', 'menu'));
            $menuItem->setBadgeType($request->request->get('badge_type'));
            $menuItem->setDescription($request->request->get('description'));
            $menuItem->setUpdatedAt(new \DateTime());

            // Parent
            $parentId = $request->request->get('parent');
            if ($parentId) {
                $parent = $this->menuRepository->find($parentId);
                $menuItem->setParent($parent);
            } else {
                $menuItem->setParent(null);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Menu modifié avec succès !');
            return $this->redirectToRoute('app_admin_menu_index');
        }

        $parentMenus = $this->menuRepository->findRootMenus();

        return $this->render('admin/menu/edit.html.twig', [
            'menu' => $menuItem,
            'parent_menus' => $parentMenus,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_admin_menu_toggle', methods: ['POST'])]
    public function toggle(MenuItem $menuItem): Response
    {
        $menuItem->setActive(!$menuItem->isActive());
        $this->entityManager->flush();

        $this->addFlash('success', 'Statut du menu modifié !');
        return $this->redirectToRoute('app_admin_menu_index');
    }

    #[Route('/{id}/supprimer', name: 'app_admin_menu_delete', methods: ['POST'])]
    public function delete(MenuItem $menuItem): Response
    {
        $this->entityManager->remove($menuItem);
        $this->entityManager->flush();

        $this->addFlash('success', 'Menu supprimé !');
        return $this->redirectToRoute('app_admin_menu_index');
    }

    #[Route('/synchroniser', name: 'app_admin_menu_sync', methods: ['POST'])]
    public function syncFromCode(): Response
    {
        $codeMenus = $this->menuService->getMenuStructure();
        $synced = 0;

        foreach ($codeMenus as $key => $menuData) {
            $menuItem = $this->menuRepository->findByKey($key);

            if (!$menuItem) {
                $menuItem = new MenuItem();
                $menuItem->setMenuKey($key);
                $synced++;
            }

            $menuItem->setLabel($menuData['label'] ?? '');
            $menuItem->setIcon($menuData['icon'] ?? null);
            $menuItem->setRoute($menuData['route'] ?? null);
            $menuItem->setRoles($menuData['roles'] ?? []);
            $menuItem->setDisplayOrder($menuData['order'] ?? 0);
            $menuItem->setType($menuData['type'] ?? 'menu');
            $menuItem->setBadgeType($menuData['badge'] ?? null);

            $this->entityManager->persist($menuItem);

            // Gérer les sous-menus
            if (isset($menuData['submenu'])) {
                foreach ($menuData['submenu'] as $subKey => $subData) {
                    $subMenuItem = $this->menuRepository->findByKey($subKey);

                    if (!$subMenuItem) {
                        $subMenuItem = new MenuItem();
                        $subMenuItem->setMenuKey($subKey);
                        $synced++;
                    }

                    $subMenuItem->setLabel($subData['label'] ?? '');
                    $subMenuItem->setRoute($subData['route'] ?? null);
                    $subMenuItem->setRoles($subData['roles'] ?? []);
                    $subMenuItem->setParent($menuItem);

                    $this->entityManager->persist($subMenuItem);
                }
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', "{$synced} menu(s) synchronisé(s) depuis le code !");
        return $this->redirectToRoute('app_admin_menu_index');
    }
}

