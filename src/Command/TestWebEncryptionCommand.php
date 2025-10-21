<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-web-encryption',
    description: 'Teste la clé de chiffrement dans le contexte web',
)]
class TestWebEncryptionCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de la clé de chiffrement dans le contexte web');

        // 1. Vérifier les variables d'environnement
        $io->section('1. Variables d\'environnement');

        $envKey = $_ENV['APP_ENCRYPTION_KEY'] ?? 'NON DÉFINIE';
        $serverKey = $_SERVER['APP_ENCRYPTION_KEY'] ?? 'NON DÉFINIE';
        $getenvKey = getenv('APP_ENCRYPTION_KEY') ?: 'NON DÉFINIE';

        $io->writeln("\$_ENV['APP_ENCRYPTION_KEY']: " . ($envKey !== 'NON DÉFINIE' ? substr($envKey, 0, 10) . '...' : $envKey));
        $io->writeln("\$_SERVER['APP_ENCRYPTION_KEY']: " . ($serverKey !== 'NON DÉFINIE' ? substr($serverKey, 0, 10) . '...' : $serverKey));
        $io->writeln("getenv('APP_ENCRYPTION_KEY'): " . ($getenvKey !== 'NON DÉFINIE' ? substr($getenvKey, 0, 10) . '...' : $getenvKey));

        // 2. Vérifier le fichier .env
        $io->section('2. Fichier .env');

        $envFile = '.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (strpos($envContent, 'APP_ENCRYPTION_KEY=') !== false) {
                preg_match('/APP_ENCRYPTION_KEY=(.+)/', $envContent, $matches);
                if (isset($matches[1])) {
                    $keyValue = trim($matches[1]);
                    $io->writeln("✅ Clé trouvée dans .env: " . substr($keyValue, 0, 10) . "...");

                    if (empty($keyValue) || $keyValue === '%env(APP_ENCRYPTION_KEY)%') {
                        $io->error("❌ Clé vide ou référence circulaire dans .env");
                        return Command::FAILURE;
                    }
                }
            } else {
                $io->error("❌ Variable APP_ENCRYPTION_KEY manquante dans .env");
                return Command::FAILURE;
            }
        } else {
            $io->error("❌ Fichier .env non trouvé");
            return Command::FAILURE;
        }

        // 3. Test de chiffrement avec différentes sources
        $io->section('3. Test de chiffrement');

        $testData = 'Test web encryption - ' . date('Y-m-d H:i:s');
        $keys = [
            '$_ENV' => $envKey,
            '$_SERVER' => $serverKey,
            'getenv()' => $getenvKey,
            '.env file' => $keyValue
        ];

        foreach ($keys as $source => $key) {
            if ($key === 'NON DÉFINIE' || empty($key)) {
                $io->writeln("❌ $source: Clé non disponible");
                continue;
            }

            try {
                // Test de chiffrement
                $iv = random_bytes(16);
                $encrypted = openssl_encrypt($testData, 'AES-256-CBC', $key, 0, $iv);

                if ($encrypted === false) {
                    $io->writeln("❌ $source: Erreur de chiffrement");
                    continue;
                }

                $encryptedData = base64_encode($iv . $encrypted);

                // Test de déchiffrement
                $data = base64_decode($encryptedData);
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);

                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

                if ($decrypted === false) {
                    $io->writeln("❌ $source: Erreur de déchiffrement");
                    continue;
                }

                if ($decrypted === $testData) {
                    $io->writeln("✅ $source: Chiffrement/déchiffrement OK");
                } else {
                    $io->writeln("❌ $source: Données déchiffrées incorrectes");
                }

            } catch (\Exception $e) {
                $io->writeln("❌ $source: Exception - " . $e->getMessage());
            }
        }

        // 4. Recommandations
        $io->section('4. Recommandations');

        if ($envKey === 'NON DÉFINIE' && $serverKey === 'NON DÉFINIE') {
            $io->error([
                'PROBLÈME IDENTIFIÉ:',
                'La clé de chiffrement n\'est pas disponible dans le contexte web.',
                '',
                'SOLUTIONS:',
                '1. Redémarrez votre serveur web (Apache/Nginx)',
                '2. Vérifiez que le fichier .env est lu par le serveur web',
                '3. Ajoutez la clé aux variables d\'environnement du serveur',
                '4. Vérifiez la configuration PHP du serveur web'
            ]);
        } else {
            $io->success('La clé de chiffrement est disponible dans le contexte web.');
        }

        return Command::SUCCESS;
    }
}
