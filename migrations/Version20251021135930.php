<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251021135930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        $this->addSql('ALTER TABLE task CHANGE parameters parameters JSON DEFAULT NULL');
        $this->addSql('DROP INDEX idx_user_marital_status ON user');
        $this->addSql('DROP INDEX idx_user_country ON user');
        $this->addSql('DROP INDEX idx_user_preferred_payment_method ON user');
        $this->addSql('ALTER TABLE user DROP country, DROP marital_status, DROP preferred_payment_method, DROP consents, CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D332C8A3DE');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE task CHANGE parameters parameters LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('ALTER TABLE `user` ADD country VARCHAR(100) DEFAULT NULL, ADD marital_status VARCHAR(50) DEFAULT NULL, ADD preferred_payment_method VARCHAR(50) DEFAULT NULL, ADD consents LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('CREATE INDEX idx_user_marital_status ON `user` (marital_status)');
        $this->addSql('CREATE INDEX idx_user_country ON `user` (country)');
        $this->addSql('CREATE INDEX idx_user_preferred_payment_method ON `user` (preferred_payment_method)');
    }
}
