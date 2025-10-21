<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Entity\Settings;
use App\Form\CurrencyType;
use App\Repository\CurrencyRepository;
use App\Repository\SettingsRepository;
use App\Service\CurrencyService;
use App\Service\OrangeSmsService;
use App\Service\OrangeSmsDsnService;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/parametres')]
class SettingsController extends AbstractController
{
    #[Route('/', name: 'app_admin_settings_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/settings/index.html.twig');
    }

    #[Route('/devises', name: 'app_admin_currencies', methods: ['GET'])]
    public function currencies(CurrencyRepository $currencyRepository): Response
    {
        $currencies = $currencyRepository->findBy([], ['code' => 'ASC']);
        $stats = $currencyRepository->getStatistics();

        return $this->render('admin/settings/currencies.html.twig', [
            'currencies' => $currencies,
            'stats' => $stats,
        ]);
    }

    #[Route('/devises/nouvelle', name: 'app_admin_currency_new', methods: ['GET', 'POST'])]
    public function newCurrency(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currency = new Currency();
        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($currency);
            $entityManager->flush();

            $this->addFlash('success', 'La devise a été créée avec succès.');
            return $this->redirectToRoute('app_admin_currencies');
        }

        return $this->render('admin/settings/currency_new.html.twig', [
            'currency' => $currency,
            'form' => $form,
        ]);
    }

    #[Route('/devises/{id}/modifier', name: 'app_admin_currency_edit', methods: ['GET', 'POST'])]
    public function editCurrency(Currency $currency, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CurrencyType::class, $currency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La devise a été modifiée avec succès.');
            return $this->redirectToRoute('app_admin_currencies');
        }

        return $this->render('admin/settings/currency_edit.html.twig', [
            'currency' => $currency,
            'form' => $form,
        ]);
    }

    #[Route('/devises/{id}/supprimer', name: 'app_admin_currency_delete', methods: ['POST'])]
    public function deleteCurrency(Currency $currency, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$currency->getId(), $request->request->get('_token'))) {
            // Ne pas permettre la suppression de la devise par défaut
            if ($currency->isDefault()) {
                $this->addFlash('error', 'Impossible de supprimer la devise par défaut. Définissez d\'abord une autre devise comme devise par défaut.');
                return $this->redirectToRoute('app_admin_currencies');
            }

            // Vérifier si la devise est utilisée (optionnel, selon votre logique métier)
            // Vous pourriez vérifier si des paiements, baux, etc. utilisent cette devise

            try {
                $code = $currency->getCode();
                $entityManager->remove($currency);
                $entityManager->flush();

                $this->addFlash('success', "La devise {$code} a été supprimée avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette devise. Elle est peut-être utilisée dans le système.');
            }
        }

        return $this->redirectToRoute('app_admin_currencies');
    }

    #[Route('/devises/{id}/defaut', name: 'app_admin_currency_set_default', methods: ['POST'])]
    public function setDefaultCurrency(Currency $currency, CurrencyService $currencyService): Response
    {
        $currencyService->setDefaultCurrency($currency);

        $this->addFlash('success', "La devise {$currency->getCode()} est maintenant la devise par défaut.");
        return $this->redirectToRoute('app_admin_currencies');
    }

    #[Route('/devises/{id}/active', name: 'app_admin_currency_set_active', methods: ['POST'])]
    public function setActiveCurrency(Currency $currency, CurrencyService $currencyService): Response
    {
        try {
            $currencyService->setActiveCurrency($currency);
            $this->addFlash('success', "La devise {$currency->getCode()} est maintenant la devise active dans l'application.");
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_currencies');
    }

    #[Route('/devises/mettre-a-jour-taux', name: 'app_admin_currencies_update_rates', methods: ['POST'])]
    public function updateExchangeRates(CurrencyService $currencyService): Response
    {
        $success = $currencyService->updateExchangeRates();

        if ($success) {
            $this->addFlash('success', 'Les taux de change ont été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la mise à jour des taux de change.');
        }

        return $this->redirectToRoute('app_admin_currencies');
    }

    #[Route('/localisation', name: 'app_admin_localization', methods: ['GET', 'POST'])]
    public function localization(Request $request, CurrencyService $currencyService): Response
    {
        $settings = $currencyService->getLocalizationSettings();

        if ($request->isMethod('POST')) {
            $newSettings = [
                'date_format' => $request->request->get('date_format', 'd/m/Y'),
                'time_format' => $request->request->get('time_format', 'H:i'),
                'timezone' => $request->request->get('timezone', 'Europe/Paris'),
                'locale' => $request->request->get('locale', 'fr_FR'),
                'decimal_separator' => $request->request->get('decimal_separator', ','),
                'thousands_separator' => $request->request->get('thousands_separator', ' '),
            ];

            $currencyService->updateLocalizationSettings($newSettings);

            $this->addFlash('success', 'Les paramètres de localisation ont été mis à jour.');
            return $this->redirectToRoute('app_admin_localization');
        }

        return $this->render('admin/settings/localization.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/application', name: 'app_admin_app_settings', methods: ['GET', 'POST'])]
    public function applicationSettings(Request $request, SettingsService $settingsService): Response
    {
        $settings = $settingsService->getAppSettings();

        if ($request->isMethod('POST')) {
            $newSettings = [
                'app_name' => $request->request->get('app_name'),
                'company_name' => $request->request->get('company_name'),
                'company_address' => $request->request->get('company_address'),
                'company_phone' => $request->request->get('company_phone'),
                'company_email' => $request->request->get('company_email'),
                'app_logo' => $request->request->get('app_logo'),
                'app_description' => $request->request->get('app_description'),
                'maintenance_mode' => $request->request->has('maintenance_mode'),
                'registration_enabled' => $request->request->has('registration_enabled'),
            ];

            foreach ($newSettings as $key => $value) {
                $settingsService->set($key, $value);
            }

            $this->addFlash('success', 'Les paramètres de l\'application ont été mis à jour.');
            return $this->redirectToRoute('app_admin_app_settings');
        }

        return $this->render('admin/settings/application.html.twig', [
            'settings' => array_merge($settings, [
                'app_logo' => $settingsService->get('app_logo', ''),
                'app_description' => $settingsService->get('app_description', 'Logiciel de gestion locative'),
                'maintenance_mode' => $settingsService->get('maintenance_mode', false),
                'registration_enabled' => $settingsService->get('registration_enabled', true),
            ]),
        ]);
    }

    #[Route('/email', name: 'app_admin_email_settings', methods: ['GET', 'POST'])]
    public function emailSettings(Request $request, SettingsService $settingsService): Response
    {
        $settings = $settingsService->getEmailSettings();

        if ($request->isMethod('POST')) {
            $newSettings = [
                'smtp_host' => $request->request->get('smtp_host'),
                'smtp_port' => $request->request->get('smtp_port'),
                'smtp_username' => $request->request->get('smtp_username'),
                'smtp_password' => $request->request->get('smtp_password'),
                'smtp_encryption' => $request->request->get('smtp_encryption'),
                'email_from' => $request->request->get('email_from'),
                'email_from_name' => $request->request->get('email_from_name'),
                'email_notifications' => $request->request->has('email_notifications'),
            ];

            foreach ($newSettings as $key => $value) {
                $settingsService->set($key, $value);
            }

            $this->addFlash('success', 'Les paramètres email ont été mis à jour.');
            return $this->redirectToRoute('app_admin_email_settings');
        }

        return $this->render('admin/settings/email.html.twig', [
            'settings' => array_merge($settings, [
                'smtp_username' => $settingsService->get('smtp_username', ''),
                'smtp_password' => $settingsService->get('smtp_password', ''),
                'smtp_encryption' => $settingsService->get('smtp_encryption', 'tls'),
                'email_from_name' => $settingsService->get('email_from_name', 'LOKAPRO'),
                'email_notifications' => $settingsService->get('email_notifications', true),
            ]),
        ]);
    }

    #[Route('/paiements', name: 'app_admin_payment_settings', methods: ['GET', 'POST'])]
    public function paymentSettings(Request $request, SettingsService $settingsService): Response
    {
        $settings = $settingsService->getPaymentSettings();

        if ($request->isMethod('POST')) {
            $newSettings = [
                'default_rent_due_day' => $request->request->get('default_rent_due_day'),
                'late_fee_rate' => $request->request->get('late_fee_rate'),
                'auto_generate_rent' => $request->request->has('auto_generate_rent'),
                'payment_reminder_days' => $request->request->get('payment_reminder_days'),
                'allow_partial_payments' => $request->request->has('allow_partial_payments'),
                'minimum_payment_amount' => $request->request->get('minimum_payment_amount'),
                'allow_advance_payments' => $request->request->has('allow_advance_payments'),
                'minimum_advance_amount' => $request->request->get('minimum_advance_amount'),
            ];

            foreach ($newSettings as $key => $value) {
                $settingsService->set($key, $value);
            }

            $this->addFlash('success', 'Les paramètres de paiement ont été mis à jour.');
            return $this->redirectToRoute('app_admin_payment_settings');
        }

        return $this->render('admin/settings/payment.html.twig', [
            'settings' => array_merge($settings, [
                'payment_reminder_days' => $settingsService->get('payment_reminder_days', 7),
                'allow_partial_payments' => $settingsService->get('allow_partial_payments', false),
                'minimum_payment_amount' => $settingsService->get('minimum_payment_amount', 10),
                'allow_advance_payments' => $settingsService->get('allow_advance_payments', true),
                'minimum_advance_amount' => $settingsService->get('minimum_advance_amount', 50),
            ]),
        ]);
    }

    #[Route('/cinetpay', name: 'app_admin_cinetpay_settings', methods: ['GET', 'POST'])]
    public function cinetpaySettings(Request $request, SettingsService $settingsService): Response
    {
        if ($request->isMethod('POST')) {
            $newSettings = [
                'cinetpay_apikey' => $request->request->get('cinetpay_apikey'),
                'cinetpay_site_id' => $request->request->get('cinetpay_site_id'),
                'cinetpay_secret_key' => $request->request->get('cinetpay_secret_key'),
                'cinetpay_environment' => $request->request->get('cinetpay_environment'),
                'cinetpay_currency' => $request->request->get('cinetpay_currency'),
                'cinetpay_return_url' => $request->request->get('cinetpay_return_url'),
                'cinetpay_enabled' => $request->request->has('cinetpay_enabled'),
                'cinetpay_channels' => $request->request->get('cinetpay_channels'),
            ];

            foreach ($newSettings as $key => $value) {
                if ($value !== null && $value !== '') {
                    $settingsService->set($key, $value);
                }
            }

            $this->addFlash('success', 'La configuration CinetPay a été mise à jour avec succès.');
            return $this->redirectToRoute('app_admin_cinetpay_settings');
        }

        // Charger les paramètres
        $settings = [
            'cinetpay_apikey' => $settingsService->get('cinetpay_apikey', ''),
            'cinetpay_site_id' => $settingsService->get('cinetpay_site_id', ''),
            'cinetpay_secret_key' => $settingsService->get('cinetpay_secret_key', ''),
            'cinetpay_environment' => $settingsService->get('cinetpay_environment', 'test'),
            'cinetpay_currency' => $settingsService->get('cinetpay_currency', 'XOF'),
            'cinetpay_return_url' => $settingsService->get('cinetpay_return_url', ''),
            'cinetpay_enabled' => $settingsService->get('cinetpay_enabled', true),
            'cinetpay_channels' => $settingsService->get('cinetpay_channels', 'ALL'),
            'is_configured' => !empty($settingsService->get('cinetpay_apikey')) && !empty($settingsService->get('cinetpay_site_id')),
        ];

        return $this->render('admin/settings/cinetpay.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/cinetpay/tester', name: 'app_admin_cinetpay_test', methods: ['POST'])]
    public function testCinetpay(SettingsService $settingsService): Response
    {
        try {
            $apikey = $settingsService->get('cinetpay_apikey');
            $siteId = $settingsService->get('cinetpay_site_id');

            if (empty($apikey) || empty($siteId)) {
                return $this->json([
                    'success' => false,
                    'message' => 'API Key ou Site ID manquant. Veuillez configurer vos identifiants.'
                ]);
            }

            // Test de connexion basique (vérifier que les credentials existent)
            // En production, vous pourriez faire un vrai appel API CinetPay

            return $this->json([
                'success' => true,
                'message' => 'Configuration valide. Les identifiants sont présents.',
                'config' => [
                    'apikey_length' => strlen($apikey),
                    'site_id' => $siteId,
                    'environment' => $settingsService->get('cinetpay_environment', 'test'),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/initialiser', name: 'app_admin_initialize_settings', methods: ['POST'])]
    public function initializeSettings(
        CurrencyService $currencyService,
        SettingsService $settingsService
    ): Response {
        // Initialiser les devises
        $currencyService->initializeDefaultCurrencies();

        // Restaurer les paramètres par défaut
        $settingsService->restoreDefaults();

        $this->addFlash('success', 'Les paramètres ont été initialisés avec les valeurs par défaut.');
        return $this->redirectToRoute('app_admin_settings');
    }

    /**
     * Configuration Orange SMS
     */
    #[Route('/orange-sms', name: 'app_admin_orange_sms_settings', methods: ['GET', 'POST'])]
    public function orangeSmsSettings(Request $request, SettingsService $settingsService): Response
    {
        if ($request->isMethod('POST')) {
            $newSettings = [
                'orange_sms_client_id' => $request->request->get('orange_sms_client_id'),
                'orange_sms_client_secret' => $request->request->get('orange_sms_client_secret'),
                'orange_sms_sender_name' => $request->request->get('orange_sms_sender_name'),
                'orange_sms_enabled' => $request->request->has('orange_sms_enabled'),
            ];

            foreach ($newSettings as $key => $value) {
                if ($value !== null && $value !== '') {
                    $settingsService->set($key, $value);
                }
            }

            $this->addFlash('success', 'La configuration Orange SMS a été mise à jour avec succès.');
            return $this->redirectToRoute('app_admin_orange_sms_settings');
        }

        // Charger les paramètres
        $settings = [
            'orange_sms_client_id' => $settingsService->get('orange_sms_client_id', ''),
            'orange_sms_client_secret' => $settingsService->get('orange_sms_client_secret', ''),
            'orange_sms_sender_name' => $settingsService->get('orange_sms_sender_name', 'LOKAPRO'),
            'orange_sms_enabled' => $settingsService->get('orange_sms_enabled', false),
            'is_configured' => !empty($settingsService->get('orange_sms_client_id')) && !empty($settingsService->get('orange_sms_client_secret')),
        ];

        return $this->render('admin/settings/orange_sms.html.twig', [
            'settings' => $settings,
        ]);
    }

    /**
     * Tester la configuration Orange SMS avec le système de notification Symfony
     */
    #[Route('/orange-sms/tester', name: 'app_admin_orange_sms_test', methods: ['POST'])]
    public function testOrangeSms(Request $request, TexterInterface $texter, SettingsService $settingsService, LoggerInterface $logger): Response
    {
        try {
            $clientId = $settingsService->get('orange_sms_client_id');
            $clientSecret = $settingsService->get('orange_sms_client_secret');
            $testPhone = $request->request->get('test_phone', '');
            $senderName = $settingsService->get('orange_sms_sender_name', 'LOKAPRO');

            if (empty($clientId) || empty($clientSecret)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Client ID ou Client Secret manquant. Veuillez configurer vos identifiants.'
                ]);
            }

            if (empty($testPhone)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Veuillez fournir un numéro de téléphone pour le test.'
                ]);
            }

            // Nettoyer et formater le numéro de téléphone
            $cleanPhone = preg_replace('/[^0-9]/', '', $testPhone);
            if (substr($cleanPhone, 0, 1) === '0') {
                $cleanPhone = '225' . substr($cleanPhone, 1);
            }
            if (substr($cleanPhone, 0, 3) !== '225') {
                $cleanPhone = '225' . $cleanPhone;
            }
            $formattedPhone = '+' . $cleanPhone;

            // Test d'envoi de SMS avec le système de notification Symfony
            try {
                $sms = new SmsMessage(
                    // le numéro de téléphone pour envoyer le SMS
                    $formattedPhone,
                    // le message
                    'Test SMS depuis LOKAPRO - Configuration réussie !',
                    // le numéro d'envoi (optionnel, sera défini par le transport)
                    ''
                );

                $logger->info('Tentative d\'envoi SMS', [
                    'phone' => $formattedPhone,
                    'message' => 'Test SMS depuis LOKAPRO - Configuration réussie !',
                    'transport' => 'orange-sms'
                ]);

                // Envoyer le SMS via le transport Orange SMS configuré
                $sentMessage = $texter->send($sms, 'orange-sms');

                // Vérifier si l'envoi a réussi
                if (!$sentMessage) {
                    throw new \Exception('L\'envoi du SMS a échoué - aucune réponse du service');
                }

                $messageId = $sentMessage->getMessageId() ?? 'unknown';

                $logger->info('SMS de test envoyé avec succès', [
                    'phone' => $formattedPhone,
                    'message_id' => $messageId
                ]);

                // SMS envoyé avec succès
                return $this->json([
                    'success' => true,
                    'message' => 'SMS de test envoyé avec succès ! Vérifiez le numéro ' . $testPhone,
                    'config' => [
                        'client_id_length' => strlen($clientId),
                        'client_secret_length' => strlen($clientSecret),
                        'sender_name' => $senderName,
                        'formatted_phone' => $formattedPhone,
                        'message_id' => $messageId
                    ]
                ]);

            } catch (\Exception $smsException) {
                $logger->error('Erreur lors de l\'envoi du SMS via Symfony Notifier', [
                    'error' => $smsException->getMessage(),
                    'phone' => $formattedPhone
                ]);

                // Fallback : essayer avec le service Orange SMS direct
                try {
                    $logger->info('Tentative de fallback avec OrangeSmsService direct');

                    $osms = new OrangeSmsService($settingsService);
                    $osms->setVerifyPeerSSL(false);

                    $response = $osms->getTokenFromConsumerKey();

                    if (empty($response['access_token'])) {
                        throw new \Exception("Impossible d'obtenir le token d'accès Orange SMS");
                    }

                    $result = $osms->envoyerSms($testPhone, 'Test SMS depuis LOKAPRO - Configuration réussie !', $senderName);

                    if (isset($result['error'])) {
                        throw new \Exception('Erreur Orange SMS direct : ' . $result['error']);
                    }

                    // SMS envoyé avec succès via le service direct
                    return $this->json([
                        'success' => true,
                        'message' => 'SMS de test envoyé avec succès via le service direct ! Vérifiez le numéro ' . $testPhone,
                        'config' => [
                            'client_id_length' => strlen($clientId),
                            'client_secret_length' => strlen($clientSecret),
                            'sender_name' => $senderName,
                            'formatted_phone' => $formattedPhone,
                            'method' => 'direct_service'
                        ]
                    ]);

                } catch (\Exception $fallbackException) {
                    $logger->error('Erreur également avec le service direct', [
                        'error' => $fallbackException->getMessage()
                    ]);

                    return $this->json([
                        'success' => false,
                        'message' => 'Erreur lors de l\'envoi du SMS : ' . $smsException->getMessage() . ' (Fallback: ' . $fallbackException->getMessage() . ')'
                    ]);
                }
            }

        } catch (\Exception $e) {
            $logger->error('Erreur générale lors du test Orange SMS', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Tester la génération dynamique de la DSN Orange SMS
     */
    #[Route('/orange-sms/dsn-test', name: 'app_admin_orange_sms_dsn_test', methods: ['GET'])]
    public function testOrangeSmsDsn(OrangeSmsDsnService $orangeSmsDsnService): Response
    {
        try {
            $debugInfo = $orangeSmsDsnService->getDebugInfo();

            return $this->json([
                'success' => true,
                'message' => 'DSN générée avec succès',
                'data' => $debugInfo
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la DSN : ' . $e->getMessage()
            ]);
        }
    }
}
