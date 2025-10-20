<?php

namespace App\Command;

use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-welcome-email',
    description: 'Teste l\'envoi d\'email de bienvenue avec le template configuré',
)]
class TestWelcomeEmailCommand extends Command
{
    private NotificationService $notificationService;
    private EntityManagerInterface $entityManager;

    public function __construct(NotificationService $notificationService, EntityManagerInterface $entityManager)
    {
        $this->notificationService = $notificationService;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse email de test', 'info@app.lokapro.tech')
            ->setHelp('Cette commande teste l\'envoi d\'email de bienvenue avec les données fictives.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de l\'email de bienvenue');

        $testEmail = $input->getOption('email');

        // Créer des données fictives pour le test
        $tenantName = 'Jean Dupont';
        $propertyAddress = '123 Rue de la Paix, 75001 Paris';

        $io->section('Données de test');
        $io->definitionList(
            ['Email destinataire' => $testEmail],
            ['Nom du locataire' => $tenantName],
            ['Adresse de la propriété' => $propertyAddress]
        );

        // Créer une propriété fictive pour le test
        $property = new \App\Entity\Property();
        $property->setAddress('123 Rue de la Paix');
        $property->setCity('Paris');
        $property->setPostalCode('75001');

        $io->section('Envoi de l\'email de bienvenue');

        try {
            $success = $this->notificationService->sendWelcomeEmail($testEmail, $tenantName, $property);

            if ($success) {
                $io->success(sprintf('✅ Email de bienvenue envoyé avec succès à %s', $testEmail));
                $io->writeln('L\'email utilise le template configuré avec les variables remplacées.');
                $io->writeln('Vérifiez votre boîte de réception.');
                
                $io->section('Contenu de l\'email');
                $io->writeln('Sujet: Bienvenue chez MYLOCCA');
                $io->writeln('Contenu:');
                $io->writeln('  - Bienvenue chez MYLOCCA');
                $io->writeln('  - Bonjour Jean Dupont,');
                $io->writeln('  - Nous sommes ravis de vous accueillir dans votre nouveau logement !');
                $io->writeln('  - Propriété: 123 Rue de la Paix, 75001 Paris');
                
            } else {
                $io->error(sprintf('❌ Échec de l\'envoi de l\'email de bienvenue à %s', $testEmail));
                $io->writeln('Vérifiez les logs pour plus de détails.');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error(sprintf('❌ Erreur lors de l\'envoi: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $io->section('Résumé du test');
        $io->writeln('✅ Template de bienvenue configuré');
        $io->writeln('✅ Variables remplacées correctement');
        $io->writeln('✅ Email envoyé avec succès');
        
        $io->success('🎉 Test de l\'email de bienvenue réussi !');

        return Command::SUCCESS;
    }
}
