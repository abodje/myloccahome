<?php

namespace App\Command;

use App\Service\CinetPayService;
use App\Service\SettingsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-cinetpay',
    description: 'Teste la configuration CinetPay',
)]
class TestCinetPayCommand extends Command
{
    public function __construct(
        private SettingsService $settingsService,
        private CinetPayService $cinetpayService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test de configuration CinetPay');

        // Vérifier les paramètres
        $apikey = $this->settingsService->get('cinetpay_apikey');
        $siteId = $this->settingsService->get('cinetpay_site_id');
        $secretKey = $this->settingsService->get('cinetpay_secret_key');
        $environment = $this->settingsService->get('cinetpay_environment', 'test');

        $io->section('Configuration actuelle');
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['API Key', $apikey ? '✅ Configuré (' . strlen($apikey) . ' caractères)' : '❌ Non configuré'],
                ['Site ID', $siteId ? '✅ Configuré (' . $siteId . ')' : '❌ Non configuré'],
                ['Secret Key', $secretKey ? '✅ Configuré (' . strlen($secretKey) . ' caractères)' : '❌ Non configuré'],
                ['Environnement', $environment === 'production' ? '🚀 Production' : '🧪 Test'],
            ]
        );

        if (!$apikey || !$siteId) {
            $io->error('Configuration incomplète. Veuillez configurer les identifiants CinetPay dans /admin/parametres/cinetpay');
            return Command::FAILURE;
        }

        // Test de connexion
        $io->section('Test de connexion');

        try {
            $io->writeln('Tentative d\'initialisation d\'un paiement test...');

            $this->cinetpayService
                ->setTransactionId('TEST-' . uniqid())
                ->setAmount(100)
                ->setCurrency('XOF')
                ->setDescription('Test de connexion CinetPay')
                ->setNotifyUrl('https://example.com/notify')
                ->setReturnUrl('https://example.com/return')
                ->setCustomer([
                    'customer_name' => 'Test',
                    'customer_surname' => 'User',
                    'customer_phone_number' => '22500000000',
                    'customer_email' => 'test@example.com',
                    'customer_address' => 'Test Address',
                    'customer_city' => 'Test City',
                    'customer_country' => 'CI',
                    'customer_state' => 'AB',
                    'customer_zip_code' => '00000',
                ]);

            $paymentUrl = $this->cinetpayService->initPayment();

            $io->success('✅ Connexion CinetPay réussie !');
            $io->writeln('URL de paiement générée : ' . $paymentUrl);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('❌ Erreur de connexion CinetPay :');
            $io->writeln($e->getMessage());

            $io->section('Solutions possibles');
            $io->listing([
                'Vérifiez vos identifiants CinetPay',
                'Assurez-vous que votre compte CinetPay est actif',
                'Vérifiez que l\'environnement (test/production) est correct',
                'Contactez le support CinetPay si le problème persiste'
            ]);

            return Command::FAILURE;
        }
    }
}
