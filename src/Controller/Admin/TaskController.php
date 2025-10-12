<?php

namespace App\Controller\Admin;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Service\TaskManagerService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/taches')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'app_admin_task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findBy([], ['createdAt' => 'DESC']);
        $stats = $taskRepository->getStatistics();
        $dueTasks = $taskRepository->findDueTasks();
        $recentFailures = $taskRepository->findRecentFailures();

        return $this->render('admin/task/index.html.twig', [
            'tasks' => $tasks,
            'stats' => $stats,
            'due_tasks' => $dueTasks,
            'recent_failures' => $recentFailures,
        ]);
    }

    #[Route('/nouvelle', name: 'app_admin_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->calculateNextRun();
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a été créée avec succès.');
            return $this->redirectToRoute('app_admin_task_index');
        }

        return $this->render('admin/task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        return $this->render('admin/task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/executer', name: 'app_admin_task_execute', methods: ['POST'])]
    public function execute(Task $task, TaskManagerService $taskManager): Response
    {
        try {
            $taskManager->forceExecuteTask($task);
            $this->addFlash('success', "La tâche '{$task->getName()}' a été exécutée avec succès.");
        } catch (\Exception $e) {
            $this->addFlash('error', "Erreur lors de l'exécution: " . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_task_show', ['id' => $task->getId()]);
    }

    #[Route('/{id}/toggle', name: 'app_admin_task_toggle', methods: ['POST'])]
    public function toggle(Task $task, TaskManagerService $taskManager): Response
    {
        $newStatus = !$task->isActive();
        $taskManager->toggleTask($task, $newStatus);

        $statusText = $newStatus ? 'activée' : 'désactivée';
        $this->addFlash('success', "La tâche '{$task->getName()}' a été {$statusText}.");

        return $this->redirectToRoute('app_admin_task_index');
    }

    #[Route('/executer-toutes', name: 'app_admin_task_run_all', methods: ['POST'])]
    public function runAll(TaskManagerService $taskManager): Response
    {
        try {
            $results = $taskManager->runDueTasks();

            if ($results['executed'] > 0) {
                $this->addFlash('success', "{$results['executed']} tâche(s) exécutée(s) avec succès.");
            }

            if ($results['failed'] > 0) {
                $this->addFlash('warning', "{$results['failed']} tâche(s) ont échoué.");
            }

            if ($results['executed'] === 0 && $results['failed'] === 0) {
                $this->addFlash('info', 'Aucune tâche à exécuter pour le moment.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'exécution des tâches: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_task_index');
    }

    #[Route('/initialiser', name: 'app_admin_task_initialize', methods: ['POST'])]
    public function initialize(TaskManagerService $taskManager): Response
    {
        try {
            $taskManager->createDefaultTasks();
            $this->addFlash('success', 'Les tâches par défaut ont été créées avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'initialisation: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_task_index');
    }

    #[Route('/test-email', name: 'app_admin_task_test_email', methods: ['POST'])]
    public function testEmail(Request $request, NotificationService $notificationService): Response
    {
        $testEmail = $request->request->get('test_email');

        if (!$testEmail) {
            $this->addFlash('error', 'Veuillez fournir une adresse email de test.');
            return $this->redirectToRoute('app_admin_task_index');
        }

        try {
            $success = $notificationService->testEmailConfiguration($testEmail);

            if ($success) {
                $this->addFlash('success', "Email de test envoyé avec succès à {$testEmail}.");
            } else {
                $this->addFlash('error', 'Échec de l\'envoi de l\'email de test. Vérifiez la configuration SMTP.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du test: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_task_index');
    }

    #[Route('/envoyer-quittances', name: 'app_admin_task_send_receipts', methods: ['POST'])]
    public function sendReceipts(Request $request, NotificationService $notificationService): Response
    {
        $month = $request->request->get('month');

        try {
            $forMonth = $month ? new \DateTime($month . '-01') : new \DateTime('first day of last month');
            $results = $notificationService->sendRentReceipts($forMonth);

            if ($results['sent'] > 0) {
                $this->addFlash('success', "{$results['sent']} quittance(s) envoyée(s) pour " . $forMonth->format('F Y'));
            }

            if ($results['failed'] > 0) {
                $this->addFlash('warning', "{$results['failed']} envoi(s) ont échoué.");
            }

            if ($results['sent'] === 0 && $results['failed'] === 0) {
                $this->addFlash('info', 'Aucune quittance à envoyer pour ce mois.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_task_index');
    }
}
