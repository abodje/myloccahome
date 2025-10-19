<?php

namespace App\Controller;

use App\Entity\OnlinePayment;
use App\Entity\Payment;
use App\Entity\AdvancePayment;
use App\Repository\OnlinePaymentRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Service\CinetPayService;
use App\Service\AdvancePaymentService;
use App\Service\AccountingService;
use App\Service\OrangeSmsService;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/paiement-en-ligne')]
class OnlinePaymentController extends AbstractController
{
    #[Route('/', name: 'app_online_payment_index', methods: ['GET'])]
    public function index(OnlinePaymentRepository $repository): Response
    {
        $transactions = $repository->findBy([], ['createdAt' => 'DESC'], 20);
        $stats = $repository->getStatistics();

        return $this->render('online_payment/index.html.twig', [
            'transactions' => $transactions,
            'stats' => $stats,
        ]);
    }

    #[Route('/paiement/{id}', name: 'app_online_payment_tenant_page', methods: ['GET'])]
    public function tenantPaymentPage(Payment $payment): Response
    {
        return $this->render('online_payment/tenant_payment.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/payer-loyer/{id}', name: 'app_online_payment_pay_rent', methods: ['GET', 'POST'])]
    public function payRent(
        Payment $payment,
        Request $request,
        CinetPayService $cinetpay,
        EntityManagerInterface $em
    ): Response {
        // Vérifier que le paiement n'est pas déjà payé
        if ($payment->getStatus() === 'Payé') {
            $this->addFlash('info', 'Ce loyer a déjà été payé');
            return $this->redirectToRoute('app_payment_show', ['id' => $payment->getId()]);
        }

        // Accepter GET et POST
        if ($request->isMethod('POST') || $request->query->has('method')) {
            try {
                $tenant = $payment->getLease()->getTenant();
                $transactionId = 'RENT-' . $payment->getId() . '-' . uniqid();
                $paymentMethod = $request->query->get('method', 'mobile_money');

                // Debug: Log des données de paiement
                error_log('=== DEBUG PAYMENT ===');
                error_log('Payment ID: ' . $payment->getId());
                error_log('Amount: ' . $payment->getAmount());
                error_log('Amount as int: ' . (int) $payment->getAmount());
                error_log('Tenant: ' . ($tenant ? $tenant->getFullName() : 'NULL'));
                error_log('Transaction ID: ' . $transactionId);
                error_log('Payment Method: ' . $paymentMethod);

                // Créer l'enregistrement de transaction
                $onlinePayment = new OnlinePayment();
                $onlinePayment->setTransactionId($transactionId);
                $onlinePayment->setPaymentType('rent');
                $onlinePayment->setPaymentMethod($paymentMethod);
                $onlinePayment->setCurrency('XOF');
                $onlinePayment->setProvider('CinetPay');
                $onlinePayment->setStatus('pending');
                $onlinePayment->setLease($payment->getLease());
                $onlinePayment->setPayment($payment);
                $onlinePayment->setAmount($payment->getAmount());
                $onlinePayment->setCustomerName($tenant->getFullName());
                $onlinePayment->setCustomerPhone($tenant->getPhone());
                $onlinePayment->setCustomerEmail($tenant->getEmail());

                // Configurer CinetPay
                error_log('=== CONFIGURING CINETPAY ===');
                $notifyUrl = $this->generateUrl('app_online_payment_notify', [], UrlGeneratorInterface::ABSOLUTE_URL);
                $returnUrl = $this->generateUrl('app_online_payment_return', ['transactionId' => $transactionId], UrlGeneratorInterface::ABSOLUTE_URL);

                error_log('Notify URL: ' . $notifyUrl);
                error_log('Return URL: ' . $returnUrl);

                error_log('=== SETTING CINETPAY PARAMETERS ===');
                $cinetpay
                    ->setTransactionId($transactionId)
                    ->setAmount((int) $payment->getAmount())
                    ->setDescription("Paiement loyer - Bail #{$payment->getLease()->getId()}")
                    ->setNotifyUrl($notifyUrl)
                    ->setReturnUrl($returnUrl)
                    ->setCustomer([
                        'customer_name' => $tenant->getLastName() ?? 'Locataire',
                        'customer_surname' => $tenant->getFirstName() ?? '',
                        'customer_phone_number' => $tenant->getPhone() ?? '22500000000',
                        'customer_email' => $tenant->getEmail() ?? 'noreply@mylocca.com',
                        'customer_address' => $tenant->getAddress() ?? 'Adresse',
                        'customer_city' => $tenant->getCity() ?? 'Ville',
                        'customer_country' => 'CI',
                        'customer_state' => 'AB',
                        'customer_zip_code' => $tenant->getPostalCode() ?? '00000',
                    ])
                    ->setMetadata([
                        'payment_id' => $payment->getId(),
                        'lease_id' => $payment->getLease()->getId(),
                        'type' => 'rent',
                    ]);

                $paymentUrl = $cinetpay->initPayment();

                $onlinePayment->setPaymentUrl($paymentUrl);
                $em->persist($onlinePayment);
                $em->flush();

                // Rediriger vers CinetPay
                return $this->render('online_payment/pay_rent.html.twig', [
                    'payment' => $payment,
                    'payment_url' => $paymentUrl,
                ]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'initialisation du paiement : ' . $e->getMessage());

                // Log l'erreur pour debug
                error_log('Erreur CinetPay: ' . $e->getMessage());
                error_log('Payment ID: ' . $payment->getId());
                error_log('Amount: ' . $payment->getAmount());

                // Retourner avec une URL vide pour afficher l'erreur
                return $this->render('online_payment/pay_rent.html.twig', [
                    'payment' => $payment,
                    'payment_url' => null,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return $this->render('online_payment/pay_rent.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/payer-acompte', name: 'app_online_payment_pay_advance', methods: ['GET', 'POST'])]
    public function payAdvance(
        Request $request,
        CinetPayService $cinetpay,
        LeaseRepository $leaseRepository,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            try {
                $leaseId = $request->request->get('lease_id');
                $amount = (float) $request->request->get('amount');

                $lease = $leaseRepository->find($leaseId);
                if (!$lease) {
                    throw new \Exception('Bail introuvable');
                }

                if ($amount <= 0) {
                    throw new \Exception('Le montant doit être positif');
                }

                $tenant = $lease->getTenant();
                $transactionId = 'ADV-' . $lease->getId() . '-' . uniqid();

                // Créer l'enregistrement de transaction
                $onlinePayment = new OnlinePayment();
                $onlinePayment->setTransactionId($transactionId);
                $onlinePayment->setPaymentType('advance');
                $onlinePayment->setPaymentMethod('cinetpay');
                $onlinePayment->setLease($lease);
                $onlinePayment->setAmount((string) $amount);
                $onlinePayment->setStatus('pending');
                $onlinePayment->setCustomerName($tenant->getFullName());
                $onlinePayment->setCustomerPhone($tenant->getPhone());
                $onlinePayment->setCustomerEmail($tenant->getEmail());

                // Configurer CinetPay
                $notifyUrl = $this->generateUrl('app_online_payment_notify', [], UrlGeneratorInterface::ABSOLUTE_URL);
                $returnUrl = $this->generateUrl('app_online_payment_return', ['transactionId' => $transactionId], UrlGeneratorInterface::ABSOLUTE_URL);

                $cinetpay
                    ->setTransactionId($transactionId)
                    ->setAmount($amount)
                    ->setDescription("Acompte - Bail #{$lease->getId()}")
                    ->setNotifyUrl($notifyUrl)
                    ->setReturnUrl($returnUrl)
                    ->setCustomer([
                        'customer_name' => $tenant->getLastName() ?? 'Locataire',
                        'customer_surname' => $tenant->getFirstName() ?? '',
                        'customer_phone_number' => $tenant->getPhone() ?? '22500000000',
                        'customer_email' => $tenant->getEmail() ?? 'noreply@mylocca.com',
                        'customer_address' => $tenant->getAddress() ?? 'Adresse',
                        'customer_city' => $tenant->getCity() ?? 'Ville',
                        'customer_country' => 'CI',
                        'customer_state' => 'AB',
                        'customer_zip_code' => $tenant->getPostalCode() ?? '00000',
                    ])
                    ->setMetadata([
                        'lease_id' => $lease->getId(),
                        'type' => 'advance',
                        'amount' => $amount,
                    ]);

                $paymentUrl = $cinetpay->initPayment();

                $onlinePayment->setPaymentUrl($paymentUrl);
                $em->persist($onlinePayment);
                $em->flush();

                // Redirection directe vers CinetPay
                if (!empty($paymentUrl)) {
                    return $this->redirect($paymentUrl);
                } else {
                    $this->addFlash('error', 'Erreur : URL de paiement non disponible.');
                    return $this->redirectToRoute('app_online_payment_pay_advance');
                }

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'initialisation du paiement : ' . $e->getMessage());
                return $this->redirectToRoute('app_online_payment_pay_advance');
            }
        }

        // Récupérer uniquement les baux actifs du locataire connecté
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $activeLeases = [];

        if ($user && $user->getTenant()) {
            $tenant = $user->getTenant();
            $activeLeases = $leaseRepository->findBy([
                'tenant' => $tenant,
                'status' => 'Actif'
            ]);
        }

        return $this->render('online_payment/pay_advance.html.twig', [
            'leases' => $activeLeases,
        ]);
    }

    #[Route('/notification', name: 'app_online_payment_notify', methods: ['POST'])]
    public function notification(
        Request $request,
        OnlinePaymentRepository $onlinePaymentRepo,
        CinetPayService $cinetpay,
        AdvancePaymentService $advanceService,
        AccountingService $accountingService,
        SettingsService $settingsService,
        OrangeSmsService $orangeSmsService,
        EntityManagerInterface $em
    ): Response {
        // Récupérer les données de notification
        $content = $request->getContent();
        $data = $request->request->all();
        $dataJson = json_decode($content, true);

        // Logger pour debug
        $logFile = __DIR__ . '/../../var/log/cinetpay_notifications.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST DATA: " . print_r($data, true) . "\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - JSON DATA: " . print_r($dataJson, true) . "\n", FILE_APPEND);

        // Utiliser les données POST si disponibles, sinon JSON
        $data = !empty($data) ? $data : ($dataJson ?? []);

        try {
            // 🔐 VÉRIFICATION HMAC (Sécurité CinetPay)
            $secretKey = $settingsService->get('cinetpay_secret_key', '202783455685bd868b44665.45198979');
            $receivedToken = $request->headers->get('x-token');

            // Liste des champs requis pour le token HMAC
            $requiredFields = [
                'cpm_site_id', 'cpm_trans_id', 'cpm_trans_date', 'cpm_amount', 'cpm_currency',
                'signature', 'payment_method', 'cel_phone_num', 'cpm_phone_prefixe', 'cpm_language',
                'cpm_version', 'cpm_payment_config', 'cpm_page_action', 'cpm_custom',
                'cpm_designation', 'cpm_error_message'
            ];

            // Vérifier les champs requis
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Champ manquant: $field\n", FILE_APPEND);
                    return new Response("Champ manquant : $field", 400);
                }
            }

            // Construction de la chaîne pour HMAC
            $concatenated = implode('', [
                $data['cpm_site_id'],
                $data['cpm_trans_id'],
                $data['cpm_trans_date'],
                $data['cpm_amount'],
                $data['cpm_currency'],
                $data['signature'],
                $data['payment_method'],
                $data['cel_phone_num'],
                $data['cpm_phone_prefixe'],
                $data['cpm_language'],
                $data['cpm_version'],
                $data['cpm_payment_config'],
                $data['cpm_page_action'],
                $data['cpm_custom'],
                $data['cpm_designation'],
                $data['cpm_error_message'],
            ]);

            // Générer le token HMAC
            $generatedToken = hash_hmac('sha256', $concatenated, $secretKey);

            // Vérifier la signature
            if ($receivedToken && $generatedToken !== $receivedToken) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - HMAC INVALIDE\n", FILE_APPEND);
                file_put_contents($logFile, "Expected: $generatedToken\n", FILE_APPEND);
                file_put_contents($logFile, "Received: $receivedToken\n", FILE_APPEND);
                return new Response('Signature HMAC invalide', 403);
            }

            $transactionId = $data['cpm_trans_id'];

            // Récupérer la transaction
            $onlinePayment = $onlinePaymentRepo->findByTransactionId($transactionId);

            if (!$onlinePayment) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Transaction not found: $transactionId\n", FILE_APPEND);
                return new Response('Transaction not found', 404);
            }

            // Stocker les données de notification
            $onlinePayment->setNotificationData(json_encode($data));

            // Vérifier le statut auprès de CinetPay (double vérification)
            try {
                $status = $cinetpay->checkTransactionStatus($transactionId);
                $onlinePayment->setCinetpayResponse(json_encode($status));
            } catch (\Exception $e) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erreur vérification CinetPay: " . $e->getMessage() . "\n", FILE_APPEND);
                // Continuer avec les données de notification
                $status = null;
            }

            // Vérifier si le paiement est réussi
            // CinetPay envoie "SUCCES" dans cpm_error_message quand c'est OK
            $isSuccess = ($status && $status['code'] == '00' && $status['message'] == 'SUCCES')
                      || (isset($data['cpm_error_message']) && strtoupper($data['cpm_error_message']) === 'SUCCES');

            if ($isSuccess) {
                $paymentMethod = $data['payment_method'] ?? ($status['data']['payment_method'] ?? 'ONLINE');
                $onlinePayment->markAsCompleted($paymentMethod);

                // Traiter selon le type
                if ($onlinePayment->getPaymentType() === 'rent' && $onlinePayment->getPayment()) {
                    // 💰 Paiement de loyer
                    $payment = $onlinePayment->getPayment();
                    $payment->markAsPaid(
                        new \DateTime($data['cpm_trans_date']),
                        'Paiement en ligne - ' . $paymentMethod,
                        $transactionId
                    );

                    // Ajouter les détails dans les notes
                    $payment->setNotes(sprintf(
                        "Paiement en ligne via CinetPay\nMéthode: %s\nTéléphone: %s\nDate: %s",
                        $paymentMethod,
                        $data['cel_phone_num'] ?? 'N/A',
                        $data['cpm_trans_date']
                    ));

                    // Enregistrer en comptabilité
                    $accountingService->createEntryFromPayment($payment);

                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✅ Loyer payé: Payment #{$payment->getId()}\n", FILE_APPEND);

                } elseif ($onlinePayment->getPaymentType() === 'advance') {
                    // 💰 Acompte
                    $advance = $advanceService->createAdvancePayment(
                        $onlinePayment->getLease(),
                        (float) $data['cpm_amount'],
                        'Paiement en ligne - ' . $paymentMethod,
                        $transactionId,
                        sprintf(
                            "Paiement en ligne via CinetPay\nMéthode: %s\nTéléphone: %s",
                            $paymentMethod,
                            $data['cel_phone_num'] ?? 'N/A'
                        )
                    );

                    $onlinePayment->setAdvancePayment($advance);

                    // Enregistrer en comptabilité
                    $accountingService->recordAdvancePayment($advance);

                    // Appliquer automatiquement aux paiements en attente
                    $results = $advanceService->applyAdvanceToAllPendingPayments($onlinePayment->getLease());

                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✅ Acompte créé: AdvancePayment #{$advance->getId()}\n", FILE_APPEND);
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 💰 Paiements soldés: {$results['payments_fully_paid']}\n", FILE_APPEND);
                }

                $em->flush();

                // Envoyer SMS de confirmation si activé
                if ($settingsService->get('orange_sms_enabled', false)) {
                    $this->sendPaymentConfirmationSms($onlinePayment, $orangeSmsService, $logFile);
                }

                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✅ SUCCESS: Transaction $transactionId traitée\n", FILE_APPEND);
                return new Response('OK', 200);

            } else {
                // ❌ Paiement échoué
                $onlinePayment->markAsFailed();
                $em->flush();

                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ FAILED: Transaction $transactionId échouée\n", FILE_APPEND);
                return new Response('Payment failed', 200);
            }

        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, $e->getTraceAsString() . "\n", FILE_APPEND);
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/retour/{transactionId}', name: 'app_online_payment_return', methods: ['GET', 'POST'])]
    public function returnPage(
        string $transactionId,
        OnlinePaymentRepository $onlinePaymentRepo,
        CinetPayService $cinetpay
    ): Response {
        $onlinePayment = $onlinePaymentRepo->findByTransactionId($transactionId);

        if (!$onlinePayment) {
            $this->addFlash('error', 'Transaction introuvable');
            return $this->redirectToRoute('app_payment_index');
        }

        try {
            // Vérifier le statut final
            $status = $cinetpay->checkTransactionStatus($transactionId);

            if ($status['code'] == '00' && $status['message'] == 'SUCCES') {
                $this->addFlash('success', '✅ Paiement réussi ! Votre transaction a été enregistrée.');
            } else {
                $this->addFlash('error', '❌ Le paiement a échoué ou a été annulé.');
            }
        } catch (\Exception $e) {
            $this->addFlash('warning', 'Vérification du paiement en cours...');
        }

        return $this->render('online_payment/return.html.twig', [
            'transaction' => $onlinePayment,
        ]);
    }

    #[Route('/{id}', name: 'app_online_payment_show', methods: ['GET'])]
    public function show(OnlinePayment $onlinePayment): Response
    {
        return $this->render('online_payment/show.html.twig', [
            'transaction' => $onlinePayment,
        ]);
    }

    /**
     * Envoie un SMS de confirmation de paiement
     */
    private function sendPaymentConfirmationSms(
        OnlinePayment $onlinePayment,
        OrangeSmsService $orangeSmsService,
        string $logFile
    ): void {
        try {
            $tenant = $onlinePayment->getLease()->getTenant();

            if (!$tenant->getPhone()) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ⚠️ Pas de numéro de téléphone pour {$tenant->getFullName()}\n", FILE_APPEND);
                return;
            }

            if ($onlinePayment->getPaymentType() === 'rent' && $onlinePayment->getPayment()) {
                // Confirmation de paiement de loyer
                $payment = $onlinePayment->getPayment();
                $message = sprintf(
                    "MYLOCCA: Paiement de %s bien recu le %s. Votre quittance est disponible sur mylocca.com. Merci!",
                    number_format($payment->getAmount(), 0, ',', ' ') . ' FCFA',
                    (new \DateTime())->format('d/m/Y')
                );
            } elseif ($onlinePayment->getPaymentType() === 'advance') {
                // Confirmation d'acompte
                $message = sprintf(
                    "MYLOCCA: Acompte de %s bien recu. Il sera applique automatiquement a vos prochains loyers. Merci!",
                    number_format($onlinePayment->getAmount(), 0, ',', ' ') . ' FCFA'
                );
            } else {
                return;
            }

            // Limiter à 160 caractères
            if (strlen($message) > 160) {
                $message = substr($message, 0, 157) . '...';
            }

            $result = $orangeSmsService->envoyerSms($tenant->getPhone(), $message);
          

            if (isset($result['error'])) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ Erreur SMS: {$result['error']}\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✅ SMS confirmation envoyé à {$tenant->getFullName()}\n", FILE_APPEND);
            }
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ Exception SMS: {$e->getMessage()}\n", FILE_APPEND);
        }
    }
}

