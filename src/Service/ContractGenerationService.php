<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Lease;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;

class ContractGenerationService
{
    public function __construct(
        private PdfService $pdfService,
        private EntityManagerInterface $entityManager,
        private SettingsService $settingsService,
        private string $documentsDirectory
    ) {
    }

    /**
     * Génère automatiquement un contrat de bail après paiement de la caution
     */
    public function generateContractAfterDeposit(Payment $payment, ?\App\Entity\User $user = null): ?Document
    {
        $lease = $payment->getLease();

        // Vérifier que c'est bien un paiement de caution
        if ($payment->getType() !== 'Dépôt de garantie' && $payment->getType() !== 'Caution') {
            return null;
        }

        // Vérifier que le paiement est bien payé
        if (!$payment->isPaid()) {
            return null;
        }

        // Vérifier si un contrat n'a pas déjà été généré pour ce bail
        $existingContract = $this->entityManager->getRepository(Document::class)
            ->findOneBy([
                'lease' => $lease,
                'type' => 'Bail',
                'name' => 'Contrat de bail - ' . $lease->getId()
            ]);

        if ($existingContract) {
            return $existingContract; // Déjà généré
        }

        // Générer le PDF du contrat
        $pdfContent = $this->pdfService->generateLeaseContract($lease, false, $user);

        // Créer le nom du fichier
        $fileName = sprintf(
            'Contrat_Bail_%s_%s_%s.pdf',
            $lease->getId(),
            $lease->getTenant()->getLastName(),
            $lease->getStartDate()->format('Y-m-d')
        );

        // Sauvegarder le PDF sur le disque
        $filePath = $this->documentsDirectory . '/' . $fileName;

        // Créer le dossier si nécessaire
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($filePath, $pdfContent);

        // Créer l'entité Document
        $document = new Document();
        $document->setName('Contrat de bail - ' . $lease->getId())
                 ->setType('Bail')
                 ->setCategory('Bail')
                 ->setFilePath($fileName)
                 ->setFileSize(strlen($pdfContent))
                 ->setMimeType('application/pdf')
                 ->setLease($lease)
                 ->setTenant($lease->getTenant())
                 ->setProperty($lease->getProperty())
                 ->setDescription('Contrat de bail généré automatiquement après paiement de la caution')
                 ->setCreatedAt(new \DateTime())
                 ->setIsOfficial(true);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * Génère manuellement un contrat pour un bail
     */
    public function generateContractManually(Lease $lease, ?\App\Entity\User $user = null): Document
    {
        // Générer le PDF
        $pdfContent = $this->pdfService->generateLeaseContract($lease, false, $user);

        // Créer le nom du fichier
        $fileName = sprintf(
            'Contrat_Bail_%s_%s_%s.pdf',
            $lease->getId(),
            $lease->getTenant()->getLastName(),
            date('Y-m-d')
        );

        // Sauvegarder le PDF
        $filePath = $this->documentsDirectory . '/' . $fileName;

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($filePath, $pdfContent);

        // Créer l'entité Document
        $document = new Document();
        $document->setName('Contrat de location - ' . $lease->getTenant()->getFullName())
                 ->setType('Contrat de location')
                 ->setFileName($fileName)
                 ->setOriginalFileName($fileName)
                 ->setFileSize(strlen($pdfContent))
                 ->setMimeType('application/pdf')
                 ->setLease($lease)
                 ->setTenant($lease->getTenant())
                 ->setProperty($lease->getProperty())
                 ->setDescription('Contrat de location généré automatiquement')
                 ->setDocumentDate(new \DateTime());

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * Vérifie si tous les documents requis pour un bail sont présents
     */
    public function checkRequiredDocuments(Lease $lease): array
    {
        $requiredDocs = [
            'Bail' => false,
            'État des lieux entrée' => false,
            'Quittance caution' => false,
        ];

        $documents = $this->entityManager->getRepository(Document::class)
            ->findBy(['lease' => $lease]);

        foreach ($documents as $doc) {
            if ($doc->getType() === 'Bail' || $doc->getCategory() === 'Bail') {
                $requiredDocs['Bail'] = true;
            }
            if (str_contains($doc->getName(), 'État des lieux') || $doc->getType() === 'Inventory') {
                $requiredDocs['État des lieux entrée'] = true;
            }
            if (str_contains($doc->getName(), 'caution') || str_contains($doc->getDescription() ?? '', 'caution')) {
                $requiredDocs['Quittance caution'] = true;
            }
        }

        return $requiredDocs;
    }
}

