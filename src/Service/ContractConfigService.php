<?php

namespace App\Service;

use App\Entity\Settings;

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
        'contract_company_name' => 'MYLOCCA Gestion',
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
        private SettingsService $settingsService
    ) {
    }

    /**
     * Récupère la configuration complète du contrat
     */
    public function getContractConfig(): array
    {
        $config = $this->defaultConfig;

        // Récupérer les paramètres personnalisés depuis la base de données
        $appSettings = $this->settingsService->getAppSettings();

        // Mapper les paramètres de l'application vers la configuration du contrat
        if ($appSettings) {
            $config['contract_company_name'] = $appSettings['company_name'] ?? $config['contract_company_name'];
            $config['contract_company_address'] = $appSettings['company_address'] ?? $config['contract_company_address'];
            $config['contract_primary_color'] = $this->settingsService->get('app_primary_color') ?? $config['contract_primary_color'];
            $config['contract_logo_url'] = $this->settingsService->get('app_logo') ?? $config['contract_logo_url'];
        }

        return $config;
    }

    /**
     * Récupère une valeur de configuration spécifique
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $config = $this->getContractConfig();
        return $config[$key] ?? $default;
    }

    /**
     * Met à jour une valeur de configuration
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        // Sauvegarder les paramètres importants en base de données
        switch ($key) {
            case 'contract_company_name':
                $this->settingsService->set('company_name', $value);
                break;
            case 'contract_company_address':
                $this->settingsService->set('company_address', $value);
                break;
            case 'contract_primary_color':
                $this->settingsService->set('app_primary_color', $value);
                break;
            case 'contract_logo_url':
                $this->settingsService->set('app_logo', $value);
                break;
        }
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
     * Applique un thème prédéfini
     */
    public function applyTheme(string $themeName): array
    {
        $themes = $this->getAvailableThemes();

        if (!isset($themes[$themeName])) {
            throw new \InvalidArgumentException("Thème '{$themeName}' non trouvé");
        }

        $theme = $themes[$themeName];
        $config = $this->getContractConfig();

        // Appliquer les couleurs du thème
        foreach ($theme as $key => $value) {
            if ($key !== 'name') {
                $config[$key] = $value;
            }
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
}
