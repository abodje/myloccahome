<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration finale pour la gestion des impôts fonciers - Version 4.0
 * Migration complète avec toutes les tables et données de référence
 */
final class Version20250122000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Complete tax management system migration with all tables and reference data - Version 4.0';
    }

    public function up(Schema $schema): void
    {
        // Supprimer les anciennes tables si elles existent
        $this->addSql('DROP TABLE IF EXISTS tax_document');
        $this->addSql('DROP TABLE IF EXISTS dgi_form');
        $this->addSql('DROP TABLE IF EXISTS property_tax');
        $this->addSql('DROP TABLE IF EXISTS tax_declaration');
        $this->addSql('DROP TABLE IF EXISTS document_type');
        $this->addSql('DROP TABLE IF EXISTS district');
        $this->addSql('DROP TABLE IF EXISTS fiscal_category');
        $this->addSql('DROP TABLE IF EXISTS property_type');
        $this->addSql('DROP TABLE IF EXISTS tax_declaration_type');

        // Créer les tables de référence en premier
        $this->createReferenceTables();

        // Créer les tables principales
        $this->createMainTables();

        // Créer les contraintes de clés étrangères
        $this->createForeignKeys();

        // Créer les index pour optimiser les performances
        $this->createIndexes();

        // Insérer les données de référence
        $this->insertReferenceData();
    }

    public function down(Schema $schema): void
    {
        // Supprimer les données de référence
        $this->addSql('DELETE FROM document_type WHERE code IN ("TAX_NOTICE", "PAYMENT_RECEIPT", "TAX_CERTIFICATE", "DGI_CORRESPONDENCE", "DGI_FORM", "JUSTIFICATION", "LEASE_CONTRACT", "INVENTORY", "INVOICE", "RECEIPT")');
        $this->addSql('DELETE FROM district WHERE code IN ("ABJ", "BOU", "DAL", "KOR", "SAN", "YAM", "GAG", "MAN", "DIV", "ANY", "BIN", "COC", "KOU", "MAR", "PB", "TRE", "YOP", "ADJ", "ATT", "PLA", "ABO", "SON")');
        $this->addSql('DELETE FROM fiscal_category WHERE code IN ("CAT_A", "CAT_B", "CAT_C", "CAT_D")');
        $this->addSql('DELETE FROM property_type WHERE code IN ("RESIDENTIAL", "COMMERCIAL", "INDUSTRIAL", "MIXED")');
        $this->addSql('DELETE FROM tax_declaration_type WHERE code IN ("DGI-1", "DGI-2", "DGI-3", "DGI-4", "DGI-5")');

        // Supprimer les index
        $this->addSql('DROP INDEX IDX_TAX_DOCUMENT_ORG_STATUS ON tax_document');
        $this->addSql('DROP INDEX IDX_TAX_DOCUMENT_ORG_TYPE ON tax_document');
        $this->addSql('DROP INDEX IDX_DGI_FORM_ORG_STATUS ON dgi_form');
        $this->addSql('DROP INDEX IDX_DGI_FORM_ORG_TYPE ON dgi_form');
        $this->addSql('DROP INDEX IDX_PROPERTY_TAX_DISTRICT_SECTOR ON property_tax');
        $this->addSql('DROP INDEX IDX_PROPERTY_TAX_TYPE_CATEGORY ON property_tax');
        $this->addSql('DROP INDEX IDX_TAX_DECLARATION_ORG_YEAR ON tax_declaration');
        $this->addSql('DROP INDEX IDX_TAX_DECLARATION_ORG_STATUS ON tax_declaration');
        $this->addSql('DROP INDEX IDX_TAX_DECLARATION_ORG_COMPANY ON tax_declaration');

        // Supprimer les contraintes de clés étrangères
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_PROPERTY');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_DGI_FORM');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_DECLARATION');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_COMPANY');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_ORGANIZATION');

        $this->addSql('ALTER TABLE dgi_form DROP FOREIGN KEY FK_DGI_FORM_DECLARATION');
        $this->addSql('ALTER TABLE dgi_form DROP FOREIGN KEY FK_DGI_FORM_COMPANY');
        $this->addSql('ALTER TABLE dgi_form DROP FOREIGN KEY FK_DGI_FORM_ORGANIZATION');

        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_PROPERTY');
        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_DECLARATION');

        $this->addSql('ALTER TABLE tax_declaration DROP FOREIGN KEY FK_TAX_DECLARATION_COMPANY');
        $this->addSql('ALTER TABLE tax_declaration DROP FOREIGN KEY FK_TAX_DECLARATION_ORGANIZATION');

        // Supprimer les tables
        $this->addSql('DROP TABLE tax_document');
        $this->addSql('DROP TABLE dgi_form');
        $this->addSql('DROP TABLE property_tax');
        $this->addSql('DROP TABLE tax_declaration');
        $this->addSql('DROP TABLE document_type');
        $this->addSql('DROP TABLE district');
        $this->addSql('DROP TABLE fiscal_category');
        $this->addSql('DROP TABLE property_type');
        $this->addSql('DROP TABLE tax_declaration_type');
    }

    private function createReferenceTables(): void
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
    }

    private function createMainTables(): void
    {
        // Create tax_declaration table
        $this->addSql('CREATE TABLE tax_declaration (
            id INT AUTO_INCREMENT NOT NULL,
            organization_id INT NOT NULL,
            company_id INT DEFAULT NULL,
            declaration_number VARCHAR(100) NOT NULL,
            declaration_type VARCHAR(50) NOT NULL,
            tax_year VARCHAR(50) NOT NULL,
            declaration_date DATE NOT NULL,
            due_date DATE DEFAULT NULL,
            submission_date DATE DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT "Brouillon",
            total_taxable_value DECIMAL(15,2) DEFAULT NULL,
            total_tax_amount DECIMAL(15,2) DEFAULT NULL,
            penalties_amount DECIMAL(15,2) DEFAULT NULL,
            interest_amount DECIMAL(15,2) DEFAULT NULL,
            total_amount DECIMAL(15,2) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            rejection_reason LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            dgi_reference VARCHAR(255) DEFAULT NULL,
            submission_reference VARCHAR(255) DEFAULT NULL,
            INDEX IDX_TAX_DECLARATION_ORGANIZATION (organization_id),
            INDEX IDX_TAX_DECLARATION_COMPANY (company_id),
            INDEX IDX_TAX_DECLARATION_TYPE (declaration_type),
            INDEX IDX_TAX_DECLARATION_STATUS (status),
            INDEX IDX_TAX_DECLARATION_TAX_YEAR (tax_year),
            INDEX IDX_TAX_DECLARATION_DATE (declaration_date),
            INDEX IDX_TAX_DECLARATION_DUE_DATE (due_date),
            INDEX IDX_TAX_DECLARATION_NUMBER (declaration_number),
            UNIQUE INDEX UNIQ_TAX_DECLARATION_NUMBER (declaration_number, organization_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create property_tax table
        $this->addSql('CREATE TABLE property_tax (
            id INT AUTO_INCREMENT NOT NULL,
            tax_declaration_id INT NOT NULL,
            property_id INT NOT NULL,
            cadastral_reference VARCHAR(100) NOT NULL,
            taxable_value DECIMAL(15,2) NOT NULL,
            previous_taxable_value DECIMAL(15,2) DEFAULT NULL,
            revision_amount DECIMAL(15,2) DEFAULT NULL,
            tax_rate DECIMAL(5,2) DEFAULT NULL,
            tax_amount DECIMAL(15,2) NOT NULL,
            property_type VARCHAR(100) NOT NULL,
            fiscal_category VARCHAR(100) NOT NULL,
            property_address VARCHAR(255) DEFAULT NULL,
            district VARCHAR(100) DEFAULT NULL,
            sector VARCHAR(100) DEFAULT NULL,
            surface DECIMAL(10,2) DEFAULT NULL,
            construction_year VARCHAR(50) DEFAULT NULL,
            renovation_year VARCHAR(50) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            owner_name VARCHAR(100) DEFAULT NULL,
            owner_address VARCHAR(255) DEFAULT NULL,
            owner_phone VARCHAR(20) DEFAULT NULL,
            owner_email VARCHAR(255) DEFAULT NULL,
            owner_id_number VARCHAR(100) DEFAULT NULL,
            INDEX IDX_PROPERTY_TAX_DECLARATION (tax_declaration_id),
            INDEX IDX_PROPERTY_TAX_PROPERTY (property_id),
            INDEX IDX_PROPERTY_TAX_CADASTRAL (cadastral_reference),
            INDEX IDX_PROPERTY_TAX_TYPE (property_type),
            INDEX IDX_PROPERTY_TAX_CATEGORY (fiscal_category),
            INDEX IDX_PROPERTY_TAX_DISTRICT (district),
            INDEX IDX_PROPERTY_TAX_SECTOR (sector),
            INDEX IDX_PROPERTY_TAX_OWNER (owner_name),
            UNIQUE INDEX UNIQ_PROPERTY_TAX_CADASTRAL (cadastral_reference, tax_declaration_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create dgi_form table
        $this->addSql('CREATE TABLE dgi_form (
            id INT AUTO_INCREMENT NOT NULL,
            organization_id INT NOT NULL,
            company_id INT DEFAULT NULL,
            tax_declaration_id INT DEFAULT NULL,
            form_type VARCHAR(100) NOT NULL,
            form_name VARCHAR(100) NOT NULL,
            version VARCHAR(100) NOT NULL DEFAULT "1.0",
            form_data LONGTEXT NOT NULL,
            file_name VARCHAR(255) DEFAULT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            file_format VARCHAR(50) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT "Brouillon",
            tax_year DATE NOT NULL,
            due_date DATE NOT NULL,
            submission_date DATETIME DEFAULT NULL,
            dgi_reference VARCHAR(255) DEFAULT NULL,
            submission_reference VARCHAR(255) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX IDX_DGI_FORM_ORGANIZATION (organization_id),
            INDEX IDX_DGI_FORM_COMPANY (company_id),
            INDEX IDX_DGI_FORM_DECLARATION (tax_declaration_id),
            INDEX IDX_DGI_FORM_TYPE (form_type),
            INDEX IDX_DGI_FORM_STATUS (status),
            INDEX IDX_DGI_FORM_YEAR (tax_year),
            INDEX IDX_DGI_FORM_DUE_DATE (due_date),
            INDEX IDX_DGI_FORM_VERSION (version),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create tax_document table
        $this->addSql('CREATE TABLE tax_document (
            id INT AUTO_INCREMENT NOT NULL,
            organization_id INT NOT NULL,
            company_id INT DEFAULT NULL,
            tax_declaration_id INT DEFAULT NULL,
            dgi_form_id INT DEFAULT NULL,
            property_id INT DEFAULT NULL,
            document_name VARCHAR(255) NOT NULL,
            document_type VARCHAR(100) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            original_file_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(50) DEFAULT NULL,
            file_size INT DEFAULT NULL,
            file_path VARCHAR(255) NOT NULL,
            document_date DATE DEFAULT NULL,
            expiration_date DATE DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            reference VARCHAR(100) DEFAULT NULL,
            dgi_reference VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT "Actif",
            amount DECIMAL(15,2) DEFAULT NULL,
            currency VARCHAR(50) DEFAULT "FCFA",
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            uploaded_by VARCHAR(255) DEFAULT NULL,
            tags VARCHAR(255) DEFAULT NULL,
            category VARCHAR(100) DEFAULT NULL,
            INDEX IDX_TAX_DOCUMENT_ORGANIZATION (organization_id),
            INDEX IDX_TAX_DOCUMENT_COMPANY (company_id),
            INDEX IDX_TAX_DOCUMENT_DECLARATION (tax_declaration_id),
            INDEX IDX_TAX_DOCUMENT_DGI_FORM (dgi_form_id),
            INDEX IDX_TAX_DOCUMENT_PROPERTY (property_id),
            INDEX IDX_TAX_DOCUMENT_TYPE (document_type),
            INDEX IDX_TAX_DOCUMENT_STATUS (status),
            INDEX IDX_TAX_DOCUMENT_CATEGORY (category),
            INDEX IDX_TAX_DOCUMENT_DATE (document_date),
            INDEX IDX_TAX_DOCUMENT_EXPIRATION (expiration_date),
            INDEX IDX_TAX_DOCUMENT_REFERENCE (reference),
            INDEX IDX_TAX_DOCUMENT_UPLOADED_BY (uploaded_by),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    private function createForeignKeys(): void
    {
        // Add foreign key constraints avec ON DELETE CASCADE appropriés
        $this->addSql('ALTER TABLE tax_declaration
            ADD CONSTRAINT FK_TAX_DECLARATION_ORGANIZATION
            FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE tax_declaration
            ADD CONSTRAINT FK_TAX_DECLARATION_COMPANY
            FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE property_tax
            ADD CONSTRAINT FK_PROPERTY_TAX_DECLARATION
            FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE property_tax
            ADD CONSTRAINT FK_PROPERTY_TAX_PROPERTY
            FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE dgi_form
            ADD CONSTRAINT FK_DGI_FORM_ORGANIZATION
            FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE dgi_form
            ADD CONSTRAINT FK_DGI_FORM_COMPANY
            FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE dgi_form
            ADD CONSTRAINT FK_DGI_FORM_DECLARATION
            FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE tax_document
            ADD CONSTRAINT FK_TAX_DOCUMENT_ORGANIZATION
            FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE tax_document
            ADD CONSTRAINT FK_TAX_DOCUMENT_COMPANY
            FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE tax_document
            ADD CONSTRAINT FK_TAX_DOCUMENT_DECLARATION
            FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE tax_document
            ADD CONSTRAINT FK_TAX_DOCUMENT_DGI_FORM
            FOREIGN KEY (dgi_form_id) REFERENCES dgi_form (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE tax_document
            ADD CONSTRAINT FK_TAX_DOCUMENT_PROPERTY
            FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE SET NULL');
    }

    private function createIndexes(): void
    {
        // Créer des index composés pour optimiser les requêtes
        $this->addSql('CREATE INDEX IDX_TAX_DECLARATION_ORG_COMPANY ON tax_declaration (organization_id, company_id)');
        $this->addSql('CREATE INDEX IDX_TAX_DECLARATION_ORG_STATUS ON tax_declaration (organization_id, status)');
        $this->addSql('CREATE INDEX IDX_TAX_DECLARATION_ORG_YEAR ON tax_declaration (organization_id, tax_year)');

        $this->addSql('CREATE INDEX IDX_PROPERTY_TAX_TYPE_CATEGORY ON property_tax (property_type, fiscal_category)');
        $this->addSql('CREATE INDEX IDX_PROPERTY_TAX_DISTRICT_SECTOR ON property_tax (district, sector)');

        $this->addSql('CREATE INDEX IDX_DGI_FORM_ORG_TYPE ON dgi_form (organization_id, form_type)');
        $this->addSql('CREATE INDEX IDX_DGI_FORM_ORG_STATUS ON dgi_form (organization_id, status)');

        $this->addSql('CREATE INDEX IDX_TAX_DOCUMENT_ORG_TYPE ON tax_document (organization_id, document_type)');
        $this->addSql('CREATE INDEX IDX_TAX_DOCUMENT_ORG_STATUS ON tax_document (organization_id, status)');
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
