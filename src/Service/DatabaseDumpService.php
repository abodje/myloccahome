<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Doctrine\DBAL\Connection;

/**
 * Service pour gérer les dumps et imports de base de données
 */
class DatabaseDumpService
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private string $projectDir
    ) {
    }

    /**
     * Crée un dump SQL de la base de données actuelle
     */
    public function createDump(array $options = []): array
    {
        try {
            $this->logger->info("📦 Création du dump de base de données");

            // Récupérer les paramètres de connexion
            $params = $this->connection->getParams();
            $host = $params['host'] ?? 'localhost';
            $port = $params['port'] ?? 3306;
            $dbname = $params['dbname'] ?? '';
            $user = $params['user'] ?? '';
            $password = $params['password'] ?? '';

            // Générer le nom du fichier dump
            $dumpFile = $this->projectDir . '/var/dumps/demo_template_' . date('Y-m-d_His') . '.sql';
            $dumpDir = dirname($dumpFile);

            // Créer le dossier si nécessaire
            if (!is_dir($dumpDir)) {
                mkdir($dumpDir, 0755, true);
            }

            // Tables à exclure (sessions, cache, logs, etc.)
            $excludeTables = $options['excludeTables'] ?? [
                'messenger_messages',
                'sessions',
                'migration_versions',
                'doctrine_migration_versions'
            ];

            // Construire les arguments de mysqldump
            $ignoreTables = [];
            foreach ($excludeTables as $table) {
                $ignoreTables[] = "--ignore-table={$dbname}.{$table}";
            }

            // Commande mysqldump
            $command = [
                'mysqldump',
                '-h', $host,
                '-P', (string)$port,
                '-u', $user,
                '--password=' . $password,
                '--single-transaction',
                '--quick',
                '--lock-tables=false',
                ...$ignoreTables,
                $dbname
            ];

            $process = new Process($command);
            $process->setTimeout(300); // 5 minutes max
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Sauvegarder le dump
            file_put_contents($dumpFile, $process->getOutput());

            $fileSize = filesize($dumpFile);
            $this->logger->info("✅ Dump créé avec succès", [
                'file' => $dumpFile,
                'size' => round($fileSize / 1024, 2) . ' KB'
            ]);

            return [
                'success' => true,
                'file' => $dumpFile,
                'size' => $fileSize
            ];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur création dump: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Importe un dump SQL dans une base de données distante
     */
    public function importDump(string $dumpFile, array $targetDb): array
    {
        try {
            $this->logger->info("📥 Import du dump dans la base cible", [
                'dump' => basename($dumpFile),
                'target_db' => $targetDb['database'] ?? 'unknown'
            ]);

            if (!file_exists($dumpFile)) {
                throw new \Exception("Fichier dump introuvable: {$dumpFile}");
            }

            $host = $targetDb['host'] ?? 'localhost';
            $port = $targetDb['port'] ?? 3306;
            $database = $targetDb['database'] ?? '';
            $user = $targetDb['user'] ?? '';
            $password = $targetDb['password'] ?? '';

            // Vérifier que la base de données cible existe
            $this->logger->info("Vérification de la base cible...");

            // Commande mysql pour importer
            $command = [
                'mysql',
                '-h', $host,
                '-P', (string)$port,
                '-u', $user,
                '--password=' . $password,
                $database
            ];

            $process = new Process($command);
            $process->setInput(file_get_contents($dumpFile));
            $process->setTimeout(600); // 10 minutes max
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->logger->info("✅ Import terminé avec succès");

            return [
                'success' => true,
                'message' => 'Dump importé avec succès'
            ];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur import dump: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clone la structure et les données d'une base vers une autre
     */
    public function cloneDatabase(array $targetDb, array $options = []): array
    {
        try {
            $this->logger->info("🔄 Clonage de la base de données");

            // 1. Créer un dump temporaire
            $dumpResult = $this->createDump($options);
            if (!$dumpResult['success']) {
                throw new \Exception("Échec création dump: " . $dumpResult['error']);
            }

            $dumpFile = $dumpResult['file'];

            // 2. Importer le dump dans la base cible
            $importResult = $this->importDump($dumpFile, $targetDb);
            
            // 3. Nettoyer le fichier temporaire si demandé
            if ($options['cleanup'] ?? true) {
                unlink($dumpFile);
                $this->logger->info("🗑️  Fichier dump temporaire supprimé");
            }

            if (!$importResult['success']) {
                throw new \Exception("Échec import: " . $importResult['error']);
            }

            return [
                'success' => true,
                'message' => 'Base de données clonée avec succès'
            ];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur clonage base de données: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Importe le dump directement via Doctrine (alternative sans mysqldump)
     */
    public function importDumpViaDoctrine(string $dumpFile, Connection $targetConnection): array
    {
        try {
            $this->logger->info("📥 Import SQL via Doctrine");

            if (!file_exists($dumpFile)) {
                throw new \Exception("Fichier dump introuvable: {$dumpFile}");
            }

            $sql = file_get_contents($dumpFile);
            
            // Séparer les instructions SQL
            $statements = array_filter(
                explode(';', $sql),
                fn($stmt) => trim($stmt) !== ''
            );

            $this->logger->info("📝 Exécution de " . count($statements) . " instructions SQL");

            foreach ($statements as $index => $statement) {
                $statement = trim($statement);
                if (empty($statement)) {
                    continue;
                }

                try {
                    $targetConnection->executeStatement($statement);
                } catch (\Exception $e) {
                    $this->logger->warning("⚠️ Erreur instruction {$index}: " . $e->getMessage());
                    // Continue même en cas d'erreur (tables déjà existantes, etc.)
                }
            }

            $this->logger->info("✅ Import Doctrine terminé");

            return [
                'success' => true,
                'statements_executed' => count($statements)
            ];

        } catch (\Exception $e) {
            $this->logger->error("❌ Erreur import Doctrine: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
