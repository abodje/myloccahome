<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011225406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE owner ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE owner ADD CONSTRAINT FK_CF60E67CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CF60E67CA76ED395 ON owner (user_id)');
        $this->addSql('ALTER TABLE tenant ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E59C462A76ED395 ON tenant (user_id)');
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL, ADD is_active TINYINT(1) NOT NULL, DROP mobile_phone, DROP landline_phone, DROP address, DROP city, DROP postal_code, DROP country, DROP birth_date, DROP marital_status, DROP account_number, DROP preferred_payment_method, DROP consent_settings, DROP updated_at');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_identifier_email TO UNIQ_8D93D649E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE owner DROP FOREIGN KEY FK_CF60E67CA76ED395');
        $this->addSql('DROP INDEX UNIQ_CF60E67CA76ED395 ON owner');
        $this->addSql('ALTER TABLE owner DROP user_id');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462A76ED395');
        $this->addSql('DROP INDEX UNIQ_4E59C462A76ED395 ON tenant');
        $this->addSql('ALTER TABLE tenant DROP user_id');
        $this->addSql('ALTER TABLE `user` ADD landline_phone VARCHAR(20) DEFAULT NULL, ADD address VARCHAR(255) DEFAULT NULL, ADD city VARCHAR(100) DEFAULT NULL, ADD postal_code VARCHAR(10) DEFAULT NULL, ADD country VARCHAR(50) DEFAULT NULL, ADD birth_date DATE DEFAULT NULL, ADD marital_status VARCHAR(50) DEFAULT NULL, ADD account_number VARCHAR(100) DEFAULT NULL, ADD preferred_payment_method VARCHAR(100) DEFAULT NULL, ADD consent_settings JSON DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP is_active, CHANGE phone mobile_phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_IDENTIFIER_EMAIL');
    }
}
