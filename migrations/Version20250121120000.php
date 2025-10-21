<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create contract_config table for multi-tenant contract configuration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contract_config (
            id INT AUTO_INCREMENT NOT NULL,
            organization_id INT NOT NULL,
            company_id INT DEFAULT NULL,
            contract_language VARCHAR(10) NOT NULL DEFAULT \'fr\',
            contract_title VARCHAR(255) NOT NULL DEFAULT \'Contrat de Bail\',
            contract_main_title VARCHAR(255) NOT NULL DEFAULT \'CONTRAT DE BAIL D\\\'HABITATION\',
            contract_font_family VARCHAR(255) NOT NULL DEFAULT \'DejaVu Sans, sans-serif\',
            contract_font_size VARCHAR(10) NOT NULL DEFAULT \'11pt\',
            contract_line_height DECIMAL(3,1) NOT NULL DEFAULT \'1.6\',
            contract_text_color VARCHAR(7) NOT NULL DEFAULT \'#333\',
            contract_margin VARCHAR(10) NOT NULL DEFAULT \'40px\',
            contract_title_size VARCHAR(10) NOT NULL DEFAULT \'24pt\',
            contract_label_width VARCHAR(10) NOT NULL DEFAULT \'180px\',
            contract_primary_color VARCHAR(7) NOT NULL DEFAULT \'#0066cc\',
            contract_info_bg_color VARCHAR(7) NOT NULL DEFAULT \'#f5f5f5\',
            contract_highlight_color VARCHAR(7) NOT NULL DEFAULT \'#f0f8ff\',
            contract_company_name VARCHAR(255) NOT NULL DEFAULT \'MYLOCCA Gestion\',
            contract_company_address LONGTEXT DEFAULT NULL,
            contract_logo_url LONGTEXT DEFAULT NULL,
            contract_section_1_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 1 : LES PARTIES\',
            contract_section_2_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 2 : DÉSIGNATION DU BIEN LOUÉ\',
            contract_section_3_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 3 : DURÉE DU BAIL\',
            contract_section_4_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 4 : LOYER ET CHARGES\',
            contract_section_5_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 5 : DÉPÔT DE GARANTIE\',
            contract_section_6_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 6 : OBLIGATIONS DU LOCATAIRE\',
            contract_section_7_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 7 : OBLIGATIONS DU BAILLEUR\',
            contract_section_8_title VARCHAR(255) NOT NULL DEFAULT \'ARTICLE 8 : CLAUSE RÉSOLUTOIRE\',
            contract_landlord_title VARCHAR(255) NOT NULL DEFAULT \'LE BAILLEUR\',
            contract_tenant_title VARCHAR(255) NOT NULL DEFAULT \'LE LOCATAIRE\',
            contract_signature_landlord_title VARCHAR(255) NOT NULL DEFAULT \'Le Bailleur\',
            contract_signature_tenant_title VARCHAR(255) NOT NULL DEFAULT \'Le Locataire\',
            contract_signature_place VARCHAR(255) NOT NULL DEFAULT \'Fait à ____________\',
            contract_signature_landlord_text VARCHAR(255) NOT NULL DEFAULT \'Signature\',
            contract_signature_tenant_text VARCHAR(255) NOT NULL DEFAULT \'Signature précédée de la mention "Lu et approuvé"\',
            contract_footer_text VARCHAR(255) NOT NULL DEFAULT \'Document généré le\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_CONTRACT_CONFIG_ORGANIZATION (organization_id),
            INDEX IDX_CONTRACT_CONFIG_COMPANY (company_id),
            UNIQUE INDEX UNIQ_CONTRACT_CONFIG_ORG_COMP (organization_id, company_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_CONTRACT_CONFIG_ORGANIZATION FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE contract_config ADD CONSTRAINT FK_CONTRACT_CONFIG_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_CONTRACT_CONFIG_ORGANIZATION');
        $this->addSql('ALTER TABLE contract_config DROP FOREIGN KEY FK_CONTRACT_CONFIG_COMPANY');
        $this->addSql('DROP TABLE contract_config');
    }
}

