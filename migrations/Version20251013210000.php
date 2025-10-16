<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration intelligente pour Company avec vérifications d'existence
 */
final class Version20251013210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du système Company avec vérification d\'existence des colonnes';
    }

    public function up(Schema $schema): void
    {
        // Créer la table company
        $this->addSql('CREATE TABLE IF NOT EXISTS company (
            id INT AUTO_INCREMENT NOT NULL,
            organization_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            legal_name VARCHAR(255) DEFAULT NULL,
            registration_number VARCHAR(100) DEFAULT NULL,
            tax_number VARCHAR(255) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            address VARCHAR(255) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            postal_code VARCHAR(20) DEFAULT NULL,
            country VARCHAR(100) DEFAULT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            website VARCHAR(255) DEFAULT NULL,
            logo VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            is_headquarter TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX IDX_company_organization (organization_id),
            CONSTRAINT FK_company_organization
                FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS company');
    }
}

