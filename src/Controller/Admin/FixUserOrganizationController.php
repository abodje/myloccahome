<?php

namespace App\Controller\Admin;

use App\Service\TaskManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/fix-user-organization')]
class FixUserOrganizationController extends AbstractController
{
    public function __construct(
        private TaskManagerService $taskManagerService
    ) {
    }

    /**
     * Page principale pour la correction des utilisateurs sans organisation
     */
    #[Route('/', name: 'app_admin_fix_user_organization', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/fix_user_organization/index.html.twig');
    }

    /**
     * Exécute la correction des utilisateurs sans organisation
     */
    #[Route('/execute', name: 'app_admin_fix_user_organization_execute', methods: ['POST'])]
    public function execute(Request $request): JsonResponse
    {
        try {
            // Créer et exécuter la tâche
            $task = $this->taskManagerService->createFixUserOrganizationTask();
            $this->taskManagerService->executeTask($task);

            return new JsonResponse([
                'success' => true,
                'message' => 'Correction des utilisateurs sans organisation exécutée avec succès',
                'task_result' => $task->getResult()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la correction : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtient les statistiques des utilisateurs
     */
    #[Route('/stats', name: 'app_admin_fix_user_organization_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        try {
            $entityManager = $this->taskManagerService->getEntityManager();

            // Statistiques générales
            $totalUsers = $entityManager->getRepository(\App\Entity\User::class)->count([]);

            $usersWithOrg = $entityManager->getRepository(\App\Entity\User::class)
                ->createQueryBuilder('u')
                ->where('u.organization IS NOT NULL')
                ->getQuery()
                ->getSingleScalarResult();

            $usersWithoutOrg = $totalUsers - $usersWithOrg;

            // Détails des utilisateurs sans organisation
            $usersWithoutOrgDetails = $entityManager->getRepository(\App\Entity\User::class)
                ->createQueryBuilder('u')
                ->where('u.organization IS NULL')
                ->getQuery()
                ->getResult();

            $usersDetails = [];
            foreach ($usersWithoutOrgDetails as $user) {
                $usersDetails[] = [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'roles' => $user->getRoles(),
                    'hasTenant' => $user->getTenant() !== null,
                    'tenantOrganization' => $user->getTenant() ? ($user->getTenant()->getOrganization() ? $user->getTenant()->getOrganization()->getName() : null) : null,
                ];
            }

            return new JsonResponse([
                'success' => true,
                'stats' => [
                    'total_users' => $totalUsers,
                    'users_with_organization' => $usersWithOrg,
                    'users_without_organization' => $usersWithoutOrg,
                    'percentage_with_org' => $totalUsers > 0 ? round(($usersWithOrg / $totalUsers) * 100, 2) : 0,
                ],
                'users_without_org_details' => $usersDetails
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques : ' . $e->getMessage()
            ], 500);
        }
    }
}
