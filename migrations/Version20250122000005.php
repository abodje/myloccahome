<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250122000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organization and company relations to PropertyTax entity';
    }

    public function up(Schema $schema): void
    {
        // Add organization_id column to property_tax table
        $this->addSql('ALTER TABLE property_tax ADD organization_id INT NOT NULL');
        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_PROPERTY_TAX_ORGANIZATION ON property_tax (organization_id)');

        // Add company_id column to property_tax table
        $this->addSql('ALTER TABLE property_tax ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE property_tax ADD CONSTRAINT FK_PROPERTY_TAX_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_PROPERTY_TAX_COMPANY ON property_tax (company_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove company_id column from property_tax table
        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_COMPANY');
        $this->addSql('DROP INDEX IDX_PROPERTY_TAX_COMPANY ON property_tax');
        $this->addSql('ALTER TABLE property_tax DROP COLUMN company_id');

        // Remove organization_id column from property_tax table
        $this->addSql('ALTER TABLE property_tax DROP FOREIGN KEY FK_PROPERTY_TAX_ORGANIZATION');
        $this->addSql('DROP INDEX IDX_PROPERTY_TAX_ORGANIZATION ON property_tax');
        $this->addSql('ALTER TABLE property_tax DROP COLUMN organization_id');
    }
}
