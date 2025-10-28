<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour le système de gestion des visites de propriétés
 */
final class Version20251028100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables pour la gestion des visites et candidatures locataires';
    }

    public function up(Schema $schema): void
    {
        // Création de la table visit_slot
        $this->addSql('CREATE TABLE visit_slot (
            id INT AUTO_INCREMENT NOT NULL,
            property_id INT NOT NULL,
            organization_id INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            max_visitors INT NOT NULL,
            current_visitors INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_7356DDA5549213EC (property_id),
            INDEX IDX_7356DDA532C8A3DE (organization_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Création de la table visit
        $this->addSql('CREATE TABLE visit (
            id INT AUTO_INCREMENT NOT NULL,
            visit_slot_id INT NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            message LONGTEXT DEFAULT NULL,
            email_sent TINYINT(1) NOT NULL,
            sms_sent TINYINT(1) NOT NULL,
            reminder_sent TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            confirmed_at DATETIME DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            cancellation_reason LONGTEXT DEFAULT NULL,
            confirmation_token VARCHAR(50) DEFAULT NULL,
            INDEX IDX_437EE939F6C155B (visit_slot_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Création de la table tenant_application
        $this->addSql('CREATE TABLE tenant_application (
            id INT AUTO_INCREMENT NOT NULL,
            property_id INT NOT NULL,
            visit_id INT DEFAULT NULL,
            reviewed_by_id INT DEFAULT NULL,
            organization_id INT NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            birth_date DATE NOT NULL,
            email VARCHAR(180) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            current_address LONGTEXT DEFAULT NULL,
            employment_status VARCHAR(50) NOT NULL,
            employer VARCHAR(100) DEFAULT NULL,
            job_title VARCHAR(100) DEFAULT NULL,
            monthly_income NUMERIC(10, 2) NOT NULL,
            contract_type VARCHAR(50) DEFAULT NULL,
            has_guarantor TINYINT(1) NOT NULL,
            guarantor_name VARCHAR(100) DEFAULT NULL,
            guarantor_relation VARCHAR(100) DEFAULT NULL,
            guarantor_income NUMERIC(10, 2) DEFAULT NULL,
            number_of_occupants INT NOT NULL,
            number_of_children INT NOT NULL,
            has_pets TINYINT(1) NOT NULL,
            pet_details VARCHAR(255) DEFAULT NULL,
            desired_move_in_date DATE NOT NULL,
            desired_lease_duration INT DEFAULT NULL,
            additional_info LONGTEXT DEFAULT NULL,
            documents JSON NOT NULL,
            score NUMERIC(5, 2) DEFAULT NULL,
            score_details JSON DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            review_notes LONGTEXT DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_7CAEB74D549213EC (property_id),
            UNIQUE INDEX UNIQ_7CAEB74D75FA0FF2 (visit_id),
            INDEX IDX_7CAEB74DFC6B21F1 (reviewed_by_id),
            INDEX IDX_7CAEB74D32C8A3DE (organization_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajout des contraintes de clés étrangères
        $this->addSql('ALTER TABLE visit_slot
            ADD CONSTRAINT FK_7356DDA5549213EC
            FOREIGN KEY (property_id) REFERENCES property (id)');

        $this->addSql('ALTER TABLE visit_slot
            ADD CONSTRAINT FK_7356DDA532C8A3DE
            FOREIGN KEY (organization_id) REFERENCES organization (id)');

        $this->addSql('ALTER TABLE visit
            ADD CONSTRAINT FK_437EE939F6C155B
            FOREIGN KEY (visit_slot_id) REFERENCES visit_slot (id)');

        $this->addSql('ALTER TABLE tenant_application
            ADD CONSTRAINT FK_7CAEB74D549213EC
            FOREIGN KEY (property_id) REFERENCES property (id)');

        $this->addSql('ALTER TABLE tenant_application
            ADD CONSTRAINT FK_7CAEB74D75FA0FF2
            FOREIGN KEY (visit_id) REFERENCES visit (id)');

        $this->addSql('ALTER TABLE tenant_application
            ADD CONSTRAINT FK_7CAEB74DFC6B21F1
            FOREIGN KEY (reviewed_by_id) REFERENCES `user` (id)');

        $this->addSql('ALTER TABLE tenant_application
            ADD CONSTRAINT FK_7CAEB74D32C8A3DE
            FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression des contraintes de clés étrangères
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74D549213EC');
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74D75FA0FF2');
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74DFC6B21F1');
        $this->addSql('ALTER TABLE tenant_application DROP FOREIGN KEY FK_7CAEB74D32C8A3DE');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE939F6C155B');
        $this->addSql('ALTER TABLE visit_slot DROP FOREIGN KEY FK_7356DDA5549213EC');
        $this->addSql('ALTER TABLE visit_slot DROP FOREIGN KEY FK_7356DDA532C8A3DE');

        // Suppression des tables
        $this->addSql('DROP TABLE tenant_application');
        $this->addSql('DROP TABLE visit');
        $this->addSql('DROP TABLE visit_slot');
    }
}
