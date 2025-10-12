<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012121127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE advance_payment (id INT AUTO_INCREMENT NOT NULL, lease_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, remaining_balance NUMERIC(10, 2) NOT NULL, paid_date DATETIME NOT NULL, payment_method VARCHAR(50) NOT NULL, reference VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4245625FD3CA542C (lease_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE advance_payment ADD CONSTRAINT FK_4245625FD3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE advance_payment DROP FOREIGN KEY FK_4245625FD3CA542C');
        $this->addSql('DROP TABLE advance_payment');
    }
}
