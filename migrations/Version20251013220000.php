<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration en 2 étapes pour Company
 * Étape 1: Ajouter les colonnes SANS contraintes FK
 */
final class Version20251013220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes organization_id et company_id SANS contraintes';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les colonnes organization_id et company_id aux tables (sans FK)
        $this->addSql('ALTER TABLE property ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tenant ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE lease ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE expense ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');

        // Ajouter les index
        $this->addSql('CREATE INDEX IDX_property_organization ON property (organization_id)');
        $this->addSql('CREATE INDEX IDX_property_company ON property (company_id)');
        $this->addSql('CREATE INDEX IDX_tenant_organization ON tenant (organization_id)');
        $this->addSql('CREATE INDEX IDX_tenant_company ON tenant (company_id)');
        $this->addSql('CREATE INDEX IDX_lease_organization ON lease (organization_id)');
        $this->addSql('CREATE INDEX IDX_lease_company ON lease (company_id)');
        $this->addSql('CREATE INDEX IDX_payment_organization ON payment (organization_id)');
        $this->addSql('CREATE INDEX IDX_payment_company ON payment (company_id)');
        $this->addSql('CREATE INDEX IDX_user_company ON user (company_id)');
        $this->addSql('CREATE INDEX IDX_expense_organization ON expense (organization_id)');
        $this->addSql('CREATE INDEX IDX_expense_company ON expense (company_id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les index
        $this->addSql('DROP INDEX IDX_property_organization ON property');
        $this->addSql('DROP INDEX IDX_property_company ON property');
        $this->addSql('DROP INDEX IDX_tenant_organization ON tenant');
        $this->addSql('DROP INDEX IDX_tenant_company ON tenant');
        $this->addSql('DROP INDEX IDX_lease_organization ON lease');
        $this->addSql('DROP INDEX IDX_lease_company ON lease');
        $this->addSql('DROP INDEX IDX_payment_organization ON payment');
        $this->addSql('DROP INDEX IDX_payment_company ON payment');
        $this->addSql('DROP INDEX IDX_user_company ON user');
        $this->addSql('DROP INDEX IDX_expense_organization ON expense');
        $this->addSql('DROP INDEX IDX_expense_company ON expense');

        // Supprimer les colonnes
        $this->addSql('ALTER TABLE property DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE tenant DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE lease DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE payment DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE user DROP company_id');
        $this->addSql('ALTER TABLE expense DROP organization_id, DROP company_id');
    }
}

