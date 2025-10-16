<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016211639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C7749532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_E6C7749532C8A3DE ON lease (organization_id)');
        $this->addSql('CREATE INDEX IDX_E6C77495979B1AD6 ON lease (company_id)');
        $this->addSql('ALTER TABLE maintenance_request DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE organization ADD website VARCHAR(255) DEFAULT NULL, DROP registration_number');
        $this->addSql('ALTER TABLE owner ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE owner ADD CONSTRAINT FK_CF60E67C32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE owner ADD CONSTRAINT FK_CF60E67C979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_CF60E67C32C8A3DE ON owner (organization_id)');
        $this->addSql('CREATE INDEX IDX_CF60E67C979B1AD6 ON owner (company_id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D32C8A3DE ON payment (organization_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D979B1AD6 ON payment (company_id)');
        $this->addSql('DROP INDEX idx_user_marital_status ON user');
        $this->addSql('DROP INDEX idx_user_country ON user');
        $this->addSql('DROP INDEX idx_user_preferred_payment_method ON user');
        $this->addSql('ALTER TABLE user DROP country, DROP marital_status, DROP preferred_payment_method, DROP consents');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company DROP is_active');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C7749532C8A3DE');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495979B1AD6');
        $this->addSql('DROP INDEX IDX_E6C7749532C8A3DE ON lease');
        $this->addSql('DROP INDEX IDX_E6C77495979B1AD6 ON lease');
        $this->addSql('ALTER TABLE maintenance_request ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD registration_number VARCHAR(100) DEFAULT NULL COMMENT \'RCCM, SIRET, SIREN, etc.\', DROP website');
        $this->addSql('ALTER TABLE owner DROP FOREIGN KEY FK_CF60E67C32C8A3DE');
        $this->addSql('ALTER TABLE owner DROP FOREIGN KEY FK_CF60E67C979B1AD6');
        $this->addSql('DROP INDEX IDX_CF60E67C32C8A3DE ON owner');
        $this->addSql('DROP INDEX IDX_CF60E67C979B1AD6 ON owner');
        $this->addSql('ALTER TABLE owner DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D32C8A3DE');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D979B1AD6');
        $this->addSql('DROP INDEX IDX_6D28840D32C8A3DE ON payment');
        $this->addSql('DROP INDEX IDX_6D28840D979B1AD6 ON payment');
        $this->addSql('ALTER TABLE `user` ADD country VARCHAR(100) DEFAULT NULL, ADD marital_status VARCHAR(50) DEFAULT NULL, ADD preferred_payment_method VARCHAR(50) DEFAULT NULL, ADD consents JSON DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_user_marital_status ON `user` (marital_status)');
        $this->addSql('CREATE INDEX idx_user_country ON `user` (country)');
        $this->addSql('CREATE INDEX idx_user_preferred_payment_method ON `user` (preferred_payment_method)');
    }
}
