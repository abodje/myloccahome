<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250122000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tax declaration and related entities';
    }

    public function up(Schema $schema): void
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
            status VARCHAR(50) NOT NULL,
            total_taxable_value DECIMAL(15,2) DEFAULT NULL,
            total_tax_amount DECIMAL(15,2) DEFAULT NULL,
            penalties_amount DECIMAL(15,2) DEFAULT NULL,
            interest_amount DECIMAL(15,2) DEFAULT NULL,
            total_amount DECIMAL(15,2) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            rejection_reason LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            dgi_reference VARCHAR(255) DEFAULT NULL,
            submission_reference VARCHAR(255) DEFAULT NULL,
            INDEX IDX_TAX_DECLARATION_ORGANIZATION (organization_id),
            INDEX IDX_TAX_DECLARATION_COMPANY (company_id),
            INDEX IDX_TAX_DECLARATION_TYPE (declaration_type),
            INDEX IDX_TAX_DECLARATION_STATUS (status),
            INDEX IDX_TAX_DECLARATION_TAX_YEAR (tax_year),
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
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
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
            version VARCHAR(100) NOT NULL,
            form_data LONGTEXT NOT NULL,
            file_name VARCHAR(255) DEFAULT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            file_format VARCHAR(50) DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            tax_year DATE NOT NULL,
            due_date DATE NOT NULL,
            submission_date DATETIME DEFAULT NULL,
            dgi_reference VARCHAR(255) DEFAULT NULL,
            submission_reference VARCHAR(255) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_DGI_FORM_ORGANIZATION (organization_id),
            INDEX IDX_DGI_FORM_COMPANY (company_id),
            INDEX IDX_DGI_FORM_DECLARATION (tax_declaration_id),
            INDEX IDX_DGI_FORM_TYPE (form_type),
            INDEX IDX_DGI_FORM_STATUS (status),
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
            status VARCHAR(50) NOT NULL,
            amount DECIMAL(15,2) DEFAULT NULL,
            currency VARCHAR(50) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
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
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE tax_declaration ADD CONSTRAINT FK_TAX_DECLARATION_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE tax_declaration ADD CONSTRAINT FK_TAX_DECLARATION_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');

        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_DECLARATION FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id)');

        $this->addSql('ALTER TABLE dgi_form ADD CONSTRAINT FK_DGI_FORM_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE dgi_form ADD CONSTRAINT FK_DGI_FORM_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE dgi_form ADD CONSTRAINT FK_DGI_FORM_DECLARATION FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id)');

        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_DECLARATION FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id)');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_DGI_FORM FOREIGN KEY (dgi_form_id) REFERENCES dgi_form (id)');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraints first
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

        // Drop tables
        $this->addSql('DROP TABLE tax_document');
        $this->addSql('DROP TABLE dgi_form');
        $this->addSql('DROP TABLE property_tax');
        $this->addSql('DROP TABLE tax_declaration');
    }
}
