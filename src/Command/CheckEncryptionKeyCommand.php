<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-encryption-key',
    description: 'Vérifie et génère la clé de chiffrement pour la production',
)]
class CheckEncryptionKeyCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('generate', 'g', InputOption::VALUE_NONE, 'Génère une nouvelle clé de chiffrement');
        $this->addOption('check-env', 'c', InputOption::VALUE_NONE, 'Vérifie la configuration dans .env');
        $this->addOption('test-encryption', 't', InputOption::VALUE_NONE, 'Teste le chiffrement/déchiffrement');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification de la clé de chiffrement');

        // 1. Vérifier la configuration actuelle
        $io->section('1. Configuration actuelle');

        $envKey = $_ENV['APP_ENCRYPTION_KEY'] ?? null;
        $serverKey = $_SERVER['APP_ENCRYPTION_KEY'] ?? null;

        if ($envKey) {
            $io->writeln("✅ Clé trouvée dans \$_ENV: " . substr($envKey, 0, 10) . "...");
        } else {
            $io->writeln("❌ Aucune clé trouvée dans \$_ENV");
        }

        if ($serverKey) {
            $io->writeln("✅ Clé trouvée dans \$_SERVER: " . substr($serverKey, 0, 10) . "...");
        } else {
            $io->writeln("❌ Aucune clé trouvée dans \$_SERVER");
        }

        // 2. Vérifier le fichier .env
        if ($input->getOption('check-env')) {
            $io->section('2. Vérification du fichier .env');

            $envFile = '.env';
            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                if (strpos($envContent, 'APP_ENCRYPTION_KEY=') !== false) {
                    $io->writeln("✅ Variable APP_ENCRYPTION_KEY trouvée dans .env");

                    // Extraire la valeur
                    preg_match('/APP_ENCRYPTION_KEY=(.+)/', $envContent, $matches);
                    if (isset($matches[1])) {
                        $keyValue = trim($matches[1]);
                        if (!empty($keyValue) && $keyValue !== '%env(APP_ENCRYPTION_KEY)%') {
                            $io->writeln("✅ Clé définie: " . substr($keyValue, 0, 10) . "...");
                        } else {
                            $io->writeln("❌ Clé non définie ou référence circulaire");
                        }
                    }
                } else {
                    $io->writeln("❌ Variable APP_ENCRYPTION_KEY manquante dans .env");
                }
            } else {
                $io->writeln("❌ Fichier .env non trouvé");
            }
        }

        // 3. Générer une nouvelle clé si demandé
        if ($input->getOption('generate')) {
            $io->section('3. Génération d\'une nouvelle clé');

            $newKey = $this->generateSecureKey();
            $io->writeln("🔑 Nouvelle clé générée:");
            $io->writeln("   APP_ENCRYPTION_KEY=" . $newKey);

            $io->warning([
                'IMPORTANT:',
                '1. Ajoutez cette clé à votre fichier .env',
                '2. Ajoutez-la aussi aux variables d\'environnement de votre serveur',
                '3. Redémarrez votre application',
                '4. Les documents existants ne pourront plus être déchiffrés avec l\'ancienne clé !'
            ]);
        }

        // 4. Tester le chiffrement si demandé
        if ($input->getOption('test-encryption')) {
            $io->section('4. Test de chiffrement');

            $testData = 'Test de chiffrement - ' . date('Y-m-d H:i:s');
            $key = $envKey ?: $serverKey;

            if (!$key) {
                $io->error("Aucune clé de chiffrement disponible pour le test");
                return Command::FAILURE;
            }

            try {
                // Test de chiffrement
                $iv = random_bytes(16);
                $encrypted = openssl_encrypt($testData, 'AES-256-CBC', $key, 0, $iv);

                if ($encrypted === false) {
                    $io->error("❌ Erreur lors du chiffrement");
                    return Command::FAILURE;
                }

                $encryptedData = base64_encode($iv . $encrypted);
                $io->writeln("✅ Chiffrement réussi");
                $io->writeln("   Données chiffrées: " . substr($encryptedData, 0, 50) . "...");

                // Test de déchiffrement
                $data = base64_decode($encryptedData);
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);

                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

                if ($decrypted === false) {
                    $io->error("❌ Erreur lors du déchiffrement");
                    return Command::FAILURE;
                }

                if ($decrypted === $testData) {
                    $io->writeln("✅ Déchiffrement réussi");
                    $io->writeln("   Données déchiffrées: " . $decrypted);
                } else {
                    $io->error("❌ Données déchiffrées incorrectes");
                    return Command::FAILURE;
                }

            } catch (\Exception $e) {
                $io->error("❌ Erreur lors du test: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        // 5. Recommandations pour la production
        $io->section('5. Recommandations pour la production');

        $recommendations = [
            '🔐 Utilisez une clé de chiffrement forte (32+ caractères)',
            '🔒 Stockez la clé dans les variables d\'environnement du serveur',
            '📝 Ne commitez JAMAIS la clé dans le code source',
            '🔄 Redémarrez l\'application après changement de clé',
            '💾 Sauvegardez la clé de manière sécurisée',
            '⚠️  Changer la clé rendra les documents existants illisibles'
        ];

        foreach ($recommendations as $recommendation) {
            $io->writeln($recommendation);
        }

        return Command::SUCCESS;
    }

    /**
     * Génère une clé de chiffrement sécurisée
     */
    private function generateSecureKey(): string
    {
        // Générer une clé de 32 caractères (256 bits)
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $key = '';

        for ($i = 0; $i < 32; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }
}
