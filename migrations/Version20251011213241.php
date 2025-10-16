<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011213241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accounting_entry (id INT AUTO_INCREMENT NOT NULL, property_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, expense_id INT DEFAULT NULL, entry_date DATE NOT NULL, description VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, type VARCHAR(50) NOT NULL, category VARCHAR(100) NOT NULL, reference VARCHAR(255) DEFAULT NULL, running_balance NUMERIC(10, 2) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DB6C942A549213EC (property_id), INDEX IDX_DB6C942A7E3C61F9 (owner_id), INDEX IDX_DB6C942A4C3A3BB (payment_id), INDEX IDX_DB6C942AF395DB7B (expense_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, property_id INT DEFAULT NULL, tenant_id INT DEFAULT NULL, lease_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, maintenance_request_id INT DEFAULT NULL, inventory_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, file_name VARCHAR(255) NOT NULL, original_file_name VARCHAR(255) NOT NULL, mime_type VARCHAR(50) DEFAULT NULL, file_size INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, document_date DATE DEFAULT NULL, expiration_date DATE DEFAULT NULL, is_archived TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D8698A76549213EC (property_id), INDEX IDX_D8698A769033212A (tenant_id), INDEX IDX_D8698A76D3CA542C (lease_id), INDEX IDX_D8698A767E3C61F9 (owner_id), INDEX IDX_D8698A766539382B (maintenance_request_id), INDEX IDX_D8698A769EEA759 (inventory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expense (id INT AUTO_INCREMENT NOT NULL, property_id INT DEFAULT NULL, description VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, category VARCHAR(100) NOT NULL, expense_date DATE NOT NULL, supplier VARCHAR(255) DEFAULT NULL, invoice_number VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_2D3A8DA6549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, lease_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, inventory_date DATE NOT NULL, performed_by VARCHAR(255) DEFAULT NULL, general_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_B12D4A36549213EC (property_id), INDEX IDX_B12D4A36D3CA542C (lease_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE inventory_item (id INT AUTO_INCREMENT NOT NULL, inventory_id INT NOT NULL, room VARCHAR(100) NOT NULL, category VARCHAR(100) NOT NULL, item VARCHAR(255) NOT NULL, `condition` VARCHAR(50) NOT NULL, quantity INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, estimated_value NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_55BDEA309EEA759 (inventory_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lease (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, tenant_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, monthly_rent NUMERIC(10, 2) NOT NULL, charges NUMERIC(10, 2) DEFAULT NULL, deposit NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(50) NOT NULL, terms LONGTEXT DEFAULT NULL, rent_due_day INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_E6C77495549213EC (property_id), INDEX IDX_E6C774959033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_request (id INT AUTO_INCREMENT NOT NULL, property_id INT NOT NULL, tenant_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, category VARCHAR(100) NOT NULL, priority VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, requested_date DATE DEFAULT NULL, scheduled_date DATE DEFAULT NULL, completed_date DATE DEFAULT NULL, assigned_to VARCHAR(255) DEFAULT NULL, assigned_phone VARCHAR(20) DEFAULT NULL, assigned_email VARCHAR(255) DEFAULT NULL, estimated_cost NUMERIC(10, 2) DEFAULT NULL, actual_cost NUMERIC(10, 2) DEFAULT NULL, work_performed LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4261CA0D549213EC (property_id), INDEX IDX_4261CA0D9033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE owner (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, owner_type VARCHAR(50) DEFAULT NULL, siret VARCHAR(50) DEFAULT NULL, bank_account VARCHAR(255) DEFAULT NULL, commission_rate NUMERIC(5, 2) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_CF60E67CE7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, lease_id INT NOT NULL, due_date DATE NOT NULL, paid_date DATE DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, payment_method VARCHAR(100) DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_6D28840DD3CA542C (lease_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, postal_code VARCHAR(10) NOT NULL, property_type VARCHAR(50) NOT NULL, surface DOUBLE PRECISION NOT NULL, rooms INT NOT NULL, monthly_rent NUMERIC(10, 2) NOT NULL, charges NUMERIC(10, 2) DEFAULT NULL, deposit NUMERIC(10, 2) DEFAULT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_8BF21CDE7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tenant (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, profession VARCHAR(100) DEFAULT NULL, monthly_income NUMERIC(10, 2) DEFAULT NULL, emergency_contact_name VARCHAR(255) DEFAULT NULL, emergency_contact_phone VARCHAR(20) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_4E59C462E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942A4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_DB6C942AF395DB7B FOREIGN KEY (expense_id) REFERENCES expense (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A769033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A767E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A766539382B FOREIGN KEY (maintenance_request_id) REFERENCES maintenance_request (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A769EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id)');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA6549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA309EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C774959033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D549213EC FOREIGN KEY (property_id) REFERENCES property (id)');
        $this->addSql('ALTER TABLE maintenance_request ADD CONSTRAINT FK_4261CA0D9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DD3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES owner (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A549213EC');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A7E3C61F9');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942A4C3A3BB');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_DB6C942AF395DB7B');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76549213EC');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A769033212A');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76D3CA542C');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A767E3C61F9');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A766539382B');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A769EEA759');
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA6549213EC');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A36549213EC');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A36D3CA542C');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA309EEA759');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495549213EC');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C774959033212A');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D549213EC');
        $this->addSql('ALTER TABLE maintenance_request DROP FOREIGN KEY FK_4261CA0D9033212A');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DD3CA542C');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE7E3C61F9');
        $this->addSql('DROP TABLE accounting_entry');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE inventory');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('DROP TABLE lease');
        $this->addSql('DROP TABLE maintenance_request');
        $this->addSql('DROP TABLE owner');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
