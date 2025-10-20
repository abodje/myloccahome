<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accounting_configuration table for professional accounting setup';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accounting_configuration (
            id INT AUTO_INCREMENT NOT NULL,
            operation_type VARCHAR(100) NOT NULL,
            account_number VARCHAR(20) NOT NULL,
            account_label VARCHAR(255) NOT NULL,
            entry_type VARCHAR(10) NOT NULL,
            description VARCHAR(255) NOT NULL,
            reference VARCHAR(255) DEFAULT NULL,
            category VARCHAR(100) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_ACCOUNTING_CONFIG_OPERATION_TYPE (operation_type),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE accounting_configuration');
    }
}
