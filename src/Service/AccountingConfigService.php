<?php

namespace App\Service;

use App\Entity\AccountingConfiguration;
use App\Repository\AccountingConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour gérer la configuration comptable
 */
class AccountingConfigService
{
    private AccountingConfigurationRepository $repository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AccountingConfigurationRepository $repository,
        EntityManagerInterface $entityManager
    ) {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
    }

    /**
     * Récupère la configuration pour un type d'opération
     */
    public function getConfigurationForOperation(string $operationType, ?int $organizationId = null, ?int $companyId = null): ?AccountingConfiguration
    {
        if ($organizationId || $companyId) {
            return $this->repository->findByOperationTypeAndOrganization($operationType, $organizationId, $companyId);
        }
        return $this->repository->findByOperationType($operationType);
    }

    /**
     * Récupère toutes les configurations actives
     */
    public function getAllActiveConfigurations(?int $organizationId = null, ?int $companyId = null): array
    {
        if ($organizationId || $companyId) {
            return $this->repository->findActiveConfigurationsByOrganization($organizationId, $companyId);
        }
        return $this->repository->findActiveConfigurations();
    }

    /**
     * Récupère les configurations par catégorie
     */
    public function getConfigurationsByCategory(string $category, ?int $organizationId = null, ?int $companyId = null): array
    {
        if ($organizationId || $companyId) {
            return $this->repository->findByCategoryAndOrganization($category, $organizationId, $companyId);
        }
        return $this->repository->findByCategory($category);
    }

    /**
     * Crée ou met à jour une configuration
     */
    public function saveConfiguration(AccountingConfiguration $configuration): void
    {
        $configuration->setUpdatedAt(new \DateTime());
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }

    /**
     * Supprime une configuration
     */
    public function deleteConfiguration(AccountingConfiguration $configuration): void
    {
        $this->entityManager->remove($configuration);
        $this->entityManager->flush();
    }

    /**
     * Désactive une configuration
     */
    public function deactivateConfiguration(AccountingConfiguration $configuration): void
    {
        $configuration->setIsActive(false);
        $configuration->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
    }

    /**
     * Active une configuration
     */
    public function activateConfiguration(AccountingConfiguration $configuration): void
    {
        $configuration->setIsActive(true);
        $configuration->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
    }

    /**
     * Crée les configurations par défaut
     */
    public function createDefaultConfigurations(): void
    {
        $defaultConfigs = [
            [
                'operationType' => 'LOYER_ATTENDU',
                'accountNumber' => '411000',
                'accountLabel' => 'Clients - Loyers attendus',
                'entryType' => 'CREDIT',
                'description' => 'Loyer généré automatiquement',
                'reference' => 'LOYER-GEN-',
                'category' => 'LOYER',
                'notes' => 'Configuration pour les loyers générés automatiquement'
            ],
            [
                'operationType' => 'LOYER_PAYE',
                'accountNumber' => '706000',
                'accountLabel' => 'Produits - Loyers encaissés',
                'entryType' => 'CREDIT',
                'description' => 'Loyer encaissé',
                'reference' => 'LOYER-PAYE-',
                'category' => 'LOYER',
                'notes' => 'Configuration pour les loyers payés'
            ],
            [
                'operationType' => 'CHARGE',
                'accountNumber' => '625000',
                'accountLabel' => 'Charges - Charges locatives',
                'entryType' => 'DEBIT',
                'description' => 'Charge locative',
                'reference' => 'CHARGE-',
                'category' => 'CHARGE',
                'notes' => 'Configuration pour les charges locatives'
            ],
            [
                'operationType' => 'TRAVAUX',
                'accountNumber' => '628000',
                'accountLabel' => 'Charges - Travaux de maintenance',
                'entryType' => 'DEBIT',
                'description' => 'Travaux de maintenance',
                'reference' => 'TRAVAUX-',
                'category' => 'TRAVAUX',
                'notes' => 'Configuration pour les travaux de maintenance'
            ],
            [
                'operationType' => 'ASSURANCE',
                'accountNumber' => '616000',
                'accountLabel' => 'Charges - Assurances',
                'entryType' => 'DEBIT',
                'description' => 'Prime d\'assurance',
                'reference' => 'ASSURANCE-',
                'category' => 'ASSURANCE',
                'notes' => 'Configuration pour les primes d\'assurance'
            ],
            [
                'operationType' => 'TAXE_FONCIERE',
                'accountNumber' => '635000',
                'accountLabel' => 'Charges - Taxes foncières',
                'entryType' => 'DEBIT',
                'description' => 'Taxe foncière',
                'reference' => 'TAXE-',
                'category' => 'TAXE',
                'notes' => 'Configuration pour les taxes foncières'
            ],
            [
                'operationType' => 'CAUTION_RESTITUEE',
                'accountNumber' => '411000',
                'accountLabel' => 'Clients - Caution restituée',
                'entryType' => 'DEBIT',
                'description' => 'Restitution de caution',
                'reference' => 'CAUTION-REST-',
                'category' => 'CAUTION',
                'notes' => 'Configuration pour la restitution de caution'
            ],
            [
                'operationType' => 'CAUTION_ENCAISSEE',
                'accountNumber' => '419000',
                'accountLabel' => 'Clients - Caution en attente',
                'entryType' => 'CREDIT',
                'description' => 'Caution encaissée',
                'reference' => 'CAUTION-ENC-',
                'category' => 'CAUTION',
                'notes' => 'Configuration pour les cautions encaissées'
            ],
            [
                'operationType' => 'QUITTANCE_LOYER',
                'accountNumber' => '706000',
                'accountLabel' => 'Produits - Quittances de loyer',
                'entryType' => 'CREDIT',
                'description' => 'Quittance de loyer générée',
                'reference' => 'QUITTANCE-',
                'category' => 'LOYER',
                'notes' => 'Configuration pour les quittances de loyer'
            ],
            [
                'operationType' => 'AVIS_ECHEANCE',
                'accountNumber' => '411000',
                'accountLabel' => 'Clients - Avis d\'échéance',
                'entryType' => 'CREDIT',
                'description' => 'Avis d\'échéance généré',
                'reference' => 'AVIS-',
                'category' => 'LOYER',
                'notes' => 'Configuration pour les avis d\'échéance'
            ]
        ];

        foreach ($defaultConfigs as $configData) {
            $existingConfig = $this->repository->findByOperationType($configData['operationType']);

            if (!$existingConfig) {
                $config = new AccountingConfiguration();
                $config->setOperationType($configData['operationType'])
                       ->setAccountNumber($configData['accountNumber'])
                       ->setAccountLabel($configData['accountLabel'])
                       ->setEntryType($configData['entryType'])
                       ->setDescription($configData['description'])
                       ->setReference($configData['reference'])
                       ->setCategory($configData['category'])
                       ->setNotes($configData['notes'])
                       ->setIsActive(true);

                $this->entityManager->persist($config);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Valide qu'une configuration est complète
     */
    public function validateConfiguration(AccountingConfiguration $configuration): array
    {
        $errors = [];

        if (empty($configuration->getOperationType())) {
            $errors[] = 'Le type d\'opération est requis';
        }

        if (empty($configuration->getAccountNumber())) {
            $errors[] = 'Le numéro de compte est requis';
        }

        if (empty($configuration->getAccountLabel())) {
            $errors[] = 'Le libellé du compte est requis';
        }

        if (!in_array($configuration->getEntryType(), ['CREDIT', 'DEBIT'])) {
            $errors[] = 'Le sens de l\'écriture doit être CREDIT ou DEBIT';
        }

        if (empty($configuration->getCategory())) {
            $errors[] = 'La catégorie est requise';
        }

        return $errors;
    }

    /**
     * Récupère les types d'opérations disponibles
     */
    public function getAvailableOperationTypes(): array
    {
        return [
            'LOYER_ATTENDU' => 'Loyer attendu',
            'LOYER_PAYE' => 'Loyer payé',
            'CHARGE' => 'Charge locative',
            'TRAVAUX' => 'Travaux de maintenance',
            'ASSURANCE' => 'Prime d\'assurance',
            'TAXE_FONCIERE' => 'Taxe foncière',
            'CAUTION_RESTITUEE' => 'Caution restituée',
            'CAUTION_ENCAISSEE' => 'Caution encaissée',
            'LOYER_IMPAYE' => 'Loyer impayé',
            'FRAIS_RECUPERATION' => 'Frais de récupération',
            'QUITTANCE_LOYER' => 'Quittance de loyer',
            'AVIS_ECHEANCE' => 'Avis d\'échéance'
        ];
    }

    /**
     * Récupère les catégories disponibles
     */
    public function getAvailableCategories(): array
    {
        return [
            'LOYER' => 'Loyers',
            'CHARGE' => 'Charges',
            'TRAVAUX' => 'Travaux',
            'ASSURANCE' => 'Assurances',
            'TAXE' => 'Taxes',
            'CAUTION' => 'Cautions',
            'FRAIS' => 'Frais divers'
        ];
    }

    /**
     * Récupère les types d'écriture disponibles
     */
    public function getAvailableEntryTypes(): array
    {
        return [
            'CREDIT' => 'Crédit',
            'DEBIT' => 'Débit'
        ];
    }
}
