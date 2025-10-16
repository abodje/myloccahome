<?php

namespace App\Controller\Admin;

use App\Entity\AuditLog;
use App\Repository\AuditLogRepository;
use App\Service\AuditLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/audit')]
class AuditLogController extends AbstractController
{
    #[Route('/', name: 'app_admin_audit_index', methods: ['GET'])]
    public function index(Request $request, AuditLogRepository $repository): Response
    {
        // Récupérer les filtres
        $action = $request->query->get('action');
        $entityType = $request->query->get('entity_type');
        $startDate = $request->query->get('start_date')
            ? new \DateTime($request->query->get('start_date'))
            : null;
        $endDate = $request->query->get('end_date')
            ? new \DateTime($request->query->get('end_date'))
            : null;
        $limit = (int) $request->query->get('limit', 100);

        // Appliquer les filtres
        $logs = $repository->findWithFilters(
            user: null,
            action: $action,
            entityType: $entityType,
            startDate: $startDate,
            endDate: $endDate,
            limit: $limit
        );

        // Stats
        $stats = [
            'total' => $repository->countAll(),
            'today' => count($repository->findToday()),
        ];

        // Listes pour les filtres
        $actions = ['CREATE', 'UPDATE', 'DELETE', 'VIEW', 'LOGIN', 'LOGOUT', 'DOWNLOAD', 'EXPORT', 'SEND_EMAIL', 'SEND_SMS'];
        $entityTypes = ['Property', 'Tenant', 'Lease', 'Payment', 'MaintenanceRequest', 'Document', 'User', 'Expense', 'Currency'];

        return $this->render('admin/audit/index.html.twig', [
            'logs' => $logs,
            'stats' => $stats,
            'actions' => $actions,
            'entity_types' => $entityTypes,
            'current_filters' => [
                'action' => $action,
                'entity_type' => $entityType,
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
                'limit' => $limit
            ]
        ]);
    }

    #[Route('/{id}', name: 'app_admin_audit_show', methods: ['GET'])]
    public function show(AuditLog $auditLog): Response
    {
        return $this->render('admin/audit/show.html.twig', [
            'log' => $auditLog,
        ]);
    }

    #[Route('/entity/{entityType}/{entityId}', name: 'app_admin_audit_entity', methods: ['GET'])]
    public function entityHistory(string $entityType, int $entityId, AuditLogRepository $repository): Response
    {
        $logs = $repository->findByEntity($entityType, $entityId);

        return $this->render('admin/audit/entity_history.html.twig', [
            'logs' => $logs,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    #[Route('/statistiques', name: 'app_admin_audit_stats', methods: ['GET'])]
    public function statistics(AuditLogService $auditLogService, AuditLogRepository $repository): Response
    {
        $startDate = new \DateTime('-30 days');
        $endDate = new \DateTime();

        $stats = $auditLogService->getActivityStats($startDate, $endDate);

        // Activité par jour (derniers 30 jours)
        $dailyActivity = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-{$i} days");
            $dayStart = clone $date;
            $dayStart->setTime(0, 0, 0);
            $dayEnd = clone $date;
            $dayEnd->setTime(23, 59, 59);

            $count = $repository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.createdAt BETWEEN :start AND :end')
                ->setParameter('start', $dayStart)
                ->setParameter('end', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $dailyActivity[] = [
                'date' => $date->format('d/m'),
                'count' => $count
            ];
        }

        return $this->render('admin/audit/statistics.html.twig', [
            'stats' => $stats,
            'daily_activity' => $dailyActivity,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    #[Route('/nettoyage', name: 'app_admin_audit_cleanup', methods: ['POST'])]
    public function cleanup(Request $request, AuditLogService $auditLogService): Response
    {
        $days = (int) $request->request->get('days', 90);

        if ($days < 30) {
            $this->addFlash('error', 'La période minimum est de 30 jours.');
            return $this->redirectToRoute('app_admin_audit_stats');
        }

        try {
            $deleted = $auditLogService->cleanOldLogs($days);
            $this->addFlash('success', "{$deleted} enregistrement(s) supprimé(s).");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du nettoyage : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_audit_stats');
    }
}

