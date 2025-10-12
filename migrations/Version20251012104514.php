<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012104514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE menu_item (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, label VARCHAR(100) NOT NULL, menu_key VARCHAR(100) NOT NULL, icon VARCHAR(50) DEFAULT NULL, route VARCHAR(100) DEFAULT NULL, roles JSON NOT NULL, display_order INT NOT NULL, is_active TINYINT(1) NOT NULL, type VARCHAR(20) DEFAULT NULL, badge_type VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D754D5501231659B (menu_key), INDEX IDX_D754D550727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE menu_item ADD CONSTRAINT FK_D754D550727ACA70 FOREIGN KEY (parent_id) REFERENCES menu_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_item DROP FOREIGN KEY FK_D754D550727ACA70');
        $this->addSql('DROP TABLE menu_item');
    }
}
