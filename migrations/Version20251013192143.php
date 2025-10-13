<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013192143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, active_subscription_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, country VARCHAR(50) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, tax_number VARCHAR(100) DEFAULT NULL, status VARCHAR(50) NOT NULL, settings JSON DEFAULT NULL, features JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, trial_ends_at DATE DEFAULT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_C1EE637C989D9B62 (slug), INDEX IDX_C1EE637C9A208144 (active_subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, monthly_price NUMERIC(10, 2) NOT NULL, yearly_price NUMERIC(10, 2) DEFAULT NULL, currency VARCHAR(10) NOT NULL, max_properties INT DEFAULT NULL, max_tenants INT DEFAULT NULL, max_users INT DEFAULT NULL, max_documents INT DEFAULT NULL, features JSON DEFAULT NULL, sort_order INT NOT NULL, is_active TINYINT(1) NOT NULL, is_popular TINYINT(1) NOT NULL, is_custom TINYINT(1) NOT NULL, trial_days INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_DD5A5B7D989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, organization_id INT NOT NULL, plan_id INT NOT NULL, status VARCHAR(50) NOT NULL, billing_cycle VARCHAR(50) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, next_billing_date DATE DEFAULT NULL, cancelled_at DATE DEFAULT NULL, cancellation_reason VARCHAR(255) DEFAULT NULL, auto_renew TINYINT(1) DEFAULT NULL, payment_transaction_id VARCHAR(255) DEFAULT NULL, payment_method VARCHAR(50) DEFAULT NULL, last_payment_date DATETIME DEFAULT NULL, metadata JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_A3C664D332C8A3DE (organization_id), INDEX IDX_A3C664D3E899029B (plan_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C9A208144 FOREIGN KEY (active_subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C9A208144');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D332C8A3DE');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE plan');
        $this->addSql('DROP TABLE subscription');
    }
}
