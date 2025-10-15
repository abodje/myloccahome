<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015131933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C7749532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE lease ADD CONSTRAINT FK_E6C77495979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_E6C7749532C8A3DE ON lease (organization_id)');
        $this->addSql('CREATE INDEX IDX_E6C77495979B1AD6 ON lease (company_id)');
        $this->addSql('ALTER TABLE maintenance_request DROP organization_id, DROP company_id');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D32C8A3DE ON payment (organization_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D979B1AD6 ON payment (company_id)');
        $this->addSql('ALTER TABLE property ADD country VARCHAR(100) DEFAULT NULL, ADD region VARCHAR(100) DEFAULT NULL, ADD district VARCHAR(100) DEFAULT NULL, ADD latitude NUMERIC(10, 6) DEFAULT NULL, ADD longitude NUMERIC(10, 6) DEFAULT NULL, ADD floor INT DEFAULT NULL, ADD total_floors INT DEFAULT NULL, ADD bedrooms INT DEFAULT NULL, ADD bathrooms INT DEFAULT NULL, ADD toilets INT DEFAULT NULL, ADD balconies INT DEFAULT NULL, ADD terrace INT DEFAULT NULL, ADD garden INT DEFAULT NULL, ADD parking_spaces INT DEFAULT NULL, ADD garage INT DEFAULT NULL, ADD cellar INT DEFAULT NULL, ADD attic INT DEFAULT NULL, ADD land_surface NUMERIC(10, 2) DEFAULT NULL, ADD construction_year NUMERIC(10, 2) DEFAULT NULL, ADD renovation_year NUMERIC(10, 2) DEFAULT NULL, ADD heating_type VARCHAR(50) DEFAULT NULL, ADD hot_water_type VARCHAR(50) DEFAULT NULL, ADD energy_class VARCHAR(50) DEFAULT NULL, ADD energy_consumption NUMERIC(10, 2) DEFAULT NULL, ADD orientation VARCHAR(50) DEFAULT NULL, ADD equipment LONGTEXT DEFAULT NULL, ADD proximity LONGTEXT DEFAULT NULL, ADD restrictions LONGTEXT DEFAULT NULL, ADD notes LONGTEXT DEFAULT NULL, ADD purchase_price NUMERIC(10, 2) DEFAULT NULL, ADD purchase_date DATETIME DEFAULT NULL, ADD estimated_value NUMERIC(10, 2) DEFAULT NULL, ADD monthly_charges NUMERIC(10, 2) DEFAULT NULL, ADD property_tax NUMERIC(10, 2) DEFAULT NULL, ADD insurance NUMERIC(10, 2) DEFAULT NULL, ADD maintenance_budget NUMERIC(10, 2) DEFAULT NULL, ADD key_location VARCHAR(50) DEFAULT NULL, ADD access_code VARCHAR(50) DEFAULT NULL, ADD intercom VARCHAR(50) DEFAULT NULL, ADD furnished TINYINT(1) DEFAULT NULL, ADD pets_allowed TINYINT(1) DEFAULT NULL, ADD smoking_allowed TINYINT(1) DEFAULT NULL, ADD elevator TINYINT(1) DEFAULT NULL, ADD has_balcony TINYINT(1) DEFAULT NULL, ADD has_parking TINYINT(1) DEFAULT NULL, ADD air_conditioning TINYINT(1) DEFAULT NULL, ADD heating TINYINT(1) DEFAULT NULL, ADD hot_water TINYINT(1) DEFAULT NULL, ADD internet TINYINT(1) DEFAULT NULL, ADD cable TINYINT(1) DEFAULT NULL, ADD dishwasher TINYINT(1) DEFAULT NULL, ADD washing_machine TINYINT(1) DEFAULT NULL, ADD dryer TINYINT(1) DEFAULT NULL, ADD refrigerator TINYINT(1) DEFAULT NULL, ADD oven TINYINT(1) DEFAULT NULL, ADD microwave TINYINT(1) DEFAULT NULL, ADD stove TINYINT(1) DEFAULT NULL');
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
        $this->addSql('ALTER TABLE property DROP country, DROP region, DROP district, DROP latitude, DROP longitude, DROP floor, DROP total_floors, DROP bedrooms, DROP bathrooms, DROP toilets, DROP balconies, DROP terrace, DROP garden, DROP parking_spaces, DROP garage, DROP cellar, DROP attic, DROP land_surface, DROP construction_year, DROP renovation_year, DROP heating_type, DROP hot_water_type, DROP energy_class, DROP energy_consumption, DROP orientation, DROP equipment, DROP proximity, DROP restrictions, DROP notes, DROP purchase_price, DROP purchase_date, DROP estimated_value, DROP monthly_charges, DROP property_tax, DROP insurance, DROP maintenance_budget, DROP key_location, DROP access_code, DROP intercom, DROP furnished, DROP pets_allowed, DROP smoking_allowed, DROP elevator, DROP has_balcony, DROP has_parking, DROP air_conditioning, DROP heating, DROP hot_water, DROP internet, DROP cable, DROP dishwasher, DROP washing_machine, DROP dryer, DROP refrigerator, DROP oven, DROP microwave, DROP stove');
        $this->addSql('ALTER TABLE property RENAME INDEX idx_8bf21cde979b1ad6 TO IDX_property_company');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C46232C8A3DE');
        $this->addSql('ALTER TABLE tenant DROP FOREIGN KEY FK_4E59C462979B1AD6');
        $this->addSql('DROP INDEX IDX_4E59C46232C8A3DE ON tenant');
        $this->addSql('ALTER TABLE tenant RENAME INDEX idx_4e59c462979b1ad6 TO IDX_tenant_company');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('ALTER TABLE `user` RENAME INDEX idx_8d93d649979b1ad6 TO IDX_user_company');
    }
}
