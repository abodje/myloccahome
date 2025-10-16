<?php

namespace App\Command;

use App\Service\EncryptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-encryption-key',
    description: 'Génère une clé de chiffrement sécurisée pour l\'application',
)]
class GenerateEncryptionKeyCommand extends Command
{
    private EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔐 Génération de clé de chiffrement');

        // Générer une nouvelle clé
        $newKey = $this->encryptionService->generateNewKey();
        $hashedKey = base64_encode($this->encryptionService->hashKey($newKey));

        $io->section('Nouvelle clé générée :');
        $io->text($newKey);

        $io->section('Instructions :');
        $io->listing([
            'Ajoutez cette ligne dans votre fichier .env.local :',
            'APP_ENCRYPTION_KEY=' . $newKey,
            '',
            '⚠️  IMPORTANT :',
            '- Gardez cette clé secrète et ne la partagez jamais',
            '- Sauvegardez-la dans un endroit sûr',
            '- Perdre cette clé rendra impossible le déchiffrement des données existantes',
            '- Utilisez une clé différente pour chaque environnement (dev, prod, etc.)'
        ]);

        $io->section('Test de la clé :');

        // Tester la clé générée
        try {
            $testData = 'Test de chiffrement avec la nouvelle clé';
            $encrypted = $this->encryptionService->encrypt($testData);
            $decrypted = $this->encryptionService->decrypt($encrypted);

            if ($decrypted === $testData) {
                $io->success('✅ La clé fonctionne correctement !');
            } else {
                $io->error('❌ Erreur dans le test de la clé');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors du test : ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->note([
            'Après avoir ajouté la clé dans .env.local,',
            'redémarrez votre serveur pour que les changements prennent effet.'
        ]);

        return Command::SUCCESS;
    }
}
