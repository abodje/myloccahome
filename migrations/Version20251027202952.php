<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027202952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dgi_form DROP FOREIGN KEY FK_DGI_FORM_COMPANY');
        $this->addSql('ALTER TABLE dgi_form DROP FOREIGN KEY FK_DGI_FORM_DECLARATION');
        $this->addSql('ALTER TABLE dgi_form DROP FOREIGN KEY FK_DGI_FORM_ORGANIZATION');
        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_ORGANIZATION');
        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_COMPANY');
        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_PROPERTY');
        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_DECLARATION');
        $this->addSql('ALTER TABLE tax_declaration DROP FOREIGN KEY FK_TAX_DECLARATION_COMPANY');
        $this->addSql('ALTER TABLE tax_declaration DROP FOREIGN KEY FK_TAX_DECLARATION_ORGANIZATION');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_DGI_FORM');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_COMPANY');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_ORGANIZATION');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_DECLARATION');
        $this->addSql('ALTER TABLE tax_document DROP FOREIGN KEY FK_TAX_DOCUMENT_PROPERTY');
        $this->addSql('DROP TABLE dgi_form');
        $this->addSql('DROP TABLE district');
        $this->addSql('DROP TABLE document_type');
        $this->addSql('DROP TABLE fiscal_category');
        $this->addSql('DROP TABLE property_tax');
        $this->addSql('DROP TABLE property_type');
        $this->addSql('DROP TABLE tax_declaration');
        $this->addSql('DROP TABLE tax_declaration_type');
        $this->addSql('DROP TABLE tax_document');
        $this->addSql('ALTER TABLE accounting_configuration CHANGE is_active is_active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE accounting_configuration RENAME INDEX uniq_accounting_config_operation_type TO UNIQ_F6E46E5A3AE0AB8');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_accounting_entry_organization TO IDX_DB6C942A32C8A3DE');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_accounting_entry_company TO IDX_DB6C942A979B1AD6');
        $this->addSql('ALTER TABLE audit_log CHANGE old_values old_values JSON DEFAULT NULL, CHANGE new_values new_values JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE company CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_E8CACA3132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_E8CACA31979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE email_template CHANGE available_variables available_variables JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE environment CHANGE configuration configuration JSON DEFAULT NULL, CHANGE environment_variables environment_variables JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C7749532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_E6C7749532C8A3DE ON lease (organization_id)');
        $this->addSql('CREATE INDEX IDX_E6C77495979B1AD6 ON lease (company_id)');
        $this->addSql('ALTER TABLE maintenance_request DROP company_id, CHANGE organization_id organization_id INT NOT NULL');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_4261CA0D32C8A3DE ON maintenance_request (organization_id)');
        $this->addSql('ALTER TABLE menu_item CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE organization DROP registration_number, CHANGE website website VARCHAR(255) DEFAULT NULL, CHANGE settings settings JSON DEFAULT NULL, CHANGE features features JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE owner RENAME INDEX fk_cf60e67c32c8a3de TO IDX_CF60E67C32C8A3DE');
        $this->addSql('ALTER TABLE owner RENAME INDEX fk_cf60e67c979b1ad6 TO IDX_CF60E67C979B1AD6');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D32C8A3DE ON payment (organization_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D979B1AD6 ON payment (company_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_lease_date_type ON payment (lease_id, due_date, type)');
        $this->addSql('ALTER TABLE plan CHANGE features features JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription CHANGE metadata metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE task CHANGE parameters parameters JSON DEFAULT NULL');
        $this->addSql('DROP INDEX idx_user_marital_status ON user');
        $this->addSql('DROP INDEX idx_user_country ON user');
        $this->addSql('DROP INDEX idx_user_preferred_payment_method ON user');
        $this->addSql('ALTER TABLE user DROP country, DROP marital_status, DROP preferred_payment_method, CHANGE roles roles JSON NOT NULL, CHANGE consents consents JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dgi_form (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, company_id INT DEFAULT NULL, tax_declaration_id INT DEFAULT NULL, form_type VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, form_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, version VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'1.0\' NOT NULL COLLATE `utf8mb4_unicode_ci`, form_data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, file_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, file_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, file_format VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'Brouillon\' NOT NULL COLLATE `utf8mb4_unicode_ci`, tax_year DATE NOT NULL, due_date DATE NOT NULL, submission_date DATETIME DEFAULT NULL, dgi_reference VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, submission_reference VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DGI_FORM_ORG_STATUS (organization_id, status), INDEX IDX_DGI_FORM_ORGANIZATION (organization_id), INDEX IDX_DGI_FORM_DECLARATION (tax_declaration_id), INDEX IDX_DGI_FORM_STATUS (status), INDEX IDX_DGI_FORM_DUE_DATE (due_date), INDEX IDX_DGI_FORM_ORG_TYPE (organization_id, form_type), INDEX IDX_DGI_FORM_COMPANY (company_id), INDEX IDX_DGI_FORM_TYPE (form_type), INDEX IDX_DGI_FORM_YEAR (tax_year), INDEX IDX_DGI_FORM_VERSION (version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE district (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, region VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DISTRICT_ACTIVE (is_active), UNIQUE INDEX UNIQ_DISTRICT_CODE (code), INDEX IDX_DISTRICT_REGION (region), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE document_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_required TINYINT(1) DEFAULT 0 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DOCUMENT_TYPE_ACTIVE (is_active), UNIQUE INDEX UNIQ_DOCUMENT_TYPE_CODE (code), INDEX IDX_DOCUMENT_TYPE_REQUIRED (is_required), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE fiscal_category (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, min_value NUMERIC(15, 2) DEFAULT NULL, max_value NUMERIC(15, 2) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_FISCAL_CATEGORY_CODE (code), INDEX IDX_FISCAL_CATEGORY_MIN_VALUE (min_value), INDEX IDX_FISCAL_CATEGORY_MAX_VALUE (max_value), INDEX IDX_FISCAL_CATEGORY_ACTIVE (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE property_tax (id INT AUTO_INCREMENT NOT NULL, tax_declaration_id INT NOT NULL, property_id INT NOT NULL, organization_id INT NOT NULL, company_id INT DEFAULT NULL, cadastral_reference VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, taxable_value NUMERIC(15, 2) NOT NULL, previous_taxable_value NUMERIC(15, 2) DEFAULT NULL, revision_amount NUMERIC(15, 2) DEFAULT NULL, tax_rate NUMERIC(5, 2) DEFAULT NULL, tax_amount NUMERIC(15, 2) NOT NULL, property_type VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, fiscal_category VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, property_address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, district VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, sector VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, surface NUMERIC(10, 2) DEFAULT NULL, construction_year VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, renovation_year VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, owner_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, owner_address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, owner_phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, owner_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, owner_id_number VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_PROPERTY_TAX_PROPERTY (property_id), INDEX IDX_PROPERTY_TAX_COMPANY (company_id), INDEX IDX_PROPERTY_TAX_TYPE (property_type), INDEX IDX_PROPERTY_TAX_DISTRICT (district), INDEX IDX_PROPERTY_TAX_OWNER (owner_name), INDEX IDX_PROPERTY_TAX_DECLARATION (tax_declaration_id), INDEX IDX_PROPERTY_TAX_ORGANIZATION (organization_id), INDEX IDX_PROPERTY_TAX_CADASTRAL (cadastral_reference), INDEX IDX_PROPERTY_TAX_CATEGORY (fiscal_category), INDEX IDX_PROPERTY_TAX_SECTOR (sector), INDEX IDX_PROPERTY_TAX_TYPE_CATEGORY (property_type, fiscal_category), UNIQUE INDEX UNIQ_PROPERTY_TAX_CADASTRAL (cadastral_reference, tax_declaration_id), INDEX IDX_PROPERTY_TAX_DISTRICT_SECTOR (district, sector), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE property_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, tax_rate NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_PROPERTY_TYPE_CODE (code), INDEX IDX_PROPERTY_TYPE_TAX_RATE (tax_rate), INDEX IDX_PROPERTY_TYPE_ACTIVE (is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tax_declaration (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, company_id INT DEFAULT NULL, declaration_number VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, declaration_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, tax_year VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, declaration_date DATE NOT NULL, due_date DATE DEFAULT NULL, submission_date DATE DEFAULT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'Brouillon\' NOT NULL COLLATE `utf8mb4_unicode_ci`, total_taxable_value NUMERIC(15, 2) DEFAULT NULL, total_tax_amount NUMERIC(15, 2) DEFAULT NULL, penalties_amount NUMERIC(15, 2) DEFAULT NULL, interest_amount NUMERIC(15, 2) DEFAULT NULL, total_amount NUMERIC(15, 2) DEFAULT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, rejection_reason LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, dgi_reference VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, submission_reference VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_TAX_DECLARATION_ORG_COMPANY (organization_id, company_id), UNIQUE INDEX UNIQ_TAX_DECLARATION_NUMBER (declaration_number, organization_id), INDEX IDX_TAX_DECLARATION_ORG_STATUS (organization_id, status), INDEX IDX_TAX_DECLARATION_ORGANIZATION (organization_id), INDEX IDX_TAX_DECLARATION_ORG_YEAR (organization_id, tax_year), INDEX IDX_TAX_DECLARATION_TYPE (declaration_type), INDEX IDX_TAX_DECLARATION_TAX_YEAR (tax_year), INDEX IDX_TAX_DECLARATION_DUE_DATE (due_date), INDEX IDX_TAX_DECLARATION_NUMBER (declaration_number), INDEX IDX_TAX_DECLARATION_COMPANY (company_id), INDEX IDX_TAX_DECLARATION_STATUS (status), INDEX IDX_TAX_DECLARATION_DATE (declaration_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tax_declaration_type (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_TAX_DECLARATION_TYPE_ACTIVE (is_active), UNIQUE INDEX UNIQ_TAX_DECLARATION_TYPE_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tax_document (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, company_id INT DEFAULT NULL, tax_declaration_id INT DEFAULT NULL, dgi_form_id INT DEFAULT NULL, property_id INT DEFAULT NULL, document_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, document_type VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, file_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, original_file_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, mime_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, file_size INT DEFAULT NULL, file_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, document_date DATE DEFAULT NULL, expiration_date DATE DEFAULT NULL, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, reference VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, dgi_reference VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'Actif\' NOT NULL COLLATE `utf8mb4_unicode_ci`, amount NUMERIC(15, 2) DEFAULT NULL, currency VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'FCFA\' COLLATE `utf8mb4_unicode_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, uploaded_by VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, tags VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, category VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_TAX_DOCUMENT_ORGANIZATION (organization_id), INDEX IDX_TAX_DOCUMENT_ORG_TYPE (organization_id, document_type), INDEX IDX_TAX_DOCUMENT_DECLARATION (tax_declaration_id), INDEX IDX_TAX_DOCUMENT_ORG_STATUS (organization_id, status), INDEX IDX_TAX_DOCUMENT_PROPERTY (property_id), INDEX IDX_TAX_DOCUMENT_STATUS (status), INDEX IDX_TAX_DOCUMENT_DATE (document_date), INDEX IDX_TAX_DOCUMENT_REFERENCE (reference), INDEX IDX_TAX_DOCUMENT_COMPANY (company_id), INDEX IDX_TAX_DOCUMENT_DGI_FORM (dgi_form_id), INDEX IDX_TAX_DOCUMENT_TYPE (document_type), INDEX IDX_TAX_DOCUMENT_CATEGORY (category), INDEX IDX_TAX_DOCUMENT_EXPIRATION (expiration_date), INDEX IDX_TAX_DOCUMENT_UPLOADED_BY (uploaded_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE dgi_form ADD CONSTRAINT FK_DGI_FORM_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE dgi_form ADD CONSTRAINT FK_DGI_FORM_DECLARATION FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE dgi_form ADD CONSTRAINT FK_DGI_FORM_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_DECLARATION FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tax_declaration ADD CONSTRAINT FK_TAX_DECLARATION_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tax_declaration ADD CONSTRAINT FK_TAX_DECLARATION_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_DGI_FORM FOREIGN KEY (dgi_form_id) REFERENCES dgi_form (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_COMPANY FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_DECLARATION FOREIGN KEY (tax_declaration_id) REFERENCES tax_declaration (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE tax_document ADD CONSTRAINT FK_TAX_DOCUMENT_PROPERTY FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE accounting_configuration CHANGE is_active is_active TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE accounting_configuration RENAME INDEX uniq_f6e46e5a3ae0ab8 TO UNIQ_ACCOUNTING_CONFIG_OPERATION_TYPE');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_db6c942a979b1ad6 TO IDX_ACCOUNTING_ENTRY_COMPANY');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_db6c942a32c8a3de TO IDX_ACCOUNTING_ENTRY_ORGANIZATION');
        $this->addSql('ALTER TABLE audit_log CHANGE old_values old_values LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE new_values new_values LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE company CHANGE is_active is_active TINYINT(1) DEFAULT 1 COMMENT \'Whether the company is active\'');
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_E8CACA3132C8A3DE');
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_E8CACA31979B1AD6');
        $this->addSql('ALTER TABLE email_template CHANGE available_variables available_variables LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE environment CHANGE configuration configuration LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE environment_variables environment_variables LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C7749532C8A3DE');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495979B1AD6');
        $this->addSql('DROP INDEX IDX_E6C7749532C8A3DE ON lease');
        $this->addSql('DROP INDEX IDX_E6C77495979B1AD6 ON lease');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D32C8A3DE');
        $this->addSql('DROP INDEX IDX_4261CA0D32C8A3DE ON maintenance_request');
        $this->addSql('ALTER TABLE maintenance_request ADD company_id INT DEFAULT NULL, CHANGE organization_id organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE menu_item CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE organization ADD registration_number VARCHAR(100) DEFAULT NULL COMMENT \'RCCM, SIRET, SIREN, etc.\', CHANGE website website VARCHAR(255) DEFAULT NULL COMMENT \'Organization website URL\', CHANGE settings settings LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE features features LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE owner RENAME INDEX idx_cf60e67c32c8a3de TO FK_CF60E67C32C8A3DE');
        $this->addSql('ALTER TABLE owner RENAME INDEX idx_cf60e67c979b1ad6 TO FK_CF60E67C979B1AD6');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D32C8A3DE');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D979B1AD6');
        $this->addSql('DROP INDEX IDX_6D28840D32C8A3DE ON payment');
        $this->addSql('DROP INDEX IDX_6D28840D979B1AD6 ON payment');
        $this->addSql('DROP INDEX unique_lease_date_type ON payment');
        $this->addSql('ALTER TABLE plan CHANGE features features LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE subscription CHANGE metadata metadata LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE task CHANGE parameters parameters LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `user` ADD country VARCHAR(100) DEFAULT NULL, ADD marital_status VARCHAR(50) DEFAULT NULL, ADD preferred_payment_method VARCHAR(50) DEFAULT NULL, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE consents consents LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('CREATE INDEX idx_user_marital_status ON `user` (marital_status)');
        $this->addSql('CREATE INDEX idx_user_country ON `user` (country)');
        $this->addSql('CREATE INDEX idx_user_preferred_payment_method ON `user` (preferred_payment_method)');
    }
}
