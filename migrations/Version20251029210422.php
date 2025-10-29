<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251029210422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accounting_configuration (id INT AUTO_INCREMENT NOT NULL, operation_type VARCHAR(100) NOT NULL, account_number VARCHAR(20) NOT NULL, account_label VARCHAR(255) NOT NULL, entry_type VARCHAR(10) NOT NULL, description VARCHAR(255) NOT NULL, reference VARCHAR(255) DEFAULT NULL, category VARCHAR(100) NOT NULL, is_active TINYINT(1) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F6E46E5A3AE0AB8 (operation_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accounting_entry (id INT AUTO_INCREMENT NOT NULL, property_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, expense_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, entry_date DATE NOT NULL, description VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, type VARCHAR(50) NOT NULL, category VARCHAR(100) NOT NULL, reference VARCHAR(255) DEFAULT NULL, running_balance NUMERIC(10, 2) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DB6C942A549213EC (property_id), INDEX IDX_DB6C942A7E3C61F9 (owner_id), INDEX IDX_DB6C942A4C3A3BB (payment_id), INDEX IDX_DB6C942AF395DB7B (expense_id), INDEX IDX_DB6C942A32C8A3DE (organization_id), INDEX IDX_DB6C942A979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE advance_payment (id INT AUTO_INCREMENT NOT NULL, lease_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, remaining_balance NUMERIC(10, 2) NOT NULL, paid_date DATETIME NOT NULL, payment_method VARCHAR(50) NOT NULL, reference VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4245625FD3CA542C (lease_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, action VARCHAR(100) NOT NULL, entity_type VARCHAR(100) NOT NULL, entity_id INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, old_values JSON DEFAULT NULL, new_values JSON DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F6E1C0F532C8A3DE (organization_id), INDEX IDX_F6E1C0F5979B1AD6 (company_id), INDEX idx_audit_created_at (created_at), INDEX idx_audit_user (user_id), INDEX idx_audit_entity_type (entity_type), INDEX idx_audit_action (action), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, name VARCHAR(255) NOT NULL, legal_name VARCHAR(255) DEFAULT NULL, registration_number VARCHAR(100) DEFAULT NULL, tax_number VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, is_headquarter TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_demo TINYINT(1) DEFAULT 0 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_4FBF094F32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contract_config (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, company_id INT DEFAULT NULL, contract_language VARCHAR(10) NOT NULL, contract_title VARCHAR(255) NOT NULL, contract_main_title VARCHAR(255) NOT NULL, contract_font_family VARCHAR(255) NOT NULL, contract_font_size VARCHAR(10) NOT NULL, contract_line_height NUMERIC(3, 1) NOT NULL, contract_text_color VARCHAR(7) NOT NULL, contract_margin VARCHAR(10) NOT NULL, contract_title_size VARCHAR(10) NOT NULL, contract_label_width VARCHAR(10) NOT NULL, contract_primary_color VARCHAR(7) NOT NULL, contract_info_bg_color VARCHAR(7) NOT NULL, contract_highlight_color VARCHAR(7) NOT NULL, contract_company_name VARCHAR(255) NOT NULL, contract_company_address LONGTEXT DEFAULT NULL, contract_logo_url LONGTEXT DEFAULT NULL, contract_section1_title VARCHAR(255) NOT NULL, contract_section2_title VARCHAR(255) NOT NULL, contract_section3_title VARCHAR(255) NOT NULL, contract_section4_title VARCHAR(255) NOT NULL, contract_section5_title VARCHAR(255) NOT NULL, contract_section6_title VARCHAR(255) NOT NULL, contract_section7_title VARCHAR(255) NOT NULL, contract_section8_title VARCHAR(255) NOT NULL, contract_landlord_title VARCHAR(255) NOT NULL, contract_tenant_title VARCHAR(255) NOT NULL, contract_signature_landlord_title VARCHAR(255) NOT NULL, contract_signature_tenant_title VARCHAR(255) NOT NULL, contract_signature_place VARCHAR(255) NOT NULL, contract_signature_landlord_text VARCHAR(255) NOT NULL, contract_signature_tenant_text VARCHAR(255) NOT NULL, contract_footer_text VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E8CACA3132C8A3DE (organization_id), INDEX IDX_E8CACA31979B1AD6 (company_id), UNIQUE INDEX UNIQ_CONTRACT_CONFIG_ORG_COMP (organization_id, company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `conversation` (id INT AUTO_INCREMENT NOT NULL, initiator_id INT NOT NULL, subject VARCHAR(255) NOT NULL, is_encrypted TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_message_at DATETIME DEFAULT NULL, INDEX IDX_8A8E26E97DB3B714 (initiator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversation_participants (conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_21821ED39AC0396 (conversation_id), INDEX IDX_21821ED3A76ED395 (user_id), PRIMARY KEY(conversation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(3) NOT NULL, name VARCHAR(100) NOT NULL, symbol VARCHAR(10) NOT NULL, exchange_rate NUMERIC(10, 6) NOT NULL, decimal_places INT NOT NULL, is_default TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, last_rate_update DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_6956883F77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, property_id INT DEFAULT NULL, tenant_id INT DEFAULT NULL, lease_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, maintenance_request_id INT DEFAULT NULL, inventory_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, file_name VARCHAR(255) NOT NULL, original_file_name VARCHAR(255) NOT NULL, mime_type VARCHAR(50) DEFAULT NULL, file_size INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, document_date DATE DEFAULT NULL, expiration_date DATE DEFAULT NULL, is_archived TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D8698A7632C8A3DE (organization_id), INDEX IDX_D8698A76979B1AD6 (company_id), INDEX IDX_D8698A76549213EC (property_id), INDEX IDX_D8698A769033212A (tenant_id), INDEX IDX_D8698A76D3CA542C (lease_id), INDEX IDX_D8698A767E3C61F9 (owner_id), INDEX IDX_D8698A766539382B (maintenance_request_id), INDEX IDX_D8698A769EEA759 (inventory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_template (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, html_content LONGTEXT NOT NULL, text_content LONGTEXT DEFAULT NULL, available_variables JSON DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_system TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, last_used_at DATETIME DEFAULT NULL, usage_count INT DEFAULT NULL, UNIQUE INDEX UNIQ_9C0600CA77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE environment (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, name VARCHAR(255) NOT NULL, subdomain VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, domain VARCHAR(255) DEFAULT NULL, configuration JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, last_deployed_at DATETIME DEFAULT NULL, deployment_log LONGTEXT DEFAULT NULL, version VARCHAR(255) DEFAULT NULL, ssl_enabled TINYINT(1) DEFAULT NULL, environment_variables JSON DEFAULT NULL, UNIQUE INDEX UNIQ_4626DE22C1D5962E (subdomain), INDEX IDX_4626DE2232C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expense (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, property_id INT DEFAULT NULL, description VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, category VARCHAR(100) NOT NULL, expense_date DATE NOT NULL, supplier VARCHAR(255) DEFAULT NULL, invoice_number VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_2D3A8DA632C8A3DE (organization_id), INDEX IDX_2D3A8DA6979B1AD6 (company_id), INDEX IDX_2D3A8DA6549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, lease_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, inventory_date DATE NOT NULL, performed_by VARCHAR(255) DEFAULT NULL, general_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_B12D4A36549213EC (property_id), INDEX IDX_B12D4A36D3CA542C (lease_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_item (id INT AUTO_INCREMENT NOT NULL, inventory_id INT NOT NULL, room VARCHAR(100) NOT NULL, category VARCHAR(100) NOT NULL, item VARCHAR(255) NOT NULL, `condition` VARCHAR(50) NOT NULL, quantity INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, estimated_value NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_55BDEA309EEA759 (inventory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lease (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, property_id INT NOT NULL, tenant_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, monthly_rent NUMERIC(10, 2) NOT NULL, charges NUMERIC(10, 2) DEFAULT NULL, deposit NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(50) NOT NULL, terms LONGTEXT DEFAULT NULL, rent_due_day INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_demo TINYINT(1) DEFAULT 0 NOT NULL, security_deposit NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_E6C7749532C8A3DE (organization_id), INDEX IDX_E6C77495979B1AD6 (company_id), INDEX IDX_E6C77495549213EC (property_id), INDEX IDX_E6C774959033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_request (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, tenant_id INT DEFAULT NULL, organization_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, category VARCHAR(100) NOT NULL, priority VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, requested_date DATE DEFAULT NULL, scheduled_date DATE DEFAULT NULL, completed_date DATE DEFAULT NULL, assigned_to VARCHAR(255) DEFAULT NULL, assigned_phone VARCHAR(20) DEFAULT NULL, assigned_email VARCHAR(255) DEFAULT NULL, estimated_cost NUMERIC(10, 2) DEFAULT NULL, actual_cost NUMERIC(10, 2) DEFAULT NULL, work_performed LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4261CA0D549213EC (property_id), INDEX IDX_4261CA0D9033212A (tenant_id), INDEX IDX_4261CA0D32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE menu_item (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, label VARCHAR(100) NOT NULL, menu_key VARCHAR(100) NOT NULL, icon VARCHAR(50) DEFAULT NULL, route VARCHAR(100) DEFAULT NULL, roles JSON NOT NULL, display_order INT NOT NULL, is_active TINYINT(1) NOT NULL, type VARCHAR(20) DEFAULT NULL, badge_type VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D754D5501231659B (menu_key), INDEX IDX_D754D550727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `message` (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, content LONGTEXT NOT NULL, is_encrypted TINYINT(1) NOT NULL, is_read TINYINT(1) NOT NULL, sent_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307FF624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE online_payment (id INT AUTO_INCREMENT NOT NULL, lease_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, advance_payment_id INT DEFAULT NULL, transaction_id VARCHAR(100) NOT NULL, payment_type VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, provider VARCHAR(50) NOT NULL, payment_method VARCHAR(50) NOT NULL, status VARCHAR(30) NOT NULL, customer_name VARCHAR(255) DEFAULT NULL, customer_phone VARCHAR(50) DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, payment_url LONGTEXT DEFAULT NULL, cinetpay_response LONGTEXT DEFAULT NULL, notification_data LONGTEXT DEFAULT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5CBE57F32FC0CB0F (transaction_id), INDEX IDX_5CBE57F3D3CA542C (lease_id), INDEX IDX_5CBE57F34C3A3BB (payment_id), INDEX IDX_5CBE57F350DC10C5 (advance_payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, active_subscription_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, country VARCHAR(50) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, tax_number VARCHAR(100) DEFAULT NULL, status VARCHAR(50) NOT NULL, settings JSON DEFAULT NULL, features JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, trial_ends_at DATE DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_demo TINYINT(1) DEFAULT 0 NOT NULL, subdomain VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_C1EE637C989D9B62 (slug), INDEX IDX_C1EE637C9A208144 (active_subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE owner (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, owner_type VARCHAR(50) DEFAULT NULL, siret VARCHAR(50) DEFAULT NULL, bank_account VARCHAR(255) DEFAULT NULL, commission_rate NUMERIC(5, 2) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_CF60E67CE7927C74 (email), INDEX IDX_CF60E67C32C8A3DE (organization_id), INDEX IDX_CF60E67C979B1AD6 (company_id), UNIQUE INDEX UNIQ_CF60E67CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, lease_id INT NOT NULL, due_date DATE NOT NULL, paid_date DATE DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, payment_method VARCHAR(100) DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, is_demo TINYINT(1) NOT NULL, INDEX IDX_6D28840D32C8A3DE (organization_id), INDEX IDX_6D28840D979B1AD6 (company_id), INDEX IDX_6D28840DD3CA542C (lease_id), UNIQUE INDEX unique_lease_date_type (lease_id, due_date, type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, monthly_price NUMERIC(10, 2) NOT NULL, yearly_price NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(10) NOT NULL, max_properties INT DEFAULT NULL, max_tenants INT DEFAULT NULL, max_users INT DEFAULT NULL, max_documents INT DEFAULT NULL, features JSON DEFAULT NULL, sort_order INT NOT NULL, is_active TINYINT(1) NOT NULL, is_popular TINYINT(1) NOT NULL, is_custom TINYINT(1) NOT NULL, trial_days INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_DD5A5B7D989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, postal_code VARCHAR(10) NOT NULL, property_type VARCHAR(50) NOT NULL, surface DOUBLE PRECISION NOT NULL, rooms INT NOT NULL, monthly_rent NUMERIC(10, 2) NOT NULL, charges NUMERIC(10, 2) DEFAULT NULL, deposit NUMERIC(10, 2) DEFAULT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, country VARCHAR(100) DEFAULT NULL, region VARCHAR(100) DEFAULT NULL, district VARCHAR(100) DEFAULT NULL, latitude NUMERIC(10, 6) DEFAULT NULL, longitude NUMERIC(10, 6) DEFAULT NULL, floor INT DEFAULT NULL, total_floors INT DEFAULT NULL, bedrooms INT DEFAULT NULL, bathrooms INT DEFAULT NULL, toilets INT DEFAULT NULL, balconies INT DEFAULT NULL, terrace_surface INT DEFAULT NULL, garden_surface INT DEFAULT NULL, parking_spaces INT DEFAULT NULL, garage_spaces INT DEFAULT NULL, cellar_surface INT DEFAULT NULL, attic_surface INT DEFAULT NULL, land_surface NUMERIC(10, 2) DEFAULT NULL, construction_year NUMERIC(10, 2) DEFAULT NULL, renovation_year NUMERIC(10, 2) DEFAULT NULL, heating_type VARCHAR(50) DEFAULT NULL, hot_water_type VARCHAR(50) DEFAULT NULL, energy_class VARCHAR(50) DEFAULT NULL, energy_consumption NUMERIC(10, 2) DEFAULT NULL, orientation VARCHAR(50) DEFAULT NULL, equipment LONGTEXT DEFAULT NULL, proximity LONGTEXT DEFAULT NULL, restrictions LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, photos JSON DEFAULT NULL, purchase_price NUMERIC(10, 2) DEFAULT NULL, purchase_date DATETIME DEFAULT NULL, estimated_value NUMERIC(10, 2) DEFAULT NULL, monthly_charges NUMERIC(10, 2) DEFAULT NULL, property_tax NUMERIC(10, 2) DEFAULT NULL, insurance NUMERIC(10, 2) DEFAULT NULL, maintenance_budget NUMERIC(10, 2) DEFAULT NULL, key_location VARCHAR(50) DEFAULT NULL, access_code VARCHAR(50) DEFAULT NULL, intercom VARCHAR(50) DEFAULT NULL, furnished TINYINT(1) DEFAULT NULL, pets_allowed TINYINT(1) DEFAULT NULL, smoking_allowed TINYINT(1) DEFAULT NULL, elevator TINYINT(1) DEFAULT NULL, has_balcony TINYINT(1) DEFAULT NULL, has_parking TINYINT(1) DEFAULT NULL, air_conditioning TINYINT(1) DEFAULT NULL, heating TINYINT(1) DEFAULT NULL, hot_water TINYINT(1) DEFAULT NULL, internet TINYINT(1) DEFAULT NULL, cable TINYINT(1) DEFAULT NULL, dishwasher TINYINT(1) DEFAULT NULL, washing_machine TINYINT(1) DEFAULT NULL, dryer TINYINT(1) DEFAULT NULL, refrigerator TINYINT(1) DEFAULT NULL, oven TINYINT(1) DEFAULT NULL, microwave TINYINT(1) DEFAULT NULL, stove TINYINT(1) DEFAULT NULL, is_demo TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, video_url VARCHAR(255) DEFAULT NULL, INDEX IDX_8BF21CDE32C8A3DE (organization_id), INDEX IDX_8BF21CDE979B1AD6 (company_id), INDEX IDX_8BF21CDE7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE property_user (property_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_928B6973549213EC (property_id), INDEX IDX_928B6973A76ED395 (user_id), PRIMARY KEY(property_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE settings (id INT AUTO_INCREMENT NOT NULL, setting_key VARCHAR(100) NOT NULL, setting_value LONGTEXT DEFAULT NULL, category VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, data_type VARCHAR(50) NOT NULL, is_editable TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_E545A0C55FA1E697 (setting_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, plan_id INT NOT NULL, status VARCHAR(50) NOT NULL, billing_cycle VARCHAR(50) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, next_billing_date DATE DEFAULT NULL, cancelled_at DATE DEFAULT NULL, trial_end_date DATE DEFAULT NULL, cancellation_reason VARCHAR(255) DEFAULT NULL, auto_renew TINYINT(1) DEFAULT NULL, payment_transaction_id VARCHAR(255) DEFAULT NULL, payment_method VARCHAR(50) DEFAULT NULL, last_payment_date DATETIME DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_A3C664D332C8A3DE (organization_id), INDEX IDX_A3C664D3E899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, frequency VARCHAR(100) NOT NULL, cron_expression VARCHAR(50) DEFAULT NULL, parameters JSON DEFAULT NULL, status VARCHAR(50) NOT NULL, last_run_at DATETIME DEFAULT NULL, next_run_at DATETIME DEFAULT NULL, run_count INT DEFAULT NULL, success_count INT DEFAULT NULL, failure_count INT DEFAULT NULL, last_error LONGTEXT DEFAULT NULL, result LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tenant (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, profession VARCHAR(100) DEFAULT NULL, monthly_income NUMERIC(10, 2) DEFAULT NULL, emergency_contact_name VARCHAR(255) DEFAULT NULL, emergency_contact_phone VARCHAR(20) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, is_demo TINYINT(1) DEFAULT 0 NOT NULL, status VARCHAR(50) DEFAULT NULL, UNIQUE INDEX UNIQ_4E59C462E7927C74 (email), INDEX IDX_4E59C46232C8A3DE (organization_id), INDEX IDX_4E59C462979B1AD6 (company_id), UNIQUE INDEX UNIQ_4E59C462A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tenant_application (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, visit_id INT DEFAULT NULL, reviewed_by_id INT DEFAULT NULL, organization_id INT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, birth_date DATE NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, current_address LONGTEXT DEFAULT NULL, employment_status VARCHAR(50) NOT NULL, employer VARCHAR(100) DEFAULT NULL, job_title VARCHAR(100) DEFAULT NULL, monthly_income NUMERIC(10, 2) NOT NULL, contract_type VARCHAR(50) DEFAULT NULL, has_guarantor TINYINT(1) NOT NULL, guarantor_name VARCHAR(100) DEFAULT NULL, guarantor_relation VARCHAR(100) DEFAULT NULL, guarantor_income NUMERIC(10, 2) DEFAULT NULL, number_of_occupants INT NOT NULL, number_of_children INT NOT NULL, has_pets TINYINT(1) NOT NULL, pet_details VARCHAR(255) DEFAULT NULL, desired_move_in_date DATE NOT NULL, desired_lease_duration INT DEFAULT NULL, additional_info LONGTEXT DEFAULT NULL, documents JSON NOT NULL, score NUMERIC(5, 2) DEFAULT NULL, score_details JSON DEFAULT NULL, status VARCHAR(20) NOT NULL, review_notes LONGTEXT DEFAULT NULL, reviewed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_7CAEB74D549213EC (property_id), UNIQUE INDEX UNIQ_7CAEB74D75FA0FF2 (visit_id), INDEX IDX_7CAEB74DFC6B21F1 (reviewed_by_id), INDEX IDX_7CAEB74D32C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, company_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, mobile_phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, birth_date DATE DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, consents JSON DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64932C8A3DE (organization_id), INDEX IDX_8D93D649979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE visit (id INT AUTO_INCREMENT NOT NULL, visit_slot_id INT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, message LONGTEXT DEFAULT NULL, email_sent TINYINT(1) NOT NULL, sms_sent TINYINT(1) NOT NULL, reminder_sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL, cancelled_at DATETIME DEFAULT NULL, cancellation_reason LONGTEXT DEFAULT NULL, confirmation_token VARCHAR(50) DEFAULT NULL, INDEX IDX_437EE939F6C155B (visit_slot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE visit_slot (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, organization_id INT NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, max_visitors INT NOT NULL, current_visitors INT NOT NULL, status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_7356DDA5549213EC (property_id), INDEX IDX_7356DDA532C8A3DE (organization_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942AF395DB7B FOREIGN KEY (expense_id) REFERENCES expense (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE advance_payment ADD CONSTRAINT FK_4245625FD3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F5979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094F32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_E8CACA3132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_E8CACA31979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE `conversation` ADD CONSTRAINT FK_8A8E26E97DB3B714 FOREIGN KEY (initiator_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_21821ED39AC0396 FOREIGN KEY (conversation_id) REFERENCES `conversation` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_21821ED3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A769033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A767E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A766539382B FOREIGN KEY (maintenance_request_id) REFERENCES maintenance_request (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A769EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id)');
        $this->addSql('ALTER TABLE environment ADD CONSTRAINT FK_4626DE2232C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA309EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C7749532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C774959033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE menu_item ADD CONSTRAINT FK_D754D550727ACA70 FOREIGN KEY (parent_id) REFERENCES menu_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `message` ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES `conversation` (id)');
        $this->addSql('ALTER TABLE `message` ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE online_payment ADD CONSTRAINT FK_5CBE57F3D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE online_payment ADD CONSTRAINT FK_5CBE57F34C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE online_payment ADD CONSTRAINT FK_5CBE57F350DC10C5 FOREIGN KEY (advance_payment_id) REFERENCES advance_payment (id)');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C9A208144 FOREIGN KEY (active_subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE owner ADD CONSTRAINT FK_CF60E67C32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE owner ADD CONSTRAINT FK_CF60E67C979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE owner ADD CONSTRAINT FK_CF60E67CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DD3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
        $this->addSql('ALTER TABLE property_user ADD CONSTRAINT FK_928B6973549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_user ADD CONSTRAINT FK_928B6973A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C46232C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE tenant_application ADD CONSTRAINT FK_7CAEB74D549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE tenant_application ADD CONSTRAINT FK_7CAEB74D75FA0FF2 FOREIGN KEY (visit_id) REFERENCES visit (id)');
        $this->addSql('ALTER TABLE tenant_application ADD CONSTRAINT FK_7CAEB74DFC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE tenant_application ADD CONSTRAINT FK_7CAEB74D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE939F6C155B FOREIGN KEY (visit_slot_id) REFERENCES visit_slot (id)');
        $this->addSql('ALTER TABLE visit_slot ADD CONSTRAINT FK_7356DDA5549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE visit_slot ADD CONSTRAINT FK_7356DDA532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A549213EC');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A7E3C61F9');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A4C3A3BB');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942AF395DB7B');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A32C8A3DE');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A979B1AD6');
        $this->addSql('ALTER TABLE advance_payment DROP FOREIGN KEY FK_4245625FD3CA542C');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5A76ED395');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F532C8A3DE');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F5979B1AD6');
        $this->addSql('ALTER TABLE company DROP FOREIGN KEY FK_4FBF094F32C8A3DE');
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_E8CACA3132C8A3DE');
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_E8CACA31979B1AD6');
        $this->addSql('ALTER TABLE `conversation` DROP FOREIGN KEY FK_8A8E26E97DB3B714');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_21821ED39AC0396');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_21821ED3A76ED395');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A7632C8A3DE');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76979B1AD6');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76549213EC');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A769033212A');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76D3CA542C');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A767E3C61F9');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A766539382B');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A769EEA759');
        $this->addSql('ALTER TABLE environment DROP FOREIGN KEY FK_4626DE2232C8A3DE');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA632C8A3DE');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6979B1AD6');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6549213EC');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A36549213EC');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A36D3CA542C');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA309EEA759');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C7749532C8A3DE');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495979B1AD6');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495549213EC');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C774959033212A');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D549213EC');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D9033212A');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D32C8A3DE');
        $this->addSql('ALTER TABLE menu_item DROP FOREIGN KEY FK_D754D550727ACA70');
        $this->addSql('ALTER TABLE `message` DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE `message` DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE online_payment DROP FOREIGN KEY FK_5CBE57F3D3CA542C');
        $this->addSql('ALTER TABLE online_payment DROP FOREIGN KEY FK_5CBE57F34C3A3BB');
        $this->addSql('ALTER TABLE online_payment DROP FOREIGN KEY FK_5CBE57F350DC10C5');
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C9A208144');
        $this->addSql('ALTER TABLE owner DROP FOREIGN KEY FK_CF60E67C32C8A3DE');
        $this->addSql('ALTER TABLE owner DROP FOREIGN KEY FK_CF60E67C979B1AD6');
        $this->addSql('ALTER TABLE owner DROP FOREIGN KEY FK_CF60E67CA76ED395');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D32C8A3DE');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D979B1AD6');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DD3CA542C');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE32C8A3DE');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE979B1AD6');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE7E3C61F9');
        $this->addSql('ALTER TABLE property_user DROP FOREIGN KEY FK_928B6973549213EC');
        $this->addSql('ALTER TABLE property_user DROP FOREIGN KEY FK_928B6973A76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D332C8A3DE');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C46232C8A3DE');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462979B1AD6');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462A76ED395');
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74D549213EC');
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74D75FA0FF2');
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74DFC6B21F1');
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74D32C8A3DE');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE939F6C155B');
        $this->addSql('ALTER TABLE visit_slot DROP FOREIGN KEY FK_7356DDA5549213EC');
        $this->addSql('ALTER TABLE visit_slot DROP FOREIGN KEY FK_7356DDA532C8A3DE');
        $this->addSql('DROP TABLE accounting_configuration');
        $this->addSql('DROP TABLE accounting_entry');
        $this->addSql('DROP TABLE advance_payment');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE contract_config');
        $this->addSql('DROP TABLE `conversation`');
        $this->addSql('DROP TABLE conversation_participants');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE email_template');
        $this->addSql('DROP TABLE environment');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE inventory');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('DROP TABLE lease');
        $this->addSql('DROP TABLE maintenance_request');
        $this->addSql('DROP TABLE menu_item');
        $this->addSql('DROP TABLE `message`');
        $this->addSql('DROP TABLE online_payment');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE owner');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE plan');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP TABLE property_user');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE tenant_application');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE visit');
        $this->addSql('DROP TABLE visit_slot');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
