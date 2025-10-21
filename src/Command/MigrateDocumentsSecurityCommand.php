<?php

namespace App\Command;

use App\Service\SecureFileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:migrate-documents-security',
    description: 'Migre les documents existants vers le système de sécurité',
)]
class MigrateDocumentsSecurityCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SecureFileService $secureFileService,
        private SluggerInterface $slugger,
        private string $documentsDirectory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans modification')
            ->addOption('backup', null, InputOption::VALUE_NONE, 'Créer une sauvegarde avant migration')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $backup = $input->getOption('backup');

        $io->title('Migration des documents vers le système de sécurité');

        if ($dryRun) {
            $io->warning('Mode simulation activé - aucune modification ne sera effectuée');
        }

        // Récupérer tous les documents
        $documents = $this->entityManager->getRepository(\App\Entity\Document::class)->findAll();
        $io->info(sprintf('Trouvé %d documents à migrer', count($documents)));

        if (empty($documents)) {
            $io->success('Aucun document à migrer.');
            return Command::SUCCESS;
        }

        // Créer une sauvegarde si demandé
        if ($backup && !$dryRun) {
            $this->createBackup($io);
        }

        $successCount = 0;
        $errorCount = 0;

        $progressBar = $io->createProgressBar(count($documents));
        $progressBar->start();

        foreach ($documents as $document) {
            try {
                $this->migrateDocument($document, $dryRun);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $io->error(sprintf('Erreur pour le document %d: %s', $document->getId(), $e->getMessage()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Résumé
        $io->section('Résumé de la migration');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['Documents migrés avec succès', $successCount],
                ['Erreurs', $errorCount],
                ['Total traité', count($documents)],
            ]
        );

        if ($errorCount === 0) {
            $io->success('Migration terminée avec succès !');
            return Command::SUCCESS;
        } else {
            $io->warning(sprintf('Migration terminée avec %d erreurs.', $errorCount));
            return Command::FAILURE;
        }
    }

    private function migrateDocument(\App\Entity\Document $document, bool $dryRun): void
    {
        $oldFilePath = $this->documentsDirectory . '/' . $document->getFileName();

        if (!file_exists($oldFilePath)) {
            throw new \RuntimeException('Fichier original non trouvé');
        }

        if ($dryRun) {
            return; // Simulation
        }

        // Lire le contenu du fichier original
        $content = file_get_contents($oldFilePath);
        if ($content === false) {
            throw new \RuntimeException('Impossible de lire le fichier original');
        }

        // Créer un fichier temporaire pour simuler un UploadedFile
        $tempFile = tempnam(sys_get_temp_dir(), 'migrate_');
        file_put_contents($tempFile, $content);

        try {
            // Simuler un UploadedFile
            $uploadedFile = new \Symfony\Component\HttpFoundation\File\UploadedFile(
                $tempFile,
                $document->getOriginalFileName(),
                $document->getMimeType(),
                null,
                true
            );

            // Générer un nouveau nom de fichier sécurisé
            $originalFilename = pathinfo($document->getOriginalFileName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $hash = hash('sha256', uniqid() . microtime(true));
            $timestamp = date('YmdHis');
            $newFilename = sprintf(
                '%s_%s_%s.%s',
                $safeFilename,
                $timestamp,
                substr($hash, 0, 16),
                pathinfo($document->getFileName(), PATHINFO_EXTENSION)
            );

            // Chiffrer le fichier
            $encryptedContent = $this->encryptFile($content);

            // Sauvegarder le fichier chiffré
            $newFilePath = $this->documentsDirectory . '/' . $newFilename;
            file_put_contents($newFilePath, $encryptedContent);

            // Mettre à jour l'entité Document
            $document->setFileName($newFilename);
            $this->entityManager->flush();

            // Supprimer l'ancien fichier
            unlink($oldFilePath);

        } finally {
            // Nettoyer le fichier temporaire
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function encryptFile(string $content): string
    {
        $encryptionKey = $_ENV['APP_ENCRYPTION_KEY'] ?? 'default-key-change-in-production';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($content, 'AES-256-CBC', $encryptionKey, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Erreur lors du chiffrement');
        }

        return base64_encode($iv . $encrypted);
    }

    private function createBackup(SymfonyStyle $io): void
    {
        $backupDir = $this->documentsDirectory . '/backup_' . date('Y-m-d_H-i-s');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        $files = glob($this->documentsDirectory . '/*');
        $backupCount = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                copy($file, $backupDir . '/' . $filename);
                $backupCount++;
            }
        }

        $io->info(sprintf('Sauvegarde créée dans %s (%d fichiers)', $backupDir, $backupCount));
    }
}
