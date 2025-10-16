<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251013232223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounting_entry DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A7632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_D8698A7632C8A3DE ON document (organization_id)');
        $this->addSql('CREATE INDEX IDX_D8698A76979B1AD6 ON document (company_id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C7749532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_E6C7749532C8A3DE ON lease (organization_id)');
        $this->addSql('CREATE INDEX IDX_E6C77495979B1AD6 ON lease (company_id)');
        $this->addSql('ALTER TABLE maintenance_request DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D32C8A3DE ON payment (organization_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D979B1AD6 ON payment (company_id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_8BF21CDE32C8A3DE ON property (organization_id)');
        $this->addSql('ALTER TABLE property RENAME INDEX idx_property_company TO IDX_8BF21CDE979B1AD6');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C46232C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE tenant ADD CONSTRAINT FK_4E59C462979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_4E59C46232C8A3DE ON tenant (organization_id)');
        $this->addSql('ALTER TABLE tenant RENAME INDEX idx_tenant_company TO IDX_4E59C462979B1AD6');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE user RENAME INDEX idx_user_company TO IDX_8D93D649979B1AD6');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounting_entry ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A7632C8A3DE');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76979B1AD6');
        $this->addSql('DROP INDEX IDX_D8698A7632C8A3DE ON document');
        $this->addSql('DROP INDEX IDX_D8698A76979B1AD6 ON document');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C7749532C8A3DE');
        $this->addSql('ALTER TABLE lease DROP FOREIGN KEY FK_E6C77495979B1AD6');
        $this->addSql('DROP INDEX IDX_E6C7749532C8A3DE ON lease');
        $this->addSql('DROP INDEX IDX_E6C77495979B1AD6 ON lease');
        $this->addSql('ALTER TABLE maintenance_request ADD organization_id INT DEFAULT NULL, ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D32C8A3DE');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D979B1AD6');
        $this->addSql('DROP INDEX IDX_6D28840D32C8A3DE ON payment');
        $this->addSql('DROP INDEX IDX_6D28840D979B1AD6 ON payment');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE32C8A3DE');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE979B1AD6');
        $this->addSql('DROP INDEX IDX_8BF21CDE32C8A3DE ON property');
        $this->addSql('ALTER TABLE property RENAME INDEX idx_8bf21cde979b1ad6 TO IDX_property_company');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C46232C8A3DE');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462979B1AD6');
        $this->addSql('DROP INDEX IDX_4E59C46232C8A3DE ON tenant');
        $this->addSql('ALTER TABLE tenant RENAME INDEX idx_4e59c462979b1ad6 TO IDX_tenant_company');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('ALTER TABLE `user` RENAME INDEX idx_8d93d649979b1ad6 TO IDX_user_company');
    }
}
