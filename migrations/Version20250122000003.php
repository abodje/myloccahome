<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour les tables de référence du système fiscal
 * Crée les tables de référence pour les types, catégories et districts
 */
final class Version20250122000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reference tables for tax management system';
    }

    public function up(Schema $schema): void
    {
        // Create tax_declaration_type table
        $this->addSql('CREATE TABLE tax_declaration_type (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_TAX_DECLARATION_TYPE_CODE (code),
            INDEX IDX_TAX_DECLARATION_TYPE_ACTIVE (is_active),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create property_type table
        $this->addSql('CREATE TABLE property_type (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_PROPERTY_TYPE_CODE (code),
            INDEX IDX_PROPERTY_TYPE_ACTIVE (is_active),
            INDEX IDX_PROPERTY_TYPE_TAX_RATE (tax_rate),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create fiscal_category table
        $this->addSql('CREATE TABLE fiscal_category (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            min_value DECIMAL(15,2) DEFAULT NULL,
            max_value DECIMAL(15,2) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_FISCAL_CATEGORY_CODE (code),
            INDEX IDX_FISCAL_CATEGORY_ACTIVE (is_active),
            INDEX IDX_FISCAL_CATEGORY_MIN_VALUE (min_value),
            INDEX IDX_FISCAL_CATEGORY_MAX_VALUE (max_value),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create district table
        $this->addSql('CREATE TABLE district (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(10) NOT NULL,
            name VARCHAR(100) NOT NULL,
            region VARCHAR(100) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_DISTRICT_CODE (code),
            INDEX IDX_DISTRICT_ACTIVE (is_active),
            INDEX IDX_DISTRICT_REGION (region),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create document_type table
        $this->addSql('CREATE TABLE document_type (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            is_required TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_DOCUMENT_TYPE_CODE (code),
            INDEX IDX_DOCUMENT_TYPE_ACTIVE (is_active),
            INDEX IDX_DOCUMENT_TYPE_REQUIRED (is_required),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Insérer les données de référence
        $this->insertReferenceData();
    }

    public function down(Schema $schema): void
    {
        // Supprimer les tables de référence
        $this->addSql('DROP TABLE document_type');
        $this->addSql('DROP TABLE district');
        $this->addSql('DROP TABLE fiscal_category');
        $this->addSql('DROP TABLE property_type');
        $this->addSql('DROP TABLE tax_declaration_type');
    }

    private function insertReferenceData(): void
    {
        // Types de déclarations fiscales
        $this->addSql("INSERT INTO tax_declaration_type (code, name, description, is_active) VALUES
            ('DGI-1', 'Déclaration des revenus fonciers', 'Déclaration des revenus fonciers pour les propriétés immobilières', 1),
            ('DGI-2', 'Déclaration des revenus commerciaux', 'Déclaration des revenus commerciaux et industriels', 1),
            ('DGI-3', 'Déclaration des revenus professionnels', 'Déclaration des revenus professionnels libéraux', 1),
            ('DGI-4', 'Déclaration des revenus de capitaux mobiliers', 'Déclaration des revenus de capitaux mobiliers', 1),
            ('DGI-5', 'Déclaration des revenus salariaux', 'Déclaration des revenus salariaux', 1)
        ");

        // Types de propriétés
        $this->addSql("INSERT INTO property_type (code, name, description, tax_rate, is_active) VALUES
            ('RESIDENTIAL', 'Résidentiel', 'Propriétés à usage résidentiel', 0.12, 1),
            ('COMMERCIAL', 'Commercial', 'Propriétés à usage commercial', 0.18, 1),
            ('INDUSTRIAL', 'Industriel', 'Propriétés à usage industriel', 0.20, 1),
            ('MIXED', 'Mixte', 'Propriétés à usage mixte', 0.15, 1)
        ");

        // Catégories fiscales
        $this->addSql("INSERT INTO fiscal_category (code, name, description, min_value, max_value, is_active) VALUES
            ('CAT_A', 'Catégorie A', 'Propriétés de luxe (> 50M FCFA)', 50000000, NULL, 1),
            ('CAT_B', 'Catégorie B', 'Propriétés moyennes (10M - 50M FCFA)', 10000000, 50000000, 1),
            ('CAT_C', 'Catégorie C', 'Propriétés standard (2M - 10M FCFA)', 2000000, 10000000, 1),
            ('CAT_D', 'Catégorie D', 'Propriétés économiques (< 2M FCFA)', 0, 2000000, 1)
        ");

        // Districts
        $this->addSql("INSERT INTO district (code, name, region, is_active) VALUES
            ('ABJ', 'Abidjan', 'Lagunes', 1),
            ('BOU', 'Bouaké', 'Vallée du Bandama', 1),
            ('DAL', 'Daloa', 'Haut-Sassandra', 1),
            ('KOR', 'Korhogo', 'Poro', 1),
            ('SAN', 'San-Pédro', 'Bas-Sassandra', 1),
            ('YAM', 'Yamoussoukro', 'Yamoussoukro', 1),
            ('GAG', 'Gagnoa', 'Gôh', 1),
            ('MAN', 'Man', 'Tonkpi', 1),
            ('DIV', 'Divo', 'Lôh-Djiboua', 1),
            ('ANY', 'Anyama', 'Lagunes', 1),
            ('BIN', 'Bingerville', 'Lagunes', 1),
            ('COC', 'Cocody', 'Lagunes', 1),
            ('KOU', 'Koumassi', 'Lagunes', 1),
            ('MAR', 'Marcory', 'Lagunes', 1),
            ('PB', 'Port-Bouët', 'Lagunes', 1),
            ('TRE', 'Treichville', 'Lagunes', 1),
            ('YOP', 'Yopougon', 'Lagunes', 1),
            ('ADJ', 'Adjamé', 'Lagunes', 1),
            ('ATT', 'Attécoubé', 'Lagunes', 1),
            ('PLA', 'Plateau', 'Lagunes', 1),
            ('ABO', 'Abobo', 'Lagunes', 1),
            ('SON', 'Songon', 'Lagunes', 1)
        ");

        // Types de documents
        $this->addSql("INSERT INTO document_type (code, name, description, is_required, is_active) VALUES
            ('TAX_NOTICE', 'Avis d\'imposition', 'Avis d\'imposition DGI', 1, 1),
            ('PAYMENT_RECEIPT', 'Quittance de paiement', 'Quittance de paiement des impôts', 1, 1),
            ('TAX_CERTIFICATE', 'Attestation fiscale', 'Attestation fiscale DGI', 0, 1),
            ('DGI_CORRESPONDENCE', 'Correspondance DGI', 'Correspondance avec la DGI', 0, 1),
            ('DGI_FORM', 'Formulaire DGI', 'Formulaire DGI pré-rempli', 0, 1),
            ('JUSTIFICATION', 'Justificatif', 'Justificatif fiscal', 0, 1),
            ('LEASE_CONTRACT', 'Contrat de bail', 'Contrat de bail', 0, 1),
            ('INVENTORY', 'État des lieux', 'État des lieux', 0, 1),
            ('INVOICE', 'Facture', 'Facture', 0, 1),
            ('RECEIPT', 'Reçu', 'Reçu de paiement', 0, 1)
        ");
    }
}
