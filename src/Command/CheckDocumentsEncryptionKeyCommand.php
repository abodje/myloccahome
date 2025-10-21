<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-documents-encryption-key',
    description: 'Vérifie la clé de chiffrement spécifique aux documents',
)]
class CheckDocumentsEncryptionKeyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification de la clé de chiffrement des documents');

        // 1. Vérifier les variables d'environnement
        $io->section('1. Variables d\'environnement');
        $envKey = $_ENV['APP_DOCUMENTS_ENCRYPTION_KEY'] ?? null;
        $serverKey = $_SERVER['APP_DOCUMENTS_ENCRYPTION_KEY'] ?? null;

        if ($envKey) {
            $io->writeln("✅ Clé trouvée dans \$_ENV: " . substr($envKey, 0, 10) . "...");
        } else {
            $io->error("❌ Clé manquante dans \$_ENV");
        }

        if ($serverKey) {
            $io->writeln("✅ Clé trouvée dans \$_SERVER: " . substr($serverKey, 0, 10) . "...");
        } else {
            $io->error("❌ Clé manquante dans \$_SERVER");
        }

        // 2. Vérifier le fichier .env
        $io->section('2. Fichier .env');
        $envFile = '.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (strpos($envContent, 'APP_DOCUMENTS_ENCRYPTION_KEY=') !== false) {
                $io->writeln("✅ Variable APP_DOCUMENTS_ENCRYPTION_KEY trouvée dans .env");

                // Extraire la valeur
                preg_match('/APP_DOCUMENTS_ENCRYPTION_KEY=(.+)/', $envContent, $matches);
                if (isset($matches[1])) {
                    $keyValue = trim($matches[1]);
                    if (!empty($keyValue) && $keyValue !== '%env(APP_DOCUMENTS_ENCRYPTION_KEY)%') {
                        $io->writeln("✅ Valeur définie: " . substr($keyValue, 0, 10) . "...");
                    } else {
                        $io->warning("⚠️  Valeur non définie ou référence circulaire");
                    }
                }
            } else {
                $io->writeln("❌ Variable APP_DOCUMENTS_ENCRYPTION_KEY manquante dans .env");
            }
        } else {
            $io->error("❌ Fichier .env non trouvé");
        }

        // 3. Test de chiffrement/déchiffrement
        $io->section('3. Test de chiffrement');
        if ($envKey) {
            try {
                $testData = 'Test de chiffrement documents - ' . date('Y-m-d H:i:s');
                $iv = random_bytes(16);
                $encrypted = openssl_encrypt($testData, 'AES-256-CBC', $envKey, 0, $iv);
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $envKey, 0, $iv);

                if ($decrypted === $testData) {
                    $io->writeln("✅ Chiffrement réussi");
                    $io->writeln("   Données chiffrées: " . substr(base64_encode($iv . $encrypted), 0, 50) . "...");
                    $io->writeln("✅ Déchiffrement réussi");
                    $io->writeln("   Données déchiffrées: " . $decrypted);
                } else {
                    $io->error("❌ Erreur de chiffrement/déchiffrement");
                }
            } catch (\Exception $e) {
                $io->error("❌ Erreur lors du test de chiffrement: " . $e->getMessage());
            }
        } else {
            $io->error("❌ Impossible de tester le chiffrement sans clé");
        }

        // 4. Recommandations
        $io->section('4. Recommandations');
        $io->writeln([
            '🔐 Utilisez une clé de chiffrement forte (32+ caractères)',
            '🔒 Stockez la clé dans les variables d\'environnement du serveur',
            '📝 Ne commitez JAMAIS la clé dans le code source',
            '🔄 Redémarrez l\'application après changement de clé',
            '💾 Sauvegardez la clé de manière sécurisée',
            '⚠️  Changer la clé rendra les documents existants illisibles'
        ]);

        return Command::SUCCESS;
    }
}
