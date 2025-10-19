<?php

require_once 'vendor/autoload.php';

use App\Kernel;

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine.orm.entity_manager');
$notificationService = $container->get('App\Service\NotificationService');

echo "Test de recherche de quittances...\n";

// Trouver un paiement récent
$paymentRepository = $entityManager->getRepository(\App\Entity\Payment::class);
$payment = $paymentRepository->findOneBy(['status' => 'Payé'], ['paidDate' => 'DESC']);

if (!$payment) {
    echo "Aucun paiement payé trouvé.\n";
    exit(1);
}

echo "Paiement trouvé: #{$payment->getId()}\n";

// Tester la recherche de quittance
try {
    $tenant = $payment->getLease()->getTenant();
    $fileName = sprintf(
        'quittance_%s_%s.pdf',
        $tenant->getLastName(),
        $payment->getDueDate()->format('Y_m')
    );
    
    echo "Nom de fichier recherché: {$fileName}\n";
    
    $receipt = $entityManager->getRepository(\App\Entity\Document::class)
        ->findOneBy([
            'type' => 'Quittance de loyer',
            'fileName' => $fileName
        ]);
    
    if ($receipt) {
        echo "Quittance trouvée: {$receipt->getName()}\n";
    } else {
        echo "Aucune quittance trouvée pour ce paiement.\n";
        
        // Lister toutes les quittances disponibles
        $allReceipts = $entityManager->getRepository(\App\Entity\Document::class)
            ->findBy(['type' => 'Quittance de loyer']);
        
        echo "Quittances disponibles:\n";
        foreach ($allReceipts as $receipt) {
            echo "  - {$receipt->getFileName()} ({$receipt->getName()})\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Erreur: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
}
