<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Entity\Settings;
use App\Form\CurrencyType;
use App\Repository\CurrencyRepository;
use App\Repository\SettingsRepository;
use App\Service\CurrencyService;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
                'email_from_name' => $settingsService->get('email_from_name', 'MYLOCCA'),
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
}
