<?php

namespace App\Controller\Admin;

use App\Service\BackupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/sauvegardes')]
class BackupController extends AbstractController
{
    #[Route('/', name: 'app_admin_backup_index', methods: ['GET'])]
    public function index(BackupService $backupService): Response
    {
        $backups = $backupService->listBackups();
        $stats = $backupService->getBackupStatistics();

        return $this->render('admin/backup/index.html.twig', [
            'backups' => $backups,
            'stats' => $stats,
        ]);
    }

    #[Route('/creer', name: 'app_admin_backup_create', methods: ['POST'])]
    public function create(BackupService $backupService): Response
    {
        try {
            $results = $backupService->createFullBackup();

            if ($results['success']) {
                $this->addFlash('success', sprintf(
                    '✅ Sauvegarde créée avec succès : %s',
                    $results['timestamp']
                ));
            } else {
                $errors = implode(', ', $results['errors']);
                $this->addFlash('error', "Erreurs lors de la sauvegarde : {$errors}");
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la création de la sauvegarde : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_backup_index');
    }

    #[Route('/telecharger/{filename}', name: 'app_admin_backup_download', methods: ['GET'])]
    public function download(string $filename, BackupService $backupService): Response
    {
        $filePath = $backupService->getBackupFile($filename);

        if (!$filePath || !file_exists($filePath)) {
            $this->addFlash('error', 'Fichier de sauvegarde introuvable.');
            return $this->redirectToRoute('app_admin_backup_index');
        }

        // Télécharger le fichier
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        return $response;
    }

    #[Route('/supprimer/{timestamp}', name: 'app_admin_backup_delete', methods: ['POST'])]
    public function delete(string $timestamp, Request $request, BackupService $backupService): Response
    {
        if ($this->isCsrfTokenValid('delete'.$timestamp, $request->request->get('_token'))) {
            try {
                $deleted = $backupService->deleteBackup($timestamp);

                if ($deleted) {
                    $this->addFlash('success', 'Sauvegarde supprimée avec succès.');
                } else {
                    $this->addFlash('error', 'Aucun fichier supprimé.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_admin_backup_index');
    }

    #[Route('/nettoyer', name: 'app_admin_backup_cleanup', methods: ['POST'])]
    public function cleanup(Request $request, BackupService $backupService): Response
    {
        $days = (int) $request->request->get('days', 30);

        if ($days < 7) {
            $this->addFlash('error', 'La période minimum est de 7 jours.');
            return $this->redirectToRoute('app_admin_backup_index');
        }

        try {
            $deleted = $backupService->cleanOldBackups($days);
            $this->addFlash('success', "{$deleted} fichier(s) de sauvegarde supprimé(s).");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du nettoyage : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_backup_index');
    }
}

