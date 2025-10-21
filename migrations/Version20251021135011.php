<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251021135011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contract_config (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, company_id INT DEFAULT NULL, contract_language VARCHAR(10) NOT NULL, contract_title VARCHAR(255) NOT NULL, contract_main_title VARCHAR(255) NOT NULL, contract_font_family VARCHAR(255) NOT NULL, contract_font_size VARCHAR(10) NOT NULL, contract_line_height NUMERIC(3, 1) NOT NULL, contract_text_color VARCHAR(7) NOT NULL, contract_margin VARCHAR(10) NOT NULL, contract_title_size VARCHAR(10) NOT NULL, contract_label_width VARCHAR(10) NOT NULL, contract_primary_color VARCHAR(7) NOT NULL, contract_info_bg_color VARCHAR(7) NOT NULL, contract_highlight_color VARCHAR(7) NOT NULL, contract_company_name VARCHAR(255) NOT NULL, contract_company_address LONGTEXT DEFAULT NULL, contract_logo_url LONGTEXT DEFAULT NULL, contract_section1_title VARCHAR(255) NOT NULL, contract_section2_title VARCHAR(255) NOT NULL, contract_section3_title VARCHAR(255) NOT NULL, contract_section4_title VARCHAR(255) NOT NULL, contract_section5_title VARCHAR(255) NOT NULL, contract_section6_title VARCHAR(255) NOT NULL, contract_section7_title VARCHAR(255) NOT NULL, contract_section8_title VARCHAR(255) NOT NULL, contract_landlord_title VARCHAR(255) NOT NULL, contract_tenant_title VARCHAR(255) NOT NULL, contract_signature_landlord_title VARCHAR(255) NOT NULL, contract_signature_tenant_title VARCHAR(255) NOT NULL, contract_signature_place VARCHAR(255) NOT NULL, contract_signature_landlord_text VARCHAR(255) NOT NULL, contract_signature_tenant_text VARCHAR(255) NOT NULL, contract_footer_text VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E8CACA3132C8A3DE (organization_id), INDEX IDX_E8CACA31979B1AD6 (company_id), UNIQUE INDEX UNIQ_CONTRACT_CONFIG_ORG_COMP (organization_id, company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_E8CACA3132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_E8CACA31979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE accounting_configuration CHANGE is_active is_active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE accounting_configuration RENAME INDEX uniq_accounting_config_operation_type TO UNIQ_F6E46E5A3AE0AB8');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_accounting_entry_organization TO IDX_DB6C942A32C8A3DE');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_accounting_entry_company TO IDX_DB6C942A979B1AD6');
        $this->addSql('ALTER TABLE audit_log CHANGE old_values old_values JSON DEFAULT NULL, CHANGE new_values new_values JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE company CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE email_template CHANGE available_variables available_variables JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE maintenance_request DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE menu_item CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE organization DROP registration_number, CHANGE website website VARCHAR(255) DEFAULT NULL, CHANGE settings settings JSON DEFAULT NULL, CHANGE features features JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE owner RENAME INDEX fk_cf60e67c32c8a3de TO IDX_CF60E67C32C8A3DE');
        $this->addSql('ALTER TABLE owner RENAME INDEX fk_cf60e67c979b1ad6 TO IDX_CF60E67C979B1AD6');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D32C8A3DE ON payment (organization_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D979B1AD6 ON payment (company_id)');
        $this->addSql('ALTER TABLE plan CHANGE features features JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        $this->addSql('ALTER TABLE task CHANGE parameters parameters JSON DEFAULT NULL');
        $this->addSql('DROP INDEX idx_user_country ON user');
        $this->addSql('DROP INDEX idx_user_preferred_payment_method ON user');
        $this->addSql('DROP INDEX idx_user_marital_status ON user');
        $this->addSql('ALTER TABLE user DROP country, DROP marital_status, DROP preferred_payment_method, DROP consents, CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_E8CACA3132C8A3DE');
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_E8CACA31979B1AD6');
        $this->addSql('DROP TABLE contract_config');
        $this->addSql('ALTER TABLE accounting_configuration CHANGE is_active is_active TINYINT(1) DEFAULT 1');
        $this->addSql('ALTER TABLE accounting_configuration RENAME INDEX uniq_f6e46e5a3ae0ab8 TO UNIQ_ACCOUNTING_CONFIG_OPERATION_TYPE');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_db6c942a979b1ad6 TO IDX_ACCOUNTING_ENTRY_COMPANY');
        $this->addSql('ALTER TABLE accounting_entry RENAME INDEX idx_db6c942a32c8a3de TO IDX_ACCOUNTING_ENTRY_ORGANIZATION');
        $this->addSql('ALTER TABLE audit_log CHANGE old_values old_values LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE new_values new_values LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE company CHANGE is_active is_active TINYINT(1) DEFAULT 1 COMMENT \'Whether the company is active\'');
        $this->addSql('ALTER TABLE email_template CHANGE available_variables available_variables LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE maintenance_request ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE menu_item CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE organization ADD registration_number VARCHAR(100) DEFAULT NULL COMMENT \'RCCM, SIRET, SIREN, etc.\', CHANGE website website VARCHAR(255) DEFAULT NULL COMMENT \'Organization website URL\', CHANGE settings settings LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE features features LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE owner RENAME INDEX idx_cf60e67c979b1ad6 TO FK_CF60E67C979B1AD6');
        $this->addSql('ALTER TABLE owner RENAME INDEX idx_cf60e67c32c8a3de TO FK_CF60E67C32C8A3DE');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D32C8A3DE');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D979B1AD6');
        $this->addSql('DROP INDEX IDX_6D28840D32C8A3DE ON payment');
        $this->addSql('DROP INDEX IDX_6D28840D979B1AD6 ON payment');
        $this->addSql('ALTER TABLE plan CHANGE features features LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D332C8A3DE');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE task CHANGE parameters parameters LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('ALTER TABLE `user` ADD country VARCHAR(100) DEFAULT NULL, ADD marital_status VARCHAR(50) DEFAULT NULL, ADD preferred_payment_method VARCHAR(50) DEFAULT NULL, ADD consents LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('CREATE INDEX idx_user_country ON `user` (country)');
        $this->addSql('CREATE INDEX idx_user_preferred_payment_method ON `user` (preferred_payment_method)');
        $this->addSql('CREATE INDEX idx_user_marital_status ON `user` (marital_status)');
    }
}
