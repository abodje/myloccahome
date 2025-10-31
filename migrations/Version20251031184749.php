<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031184749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_F6E46E5A3AE0AB8 ON accounting_configuration');
        $this->addSql('ALTER TABLE accounting_configuration ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE accounting_configuration ADD CONSTRAINT FK_F6E46E532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE accounting_configuration ADD CONSTRAINT FK_F6E46E5979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_F6E46E532C8A3DE ON accounting_configuration (organization_id)');
        $this->addSql('CREATE INDEX IDX_F6E46E5979B1AD6 ON accounting_configuration (company_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACCOUNTING_CONFIG_ORG_COMP_OP ON accounting_configuration (organization_id, company_id, operation_type)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_ACCOUNTING_CONFIG_ORG_COMP_OP ON accounting_configuration');
        $this->addSql('ALTER TABLE accounting_configuration DROP FOREIGN KEY FK_F6E46E532C8A3DE');
        $this->addSql('ALTER TABLE accounting_configuration DROP FOREIGN KEY FK_F6E46E5979B1AD6');
        $this->addSql('DROP INDEX IDX_F6E46E532C8A3DE ON accounting_configuration');
        $this->addSql('DROP INDEX IDX_F6E46E5979B1AD6 ON accounting_configuration');
        $this->addSql('ALTER TABLE accounting_configuration DROP organization_id, DROP company_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6E46E5A3AE0AB8 ON accounting_configuration (operation_type)');
    }
}
