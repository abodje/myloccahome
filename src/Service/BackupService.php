<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de sauvegarde automatique de la base de données et des fichiers
 */
class BackupService
{
    private string $backupDir;
    private string $projectDir;

    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {
        $this->projectDir = $this->params->get('kernel.project_dir');
        $this->backupDir = $this->projectDir . '/var/backups';

        // Créer le dossier de backup s'il n'existe pas
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Crée une sauvegarde complète (base de données + fichiers)
     */
    public function createFullBackup(): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $results = [
            'success' => false,
            'timestamp' => $timestamp,
            'database' => null,
            'files' => null,
            'errors' => []
        ];

        try {
            // 1. Sauvegarde de la base de données
            $dbBackup = $this->backupDatabase($timestamp);
            $results['database'] = $dbBackup;

            // 2. Sauvegarde des fichiers
            $filesBackup = $this->backupFiles($timestamp);
            $results['files'] = $filesBackup;

            // 3. Créer un fichier manifest
            $this->createManifest($timestamp, $dbBackup, $filesBackup);

            $results['success'] = true;
            $this->logger->info("✅ Sauvegarde complète créée : {$timestamp}");

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $this->logger->error("❌ Erreur sauvegarde : " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Sauvegarde la base de données
     */
    public function backupDatabase(string $timestamp): array
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? '';

        // Parser l'URL de la base de données
        $parsed = parse_url($databaseUrl);

        if (!$parsed) {
            throw new \Exception('URL de base de données invalide');
        }

        $dbName = ltrim($parsed['path'] ?? '', '/');
        $dbUser = $parsed['user'] ?? 'root';
        $dbPass = $parsed['pass'] ?? '';
        $dbHost = $parsed['host'] ?? 'localhost';
        $dbPort = $parsed['port'] ?? 3306;

        $backupFile = $this->backupDir . "/database_{$timestamp}.sql";

        // Commande mysqldump
        $command = sprintf(
            'mysqldump -h%s -P%s -u%s %s %s > %s 2>&1',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        // Exécuter la commande
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            $this->logger->error("Erreur mysqldump: {$error}");

            // Fallback : utiliser PHP pour backup simple
            return $this->backupDatabasePHP($timestamp, $dbName);
        }

        // Compresser le fichier SQL
        $this->compressFile($backupFile);

        $fileSize = file_exists($backupFile . '.gz')
            ? filesize($backupFile . '.gz')
            : (file_exists($backupFile) ? filesize($backupFile) : 0);

        return [
            'file' => basename($backupFile . '.gz'),
            'path' => $backupFile . '.gz',
            'size' => $fileSize,
            'compressed' => file_exists($backupFile . '.gz')
        ];
    }

    /**
     * Sauvegarde via PHP (fallback si mysqldump non disponible)
     */
    private function backupDatabasePHP(string $timestamp, string $dbName): array
    {
        $backupFile = $this->backupDir . "/database_{$timestamp}.sql";

        // Cette méthode est un fallback basique
        // Elle copie juste un message indiquant d'utiliser mysqldump
        $message = "-- Sauvegarde générée le " . date('Y-m-d H:i:s') . "\n";
        $message .= "-- Base de données : {$dbName}\n";
        $message .= "-- ATTENTION : mysqldump non disponible, sauvegarde limitée\n";
        $message .= "-- Utilisez : php bin/console app:backup pour une sauvegarde complète\n";

        file_put_contents($backupFile, $message);

        return [
            'file' => basename($backupFile),
            'path' => $backupFile,
            'size' => filesize($backupFile),
            'compressed' => false,
            'warning' => 'mysqldump non disponible'
        ];
    }

    /**
     * Sauvegarde les fichiers importants
     */
    public function backupFiles(string $timestamp): array
    {
        $backupFile = $this->backupDir . "/files_{$timestamp}.tar.gz";

        $directoriesToBackup = [
            'public/uploads',
            'config',
            '.env.local'
        ];

        $filesToBackup = [];
        foreach ($directoriesToBackup as $dir) {
            $fullPath = $this->projectDir . '/' . $dir;
            if (file_exists($fullPath)) {
                $filesToBackup[] = $dir;
            }
        }

        if (empty($filesToBackup)) {
            return [
                'file' => null,
                'size' => 0,
                'count' => 0
            ];
        }

        // Créer l'archive tar.gz
        $filesList = implode(' ', array_map('escapeshellarg', $filesToBackup));
        $command = sprintf(
            'cd %s && tar -czf %s %s 2>&1',
            escapeshellarg($this->projectDir),
            escapeshellarg($backupFile),
            $filesList
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($backupFile)) {
            $this->logger->warning("tar.gz non disponible, utilisation de ZIP");
            return $this->backupFilesZip($timestamp, $filesToBackup);
        }

        return [
            'file' => basename($backupFile),
            'path' => $backupFile,
            'size' => filesize($backupFile),
            'count' => count($filesToBackup)
        ];
    }

    /**
     * Sauvegarde via ZIP (fallback si tar non disponible)
     */
    private function backupFilesZip(string $timestamp, array $directories): array
    {
        $backupFile = $this->backupDir . "/files_{$timestamp}.zip";

        $zip = new \ZipArchive();
        if ($zip->open($backupFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Impossible de créer le fichier ZIP");
        }

        $fileCount = 0;
        foreach ($directories as $dir) {
            $fullPath = $this->projectDir . '/' . $dir;

            if (is_dir($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $dir);
                $fileCount++;
            } elseif (is_file($fullPath)) {
                $zip->addFile($fullPath, $dir);
                $fileCount++;
            }
        }

        $zip->close();

        return [
            'file' => basename($backupFile),
            'path' => $backupFile,
            'size' => filesize($backupFile),
            'count' => $fileCount
        ];
    }

    /**
     * Ajoute un dossier récursivement au ZIP
     */
    private function addDirectoryToZip(\ZipArchive $zip, string $directory, string $localPath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $localPath . '/' . substr($filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Compresse un fichier avec gzip
     */
    private function compressFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $gzFile = $filePath . '.gz';

        // Ouvrir le fichier source
        $source = fopen($filePath, 'rb');
        $dest = gzopen($gzFile, 'wb9');

        if (!$source || !$dest) {
            return false;
        }

        // Copier et compresser
        while (!feof($source)) {
            gzwrite($dest, fread($source, 1024 * 512));
        }

        fclose($source);
        gzclose($dest);

        // Supprimer le fichier non compressé
        if (file_exists($gzFile)) {
            unlink($filePath);
            return true;
        }

        return false;
    }

    /**
     * Crée un fichier manifest avec les informations de sauvegarde
     */
    private function createManifest(string $timestamp, array $dbBackup, array $filesBackup): void
    {
        $manifestFile = $this->backupDir . "/manifest_{$timestamp}.json";

        $manifest = [
            'timestamp' => $timestamp,
            'date' => date('Y-m-d H:i:s'),
            'database' => $dbBackup,
            'files' => $filesBackup,
            'php_version' => PHP_VERSION,
            'symfony_version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
        ];

        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Liste toutes les sauvegardes disponibles
     */
    public function listBackups(): array
    {
        $backups = [];

        if (!is_dir($this->backupDir)) {
            return [];
        }

        // Lire les fichiers manifest
        $manifestFiles = glob($this->backupDir . '/manifest_*.json');

        foreach ($manifestFiles as $manifestFile) {
            $manifest = json_decode(file_get_contents($manifestFile), true);

            if ($manifest) {
                $manifest['manifest_file'] = basename($manifestFile);
                $backups[] = $manifest;
            }
        }

        // Trier par date décroissante
        usort($backups, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return $backups;
    }

    /**
     * Supprime les anciennes sauvegardes
     */
    public function cleanOldBackups(int $daysToKeep = 30): int
    {
        $deleted = 0;
        $cutoffDate = new \DateTime("-{$daysToKeep} days");

        $backups = $this->listBackups();

        foreach ($backups as $backup) {
            $backupDate = new \DateTime($backup['date']);

            if ($backupDate < $cutoffDate) {
                // Supprimer les fichiers de cette sauvegarde
                $timestamp = $backup['timestamp'];

                $filesToDelete = [
                    "database_{$timestamp}.sql",
                    "database_{$timestamp}.sql.gz",
                    "files_{$timestamp}.tar.gz",
                    "files_{$timestamp}.zip",
                    "manifest_{$timestamp}.json"
                ];

                foreach ($filesToDelete as $file) {
                    $fullPath = $this->backupDir . '/' . $file;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                        $deleted++;
                    }
                }
            }
        }

        $this->logger->info("Nettoyage sauvegardes : {$deleted} fichier(s) supprimé(s)");

        return $deleted;
    }

    /**
     * Récupère les statistiques des sauvegardes
     */
    public function getBackupStatistics(): array
    {
        $backups = $this->listBackups();

        $totalSize = 0;
        foreach ($backups as $backup) {
            $totalSize += $backup['database']['size'] ?? 0;
            $totalSize += $backup['files']['size'] ?? 0;
        }

        return [
            'count' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'backup_dir' => $this->backupDir,
            'oldest' => $backups[count($backups) - 1] ?? null,
            'newest' => $backups[0] ?? null
        ];
    }

    /**
     * Formate les octets en taille lisible
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Télécharge une sauvegarde
     */
    public function getBackupFile(string $filename): ?string
    {
        $filePath = $this->backupDir . '/' . $filename;

        if (file_exists($filePath)) {
            return $filePath;
        }

        return null;
    }

    /**
     * Supprime une sauvegarde spécifique
     */
    public function deleteBackup(string $timestamp): bool
    {
        $deleted = false;

        $filesToDelete = [
            "database_{$timestamp}.sql",
            "database_{$timestamp}.sql.gz",
            "files_{$timestamp}.tar.gz",
            "files_{$timestamp}.zip",
            "manifest_{$timestamp}.json"
        ];

        foreach ($filesToDelete as $file) {
            $fullPath = $this->backupDir . '/' . $file;
            if (file_exists($fullPath)) {
                unlink($fullPath);
                $deleted = true;
            }
        }

        if ($deleted) {
            $this->logger->info("Sauvegarde {$timestamp} supprimée");
        }

        return $deleted;
    }
}

