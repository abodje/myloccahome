<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organization and company fields to accounting_entry table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounting_entry ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_ACCOUNTING_ENTRY_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE accounting_entry ADD CONSTRAINT FK_ACCOUNTING_ENTRY_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_ACCOUNTING_ENTRY_ORGANIZATION ON accounting_entry (organization_id)');
        $this->addSql('CREATE INDEX IDX_ACCOUNTING_ENTRY_COMPANY ON accounting_entry (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_ACCOUNTING_ENTRY_ORGANIZATION');
        $this->addSql('ALTER TABLE accounting_entry DROP FOREIGN KEY FK_ACCOUNTING_ENTRY_COMPANY');
        $this->addSql('DROP INDEX IDX_ACCOUNTING_ENTRY_ORGANIZATION ON accounting_entry');
        $this->addSql('DROP INDEX IDX_ACCOUNTING_ENTRY_COMPANY ON accounting_entry');
        $this->addSql('ALTER TABLE accounting_entry DROP organization_id, DROP company_id');
    }
}
