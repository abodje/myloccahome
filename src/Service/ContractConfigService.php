<?php

namespace App\Service;

use App\Entity\ContractConfig;
use App\Entity\Organization;
use App\Entity\Company;
use App\Entity\User;
use App\Repository\ContractConfigRepository;
use Doctrine\ORM\EntityManagerInterface;

class ContractConfigService
{
    private array $defaultConfig = [
        // Apparence
        'contract_language' => 'fr',
        'contract_title' => 'Contrat de Bail',
        'contract_main_title' => 'CONTRAT DE BAIL D\'HABITATION',
        'contract_font_family' => 'DejaVu Sans, sans-serif',
        'contract_font_size' => '11pt',
        'contract_line_height' => '1.6',
        'contract_text_color' => '#333',
        'contract_margin' => '40px',
        'contract_title_size' => '24pt',
        'contract_label_width' => '180px',

        // Couleurs
        'contract_primary_color' => '#0066cc',
        'contract_info_bg_color' => '#f5f5f5',
        'contract_highlight_color' => '#f0f8ff',

        // Entreprise
        'contract_company_name' => 'LOKAPRO Gestion',
        'contract_company_address' => '',
        'contract_logo_url' => null,

        // Titres des sections
        'contract_section_1_title' => 'ARTICLE 1 : LES PARTIES',
        'contract_section_2_title' => 'ARTICLE 2 : DÉSIGNATION DU BIEN LOUÉ',
        'contract_section_3_title' => 'ARTICLE 3 : DURÉE DU BAIL',
        'contract_section_4_title' => 'ARTICLE 4 : LOYER ET CHARGES',
        'contract_section_5_title' => 'ARTICLE 5 : DÉPÔT DE GARANTIE',
        'contract_section_6_title' => 'ARTICLE 6 : OBLIGATIONS DU LOCATAIRE',
        'contract_section_7_title' => 'ARTICLE 7 : OBLIGATIONS DU BAILLEUR',
        'contract_section_8_title' => 'ARTICLE 8 : CLAUSE RÉSOLUTOIRE',

        // Titres des parties
        'contract_landlord_title' => 'LE BAILLEUR',
        'contract_tenant_title' => 'LE LOCATAIRE',

        // Signatures
        'contract_signature_landlord_title' => 'Le Bailleur',
        'contract_signature_tenant_title' => 'Le Locataire',
        'contract_signature_place' => 'Fait à ____________',
        'contract_signature_landlord_text' => 'Signature',
        'contract_signature_tenant_text' => 'Signature précédée de la mention "Lu et approuvé"',

        // Footer
        'contract_footer_text' => 'Document généré le',
    ];

    public function __construct(
        private ContractConfigRepository $contractConfigRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère la configuration complète du contrat pour un utilisateur donné
     */
    public function getContractConfig(?User $user = null): array
    {
        if (!$user) {
            return $this->defaultConfig;
        }

        // Déterminer l'organisation et la société selon le rôle
        $organization = $user->getOrganization();
        $company = $user->getCompany();

        if (!$organization) {
            return $this->defaultConfig;
        }

        // Chercher la configuration existante
        $contractConfig = $this->contractConfigRepository->findByOrganizationAndCompany($organization, $company);

        if ($contractConfig) {
            return $contractConfig->toArray();
        }

        // Si pas de configuration trouvée, retourner les valeurs par défaut
        return $this->defaultConfig;
    }

    /**
     * Récupère la configuration complète du contrat pour une organisation/société spécifique
     */
    public function getContractConfigForOrganization(?Organization $organization, ?Company $company = null): array
    {
        if (!$organization) {
            return $this->defaultConfig;
        }

        $contractConfig = $this->contractConfigRepository->findByOrganizationAndCompany($organization, $company);

        if ($contractConfig) {
            return $contractConfig->toArray();
        }

        return $this->defaultConfig;
    }

    /**
     * Récupère une valeur de configuration spécifique pour un utilisateur
     */
    public function getConfigValue(string $key, ?User $user = null, mixed $default = null): mixed
    {
        $config = $this->getContractConfig($user);
        return $config[$key] ?? $default;
    }

    /**
     * Met à jour une valeur de configuration pour un utilisateur
     */
    public function setConfigValue(string $key, mixed $value, ?User $user = null): void
    {
        if (!$user || !$user->getOrganization()) {
            return;
        }

        $organization = $user->getOrganization();
        $company = $user->getCompany();

        // Trouver ou créer la configuration
        $contractConfig = $this->contractConfigRepository->findOrCreateForOrganizationAndCompany($organization, $company);

        // Mettre à jour la valeur
        $method = 'set' . str_replace('_', '', ucwords($key, '_'));
        if (method_exists($contractConfig, $method)) {
            $contractConfig->$method($value);
            $contractConfig->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($contractConfig);
            $this->entityManager->flush();
        }
    }

    /**
     * Met à jour toute la configuration pour un utilisateur
     */
    public function updateContractConfig(array $configData, ?User $user = null): bool
    {
        if (!$user || !$user->getOrganization()) {
            return false;
        }

        $organization = $user->getOrganization();
        $company = $user->getCompany();

        // Trouver ou créer la configuration
        $contractConfig = $this->contractConfigRepository->findOrCreateForOrganizationAndCompany($organization, $company);

        // Mettre à jour toutes les valeurs
        $contractConfig->fromArray($configData);

        $this->entityManager->persist($contractConfig);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Récupère les thèmes prédéfinis
     */
    public function getAvailableThemes(): array
    {
        return [
            'default' => [
                'name' => 'Par défaut',
                'contract_primary_color' => '#0066cc',
                'contract_info_bg_color' => '#f5f5f5',
                'contract_highlight_color' => '#f0f8ff',
            ],
            'green' => [
                'name' => 'Vert',
                'contract_primary_color' => '#28a745',
                'contract_info_bg_color' => '#f0f8f0',
                'contract_highlight_color' => '#e8f5e8',
            ],
            'red' => [
                'name' => 'Rouge',
                'contract_primary_color' => '#dc3545',
                'contract_info_bg_color' => '#f8f0f0',
                'contract_highlight_color' => '#f5e8e8',
            ],
            'purple' => [
                'name' => 'Violet',
                'contract_primary_color' => '#6f42c1',
                'contract_info_bg_color' => '#f0f0f8',
                'contract_highlight_color' => '#e8e8f5',
            ],
            'orange' => [
                'name' => 'Orange',
                'contract_primary_color' => '#fd7e14',
                'contract_info_bg_color' => '#f8f4f0',
                'contract_highlight_color' => '#f5ebe8',
            ],
        ];
    }

    /**
     * Applique un thème prédéfini pour un utilisateur
     */
    public function applyTheme(string $themeName, ?User $user = null): array
    {
        $themes = $this->getAvailableThemes();

        if (!isset($themes[$themeName])) {
            throw new \InvalidArgumentException("Thème '{$themeName}' non trouvé");
        }

        $theme = $themes[$themeName];
        $config = $this->getContractConfig($user);

        // Appliquer les couleurs du thème
        foreach ($theme as $key => $value) {
            if ($key !== 'name') {
                $config[$key] = $value;
            }
        }

        // Sauvegarder le thème si un utilisateur est fourni
        if ($user) {
            $this->updateContractConfig($config, $user);
        }

        return $config;
    }

    /**
     * Valide une configuration de contrat
     */
    public function validateConfig(array $config): array
    {
        $errors = [];

        // Vérifier les couleurs hexadécimales
        $colorKeys = ['contract_primary_color', 'contract_text_color', 'contract_info_bg_color', 'contract_highlight_color'];
        foreach ($colorKeys as $key) {
            if (isset($config[$key]) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $config[$key])) {
                $errors[] = "La couleur '{$key}' doit être au format hexadécimal (#RRGGBB)";
            }
        }

        // Vérifier les tailles de police
        $sizeKeys = ['contract_font_size', 'contract_title_size'];
        foreach ($sizeKeys as $key) {
            if (isset($config[$key]) && !preg_match('/^\d+pt$/', $config[$key])) {
                $errors[] = "La taille '{$key}' doit être au format 'XXpt'";
            }
        }

        return $errors;
    }

    /**
     * Récupère toutes les configurations d'une organisation
     */
    public function getConfigurationsForOrganization(Organization $organization): array
    {
        return $this->contractConfigRepository->findByOrganization($organization);
    }

    /**
     * Récupère toutes les configurations d'une société
     */
    public function getConfigurationsForCompany(Company $company): array
    {
        return $this->contractConfigRepository->findByCompany($company);
    }

    /**
     * Supprime une configuration
     */
    public function deleteConfiguration(ContractConfig $config): void
    {
        $this->entityManager->remove($config);
        $this->entityManager->flush();
    }

    /**
     * Crée une configuration par défaut pour une organisation
     */
    public function createDefaultConfiguration(Organization $organization, ?Company $company = null): ContractConfig
    {
        if ($company) {
            return $this->contractConfigRepository->createForCompany($organization, $company);
        } else {
            return $this->contractConfigRepository->createDefaultForOrganization($organization);
        }
    }

    /**
     * Duplique une configuration vers une autre organisation/société
     */
    public function duplicateConfiguration(ContractConfig $sourceConfig, Organization $targetOrganization, ?Company $targetCompany = null): ContractConfig
    {
        $newConfig = new ContractConfig();
        $newConfig->setOrganization($targetOrganization);
        $newConfig->setCompany($targetCompany);

        // Copier toutes les propriétés sauf l'organisation et la société
        $newConfig->fromArray($sourceConfig->toArray());

        $this->entityManager->persist($newConfig);
        $this->entityManager->flush();

        return $newConfig;
    }
}
